<?php

declare(strict_types=1);

namespace CraftCms\Cms\Field\Contracts;

use CraftCms\Cms\Element\Contracts\ElementInterface;

/**
 * Table cell types that reference other elements implement this interface, so the Table field can localize and
 * track those references.
 *
 * @since 6.0.0
 */
interface ElementAwareTableCellInterface extends TableCellInterface
{
    /**
     * Points a column’s normalized cell values’ references at the given element’s site, when the values are copied
     * from a site that the Table field translates separately.
     *
     * @template TKey of array-key
     *
     * @param  array<TKey, mixed>  $values
     * @return array<TKey, mixed>
     */
    public function localizeValues(array $values, ElementInterface $element): array;

    /**
     * Returns the element IDs referenced by a normalized cell value.
     *
     * @return int[]
     */
    public function referenceTargetIds(mixed $value): array;

    /**
     * Returns the cell value with references to the old element IDs replaced, or `null` if it has none.
     *
     * @param  int[]  $oldTargetIds
     */
    public function replaceReferences(mixed $value, array $oldTargetIds, int $newTargetId): mixed;
}
