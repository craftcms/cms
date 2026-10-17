<?php

declare(strict_types=1);

namespace CraftCms\Cms\Ai\Exceptions;

use CraftCms\Cms\Ai\Enums\AiUnavailableReason;
use RuntimeException;

/**
 * Thrown when a task can't resolve a provider, before any request is sent.
 *
 * @since 6.0.0
 */
class AiUnavailableException extends RuntimeException
{
    public function __construct(
        public readonly string $task,
        public readonly AiUnavailableReason $reason,
        ?string $provider = null,
    ) {
        parent::__construct(match ($reason) {
            AiUnavailableReason::UnknownTask => "No AI task with the handle [{$task}] is registered.",
            AiUnavailableReason::TaskDisabled => "The AI task [{$task}] is disabled.",
            AiUnavailableReason::NoDefault => "The AI task [{$task}] has no provider, and no default provider is set for its modality.",
            AiUnavailableReason::UnknownProvider => "The AI task [{$task}] uses the provider [{$provider}], which isn’t defined in the `ai.providers` config.",
            AiUnavailableReason::Canceled => "The AI task [{$task}] was canceled by a TaskRunning event listener.",
        });
    }
}
