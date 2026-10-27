<?php

declare(strict_types=1);

namespace CraftCms\Cms\Ai;

use Closure;
use CraftCms\Cms\Ai\Agents\AttachmentProbe;
use CraftCms\Cms\Ai\Agents\StructuredOutputProbe;
use CraftCms\Cms\Ai\Agents\ToolCallingProbe;
use CraftCms\Cms\Ai\Data\CapabilityCheck;
use CraftCms\Cms\Ai\Data\ResolvedProvider;
use CraftCms\Cms\Ai\Enums\Capability;
use CraftCms\Cms\Support\Str;
use Illuminate\Container\Attributes\Singleton;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Cache;
use Laravel\Ai\AiManager;
use Laravel\Ai\Contracts\Providers\TextProvider;
use Laravel\Ai\Exceptions\FailoverableException;
use Laravel\Ai\Files\Audio;
use Laravel\Ai\Files\Document;
use Laravel\Ai\Files\File;
use Laravel\Ai\Files\Image;
use Laravel\Ai\Files\Video;
use Laravel\Ai\Responses\StructuredAgentResponse;
use Throwable;

use function CraftCms\Cms\enum_value;

/**
 * Checks whether providers’ models have the {@see Capability capabilities} that tasks need.
 *
 * `laravel/ai` doesn’t know what each model supports, so Craft asks the model. For vision, it sends a small image
 * filled with one color and asks which color it is. Documents, audio, and video are checked the same way, with a PDF
 * with a word on it, a few beeps, and a video filled with one color. The files are in `resources/ai/probes`. For structured output, it asks for a word and a
 * number in a JSON schema. For tool calling, it gives the model a tool that returns a code, and asks for the code. Results are cached. A model that answers correctly, or wrongly,
 * is cached indefinitely. Other errors are cached for a day, and checked again the next time the AI settings are
 * saved. Errors that can be fixed right away, such as the provider being unreachable or rejecting its API key, aren’t
 * cached, though the model is still checked again the next time the AI settings are saved.
 *
 * @since 6.0.0
 */
#[Singleton]
class CapabilityChecks
{
    private const string CACHE_PREFIX = 'craft:ai:capability:';

    private const int ERROR_TTL = 86400;

    private const int TIMEOUT = 30;

    public function __construct(private readonly AiManager $aiManager) {}

    /**
     * Returns the cached result for a provider’s model, or `null` if it hasn’t been checked.
     */
    public function result(Capability $capability, ResolvedProvider $resolved): ?CapabilityCheck
    {
        $result = Cache::get($this->cacheKey($capability, $resolved));

        if (! is_array($result) || ! array_key_exists('supported', $result)) {
            return null;
        }

        return new CapabilityCheck(
            $capability,
            $resolved->provider,
            (string) ($result['model'] ?? ''),
            is_bool($result['supported']) ? $result['supported'] : null,
            is_string($result['error'] ?? null) ? $result['error'] : null,
        );
    }

    /**
     * Checks a provider’s model for a capability, and caches the result.
     */
    public function check(Capability $capability, ResolvedProvider $resolved): CapabilityCheck
    {
        $result = match ($capability) {
            Capability::Vision => $this->checkVision($resolved),
            Capability::DocumentInput => $this->checkDocumentInput($resolved),
            Capability::AudioInput => $this->checkAudioInput($resolved),
            Capability::VideoInput => $this->checkVideoInput($resolved),
            Capability::StructuredOutput => $this->checkStructuredOutput($resolved),
            Capability::ToolCalling => $this->checkToolCalling($resolved),
        };

        $key = $this->cacheKey($capability, $resolved);
        $value = ['model' => $result->model, 'supported' => $result->supported, 'error' => $result->error];

        if ($result->transient) {
            // Remember that the model still needs checking, but not why, since the problem may be fixed any moment
            Cache::forever($key, [...$value, 'error' => null]);
        } elseif ($result->supported === null) {
            Cache::put($key, $value, self::ERROR_TTL);
        } else {
            Cache::forever($key, $value);
        }

        return $result;
    }

    private function checkVision(ResolvedProvider $resolved): CapabilityCheck
    {
        [$path, $color, $colors] = self::probeFile('image', 'png');

        return $this->checkAttachment(
            Capability::Vision,
            $resolved,
            'Which color fills the attached image? Answer with one of: '.implode(', ', $colors).'.',
            Image::fromPath($path, 'image/png'),
            static fn (string $answer): bool => self::named($answer, $colors) === [$color],
        );
    }

    private function checkDocumentInput(ResolvedProvider $resolved): CapabilityCheck
    {
        [$path, $word] = self::probeFile('document', 'pdf');

        return $this->checkAttachment(
            Capability::DocumentInput,
            $resolved,
            'Which word is written in the attached PDF? Answer with that word.',
            Document::fromPath($path),
            static fn (string $answer): bool => self::named($answer, [$word]) === [$word],
        );
    }

    private function checkAudioInput(ResolvedProvider $resolved): CapabilityCheck
    {
        [$path, $beeps] = self::probeFile('audio', 'wav');
        $words = ['1' => 'one', '2' => 'two', '3' => 'three', '4' => 'four'];

        return $this->checkAttachment(
            Capability::AudioInput,
            $resolved,
            'How many beeps are in the attached audio? Answer with a number.',
            Audio::fromPath($path, 'audio/wav'),
            static function (string $answer) use ($beeps, $words): bool {
                $counts = array_map(
                    static fn (string $named): string => (string) (array_search($named, $words, true) ?: $named),
                    self::named($answer, [...array_map(strval(...), array_keys($words)), ...array_values($words)]),
                );

                return array_values(array_unique($counts)) === [$beeps];
            },
        );
    }

