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
 * DownloadAssetFile represents a Download Asset element action.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 */
class DownloadAssetFile extends ElementAction
{
    /**
     * @inheritdoc
     */
    public function getTriggerLabel(): string
    {
        return Craft::t('app', 'Download');
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
        activate: \$selectedItems => {
            var \$form = Craft.createForm().appendTo(Garnish.\$bod);
            $(Craft.getCsrfInput()).appendTo(\$form);
            $('<input/>', {
                type: 'hidden',
                name: 'action',
                value: 'assets/download-asset'
            }).appendTo(\$form);
            \$selectedItems.each(function() {
                $('<input/>', {
                    type: 'hidden',
                    name: 'assetId[]',
                    value: $(this).data('id')
                }).appendTo(\$form);
            });
            $('<input/>', {
                type: 'submit',
                value: 'Submit',
            }).appendTo(\$form);
            \$form.submit();
            \$form.remove();
        },
    });
})();
JS, [static::class]);

        return null;
    }
}
