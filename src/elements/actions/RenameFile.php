<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\elements\actions;

use Craft;
use craft\base\ElementAction;
use craft\helpers\Json;

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
    public function getTriggerHtml()
    {
        $type = Json::encode(static::class);
        $prompt = Json::encode(Craft::t('app', 'Enter the new filename'));

        $js = <<<JS
(() => {
    new Craft.ElementActionTrigger({
        type: {$type},
        batch: false,
        validateSelection: function(\$selectedItems)
        {
            return Garnish.hasAttr(\$selectedItems.find('.element'), 'data-movable');
        },
        activate: function(\$selectedItems)
        {
            var \$element = \$selectedItems.find('.element'),
                assetId = \$element.data('id'),
                oldName = \$element.data('url').split('/').pop();

            if (oldName.indexOf('?') !== -1)
            {
                oldName = oldName.split('?').shift();
            }

            var newName = prompt({$prompt}, oldName);

            if (!newName || newName == oldName)
            {
                return;
            }

            Craft.elementIndex.setIndexBusy();

            var data = {
                assetId:   assetId,
                folderId: Craft.elementIndex.\$source.data('folder-id'),
                filename: newName
            };

            var handleRename = function(response, textStatus)
            {
                Craft.elementIndex.setIndexAvailable();

                if (textStatus === 'success')
                {
                    if (response.conflict)
                    {
                        alert(response.conflict);
                        this.activate(\$selectedItems);
                        return;
                    }

                    if (response.success)
                    {
                        Craft.elementIndex.updateElements();

                        // If assets were just merged we should get the reference tags updated right away
                        Craft.cp.runQueue();
                    }

                    if (response.error)
                    {
                        alert(response.error);
                    }
                }
            }.bind(this);

            Craft.postActionRequest('assets/move-asset', data, handleRename);
        }
    });
})();
JS;

        Craft::$app->getView()->registerJs($js);
        return null;
    }
}
