<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Controllers\Settings;

use CraftCms\Cms\Asset\AssetTransforms;
use CraftCms\Cms\Config\GeneralConfig;
use CraftCms\Cms\Cp\Html\ContentHtml;
use CraftCms\Cms\Filesystem\Contracts\FsInterface;
use CraftCms\Cms\Filesystem\Filesystems;
use CraftCms\Cms\Filesystem\Resources\FsResource;
use CraftCms\Cms\Form\FormResolver;
use CraftCms\Cms\Http\RespondsWithFlash;
use CraftCms\Cms\Http\Responses\CpScreenResponse;
use CraftCms\Cms\Http\ViewModels\FilesystemsEditViewModel;
use CraftCms\Cms\Support\Arr;
use CraftCms\Cms\Support\Url;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
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
        private readonly FormResolver $formResolver,
        private readonly AssetTransforms $assetTransforms,
    ) {
        $this->readOnly = ! $generalConfig->allowAdminChanges;
    }

    public function index(): \Inertia\Response
    {
        return Inertia::render('settings/filesystems/Index', [
            'crumbs' => fn () => [
                ['label' => t('Settings'), 'href' => Url::cpUrl('settings')],
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
            ->inertiaPage('Form', $this->filesystemViewModel($filesystem, $handle))
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
        $data = $request->validate([
            'assetTransform' => ['nullable', 'array'],
            'assetTransform.driver' => ['nullable', 'string', Rule::in(array_keys($this->assetTransforms->getDriverDefinitions()))],
            'assetTransform.settings' => ['nullable', 'array'],
        ]);
        $type = $request->string('type')->toString();
        $oldHandle = $request->string('oldHandle')->toString() ?: null;

        $fs = $this->filesystems->createFilesystem([
            'type' => $type,
            'name' => $request->input('name'),
            'handle' => $request->input('handle'),
            'oldHandle' => $oldHandle,
            'settings' => $this->filesystemSettings($type, $oldHandle, $request->array('settings')),
            'assetTransform' => $this->assetTransform($data['assetTransform'] ?? null),
        ]);

        if (! $this->filesystems->saveFilesystem($fs)) {
            $errors = collect($fs->errors()->getMessages())
                ->mapWithKeys(fn (array $messages, string $attribute): array => [
                    in_array($attribute, ['name', 'handle', 'type'], true)
                        ? $attribute
                        : "settings.{$attribute}" => $messages,
                ])
                ->all();

            return $this->asModelFailure($fs, t('Couldn’t save filesystem.'), 'filesystem', compact('errors'));
        }

        return $this->asModelSuccess($fs, t('Filesystem saved.'), 'filesystem');
    }

    public function renderForm(Request $request): JsonResponse
    {
        $data = $request->validate([
            'values' => ['required', 'array'],
            'values.type' => ['required', 'string', Rule::in($this->filesystems->getAllFilesystemTypes())],
            'values.name' => ['nullable', 'string'],
            'values.handle' => ['nullable', 'string'],
            'values.oldHandle' => ['nullable', 'string'],
            'values.settings' => ['nullable', 'array'],
            'values.assetTransform' => ['nullable', 'array'],
            'values.assetTransform.driver' => ['nullable', 'string', Rule::in(array_keys($this->assetTransforms->getDriverDefinitions()))],
            'values.assetTransform.settings' => ['nullable', 'array'],
            'scope' => ['present', 'array', 'size:0'],
        ]);
        $values = $data['values'];
        $settings = Arr::only(
            $values['settings'] ?? [],
            $this->filesystemSettingsAttributes($values['type']),
        );
        $filesystem = $this->filesystems->createFilesystem([
            'type' => $values['type'],
            'name' => $values['name'] ?? null,
            'handle' => $values['handle'] ?? null,
            'oldHandle' => $values['oldHandle'] ?? null,
            'settings' => $settings,
            'assetTransform' => $this->assetTransform($values['assetTransform'] ?? null, false),
        ]);

        return new JsonResponse([
            'form' => $this->filesystemViewModel($filesystem, $values['oldHandle'] ?? null)->form(),
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

    private function filesystemViewModel(?FsInterface $filesystem, ?string $oldHandle): FilesystemsEditViewModel
    {
        return new FilesystemsEditViewModel(
            $filesystem,
            $this->filesystems,
            $this->formResolver,
            $this->assetTransforms,
            oldHandle: $oldHandle,
            readOnly: $this->readOnly,
        );
    }

    /**
     * @param  array<string, mixed>  $settings
     * @return array<string, mixed>
     */
    private function filesystemSettings(string $type, ?string $oldHandle, array $settings): array
    {
        $settings = Arr::whereNotNull($settings);
        $oldFilesystem = $oldHandle ? $this->filesystems->getFilesystemByHandle($oldHandle) : null;

        if ($oldFilesystem && is_a($oldFilesystem, $type)) {
            $settings = [
                ...$oldFilesystem->getSettings(),
                'hasUrls' => $oldFilesystem->hasUrls,
                'url' => $oldFilesystem->url,
                ...$settings,
            ];
        }

        return Arr::only($settings, $this->filesystemSettingsAttributes($type));
    }

    /** @return list<string> */
    private function filesystemSettingsAttributes(string $type): array
    {
        $filesystem = $this->filesystems->createFilesystem(['type' => $type]);

        return [...$filesystem->settingsAttributes(), 'hasUrls', 'url'];
    }

    /**
     * @param  array{driver?: string|null, settings?: array<string, mixed>|null}|null  $config
     * @return array{driver:string,settings:array<string,mixed>}|null
     */
    private function assetTransform(?array $config, bool $validateSettings = true): ?array
    {
        if ($config === null || $config === []) {
            return null;
        }

        $driver = $config['driver'] ?? null;
        if ($driver === null || $driver === '') {
            return null;
        }

        $settings = $config['settings'] ?? [];
        $definition = $this->assetTransforms->driver($driver)->definition();

        if ($validateSettings) {
            $handles = array_map(function ($field): string {
                $path = $field->getControl()?->path();
                $path = is_array($path) && count($path) === 1 ? $path[0] : $path;

                if (! is_string($path) || $path === '' || str_contains($path, '.')) {
                    throw ValidationException::withMessages([
                        'assetTransform.driver' => t('The selected Asset Transform driver has invalid filesystem settings.'),
                    ]);
                }

                return $path;
            }, $definition->filesystemSettings);
            $submittedHandles = array_keys($settings);
            sort($handles);
            sort($submittedHandles);

            if ($submittedHandles !== $handles) {
                throw ValidationException::withMessages([
                    'assetTransform.settings' => t('Submit one value for each Asset Transform filesystem setting.'),
                ]);
            }
        }

        return compact('driver', 'settings');
    }
}
