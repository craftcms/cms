<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\ViewModels;

use CraftCms\Cms\Asset\AssetTransformDrivers;
use CraftCms\Cms\Asset\Data\AssetTransformer;
use CraftCms\Cms\Form\Controls\Choice;
use CraftCms\Cms\Form\Controls\Handle;
use CraftCms\Cms\Form\Controls\Text;
use CraftCms\Cms\Form\Controls\Textarea;
use CraftCms\Cms\Form\Enums\ControlMode;
use CraftCms\Cms\Form\Form;
use CraftCms\Cms\Form\FormContext;
use CraftCms\Cms\Form\FormPayload;
use CraftCms\Cms\Form\FormResolver;
use CraftCms\Cms\Form\Nodes\Callout;
use CraftCms\Cms\Form\Nodes\Field;
use CraftCms\Cms\Form\Nodes\HiddenField;
use CraftCms\Cms\Http\Controllers\Settings\AssetTransformersController;
use CraftCms\Cms\Support\Arr;
use CraftCms\Cms\Support\Json;

use function CraftCms\Cms\t;

class AssetTransformerEditViewModel extends ViewModel
{
    /** @param array<string, mixed>|null $values */
    public function __construct(
        private readonly AssetTransformer $transformer,
        private readonly AssetTransformDrivers $assetTransformDrivers,
        private readonly FormResolver $formResolver,
        private readonly bool $readOnly = false,
        private readonly ?array $values = null,
    ) {}

    public function form(): FormPayload
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

        $form = $this->formResolver->resolve(Form::make([
            HiddenField::make('uid'),
            HiddenField::make('oldDriver'),
            Field::make(t('Name'), Text::make('name')->autofocus()->mode($identityMode))->required(),
            Field::make(t('Handle'), $handle)->required(),
            Field::make(
                t('Driver'),
                Choice::make('driver')->options($this->driverOptions())->mode($identityMode),
            )->reactive()->required(),
        ]), new FormContext(
            values: $values,
            errors: Arr::only($this->transformer->errors()->getMessages(), ['name', 'handle', 'driver']),
            mode: $mode,
            refreshable: ! $this->readOnly,
        ));
        $settingsForm = $this->settingsForm($values, $mode);

        return new FormPayload(
            scope: [],
            refreshable: ! $this->readOnly,
            nodes: [...$form->nodes, ...$settingsForm->nodes],
            values: [...$form->values, ...$settingsForm->values],
            errors: [...$form->errors, ...$settingsForm->errors],
            globalErrors: [...$form->globalErrors, ...$settingsForm->globalErrors],
        );
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
            : action([AssetTransformersController::class, 'renderForm']);
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
    private function settingsForm(array $values, ControlMode $mode): FormPayload
    {
        $driver = $values['driver'];

        if (! is_string($driver) || ! $this->assetTransformDrivers->has($driver)) {
            return $this->formResolver->resolve(Form::make([
                Callout::make('unavailable-driver', t('This Asset Transformer’s driver is unavailable. Select an available driver to save it.')),
                Field::make(
                    t('Stored settings'),
                    Textarea::make('unavailableSettings')
                        ->value(Json::encode($this->transformer->settings, JSON_PRETTY_PRINT))
                        ->mode(ControlMode::ReadOnly),
                ),
            ]), new FormContext(mode: $mode));
        }

        $definition = $this->assetTransformDrivers->driver($driver)->definition();

        return $this->formResolver->resolve(
            Form::make($definition->settingsFields),
            new FormContext(
                namespace: 'settings',
                values: $values,
                errors: Arr::except($this->transformer->errors()->getMessages(), ['name', 'handle', 'driver']),
                mode: $mode,
                refreshable: ! $this->readOnly,
            ),
        );
    }
}
