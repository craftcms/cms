<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\elements\actions;

use Craft;
use craft\base\ElementAction;

/**
 * RenameFile represents a Rename File element action.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 */
class RenameFile extends ElementAction
{
    /**
     * @inheritdoc
     */
    public function getTriggerLabel(): string
    {
        return Craft::t('app', 'Rename file');
    }

    /**
     * @inheritdoc
     */
    public function getTriggerHtml(): ?string
    {
        Craft::$app->getView()->registerJsWithVars(
            fn($type, $prompt) => <<<JS
(() => {
    new Craft.ElementActionTrigger({
        type: $type,
        bulk: false,
        validateSelection: (selectedItems, elementIndex) => Garnish.hasAttr(selectedItems.find('.element'), 'data-movable'),
        activate: (selectedItems, elementIndex) => {
            const \$element = selectedItems.find('.element')
            const assetId = \$element.data('id');
            let oldName = \$element.data('filename');

            const newName = prompt($prompt, oldName);

            if (!newName || newName == oldName)
            {
                return;
            }

            elementIndex.setIndexBusy();

            let currentFolderId = elementIndex.\$source.data('folder-id');
            const currentFolder = elementIndex.sourcePath[elementIndex.sourcePath.length - 1];
            if (currentFolder && currentFolder.folderId) {
              currentFolderId = currentFolder.folderId;
            }

            const data = {
                assetId:   assetId,
                folderId: currentFolderId,
                filename: newName
            };
            
            Craft.sendActionRequest('POST', 'assets/move-asset', {data})
                .then(response => {
                    if (response.data.conflict) {
                        alert(response.data.conflict);
                        this.activate(selectedItems);
                        return;
                    }

                    if (response.data.success) {
                        elementIndex.updateElements();

                        // If assets were just merged we should get the reference tags updated right away
                        Craft.cp.runQueue();
                    }
                })
                .catch(({response}) => {
                    alert(response.data.message)
                })
                .finally(() => {
                    elementIndex.setIndexAvailable();
                });
        },
    });
})();
JS,
            [
                static::class,
                Craft::t('app', 'Enter the new filename'),
            ]);

        return null;
    }
}
