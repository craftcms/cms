<?php

/**
 * @link https://craftcms.com/
 *
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\fields;

use craft\base\Event as YiiEvent;
use craft\events\LocateUploadedFilesEvent;
use CraftCms\Cms\Field\Events\LocateUploadedFiles;
use Illuminate\Support\Facades\Event;

/**
 * @since 3.0.0
 * @deprecated 6.0.0 use {@see \CraftCms\Cms\Field\Assets} instead.
 */
class Assets extends \CraftCms\Cms\Field\Assets
{
    use \craft\base\FieldEventConstants;
    use \craft\base\LegacyEventConstants;

    /**
     * @event LocateUploadedFilesEvent The event that is triggered when identifying any uploaded files that
     * should be stored as assets and related by the field.
     *
     * @since 4.0.2
     */
    public const string EVENT_LOCATE_UPLOADED_FILES = 'locateUploadedFiles';

    public static function registerEvents(): void
    {
        Event::listen(function(LocateUploadedFiles $event) {
            if (!$event->field instanceof \CraftCms\Cms\Field\Assets) {
                return;
            }

            if (!YiiEvent::hasHandlers(self::class, self::EVENT_LOCATE_UPLOADED_FILES)) {
                return;
            }

            $yiiEvent = new LocateUploadedFilesEvent([
                'element' => $event->element,
                'files' => $event->files,
                'sender' => $event->field,
            ]);

            YiiEvent::trigger(self::class, self::EVENT_LOCATE_UPLOADED_FILES, $yiiEvent);

            $event->files = $yiiEvent->files;
        });
    }
}
