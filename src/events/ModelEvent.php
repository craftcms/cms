<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\events;

/**
 * ModelEvent class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class ModelEvent extends \yii\base\ModelEvent
{
    // Properties
    // =========================================================================

    /**
     * @var bool Whether the model is brand new
     */
    public $isNew = false;
}
