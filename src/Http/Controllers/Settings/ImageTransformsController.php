<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Controllers\Settings;

use CraftCms\Cms\Cp\Data\NavItem;
use CraftCms\Cms\Http\RespondsWithFlash;
use CraftCms\Cms\Http\Responses\CpScreenResponse;
use CraftCms\Cms\Image\Data\ImageTransform;
use CraftCms\Cms\Image\Enums\ImageTransformFormat;
use CraftCms\Cms\Image\Enums\ImageTransformInterlace;
use CraftCms\Cms\Image\Enums\ImageTransformMode;
use CraftCms\Cms\Image\Enums\ImageTransformPosition;
use CraftCms\Cms\Image\Enums\ImageTransformQuality;
use CraftCms\Cms\Image\Images;
use CraftCms\Cms\Image\ImageTransforms;
use CraftCms\Cms\Support\Url;
use CraftCms\Cms\Validation\Rules\ColorRule;
use Illuminate\Http\Request;
use Imagine\Image\Format;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

use function CraftCms\Cms\t;

class ImageTransformsController
{
    use RespondsWithFlash;

    public function index(ImageTransforms $imageTransforms)
    {
        return Inertia::render('settings/assets/transforms/Index', [
            'crumbs' => fn () => [
                ['label' => t('Settings'), 'url' => Url::cpUrl('settings')],
                ['label' => t('Assets'), 'url' => Url::cpUrl('settings/assets/transforms')],
                ['label' => t('Image Transforms')],
            ],
            'subnav' => [
                new NavItem()->label(t('Volumes'))->url(Url::cpUrl('settings/assets')),
                new NavItem()->label(t('Image Transforms'))->url(Url::cpUrl('settings/assets/transforms'))->selected(true),
            ],
            'title' => t('Image Transforms'),
            'transforms' => $imageTransforms
                ->getAllTransforms()
                ->sortBy(fn (ImageTransform $transform): string => t($transform->name, category: 'site')),
            'modes' => ImageTransformMode::asOptions(),
        ]);
    }

    public function create(Images $images): CpScreenResponse
    {
        return $this->editScreen(new ImageTransform, $images);
    }

    public function edit(ImageTransforms $imageTransforms, Images $images, string $transformHandle): CpScreenResponse
    {
        $transform = $imageTransforms->getTransformByHandle($transformHandle);

        abort_if(is_null($transform), 404, 'Transform not found');

        return $this->editScreen($transform, $images);
    }

    public function store(Request $request, ImageTransforms $imageTransforms): Response
    {
        $transform = new ImageTransform;
        $transform->id = $request->integer('transformId') ?: null;
        $transform->name = $request->input('name');
        $transform->handle = $request->input('handle');
        $transform->width = (int) $request->input('width') ?: null;
        $transform->height = (int) $request->input('height') ?: null;
        $transform->mode = (string) $request->input('mode', $transform->mode);
        $transform->position = (string) $request->input('position', $transform->position);
        $transform->quality = ($quality = $request->input('quality')) !== '' && ! is_null($quality)
            ? (int) $quality
            : null;
        $transform->interlace = (string) $request->input('interlace', $transform->interlace);
        $transform->format = $request->input('format');
        $transform->fill = ($fill = $request->input('fill')) !== '' && ! is_null($fill)
            ? (string) $fill
            : null;
        $transform->upscale = $request->boolean('upscale', $transform->upscale);

        if ($transform->format === '') {
            $transform->format = null;
        }

        if ($transform->mode === 'letterbox') {
            $transform->fill = $transform->fill ? ColorRule::normalizeColor($transform->fill) : 'transparent';
        }

        $isValid = $transform->validate();

        if (empty($transform->width) && empty($transform->height)) {
            $transform->errors()->add('width', t('You must set at least one of the dimensions.'));
            $isValid = false;
        }

        if (! $isValid || ! $imageTransforms->saveTransform($transform, runValidation: false)) {
            return $this->asModelFailure($transform, modelName: 'transform');
        }

        return $this->asModelSuccess(
            $transform,
            t('Transform saved.'),
            'transform',
            redirect: $this->getPostedRedirectUrl($transform)
                ?? Url::cpUrl("settings/assets/transforms/$transform->handle"),
        );
    }

    public function destroy(ImageTransforms $imageTransforms, int $transformId): Response
    {
        $imageTransforms->deleteTransformById($transformId);

        return $this->asSuccess();
    }

    private function editScreen(ImageTransform $transform, Images $images): CpScreenResponse
    {
        $title = $transform->id
            ? (trim((string) $transform->name) ?: t('Edit Image Transform'))
            : t('Create a new image transform');

        return new CpScreenResponse()
            ->title($title)
            ->addCrumb(t('Settings'), 'settings')
            ->addCrumb(t('Assets'), 'settings/assets/transforms')
            ->addCrumb(t('Image Transforms'), 'settings/assets/transforms')
            ->addCrumb($title)
            ->redirectUrl('settings/assets/transforms')
            ->inertiaPage('settings/assets/transforms/Edit', [
                'transform' => $transform,
                'modeOptions' => ImageTransformMode::asOptions(),
                'positionOptions' => ImageTransformPosition::asOptions(),
                'interlaceOptions' => ImageTransformInterlace::asOptions(),
                'formatOptions' => $this->formatOptions($images, $transform),
                'qualityOptions' => ImageTransformQuality::asOptions(),
            ]);
    }

    /**
     * @return array<int, array{label: string, value: string}>
     */
    private function formatOptions(Images $images, ImageTransform $transform): array
    {
        return collect(ImageTransformFormat::asOptions())
            ->prepend(['label' => t('Auto'), 'value' => ''])
            ->reject(fn (array $option) => $option['value'] === ImageTransformFormat::WEBP->value && $transform->format !== Format::ID_WEBP && ! $images->getSupportsWebP())
            ->reject(fn (array $option) => $option['value'] === ImageTransformFormat::AVIF->value && $transform->format !== Format::ID_AVIF && ! $images->getSupportsAvif())
            ->all();
    }
}
