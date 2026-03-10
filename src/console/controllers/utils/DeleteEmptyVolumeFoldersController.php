<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\console\controllers\utils;

use Craft;
use craft\console\Controller;
use craft\db\Query;
use craft\db\Table;
use yii\console\ExitCode;

/**
 * Deletes empty volume folders.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 5.6.0
 */
class DeleteEmptyVolumeFoldersController extends Controller
{
    /**
     * @var string|null The volume handle(s) to delete folders from. Can be set to multiple comma-separated volumes.
     */
    public ?string $volume = null;

    /**
     * @inheritdoc
     */
    public function options($actionID): array
    {
        return [
            ...parent::options($actionID),
            'volume',
        ];
    }

    /**
     * Deletes empty volume folders.
     *
     * @return int
     */
    public function actionIndex(): int
    {
        $query = (new Query())
            ->select(['folders.id'])
            ->from(['folders' => Table::VOLUMEFOLDERS])
            ->leftJoin(['assets' => Table::ASSETS], '[[assets.folderId]] = [[folders.id]]')
            ->leftJoin(['subfolders' => Table::VOLUMEFOLDERS], '[[subfolders.parentId]] = [[folders.id]]')
            ->where([
                'assets.id' => null,
                'subfolders.id' => null,
            ])
            ->andWhere(['not', ['folders.parentId' => null]])
            ->andWhere(['not', ['folders.path' => null]]);

        if ($this->volume) {
            $volumeHandles = explode(',', $this->volume);
            $volumeIds = [];
            $volumesService = Craft::$app->getVolumes();
            foreach ($volumeHandles as $handle) {
                $volume = $volumesService->getVolumeByHandle($handle);
                if (!$volume) {
                    $this->stderr("Invalid volume handle: $handle\n");
                    return ExitCode::UNSPECIFIED_ERROR;
                }
                $volumeIds[] = $volume->id;
            }

            $query->andWhere(['folders.volumeId' => $volumeIds]);
        }

        $emptyFolderIds = $query->column();

        if (empty($emptyFolderIds)) {
            $this->stdout("No empty folders found.\n");
            return ExitCode::OK;
        }

        $message = sprintf(
            'Deleting %s empty %s',
            count($emptyFolderIds),
            count($emptyFolderIds) === 1 ? 'folder' : 'folders',
        );

        $this->do($message, function() use ($emptyFolderIds) {
            Craft::$app->getAssets()->deleteFoldersByIds($emptyFolderIds);
        });

        return ExitCode::OK;
    }
}
