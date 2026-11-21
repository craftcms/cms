<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\uploads;

use craft\elements\User;
use yii\web\HttpException;

/**
 * UploadHandlerInterface defines where an upload goes, who may send it, and what happens to it once it arrives.
 *
 * Pass the handler’s class to [[\craft\services\Uploads::start()]]. It’s stored with the upload, so clients can’t
 * change it.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 5.13.0
 */
interface UploadHandlerInterface
{
    /**
     * Validates an upload’s file and destination, and returns its normalized parameters.
     *
     * This runs when the upload starts and again when it’s completed. The normalized parameters must be the same
     * both times. Allow guests only if a later check, such as [[\craft\services\Uploads::EVENT_AUTHORIZE_UPLOAD]],
     * decides for them.
     *
     * @param array $params The client’s destination parameters
     * @param string|null $filename The file’s name, if known
     * @param int|null $size The file’s size in bytes, if known
     * @param User|null $user The uploader, or `null` for a guest
     * @return array
     * @throws HttpException if the upload isn’t allowed
     */
    public function authorize(array $params, ?string $filename, ?int $size, ?User $user): array;

    /**
     * Returns the UID of the volume whose filesystem should hold the file until it’s completed, or `null` for the
     * temporary asset upload filesystem.
     *
     * Staging a file on the filesystem it ends up on lets it be moved into place without downloading it.
     */
    public function stagingVolumeUid(array $params): ?string;

    /**
     * Processes a completed upload, and returns the response data for the client.
     *
     * @param array $params The normalized parameters
     * @param StagedUpload $upload The uploaded file
     * @return array
     * @throws HttpException if the file can’t be processed. The upload can be completed again.
     */
    public function complete(array $params, StagedUpload $upload): array;
}
