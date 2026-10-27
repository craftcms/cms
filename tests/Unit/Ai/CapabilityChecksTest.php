<?php

declare(strict_types=1);

use CraftCms\Cms\Ai\Agents\AttachmentProbe;
use CraftCms\Cms\Ai\Agents\StructuredOutputProbe;
use CraftCms\Cms\Ai\Agents\ToolCallingProbe;
use CraftCms\Cms\Ai\Ai;
use CraftCms\Cms\Ai\AiTasks;
use CraftCms\Cms\Ai\CapabilityChecks;
use CraftCms\Cms\Ai\Data\ResolvedProvider;
use CraftCms\Cms\Ai\Enums\AiUnavailableReason;
use CraftCms\Cms\Ai\Enums\Capability;
use CraftCms\Cms\Ai\Exceptions\AiUnavailableException;
use CraftCms\Cms\Ai\Settings;
use CraftCms\Cms\Asset\Analysis\Tasks\DetectFocalPoint;
use CraftCms\Cms\Asset\Analysis\Tasks\GenerateAltText;
use CraftCms\Cms\Asset\Analysis\Tasks\GenerateTitles;
use CraftCms\Cms\Asset\Analysis\Tasks\TagImage;
use CraftCms\Cms\Support\Str;
use CraftCms\Cms\Tests\Support\Ai\AltTextTask;
use CraftCms\Cms\Tests\Support\Ai\MediaTask;
use CraftCms\Cms\Tests\Support\Ai\ResearchTask;
use CraftCms\Cms\Tests\Support\Ai\SummarizeTask;
use GuzzleHttp\Psr7\Response as Psr7Response;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Collection;
use Laravel\Ai\Exceptions\ProviderConnectionException;
use Laravel\Ai\Exceptions\ProviderOverloadedException;

/** Names the color that fills a PNG. */
function swatchColor(string $png): string
{
    $image = imagecreatefromstring($png);
    $rgb = imagecolorat($image, 0, 0);
    $color = [($rgb >> 16) & 255, ($rgb >> 8) & 255, $rgb & 255];

    return match ($color) {
        [220, 30, 30] => 'red',
        [30, 170, 60] => 'green',
        [30, 80, 220] => 'blue',
        [240, 210, 30] => 'yellow',
        [0, 0, 0] => 'black',
        [255, 255, 255] => 'white',
        default => 'unknown',
    };
}

/** Counts the beeps in a WAV file: runs of sound separated by at least a tenth of a second of silence. */
function beepCount(string $wav): int
{
    $beeps = 0;
    $quiet = PHP_INT_MAX;

    foreach (unpack('s*', substr($wav, 44)) as $sample) {
        if (abs($sample) > 1000) {
            $beeps += $quiet > 800 ? 1 : 0;
            $quiet = 0;
        } else {
            $quiet++;
        }
    }

    return $beeps;
}

/** Answers a probe about its attachment the way a model that can read it would. Probe files are named after their answers. */
function probeAnswer(Collection $attachments): string
{
    return ucfirst(pathinfo($attachments->first()->path, PATHINFO_FILENAME));
}

beforeEach(function (): void {
    config(['ai.providers' => [
        'openai' => ['driver' => 'openai', 'key' => 'sk-test'],
        'deepseek' => ['driver' => 'deepseek', 'key' => 'sk-test'],
    ]]);
    // These tests are about their own tasks, not Craft’s
    app(AiTasks::class)->remove(GenerateAltText::class, GenerateTitles::class, DetectFocalPoint::class, TagImage::class);
    app(AiTasks::class)->register(AltTextTask::class, SummarizeTask::class);
    app()->instance(Settings::class, new Settings([
        'defaults' => ['text' => ['provider' => 'openai', 'model' => 'gpt-vision']],
        'tasks' => ['test:altText' => ['provider' => 'deepseek', 'model' => 'deepseek-chat']],
    ]));
});

