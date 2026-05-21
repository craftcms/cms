<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Controllers\Settings;

use CraftCms\Cms\Http\RespondsWithFlash;
use CraftCms\Cms\Http\Responses\CpScreenResponse;
use CraftCms\Cms\Image\Data\ImageTransform;
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
        $transforms = $imageTransforms
            ->getAllTransforms()
            ->sort(fn (ImageTransform $a, ImageTransform $b): int => t($a->name, category: 'site') <=> t($b->name, category: 'site'))
            ->values();

        return Inertia::render('settings/assets/transforms/ImageTransformsIndexPage', [
            'crumbs' => fn () => [
                ['label' => t('Settings'), 'url' => Url::cpUrl('settings')],
                ['label' => t('Assets'), 'url' => Url::cpUrl('settings/assets/transforms')],
                ['label' => t('Image Transforms')],
            ],
            'title' => t('Image Transforms'),
            'transforms' => $transforms,
            'modes' => ImageTransform::modes(),
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

    public function save(Request $request, ImageTransforms $imageTransforms): Response
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
            ->inertiaPage('settings/assets/transforms/EditImageTransformPage', [
                'transform' => $this->transformData($transform),
                'modeOptions' => $this->modeOptions(),
                'positionOptions' => $this->positionOptions(),
                'interlaceOptions' => $this->interlaceOptions(),
                'formatOptions' => $this->formatOptions($images, $transform),
                'qualityOptions' => $this->qualityOptions(),
            ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function transformData(ImageTransform $transform): array
    {
        return [
            'id' => $transform->id,
            'name' => $transform->name,
            'handle' => $transform->handle,
            'width' => $transform->width,
            'height' => $transform->height,
            'mode' => $transform->mode,
            'position' => $transform->position,
            'quality' => $transform->quality,
            'interlace' => $transform->interlace,
            'format' => $transform->format,
            'fill' => $transform->fill,
            'upscale' => $transform->upscale,
        ];
    }

    /**
     * @return array<int, array{label: string, value: string}>
     */
    private function modeOptions(): array
    {
        $modes = ImageTransform::modes();

        return collect(['crop', 'fit', 'letterbox', 'stretch'])
            ->map(fn (string $value): array => [
                'label' => $modes[$value],
                'value' => $value,
            ])
            ->values()
            ->all();
    }

    /**
     * @return array<int, array{label: string, value: string}>
     */
    private function positionOptions(): array
    {
        return [
            ['label' => t('Top-Left'), 'value' => 'top-left'],
            ['label' => t('Top-Center'), 'value' => 'top-center'],
            ['label' => t('Top-Right'), 'value' => 'top-right'],
            ['label' => t('Center-Left'), 'value' => 'center-left'],
            ['label' => t('Center-Center'), 'value' => 'center-center'],
            ['label' => t('Center-Right'), 'value' => 'center-right'],
            ['label' => t('Bottom-Left'), 'value' => 'bottom-left'],
            ['label' => t('Bottom-Center'), 'value' => 'bottom-center'],
            ['label' => t('Bottom-Right'), 'value' => 'bottom-right'],
        ];
    }

    /**
     * @return array<int, array{label: string, value: string}>
     */
    private function interlaceOptions(): array
    {
        return [
            ['label' => t('None'), 'value' => 'none'],
            ['label' => t('Line'), 'value' => 'line'],
            ['label' => t('Plane'), 'value' => 'plane'],
            ['label' => t('Partition'), 'value' => 'partition'],
        ];
    }

    /**
     * @return array<int, array{label: string, value: string}>
     */
    private function formatOptions(Images $images, ImageTransform $transform): array
    {
        $options = [
            ['label' => t('Auto'), 'value' => ''],
            ['label' => 'jpg', 'value' => 'jpg'],
            ['label' => 'png', 'value' => 'png'],
            ['label' => 'gif', 'value' => 'gif'],
        ];

        if ($transform->format === Format::ID_WEBP || $images->getSupportsWebP()) {
            $options[] = ['label' => Format::ID_WEBP, 'value' => Format::ID_WEBP];
        }

        if ($transform->format === Format::ID_AVIF || $images->getSupportsAvif()) {
            $options[] = ['label' => Format::ID_AVIF, 'value' => Format::ID_AVIF];
        }

        return $options;
    }

    /**
     * @return array<int, array{label: string, value: int}>
     */
    private function qualityOptions(): array
    {
        return [
            ['label' => t('Low'), 'value' => 10],
            ['label' => t('Medium'), 'value' => 30],
            ['label' => t('High'), 'value' => 60],
            ['label' => t('Very High'), 'value' => 80],
            ['label' => t('Maximum'), 'value' => 100],
        ];
    }
}
