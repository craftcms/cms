<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\uploads;

use Craft;
use craft\elements\User;
use craft\helpers\ConfigHelper;
use yii\base\NotSupportedException;
use yii\web\BadRequestHttpException;
use yii\web\ForbiddenHttpException;

/**
 * Stages files in the temporary asset upload filesystem, for callers that choose where they go when they claim them
 * with [[\craft\services\Uploads::claim()]], such as MCP’s `assets_upload_url` tool.
 *
 * Only users who can save assets in some volume may upload, so registered front-end users can’t fill temporary
 * storage.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 5.13.0
 */
class TemporaryUploadHandler implements UploadHandlerInterface
{
    /**
     * @inheritdoc
     */
    public function authorize(array $params, ?string $filename, ?int $size, ?User $user): array
    {
        $canUpload = false;

        if ($user) {
            foreach (Craft::$app->getVolumes()->getAllVolumes() as $volume) {
                if ($user->can("saveAssets:$volume->uid")) {
                    $canUpload = true;
                    break;
                }
            }
        }

        if (!$canUpload) {
            throw new ForbiddenHttpException('You are not authorized to upload assets.');
        }

        if ($size !== null && ($size < 1 || $size > ConfigHelper::sizeInBytes(Craft::$app->getConfig()->getGeneral()->maxUploadFileSize))) {
            throw new BadRequestHttpException('The file exceeds the maximum upload size.');
        }

        return [];
    }

    /**
     * @inheritdoc
     */
    public function stagingVolumeUid(array $params): ?string
    {
        return null;
    }

    /**
     * @inheritdoc
     */
    public function complete(array $params, StagedUpload $upload): array
    {
        throw new NotSupportedException('Temporary uploads must be claimed with Uploads::claim().');
    }
}
