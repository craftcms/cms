<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Controllers\Settings;

use CraftCms\Cms\Asset\AssetTransformers;
use CraftCms\Cms\Asset\Data\Volume;
use CraftCms\Cms\Asset\Elements\Asset;
use CraftCms\Cms\Asset\Volumes;
use CraftCms\Cms\Config\GeneralConfig;
use CraftCms\Cms\Cp\Components\CopyAttribute;
use CraftCms\Cms\Cp\Data\ActionItem;
use CraftCms\Cms\Cp\Html\ContentHtml;
use CraftCms\Cms\Field\Enums\TranslationMethod;
use CraftCms\Cms\Field\Fields;
use CraftCms\Cms\Http\RespondsWithFlash;
use CraftCms\Cms\Http\Responses\CpScreenResponse;
use CraftCms\Cms\Http\ViewModels\VolumeEditViewModel;
use CraftCms\Cms\Support\File;
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
class VolumesController extends BaseAssetSettingsController
{
    use RespondsWithFlash;

    private bool $readOnly;

    public function __construct(
        GeneralConfig $generalConfig,
        private readonly AssetTransformers $assetTransformers,
    ) {
        $this->readOnly = ! $generalConfig->allowAdminChanges;
    }

    public function index(Volumes $volumes): CpScreenResponse
    {
        $table = Table::make('asset-volumes')
            ->columns([
                ['key' => 'name', 'label' => t('Name')],
                ['key' => 'handle', 'label' => t('Handle')],
            ])
            ->rows($volumes->getAllVolumes()->map(fn (Volume $volume): array => [
                'id' => $volume->id,
                'name' => [
                    'label' => $volume->name,
                    'url' => route('craft.cp.settings.assets.volumes.edit', ['volumeId' => $volume->id]),
                ],
                'handle' => [
                    'html' => CopyAttribute::make()->value($volume->handle)->toHtml(),
                ],
                ...($this->readOnly ? [] : [
                    '_deleteUrl' => route('craft.cp.settings.assets.volumes.destroy', ['volumeId' => $volume->id]),
                    '_deleteConfirmMessage' => t('Are you sure you want to delete "{name}"?', ['name' => $volume->name]),
                ]),
            ])->values()->all())
            ->emptyMessage(t('No volumes exist yet.'))
            ->showFooter(false)
            ->unless($this->readOnly, fn (Table $table) => $table
                ->createAction(t('New volume'), route('craft.cp.settings.assets.volumes.create'))
                ->createActionInPageHeader()
                ->deletable()
                ->reorderable(route('craft.actions.volumes.reorder')));

        return new CpScreenResponse()
            ->title(t('Volume Settings'))
            ->subnav($this->subnav())
            ->crumbs([
                new ActionItem()->label(t('Settings'))->href(route('craft.cp.settings.index')),
                new ActionItem()->label(t('Assets'))->href(route('craft.cp.settings.assets.volumes.index')),
                new ActionItem()->label(t('Volumes')),
            ])
            ->ui(Ui::make([$table]));
    }

    public function create(Volumes $volumes, UiResolver $uiResolver): CpScreenResponse
    {
        abort_if($this->readOnly, 403, 'Administrative changes are disallowed in this environment.');

        return $this->editScreen(new Volume, $volumes, $uiResolver);
    }

    public function edit(Volumes $volumes, UiResolver $uiResolver, int $volumeId): CpScreenResponse
    {
        $volume = $volumes->getVolumeById($volumeId);

        abort_if(is_null($volume), 404, 'Volume not found');

        return $this->editScreen($volume, $volumes, $uiResolver);
    }

    public function renderUi(Request $request, Volumes $volumes, UiResolver $uiResolver): JsonResponse
    {
        $data = $request->validate([
            'values' => ['required', 'array'],
            'values.volumeId' => ['nullable', 'integer'],
            'values.name' => ['nullable', 'string'],
            'values.handle' => ['nullable', 'string'],
            'values.fsHandle' => ['nullable', 'string'],
            'values.hasUrls' => ['required', 'boolean'],
            'values.subpath' => ['nullable', 'string'],
            'values.assetTransformer' => ['nullable', 'string'],
            'values.titleTranslationMethod' => ['required', Rule::enum(TranslationMethod::class)],
            'values.titleTranslationKeyFormat' => ['nullable', 'string'],
            'values.altTranslationMethod' => ['required', Rule::enum(TranslationMethod::class)],
            'values.altTranslationKeyFormat' => ['nullable', 'string'],
            'values.fieldLayout' => ['present', 'array'],
            'scope' => ['present', 'array', 'size:0'],
        ]);
        $volumeId = $data['values']['volumeId'] ?? null;
        $volume = $volumeId ? $volumes->getVolumeById((int) $volumeId) : new Volume;

        abort_if($volume === null, 404, 'Volume not found');

        return new JsonResponse([
            'ui' => new VolumeEditViewModel(
                $volume,
                $volumes,
                $uiResolver,
                $this->assetTransformers,
                values: $data['values'],
            )->ui(),
        ]);
    }

