<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Controllers\Settings;

use CraftCms\Cms\Asset\Data\Volume;
use CraftCms\Cms\Asset\Elements\Asset;
use CraftCms\Cms\Asset\Volumes;
use CraftCms\Cms\Config\GeneralConfig;
use CraftCms\Cms\Cp\Data\NavItem;
use CraftCms\Cms\Cp\Html\ContentHtml;
use CraftCms\Cms\Cp\SelectOptions;
use CraftCms\Cms\Field\Enums\TranslationMethod;
use CraftCms\Cms\Field\Fields;
use CraftCms\Cms\Http\RespondsWithFlash;
use CraftCms\Cms\Http\Responses\CpScreenResponse;
use CraftCms\Cms\Support\Arr;
use CraftCms\Cms\Support\Facades\Filesystems;
use CraftCms\Cms\Support\File;
use CraftCms\Cms\Support\Url;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
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

    public function create(Volumes $volumes): CpScreenResponse
    {
        return $this->edit($volumes);
    }

    public function edit(Volumes $volumes, ?Volume $volume = null, ?int $volumeId = null): CpScreenResponse
    {
        if ($volumeId === null && $this->readOnly) {
            abort(403, 'Administrative changes are disallowed in this environment.');
        }

        if ($volumeId !== null) {
            $volume = $volumes->getVolumeById($volumeId);

            abort_if(is_null($volume), 404, 'Volume not found');
        }

        $volume ??= new Volume;
        $isNewVolume = ! $volume->id;

        $title = $isNewVolume
            ? t('Create a new asset volume')
            : (trim((string) $volume->name) ?: t('Edit Volume'));

        $fsTarget = $volume->getResolvedFsTarget();
        $allVolumes = $volumes->getAllVolumes();

        /** @var Collection<int, string> $takenFsTargets */
        $takenFsTargets = $allVolumes
            ->reject(fn (Volume $v): bool => (bool) $v->getSubpath())
            ->map(fn (Volume $v) => $v->getResolvedFsTarget())
            ->filter();

        $fsOptions = Collection::make(SelectOptions::getFsOptions())
            ->map(function (array $option) use ($takenFsTargets, $fsTarget): array {
                $optionTarget = $this->fsOptionTargetKey($option['value'] ?? null);
                $option['disabled'] = $optionTarget !== null
                    && $takenFsTargets->contains($optionTarget)
                    && $optionTarget !== $fsTarget;

                return $option;
            });

        $diskOptions = $fsOptions->filter(fn (array $option): bool => str_starts_with($option['value'], Volume::STORAGE_DISK_PREFIX));
        $craftFilesystemOptions = $fsOptions->reject(fn (array $option): bool => str_starts_with($option['value'], Volume::STORAGE_DISK_PREFIX));

        $groupedFsOptions = $this->groupFsOptions($craftFilesystemOptions->all(), $diskOptions->all());
        $fsOptions = $groupedFsOptions;
        array_unshift($fsOptions, ['label' => t('Select a filesystem'), 'value' => '', 'data' => ['hint' => '']]);

        return new CpScreenResponse()
            ->title($title)
            ->addCrumb(t('Settings'), 'settings')
            ->addCrumb(t('Assets'), 'settings/assets')
            ->addCrumb(t('Volumes'), 'settings/assets')
            ->contentTemplate('settings/assets/volumes/_edit.twig', [
                'volumeId' => $volumeId,
                'volume' => $volume,
                'isNewVolume' => $isNewVolume,
                'typeName' => Asset::displayName(),
                'lowerTypeName' => Asset::lowerDisplayName(),
                'fsOptions' => $fsOptions,
                'groupedFsOptions' => $groupedFsOptions,
                'envTextExpanderTriggers' => SelectOptions::getEnvTextExpanderTriggers(),
                'readOnly' => $this->readOnly,
            ])
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

    public function save(Request $request, Volumes $volumes, Fields $fields): Response
    {
        $volumeId = $request->input('volumeId') ?: null;
        $oldVolume = null;

        if ($volumeId) {
            $volumeId = (int) $volumeId;
            $oldVolume = $volumes->getVolumeById($volumeId);

            abort_if(is_null($oldVolume), 400, "Invalid volume ID: {$volumeId}");
        }

        $subpath = $request->input('subpath');

        if (! empty($subpath)) {
            $subpath = File::normalizePath(ltrim(trim((string) $subpath), '/'));
        }

        $volume = new Volume([
            'id' => $volumeId,
            'uid' => $oldVolume->uid ?? null,
            'sortOrder' => $oldVolume->sortOrder ?? null,
            'name' => $request->input('name'),
            'handle' => $request->input('handle'),
            'fsHandle' => $request->input('fsHandle'),
            'subpath' => $subpath ?? null,
            'transformFsHandle' => $request->input('transformFsHandle'),
            'transformSubpath' => $request->input('transformSubpath', ''),
            'titleTranslationMethod' => $request->enum('titleTranslationMethod', TranslationMethod::class, TranslationMethod::Site),
            'titleTranslationKeyFormat' => $request->input('titleTranslationKeyFormat'),
            'altTranslationMethod' => $request->enum('altTranslationMethod', TranslationMethod::class, TranslationMethod::None),
            'altTranslationKeyFormat' => $request->input('altTranslationKeyFormat'),
        ]);

        $fieldLayout = $fields->assembleLayoutFromPost();
        $fieldLayout->type = Asset::class;
        $volume->setFieldLayout($fieldLayout);

        if (! $volumes->saveVolume($volume)) {
            return $this->asModelFailure($volume, t("Couldn\u{2019}t save volume."), 'volume');
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

    /**
     * @param  array<int, array{label:string, value:string, disabled:bool}>  $craftFilesystemOptions
     * @param  array<int, array{label:string, value:string, disabled:bool}>  $diskOptions
     * @return list<array{optgroup:string}|array{label:string, value:string, disabled:bool}>
     */
    private function groupFsOptions(array $craftFilesystemOptions, array $diskOptions): array
    {
        $options = [];

        $options[] = ['optgroup' => t('Craft Filesystems')];
        array_push($options, ...$this->sortFsOptions($craftFilesystemOptions));

        if ($diskOptions !== []) {
            $options[] = ['optgroup' => t('Laravel Disks')];
            array_push($options, ...$this->sortFsOptions($diskOptions));
        }

        return $options;
    }

    /**
     * @param  array<int, array{label:string, value:string, disabled:bool}>  $options
     * @return list<array{label:string, value:string, disabled:bool}>
     */
    private function sortFsOptions(array $options): array
    {
        return collect($options)
            ->sortBy(fn (array $option) => $option['label'])
            ->values()
            ->all();
    }

    private function fsOptionTargetKey(mixed $value): ?string
    {
        if (! is_string($value) || $value === '') {
            return null;
        }

        return Filesystems::resolveDiskName($value);
    }
}
