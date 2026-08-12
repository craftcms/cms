<?php

declare(strict_types=1);

namespace CraftCms\Cms\Asset\Actions;

use CraftCms\Cms\Element\Actions\ElementAction;
use CraftCms\Cms\Support\Facades\HtmlStack;

use function CraftCms\Cms\t;

class DownloadAssetFile extends ElementAction
{
    #[\Override]
    public function getTriggerLabel(): string
    {
        return t('Download');
    }

    #[\Override]
    public static function supportsBulk(): bool
    {
        return false;
    }

    public function getTriggerHtml(): ?string
    {
        HtmlStack::jsWithVars(fn ($type) => <<<JS
(() => {
    new Craft.ElementActionTrigger({
        type: $type,
        activate: (selectedItems, elementIndex) => {
            var \$form = Craft.createForm().appendTo(Garnish.\$bod);
            $(Craft.getCsrfInput()).appendTo(\$form);
            $('<input/>', {
                type: 'hidden',
                name: 'action',
                value: 'assets/download-asset'
            }).appendTo(\$form);
            selectedItems.each(function() {
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
