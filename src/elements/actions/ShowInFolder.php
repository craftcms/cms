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
use yii\base\Exception;

/**
 * ShowInFolder represents a Show In Folder element action.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 5.0.0
 */
class ShowInFolder extends ElementAction
{
    /**
     * @inheritdoc
     */
    public function getTriggerLabel(): string
    {
        return Craft::t('app', 'Show in folder');
    }

    /**
     * @inheritdoc
     */
    public function getTriggerHtml(): ?string
    {
        if ($this->elementType !== Asset::class) {
            throw new Exception("Show in folder is only available for Assets.");
        }

        Craft::$app->getView()->registerJsWithVars(fn($type) => <<<JS
(() => {
    new Craft.ElementActionTrigger({
        type: $type,
        bulk: false,
        activate: (selectedItem, elementIndex) => {
          const data = {
            'assetId': selectedItem.find('.element:first').data('id')
          }
          
          Craft.sendActionRequest('POST', 'assets/show-in-folder', {data})
          .then(({data}) => {
            elementIndex.sourcePath = data.sourcePath;
            elementIndex.stopSearching();
            
            // prevent searching in subfolders - we want the exact folder the asset belongs to
            elementIndex.setSelecetedSourceState('includeSubfolders', false);
           
            // search for the selected asset's filename
            elementIndex.\$search.val(data.filename);
            elementIndex.\$search.trigger('input');
          })
          .catch((e) => {
            Craft.cp.displayError(e?.response?.data?.message);
          });
        },
    });
})();
JS, [static::class]);

        return null;
    }
}