it('checks the models of tasks that need vision once, and keeps tasks whose model can see', function (): void {
    AttachmentProbe::fake(fn (string $prompt, Collection $attachments): string => probeAnswer($attachments));

    app(Ai::class)->checkRequirements();
    app(Ai::class)->checkRequirements();

    $check = app(CapabilityChecks::class)->result(Capability::Vision, new ResolvedProvider('deepseek', 'deepseek-chat'));

    AttachmentProbe::assertPromptedTimes(1);
    expect($check?->supported)->toBeTrue()
        ->and(app(Ai::class)->available('test:altText'))->toBeTrue()
        ->and(app(Ai::class)->problems())->toBe([]);
});

it('makes tasks unavailable when their model can’t see images, and explains why', function (): void {
    AttachmentProbe::fake(fn (string $prompt, Collection $attachments): string => probeAnswer($attachments) === 'Red' ? 'blue' : 'red');

    app(Ai::class)->checkRequirements();

    expect(fn () => app(Ai::class)->resolve('test:altText'))
        ->toThrow(fn (AiUnavailableException $e) => expect($e->reason)->toBe(AiUnavailableReason::MissingCapability)
            ->and($e->capability)->toBe(Capability::Vision)
            ->and($e->model)->toBe('deepseek-chat'))
        ->and(app(Ai::class)->available('test:summarize'))->toBeTrue()
        ->and(app(Ai::class)->problems())->toBe([
            'test:altText' => 'Alt text needs vision, which the deepseek-chat model on the deepseek provider doesn’t support.',
        ]);
});

it('keeps tasks available when their model couldn’t be checked, reports the error, and checks again', function (): void {
    $attempts = 0;
    AttachmentProbe::fake(function (string $prompt, Collection $attachments) use (&$attempts): string {
        if (++$attempts === 1) {
            throw new RuntimeException('Invalid image content');
        }

        return probeAnswer($attachments);
    });

    app(Ai::class)->checkRequirements();

    expect(app(Ai::class)->available('test:altText'))->toBeTrue()
        ->and(app(Ai::class)->problems())->toBe([
            'test:altText' => 'Craft couldn’t check whether the deepseek-chat model on the deepseek provider supports vision, which Alt text needs: Invalid image content',
        ]);

    app(Ai::class)->checkRequirements();

    expect(app(Ai::class)->problems())->toBe([]);
    AttachmentProbe::assertPromptedTimes(2);
});

it('only checks tasks that need a capability and can run', function (): void {
    AttachmentProbe::fake(fn (string $prompt, Collection $attachments): string => probeAnswer($attachments));
    app()->instance(Settings::class, new Settings([
        'defaults' => ['text' => ['provider' => 'openai']],
        'tasks' => ['test:altText' => ['enabled' => false]],
    ]));

    app(Ai::class)->checkRequirements();

    AttachmentProbe::assertNeverPrompted();
});

it('checks that models can return structured output and call tools', function (mixed $structured, string $reply, ?bool $structuredOutput, ?bool $toolCalling, ?string $problem): void {
    app(AiTasks::class)->register(ResearchTask::class);
    app()->instance(Settings::class, new Settings(['tasks' => ['test:research' => ['provider' => 'openai', 'model' => 'gpt-small']]]));
    Str::createRandomStringsUsing(fn (): string => 'kxqzpwmvtrla');
    StructuredOutputProbe::fake([$structured]);
    ToolCallingProbe::fake([$reply]);

    app(Ai::class)->checkRequirements();
    Str::createRandomStringsNormally();
    $resolved = new ResolvedProvider('openai', 'gpt-small');

    expect(app(CapabilityChecks::class)->result(Capability::StructuredOutput, $resolved)?->supported)->toBe($structuredOutput)
        ->and(app(CapabilityChecks::class)->result(Capability::ToolCalling, $resolved)?->supported)->toBe($toolCalling)
        ->and(app(Ai::class)->problems()['test:research'] ?? null)->toBe($problem);
})->with([
    'both' => [['word' => 'lighthouse', 'number' => 42], 'The code is kxqzpwmvtrla.', true, true, null],
    'neither' => [['word' => 'lighthouse', 'number' => '42'], 'I can’t call tools.', false, false, 'Research needs structured output, which the gpt-small model on the openai provider doesn’t support.'],
    'no tools' => [['word' => 'lighthouse', 'number' => 42], 'I can’t call tools.', true, false, 'Research needs tool calling, which the gpt-small model on the openai provider doesn’t support.'],
]);

