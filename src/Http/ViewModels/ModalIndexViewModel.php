<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\ViewModels;

use CraftCms\Cms\Asset\Elements\Asset;
use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Element\ElementSources;
use CraftCms\Cms\Http\Requests\ElementIndexRequest;

/**
 * The index payload for the element selector modal.
 *
 * The same shape the index screens render from, resolved in the `modal` context
 * and narrowed to the source keys the opener allows — a relation field may only
 * offer some of an element type's sources.
 */
class ModalIndexViewModel extends ContentIndexViewModel
{
    protected const string RENDER_CONTEXT = ElementSources::CONTEXT_MODAL;

    /**
     * @param  class-string<ElementInterface>  $elementType
     * @param  list<string>|null  $restrictToSources  Source keys the opener allows, or null for all of them.
     */
    public function __construct(
        string $elementType,
        ElementIndexRequest $request,
        private readonly ?array $restrictToSources = null,
        ?string $page = null,
    ) {
        parent::__construct($elementType, $request, $page);
    }

    /**
     * The element metadata a relation field needs back from a selection.
     *
     * Index rows are otherwise column HTML keyed by attribute — enough to render
     * a table, but not to describe the element. These are the same keys the
     * legacy modal read off each row's chip via `Craft.getElementInfo()`, which
     * `onModalSelect()` and `app/render-elements` both still consume.
     *
     * @return array<string, mixed>
     */
    #[\Override]
    protected function extraRowData(ElementInterface $element): array
    {
        return [
            // Nested rather than merged into the row.
            //
            // `tableRows()` spreads extra row data *before* the visible columns,
            // so any key that matches a column attribute is overwritten by that
            // column's rendered HTML — `status` came back as a `<craft-badge>`,
            // and `kind` would come back as "Image" rather than "image" whenever
            // the File Kind column happened to be visible. A key of its own can't
            // collide with a column name.
            'elementInfo' => [
                'siteId' => $element->siteId,
                'label' => $element->getUiLabel(),
                'status' => $element->getStatus(),
                'url' => $element->getUrl(),
                // Per element, not per type: an asset with no preview renders no
                // thumb even though its element type has them.
                'hasThumb' => $element->getThumbHtml(30) !== null,
                ...$this->typeSpecificRowData($element),
            ],
        ];
    }

    /**
     * Metadata only some element types carry.
     *
     * The modal renders every element type through this one view model, so
     * element-type view models (and their own `extraRowData()`) never run here.
     * Callers that need more than the common keys — the Markdown field, which
     * decides between an image embed and a link — would otherwise have no way to
     * get it.
     *
     * @return array<string, mixed>
     */
    protected function typeSpecificRowData(ElementInterface $element): array
    {
        if ($element instanceof Asset) {
            return [
                'kind' => $element->kind,
                'alt' => $element->alt,
                // What the folder picker selects; the element's own id is the
                // asset's, not the folder's.
                ...($element->isFolder ? ['folderId' => $element->folderId] : []),
            ];
        }

        return [];
    }

    /**
     * Unlike the index screens', these resolve in the `modal` context and honor
     * the opener's source restriction.
     *
     * @return list<array<string, mixed>>
     */
    #[\Override]
    public function sources(): array
    {
        return $this->indexState()->sources(
            $this->elementType,
            static::RENDER_CONTEXT,
            withDisabled: true,
            page: $this->page,
            restrictTo: $this->restrictToSources,
        )->all();
    }
}
