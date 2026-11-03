<?php

declare(strict_types=1);

namespace CraftCms\Cms\Ai\Tasks;

use CraftCms\Cms\Ai\Exceptions\AiUnavailableException;
use CraftCms\Cms\Ai\Task;
use CraftCms\Cms\Support\Facades\Ai;
use Illuminate\Support\Str;
use Laravel\Ai\AnonymousAgent;
use Throwable;

use function CraftCms\Cms\t;

/**
 * Names the conversations in the control panel’s AI chat after their first message, with the provider and model
 * administrators chose for it.
 *
 * @since 6.0.0
 */
class GenerateChatTitle extends Task
{
    private const string INSTRUCTIONS = 'Generate a concise 3-5 word title for the following text. Use the same language as the text. Respond with only the title, no quotes or punctuation.';

    public static function handle(): string
    {
        return 'craft:chatTitle';
    }

    public static function label(): string
    {
        return t('Generate chat titles');
    }

    public static function description(): ?string
    {
        return t('Names the conversations in the control panel’s AI chat after their first message. A small, fast model is enough.');
    }

    /**
     * Generates a chat’s name from its first message, at most 100 characters long, or returns `null` if the task is
     * unavailable or the request fails.
     *
     * @internal
     */
    public static function generate(string $text): ?string
    {
        try {
            $response = Ai::task(self::handle())->prompt(new AnonymousAgent(self::INSTRUCTIONS, [], []), Str::limit($text, 500));
        } catch (AiUnavailableException) {
            return null;
        } catch (Throwable $exception) {
            report($exception);

            return null;
        }

        $title = (string) preg_replace('/^[\s"\'“”]+|[\s"\'“”.]+$/u', '', $response->text);

        return $title !== '' ? Str::limit($title, 100) : null;
    }
}
