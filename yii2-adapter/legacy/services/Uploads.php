<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\services;

use yii\base\Component;

/**
 * Uploads service.
 *
 * Craft 6 uploads files through {@see \CraftCms\Cms\Filesystem\Uploads upload sessions}. This service only keeps
 * [[EVENT_AUTHORIZE_UPLOAD]] working for asset uploads, and garbage collection of expired uploads.
 *
 * An instance of the service is available via [[\craft\base\ApplicationTrait::getUploads()|`Craft::$app->getUploads()`]].
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 5.13.0
 * @deprecated 6.0.0 use {@see \CraftCms\Cms\Filesystem\Uploads} instead.
 */
class Uploads extends Component
{
    /**
     * @event \craft\events\AuthorizeUploadEvent The event that is triggered whenever an asset upload is authorized: when
     * it starts, as its file is sent, and when it’s completed.
     *
     * Guests can only upload when a handler sets [[\craft\events\AuthorizeUploadEvent::$isValid]] to `true`, or a
     * {@see \CraftCms\Cms\Asset\AssetUploads::allowGuestUploadsUsing()} callback allows them:
     *
     * ```php
     * use craft\events\AuthorizeUploadEvent;
     * use craft\services\Uploads;
     * use yii\base\Event;
     *
     * Event::on(Uploads::class, Uploads::EVENT_AUTHORIZE_UPLOAD, function(AuthorizeUploadEvent $event) {
     *     if (!$event->user && ($event->params['folderId'] ?? null) === 123 && $event->size <= 10_000_000) {
     *         $event->isValid = true;
     *     }
     * });
     * ```
     *
     * @deprecated 6.0.0 use {@see \CraftCms\Cms\Asset\AssetUploads::allowGuestUploadsUsing()} instead.
     */
    public const EVENT_AUTHORIZE_UPLOAD = 'authorizeUpload';

    /**
     * Deletes expired uploads and their files.
     *
     * @return int The number of uploads deleted
     */
    public function deleteExpiredUploads(): int
    {
        return app(\CraftCms\Cms\Filesystem\Uploads::class)->cleanupExpired()['removed'];
    }
}
