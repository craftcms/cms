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
        validateSelection: \$selectedItems => Garnish.hasAttr(\$selectedItems.find('.element'), 'data-replaceable'),
        activate: \$selectedItems => {
            $('.replaceFile').remove();

            const \$element = \$selectedItems.find('.element');
            const \$fileInput = $('<input type="file" name="replaceFile" class="replaceFile" style="display: none;"/>').appendTo(Garnish.\$bod);
            const options = Craft.elementIndex._currentUploaderSettings;

            options.url = Craft.getActionUrl('assets/replace-file');
            options.dropZone = null;
            options.fileInput = \$fileInput;
            options.paramName = 'replaceFile';

            const tempUploader = new Craft.Uploader(\$fileInput, options);
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
