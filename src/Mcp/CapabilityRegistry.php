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
use Mcp\Capability\Registry\ResourceReference;
use Mcp\Schema\Prompt;
use Mcp\Schema\ResourceDefinition;
use Mcp\Schema\ResourceTemplate;
use Mcp\Schema\Tool;
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

        $registry = new Registry;
        $identities = [];
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

                    $handler = [$class, $method->getName()];

                    $identity = match (true) {
                        $attribute instanceof McpResource => $attribute->uri,
                        $attribute instanceof McpResourceTemplate => $attribute->uriTemplate,
                        default => ElementMetadataResolver::resolveName($method, $attribute->name),
                    };

                    if (isset($identities[$category][$identity])) {
                        throw new LogicException("Duplicate MCP capability identity [$identity].");
                    }

                    $identities[$category][$identity] = true;

                    if ($attribute instanceof McpTool) {
                        $registry->loadFrom(new ReflectedElementLoader(tools: [[
                            'handler' => $handler,
                            'name' => $attribute->name,
                            'title' => $attribute->title,
                            'description' => $attribute->description,
                            'annotations' => $attribute->annotations,
                            'icons' => $attribute->icons,
                            'meta' => $attribute->meta,
                            'outputSchema' => $attribute->outputSchema ?? $schemas->generateOutputSchema($method),
                        ]]));
                    } elseif ($attribute instanceof McpResource) {
                        $registry->loadFrom(new ReflectedElementLoader(resources: [[
                            'handler' => $handler,
                            'uri' => $attribute->uri,
                            'name' => $attribute->name,
                            'title' => $attribute->title,
                            'description' => $attribute->description,
                            'mimeType' => $attribute->mimeType,
                            'size' => $attribute->size,
                            'annotations' => $attribute->annotations,
                            'icons' => $attribute->icons,
                            'meta' => $attribute->meta,
                        ]]));
                    } elseif ($attribute instanceof McpResourceTemplate) {
                        $registry->loadFrom(new ReflectedElementLoader(resourceTemplates: [[
                            'handler' => $handler,
                            'uriTemplate' => $attribute->uriTemplate,
                            'name' => $attribute->name,
                            'title' => $attribute->title,
                            'description' => $attribute->description,
                            'mimeType' => $attribute->mimeType,
                            'annotations' => $attribute->annotations,
                            'meta' => $attribute->meta,
                        ]]));
                    } elseif ($attribute instanceof McpPrompt) {
                        $registry->loadFrom(new ReflectedElementLoader(prompts: [[
                            'handler' => $handler,
                            'name' => $attribute->name,
                            'title' => $attribute->title,
                            'description' => $attribute->description,
                            'icons' => $attribute->icons,
                            'meta' => $attribute->meta,
                        ]]));
                    }
                    break;
                }
            }
        }

        $tools = [];
        foreach ($registry->getTools()->references as $tool) {
            if ($tool instanceof Tool) {
                $tools[$tool->name] = $registry->getTool($tool->name);
            }
        }

        $resources = [];
        foreach ($registry->getResources()->references as $resource) {
            if ($resource instanceof ResourceDefinition) {
                $reference = $registry->getResource($resource->uri, false);
                if ($reference instanceof ResourceReference) {
                    $resources[$resource->uri] = $reference;
                }
            }
        }

        $templates = [];
        foreach ($registry->getResourceTemplates()->references as $template) {
            if ($template instanceof ResourceTemplate) {
                $templates[$template->uriTemplate] = $registry->getResourceTemplate($template->uriTemplate);
            }
        }

        $prompts = [];
        foreach ($registry->getPrompts()->references as $prompt) {
            if ($prompt instanceof Prompt) {
                $prompts[$prompt->name] = $registry->getPrompt($prompt->name);
            }
        }

        return new DiscoveryState(
            tools: $tools,
            resources: $resources,
            resourceTemplates: $templates,
            prompts: $prompts,
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
