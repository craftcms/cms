<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Controllers\Settings;

use CraftCms\Cms\Asset\AssetTransformDrivers;
use CraftCms\Cms\Asset\AssetTransformers;
use CraftCms\Cms\Asset\Data\AssetTransformer;
use CraftCms\Cms\Config\GeneralConfig;
use CraftCms\Cms\Cp\Data\NavItem;
use CraftCms\Cms\Form\FormResolver;
use CraftCms\Cms\Http\RespondsWithFlash;
use CraftCms\Cms\Http\Responses\CpScreenResponse;
use CraftCms\Cms\Http\ViewModels\AssetTransformerEditViewModel;
use CraftCms\Cms\Support\Arr;
use CraftCms\Cms\Support\Url;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

use function CraftCms\Cms\t;

class AssetTransformersController
{
    use RespondsWithFlash;

    private bool $readOnly;

    public function __construct(
        GeneralConfig $generalConfig,
        private readonly AssetTransformers $assetTransformers,
        private readonly AssetTransformDrivers $assetTransformDrivers,
        private readonly FormResolver $formResolver,
    ) {
        $this->readOnly = ! $generalConfig->allowAdminChanges;
    }

    public function index(): \Inertia\Response
    {
        $defaultHandle = $this->assetTransformers->getDefaultAssetTransformer()->handle;

        return Inertia::render('settings/assets/transformers/Index', [
            'crumbs' => fn () => [
                ['label' => t('Settings'), 'url' => Url::cpUrl('settings')],
                ['label' => t('Assets'), 'url' => Url::cpUrl('settings/assets')],
                ['label' => t('Asset Transformers')],
            ],
            'readOnly' => $this->readOnly,
            'subnav' => $this->subnav(),
            'title' => t('Asset Transformers'),
            'transformers' => $this->assetTransformers
                ->getAllAssetTransformers()
                ->map(fn (AssetTransformer $transformer): array => [
                    'uid' => $transformer->uid,
                    'name' => $transformer->name,
                    'handle' => $transformer->handle,
                    'driver' => $this->assetTransformDrivers->has($transformer->driver)
                        ? $this->assetTransformDrivers->driver($transformer->driver)->definition()->name
                        : t('{driver} (Unavailable)', ['driver' => $transformer->driver]),
                    'isDefault' => $transformer->handle === $defaultHandle,
                    'canDelete' => ! $this->readOnly && $transformer->handle !== 'craft' && $transformer->handle !== $defaultHandle,
                ])
                ->sortBy('name')
                ->values(),
        ]);
    }

    public function create(): CpScreenResponse
    {
        abort_if($this->readOnly, 403, 'Administrative changes are disallowed in this environment.');

        return $this->editScreen(new AssetTransformer([
            'driver' => 'craft',
            'settings' => [],
        ]));
    }

    public function edit(string $handle): CpScreenResponse
    {
        $transformer = $this->assetTransformers->getAssetTransformerByHandle($handle);

        abort_if($transformer === null, 404, 'Asset Transformer not found');

        return $this->editScreen($transformer);
    }

    public function store(Request $request): Response
    {
        $data = $request->validate([
            'uid' => ['nullable', 'uuid'],
            'name' => ['nullable', 'string'],
            'handle' => ['nullable', 'string'],
            'driver' => ['required', 'string', Rule::in(array_keys($this->assetTransformDrivers->definitions()))],
            'settings' => ['nullable', 'array'],
        ]);
        $transformer = new AssetTransformer([
            'uid' => $data['uid'] ?? null,
            'name' => $data['name'] ?? null,
            'handle' => $data['handle'] ?? null,
            'driver' => $data['driver'],
            'settings' => $this->settings($data['driver'], $data['settings'] ?? []),
        ]);

        if (! $this->assetTransformers->saveAssetTransformer($transformer)) {
            throw ValidationException::withMessages($transformer->errors()->getMessages());
        }

        return $this->asModelSuccess(
            $transformer,
            t('Asset Transformer saved.'),
            'assetTransformer',
            redirect: $this->getPostedRedirectUrl($transformer)
                ?? Url::cpUrl("settings/assets/transformers/{$transformer->handle}"),
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
            'values.driver' => ['required', 'string', Rule::in(array_keys($this->assetTransformDrivers->definitions()))],
            'values.settings' => ['nullable', 'array'],
            'scope' => ['present', 'array', 'size:0'],
        ]);
        $values = $data['values'];

        if (($values['oldDriver'] ?? null) !== $values['driver']) {
            $values['settings'] = [];
            $values['oldDriver'] = $values['driver'];
        }

        $transformer = new AssetTransformer([
            'uid' => $values['uid'] ?? null,
            'name' => $values['name'] ?? null,
            'handle' => $values['handle'] ?? null,
            'driver' => $values['driver'],
            'settings' => $values['settings'] ?? [],
        ]);

        return new JsonResponse([
            'form' => $this->viewModel($transformer, $values)->form(),
        ]);
    }

    public function destroy(string $handle): Response
    {
        $transformer = $this->assetTransformers->getAssetTransformerByHandle($handle);

        if ($transformer !== null) {
            $this->assetTransformers->deleteAssetTransformer($transformer);
        }

        return $this->asSuccess(t('Asset Transformer deleted.'));
    }

    private function editScreen(AssetTransformer $transformer): CpScreenResponse
    {
        $title = $transformer->uid
            ? trim((string) $transformer->name) ?: t('Edit Asset Transformer')
            : t('Create a new Asset Transformer');

        return new CpScreenResponse()
            ->title($title)
            ->addCrumb(t('Settings'), 'settings')
            ->addCrumb(t('Assets'), 'settings/assets')
            ->addCrumb(t('Asset Transformers'), 'settings/assets/transformers')
            ->inertiaPage('Form', $this->viewModel($transformer))
            ->redirectUrl('settings/assets/transformers')
            ->unless($this->readOnly, function (CpScreenResponse $response) {
                $response
                    ->addAltAction(t('Save and continue editing'), [
                        'redirect' => 'settings/assets/transformers/{handle}',
                        'shortcut' => true,
                        'retainScroll' => true,
                    ]);
            });
    }

    /** @param array<string, mixed>|null $values */
    private function viewModel(AssetTransformer $transformer, ?array $values = null): AssetTransformerEditViewModel
    {
        return new AssetTransformerEditViewModel(
            $transformer,
            $this->assetTransformDrivers,
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
                    'driver' => t('The selected Asset Transform driver has invalid settings.'),
                ]);
            }

            return $path;
        }, $this->assetTransformDrivers->driver($driver)->definition()->settings);

        return Arr::only($settings, $handles);
    }

    /** @return list<NavItem> */
    private function subnav(): array
    {
        return [
            new NavItem()->label(t('Volumes'))->url(Url::cpUrl('settings/assets')),
            new NavItem()->label(t('Image Transforms'))->url(Url::cpUrl('settings/assets/transforms')),
            new NavItem()->label(t('Asset Transformers'))->url(Url::cpUrl('settings/assets/transformers'))->selected(true),
        ];
    }
}
