<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\ViewModels;

use CraftCms\Cms\Asset\Elements\Asset;
use CraftCms\Cms\Http\Requests\ElementRequest;
use CraftCms\Cms\Support\Facades\HtmlStack;
use CraftCms\Cms\Support\Url;
use CraftCms\Cms\View\HtmlFragment;
use Override;

/**
 * The Inertia payload for the asset edit screen (`assets/Edit`).
 *
 * Assets have no drafts, revisions, or statuses, so most of the shared
 * editor's machinery stays dormant here; what's left is the field layout, the
 * filename meta field, and the file preview.
 */
class AssetEditViewModel extends ElementEditViewModel
{
    public function __construct(
        private readonly Asset $asset,
        ElementRequest $request,
        bool $canSave = true,
    ) {
        parent::__construct($asset, $request, $canSave);
    }

    /**
     * Assets have no store action of their own — the generic element save
     * reads the identity params every element edit screen submits.
     */
    #[Override]
    protected function elementSaveUrl(): string
    {
        return Url::actionUrl('elements/save');
    }

    public function volumeId(): ?int
    {
        return $this->asset->getVolumeId();
    }

    public function folderId(): ?int
    {
        return $this->asset->folderId;
    }

    /**
     * The file preview — a thumbnail, or a player for audio and video — shown
     * above the meta fields.
     *
     * Still server-rendered HTML: previewing and image editing both open
     * legacy modals, and the markup carries the JS that wires them up, so it
     * arrives as a fragment rather than a payload the Vue side rebuilds.
     */
    public function previewFragment(): ?HtmlFragment
    {
        if (! $this->asset->id) {
            return null;
        }

        $fragment = HtmlStack::capture(fn (): string => $this->asset->getPreviewHtml());

        return $fragment->isEmpty() ? null : $fragment;
    }
}
