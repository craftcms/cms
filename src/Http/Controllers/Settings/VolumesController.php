<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Controllers\Settings;

use CraftCms\Cms\Asset\Data\Volume;
use CraftCms\Cms\Asset\Elements\Asset;
use CraftCms\Cms\Asset\Volumes;
use CraftCms\Cms\Config\GeneralConfig;
use CraftCms\Cms\Cp\Data\NavItem;
use CraftCms\Cms\Cp\Html\ContentHtml;
use CraftCms\Cms\Field\Enums\TranslationMethod;
use CraftCms\Cms\Field\Fields;
use CraftCms\Cms\Form\FormResolver;
use CraftCms\Cms\Http\RespondsWithFlash;
use CraftCms\Cms\Http\Responses\CpScreenResponse;
use CraftCms\Cms\Http\ViewModels\VolumeEditViewModel;
use CraftCms\Cms\Support\Arr;
use CraftCms\Cms\Support\File;
use CraftCms\Cms\Support\Url;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

use function CraftCms\Cms\t;

class VolumesController
{
    use RespondsWithFlash;

    private bool $readOnly;

    public function __construct(GeneralConfig $generalConfig)
    {
        $this->readOnly = ! $generalConfig->allowAdminChanges;
    }

    public function index(Request $request, Volumes $volumes): \Inertia\Response
    {
        $sort = ! empty($request->array('sort')) ? $request->array('sort') : [
            ['field' => 'sortOrder', 'direction' => 'asc'],
        ];

        match (Arr::get($sort, '0.field')) {
            'handle' => 'handle',
            'type' => 'type',
            default => 'name',
        };

        match (Arr::get($sort, '0.direction')) {
            'desc' => SORT_DESC,
            default => SORT_ASC,
        };

        return Inertia::render('settings/assets/Index', [
            'crumbs' => fn () => [
                ['label' => t('Settings'), 'url' => Url::cpUrl('settings')],
                ['label' => t('Assets'), 'url' => Url::cpUrl('settings/assets')],
                ['label' => t('Volumes')],
            ],
            'sort' => $sort,
            'subnav' => [
                new NavItem()->label(t('Volumes'))->url(Url::cpUrl('settings/assets'))->selected(true),
                new NavItem()->label(t('Image Transforms'))->url(Url::cpUrl('settings/assets/transforms')),
            ],
            'title' => t('Volume Settings'),
            'volumes' => $volumes->getAllVolumes(...),
        ]);
    }

    public function create(Volumes $volumes, FormResolver $formResolver): CpScreenResponse
    {
        abort_if($this->readOnly, 403, 'Administrative changes are disallowed in this environment.');

        return $this->editScreen(new Volume, $volumes, $formResolver);
    }

    public function edit(Volumes $volumes, FormResolver $formResolver, int $volumeId): CpScreenResponse
    {
        $volume = $volumes->getVolumeById($volumeId);

        abort_if(is_null($volume), 404, 'Volume not found');

        return $this->editScreen($volume, $volumes, $formResolver);
    }

    public function renderForm(Request $request, Volumes $volumes, FormResolver $formResolver): JsonResponse
    {
        $data = $request->validate([
            'values' => ['required', 'array'],
            'values.volumeId' => ['nullable', 'integer'],
            'values.name' => ['nullable', 'string'],
            'values.handle' => ['nullable', 'string'],
            'values.fsHandle' => ['nullable', 'string'],
            'values.subpath' => ['nullable', 'string'],
            'values.transformFsHandle' => ['nullable', 'string'],
            'values.transformSubpath' => ['nullable', 'string'],
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
            'form' => new VolumeEditViewModel(
                $volume,
                $volumes,
                $formResolver,
                values: $data['values'],
            )->form(),
        ]);
    }

    public function store(Request $request, Volumes $volumes, Fields $fields): Response
    {
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
        $volume->subpath = $subpath;
        $volume->transformFsHandle = $request->input('transformFsHandle');
        $volume->transformSubpath = $request->input('transformSubpath', '');
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
        $volumeIds = $request->input('ids', []);
        $volumes->reorderVolumes($volumeIds);

        return $this->asSuccess(t('Order updated.'));
    }

    public function destroy(Request $request, Volumes $volumes, int $volumeId): Response
    {
        $volumes->deleteVolumeById($volumeId);

        return $this->asSuccess();
    }

    private function editScreen(Volume $volume, Volumes $volumes, FormResolver $formResolver): CpScreenResponse
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
            ->inertiaPage('Form', new VolumeEditViewModel(
                $volume,
                $volumes,
                $formResolver,
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
