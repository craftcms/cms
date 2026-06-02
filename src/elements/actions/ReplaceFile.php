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
 * ReplaceFile represents a Replace File element action.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 */
class ReplaceFile extends ElementAction
{
    /**
     * @inheritdoc
     */
    public function getTriggerLabel(): string
    {
        return Craft::t('app', 'Replace file');
    }

    /**
     * @inheritdoc
     */
    public function getTriggerHtml(): ?string
    {
        Craft::$app->getView()->registerJsWithVars(fn($type) => <<<JS
(() => {
    new Craft.ElementActionTrigger({
        type: $type,
        bulk: false,
        validateSelection: (selectedItems, elementIndex) => Garnish.hasAttr(selectedItems.find('.element'), 'data-replaceable'),
        activate: (selectedItems, elementIndex) => {
            $('.replaceFile').remove();

            const \$element = selectedItems.find('.element');
            const \$fileInput = $('<input type="file" name="replaceFile" class="replaceFile" style="display: none;"/>').appendTo(Garnish.\$bod);
            const settings = elementIndex._currentUploaderSettings;

            settings.dropZone = null;
            settings.fileInput = \$fileInput;
            settings.paramName = 'replaceFile';
            settings.replace = true;
            settings.events = {};

            const fileuploaddone = settings.events?.fileuploaddone;
            settings.events = Object.assign({}, settings.events || {}, {
              fileuploaddone: (event, data = null) => {
                const result = event instanceof CustomEvent ? event.detail : data.result;
                if (!result.error) {
                  Craft.cp.displayNotice(Craft.t('app', 'New file uploaded.'));
                  // update the element row
                  if (Craft.broadcaster) {
                      Craft.broadcaster.postMessage({
                        event: 'saveElement',
                        id: result.assetId,
                      });
                  }
                }
                if (fileuploaddone) {
                  fileuploaddone(event, data);                      
                }
              }
            });

            const tempUploader = Craft.createUploader(elementIndex.uploader.fsType, \$fileInput, settings);
            tempUploader.setParams({
                assetId: \$element.data('id')
            });

            \$fileInput.click();
        },
    });
})();
JS, [static::class]);

        return null;
    }
}
