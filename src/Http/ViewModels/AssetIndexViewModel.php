<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\ViewModels;

use CraftCms\Cms\Asset\Data\Volume;
use CraftCms\Cms\Asset\Data\VolumeFolder;
use CraftCms\Cms\Asset\Elements\Asset;
use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Http\Requests\ElementIndexRequest;
use CraftCms\Cms\Support\Arr;
use CraftCms\Cms\Support\Facades\Folders;
use CraftCms\Cms\Support\Facades\Volumes;
use CraftCms\Cms\Support\Url;
use Illuminate\Support\Facades\Gate;
use Override;

use function CraftCms\Cms\t;

/**
 * The Inertia payload for the asset index screen (`assets/Index`).
 *
 * A `defaultSource` path like `volumeHandle/sub/folder` selects the volume's
 * source and resolves the subfolder chain into `breadcrumbs`.
 */
class AssetIndexViewModel extends ContentIndexViewModel
{
    /**
     * Client event fired by the current folder's "New subfolder" breadcrumb
     * action; the page listens for it and opens the name prompt.
     */
    public const NEW_SUBFOLDER_EVENT = 'assets:new-subfolder';

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
                'label' => $info['label'],
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

            // The current folder gets a "New subfolder" action when the user can
            // create in it.
            if ($isCurrent && ($info['canCreate'] ?? false)) {
                $crumb['actions'] = [[
                    'label' => t('New subfolder'),
                    'icon' => 'folder-plus',
                    'action' => [
                        'type' => 'event',
                        'name' => self::NEW_SUBFOLDER_EVENT,
                        'detail' => ['folderId' => $folder->id],
                    ],
                ]];
            }

            $crumbs[] = $crumb;
        }

        return $crumbs;
    }

    /**
     * Folder rows have no element id, so key them by folder id to stay unique
     * and stable for the client's table/selection.
     */
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
        if (! $element instanceof Asset || ! $element->isFolder) {
            return [];
        }

        $uri = array_last($element->sourcePath)['uri'] ?? null;
        $folder = Folders::getFolderById($element->folderId);

        return [
            'isFolder' => true,
            'folderId' => $element->folderId,
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

        return [$sourceKey, $source];
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

        $volume = $segments === []
            ? null
            : Volumes::getVolumeByHandle(array_shift($segments));

        return $this->resolvedDefaultSource = [$volume, $segments];
    }

    /** The volume's root folder (the breadcrumb chain's top step). */
    private function rootFolder(): ?VolumeFolder
    {
        [$volume] = $this->resolveDefaultSource();

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
