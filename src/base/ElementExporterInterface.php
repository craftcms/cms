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
     * Returns whether the response data can be formatted as CSV, JSON, or XML.
     *
     * @return bool
     * @since 3.6.0
     */
    public static function isFormattable(): bool;

    /**
     * Sets the element type on the exporter.
     *
     * @param string $elementType
     * @phpstan-param class-string<ElementInterface> $elementType
     */
    public function setElementType(string $elementType): void;

    /**
     * Creates the export data for elements fetched with the given element query.
     *
     * If [[isFormattable()]] returns `true`, then this must return one of the followings:
     *
     * - An array of arrays
     * - A callable that returns an array of arrays
     * - A [generator function](https://www.php.net/manual/en/language.generators.overview.php) that yields arrays.
     *
     * Otherwise, a string or resource could also be returned.
     *
     * @param ElementQueryInterface $query The element query
     * @return array|string|callable|resource
     */
    public function export(ElementQueryInterface $query): mixed;

    /**
     * Returns the filename that the export file should have.
     *
     * If the data is [[isFormattable()|formattable]], then a file extension will be added based on the selected format.
     *
     * @return string
     */
    public function getFilename(): string;
}
