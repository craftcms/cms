<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\ViewModels;

use CraftCms\Cms\Asset\Data\Volume;
use CraftCms\Cms\Asset\Data\VolumeFolder;
use CraftCms\Cms\Asset\Elements\Asset;
use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Element\Enums\ElementIndexViewMode;
use CraftCms\Cms\Http\Requests\ElementIndexRequest;
use CraftCms\Cms\Support\Arr;
use CraftCms\Cms\Support\Facades\Assets;
use CraftCms\Cms\Support\Facades\Folders;
use CraftCms\Cms\Support\Facades\Volumes;
use CraftCms\Cms\Support\Html;
use CraftCms\Cms\Support\Url;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Override;

use function CraftCms\Cms\t;

/**
 * The Inertia payload for the asset index screen (`assets/Index`).
 *
 * A `defaultSource` path like `volumeHandle/sub/folder` selects the volume's
 * source and resolves the subfolder chain into `breadcrumbs`.
 *
 * Those breadcrumbs are the trail *within* a volume, drawn in the index pane.
 * The header crumbs are the trail *to* it — `Assets › Uploads` — which is the
 * same trail every other index shows and the one the main nav agrees with.
 */
class AssetIndexViewModel extends ContentIndexViewModel
{
    /**
     * Client event fired by the current folder's "New subfolder" breadcrumb
     * action; the page listens for it and opens the name prompt.
     */
    public const NEW_SUBFOLDER_EVENT = 'assets:new-subfolder';

    public const RENAME_FOLDERS_EVENT = 'assets:rename-folders';

    public const MOVE_FOLDERS_EVENT = 'assets:move-folders';

    public const DELETE_FOLDERS_EVENT = 'assets:delete-folders';

    /** @var array{0: Volume|null, 1: string[]}|null */
    private ?array $resolvedDefaultSource = null;

    private bool $subfolderResolved = false;

    private ?VolumeFolder $resolvedSubfolder = null;

    public function __construct(
        ElementIndexRequest $request,
        ?string $page = null,
        private readonly ?string $defaultSource = null,
    ) {
        parent::__construct(Asset::class, $request, $page);
    }

    /**
     * The raw source path from the route (e.g. `volumeHandle/sub/folder`),
     * echoed back so client-side index reloads keep the current volume/folder
     * in the URL instead of bouncing to the root. The resolved source key
     * (`volume:{uid}`) is available on the base `source` payload.
     */
    public function defaultSource(): ?string
    {
        return $this->defaultSource;
    }

    public function includeSubfolders(): bool
    {
        return $this->search() !== null
            && $this->search() !== ''
            && $this->request->boolean('includeSubfolders');
    }

    public function canSearchSubfolders(): bool
    {
        return ($this->subfolder() ?? $this->rootFolder())?->getHasChildren() ?? false;
    }

    /** @return list<array<string, mixed>>|null */
    #[Override]
    public function actions(): ?array
    {
        $actions = array_map(
            fn (array $action): array => [...$action, 'appliesTo' => 'elements'],
            parent::actions() ?? [],
        );
        $folder = $this->rootFolder();

        if ($folder === null) {
            return $actions ?: null;
        }

        if (Gate::check('renameFolder', $folder)) {
            $actions[] = $this->folderBulkAction(
                'rename-folder',
                t('Rename folder'),
                self::RENAME_FOLDERS_EVENT,
                bulk: false,
            );
        }

        if (Gate::check('moveFolderFrom', $folder) && $this->moveTargetSourceKeys() !== []) {
            $currentFolder = $this->subfolder() ?? $folder;
            $actions[] = $this->folderBulkAction(
                'move-folder',
                t('Move folder'),
                self::MOVE_FOLDERS_EVENT,
                detail: ['disabledFolderIds' => [$currentFolder->id]],
            );
        }

        if (Gate::check('deleteFolder', $folder)) {
            $actions[] = $this->folderBulkAction(
                'delete-folder',
                t('Delete folder'),
                self::DELETE_FOLDERS_EVENT,
                destructive: true,
            );
        }

        return $actions ?: null;
    }

    /**
     * @param  array<string, mixed>  $detail
     * @return array<string, mixed>
     */
    private function folderBulkAction(
        string $key,
        string $label,
        string $event,
        bool $bulk = true,
        bool $destructive = false,
        array $detail = [],
    ): array {
        return array_filter([
            'key' => $key,
            'label' => $label,
            'appliesTo' => 'folders',
            'bulk' => $bulk ? null : false,
            'destructive' => $destructive ?: null,
            'variant' => $destructive ? 'danger' : null,
            'action' => [
                'type' => 'event',
                'name' => $event,
                ...($detail !== [] ? ['detail' => $detail] : []),
            ],
        ], fn (mixed $value): bool => $value !== null);
    }

    #[Override]
    protected function prepareElements(array $elements): void
    {
        if ($this->viewState()['mode'] !== ElementIndexViewMode::Table->value) {
            return;
        }

        $assets = array_values(array_filter($elements, fn (mixed $element): bool => $element instanceof Asset));

        Assets::preloadThumbs($assets, [30, 60]);
    }

