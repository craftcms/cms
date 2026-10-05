<?php

declare(strict_types=1);

namespace CraftCms\Cms\Mcp;

use CraftCms\Cms\Component\TypeRegistry;
use Illuminate\Container\Attributes\Singleton;
use InvalidArgumentException;
use LogicException;
use Mcp\Capability\Attribute\McpPrompt;
use Mcp\Capability\Attribute\McpResource;
use Mcp\Capability\Attribute\McpResourceTemplate;
use Mcp\Capability\Attribute\McpTool;
use Mcp\Capability\Discovery\DiscoveryState;
use Mcp\Capability\Discovery\DocBlockParser;
use Mcp\Capability\Discovery\ElementMetadataResolver;
use Mcp\Capability\Discovery\SchemaGenerator;
use Mcp\Capability\Registry;
use Mcp\Capability\Registry\Loader\ReflectedElementLoader;
use ReflectionClass;
use ReflectionMethod;

/**
 * Registers SDK-attributed capability classes without scanning plugin files.
 *
 * Plugins may register their capabilities from a service provider:
 *
 * ```php
 * public function boot(CapabilityRegistry $capabilities): void
 * {
 *     $capabilities->register(MyCapabilities::class);
 * }
 * ```
 *
 * @extends TypeRegistry<object>
 *
 * @since 6.0.0
 */
#[Singleton]
class CapabilityRegistry extends TypeRegistry
{
    public function definitions(): DiscoveryState
    {
        $classes = $this->types();

        if ($classes->isEmpty()) {
            return new DiscoveryState;
        }

        $elements = ['tools' => [], 'resources' => [], 'resourceTemplates' => [], 'prompts' => []];
        $attributes = [
            'tools' => McpTool::class,
            'resources' => McpResource::class,
            'resourceTemplates' => McpResourceTemplate::class,
            'prompts' => McpPrompt::class,
        ];
        $schemas = new SchemaGenerator(new DocBlockParser);

        foreach ($classes as $class) {
            $reflection = new ReflectionClass($class);

            foreach ($reflection->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
                if ($method->isStatic() || $method->isConstructor() || $method->isDestructor()) {
                    continue;
                }

                foreach ($attributes as $category => $attributeClass) {
                    $matched = $method->getAttributes($attributeClass);

                    if ($method->getName() === '__invoke') {
                        $matched = $reflection->getAttributes($attributeClass) ?: $matched;
                    }

                    $attribute = ($matched[0] ?? null)?->newInstance();

                    if ($attribute === null) {
                        continue;
                    }

                    $data = ['handler' => [$class, $method->getName()], ...get_object_vars($attribute)];

                    if ($attribute instanceof McpTool) {
                        $data['outputSchema'] ??= $schemas->generateOutputSchema($method);
                    }

                    $identity = match (true) {
                        $attribute instanceof McpResource => $attribute->uri,
                        $attribute instanceof McpResourceTemplate => $attribute->uriTemplate,
                        default => ElementMetadataResolver::resolveName($method, $attribute->name),
                    };

                    if (isset($elements[$category][$identity])) {
                        throw new LogicException("Duplicate MCP capability identity [$identity].");
                    }

                    $elements[$category][$identity] = $data;
                    break;
                }
            }
        }

        $registry = new Registry(loader: new ReflectedElementLoader(...array_map(array_values(...), $elements)));

        return new DiscoveryState(
            tools: array_map(fn ($tool) => $registry->getTool($tool->name), $registry->getTools()->references),
            resources: array_map(fn ($resource) => $registry->getResource($resource->uri, false), $registry->getResources()->references),
            resourceTemplates: array_map(fn ($template) => $registry->getResourceTemplate($template->uriTemplate), $registry->getResourceTemplates()->references),
            prompts: array_map(fn ($prompt) => $registry->getPrompt($prompt->name), $registry->getPrompts()->references),
        );
    }

    /** @param class-string $type */
    #[\Override]
    protected function validate(string $type): void
    {
        if (! class_exists($type) || ! new ReflectionClass($type)->isInstantiable()) {
            throw new InvalidArgumentException("MCP capability class [$type] must be instantiable.");
        }
    }
}
