<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element\Actions;

use craft\base\ElementInterface;
use CraftCms\Cms\Support\Facades\HtmlStack;

use function CraftCms\Cms\t;

class MoveUp extends ElementAction
{
    public function __construct(
        private readonly ElementInterface $owner,
        private readonly string $attribute,
        $config = [],
    ) {
        parent::__construct($config);
    }

    #[\Override]
    public function getTriggerLabel(): string
    {
        return t('Move forward'); // up
    }

    public function getTriggerHtml(): ?string
    {
        HtmlStack::jsWithVars(
            fn ($type, $params) => <<<JS
(() => {
  new Craft.ElementActionTrigger({
    type: $type,
    bulk: false,
    validateSelection: (selectedItems, elementIndex) => {
      return (
        elementIndex.sortable &&
        selectedItems.parent().children().first().data('id') !== selectedItems.data('id')
      );
    },
    activate: async (selectedItems, elementIndex) => {
      const selectedItemIndex = Object.values(elementIndex.view.getAllElements()).indexOf(selectedItems[0]);
      const offset = selectedItemIndex - 1;
      await elementIndex.settings.onBeforeReorderElements(selectedItems, offset);

      const data = Object.assign($params, {
        elementIds: elementIndex.getSelectedElementIds(),
        offset: offset,
      });

      // swap out the ownerId with the new draft ownerId
      const elementEditor = elementIndex.\$container.closest('form').data('elementEditor');
      if (elementEditor) {
        data.ownerId = elementEditor.getDraftElementId(data.ownerId);
      }

      let response;
      try {
        response = await Craft.sendActionRequest('POST', 'nested-elements/reorder', {data});
      } catch (e) {
        Craft.cp.displayError(response.data && response.data.error);
        return;
      }

      Craft.cp.displayNotice(response.data.message);
      await elementIndex.settings.onReorderElements(selectedItems, offset);
      elementIndex.updateElements(true, true);
    },
  });
})();
JS,
            [
                static::class,
                [
                    'ownerElementType' => $this->owner::class,
                    'ownerId' => $this->owner->id,
                    'ownerSiteId' => $this->owner->siteId,
                    'attribute' => $this->attribute,
                ],
            ]);

        return null;
    }
}