    /**
     * The breadcrumb trail for the folder bar: volume root → current folder.
     *
     * Ancestors link to their own folder index and double as drag-and-drop move
     * targets (`data-folder-*` attrs); the current folder is plain text but
     * carries a "New subfolder" action menu. That action is a server-driven
     * `event` primitive — the page listens for {@see self::NEW_SUBFOLDER_EVENT}
     * and opens the name prompt (the modal can't be described server-side).
     *
     * @return array<int, array<string, mixed>>
     */
    public function breadcrumbs(): array
    {
        $current = $this->subfolder() ?? $this->rootFolder();

        if ($current === null) {
            return [];
        }

        $chain = [];

        while ($current !== null) {
            array_unshift($chain, $current);
            $current = $current->getParent();
        }

        $lastIndex = count($chain) - 1;
        $crumbs = [];

        foreach ($chain as $i => $folder) {
            $info = $folder->getSourcePathInfo();

            if ($info === null) {
                continue;
            }

            $isCurrent = $i === $lastIndex;

            $crumb = [
                'label' => $folder->parentId
                    ? $info['label']
                    : Html::encode(t($folder->getVolume()->name, category: 'site')),
                'icon' => $info['icon'] ?? null,
                'href' => $isCurrent ? null : Url::cpUrl($info['uri']),
            ];

            // Ancestors the user can move into become drop targets, matching the
            // folder rows and sidebar sources (see extraRowData()).
            if (! $isCurrent && Gate::check('moveIntoFolder', $folder)) {
                $crumb['attrs'] = [
                    'data-folder-drop-target' => '',
                    'data-folder-id' => (string) $folder->id,
                    'data-can-move-to' => '',
                ];
            }

            if ($isCurrent) {
                $crumb['attrs'] = [
                    'data-current-folder-id' => (string) $folder->id,
                ];
                $crumb['items'] = $this->currentFolderActions($folder, $info);
            }

            $crumbs[] = $crumb;
        }

        return $crumbs;
    }

    /**
     * @param  array<string, mixed>  $info
     * @return list<array<string, mixed>>
     */
    private function currentFolderActions(VolumeFolder $folder, array $info): array
    {
        $actions = [];

        if ($info['canCreate'] ?? false) {
            $actions[] = [
                'label' => t('New subfolder'),
                'icon' => 'folder-plus',
                'action' => [
                    'type' => 'event',
                    'name' => self::NEW_SUBFOLDER_EVENT,
                    'detail' => ['folderId' => $folder->id],
                ],
            ];
        }

        if ($info['canRename'] ?? false) {
            $actions[] = [
                'label' => t('Rename folder'),
                'action' => [
                    'type' => 'event',
                    'name' => self::RENAME_FOLDERS_EVENT,
                    'detail' => [
                        'folderIds' => [$folder->id],
                        'label' => $folder->name,
                        'navigate' => true,
                    ],
                ],
            ];
        }

        $moveTargetSourceKeys = $this->moveTargetSourceKeys();

        if (($info['canMove'] ?? false) && $moveTargetSourceKeys !== []) {
            $parent = $folder->getParent();
            $actions[] = [
                'label' => t('Move folder'),
                'action' => [
                    'type' => 'event',
                    'name' => self::MOVE_FOLDERS_EVENT,
                    'detail' => [
                        'folderIds' => [$folder->id],
                        'sources' => $moveTargetSourceKeys,
                        'defaultSource' => $this->sourceState()[0],
                        'defaultSourcePath' => $this->folderPath($parent),
                        'disabledFolderIds' => array_values(array_filter([
                            $folder->id,
                            $parent?->id,
                        ])),
                        'redirectUrl' => $parent !== null
                            ? Url::cpUrl($parent->getSourcePathInfo()['uri'])
                            : null,
                    ],
                ],
            ];
        }

        if ($info['canDelete'] ?? false) {
            $parent = $folder->getParent();
            $actions[] = [
                'label' => t('Delete folder'),
                'variant' => 'danger',
                'action' => [
                    'type' => 'event',
                    'name' => self::DELETE_FOLDERS_EVENT,
                    'detail' => [
                        'folderIds' => [$folder->id],
                        'label' => $folder->name,
                        'redirectUrl' => $parent !== null
                            ? Url::cpUrl($parent->getSourcePathInfo()['uri'])
                            : null,
                    ],
                ],
            ];
        }

        return $actions;
    }

    /**
     * Folder rows have no element id, so key them by folder id to stay unique
     * and stable for the client's table/selection.
     */
    /**
     * The assets index is a page of its own, so its crumbs start there.
     *
     * The folder chain in the pane covers everything below the volume; without
     * this there was nothing above it, and the header sat empty while every
     * other index had a trail.
     */
    #[Override]
    protected function indexUrl(): ?string
    {
        return Url::cpUrl('assets');
    }

    /**
     * Volumes have index URLs of their own, which is what the nav and the rest
     * of the CP link them by — so a crumb lands on the same URL rather than a
     * `?source=` query naming the same thing.
     *
     * @param  array<string, mixed>  $source
     */
    #[Override]
    protected function sourceUrl(array $source): ?string
    {
        $uri = Asset::sourceCpUri($source);

        return $uri === null ? parent::sourceUrl($source) : Url::cpUrl($uri);
    }

