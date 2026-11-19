<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\ViewModels;

use CraftCms\Cms\Cp\Html\ElementHtml;
use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Element\Contracts\NestedElementInterface;
use CraftCms\Cms\Element\ElementIndexes;
use CraftCms\Cms\Element\ElementIndexSourceSettings;
use CraftCms\Cms\Element\ElementSources;
use CraftCms\Cms\Element\EmbeddedElementIndex;
use CraftCms\Cms\Element\Enums\ElementIndexViewMode;
use CraftCms\Cms\Element\NestedElementManager;
use CraftCms\Cms\Element\Queries\Contracts\ElementQueryInterface;
use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\FieldLayout\FieldLayout;
use CraftCms\Cms\Http\Contracts\EmbeddedIndexScope;
use CraftCms\Cms\Http\EmbeddedElementIndexScope;
use CraftCms\Cms\Http\EmbeddedNestedElementScope;
use CraftCms\Cms\Http\Requests\ElementIndexRequest;
use CraftCms\Cms\Site\Data\Site;
use CraftCms\Cms\Support\Facades\HtmlStack;
use Illuminate\Routing\Redirector;
use Illuminate\Support\Collection;
use Override;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * @since 6.0.0
 */
class EmbeddedIndexViewModel extends ContentIndexViewModel
{
    protected const string RENDER_CONTEXT = ElementSources::CONTEXT_EMBEDDED_INDEX;

    private ?int $unfilteredTotal = null;

    private ?ElementQueryInterface $restrictedQuery = null;

    private readonly EmbeddedIndexScope $scope;

    private readonly ElementIndexSourceSettings $indexSource;

    /**
     * @param  class-string<ElementInterface>  $elementType
     * @param  array<string, mixed>|null  $config
     */
    public function __construct(
        string $elementType,
        ElementIndexRequest $request,
        ?EmbeddedIndexScope $scope = null,
        ?array $config = null,
    ) {
        $this->scope = $scope ?? new EmbeddedNestedElementScope($request);
        $this->indexSource = $this->scope->indexSource($elementType, $config);

        parent::__construct($elementType, $request);
    }

    /**
     * Builds an embedded index for a request, scoped by its {@see EmbeddedElementIndex} token, its owner, or its criteria.
     */
    public static function forRequest(ElementIndexRequest $request): self
    {
        return new self($request->elementType(), $request, $request->embeddedIndexScope());
    }

    /**
     * Returns the query an embedded index request lists its elements from, with its scope, filters, search, status and site,
     * such as for exports.
     */
    public static function listedQuery(ElementIndexRequest $request): ElementQueryInterface
    {
        return self::forRequest($request)->resolveQuery();
    }

    /**
     * Returns the query an embedded index request lists its elements from, for element actions and inline editing.
     *
     * @throws HttpException if the index is read-only
     */
    public static function editableQuery(ElementIndexRequest $request): ElementQueryInterface
    {
        $viewModel = self::forRequest($request);

        abort_if($viewModel->readOnly(), 403, 'This element index is read-only.');

        return $viewModel->resolveQuery();
    }

    /**
     * Builds an embedded index for an {@see EmbeddedElementIndex} without making an HTTP request.
     */
    public static function forDefinition(EmbeddedElementIndex $index): self
    {
        $defaultViewMode = $index->sourceSettings()->defaultViewMode;
        $request = self::internalRequest([
            'elementType' => $index->elementType(),
            'source' => ElementIndexSourceSettings::NESTED_KEY,
            'viewMode' => $defaultViewMode,
            'viewState' => ['mode' => $defaultViewMode],
        ]);

        return new self($index->elementType(), $request, new EmbeddedElementIndexScope($index));
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
        $request = self::internalRequest([
            'elementType' => $elementType,
            'source' => ElementIndexSourceSettings::NESTED_KEY,
            'ownerElementType' => $owner::class,
            'ownerId' => $owner->id,
            'ownerSiteId' => $owner->siteId,
            'attribute' => $attribute,
            'viewMode' => $config['defaultViewMode'],
            'viewState' => [
                'mode' => $config['defaultViewMode'],
            ],
        ]);

        $scope = new EmbeddedNestedElementScope($request, $owner);

        return new self($elementType, $request, $scope, $config);
    }

