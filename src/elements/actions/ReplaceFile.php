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
 * @since 3.0
 */
class ReplaceFile extends ElementAction
{
    // Public Methods
    // =========================================================================

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

        $js = <<<EOD
(function()
{
    var trigger = new Craft.ElementActionTrigger({
        type: {$type},
        batch: false,
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
EOD;

        Craft::$app->getView()->registerJs($js);
    }
}
