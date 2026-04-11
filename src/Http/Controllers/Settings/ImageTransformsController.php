<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Controllers\Settings;

use Craft;
use craft\web\assets\edittransform\EditTransformAsset;
use CraftCms\Cms\Config\GeneralConfig;
use CraftCms\Cms\Http\RespondsWithFlash;
use CraftCms\Cms\Image\Data\ImageTransform;
use CraftCms\Cms\Image\ImageTransforms;
use CraftCms\Cms\Support\Url;
use CraftCms\Cms\Validation\Rules\ColorRule;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

use function CraftCms\Cms\t;

class ImageTransformsController
{
    use RespondsWithFlash;

    private bool $readOnly;

    public function __construct(GeneralConfig $generalConfig)
    {
        $this->readOnly = ! $generalConfig->allowAdminChanges;
    }

    public function index(ImageTransforms $imageTransforms)
    {
        $transforms = $imageTransforms
            ->getAllTransforms()
            ->sort(fn (ImageTransform $a, ImageTransform $b): int => t($a->name, category: 'site') <=> t($b->name, category: 'site'))
            ->values();

        return Inertia::render('SettingsImageTransformsIndexPage', [
            'crumbs' => fn () => [
                ['label' => t('Settings'), 'url' => Url::cpUrl('settings')],
                ['label' => t('Transforms')],
            ],
            'title' => t('Image Transforms'),
            'transforms' => $transforms,
            'modes' => ImageTransform::modes(),
        ]);
    }

    public function create(): View
    {
        abort_if($this->readOnly, 403, 'Administrative changes are disallowed in this environment.');

        return $this->editView();
    }

    public function edit(ImageTransforms $imageTransforms, string $transformHandle): View
    {
        $transform = $imageTransforms->getTransformByHandle($transformHandle);
        abort_if(is_null($transform), 404, 'Transform not found');

        return $this->editView($transformHandle, $transform);
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

        return $this->asModelSuccess($transform, t('Transform saved.'), 'transform');
    }

    public function destroy(Request $request, ImageTransforms $imageTransforms, int $transformId): Response
    {
        $imageTransforms->deleteTransformById($transformId);

        return $this->asSuccess();
    }

    private function editView(?string $transformHandle = null, ?ImageTransform $transform = null): View
    {
        $transform ??= new ImageTransform;
        $bundle = Craft::$app->getView()->registerAssetBundle(EditTransformAsset::class);

        $title = $transform->id
            ? (trim((string) $transform->name) ?: t('Edit Image Transform'))
            : t('Create a new image transform');

        [$qualityPickerOptions, $qualityPickerValue] = $this->qualityPickerData($transform);

        return view('settings/assets/transforms/_settings', [
            'handle' => $transformHandle,
            'transform' => $transform,
            'title' => $title,
            'qualityPickerOptions' => $qualityPickerOptions,
            'qualityPickerValue' => $qualityPickerValue,
            'readOnly' => $this->readOnly,
            'baseIconsUrl' => $bundle->baseUrl,
        ]);
    }

    /**
     * @return array{0: array<int, array{label: string, value: int}>, 1: int}
     */
    private function qualityPickerData(ImageTransform $transform): array
    {
        $qualityPickerOptions = [
            ['label' => t('Low'), 'value' => 10],
            ['label' => t('Medium'), 'value' => 30],
            ['label' => t('High'), 'value' => 60],
            ['label' => t('Very High'), 'value' => 80],
            ['label' => t('Maximum'), 'value' => 100],
        ];

        if ($transform->quality) {
            // Default to Low, even if quality is < 10.
            $qualityPickerValue = 10;
            foreach ($qualityPickerOptions as $option) {
                if ($transform->quality >= $option['value']) {
                    $qualityPickerValue = $option['value'];
                } else {
                    break;
                }
            }
        } else {
            // Auto
            $qualityPickerValue = 0;
        }

        return [$qualityPickerOptions, $qualityPickerValue];
    }
}
