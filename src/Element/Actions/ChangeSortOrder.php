<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element\Actions;

use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Support\Facades\HtmlStack;

use function CraftCms\Cms\t;

class ChangeSortOrder extends ElementAction
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
        return t('Move to page…');
    }

    public function getTriggerHtml(): ?string
    {
        HtmlStack::jsWithVars(
            fn ($type, $params) => <<<JS
(() => {
  new Craft.ElementActionTrigger({
    type: $type,
    bulk: true,
    validateSelection: (selectedItems, elementIndex) => {
      return (
        elementIndex.sortable &&
        elementIndex.totalResults &&
        elementIndex.totalResults > elementIndex.settings.batchSize
      );
    },
    activate: (selectedItems, elementIndex) => {
      const totalPages = Math.ceil(elementIndex.totalResults / elementIndex.settings.batchSize);
      const container = $('<div/>');
      const flex = $('<div/>', {class: 'flex flex-nowrap'});
      const select = Craft.ui.createSelect({
        options: [...Array(totalPages).keys()].map(num => ({label: num + 1, value: num + 1})),
        value: elementIndex.page === totalPages ? elementIndex.page - 1 : elementIndex.page + 1,
      }).appendTo(flex);
      select.find('option[value=' + elementIndex.page + ']').attr('disabled', 'disabled');
      const button = Craft.ui.createSubmitButton({
        label: Craft.t('app', 'Move'),
        spinner: true,
      }).appendTo(flex);
      Craft.ui.createField(flex, {
        label: Craft.t('app', 'Choose a page'),
      }).appendTo(container);
      const hud = new Garnish.HUD(elementIndex.\$actionMenuBtn, container);

      button.one('activate', async () => {
        const page = parseInt(select.find('select').val());
        moveToPage(selectedItems, elementIndex, page, button, hud);
      });
    },
  })

  async function moveToPage(selectedItems, elementIndex, page, button, hud) {
    button.addClass('loading');
    await elementIndex.settings.onBeforeMoveElementsToPage(selectedItems, page);

    const data = Object.assign($params, {
      elementIds: elementIndex.getSelectedElementIds(),
      offset: (page - 1) * elementIndex.settings.batchSize,
    })

    const elementEditor = elementIndex.\$container.closest('form').data('elementEditor');
    if (elementEditor) {
      data.ownerId = elementEditor.getDraftElementId(data.ownerId);
    }

    let response;
    try {
      response = await Craft.sendActionRequest('POST', 'nested-elements/reorder', {data});
    } catch (e) {
      Craft.cp.displayError(e?.response?.data?.error);
      return;
    } finally {
      button.removeClass('loading');
    }

    hud.hide();
    Craft.cp.displayNotice(response.data.message);
    await elementIndex.settings.onMoveElementsToPage(selectedItems, page);
    elementIndex.setPage(page);
    elementIndex.updateElements(true, true)
  }
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
