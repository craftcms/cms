<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\ViewModels;

use CraftCms\Cms\Asset\AssetTransformDrivers;
use CraftCms\Cms\Asset\Data\AssetTransformer;
use CraftCms\Cms\Http\Controllers\Settings\AssetTransformersController;
use CraftCms\Cms\Support\Arr;
use CraftCms\Cms\Support\Json;
use CraftCms\Cms\Ui\Contracts\Node;
use CraftCms\Cms\Ui\Controls\Choice;
use CraftCms\Cms\Ui\Controls\Handle;
use CraftCms\Cms\Ui\Controls\Text;
use CraftCms\Cms\Ui\Controls\Textarea;
use CraftCms\Cms\Ui\Enums\ControlMode;
use CraftCms\Cms\Ui\Nodes\Callout;
use CraftCms\Cms\Ui\Nodes\Field;
use CraftCms\Cms\Ui\Nodes\Group;
use CraftCms\Cms\Ui\Nodes\HiddenField;
use CraftCms\Cms\Ui\Nodes\Scope;
use CraftCms\Cms\Ui\Ui;
use CraftCms\Cms\Ui\UiContext;
use CraftCms\Cms\Ui\UiPayload;
use CraftCms\Cms\Ui\UiResolver;

use function CraftCms\Cms\t;

/**
 * @since 6.0.0
 */
class AssetTransformerEditViewModel extends ViewModel
{
    /** @param array<string, mixed>|null $values */
    public function __construct(
        private readonly AssetTransformer $transformer,
        private readonly AssetTransformDrivers $assetTransformDrivers,
        private readonly UiResolver $uiResolver,
        private readonly bool $readOnly = false,
        private readonly ?array $values = null,
    ) {}

    public function ui(): UiPayload
    {
        $values = $this->values ?? [
            'uid' => $this->transformer->uid,
            'name' => $this->transformer->name,
            'handle' => $this->transformer->handle,
            'driver' => $this->transformer->driver,
            'oldDriver' => $this->transformer->driver,
            'settings' => $this->transformer->settings,
        ];
        $mode = $this->readOnly ? ControlMode::ReadOnly : ControlMode::Editable;
        $identityMode = $this->transformer->handle === 'craft' ? ControlMode::ReadOnly : ControlMode::Editable;
        $handle = Handle::make('handle')->mode($identityMode);

        if ($this->transformer->uid === null) {
            $handle->source('name');
        }

        $settings = $this->settingsNode($values);
        $errors = Arr::only($this->transformer->errors()->getMessages(), ['name', 'handle', 'driver']);

        if ($settings instanceof Scope) {
            foreach (Arr::except($this->transformer->errors()->getMessages(), ['name', 'handle', 'driver']) as $path => $messages) {
                $errors[$path === '' ? 'settings' : "settings.{$path}"] = $messages;
            }
        }

        return $this->uiResolver->resolve(Ui::make([
            HiddenField::make('uid'),
            HiddenField::make('oldDriver'),
            Field::make(t('Name'), Text::make('name')->autofocus()->mode($identityMode))->required(),
            Field::make(t('Handle'), $handle)->required(),
            Field::make(
                t('Driver'),
                Choice::make('driver')->options($this->driverOptions())->mode($identityMode)->reactive(),
            )->required(),
            $settings,
        ]), new UiContext(
            values: $values,
            errors: $errors,
            mode: $mode,
            refreshable: ! $this->readOnly,
        ));
    }

    /** @return array{method:'post',url:string} */
    public function submit(): array
    {
        return [
            'method' => 'post',
            'url' => action([AssetTransformersController::class, 'store']),
        ];
    }

    public function refreshUrl(): ?string
    {
        return $this->readOnly
            ? null
            : action([AssetTransformersController::class, 'renderUi']);
    }

    /** @return list<array{label:string,value:string,disabled?:bool}> */
    private function driverOptions(): array
    {
        $options = collect($this->assetTransformDrivers->definitions())
            ->map(fn ($definition, string $handle): array => [
                'label' => $definition->name,
                'value' => $handle,
            ]);
        $selected = $this->transformer->driver;

        if ($selected && ! $options->has($selected)) {
            $options->put($selected, [
                'label' => t('{driver} (Unavailable)', ['driver' => $selected]),
                'value' => $selected,
                'disabled' => true,
            ]);
        }

        return $options->sortBy('label')->values()->all();
    }

    /** @param array<string, mixed> $values */
    private function settingsNode(array $values): Node
    {
        $driver = $values['driver'];

        if (! is_string($driver) || ! $this->assetTransformDrivers->has($driver)) {
            return Group::make('asset-transformer-settings', [
                Callout::make('unavailable-driver', t('This Asset Transformer’s driver is unavailable. Select an available driver to save it.')),
                Field::make(
                    t('Stored settings'),
                    Textarea::make('unavailableSettings')
                        ->value(Json::encode($this->transformer->settings, JSON_PRETTY_PRINT))
                        ->mode(ControlMode::ReadOnly),
                ),
            ])->dependsOn('driver');
        }

        $definition = $this->assetTransformDrivers->driver($driver)->definition();

        return Scope::make('settings', [
            Group::make('asset-transformer-settings', $definition->settingsFields)
                ->dependsOn('driver'),
        ]);
    }
}
