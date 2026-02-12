<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\base;

/**
 * PreviewableFieldInterface defines the common interface to be implemented by field classes
 * that wish to be previewable in element table and card views.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 * @mixin Field
 */
interface PreviewableFieldInterface extends FieldInterface
{
    /**
     * Returns the HTML that should be shown for this field in table and card views.
     *
     * @param mixed $value The field’s value
     * @param ElementInterface $element The element the field is associated with
     * @return string The HTML that should be shown for this field in table and card views
     * @since 5.0.0
     */
    public function getPreviewHtml(mixed $value, ElementInterface $element): string;

    /**
     * Return the HTML that should be shown for the field in the card preview.
     * It can be used outside an element context, e.g. in a card view designer.
     *
     * @param mixed $value
     * @param ElementInterface|null $element
     * @return string
     * @since 5.5.0
     */
    public function previewPlaceholderHtml(mixed $value, ?ElementInterface $element): string;
}
