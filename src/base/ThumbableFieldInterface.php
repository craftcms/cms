<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\base;

/**
 * ThumbableFieldInterface defines the common interface to be implemented by field classes
 * that can provide a thumbnail for element card views.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 5.0.0
 * @mixin Field
 */
interface ThumbableFieldInterface extends FieldInterface
{
    /**
     * Returns the HTML for an element’s thumbnail.
     *
     * @param mixed $value The field’s value
     * @param ElementInterface $element The element the field is associated with
     * @param int $size The maximum width and height the thumbnail should have.
     * @return string|null
     */
    public function getThumbHtml(mixed $value, ElementInterface $element, int $size): ?string;
}
