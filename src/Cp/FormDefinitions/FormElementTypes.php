<?php

declare(strict_types=1);

namespace CraftCms\Cms\Cp\FormDefinitions;

use CraftCms\Cms\Cp\FormDefinitions\Data\PluginData;
use CraftCms\Cms\Cp\FormDefinitions\Elements\FormElement;
use CraftCms\Cms\Cp\FormDefinitions\Elements\TextInput;
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
            TextInput::type() => ['class' => TextInput::class, 'container' => false, 'plugin' => null],
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
