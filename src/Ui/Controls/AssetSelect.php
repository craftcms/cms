<?php

declare(strict_types=1);

namespace CraftCms\Cms\Ui\Controls;

use CraftCms\Cms\Field\Assets;

/**
 * An {@see ElementSelect} for assets, which can also upload new ones.
 *
 * Uploading is the one thing an asset relation can do that no other element
 * relation can, so it lives here rather than on the generic control. The
 * component is inherited: the same Vue control renders both, and branches on
 * these props.
 *
 * Deciding *whether* uploads are allowed is the field's job — see
 * {@see Assets::selectControl()} — because it depends on the field's upload
 * settings and the volume permissions behind them.
 *
 * @since 6.0.0
 */
class AssetSelect extends ElementSelect
{
    private bool $canUpload = false;

    /** @var array<string, int> */
    private array $uploadParams = [];

    private bool $showFolders = true;

    public function showFolders(bool $showFolders = true): static
    {
        $this->showFolders = $showFolders;

        return $this;
    }

    public function canUpload(bool $canUpload = true): static
    {
        $this->canUpload = $canUpload;

        return $this;
    }

    /**
     * Sets the destination parameters uploads are sent with, such as a `folderId`, or a `fieldId` with an `elementId`
     * and `siteId`.
     *
     * @param  array<string, int>  $uploadParams
     */
    public function uploadParams(array $uploadParams): static
    {
        $this->uploadParams = $uploadParams;

        return $this;
    }

    /** @return array<string, mixed> */
    #[\Override]
    public function props(mixed $value = null): array
    {
        return [
            ...parent::props($value),
            'canUpload' => $this->canUpload,
            'uploadParams' => $this->uploadParams ?: null,
            'showFolders' => $this->showFolders,
        ];
    }
}
