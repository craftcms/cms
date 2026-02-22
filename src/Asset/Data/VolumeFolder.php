<?php

declare(strict_types=1);

namespace CraftCms\Cms\Asset\Data;

use Craft;
use CraftCms\Cms\Component\Component;
use CraftCms\Cms\Filesystem\Contracts\FsInterface;
use CraftCms\Cms\Support\Html;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Traits\Macroable;
use Override;
use RuntimeException;
use Stringable;

use function CraftCms\Cms\t;

final class VolumeFolder extends Component implements Stringable
{
    use Macroable;

    public ?int $id = null;

    public int|string|null $parentId = null;

    public ?int $volumeId = null;

    public ?string $name = null;

    public ?string $path = null;

    public ?string $uid = null;

    public Volume $volume {
        get => $this->getVolume();
    }

    public ?self $parent {
        get => $this->getParent();
    }

    /**
     * @var self[]
     */
    public array $children {
        get => $this->getChildren();
        set => $this->setChildren($value);
    }

    private ?FsInterface $_fs = null;

    /**
     * @var self[]|null
     */
    private ?array $_children = null;

    private bool $_hasChildren;

    #[Override]
    public function getRules(): array
    {
        return [
            'id' => ['nullable', 'integer'],
            'parentId' => ['nullable', 'integer'],
            'volumeId' => ['nullable', 'integer'],
        ];
    }

    #[Override]
    public function attributes(): array
    {
        return [
            'id',
            'parentId',
            'volumeId',
            'name',
            'path',
            'uid',
        ];
    }

    #[Override]
    public function getAttributes(): array
    {
        return [
            'id' => $this->id,
            'parentId' => $this->parentId,
            'volumeId' => $this->volumeId,
            'name' => $this->name,
            'path' => $this->path,
            'uid' => $this->uid,
        ];
    }

    public function __toString(): string
    {
        $name = (string) $this->name;
        if ($name) {
            return $name;
        }

        return self::class;
    }

    /**
     * Returns the volume this folder belongs to.
     *
     * @throws RuntimeException if [[volumeId]] is invalid
     */
    public function getVolume(): Volume
    {
        if ($this->volumeId === null) {
            return Craft::$app->getVolumes()->getTemporaryVolume();
        }

        if (($volume = Craft::$app->getVolumes()->getVolumeById($this->volumeId)) === null) {
            throw new RuntimeException('Invalid volume ID: '.$this->volumeId);
        }

        return $volume;
    }

    public function getFs(): FsInterface
    {
        return $this->_fs ?? $this->getVolume()->getFs();
    }

    public function setFs(FsInterface $fs): void
    {
        $this->_fs = $fs;
    }

    public function getSourcePathInfo(): ?array
    {
        if (! $this->volumeId) {
            return null;
        }

        $volume = $this->getVolume();
        $canView = Gate::check("viewAssets:$volume->uid");
        $canCreate = Gate::check("createFolders:$volume->uid");
        $canDelete = Gate::check("deletePeerAssets:$volume->uid");
        $canMove = $canDelete && Gate::check("savePeerAssets:$volume->uid");
        $path = $this->path ? sprintf('/%s', trim($this->path, '/')) : '';

        $info = [
            'uri' => sprintf('assets/%s%s', $volume->handle, $path),
            'folderId' => (int) $this->id,
            'hasChildren' => $this->getHasChildren(),
            'canView' => $canView,
            'canCreate' => $canCreate,
            'canMoveSubItems' => $canMove,
        ];

        if (! $this->parentId) {
            $info += [
                'key' => "volume:$volume->uid",
                'icon' => 'home',
                'label' => t('{volume} root', [
                    'volume' => Html::encode(t($volume->name, category: 'site')),
                ]),
                'handle' => $volume->handle,
            ];
        } else {
            $canRename = $canCreate && Gate::check("deleteAssets:$volume->uid");

            $info += [
                'key' => "folder:$this->uid",
                'label' => Html::encode((string) $this->name),
                'criteria' => [
                    'folderId' => $this->id,
                ],
                'canRename' => $canRename,
                'canMove' => $canMove,
                'canDelete' => $canDelete,
            ];
        }

        return $info;
    }

    public function getHasChildren(): bool
    {
        if ($this->_children !== null) {
            return ! empty($this->_children);
        }

        if (! isset($this->_hasChildren)) {
            $this->_hasChildren = Craft::$app->getAssets()->foldersExist(['parentId' => $this->id]);
        }

        return $this->_hasChildren;
    }

    public function setHasChildren(bool $value): void
    {
        $this->_hasChildren = $value;
    }

    /** @param self[] $children */
    public function setChildren(array $children): void
    {
        $this->_children = $children;
    }

    /** @return self[] */
    public function getChildren(): array
    {
        if ($this->_children === null) {
            $this->_children = Craft::$app->getAssets()->findFolders(['parentId' => $this->id]);
        }

        return $this->_children;
    }

    public function getParent(): ?self
    {
        if (! $this->parentId) {
            return null;
        }

        return Craft::$app->getAssets()->getFolderById((int) $this->parentId);
    }

    public function addChild(self $folder): void
    {
        $this->_children ??= [];
        $this->_children[] = $folder;
    }
}
