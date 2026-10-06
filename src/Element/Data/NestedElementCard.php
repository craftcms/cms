<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element\Data;

use Illuminate\Contracts\Support\Arrayable;
use JsonSerializable;
use Spatie\TypeScriptTransformer\Attributes\LiteralTypeScriptType;

/**
 * @implements Arrayable<string, mixed>
 *
 * @since 6.0.0
 */
readonly class NestedElementCard implements Arrayable, JsonSerializable
{
    /**
     * @param  list<array<string, mixed>>  $actionMenuItems
     * @param  array<string, mixed>  $cardAttributes
     */
    public function __construct(
        public int $id,
        public ?int $siteId,
        public ?int $entryTypeId,
        public ?int $ownerId,
        public bool $ownerIsCanonical,
        public bool $isUnpublishedDraft,
        public bool $ownerIsUnpublishedDraft,
        public ?int $primaryOwnerId,
        public bool $isCanonical,
        /** @var array{copyable: bool, duplicatable: bool, deletable: bool} */
        public array $capabilities,
        /** The slideout editor URL for this nested context. */
        public ?string $editUrl,
        /** The element’s own edit page, for opening in a new tab. */
        public ?string $cpEditUrl,
        #[LiteralTypeScriptType("import('@/common/types').ActionItems")]
        public array $actionMenuItems,
        #[LiteralTypeScriptType("import('@craftcms/ui').ServerAttributes & { data?: { label?: string; url?: string; 'draft-id'?: number; 'revision-id'?: number; editable?: boolean } }")]
        public array $cardAttributes,
        public string $cardHeaderHtml,
        public string $cardActionsHtml,
        public string $cardContentHtml,
        public string $cardFooterHtml,
        public string $cardThumbHtml,
        #[LiteralTypeScriptType("'start' | 'end'")]
        public string $thumbAlignment,
    ) {}

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return get_object_vars($this);
    }

    /** @return array<string, mixed> */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
