<?php

declare(strict_types=1);

namespace CraftCms\Cms\Cp\FormDefinitions;

use CraftCms\Cms\Cp\FormDefinitions\Data\PluginData;
use CraftCms\Cms\Cp\FormDefinitions\Elements\CheckboxSelectInput;
use CraftCms\Cms\Cp\FormDefinitions\Elements\ColorPaletteInput;
use CraftCms\Cms\Cp\FormDefinitions\Elements\DateInput;
use CraftCms\Cms\Cp\FormDefinitions\Elements\ElementConditionInput;
use CraftCms\Cms\Cp\FormDefinitions\Elements\FormElement;
use CraftCms\Cms\Cp\FormDefinitions\Elements\Group;
use CraftCms\Cms\Cp\FormDefinitions\Elements\LightswitchInput;
use CraftCms\Cms\Cp\FormDefinitions\Elements\MoneyInput;
use CraftCms\Cms\Cp\FormDefinitions\Elements\NumberInput;
use CraftCms\Cms\Cp\FormDefinitions\Elements\OptionRows;
use CraftCms\Cms\Cp\FormDefinitions\Elements\SelectInput;
use CraftCms\Cms\Cp\FormDefinitions\Elements\Tab;
use CraftCms\Cms\Cp\FormDefinitions\Elements\Tabs;
use CraftCms\Cms\Cp\FormDefinitions\Elements\TextInput;
use CraftCms\Cms\Cp\FormDefinitions\Elements\TimeInput;
use CraftCms\Cms\Plugin\Contracts\PluginInterface;
use Illuminate\Container\Attributes\Singleton;
use InvalidArgumentException;

#[Singleton]
class FormElementTypes
{
    /** @var array<string, array{class: class-string<FormElement>|null, container: bool, plugin: PluginData|null}> */
    private array $registrations;

    public function __construct()
    {
        $this->registrations = [
            'craft:field' => ['class' => null, 'container' => true, 'plugin' => null],
            Group::type() => ['class' => Group::class, 'container' => true, 'plugin' => null],
            Tabs::type() => ['class' => Tabs::class, 'container' => true, 'plugin' => null],
            Tab::type() => ['class' => Tab::class, 'container' => true, 'plugin' => null],
            TextInput::type() => ['class' => TextInput::class, 'container' => false, 'plugin' => null],
            CheckboxSelectInput::type() => ['class' => CheckboxSelectInput::class, 'container' => false, 'plugin' => null],
            ElementConditionInput::type() => ['class' => ElementConditionInput::class, 'container' => false, 'plugin' => null],
            NumberInput::type() => ['class' => NumberInput::class, 'container' => false, 'plugin' => null],
            SelectInput::type() => ['class' => SelectInput::class, 'container' => false, 'plugin' => null],
            LightswitchInput::type() => ['class' => LightswitchInput::class, 'container' => false, 'plugin' => null],
            OptionRows::type() => ['class' => OptionRows::class, 'container' => false, 'plugin' => null],
            DateInput::type() => ['class' => DateInput::class, 'container' => false, 'plugin' => null],
            TimeInput::type() => ['class' => TimeInput::class, 'container' => false, 'plugin' => null],
            ColorPaletteInput::type() => ['class' => ColorPaletteInput::class, 'container' => false, 'plugin' => null],
            MoneyInput::type() => ['class' => MoneyInput::class, 'container' => false, 'plugin' => null],
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
