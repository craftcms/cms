<?php

declare(strict_types=1);

namespace CraftCms\Cms\Ai\AskAi;

use CraftCms\Cms\Ui\UiPayload;

/**
 * A subject’s field layout, compiled with the proposed values: editable, for the editor to apply, and read-only before
 * and after, for previews.
 *
 * @internal
 *
 * @since 6.0.0
 */
readonly class CompiledSubject
{
    /**
     * @param  array<string, mixed>  $proposedContext  The proposed content, as the model sees it.
     */
    public function __construct(
        public UiPayload $editable,
        public UiPayload $current,
        public UiPayload $proposed,
        public array $proposedContext,
    ) {}
}
