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
 * @since 3.0.0
 * @deprecated 6.0.0 Use {@see \CraftCms\Cms\Element\Events\BeforeSave}, {@see \CraftCms\Cms\Element\Events\AfterSave}, {@see \CraftCms\Cms\Element\Events\AfterPropagate}, {@see \CraftCms\Cms\Element\Events\BeforeDelete}, {@see \CraftCms\Cms\Element\Events\AfterDelete}, {@see \CraftCms\Cms\Element\Events\BeforeRestore}, or {@see \CraftCms\Cms\Element\Events\AfterRestore} instead.
 */
class ModelEvent extends \yii\base\ModelEvent
{
    /**
     * @var bool Whether the model is brand new
     */
    public bool $isNew = false;
}
