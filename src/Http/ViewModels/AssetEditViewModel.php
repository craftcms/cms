<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\ViewModels;

use CraftCms\Cms\Asset\Elements\Asset;
use CraftCms\Cms\Cms;
use CraftCms\Cms\Http\Requests\ElementRequest;
use CraftCms\Cms\Support\Facades\HtmlStack;
use CraftCms\Cms\Support\Facades\I18N;
use CraftCms\Cms\Support\Facades\Images;
use CraftCms\Cms\Support\Url;
use CraftCms\Cms\View\HtmlFragment;
use Override;

use function CraftCms\Cms\currentUserElement;

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

    #[Override]
    public function activityTimelineUrl(): ?string
    {
        return $this->asset->id
            ? Url::actionUrl('elements/activity')
            : null;
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
     * Everything the image editor dialog needs, or null when this asset can't
     * be image-edited — which is also what tells the edit screen whether to
     * render the dialog at all.
     *
     * @return array<string, mixed>|null
     */
    public function imageEditor(): ?array
    {
        if (! $this->asset->id || ! $this->asset->getSupportsImageEditor()) {
            return null;
        }

        $user = currentUserElement();

        if (! $user?->can('editImage', $this->asset)) {
            return null;
        }

        return [
            'assetId' => $this->asset->id,
            'filename' => $this->asset->getFilename(),
            'focalPoint' => $this->asset->getHasFocalPoint()
                ? $this->asset->getFocalPoint()
                : null,
            'imageEditorRatios' => Cms::config()->imageEditorRatios,
            // Only Imagick can rotate by a fraction of a degree; GD rounds.
            'allowDegreeFractions' => Images::getIsImagick(),
            // Drives which cropper handle gets the "left" label and which the
            // "right" one, as `_special/image_editor.twig` did.
            'orientation' => I18N::getLocale()->getOrientation(),
        ];
    }

    /**
     * Whether to open the image editor as the screen loads, from `?editing`.
     *
     * Lets a link land straight in the editor. Guarded on `imageEditor()` so
     * the parameter can't ask for an editor this asset doesn't get.
     */
    public function editingImage(): bool
    {
        return $this->request->boolean('editing') && $this->imageEditor() !== null;
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
