<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Controllers\Settings;

use CraftCms\Cms\Asset\AssetTransformDrivers;
use CraftCms\Cms\Asset\AssetTransformers;
use CraftCms\Cms\Asset\Data\AssetTransformer;
use CraftCms\Cms\Config\GeneralConfig;
use CraftCms\Cms\Cp\Components\CopyAttribute;
use CraftCms\Cms\Cp\Data\ActionItem;
use CraftCms\Cms\Http\RespondsWithFlash;
use CraftCms\Cms\Http\Responses\CpScreenResponse;
use CraftCms\Cms\Http\ViewModels\AssetTransformerEditViewModel;
use CraftCms\Cms\Support\Arr;
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
class AssetTransformersController extends BaseAssetSettingsController
{
    use RespondsWithFlash;

    private bool $readOnly;

    public function __construct(
        GeneralConfig $generalConfig,
        private readonly AssetTransformers $assetTransformers,
        private readonly AssetTransformDrivers $assetTransformDrivers,
        private readonly UiResolver $uiResolver,
    ) {
        $this->readOnly = ! $generalConfig->allowAdminChanges;
    }

    public function index(): CpScreenResponse
    {
        $defaultHandle = $this->assetTransformers->getDefaultAssetTransformer()->handle;
        $rows = $this->assetTransformers->getAllAssetTransformers()
            ->sortBy('name')
            ->map(function (AssetTransformer $transformer) use ($defaultHandle): array {
                $deleteDisabledReason = $this->assetTransformers->getDeleteDisabledReason($transformer);

                return [
                    'id' => $transformer->uid,
                    'name' => [
                        'label' => $transformer->name.($transformer->handle === $defaultHandle ? ' ('.t('Default').')' : ''),
                        'url' => route('craft.cp.settings.assets.transformers.edit', ['handle' => $transformer->handle]),
                    ],
                    'handle' => ['html' => CopyAttribute::make()->value($transformer->handle)->toHtml()],
                    'driver' => $this->assetTransformDrivers->has($transformer->driver)
                        ? $this->assetTransformDrivers->driver($transformer->driver)->definition()->name
                        : t('{driver} (Unavailable)', ['driver' => $transformer->driver]),
                    ...($this->readOnly ? [] : [
                        '_deletable' => $deleteDisabledReason === null,
                        '_deleteDisabledReason' => $deleteDisabledReason,
                        '_deleteUrl' => route('craft.cp.settings.assets.transformers.destroy', ['handle' => $transformer->handle]),
                        '_deleteConfirmMessage' => t('Are you sure you want to delete the “{name}” Asset Transformer?', ['name' => $transformer->name]),
                    ]),
                ];
            });
        $table = Table::make('asset-transformers')
            ->columns([
                ['key' => 'name', 'label' => t('Name')],
                ['key' => 'handle', 'label' => t('Handle')],
                ['key' => 'driver', 'label' => t('Driver')],
            ])
            ->rows($rows)
            ->unless($this->readOnly, fn (Table $table) => $table
                ->createAction(t('New Asset Transformer'), route('craft.cp.settings.assets.transformers.create'))
                ->createActionInPageHeader()
                ->deletable());

        return new CpScreenResponse()
            ->title(t('Asset Transformers'))
            ->subnav($this->subnav())
            ->crumbs([
                new ActionItem()->label(t('Settings'))->href(route('craft.cp.settings.index')),
                new ActionItem()->label(t('Assets'))->href(route('craft.cp.settings.assets.volumes.index')),
                new ActionItem()->label(t('Asset Transformers')),
            ])
            ->ui(Ui::make([$table]));
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
            'name' => $data['name'] ?? '',
            'handle' => $data['handle'] ?? '',
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

    public function renderUi(Request $request): JsonResponse
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
            'name' => $values['name'] ?? '',
            'handle' => $values['handle'] ?? '',
            'driver' => $values['driver'],
            'settings' => $values['settings'] ?? [],
        ]);

        return new JsonResponse([
            'ui' => $this->viewModel($transformer, $values)->ui(),
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
            ? trim($transformer->name) ?: t('Edit Asset Transformer')
            : t('Create a new Asset Transformer');

        return new CpScreenResponse()
            ->title($title)
            ->addCrumb(t('Settings'), 'settings')
            ->addCrumb(t('Assets'), 'settings/assets')
            ->addCrumb(t('Asset Transformers'), 'settings/assets/transformers')
            ->inertiaPage('Ui', $this->viewModel($transformer))
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
            $this->uiResolver,
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
        }, $this->assetTransformDrivers->driver($driver)->definition()->settingsFields);

        return Arr::only($settings, $handles);
    }
}
