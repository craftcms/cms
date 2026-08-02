<?php

declare(strict_types=1);

namespace CraftCms\Cms\Cp\FormDefinitions;

use CraftCms\Cms\Cp\Components\CheckboxSelect as CheckboxSelectComponent;
use CraftCms\Cms\Cp\Components\ColorPalette as ColorPaletteComponent;
use CraftCms\Cms\Cp\Components\Combobox as ComboboxComponent;
use CraftCms\Cms\Cp\Components\DateInput as DateInputComponent;
use CraftCms\Cms\Cp\Components\EditableTable as EditableTableComponent;
use CraftCms\Cms\Cp\Components\ElementCondition as ElementConditionComponent;
use CraftCms\Cms\Cp\Components\Field as FieldComponent;
use CraftCms\Cms\Cp\Components\KeyedTable as KeyedTableComponent;
use CraftCms\Cms\Cp\Components\Lightswitch as LightswitchComponent;
use CraftCms\Cms\Cp\Components\MoneyInput as MoneyInputComponent;
use CraftCms\Cms\Cp\Components\NumberInput as NumberInputComponent;
use CraftCms\Cms\Cp\Components\ObjectSelect as ObjectSelectComponent;
use CraftCms\Cms\Cp\Components\OptionRows as OptionRowsComponent;
use CraftCms\Cms\Cp\Components\Select as SelectComponent;
use CraftCms\Cms\Cp\Components\TextInput as TextInputComponent;
use CraftCms\Cms\Cp\Components\TimeInput as TimeInputComponent;
use CraftCms\Cms\Cp\FormDefinitions\Contracts\ProjectableFormElement;
use CraftCms\Cms\Cp\FormDefinitions\Data\PluginData;
use CraftCms\Cms\Cp\FormDefinitions\Elements\FieldLayoutInput;
use CraftCms\Cms\Cp\FormDefinitions\Elements\FormElement;
use CraftCms\Cms\Cp\FormDefinitions\Elements\Group;
use CraftCms\Cms\Cp\FormDefinitions\Elements\Tab;
use CraftCms\Cms\Cp\FormDefinitions\Elements\Tabs;
use CraftCms\Cms\Plugin\Contracts\PluginInterface;
use Illuminate\Container\Attributes\Singleton;
use InvalidArgumentException;

#[Singleton]
class FormElementTypes
{
    /**
     * @var array<string, array{
     *     class: class-string<FormElement|ProjectableFormElement>|null,
     *     container: bool,
     *     plugin: PluginData|null,
     * }>
     */
    private array $registrations;

    public function __construct()
    {
        $this->registrations = [
            FieldComponent::formElementType() => [
                'class' => FieldComponent::class,
                'container' => FieldComponent::isFormElementContainer(),
                'plugin' => null,
            ],
            Group::type() => ['class' => Group::class, 'container' => true, 'plugin' => null],
            Tabs::type() => ['class' => Tabs::class, 'container' => true, 'plugin' => null],
            Tab::type() => ['class' => Tab::class, 'container' => true, 'plugin' => null],
            TextInputComponent::formElementType() => [
                'class' => TextInputComponent::class,
                'container' => TextInputComponent::isFormElementContainer(),
                'plugin' => null,
            ],
            ComboboxComponent::formElementType() => [
                'class' => ComboboxComponent::class,
                'container' => ComboboxComponent::isFormElementContainer(),
                'plugin' => null,
            ],
            CheckboxSelectComponent::formElementType() => [
                'class' => CheckboxSelectComponent::class,
                'container' => CheckboxSelectComponent::isFormElementContainer(),
                'plugin' => null,
            ],
            ElementConditionComponent::formElementType() => [
                'class' => ElementConditionComponent::class,
                'container' => ElementConditionComponent::isFormElementContainer(),
                'plugin' => null,
            ],
            EditableTableComponent::formElementType() => [
                'class' => EditableTableComponent::class,
                'container' => EditableTableComponent::isFormElementContainer(),
                'plugin' => null,
            ],
            FieldLayoutInput::type() => ['class' => FieldLayoutInput::class, 'container' => false, 'plugin' => null],
            NumberInputComponent::formElementType() => [
                'class' => NumberInputComponent::class,
                'container' => NumberInputComponent::isFormElementContainer(),
                'plugin' => null,
            ],
            SelectComponent::formElementType() => [
                'class' => SelectComponent::class,
                'container' => SelectComponent::isFormElementContainer(),
                'plugin' => null,
            ],
            LightswitchComponent::formElementType() => [
                'class' => LightswitchComponent::class,
                'container' => LightswitchComponent::isFormElementContainer(),
                'plugin' => null,
            ],
            KeyedTableComponent::formElementType() => [
                'class' => KeyedTableComponent::class,
                'container' => KeyedTableComponent::isFormElementContainer(),
                'plugin' => null,
            ],
            OptionRowsComponent::formElementType() => [
                'class' => OptionRowsComponent::class,
                'container' => OptionRowsComponent::isFormElementContainer(),
                'plugin' => null,
            ],
            ObjectSelectComponent::formElementType() => [
                'class' => ObjectSelectComponent::class,
                'container' => ObjectSelectComponent::isFormElementContainer(),
                'plugin' => null,
            ],
            DateInputComponent::formElementType() => [
                'class' => DateInputComponent::class,
                'container' => DateInputComponent::isFormElementContainer(),
                'plugin' => null,
            ],
            TimeInputComponent::formElementType() => [
                'class' => TimeInputComponent::class,
                'container' => TimeInputComponent::isFormElementContainer(),
                'plugin' => null,
            ],
            ColorPaletteComponent::formElementType() => [
                'class' => ColorPaletteComponent::class,
                'container' => ColorPaletteComponent::isFormElementContainer(),
                'plugin' => null,
            ],
            MoneyInputComponent::formElementType() => [
                'class' => MoneyInputComponent::class,
                'container' => MoneyInputComponent::isFormElementContainer(),
                'plugin' => null,
            ],
        ];
    }

