<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\base;

/**
 * RelationalFieldInterface defines the common interface to be implemented by field classes
 * which can store relation data.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 5.3.0
 */
interface RelationalFieldInterface extends FieldInterface
{
    /**
     * Returns whether relations stored for the field should include the source element’s site ID.
     *
     * Note that this must be consistent across all instances of the same field.
     *
     * @return bool
     */
    public function localizeRelations(): bool;

    /**
     * Returns whether relations should be updated for the field.
     *
     * @param ElementInterface $element
     * @return bool
     */
    public function forceUpdateRelations(ElementInterface $element): bool;

    /**
     * Returns the related element IDs for this field.
     *
     * @param ElementInterface $element
     * @return int[]
     */
    public function getRelationTargetIds(ElementInterface $element): array;
}
