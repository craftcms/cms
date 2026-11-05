<?php

namespace craft\mcp;

use Craft;
use craft\config\GeneralConfig;
use craft\mcp\contentmodel\CheckRegistry;
use craft\mcp\elements\ElementAdapterRegistry;
use Psr\Container\ContainerInterface;
use ReflectionNamedType;
use ReflectionObject;
use yii\di\Container;

/** Resolves MCP capabilities without rebinding Craft's application service container. */
class CapabilityContainer implements ContainerInterface
{
    private Container $container;

    public function __construct()
    {
        $this->container = new Container();

        foreach (array_keys(\Yii::$container->getDefinitions()) as $id) {
            $this->container->set($id, static fn() => \Yii::$container->get($id));
        }

        foreach ((new ReflectionObject(Craft::$app))->getMethods() as $method) {
            $type = $method->getReturnType();

            if ($method->isPublic() && $method->getNumberOfRequiredParameters() === 0 && $type instanceof ReflectionNamedType && str_starts_with($type->getName(), 'craft\\services\\')) {
                $getter = $method->getName();
                $this->container->set($type->getName(), static fn() => Craft::$app->$getter());
            }
        }

        $this->container->set(\Psr\Log\LoggerInterface::class, Logger::class);
        $this->container->set(GeneralConfig::class, static fn() => Craft::$app->getConfig()->getGeneral());
        $this->container->set(Settings::class, static fn() => Craft::$app->get('mcp')->getSettings());
        $this->container->set(CapabilityRegistry::class, static fn() => Craft::$app->get('mcp')->getCapabilityRegistry());
        $this->container->set(ElementAdapterRegistry::class, static fn() => Craft::$app->get('mcp')->getElementAdapterRegistry());
        $this->container->set(CheckRegistry::class, static fn() => Craft::$app->get('mcp')->getCheckRegistry());
        $this->container->set(CapabilityContainer::class, fn() => $this);
    }

    public function get(string $id): mixed
    {
        return $this->container->get($id);
    }

    public function has(string $id): bool
    {
        return class_exists($id) || $this->container->has($id);
    }
}
