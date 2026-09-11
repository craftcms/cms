<?php

declare(strict_types=1);

namespace CraftCms\Cms\Asset\Actions;

use CraftCms\Cms\Element\Actions\ElementAction;
use CraftCms\Cms\Support\Facades\HtmlStack;

use function CraftCms\Cms\t;

class ReplaceFile extends ElementAction
{
    #[\Override]
    public function getTriggerLabel(): string
    {
        return t('Replace file');
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
        bulk: false,
        validateSelection: (selectedItems, elementIndex) => Garnish.hasAttr(selectedItems.find('.element'), 'data-replaceable'),
        activate: (selectedItems, elementIndex) => {
            $('.replaceFile').remove();

            const \$element = selectedItems.find('.element');
            const \$fileInput = $('<input type="file" name="replaceFile" class="replaceFile" style="display: none;"/>').appendTo(Garnish.\$bod);
            const settings = {...elementIndex._currentUploaderSettings};

            settings.dropZone = null;
            settings.fileInput = \$fileInput[0];
            settings.replace = true;
            settings.on = {
              done: ({result}) => {
                if (!result.error) {
                  Craft.cp.displayNotice(Craft.t('app', 'New file uploaded.'));
                  Craft.broadcaster?.postMessage({event: 'saveElement', id: result.assetId});
                }
              },
              fail: ({error, canceled}) => {
                if (!canceled) {
                  Craft.cp.displayError(error instanceof Error ? error.message : Craft.t('app', 'Replace file failed.'));
                }
              },
            };

            const tempUploader = new Craft.Uploaders.Uploader(\$fileInput[0], settings);
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
