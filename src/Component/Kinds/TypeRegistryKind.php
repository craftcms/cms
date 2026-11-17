<?php

declare(strict_types=1);

namespace CraftCms\Cms\Component\Kinds;

use CraftCms\Cms\Component\ComponentHelper;
use CraftCms\Cms\Component\Contracts\ComponentInterface;
use CraftCms\Cms\Component\Contracts\ConfigurableComponentInterface;
use CraftCms\Cms\Component\TypeRegistry;
use CraftCms\Cms\Ui\SettingsSchema;

/**
 * A component kind whose types are registered with a {@see TypeRegistry} and configured with settings UIs.
 *
 * Subclasses only need to name the kind and return its registry.
 *
 * @since 6.0.0
 */
abstract readonly class TypeRegistryKind implements ComponentKind
{
    public function __construct(protected SettingsSchema $settingsSchema) {}

    /** @return TypeRegistry<covariant ComponentInterface> */
    abstract protected function registry(): TypeRegistry;

    public function scope(): ?string
    {
        return null;
    }

    public function types(?string $scope): array
    {
        /** @var list<class-string<ComponentInterface>> $classes */
        $classes = $this->registry()->types()->all();

        return collect($classes)
            ->filter(static fn (string $class): bool => $class::isSelectable())
            ->map(static fn (string $class): array => ['type' => $class, 'name' => $class::displayName()])
            ->sortBy('name')
            ->values()
            ->all();
    }

    public function describe(string $type, ?string $scope): array
    {
        return ['settingsSchema' => $this->settingsSchema->fromComponent($this->createComponent($type))];
    }

    /** Creates a component of the given type with its default settings. */
    protected function createComponent(string $type): ConfigurableComponentInterface
    {
        $component = ComponentHelper::createComponent(['type' => $type, 'settings' => []]);
        assert($component instanceof ConfigurableComponentInterface);

        return $component;
    }
}
