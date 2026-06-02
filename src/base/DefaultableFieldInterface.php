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
 * @since 5.10.0
 * @mixin Field
 */
interface DefaultableFieldInterface extends FieldInterface
{
    /**
     * Returns the default value that should be set on existing elements.
     *
     * @return mixed
     */
    public function getDefaultValue(): mixed;
}
