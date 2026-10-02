<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\ViewModels;

use CraftCms\Cms\Cp\Html\ElementHtml;
use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Element\Contracts\NestedElementInterface;
use CraftCms\Cms\Element\ElementIndexes;
use CraftCms\Cms\Element\ElementIndexSourceSettings;
use CraftCms\Cms\Element\ElementSources;
use CraftCms\Cms\Element\Enums\ElementIndexViewMode;
use CraftCms\Cms\Element\NestedElementManager;
use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\FieldLayout\FieldLayout;
use CraftCms\Cms\Http\EmbeddedNestedElementScope;
use CraftCms\Cms\Http\Requests\ElementIndexRequest;
use CraftCms\Cms\Support\Facades\HtmlStack;
use Illuminate\Routing\Redirector;
use Override;

/**
 * @since 6.0.0
 */
class EmbeddedIndexViewModel extends ContentIndexViewModel
{
    protected const string RENDER_CONTEXT = ElementSources::CONTEXT_EMBEDDED_INDEX;

    private ?int $unfilteredTotal = null;

    private readonly EmbeddedNestedElementScope $scope;

    private readonly ElementIndexSourceSettings $indexSource;

    /**
     * @param  class-string<ElementInterface>  $elementType
     * @param  array<string, mixed>|null  $config
     */
    public function __construct(
        string $elementType,
        ElementIndexRequest $request,
        ?EmbeddedNestedElementScope $scope = null,
        ?array $config = null,
    ) {
        $this->scope = $scope ?? new EmbeddedNestedElementScope($request);
        $this->indexSource = $this->scope->indexSource($elementType, $config);

        parent::__construct($elementType, $request);
    }

    /**
     * Builds an embedded index against an owner without making an HTTP request.
     *
     * @param  class-string<ElementInterface>  $elementType
     * @param  array<string, mixed>  $config
     */
    public static function forOwner(
        string $elementType,
        ElementInterface $owner,
        string $attribute,
        array $config,
    ): self {
        $currentRequest = request();
        $request = ElementIndexRequest::create('/', 'POST', [
            'elementType' => $elementType,
            'context' => ElementSources::CONTEXT_EMBEDDED_INDEX,
            'source' => '__IMP__',
            'ownerElementType' => $owner::class,
            'ownerId' => $owner->id,
            'ownerSiteId' => $owner->siteId,
            'attribute' => $attribute,
            'viewMode' => $config['defaultViewMode'],
            'viewState' => [
                'mode' => $config['defaultViewMode'],
            ],
        ], server: array_filter([
            'HTTP_USER_AGENT' => $currentRequest->userAgent(),
        ]));
        $request->setContainer(app())->setRedirector(app(Redirector::class));
        $request->setUserResolver($currentRequest->getUserResolver());
        $request->setRouteResolver($currentRequest->getRouteResolver());

        if ($currentRequest->hasSession()) {
            $request->setLaravelSession($currentRequest->session());
        }

        $scope = new EmbeddedNestedElementScope($request, $owner);

        return new self($elementType, $request, $scope, $config);
    }

    /** @return array<string, mixed> */
    public function payload(bool $withAssets = true): array
    {
        if (! $withAssets) {
            return $this->toArray();
        }

        $payload = null;
        $fragment = HtmlStack::capture(function () use (&$payload): string {
            $payload = $this->toArray();

            return '';
        });

        return [
            ...$payload,
            'headHtml' => $fragment->headHtml,
            'bodyHtml' => $fragment->bodyHtml,
        ];
    }

    #[Override]
    public function pagination(): array
    {
        return [...parent::pagination(), 'unfilteredTotal' => $this->unfilteredTotal()];
    }

    #[Override]
    protected function resolveSourceSettings(): ElementIndexSourceSettings
    {
        return $this->indexSource;
    }

    private function unfilteredTotal(): int
    {
        [$sourceKey, $source] = $this->sourceState();

        return $this->unfilteredTotal ??= $this->elementType::indexElementCount(
            app(ElementIndexes::class)->buildQueryState(
                elementType: $this->elementType,
                source: $source,
                baseCriteria: $this->baseCriteria(),
            )['query'],
            $sourceKey,
        );
    }

    public function reorderable(): bool
    {
        if (
            $this->indexSource->static ||
            ! $this->indexSource->sortable ||
            ! $this->scope->canReorder() ||
            ($this->search() !== null && $this->search() !== '') ||
            $this->status() !== ''
        ) {
            return false;
        }

        $condition = $this->request->condition();

        if ($condition !== null && $condition->getConditionRules()->getRules() !== []) {
            return false;
        }

        $primarySort = $this->sort()[0] ?? null;

        return ($primarySort['field'] ?? null) === 'sortOrder' && ($primarySort['direction'] ?? 'asc') === 'asc';
    }

    /** @return list<array<string, mixed>> */
    public function fieldLayouts(): array
    {
        return array_map(
            fn (FieldLayout $layout): array => ['type' => $layout->type, ...($layout->getConfig() ?? [])],
            $this->indexSource->fieldLayouts ?? [],
        );
    }

    #[Override]
    protected function baseCriteria(): array
    {
        return $this->indexSource->criteria;
    }

    #[Override]
    protected function extraRowData(ElementInterface $element): array
    {
        $cardConfig = $this->cardConfig($element);

        return [
            'siteId' => $element->siteId,
            'ownerId' => $element instanceof NestedElementInterface ? $element->getOwnerId() : null,
            'editUrl' => $this->editUrl($element),
            'ownerIsCanonical' => app(ElementHtml::class)->elementOwnerIsCanonical($element),
            'isUnpublishedDraft' => $element->getIsUnpublishedDraft(),
            'actionMenuItems' => app(ElementHtml::class)->elementCardActionItems($element, $cardConfig),
            'cardAttributes' => app(ElementHtml::class)->elementCardAttributes($element, $cardConfig),
            ...($element instanceof Entry ? ['entryTypeId' => $element->typeId] : []),
        ];
    }

    #[Override]
    protected function editUrl(ElementInterface $element): ?string
    {
        if (! $element instanceof NestedElementInterface) {
            return null;
        }

        return NestedElementManager::elementEditUrl(
            $element,
            $this->indexSource->fieldId,
            prevalidate: $this->indexSource->prevalidate,
        );
    }

    /** @return array<string, mixed> */
    #[Override]
    protected function cardConfig(ElementInterface $element): array
    {
        $static = $this->indexSource->static;

        return [
            'context' => $this->indexSource->fieldId !== null ? 'field' : 'embedded-index',
            'attributes' => ['data' => ['cp-url' => $this->editUrl($element)]],
            'hyperlink' => true,
            'autoReload' => $this->mode() !== ElementIndexViewMode::Cards->value,
            'showStatus' => true,
            'showActionMenu' => true,
            'showNestedActions' => ! $static,
            'nestedActionEvents' => ! $static,
            'sortable' => ! $static && $this->indexSource->sortable,
            'canPaste' => ! $static && $this->indexSource->canPaste,
            'showInGrid' => $this->request->boolean('showInGrid'),
        ];
    }
}
