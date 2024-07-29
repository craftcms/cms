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
interface FieldLayoutProviderInterface extends Grippable
{
    /**
     * Returns the field layout defined by this component.
     *
     * @return FieldLayout
     */
    public function getFieldLayout(): FieldLayout;
}
