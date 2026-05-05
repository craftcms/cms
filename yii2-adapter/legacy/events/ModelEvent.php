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
 * @deprecated 6.0.0 Use {@see \CraftCms\Cms\Element\Events\ElementLifecycleSaving}, {@see \CraftCms\Cms\Element\Events\ElementLifecycleSaved}, {@see \CraftCms\Cms\Element\Events\ElementLifecyclePropagated}, {@see \CraftCms\Cms\Element\Events\ElementLifecycleDeleting}, {@see \CraftCms\Cms\Element\Events\ElementLifecycleDeleted}, {@see \CraftCms\Cms\Element\Events\ElementLifecycleRestoring}, or {@see \CraftCms\Cms\Element\Events\ElementLifecycleRestored} instead.
 */
class ModelEvent extends \yii\base\ModelEvent
{
    /**
     * @var bool Whether the model is brand new
     */
    public bool $isNew = false;
}
