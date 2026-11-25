<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\base;

use DateTimeInterface;

/**
 * TemporaryUploadUrlInterface defines a filesystem that can accept files directly from clients, such as through a
 * presigned S3 URL, so uploads don’t pass through Craft’s request handling.
 *
 * Craft 6 doesn’t call it. Uploads to Laravel S3 disks already go straight to storage, and other storage can send
 * them there with an uploader registered with {@see \CraftCms\Cms\Filesystem\Uploaders::extend()}.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 5.13.0
 * @deprecated 6.0.0 implement {@see \CraftCms\Cms\Filesystem\Contracts\Uploader} instead.
 */
interface TemporaryUploadUrlInterface
{
    /**
     * Returns a URL that stores the body of a `PUT` request at a path, and the headers the request must include.
     *
     * @param string $path The path, relative to the filesystem root.
     * @param DateTimeInterface $expiresAt When the URL stops accepting requests.
     * @param array{ContentType?: string, ContentLength?: int} $options Values the URL must be signed for, so that
     * requests with other values are rejected.
     * @return array{url: string, headers: array<string, string>}|null The URL and every header it was signed with
     * (other than `Host`), or `null` if the filesystem can’t accept direct uploads right now.
     */
    public function temporaryUploadUrl(string $path, DateTimeInterface $expiresAt, array $options = []): ?array;
}
