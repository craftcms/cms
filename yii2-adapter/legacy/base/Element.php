<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\base;

use craft\base\Event as YiiEvent;
use craft\events\DefineValueEvent;
use CraftCms\Cms\Element\Events\DefineCacheTags;
use Illuminate\Support\Facades\Event;

/**
 * @since 3.0.0
 * @deprecated 6.0.0 use {@see \CraftCms\Cms\Element\Element} instead.
 */
abstract class Element extends \CraftCms\Cms\Element\Element
{
    /**
     * @event DefineValueEvent The event that is triggered when defining the cache tags that should be cleared when
     * this element is saved.
     *
     * @see getCacheTags()
     * @since 4.1.0
     * @deprecated 6.0.0 Use {@see DefineCacheTags} instead.
     */
    public const EVENT_DEFINE_CACHE_TAGS = 'defineCacheTags';

    public static function registerEvents(): void
    {
        // Find all classes that extend Element
        $classes = get_declared_classes();
        $elementClasses = [];
        foreach ($classes as $class) {
            if (is_subclass_of($class, self::class)) {
                $elementClasses[] = $class;
            }
        }

        Event::listen(function(DefineCacheTags $event) use ($elementClasses) {
            foreach ($elementClasses as $class) {
                if (!YiiEvent::hasHandlers($class, self::EVENT_DEFINE_CACHE_TAGS)) {
                    return;
                }

                $yiiEvent = new DefineValueEvent([
                    'sender' => $event->element,
                    'value' => $event->tags,
                ]);

                YiiEvent::trigger($class, self::EVENT_DEFINE_CACHE_TAGS, $yiiEvent);

                $event->tags = $yiiEvent->value;
            }
        });
    }
}