    private function checkVideoInput(ResolvedProvider $resolved): CapabilityCheck
    {
        [$path, $color, $colors] = self::probeFile('video', 'mp4');

        return $this->checkAttachment(
            Capability::VideoInput,
            $resolved,
            'Which color fills the attached video? Answer with one of: '.implode(', ', $colors).'.',
            Video::fromPath($path, 'video/mp4'),
            static fn (string $answer): bool => self::named($answer, $colors) === [$color],
        );
    }

    /**
     * Asks a model about an attached file.
     *
     * @param  Closure(string): bool  $isCorrect  Whether the lowercased answer is right.
     */
    private function checkAttachment(Capability $capability, ResolvedProvider $resolved, string $question, File $attachment, Closure $isCorrect): CapabilityCheck
    {
        try {
            $response = new AttachmentProbe()->prompt($question, [$attachment], $resolved->provider, $resolved->model, self::TIMEOUT);
        } catch (Throwable $exception) {
            return $this->failedResult($capability, $resolved, $exception);
        }

        return $this->checkResult($capability, $resolved, $isCorrect(mb_strtolower($response->text)));
    }

    /**
     * Picks a random probe file of a kind from `resources/ai/probes`. Each file is named after its answer, such as
     * `red.png` for an image filled with red.
     *
     * @return array{string, string, list<string>} The file’s path, its answer, and the answers of every file of the kind.
     */
    private static function probeFile(string $kind, string $extension): array
    {
        $paths = glob(dirname(__DIR__, 2)."/resources/ai/probes/$kind/*.$extension") ?: [];
        $path = $paths[array_rand($paths)];

        return [$path, basename($path, ".$extension"), array_map(static fn (string $path): string => basename($path, ".$extension"), $paths)];
    }

    /**
     * Returns the words an answer names, out of the given ones.
     *
     * @param  list<string>  $words
     * @return list<string>
     */
    private static function named(string $answer, array $words): array
    {
        return array_values(array_filter($words, static fn (string $word): bool => preg_match('/\\b'.preg_quote($word, '/').'\\b/', $answer) === 1));
    }

    private function checkStructuredOutput(ResolvedProvider $resolved): CapabilityCheck
    {
        try {
            $response = new StructuredOutputProbe()->prompt(
                'Return the word "lighthouse" as word, and the number 42 as number.',
                provider: $resolved->provider,
                model: $resolved->model,
                timeout: self::TIMEOUT,
            );
        } catch (Throwable $exception) {
            return $this->failedResult(Capability::StructuredOutput, $resolved, $exception);
        }

        $data = $response instanceof StructuredAgentResponse ? $response->toArray() : [];

        return $this->checkResult(Capability::StructuredOutput, $resolved, ($data['word'] ?? null) === 'lighthouse' && ($data['number'] ?? null) === 42);
    }

    private function checkToolCalling(ResolvedProvider $resolved): CapabilityCheck
    {
        $code = Str::random(12);

        try {
            $response = new ToolCallingProbe($code)->prompt(
                'Call the tool to get the verification code, then reply with the code.',
                provider: $resolved->provider,
                model: $resolved->model,
                timeout: self::TIMEOUT,
            );
        } catch (Throwable $exception) {
            return $this->failedResult(Capability::ToolCalling, $resolved, $exception);
        }

        return $this->checkResult(Capability::ToolCalling, $resolved, str_contains($response->text, $code));
    }

    private function checkResult(Capability $capability, ResolvedProvider $resolved, ?bool $supported): CapabilityCheck
    {
        return new CapabilityCheck($capability, $resolved->provider, $this->modelName($resolved), $supported);
    }

    private function failedResult(Capability $capability, ResolvedProvider $resolved, Throwable $exception): CapabilityCheck
    {
        return new CapabilityCheck(
            $capability,
            $resolved->provider,
            $this->modelName($resolved),
            null,
            $exception->getMessage(),
            self::isTransient($exception),
        );
    }

    /**
     * Returns whether a request failed for a reason that can be fixed right away, rather than because of the model:
     * the provider couldn’t be reached, rejected the API key, was rate limited or overloaded, or had a server error.
     */
    private static function isTransient(Throwable $exception): bool
    {
        if ($exception instanceof FailoverableException || $exception instanceof ConnectionException) {
            return true;
        }

        $status = $exception instanceof RequestException ? $exception->response->status() : null;

        return in_array($status, [401, 403, 408], true) || ($status !== null && $status >= 500);
    }

    /**
     * Identifies a model by its provider’s driver and URL, so results don’t outlive a change of either.
     */
    private function cacheKey(Capability $capability, ResolvedProvider $resolved): string
    {
        $config = config("ai.providers.{$resolved->provider}", []);
        $driver = $config['driver'] ?? null;

        return self::CACHE_PREFIX.md5(implode("\0", [
            $capability->value,
            $resolved->provider,
            (string) enum_value($driver),
            (string) ($config['url'] ?? ''),
            $this->modelName($resolved),
        ]));
    }

    /**
     * Returns the model’s name, using the provider’s default text model when a task doesn’t choose one.
     */
    private function modelName(ResolvedProvider $resolved): string
    {
        if ($resolved->model !== null) {
            return $resolved->model;
        }

        try {
            $instance = $this->aiManager->instance($resolved->provider);

            if ($instance instanceof TextProvider) {
                return $instance->defaultTextModel();
            }
        } catch (Throwable) {
        }

        return '';
    }
}
