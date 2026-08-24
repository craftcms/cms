<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Controllers\Settings;

use CraftCms\Cms\Asset\AssetProcessorDrivers;
use CraftCms\Cms\Asset\AssetProcessors;
use CraftCms\Cms\Asset\Data\AssetProcessor;
use CraftCms\Cms\Asset\Data\AssetProcessorIndexData;
use CraftCms\Cms\Config\GeneralConfig;
use CraftCms\Cms\Form\FormResolver;
use CraftCms\Cms\Http\RespondsWithFlash;
use CraftCms\Cms\Http\Responses\CpScreenResponse;
use CraftCms\Cms\Http\ViewModels\AssetProcessorEditViewModel;
use CraftCms\Cms\Support\Arr;
use CraftCms\Cms\Support\Url;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

use function CraftCms\Cms\t;

class AssetProcessorsController extends BaseAssetSettingsController
{
    use RespondsWithFlash;

    private bool $readOnly;

    public function __construct(
        GeneralConfig $generalConfig,
        private readonly AssetProcessors $assetProcessors,
        private readonly AssetProcessorDrivers $assetProcessorDrivers,
        private readonly FormResolver $formResolver,
    ) {
        $this->readOnly = ! $generalConfig->allowAdminChanges;
    }

    public function index(): \Inertia\Response
    {
        $defaultHandle = $this->assetProcessors->getDefaultAssetProcessor()->handle;

        return Inertia::render('settings/assets/processors/Index', [
            'crumbs' => fn () => [
                ['label' => t('Settings'), 'url' => Url::cpUrl('settings')],
                ['label' => t('Assets'), 'url' => Url::cpUrl('settings/assets')],
                ['label' => t('Asset Processors')],
            ],
            'readOnly' => $this->readOnly,
            'subnav' => $this->subnav(),
            'title' => t('Asset Processors'),
            'processors' => $this->assetProcessors
                ->getAllAssetProcessors()
                ->map(fn (AssetProcessor $processor): AssetProcessorIndexData => new AssetProcessorIndexData([
                    'uid' => $processor->uid,
                    'name' => $processor->name,
                    'handle' => $processor->handle,
                    'driver' => $this->assetProcessorDrivers->has($processor->driver)
                        ? $this->assetProcessorDrivers->driver($processor->driver)->definition()->name
                        : t('{driver} (Unavailable)', ['driver' => $processor->driver]),
                    'isDefault' => $processor->handle === $defaultHandle,
                    'deleteDisabledReason' => $this->assetProcessors->getDeleteDisabledReason($processor),
                ]))
                ->sortBy('name')
                ->values(),
        ]);
    }

    public function create(): CpScreenResponse
    {
        abort_if($this->readOnly, 403, 'Administrative changes are disallowed in this environment.');

        return $this->editScreen(new AssetProcessor([
            'driver' => 'craft',
            'settings' => [],
        ]));
    }

    public function edit(string $handle): CpScreenResponse
    {
        $processor = $this->assetProcessors->getAssetProcessorByHandle($handle);

        abort_if($processor === null, 404, 'Asset Processor not found');

        return $this->editScreen($processor);
    }

    public function store(Request $request): Response
    {
        $data = $request->validate([
            'uid' => ['nullable', 'uuid'],
            'name' => ['nullable', 'string'],
            'handle' => ['nullable', 'string'],
            'driver' => ['required', 'string', Rule::in(array_keys($this->assetProcessorDrivers->definitions()))],
            'settings' => ['nullable', 'array'],
        ]);
        $processor = new AssetProcessor([
            'uid' => $data['uid'] ?? null,
            'name' => $data['name'] ?? '',
            'handle' => $data['handle'] ?? '',
            'driver' => $data['driver'],
            'settings' => $this->settings($data['driver'], $data['settings'] ?? []),
        ]);

        if (! $this->assetProcessors->saveAssetProcessor($processor)) {
            throw ValidationException::withMessages($processor->errors()->getMessages());
        }

        return $this->asModelSuccess(
            $processor,
            t('Asset Processor saved.'),
            'assetProcessor',
            redirect: $this->getPostedRedirectUrl($processor)
                ?? Url::cpUrl("settings/assets/processors/{$processor->handle}"),
        );
    }

    public function renderForm(Request $request): JsonResponse
    {
        $data = $request->validate([
            'values' => ['required', 'array'],
            'values.uid' => ['nullable', 'uuid'],
            'values.name' => ['nullable', 'string'],
            'values.handle' => ['nullable', 'string'],
            'values.oldDriver' => ['nullable', 'string'],
            'values.driver' => ['required', 'string', Rule::in(array_keys($this->assetProcessorDrivers->definitions()))],
            'values.settings' => ['nullable', 'array'],
            'scope' => ['present', 'array', 'size:0'],
        ]);
        $values = $data['values'];

        if (($values['oldDriver'] ?? null) !== $values['driver']) {
            $values['settings'] = [];
            $values['oldDriver'] = $values['driver'];
        }

        $processor = new AssetProcessor([
            'uid' => $values['uid'] ?? null,
            'name' => $values['name'] ?? '',
            'handle' => $values['handle'] ?? '',
            'driver' => $values['driver'],
            'settings' => $values['settings'] ?? [],
        ]);

        return new JsonResponse([
            'form' => $this->viewModel($processor, $values)->form(),
        ]);
    }

    public function destroy(string $handle): Response
    {
        $processor = $this->assetProcessors->getAssetProcessorByHandle($handle);

        if ($processor !== null) {
            $this->assetProcessors->deleteAssetProcessor($processor);
        }

        return $this->asSuccess(t('Asset Processor deleted.'));
    }

    private function editScreen(AssetProcessor $processor): CpScreenResponse
    {
        $title = $processor->uid
            ? trim($processor->name) ?: t('Edit Asset Processor')
            : t('Create a new Asset Processor');

        return new CpScreenResponse()
            ->title($title)
            ->addCrumb(t('Settings'), 'settings')
            ->addCrumb(t('Assets'), 'settings/assets')
            ->addCrumb(t('Asset Processors'), 'settings/assets/processors')
            ->inertiaPage('Form', $this->viewModel($processor))
            ->redirectUrl('settings/assets/processors')
            ->unless($this->readOnly, function (CpScreenResponse $response) {
                $response
                    ->addAltAction(t('Save and continue editing'), [
                        'redirect' => 'settings/assets/processors/{handle}',
                        'shortcut' => true,
                        'retainScroll' => true,
                    ]);
            });
    }

    /** @param array<string, mixed>|null $values */
    private function viewModel(AssetProcessor $processor, ?array $values = null): AssetProcessorEditViewModel
    {
        return new AssetProcessorEditViewModel(
            $processor,
            $this->assetProcessorDrivers,
            $this->formResolver,
            readOnly: $this->readOnly,
            values: $values,
        );
    }

    /**
     * @param  array<string, mixed>  $settings
     * @return array<string, mixed>
     */
    private function settings(string $driver, array $settings): array
    {
        $handles = array_map(function ($field): string {
            $path = $field->getControl()?->path();
            $path = is_array($path) && count($path) === 1 ? $path[0] : $path;

            if (! is_string($path) || $path === '' || str_contains($path, '.')) {
                throw ValidationException::withMessages([
                    'driver' => t('The selected Asset Processor driver has invalid settings.'),
                ]);
            }

            return $path;
        }, $this->assetProcessorDrivers->driver($driver)->definition()->settings);

        return Arr::only($settings, $handles);
    }
}
