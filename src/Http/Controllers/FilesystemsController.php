<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Controllers;

use Craft;
use craft\base\Fs;
use craft\base\FsInterface;
use craft\helpers\Cp;
use CraftCms\Cms\Config\GeneralConfig;
use CraftCms\Cms\Http\RespondsWithFlash;
use CraftCms\Cms\Http\Responses\CpScreenResponse;
use CraftCms\Cms\Support\Arr;
use CraftCms\Cms\Support\Facades\Filesystems;
use CraftCms\Cms\Support\Html;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

use function CraftCms\Cms\t;

final class FilesystemsController
{
    use RespondsWithFlash;

    private bool $readOnly;

    public function __construct(GeneralConfig $generalConfig)
    {
        $this->readOnly = ! $generalConfig->allowAdminChanges;

        // Need Craft for now
        app('Craft');
    }

    public function index(): View
    {
        $variables = [];
        $variables['filesystems'] = Filesystems::getAllFilesystems();
        $variables['readOnly'] = $this->readOnly;

        return view('settings/filesystems/_index', $variables);
    }

    public function create(): CpScreenResponse
    {
        return $this->edit();
    }

    public function edit(?string $handle = null): CpScreenResponse
    {
        if ($handle === null && $this->readOnly) {
            abort(403, 'Administrative changes are disallowed in this environment.');
        }

        $filesystem = null;

        if ($handle !== null) {
            $filesystem = Filesystems::getFilesystemByHandle($handle);

            abort_if(is_null($filesystem), 404, 'Filesystem not found');
        }

        $allFsTypes = Filesystems::getAllFilesystemTypes();

        $fsInstances = [];
        $fsOptions = [];

        foreach ($allFsTypes as $fsType) {
            /** @var Fs $fsInstance */
            $fsInstance = Craft::createObject($fsType);

            if ($filesystem === null) {
                $filesystem = $fsInstance;
            }

            $fsInstances[$fsType] = $fsInstance;
            $fsOptions[] = [
                'value' => $fsType,
                'label' => $fsInstance::displayName(),
            ];
        }

        // Sort them by name
        $fsOptions = Arr::sort($fsOptions, 'label');

        if ($handle && Filesystems::getFilesystemByHandle($handle)) {
            $title = trim((string) $filesystem->name ?: t('Edit Filesystem'));
        } else {
            $title = t('Create a new filesystem');
        }

        return new CpScreenResponse()
            ->title($title)
            ->addCrumb(t('Settings'), 'settings')
            ->addCrumb(t('Filesystems'), 'settings/filesystems')
            ->contentTemplate('settings/filesystems/_edit.twig', [
                'oldHandle' => $handle,
                'filesystem' => $filesystem,
                'fsOptions' => $fsOptions,
                'fsInstances' => $fsInstances,
                'fsTypes' => $allFsTypes,
                'readOnly' => $this->readOnly,
            ])
            ->unless(
                $this->readOnly,
                function (CpScreenResponse $response) {
                    $response
                        ->action('fs/save')
                        ->redirectUrl('settings/filesystems')
                        ->addAltAction(t('Save and continue editing'), [
                            'redirect' => 'settings/filesystems/{handle}',
                            'shortcut' => true,
                            'retainScroll' => true,
                        ]);
                },
                function (CpScreenResponse $response) {
                    $response->noticeHtml(Cp::readOnlyNoticeHtml());
                },
            );
    }

    public function save(Request $request): Response
    {
        $type = $request->input('type');

        /** @var FsInterface|Fs $fs */
        $fs = Filesystems::createFilesystem([
            'type' => $type,
            'name' => $request->input('name'),
            'handle' => $request->input('handle'),
            'oldHandle' => $request->input('oldHandle'),
            'settings' => $request->input('types')[Html::id($type)] ?? [],
        ]);

        if (! Filesystems::saveFilesystem($fs)) {
            return $this->asModelFailure($fs, t('Couldn’t save filesystem.'), 'filesystem');
        }

        return $this->asModelSuccess($fs, t('Filesystem saved.'), 'filesystem');
    }

    public function delete(Request $request): Response
    {
        $request->validate([
            'id' => ['required', 'string'],
        ]);

        $fs = Filesystems::getFilesystemByHandle($request->input('id'));

        if ($fs) {
            Filesystems::removeFilesystem($fs);
        }

        return $this->asSuccess();
    }
}
