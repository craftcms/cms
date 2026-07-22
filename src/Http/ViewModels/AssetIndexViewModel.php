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

/**
 * The Inertia payload for the asset index screen (`assets/Index`).
 *
 * A `defaultSource` path like `volumeHandle/sub/folder` selects the volume's
 * source and resolves the subfolder chain into `defaultSourcePath`.
 */
class AssetIndexViewModel extends ContentIndexViewModel
{
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

    /** @return array<int, array|null>|null */
    public function defaultSourcePath(): ?array
    {
        $subfolder = $this->subfolder();

        if ($subfolder === null) {
            return null;
        }

        $folderChain = [];

        while ($subfolder) {
            array_unshift($folderChain, $subfolder);
            $subfolder = $subfolder->getParent();
        }

        $sourcePath = [];

        foreach ($folderChain as $i => $folder) {
            if ($i < count($folderChain) - 1) {
                $folder->setHasChildren(true);
            }

            $info = $folder->getSourcePathInfo();

            // Let the breadcrumb double as a drag-and-drop move target, matching
            // the folder rows/sources (see extraRowData()).
            if ($info !== null) {
                $info['canMoveTo'] = Gate::check('moveIntoFolder', $folder);
            }

            $sourcePath[] = $info;
        }

        return $sourcePath;
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
     * @return array{0: ?string, 1: ?array}
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
