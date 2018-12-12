<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\elements\actions;

use Craft;
use craft\base\Element;
use craft\base\ElementAction;
use craft\base\ElementInterface;
use craft\elements\db\ElementQueryInterface;
use craft\helpers\ArrayHelper;

/**
 * DeepDuplicate represents a "Duplicate (with descendants)" element action.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.30
 */
class DeepDuplicate extends Duplicate
{
    // Properties
    // =========================================================================

    /**
     * @inheritdoc
     */
    public $deep = true;
}