    /**
     * Creates an embedded index request that carries the current request's user, session and route.
     *
     * @param  array<string, mixed>  $params
     */
    private static function internalRequest(array $params): ElementIndexRequest
    {
        $currentRequest = request();
        $request = ElementIndexRequest::create('/', 'POST', [
            'context' => ElementSources::CONTEXT_EMBEDDED_INDEX,
            ...$params,
        ], server: array_filter([
            'HTTP_USER_AGENT' => $currentRequest->userAgent(),
        ]));
        $request->setContainer(app())->setRedirector(app(Redirector::class));
        $request->setUserResolver($currentRequest->getUserResolver());
        $request->setRouteResolver($currentRequest->getRouteResolver());

        if ($currentRequest->hasSession()) {
            $request->setLaravelSession($currentRequest->session());
        }

        return $request;
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

    /**
     * Returns whether the index only lists and exports its elements, without element actions or inline editing.
     */
    public function readOnly(): bool
    {
        return $this->indexSource->static;
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

    #[Override]
    protected function resolveQuery(): ElementQueryInterface
    {
        $query = parent::resolveQuery();

        if ($this->restrictedQuery !== $query) {
            $this->scope->restrictQuery($query);
            $this->restrictedQuery = $query;
        }

        return $query;
    }

    #[Override]
    public function statusOptions(): array
    {
        // As in Craft 5, an index whose criteria set a status has no status menu
        if ($this->indexSource->owner === null && array_key_exists('status', $this->indexSource->criteria)) {
            return [];
        }

        return parent::statusOptions();
    }

    private function unfilteredTotal(): int
    {
        if ($this->unfilteredTotal !== null) {
            return $this->unfilteredTotal;
        }

        [$sourceKey, $source] = $this->sourceState();
        $query = app(ElementIndexes::class)->buildQueryState(
            elementType: $this->elementType,
            source: $source,
            baseCriteria: $this->baseCriteria(),
        )['query'];
        $this->scope->restrictQuery($query);

        return $this->unfilteredTotal = $this->elementType::indexElementCount($query, $sourceKey);
    }

    public function reorderable(): bool
    {
        if (
            $this->indexSource->static ||
            ! $this->indexSource->sortable ||
            ! $this->scope->canReorder() ||
            ($this->search() !== null && $this->search() !== '') ||
            $this->status() !== '' ||
            $this->drafts() ||
            $this->trashed()
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

    /**
     * Lists one site at a time, so each row is a single localization that its element ID identifies,
     * for selection, element actions and inline editing.
     */
    #[Override]
    protected function applySite(ElementQueryInterface $query): void
    {
        if ($this->indexSource->owner !== null) {
            parent::applySite($query);

            return;
        }

        $query->siteId($this->siteId());
    }

    #[Override]
    public function siteId(): int
    {
        if ($this->indexSource->owner !== null) {
            return parent::siteId();
        }

        $criteriaSiteIds = $this->criteriaSiteIds();

        if ($criteriaSiteIds === null || in_array($this->site()->id, $criteriaSiteIds, true)) {
            return $this->site()->id;
        }

        return $this->selectableSites()->first()->id ?? $criteriaSiteIds[0];
    }

    #[Override]
    public function showSiteMenu(): bool
    {
        if ($this->indexSource->owner === null && count($this->criteriaSiteIds() ?? []) === 1) {
            return false;
        }

        return parent::showSiteMenu();
    }

    #[Override]
    protected function selectableSites(): Collection
    {
        $sites = parent::selectableSites();
        $criteriaSiteIds = $this->indexSource->owner === null ? $this->criteriaSiteIds() : null;

        if ($criteriaSiteIds === null) {
            return $sites;
        }

        return $sites
            ->filter(fn (Site $site): bool => in_array($site->id, $criteriaSiteIds, true))
            ->values();
    }

    /**
     * Returns the sites an index without an owner can list, from the `siteId` in its criteria,
     * or `null` if any site can be listed.
     *
     * Values that don't name sites by ID, like `*` or a `not` list, allow any site;
     * the criteria still limit which elements each site lists.
     *
     * @return non-empty-list<int>|null
     */
    private function criteriaSiteIds(): ?array
    {
        $siteId = $this->indexSource->criteria['siteId'] ?? null;
        $siteIds = is_array($siteId) ? $siteId : [$siteId];

        if ($siteIds === [] || array_any($siteIds, fn (mixed $id): bool => ! is_numeric($id))) {
            return null;
        }

        return array_values(array_unique(array_map(intval(...), $siteIds)));
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
        if ($element->trashed) {
            return null;
        }

        if (! $element instanceof NestedElementInterface) {
            return parent::editUrl($element);
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
            'hyperlink' => ! $element->trashed,
            'autoReload' => $this->mode() !== ElementIndexViewMode::Cards->value,
            'showStatus' => true,
            'showActionMenu' => ! $element->trashed,
            'showNestedActions' => ! $static,
            'nestedActionEvents' => ! $static,
            'sortable' => ! $static && $this->indexSource->sortable,
            'canPaste' => ! $static && $this->indexSource->canPaste,
            'showInGrid' => $this->request->boolean('showInGrid'),
        ];
    }
}