    #[Override]
    protected function rowId(ElementInterface $element): string|int|null
    {
        if ($element instanceof Asset && $element->isFolder) {
            return "folder:{$element->folderId}";
        }

        return $element->id;
    }

    /**
     * Marks folder rows so the client can (a) navigate into the folder on click
     * — the folder chip has no edit URL, so `folderUrl` (the last step of its
     * resolved source path) is provided — and (b) treat the row as a drag-and-
     * drop move target (`folderId` + `canMoveTo`).
     *
     * @return array<string, mixed>
     */
    #[Override]
    protected function extraRowData(ElementInterface $element): array
    {
        if (! $element instanceof Asset) {
            return [];
        }

        if (! $element->isFolder) {
            return ['previewable' => true];
        }

        $uri = array_last($element->sourcePath)['uri'] ?? null;
        $folder = Folders::getFolderById($element->folderId);

        return [
            'isFolder' => true,
            'folderId' => $element->folderId,
            'folderName' => $folder?->name,
            'folderUrl' => $uri !== null ? Url::cpUrl($uri) : null,
            'canMoveTo' => $folder !== null && Gate::check('moveIntoFolder', $folder),
        ];
    }

    /**
     * A `defaultSource` path selects the volume source (`volume:{uid}`), whose
     * criteria points at the volume root. When the path names a subfolder,
     * re-point the query's `folderId` at that subfolder so the listing (and its
     * merged child folders) is scoped to it rather than the volume root.
     *
     * @return array{0: ?string, 1: ?array<string, mixed>}
     */
    #[Override]
    protected function sourceState(): array
    {
        [$sourceKey, $source] = parent::sourceState();

        $subfolder = $this->subfolder();

        if ($subfolder !== null && $source !== null) {
            $source['criteria']['folderId'] = $subfolder->id;
        }

        if ($source !== null && $this->includeSubfolders()) {
            $source['criteria']['includeSubfolders'] = true;
        }

        return [$sourceKey, $source];
    }

    /** @return list<string> */
    private function moveTargetSourceKeys(): array
    {
        $keys = [];
        $collect = function (array $sources) use (&$collect, &$keys): void {
            foreach ($sources as $source) {
                if (isset($source['children'])) {
                    $collect($source['children']);

                    continue;
                }

                $data = $source['data'] ?? [];
                if (
                    ! empty($source['key'])
                    && ! empty($data['volume-handle'])
                    && $data['volume-handle'] !== 'temp'
                    && ! empty($data['can-move-to'])
                ) {
                    $keys[] = $source['key'];
                }
            }
        };

        $collect($this->sources());

        return $keys;
    }

    /** @return list<array<string, mixed>> */
    private function folderPath(?VolumeFolder $folder): array
    {
        $path = [];

        while ($folder !== null) {
            $info = $folder->getSourcePathInfo();
            if ($info !== null) {
                array_unshift($path, $info);
            }
            $folder = $folder->getParent();
        }

        return $path;
    }

    #[Override]
    protected function defaultSourceKey(): ?string
    {
        [$volume] = $this->resolveDefaultSource();

        return $volume === null ? null : "volume:{$volume->uid}";
    }

    /**
     * The volume named by the first `defaultSource` path segment, and the
     * remaining subfolder segments.
     *
     * @return array{0: Volume|null, 1: string[]}
     */
    private function resolveDefaultSource(): array
    {
        if ($this->resolvedDefaultSource !== null) {
            return $this->resolvedDefaultSource;
        }

        $segments = Arr::whereNotEmpty(explode('/', (string) $this->defaultSource));

        if ($segments === [] && preg_match('/^volume:(.+)$/', (string) $this->request->input('source'), $matches)) {
            return $this->resolvedDefaultSource = [Volumes::getVolumeByUid($matches[1]), []];
        }

        $volume = $segments === []
            ? null
            : Volumes::getVolumeByHandle(array_shift($segments));

        return $this->resolvedDefaultSource = [$volume, $segments];
    }

    /** The volume's root folder (the breadcrumb chain's top step). */
    private function rootFolder(): ?VolumeFolder
    {
        [$volume] = $this->resolveDefaultSource();

        if ($volume === null) {
            [, $source] = parent::sourceState();
            $handle = $source['data']['volume-handle'] ?? null;
            $volume = is_string($handle) ? Volumes::getVolumeByHandle($handle) : null;
        }

        return $volume === null
            ? null
            : Folders::getRootFolderByVolumeId($volume->id);
    }

    private function subfolder(): ?VolumeFolder
    {
        if ($this->subfolderResolved) {
            return $this->resolvedSubfolder;
        }

        $this->subfolderResolved = true;

        [$volume, $segments] = $this->resolveDefaultSource();

        if ($volume === null || $segments === []) {
            return $this->resolvedSubfolder = null;
        }

        return $this->resolvedSubfolder = Folders::findFolder([
            'volumeId' => $volume->id,
            'path' => sprintf('%s/', implode('/', $segments)),
        ]);
    }
}
