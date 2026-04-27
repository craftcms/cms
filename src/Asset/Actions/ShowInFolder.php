<?php

declare(strict_types=1);

namespace CraftCms\Cms\Asset\Actions;

use CraftCms\Cms\Asset\Elements\Asset;
use CraftCms\Cms\Element\Actions\ElementAction;
use CraftCms\Cms\Support\Facades\HtmlStack;
use RuntimeException;

use function CraftCms\Cms\t;

class ShowInFolder extends ElementAction
{
    #[\Override]
    public function getTriggerLabel(): string
    {
        return t('Show in folder');
    }

    public function getTriggerHtml(): ?string
    {
        if ($this->elementType !== Asset::class) {
            throw new RuntimeException('Show in folder is only available for Assets.');
        }

        HtmlStack::jsWithVars(fn ($type) => <<<JS
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
    })
})();
JS, [static::class]);

        return null;
    }
}
