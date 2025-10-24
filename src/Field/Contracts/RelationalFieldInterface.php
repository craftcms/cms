<?php

declare(strict_types=1);

namespace CraftCms\Cms\Field\Contracts;

use craft\base\ElementInterface;

/**
 * RelationalFieldInterface defines the common interface to be implemented by field classes
 * which can store relation data.
 */
interface RelationalFieldInterface extends FieldInterface
{
    /**
     * Returns whether relations stored for the field should include the source element’s site ID.
     *
     * Note that this must be consistent across all instances of the same field.
     */
    public function localizeRelations(): bool;

    /**
     * Returns whether relations should be updated for the field.
     */
    public function forceUpdateRelations(ElementInterface $element): bool;

    /**
     * Returns the related element IDs for this field.
     *
     * @return int[]
     */
    public function getRelationTargetIds(ElementInterface $element): array;
}
