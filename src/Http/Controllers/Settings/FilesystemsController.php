<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Controllers\Settings;

use CraftCms\Cms\Component\ComponentHelper;
use CraftCms\Cms\Config\GeneralConfig;
use CraftCms\Cms\Cp\Html\ContentHtml;
use CraftCms\Cms\Filesystem\Contracts\FsInterface;
use CraftCms\Cms\Filesystem\Filesystems;
use CraftCms\Cms\Filesystem\Resources\FsResource;
use CraftCms\Cms\Http\RespondsWithFlash;
use CraftCms\Cms\Http\Responses\CpScreenResponse;
use CraftCms\Cms\Http\ViewModels\FilesystemsEditViewModel;
use CraftCms\Cms\Support\Arr;
use CraftCms\Cms\Support\Html;
use CraftCms\Cms\Support\Typecast;
use CraftCms\Cms\Support\Url;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use ReflectionException;
use ReflectionProperty;
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
        $type = $request->string('type')->toString();
        $settings = Arr::whereNotNull($request->input('types', [])[Html::id($type)] ?? []);

        $fs = $this->filesystems->createFilesystem([
            'type' => $type,
            'name' => $request->input('name'),
            'handle' => $request->input('handle'),
            'oldHandle' => $request->input('oldHandle'),
            'settings' => $settings,
        ]);

        if (! $this->filesystems->saveFilesystem($fs)) {
            return $this->asModelFailure($fs, t('Couldn’t save filesystem.'), 'filesystem', [
                'errors' => new FilesystemsEditViewModel($fs, $this->filesystems)->settingsErrors(),
            ]);
        }

        return $this->asModelSuccess($fs, t('Filesystem saved.'), 'filesystem');
    }

    public function renderSettings(Request $request): JsonResponse
    {
        $request->validate([
            'type' => ['required', 'string'],
            'oldType' => ['nullable', 'string'],
            'settings' => ['nullable', 'array'],
        ]);

        $type = $request->string('type')->toString();
        $oldType = $request->input('oldType');
        $filesystem = $this->filesystems->createFilesystem($type);

        if (is_string($oldType) && ComponentHelper::validateComponentClass($oldType, FsInterface::class)) {
            $settings = $request->array('settings');

            $settings = array_filter($settings, function (string $attribute) use ($type, $oldType): bool {
                try {
                    $newProperty = new ReflectionProperty($type, $attribute);
                    $oldProperty = new ReflectionProperty($oldType, $attribute);

                    return $newProperty->getDeclaringClass()->name === $oldProperty->getDeclaringClass()->name;
                } catch (ReflectionException) {
                    return false;
                }
            }, ARRAY_FILTER_USE_KEY);

            Typecast::configure($filesystem, $settings);
        }

        $viewModel = new FilesystemsEditViewModel($filesystem, $this->filesystems);

        return new JsonResponse([
            'definition' => $viewModel->settingsDefinition(),
            'values' => $viewModel->settingsValues(),
            'errors' => $viewModel->settingsErrors(),
            'bindingScope' => $viewModel->settingsBindingScope(),
            'inputNamespace' => $viewModel->settingsInputNamespace(),
            'readOnly' => false,
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
