<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Controllers\Settings;

use craft\helpers\Cp;
use craft\helpers\FileHelper;
use CraftCms\Cms\Asset\Data\Volume;
use CraftCms\Cms\Asset\Elements\Asset;
use CraftCms\Cms\Asset\Volumes;
use CraftCms\Cms\Config\GeneralConfig;
use CraftCms\Cms\Cp\SelectOptions;
use CraftCms\Cms\Field\Field;
use CraftCms\Cms\Field\Fields;
use CraftCms\Cms\Http\RespondsWithFlash;
use CraftCms\Cms\Http\Responses\CpScreenResponse;
use CraftCms\Cms\Support\Json;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\Response;

use function CraftCms\Cms\t;

final class VolumesController
{
    use RespondsWithFlash;

    private bool $readOnly;

    public function __construct(GeneralConfig $generalConfig)
    {
        $this->readOnly = ! $generalConfig->allowAdminChanges;
    }

    public function index(Volumes $volumes): View
    {
        return view('settings/assets/volumes/_index', [
            'volumes' => $volumes->getAllVolumes(),
            'readOnly' => $this->readOnly,
        ]);
    }

    public function create(Volumes $volumes): CpScreenResponse
    {
        return $this->edit($volumes);
    }

    public function edit(Volumes $volumes, ?int $volumeId = null): CpScreenResponse
    {
        if ($volumeId === null && $this->readOnly) {
            abort(403, 'Administrative changes are disallowed in this environment.');
        }

        $volume = null;

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
            })
            ->sortBy(fn (array $option) => $option['label'])
            ->values()
            ->all();

        array_unshift($fsOptions, ['label' => t('Select a filesystem'), 'value' => '']);

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
                'readOnly' => $this->readOnly,
            ])
            ->unless(
                $this->readOnly,
                function (CpScreenResponse $response) use ($volume) {
                    $response
                        ->action('volumes/save-volume')
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
                    $response->noticeHtml(Cp::readOnlyNoticeHtml());
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
            $subpath = FileHelper::normalizePath(ltrim(trim((string) $subpath), '/'));
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
            'titleTranslationMethod' => $request->input('titleTranslationMethod', Field::TRANSLATION_METHOD_SITE),
            'titleTranslationKeyFormat' => $request->input('titleTranslationKeyFormat'),
            'altTranslationMethod' => $request->input('altTranslationMethod', Field::TRANSLATION_METHOD_NONE),
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
        $volumeIds = Json::decode($request->input('ids'));
        $volumes->reorderVolumes($volumeIds);

        return $this->asSuccess();
    }

    public function delete(Request $request, Volumes $volumes): Response
    {
        $request->validate(['id' => ['required', 'integer']]);

        $volumes->deleteVolumeById($request->integer('id'));

        return $this->asSuccess();
    }

    private function fsOptionTargetKey(mixed $value): ?string
    {
        if (! is_string($value) || $value === '') {
            return null;
        }

        if (str_starts_with($value, 'disk:')) {
            return $value;
        }

        return "fs:{$value}";
    }
}
