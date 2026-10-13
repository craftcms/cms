<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Controllers\Settings;

use CraftCms\Cms\Asset\AssetTransformDrivers;
use CraftCms\Cms\Asset\AssetTransformers;
use CraftCms\Cms\Config\GeneralConfig;
use CraftCms\Cms\Cp\Components\CopyAttribute;
use CraftCms\Cms\Cp\Data\ActionItem;
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
use CraftCms\Cms\Support\Url;
use CraftCms\Cms\Ui\Nodes\Table;
use CraftCms\Cms\Ui\Ui;
use CraftCms\Cms\Ui\UiResolver;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;

use function CraftCms\Cms\t;

/**
 * @since 6.0.0
 */
class ImageTransformsController extends BaseAssetSettingsController
{
    use RespondsWithFlash;

    public function __construct(
        private readonly GeneralConfig $generalConfig,
        private readonly UiResolver $uiResolver,
        private readonly AssetTransformers $assetTransformers,
        private readonly AssetTransformDrivers $assetTransformDrivers,
    ) {}

    public function index(ImageTransforms $imageTransforms): CpScreenResponse
    {
        $readOnly = ! $this->generalConfig->allowAdminChanges;
        $table = Table::make('image-transforms')
            ->columns([
                ['key' => 'name', 'label' => t('Name')],
                ['key' => 'handle', 'label' => t('Handle')],
                ['key' => 'mode', 'label' => t('Mode')],
                ['key' => 'dimensions', 'label' => t('Dimensions')],
                ['key' => 'interlace', 'label' => t('Interlace')],
                ['key' => 'format', 'label' => t('Format')],
            ])
            ->rows($imageTransforms->getAllTransforms()
                ->sortBy(fn (ImageTransform $transform): string => t($transform->name, category: 'site'))
                ->map(fn (ImageTransform $transform): array => [
                    'id' => $transform->id,
                    'name' => [
                        'label' => $transform->name,
                        'url' => route('craft.cp.settings.assets.transforms.edit', ['transformHandle' => $transform->handle]),
                    ],
                    'handle' => [
                        'html' => CopyAttribute::make()->value($transform->handle)->toHtml(),
                    ],
                    'mode' => $transform->mode,
                    'dimensions' => ($transform->width ?? t('Auto')).' x '.($transform->height ?? t('Auto')),
                    'interlace' => t(ucfirst($transform->interlace ?: 'none')),
                    'format' => $transform->format ? ucfirst($transform->format) : t('Auto'),
                    ...($readOnly ? [] : [
                        '_deleteUrl' => route('craft.cp.settings.assets.transforms.destroy', ['transformId' => $transform->id]),
                        '_deleteConfirmMessage' => t('Are you sure you want to delete the “{name}” transform?', ['name' => $transform->name]),
                    ]),
                ]))
            ->emptyMessage(t('No image transforms exist yet.'))
            ->unless($readOnly, fn (Table $table) => $table
                ->createAction(t('New image transform'), route('craft.cp.settings.assets.transforms.create'))
                ->createActionInPageHeader()
                ->deletable());

        return new CpScreenResponse()
            ->title(t('Image Transforms'))
            ->subnav($this->subnav())
            ->crumbs([
                new ActionItem()->label(t('Settings'))->href(route('craft.cp.settings.index')),
                new ActionItem()->label(t('Assets'))->href(route('craft.cp.settings.assets.transforms.index')),
                new ActionItem()->label(t('Image Transforms')),
            ])
            ->ui(Ui::make([$table]));
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

        $transform->setParameters($request->array('parameters'));

        if (! $imageTransforms->saveTransform($transform)) {
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

    public function renderUi(Request $request, ImageTransforms $imageTransforms, Images $images): JsonResponse
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
            'values.parameters' => ['nullable', 'array'],
            'scope' => ['present', 'array', 'size:0'],
        ]);
        $values = $data['values'];
        $transform = empty($values['transformId'])
            ? new ImageTransform
            : $imageTransforms->getTransformById((int) $values['transformId']);

        abort_if($transform === null, 404, 'Transform not found');

        return new JsonResponse([
            'ui' => $this->viewModel($transform, $images, $values)->ui(),
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
            $this->uiResolver,
            $this->assetTransformers,
            $this->assetTransformDrivers,
            readOnly: ! $this->generalConfig->allowAdminChanges,
            values: $values,
        );
    }
}
