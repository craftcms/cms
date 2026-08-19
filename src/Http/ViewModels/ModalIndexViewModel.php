<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\ViewModels;

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
