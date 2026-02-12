<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\base;

/**
 * InlineEditableFieldInterface defines the common interface to be implemented by field classes
 * that wish to be editable via inline edit forms.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 5.0.0
 * @mixin Field
 */
interface InlineEditableFieldInterface extends PreviewableFieldInterface
{
    /**
     * Returns the HTML that should be shown for this field’s inline inputs.
     *
     * @param mixed $value The field’s value
     * @param ElementInterface|null $element The element the field is associated with
     * @return string The HTML that should be shown for this field’s inline input
     * @since 5.0.0
     */
    public function getInlineInputHtml(mixed $value, ?ElementInterface $element): string;
}
