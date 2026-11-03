<?php

declare(strict_types=1);

namespace CraftCms\Cms\Ai\Assistant\Data;

use CraftCms\Cms\Ui\NodePayload;
use CraftCms\Cms\Ui\UiPayload;
use Spatie\TypeScriptTransformer\Attributes\LiteralTypeScriptType;

/**
 * A proposed change to a field, as the AI assistant’s `propose_changes` tool returns it.
 *
 * Text is proposed with a diff. Other values are proposed with a read-only preview of the field before and after, and
 * the field’s node, whose controls may depend on the value.
 *
 * @internal
 *
 * @since 6.0.0
 */
readonly class Proposal
{
    /**
     * @param  string|null  $ref  The context item the field’s element came from.
     * @param  string  $element  The element’s label.
     * @param  int  $canonicalId  The ID of the element a draft is of, or the element’s own, for finding its editor
     *                            after the page reloads, when the editor may be editing a draft that was created since.
     * @param  string|null  $cpEditUrl  Where the element is edited, for applying the change when its editor isn’t open.
     * @param  list<string>  $path  The path of the field’s control in its editor’s form.
     * @param  array{current: string, proposed: string}|null  $diff
     * @param  array{current: UiPayload, proposed: UiPayload}|null  $preview
     */
    public function __construct(
        public string $key,
        public ?string $ref,
        public string $label,
        public string $element,
        public int $elementId,
        public int $canonicalId,
        public int $siteId,
        public ?string $cpEditUrl,
        public array $path,
        #[LiteralTypeScriptType('unknown')]
        public mixed $value,
        public ?array $diff = null,
        public ?array $preview = null,
        public ?NodePayload $node = null,
    ) {}
}
