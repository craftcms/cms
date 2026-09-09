<?php

declare(strict_types=1);

namespace CraftCms\Cms\Form\Controls;

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
 */
class AssetSelect extends ElementSelect
{
    private bool $canUpload = false;

    private ?int $uploadFolderId = null;

    private ?string $fsType = null;

    public function canUpload(bool $canUpload = true): static
    {
        $this->canUpload = $canUpload;

        return $this;
    }

    public function uploadFolderId(?int $uploadFolderId): static
    {
        $this->uploadFolderId = $uploadFolderId;

        return $this;
    }

    public function fsType(?string $fsType): static
    {
        $this->fsType = $fsType;

        return $this;
    }

    /** @return array<string, mixed> */
    #[\Override]
    public function props(mixed $value = null): array
    {
        return [
            ...parent::props($value),
            'canUpload' => $this->canUpload,
            'uploadFolderId' => $this->uploadFolderId,
            'fsType' => $this->fsType,
        ];
    }
}
