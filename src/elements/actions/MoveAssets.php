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

/**
 * MoveAssets represents a Move asset action.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.4.0
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
    public function getTriggerHtml(): ?string
    {
        Craft::$app->getView()->registerJsWithVars(function($actionClass) {
            return <<<JS
(() => {
  const groupItems = function(\$items) {
    const \$folders = \$items.has('.element[data-is-folder]');
    const \$assets = \$items.not(\$folders);
    return [\$folders, \$assets];
  };

  const peerFiles = function(\$folders, \$assets) {
    return !!(\$folders.length || \$assets.has('.element[data-peer-file]').length)
  };

  new Craft.ElementActionTrigger({
    type: $actionClass,
    bulk: true,
    requireId: false,
    validateSelection: (selectedItems, elementIndex) => {
      for (let i = 0; i < selectedItems.length; i++) {
        if (!Garnish.hasAttr(selectedItems.eq(i).find('.element'), 'data-movable')) {
          return false;
        }
      }
      return elementIndex.getMoveTargetSourceKeys(peerFiles(...groupItems(selectedItems))).length;
    },
    activate: (selectedItems, elementIndex) => {
      const [\$folders, \$assets] = groupItems(selectedItems);
      const selectedFolderIds = \$folders.toArray().map((item) => {
        return parseInt($(item).find('.element:first').data('folder-id'));
      });
      const disabledFolderIds = selectedFolderIds.slice();
      if (elementIndex.sourcePath.length) {
        const currentFolder = elementIndex.sourcePath[elementIndex.sourcePath.length - 1];
        if (currentFolder.folderId) {
          disabledFolderIds.push(currentFolder.folderId);
        }
      }
      const selectedAssetIds = \$assets.toArray().map((item) => {
        return parseInt($(item).data('id'));
      });

      new Craft.VolumeFolderSelectorModal({
        sources: elementIndex.getMoveTargetSourceKeys(peerFiles(\$folders, \$assets)),
        showTitle: true,
        modalTitle: Craft.t('app', 'Move to'),
        selectBtnLabel: Craft.t('app', 'Move'),
        disabledFolderIds: disabledFolderIds,
        indexSettings: {
          defaultSource: elementIndex.sourceKey,
          defaultSourcePath: elementIndex.sourcePath,
        },
        onSelect: ([targetFolder]) => {
          const mover = new Craft.AssetMover();
          mover.moveFolders(selectedFolderIds, targetFolder.folderId).then((totalFoldersMoved) => {
            mover.moveAssets(selectedAssetIds, targetFolder.folderId).then((totalAssetsMoved) => {
              const totalItemsMoved = totalFoldersMoved + totalAssetsMoved;
              if (totalItemsMoved) {
                Craft.cp.displayNotice(Craft.t('app', '{totalItems, plural, =1{Item} other{Items}} moved.', {
                  totalItems: totalItemsMoved,
                }));
                elementIndex.updateElements(true);
              }
            });
          });
        },
      });
    },
  });
})();
JS;
        }, [
            static::class,
        ]);

        return null;
    }
}
