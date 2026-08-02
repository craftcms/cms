<?php

declare(strict_types=1);

namespace CraftCms\Cms\Cp\FormDefinitions;

use CraftCms\Cms\Cp\Components\ComponentRegistry;
use CraftCms\Cms\Cp\Components\ViewComponent;
use CraftCms\Cms\Cp\FormDefinitions\Contracts\FormElement;
use CraftCms\Cms\Cp\FormDefinitions\Data\FormElementData;
use CraftCms\Cms\Cp\FormDefinitions\Data\PluginData;
use CraftCms\Cms\Plugin\Contracts\PluginInterface;
use Illuminate\Container\Attributes\Singleton;
use InvalidArgumentException;

#[Singleton]
class FormElementTypes
{
    /**
     * @var array<string, array{
     *     class: class-string<ViewComponent&FormElement>,
     *     container: bool,
     *     plugin: PluginData|null,
     * }>
     */
    private array $registrations;

    /**
     * @var array<string, array{
     *     class: class-string<ViewComponent&FormElement>,
     *     container: bool,
     * }>
     */
    private array $nativeRegistrations = [];

    public function __construct(ComponentRegistry $components)
    {
        $this->registrations = [];

        foreach ($components->nativeComponents() as $class) {
            if (! is_subclass_of($class, FormElement::class)) {
                continue;
            }

            $type = $class::formElementType();

            if (isset($this->registrations[$type])) {
                throw new InvalidArgumentException(sprintf(
                    'Native Form Element Type "%s" is claimed by both %s and %s.',
                    $type,
                    $this->registrations[$type]['class'],
                    $class,
                ));
            }

            $this->nativeRegistrations[$type] = [
                'class' => $class,
                'container' => $class::isFormElementContainer(),
            ];
            $this->registrations[$type] = [
                ...$this->nativeRegistrations[$type],
                'plugin' => null,
            ];
        }
    }

    /** @param class-string<ViewComponent&FormElement> ...$classes */
    public function register(string ...$classes): void
    {
        $this->registerBatch(null, ...$classes);
    }

    /**
     * @internal Plugins should call Plugin::registerFormElementTypes().
     *
     * @param  class-string<ViewComponent&FormElement>  ...$classes
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
            ...$classes,
        );
    }

    /**
     * @internal
     *
     * @return array<string, array{
     *     class: class-string<ViewComponent&FormElement>,
     *     container: bool,
     * }>
     */
    public function nativeRegistrations(): array
    {
        return $this->nativeRegistrations;
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

    public function project(FormElement $component): FormElementData
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
                $registration['class'],
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

    /** @param class-string<ViewComponent&FormElement> ...$classes */
    private function registerBatch(?PluginData $plugin, string ...$classes): void
    {
        $registrations = $this->registrations;

        foreach ($classes as $class) {
            $validClass = is_subclass_of($class, ViewComponent::class)
                && is_subclass_of($class, FormElement::class);

            if (! $validClass) {
                throw new InvalidArgumentException(sprintf(
                    '%s must extend %s and implement %s.',
                    $class,
                    ViewComponent::class,
                    FormElement::class,
                ));
            }

            $type = $class::formElementType();

            if (preg_match('/^[a-z][a-z0-9-]*:[a-z][a-z0-9-]*$/D', $type) !== 1) {
                throw new InvalidArgumentException("Form Element Type \"{$type}\" must be a lowercase namespaced identifier.");
            }

            if (str_starts_with($type, 'craft:')) {
                throw new InvalidArgumentException('The "craft" Form Element namespace is reserved.');
            }

            $registration = [
                'class' => $class,
                'container' => $class::isFormElementContainer(),
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
                    $existing['class'],
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
