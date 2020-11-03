<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\base;

use craft\elements\db\ElementQueryInterface;

/**
 * ElementExporterInterface defines the common interface to be implemented by element exporter classes.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.4.0
 */
interface ElementExporterInterface extends ComponentInterface
{
    /**
     * Sets the element type on the exporter.
     *
     * @param string $elementType
     */
    public function setElementType(string $elementType);

    /**
     * Creates the export data for elements fetched with the given element query.
     *
     * @param ElementQueryInterface $query The element query
     * @return array
     */
    public function export(ElementQueryInterface $query): array;

    /**
     * Returns the filename (sans extension) that the export file should have.
     *
     * @return string
     */
    public function getFilename(): string;
}
