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
 * The method mirrors Laravel’s `AwsS3V3Adapter::temporaryUploadUrl()`.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 5.13.0
 */
interface TemporaryUploadUrlInterface
{
    /**
     * Returns a URL that stores the body of a `PUT` request at a path, and the headers the request must include.
     *
     * The file must appear at the path all at once, when the request completes. The caller removes files that are
     * never used.
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
