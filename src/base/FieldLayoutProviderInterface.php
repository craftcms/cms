<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\base;

use craft\models\FieldLayout;

/**
 * FieldLayoutProviderInterface defines the common interface to be implemented by classes
 * which provide a field layout.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.5.0
 */
interface FieldLayoutProviderInterface
{
    /**
     * Returns the provider’s handle, which could be used to identify custom fields with ambiguous handles.
     *
     * @return string|null
     * @since 5.0.0
     */
    public function getHandle(): ?string;

    /**
     * Returns the field layout defined by this component.
     *
     * @return FieldLayout
     */
    public function getFieldLayout(): FieldLayout;
}