it('checks that models can read documents, hear audio, and watch videos', function (Capability $capability, bool $readable): void {
    app(AiTasks::class)->register(MediaTask::class);
    app()->instance(Settings::class, new Settings(['tasks' => ['test:media' => ['provider' => 'openai', 'model' => 'gpt-media']]]));
    AttachmentProbe::fake(fn (string $prompt, Collection $attachments): string => $readable ? probeAnswer($attachments) : 'I can’t open attachments.');

    app(Ai::class)->checkRequirements();

    expect(app(CapabilityChecks::class)->result($capability, new ResolvedProvider('openai', 'gpt-media'))?->supported)->toBe($readable);
})->with([Capability::DocumentInput, Capability::AudioInput, Capability::VideoInput])->with(['readable' => true, 'unreadable' => false]);

it('doesn’t cache errors that can be fixed right away, but checks the model again', function (Throwable $exception): void {
    $attempts = 0;
    AttachmentProbe::fake(function (string $prompt, Collection $attachments) use (&$attempts, $exception): string {
        if (++$attempts === 1) {
            throw $exception;
        }

        return probeAnswer($attachments);
    });

    $failures = app(Ai::class)->checkRequirements();

    expect($failures)->toHaveCount(1)
        ->and($failures[0]->transient)->toBeTrue()
        ->and(app(Ai::class)->available('test:altText'))->toBeTrue()
        ->and(app(Ai::class)->problems())->toBe([]);

    app(Ai::class)->checkRequirements(app(Ai::class)->requirementMappings());

    expect($attempts)->toBe(2)
        ->and(app(CapabilityChecks::class)->result(Capability::Vision, new ResolvedProvider('deepseek', 'deepseek-chat'))?->supported)->toBeTrue();
})->with([
    'unreachable' => fn () => ProviderConnectionException::forProvider('deepseek'),
    'bad API key' => fn () => new RequestException(new Response(new Psr7Response(401, [], '{"error":{"message":"Invalid API key"}}'))),
    'overloaded' => fn () => ProviderOverloadedException::forProvider('deepseek'),
]);

it('ships probe files that contain their answers', function (): void {
    $probes = dirname(__DIR__, 3).'/resources/ai/probes';
    $answers = fn (string $kind): array => array_map(fn (string $path): string => pathinfo($path, PATHINFO_FILENAME), glob("$probes/$kind/*"));

    foreach (glob("$probes/image/*.png") as $path) {
        expect(swatchColor(file_get_contents($path)))->toBe(pathinfo($path, PATHINFO_FILENAME));
    }

    foreach (glob("$probes/document/*.pdf") as $path) {
        expect(file_get_contents($path))->toStartWith('%PDF-')->toContain('('.pathinfo($path, PATHINFO_FILENAME).') Tj');
    }

    foreach (glob("$probes/audio/*.wav") as $path) {
        expect(beepCount(file_get_contents($path)))->toBe((int) pathinfo($path, PATHINFO_FILENAME));
    }

    expect($answers('image'))->toHaveCount(6)
        ->and($answers('document'))->toHaveCount(6)
        ->and($answers('audio'))->toBe(['1', '2', '3', '4'])
        ->and($answers('video'))->toEqualCanonicalizing($answers('image'));
});