    public function store(Request $request, Volumes $volumes, Fields $fields): Response
    {
        $data = $request->validate([
            'assetTransformer' => ['nullable', 'string'],
        ]);
        $volumeId = $request->integer('volumeId') ?: null;
        $volume = $volumeId ? $volumes->getVolumeById($volumeId) : new Volume;

        abort_if($volume === null, 400, "Invalid volume ID: {$volumeId}");

        $subpath = $request->input('subpath');

        if (! empty($subpath)) {
            $subpath = File::normalizePath(ltrim(trim((string) $subpath), '/'));
        }

        $volume->name = $request->input('name');
        $volume->handle = $request->input('handle');
        $volume->fsHandle = $request->input('fsHandle');
        $volume->hasUrls = $request->boolean('hasUrls');
        $volume->subpath = $subpath;
        $volume->assetTransformer = ($data['assetTransformer'] ?? null) ?: null;
        $volume->titleTranslationMethod = $request->enum('titleTranslationMethod', TranslationMethod::class, TranslationMethod::Site);
        $volume->titleTranslationKeyFormat = $request->input('titleTranslationKeyFormat');
        $volume->altTranslationMethod = $request->enum('altTranslationMethod', TranslationMethod::class, TranslationMethod::None);
        $volume->altTranslationKeyFormat = $request->input('altTranslationKeyFormat');

        $fieldLayout = $fields->assembleLayoutFromPost();
        $fieldLayout->type = Asset::class;
        $volume->setFieldLayout($fieldLayout);

        if (! $volumes->saveVolume($volume)) {
            throw ValidationException::withMessages($volume->errors()->getMessages());
        }

        return $this->asModelSuccess($volume, t('Volume saved.'), 'volume');
    }

    public function reorder(Request $request, Volumes $volumes): Response
    {
        $data = $request->validate([
            'ids' => ['required', 'array', 'list'],
            'ids.*' => ['required', 'integer', 'min:1', 'distinct'],
        ]);

        $volumes->reorderVolumes($data['ids']);

        return $this->asSuccess(t('Order updated.'));
    }

    public function destroy(Request $request, Volumes $volumes, int $volumeId): Response
    {
        $volumes->deleteVolumeById($volumeId);

        return $this->asSuccess();
    }

    private function editScreen(Volume $volume, Volumes $volumes, UiResolver $uiResolver): CpScreenResponse
    {
        $isNewVolume = $volume->id === null;
        $title = $isNewVolume
            ? t('Create a new asset volume')
            : (trim((string) $volume->name) ?: t('Edit Volume'));

        return new CpScreenResponse()
            ->title($title)
            ->addCrumb(t('Settings'), 'settings')
            ->addCrumb(t('Assets'), 'settings/assets')
            ->addCrumb(t('Volumes'), 'settings/assets')
            ->addCrumb($title)
            ->inertiaPage('Ui', new VolumeEditViewModel(
                $volume,
                $volumes,
                $uiResolver,
                $this->assetTransformers,
                readOnly: $this->readOnly,
            ))
            ->unless(
                $this->readOnly,
                function (CpScreenResponse $response) use ($volume) {
                    $response
                        ->formAttributes([
                            'action' => Url::cpUrl('settings/assets/volumes'),
                        ])
                        ->redirectUrl('settings/assets')
                        ->saveShortcutRedirectUrl('settings/assets/volumes/{id}')
                        ->addAltAction(t('Save and continue editing'), [
                            'redirect' => 'settings/assets/volumes/{id}',
                            'shortcut' => true,
                            'retainScroll' => true,
                        ])
                        ->editUrl($volume->getCpEditUrl());
                },
                function (CpScreenResponse $response) {
                    $response->noticeHtml(app(ContentHtml::class)->readOnlyNoticeHtml());
                },
            );
    }
}
