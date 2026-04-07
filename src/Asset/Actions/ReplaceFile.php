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
