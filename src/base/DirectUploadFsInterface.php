<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\base;

use craft\errors\FsException;
use DateTimeInterface;

/**
 * DirectUploadFsInterface can be implemented by filesystems that accept uploads sent directly to their storage, such
 * as with presigned URLs, so files don’t pass through Craft’s web requests.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 5.13.0
 */
interface DirectUploadFsInterface
{
    /**
     * Returns a URL that a client can send a file to, which stores it at the given path.
     *
     * The URL must accept the file as the raw request body, sent with the returned method and headers. Clients may
     * send other headers too. It may be used repeatedly until it expires, and needn’t enforce a size limit, so callers
     * must check the file’s size before using it.
     *
     * The file must only appear at the path once its upload is complete, so [[BaseFsInterface::fileExists()]] never
     * reports a partial upload.
     *
     * @param string $path The path the file should be stored at, relative to the filesystem’s root, as with
     * [[BaseFsInterface::write()]]
     * @param DateTimeInterface $expiresAt When the URL should expire
     * @return array{url: string, method: string, headers: array<string, string>}|null The URL, or `null` if the
     * filesystem can’t accept direct uploads currently
     * @throws FsException if the URL can’t be created
     */
    public function getDirectUploadUrl(string $path, DateTimeInterface $expiresAt): ?array;
}
