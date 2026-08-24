<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Controllers\Settings;

use CraftCms\Cms\Asset\AssetProcessorDrivers;
use CraftCms\Cms\Asset\AssetProcessors;
use CraftCms\Cms\Asset\Exceptions\InvalidAssetTransformException;
use CraftCms\Cms\Config\GeneralConfig;
use CraftCms\Cms\Form\FormResolver;
use CraftCms\Cms\Http\RespondsWithFlash;
use CraftCms\Cms\Http\Responses\CpScreenResponse;
use CraftCms\Cms\Http\ViewModels\ImageTransformEditViewModel;
use CraftCms\Cms\Image\Data\ImageTransform;
use CraftCms\Cms\Image\Enums\ImageTransformFormat;
use CraftCms\Cms\Image\Enums\ImageTransformInterlace;
use CraftCms\Cms\Image\Enums\ImageTransformMode;
use CraftCms\Cms\Image\Enums\ImageTransformPosition;
use CraftCms\Cms\Image\Images;
use CraftCms\Cms\Image\ImageTransforms;
use CraftCms\Cms\Support\Arr;
use CraftCms\Cms\Support\Url;
use CraftCms\Cms\Validation\Rules\ColorRule;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

use function CraftCms\Cms\t;

class ImageTransformsController extends BaseAssetSettingsController
{
    use RespondsWithFlash;

    public function __construct(
        private readonly GeneralConfig $generalConfig,
        private readonly FormResolver $formResolver,
        private readonly AssetProcessors $assetProcessors,
        private readonly AssetProcessorDrivers $assetProcessorDrivers,
    ) {}

    public function index(ImageTransforms $imageTransforms): \Inertia\Response
    {
        return Inertia::render('settings/assets/transforms/Index', [
            'crumbs' => fn () => [
                ['label' => t('Settings'), 'href' => Url::cpUrl('settings')],
                ['label' => t('Assets'), 'href' => Url::cpUrl('settings/assets/transforms')],
                ['label' => t('Image Transforms')],
            ],
            'subnav' => $this->subnav(),
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
        $transformId = $request->integer('transformId') ?: null;
        $transform = $transformId ? $imageTransforms->getTransformById($transformId) : new ImageTransform;

        abort_if($transform === null, 404, 'Transform not found');

        $transform->id = $transformId;
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

        $operationBuckets = [];

        foreach ($this->assetProcessors->getAllAssetProcessors() as $assetProcessor) {
            if (! $this->assetProcessorDrivers->has($assetProcessor->driver)) {
                $existing = $transform->getOperationsForTransformer($assetProcessor->uid);

                if ($existing !== []) {
                    $operationBuckets[$assetProcessor->uid] = $existing;
                }

                continue;
            }

            try {
                $operations = $this->assetProcessors->validateOperations(
                    $assetProcessor,
                    $request->array("operations.{$assetProcessor->uid}"),
                );
            } catch (InvalidAssetTransformException $exception) {
                throw ValidationException::withMessages([
                    "operations.{$assetProcessor->uid}" => $exception->getMessage(),
                ]);
            }

            $operations = Arr::except($operations, ImageTransform::CORE_OPERATIONS);

            if ($operations !== []) {
                $operationBuckets[$assetProcessor->uid] = $operations;
            }
        }

        $transform->setOperations($operationBuckets);

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
            throw ValidationException::withMessages($transform->errors()->getMessages());
        }

        return $this->asModelSuccess(
            $transform,
            t('Transform saved.'),
            'transform',
            redirect: $this->getPostedRedirectUrl($transform)
                ?? Url::cpUrl("settings/assets/transforms/$transform->handle"),
        );
    }

    public function renderForm(Request $request, ImageTransforms $imageTransforms, Images $images): JsonResponse
    {
        $data = $request->validate([
            'values' => ['required', 'array'],
            'values.transformId' => ['nullable', 'integer'],
            'values.name' => ['nullable', 'string'],
            'values.handle' => ['nullable', 'string'],
            'values.width' => ['nullable', 'integer', 'min:1'],
            'values.height' => ['nullable', 'integer', 'min:1'],
            'values.mode' => ['required', Rule::enum(ImageTransformMode::class)],
            'values.position' => ['required', Rule::enum(ImageTransformPosition::class)],
            'values.quality' => ['nullable', 'integer', 'min:1', 'max:100'],
            'values.interlace' => ['required', Rule::enum(ImageTransformInterlace::class)],
            'values.format' => ['nullable', Rule::enum(ImageTransformFormat::class)],
            'values.fill' => ['nullable', 'string'],
            'values.upscale' => ['required', 'boolean'],
            'values.operations' => ['nullable', 'array'],
            'scope' => ['present', 'array', 'size:0'],
        ]);
        $values = $data['values'];
        $transform = empty($values['transformId'])
            ? new ImageTransform
            : $imageTransforms->getTransformById((int) $values['transformId']);

        abort_if($transform === null, 404, 'Transform not found');

        return new JsonResponse([
            'form' => $this->viewModel($transform, $images, $values)->form(),
        ]);
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
            ->inertiaPage('settings/assets/transforms/Edit', $this->viewModel($transform, $images));
    }

    /** @param array<string, mixed>|null $values */
    private function viewModel(ImageTransform $transform, Images $images, ?array $values = null): ImageTransformEditViewModel
    {
        return new ImageTransformEditViewModel(
            $transform,
            $images,
            $this->formResolver,
            $this->assetProcessors,
            $this->assetProcessorDrivers,
            readOnly: ! $this->generalConfig->allowAdminChanges,
            values: $values,
        );
    }
}
