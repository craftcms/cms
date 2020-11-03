<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\elements\actions;

/**
 * DeepDuplicate represents a "Duplicate (with descendants)" element action.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.30
 * @deprecated in 3.5.0. [[Duplicate]] should be used instead.
 */
class DeepDuplicate extends Duplicate
{
    /**
     * @inheritdoc
     */
    public $deep = true;
}
