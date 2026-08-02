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
use CraftCms\Cms\Cp\Components\FieldLayout as FieldLayoutComponent;
use CraftCms\Cms\Cp\Components\Group as GroupComponent;
use CraftCms\Cms\Cp\Components\KeyedTable as KeyedTableComponent;
use CraftCms\Cms\Cp\Components\Lightswitch as LightswitchComponent;
use CraftCms\Cms\Cp\Components\MoneyInput as MoneyInputComponent;
use CraftCms\Cms\Cp\Components\NumberInput as NumberInputComponent;
use CraftCms\Cms\Cp\Components\ObjectSelect as ObjectSelectComponent;
use CraftCms\Cms\Cp\Components\OptionRows as OptionRowsComponent;
use CraftCms\Cms\Cp\Components\Select as SelectComponent;
use CraftCms\Cms\Cp\Components\Tab as TabComponent;
use CraftCms\Cms\Cp\Components\Tabs as TabsComponent;
use CraftCms\Cms\Cp\Components\TextInput as TextInputComponent;
use CraftCms\Cms\Cp\Components\TimeInput as TimeInputComponent;
use CraftCms\Cms\Cp\Components\ViewComponent;
use CraftCms\Cms\Cp\FormDefinitions\Contracts\ProjectableFormElement;
use CraftCms\Cms\Cp\FormDefinitions\Data\FormElementData;
use CraftCms\Cms\Cp\FormDefinitions\Data\PluginData;
use CraftCms\Cms\Cp\FormDefinitions\Elements\FormElement;
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
            GroupComponent::formElementType() => [
                'class' => GroupComponent::class,
                'container' => GroupComponent::isFormElementContainer(),
                'plugin' => null,
            ],
            TabsComponent::formElementType() => [
                'class' => TabsComponent::class,
                'container' => TabsComponent::isFormElementContainer(),
                'plugin' => null,
            ],
            TabComponent::formElementType() => [
                'class' => TabComponent::class,
                'container' => TabComponent::isFormElementContainer(),
                'plugin' => null,
            ],
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
            FieldLayoutComponent::formElementType() => [
                'class' => FieldLayoutComponent::class,
                'container' => FieldLayoutComponent::isFormElementContainer(),
                'plugin' => null,
            ],
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

    /** @param class-string<FormElement|ProjectableFormElement> ...$classes */
    public function register(string ...$classes): void
    {
        $this->registerBatch(null, false, ...$classes);
    }

    /**
     * @internal Plugins should call Plugin::registerFormElementTypes().
     *
     * @param  class-string<ViewComponent&ProjectableFormElement>  ...$classes
     */
    public function registerForPlugin(PluginInterface $plugin, string ...$classes): void
    {
        if ($plugin->name === null || $plugin->packageName === null) {
            throw new InvalidArgumentException("Plugin {$plugin->handle} must define its name and Composer package before registering Form Elements.");
        }

        $this->registerBatch(
            new PluginData(
                handle: $plugin->handle,
                name: $plugin->name,
                packageName: $plugin->packageName,
            ),
            true,
            ...$classes,
        );
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

    public function project(ProjectableFormElement $component): FormElementData
    {
        $type = $component::formElementType();
        $class = $component::class;
        $registration = $this->registrations[$type] ?? null;

        if ($registration === null) {
            throw new InvalidArgumentException(sprintf(
                '%s declares unknown or unregistered Form Element Type "%s".',
                $class,
                $type,
            ));
        }

        if ($registration['class'] !== $class) {
            throw new InvalidArgumentException(sprintf(
                'Form Element Type "%s" is registered by %s%s; %s cannot project it.',
                $type,
                $registration['class'] ?? 'Craft',
                $this->ownerLabel($type, $registration['plugin']),
                $class,
            ));
        }

        $data = $component->toFormElementData();

        if ($data->type !== $type) {
            throw new InvalidArgumentException(sprintf(
                '%s declares Form Element Type "%s" but projected "%s".',
                $class,
                $type,
                $data->type,
            ));
        }

        return $data;
    }

    /** @param class-string<FormElement|ProjectableFormElement> ...$classes */
    private function registerBatch(?PluginData $plugin, bool $projectableOnly, string ...$classes): void
    {
        $registrations = $this->registrations;

        foreach ($classes as $class) {
            $projectable = is_subclass_of($class, ViewComponent::class)
                && is_subclass_of($class, ProjectableFormElement::class);

            if ($projectableOnly && ! $projectable) {
                throw new InvalidArgumentException(sprintf(
                    '%s must extend %s and implement %s.',
                    $class,
                    ViewComponent::class,
                    ProjectableFormElement::class,
                ));
            }

            if (! $projectable && ! is_subclass_of($class, FormElement::class)) {
                throw new InvalidArgumentException(sprintf(
                    '%s must extend %s, or extend %s and implement %s.',
                    $class,
                    FormElement::class,
                    ViewComponent::class,
                    ProjectableFormElement::class,
                ));
            }

            $type = $projectable ? $class::formElementType() : $class::type();

            if (preg_match('/^[a-z][a-z0-9-]*:[a-z][a-z0-9-]*$/D', $type) !== 1) {
                throw new InvalidArgumentException("Form Element Type \"{$type}\" must be a lowercase namespaced identifier.");
            }

            if (str_starts_with($type, 'craft:')) {
                throw new InvalidArgumentException('The "craft" Form Element namespace is reserved.');
            }

            $registration = [
                'class' => $class,
                'container' => $projectable
                    ? $class::isFormElementContainer()
                    : $class::isContainer(),
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
                    $this->ownerLabel($type, $existing['plugin']),
                    $class,
                    $this->ownerLabel($type, $plugin),
                ));
            }

            $registrations[$type] = $registration;
        }

        $this->registrations = $registrations;
    }

    private function ownerLabel(string $type, ?PluginData $plugin): string
    {
        return match (true) {
            $plugin !== null => " for plugin {$plugin->handle}",
            str_starts_with($type, 'craft:') => ' for Craft',
            default => ' for the application',
        };
    }
}
