<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\events;

use CraftCms\Cms\Element\BulkOp\Events\DeferredBulkOpReplay;
use CraftCms\Cms\Support\Facades\BulkOps;

/**
 * Bulk operation event class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 5.0.0
 */
class BulkOpEvent extends ElementQueryEvent
{
    /**
     * Listens to a class-level event, but defers calling the handler until after a bulk operation
     * is completed, and only if the event was triggered during the bulk operation.
     *
     * ```php
     * BulkOpEvent::deferredOn(ActiveRecord::class, ActiveRecord::EVENT_AFTER_INSERT, function ($event) {
     *     Yii::trace(get_class($event->sender) . ' is inserted.');
     * });
     * ```
     *
     * @param string $class the fully qualified class name to which the event handler needs to attach.
     * @param string $name the event name.
     * @param callable $handler the event handler.
     * @param mixed $data the data to be passed to the event handler when the event is triggered.
     * When the event handler is invoked, this data can be accessed via [[\yii\base\Event::data]].
     * @since 5.7.0
     */
    public static function defer(
        string $class,
        string $name,
        callable $handler,
        mixed $data = null,
    ): void {
        BulkOps::defer($class, function(DeferredBulkOpReplay $replay) use ($handler) {
            $event = new self([
                'key' => $replay->key,
                'data' => $replay->data,
            ]);

            call_user_func($handler, $event);
        }, data: $data, watchKey: $name);
    }

    /**
     * @var string The bulk operation key.
     */
    public string $key;
}
