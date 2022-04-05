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
    public function getTriggerHtml()
    {
        $type = Json::encode(static::class);

        $js = <<<JS
(() => {
    new Craft.ElementActionTrigger({
        type: {$type},
        batch: false,
        validateSelection: function(\$selectedItems)
        {
            return Garnish.hasAttr(\$selectedItems.find('.element'), 'data-replaceable');
        },
        activate: function(\$selectedItems)
        {
            $('.replaceFile').remove();

            var \$element = \$selectedItems.find('.element'),
                \$fileInput = $('<input type="file" name="replaceFile" class="replaceFile" style="display: none;"/>').appendTo(Garnish.\$bod),
                options = Craft.elementIndex._currentUploaderSettings;

            options.url = Craft.getActionUrl('assets/replace-file');
            options.dropZone = null;
            options.fileInput = \$fileInput;
            options.paramName = 'replaceFile';

            var tempUploader = new Craft.Uploader(\$fileInput, options);
            tempUploader.setParams({
                assetId: \$element.data('id')
            });

            \$fileInput.click();
        }
    });
})();
JS;

        Craft::$app->getView()->registerJs($js);
        return null;
    }
}
