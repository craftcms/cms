<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\elements\actions;

use Craft;
use craft\base\ElementAction;
use craft\elements\Asset;
use craft\volumes\Temp;

/**
 * MoveAssets represents a Move asset action.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.8.0
 */
class MoveAssets extends ElementAction
{
    /**
     * @inheritdoc
     */
    public function getTriggerLabel(): string
    {
        return Craft::t('app', 'Move…');
    }

    /**
     * @inheritdoc
     */
    public function getTriggerHtml()
    {
        $sourceKeys = [];
        $assetsService = Craft::$app->getAssets();
        foreach (Craft::$app->getVolumes()->getViewableVolumes() as $volume) {
            if (!$volume instanceof Temp) {
                $rootFolder = $assetsService->getRootFolderByVolumeId($volume->id);
                if ($rootFolder) {
                    $sourceKeys[] = "folder:$rootFolder->uid";
                }
            }
        }

        Craft::$app->getView()->registerJsWithVars(function($actionClass, $sourceKeys) {
            return <<<JS
new Craft.ElementActionTrigger({
  type: $actionClass,
  bulk: true,
  requireId: false,
  activate: function(\$selectedItems) {
    const \$folders = \$selectedItems.has('.element[data-is-folder]');
    const \$assets = \$selectedItems.not(\$folders);
    const folderIds = \$folders.toArray().map((item) => {
      return parseInt($(item).find('.element:first').data('folder-id'));
    });
    const assetIds = \$assets.toArray().map((item) => {
      return parseInt($(item).data('id'));
    });
    
    new Craft.VolumeFolderSelectorModal({
      sources: $sourceKeys,
      showTitle: true,
      modalTitle: Craft.t('app', 'Move to'),
      selectBtnLabel: Craft.t('app', 'Move'),
      indexSettings: {
        defaultSource: Craft.elementIndex.sourceKey,
        defaultSourcePath: Craft.elementIndex.sourcePath,
        disabledFolderIds: folderIds,
      },
      onSelect: ([targetFolder]) => {
        const mover = new Craft.AssetMover();
        mover.moveFolders(folderIds, targetFolder.folderId).then((totalFoldersMoved) => {
          mover.moveAssets(assetIds, targetFolder.folderId).then((totalAssetsMoved) => {
            const totalItemsMoved = totalFoldersMoved + totalAssetsMoved;
            if (totalItemsMoved) {
              Craft.cp.displayNotice(Craft.t('app', '{totalItems, plural, =1{Item} other{Items}} moved.', {
                totalItems: totalItemsMoved,
              }));
              Craft.elementIndex.updateElements(true);
            }
          });
        });
      },
    });
  },
});
JS;
        }, [
            static::class,
            $sourceKeys,
        ]);

        return null;
    }
}
