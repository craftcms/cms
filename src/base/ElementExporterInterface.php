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
     */
    public function setElementType(string $elementType);

    /**
     * Creates the export data for elements fetched with the given element query.
     *
     * If [[isFormattable()]] returns `true`, then this **must** return an array.
     *
     * If [[isFormattable()]] returns `false`, a callable (ideally a
     * [generator function](https://www.php.net/manual/en/language.generators.overview.php) or a resource can
     * be returned, which will get streamed out to the browser.
     *
     * @param ElementQueryInterface $query The element query
     * @return array|string|callable|resource
     */
    public function export(ElementQueryInterface $query);

    /**
     * Returns the filename that the export file should have.
     *
     * If the data is [[isFormattable()|formattable]], then a file extension will be added based on the selected format.
     *
     * @return string
     */
    public function getFilename(): string;
}
