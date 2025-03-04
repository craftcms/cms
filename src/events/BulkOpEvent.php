<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\events;

use Craft;
use craft\db\Query;
use craft\db\Table;
use craft\helpers\ArrayHelper;
use craft\helpers\DateTimeHelper;
use craft\helpers\Db;
use craft\services\Elements;
use yii\base\Application;
use yii\base\Event;

/**
 * Bulk operation event class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 5.0.0
 */
class BulkOpEvent extends ElementQueryEvent
{
    private static array $handlers;
    private static array $triggers;

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
        if (!isset(self::$handlers)) {
            self::$handlers = [];
            self::$triggers = [];

            Event::on(Elements::class, Elements::EVENT_AFTER_BULK_OP, function(self $event) {
                $triggers = ArrayHelper::remove(self::$triggers, $event->key, []);
                $db = Craft::$app->getElements()->bulkOpDb;

                // see if any events were fired for the same bulk op key from previous requests
                $storedTriggers = (new Query())
                    ->select(['senderClass', 'eventName'])
                    ->from(Table::BULKOPEVENTS)
                    ->where(['key' => $event->key])
                    ->all($db);
                if (!empty($storedTriggers)) {
                    Db::delete(Table::BULKOPEVENTS, ['key' => $event->key], db: $db);
                    foreach ($storedTriggers as $trigger) {
                        $triggers[$trigger['senderClass']][$trigger['eventName']] = true;
                    }
                }

                foreach ($triggers as $class => $eventNames) {
                    foreach (array_keys($eventNames) as $eventName) {
                        $handlers = self::$handlers[$class][$eventName] ?? [];
                        foreach ($handlers as [$handler, $data]) {
                            $event->data = $data;
                            call_user_func($handler, $event);
                        }
                    }
                }
            }, append: false);

            Craft::$app->on(Application::EVENT_AFTER_REQUEST, function() {
                // keep track of any event triggers that haven’t been handled yet
                if (!empty(self::$triggers)) {
                    $timestamp = Db::prepareDateForDb(DateTimeHelper::now());
                    $db = Craft::$app->getElements()->bulkOpDb;
                    foreach (self::$triggers as $key => $triggers) {
                        foreach ($triggers as $class => $eventNames) {
                            foreach (array_keys($eventNames) as $eventName) {
                                Db::upsert(Table::BULKOPEVENTS, [
                                    'key' => $key,
                                    'senderClass' => $class,
                                    'eventName' => $eventName,
                                    'timestamp' => $timestamp,
                                ], db: $db);
                            }
                        }
                    }
                }
            }, append: false);
        }

        self::$handlers[$class][$name][] = [$handler, $data];

        static::on($class, $name, function() use ($class, $name) {
            foreach (Craft::$app->getElements()->getBulkOpKeys() as $key) {
                self::$triggers[$key][$class][$name] = true;
            }
        }, append: false);
    }

    /**
     * @var string The bulk operation key.
     */
    public string $key;
}
