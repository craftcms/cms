<?php

declare(strict_types=1);

namespace CraftCms\Cms\Field\Contracts;

use CraftCms\Cms\Element\Contracts\ElementInterface;

/**
 * Marks fields whose serialized values should be scanned for element reference tags.
 */
interface TracksReferencesFieldInterface extends FieldInterface
{
    /**
     * Returns the referenced element IDs for this field.
     *
     * @return int[]
     */
    public function getReferenceTargetIds(ElementInterface $element): array;

    /**
     * Replaces references within the field value with a new element ID.
     *
     * @param  int[]  $oldTargetIds
     */
    public function replaceReferences(ElementInterface $element, array $oldTargetIds, int $newTargetId): bool;
}
