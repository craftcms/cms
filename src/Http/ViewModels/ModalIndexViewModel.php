<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\ViewModels;

use CraftCms\Cms\Asset\Data\VolumeFolder;
use CraftCms\Cms\Asset\Elements\Asset;
use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Element\ElementSources;
use CraftCms\Cms\Http\Requests\ElementIndexRequest;
use CraftCms\Cms\Image\Enums\ImageTransformMode;
use CraftCms\Cms\Support\Facades\Folders;
use CraftCms\Cms\Support\Url;
use Illuminate\Support\Facades\Gate;

use function CraftCms\Cms\t;

/**
 * The index payload for the element selector modal.
 *
 * The same shape the index screens render from, resolved in the `modal` context
 * and narrowed to the source keys the opener allows — a relation field may only
 * offer some of an element type's sources.
 *
 * Asset folders are listed alongside assets but can't be selected. Opening one
 * re-requests the index with its `folderId`, which scopes the current volume
 * source to that folder.
 *
 * @since 6.0.0
 */
class ModalIndexViewModel extends ContentIndexViewModel
{
    protected const string RENDER_CONTEXT = ElementSources::CONTEXT_MODAL;

    private bool $folderResolved = false;

    private ?VolumeFolder $resolvedFolder = null;

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
            // Top-level, unlike `elementInfo`, because they describe the row
            // rather than the selection: folders open rather than select.
            ...($element instanceof Asset && $element->isFolder ? [
                'isFolder' => true,
                'folderId' => $element->folderId,
                'folderUrl' => $this->folderUrl($element),
            ] : []),
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
                'hasThumb' => $element->getThumbHtml(30, ImageTransformMode::Fit) !== null,
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
     * Folders have no element id, so they're keyed by folder instead, as on
     * the assets index.
     */
    #[\Override]
    protected function rowId(ElementInterface $element): string|int|null
    {
        if ($element instanceof Asset && $element->isFolder) {
            return "folder:{$element->folderId}";
        }

        return $element->id;
    }

    /**
     * A click in the modal is a selection, so titles don't link — a link would
     * navigate the CP behind the modal and drop the selection being collected.
     *
     * Folders are the exception, since they can't be selected. Their labels link
     * to the folder, and a plain click opens it in the modal instead.
     */
    #[\Override]
    protected function titleLinkHtml(ElementInterface $element, string $chip): string
    {
        if (! $element instanceof Asset || ! $element->isFolder) {
            return $chip;
        }

        $folderUrl = $this->folderUrl($element);

        return $folderUrl === null
            ? $chip
            : $this->labelLinkedChipHtml($element, $folderUrl, attributes: ['data-folder-link' => true]);
    }

    /**
     * Scopes a volume source to the requested folder.
     *
     * @return array{0: ?string, 1: ?array<string, mixed>}
     */
    #[\Override]
    protected function sourceState(): array
    {
        [$sourceKey, $source] = parent::sourceState();

        $folder = $this->folder();

        if ($folder !== null && $source !== null) {
            $source['criteria']['folderId'] = $folder->id;
        }

        return [$sourceKey, $source];
    }

    /**
     * Uploads land in the folder on screen, which is the requested folder once
     * one is open rather than the volume's root.
     *
     * @return array<string, mixed>|null
     */
    #[\Override]
    public function source(): ?array
    {
        $source = parent::source();
        $folder = $this->folder();

        if ($source !== null && $folder !== null && isset($source['data']['folder-id'])) {
            $source['data']['folder-id'] = $folder->id;
        }

        return $source;
    }

    /**
     * The trail from the volume's root to the folder being listed, so the
     * modal can lead back up. Empty unless a volume source is selected.
     *
     * @return list<array{label: string, folderId: int, url: ?string}>
     */
    public function folderBreadcrumbs(): array
    {
        $current = $this->folder() ?? $this->sourceRootFolder();
        $crumbs = [];

        while ($current !== null) {
            array_unshift($crumbs, [
                'label' => $current->parentId
                    ? (string) $current->name
                    : t($current->getVolume()->name, category: 'site'),
                'folderId' => (int) $current->id,
                'url' => ($uri = $current->getSourcePathInfo()['uri'] ?? null) !== null ? Url::cpUrl($uri) : null,
            ]);
            $current = $current->getParent();
        }

        return $crumbs;
    }

    /**
     * The folder named by the request's `folderId`, if it's a subfolder of the
     * selected volume source that the user can view.
     */
    private function folder(): ?VolumeFolder
    {
        if ($this->folderResolved) {
            return $this->resolvedFolder;
        }

        $this->folderResolved = true;

        $root = $this->sourceRootFolder();
        $folderId = $this->request->integer('folderId');

        if ($root === null || $folderId === 0 || $folderId === $root->id) {
            return null;
        }

        $folder = Folders::getFolderById($folderId);

        if (
            $folder === null ||
            $folder->volumeId !== $root->volumeId ||
            Gate::denies('viewContents', $folder)
        ) {
            return null;
        }

        return $this->resolvedFolder = $folder;
    }

    /** The root folder of the selected source, when it's a volume. */
    private function sourceRootFolder(): ?VolumeFolder
    {
        if (! is_a($this->elementType, Asset::class, true)) {
            return null;
        }

        [$sourceKey, $source] = parent::sourceState();
        $folderId = $source['criteria']['folderId'] ?? null;

        if (! str_starts_with((string) $sourceKey, 'volume:') || ! is_numeric($folderId)) {
            return null;
        }

        return Folders::getFolderById((int) $folderId);
    }

    private function folderUrl(Asset $folder): ?string
    {
        $uri = array_last($folder->sourcePath ?? [])['uri'] ?? null;

        return $uri !== null ? Url::cpUrl($uri) : null;
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
        return $this->resolvedSources ??= $this->indexState()->sources(
            $this->elementType,
            static::RENDER_CONTEXT,
            withDisabled: true,
            page: $this->page,
            restrictTo: $this->restrictToSources,
        )->all();
    }

    /** @return list<array<string, mixed>> */
    #[\Override]
    protected function sourceCandidates(int $siteId): array
    {
        return $this->sources();
    }
}