    /** @param class-string<FormElement> ...$classes */
    public function register(string ...$classes): void
    {
        $this->registerBatch(null, ...$classes);
    }

    /**
     * @internal Plugins should call Plugin::registerFormElementTypes().
     *
     * @param  class-string<FormElement>  ...$classes
     */
    public function registerForPlugin(PluginInterface $plugin, string ...$classes): void
    {
        if ($plugin->name === null || $plugin->packageName === null) {
            throw new InvalidArgumentException("Plugin {$plugin->handle} must define its name and Composer package before registering Form Elements.");
        }

        $this->registerBatch(new PluginData(
            handle: $plugin->handle,
            name: $plugin->name,
            packageName: $plugin->packageName,
        ), ...$classes);
    }

    public function ownership(string $type): ?PluginData
    {
        return $this->registrations[$type]['plugin'] ?? null;
    }

    public function isRegistered(string $type): bool
    {
        return isset($this->registrations[$type]);
    }

    public function isContainer(string $type): bool
    {
        return $this->registrations[$type]['container'] ?? false;
    }

    /** @param class-string<FormElement> ...$classes */
    private function registerBatch(?PluginData $plugin, string ...$classes): void
    {
        $registrations = $this->registrations;

        foreach ($classes as $class) {
            if (! is_subclass_of($class, FormElement::class)) {
                throw new InvalidArgumentException(sprintf('%s must extend %s.', $class, FormElement::class));
            }

            $type = $class::type();

            if (preg_match('/^[a-z][a-z0-9-]*:[a-z][a-z0-9-]*$/D', $type) !== 1) {
                throw new InvalidArgumentException("Form Element Type \"{$type}\" must be a lowercase namespaced identifier.");
            }

            if (str_starts_with($type, 'craft:')) {
                throw new InvalidArgumentException('The "craft" Form Element namespace is reserved.');
            }

            $registration = [
                'class' => $class,
                'container' => $class::isContainer(),
                'plugin' => $plugin,
            ];
            $existing = $registrations[$type] ?? null;

            if (
                $existing !== null
                && $existing['class'] === $class
                && ($existing['plugin']?->equals($plugin) ?? $plugin === null)
            ) {
                continue;
            }

            if ($existing !== null) {
                throw new InvalidArgumentException(sprintf(
                    'Form Element Type "%s" is already registered by %s%s; %s%s cannot claim it.',
                    $type,
                    $existing['class'] ?? 'Craft',
                    $this->pluginLabel($existing['plugin']),
                    $class,
                    $this->pluginLabel($plugin),
                ));
            }

            $registrations[$type] = $registration;
        }

        $this->registrations = $registrations;
    }

    private function pluginLabel(?PluginData $plugin): string
    {
        return $plugin === null ? ' for the application' : " for plugin {$plugin->handle}";
    }
}
