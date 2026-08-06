<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Controllers\Settings;

use CraftCms\Cms\Config\GeneralConfig;
use CraftCms\Cms\Cp\Html\ContentHtml;
use CraftCms\Cms\Filesystem\Filesystems;
use CraftCms\Cms\Filesystem\Resources\FsResource;
use CraftCms\Cms\Form\FormContext;
use CraftCms\Cms\Form\FormResolver;
use CraftCms\Cms\Http\RespondsWithFlash;
use CraftCms\Cms\Http\Responses\CpScreenResponse;
use CraftCms\Cms\Http\ViewModels\FilesystemsEditViewModel;
use CraftCms\Cms\Support\Arr;
use CraftCms\Cms\Support\Url;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

use function CraftCms\Cms\t;

class FilesystemsController
{
    use RespondsWithFlash;

    private bool $readOnly;

    public function __construct(
        GeneralConfig $generalConfig,
        private readonly Filesystems $filesystems,
    ) {
        $this->readOnly = ! $generalConfig->allowAdminChanges;
    }

    public function index(): \Inertia\Response
    {
        return Inertia::render('settings/filesystems/Index', [
            'crumbs' => fn () => [
                ['label' => t('Settings'), 'url' => Url::cpUrl('settings')],
                ['label' => t('Filesystems')],
            ],
            'title' => t('Filesystems'),
            'filesystems' => FsResource::collection($this->filesystems->getAllFilesystems()),
        ]);
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
            $filesystem = $this->filesystems->getFilesystemByHandle($handle);

            abort_if(is_null($filesystem), 404, 'Filesystem not found');
        }

        $title = $filesystem !== null
            ? trim((string) $filesystem->name ?: t('Edit Filesystem'))
            : t('Create a new filesystem');

        return new CpScreenResponse()
            ->title($title)
            ->addCrumb(t('Settings'), 'settings')
            ->addCrumb(t('Filesystems'), 'settings/filesystems')
            ->inertiaPage('settings/filesystems/Edit', new FilesystemsEditViewModel(
                $filesystem,
                $this->filesystems,
                oldHandle: $handle,
                readOnly: $this->readOnly,
            ))
            ->unless(
                $this->readOnly,
                function (CpScreenResponse $response) {
                    $response
                        ->formAttributes([
                            'action' => Url::cpUrl('settings/filesystems'),
                        ])
                        ->redirectUrl('settings/filesystems')
                        ->addAltAction(t('Save and continue editing'), [
                            'redirect' => 'settings/filesystems/{handle}',
                            'shortcut' => true,
                            'retainScroll' => true,
                        ]);
                },
                function (CpScreenResponse $response) {
                    $response->noticeHtml(app(ContentHtml::class)->readOnlyNoticeHtml());
                },
            );
    }

    public function store(Request $request): Response
    {
        $type = $request->input('type');
        $oldFilesystem = $request->input('oldHandle')
            ? $this->filesystems->getFilesystemByHandle($request->input('oldHandle'))
            : null;
        $preservedSettings = $oldFilesystem && is_string($type) && is_a($oldFilesystem, $type)
            ? [
                ...$oldFilesystem->getSettings(),
                'hasUrls' => $oldFilesystem->hasUrls,
                'url' => $oldFilesystem->url,
            ]
            : [];
        $settings = [
            ...$preservedSettings,
            ...Arr::whereNotNull($request->array('settings')),
        ];

        $fs = $this->filesystems->createFilesystem([
            'type' => $type,
            'name' => $request->input('name'),
            'handle' => $request->input('handle'),
            'oldHandle' => $request->input('oldHandle'),
            'settings' => $settings,
        ]);

        if (! $this->filesystems->saveFilesystem($fs)) {
            return $this->asModelFailure($fs, t('Couldn’t save filesystem.'), 'filesystem');
        }

        return $this->asModelSuccess($fs, t('Filesystem saved.'), 'filesystem');
    }

    public function renderSettings(Request $request): JsonResponse
    {
        $data = $request->validate([
            'type' => ['required', 'string'],
            'settings' => ['nullable', 'array'],
        ]);
        $filesystem = $this->filesystems->createFilesystem([
            'type' => $data['type'],
            'settings' => $data['settings'] ?? [],
        ]);
        $context = new FormContext(
            namespace: 'settings',
            values: ['settings' => $data['settings'] ?? []],
            refreshable: true,
        );
        $form = $filesystem->settingsForm($context);

        return new JsonResponse([
            'form' => $form === null ? null : app(FormResolver::class)->resolve($form, $context),
        ]);
    }

    public function destroy(Request $request, string $handle): Response
    {
        $fs = $this->filesystems->getFilesystemByHandle($handle);

        if ($fs) {
            $this->filesystems->removeFilesystem($fs);
        }

        return $this->asSuccess();
    }
}
