<?php

declare(strict_types=1);

namespace CraftCms\Cms\Ai\Assistant\Data;

use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Element\Contracts\NestedElementInterface;

/**
 * The request parameters that identify an element to the AI assistant, as an editor has it.
 *
 * @internal
 *
 * @since 6.0.0
 */
readonly class ElementParams
{
    /**
     * @param  class-string<ElementInterface>  $elementType
     * @param  int|null  $ownerId  For a nested element, the owner it’s being edited in, such as a draft.
     * @param  int|null  $canonicalId  The ID of the element a draft is of, or the element’s own.
     */
    public function __construct(
        public string $elementType,
        public ?int $elementId,
        public ?int $draftId,
        public int $siteId,
        public ?int $ownerId = null,
        public ?int $fieldId = null,
        public ?int $canonicalId = null,
    ) {}

    /**
     * Returns the parameters for an element. A nested element is identified through the owner it’s being edited in,
     * such as a draft, rather than its primary owner.
     */
    public static function for(ElementInterface $element): self
    {
        $isNested = $element instanceof NestedElementInterface && $element->getOwnerId() !== null;

        return new self(
            elementType: $element::class,
            elementId: $element->id,
            draftId: $element->draftId,
            siteId: $element->siteId,
            ownerId: $isNested ? $element->getOwnerId() : null,
            fieldId: $isNested ? $element->getField()?->id : null,
            canonicalId: $element->getCanonicalId(),
        );
    }
}
