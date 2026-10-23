<?php

declare(strict_types=1);

namespace CraftCms\Cms\Ai\AskAi\Data;

use CraftCms\Cms\Ui\NodePayload;
use CraftCms\Cms\Ui\UiPayload;

/**
 * A generated value for one field, for the editor to review.
 *
 * @internal
 *
 * @since 6.0.0
 */
readonly class Proposal
{
    /**
     * @param  string  $key  The field’s key in the prompt, such as `summary` or `body.2.heading`.
     * @param  NodePayload  $node  The field’s editable node, compiled with the generated value.
     * @param  mixed  $value  The generated value, as the field’s control holds it.
     * @param  array{current: string, proposed: string}|null  $diff  The current and proposed text, for fields whose values are text.
     * @param  array{current: UiPayload, proposed: UiPayload}|null  $preview  Read-only payloads of the field, for fields whose values aren’t text.
     */
    public function __construct(
        public string $key,
        public string $label,
        public NodePayload $node,
        public mixed $value,
        public ?array $diff,
        public ?array $preview,
    ) {}
}
