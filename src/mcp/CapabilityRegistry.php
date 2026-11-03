<?php

declare(strict_types=1);

namespace craft\mcp;

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
 * Loads SDK-attributed classes registered through Mcp::EVENT_REGISTER_CAPABILITY_TYPES.
 *
 * @since 5.12.0
 */
class CapabilityRegistry
{
    private array $types = [];

    /** @param list<class-string> $types */
    public function __construct(array $types = [])
    {
        foreach ($types as $type) {
            $this->validate($type);
            $this->types[$type] = $type;
        }
    }

    public function types(): array
    {
        return array_values($this->types);
    }

    public function definitions(): DiscoveryState
    {
        $classes = $this->types;

        if ($classes === []) {
            return new DiscoveryState();
        }

        $elements = ['tools' => [], 'resources' => [], 'resourceTemplates' => [], 'prompts' => []];
        $attributes = [
            'tools' => McpTool::class,
            'resources' => McpResource::class,
            'resourceTemplates' => McpResourceTemplate::class,
            'prompts' => McpPrompt::class,
        ];
        $schemas = new SchemaGenerator(new DocBlockParser());

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
            tools: array_map(fn($tool) => $registry->getTool($tool->name), $registry->getTools()->references),
            resources: array_map(fn($resource) => $registry->getResource($resource->uri, false), $registry->getResources()->references),
            resourceTemplates: array_map(fn($template) => $registry->getResourceTemplate($template->uriTemplate), $registry->getResourceTemplates()->references),
            prompts: array_map(fn($prompt) => $registry->getPrompt($prompt->name), $registry->getPrompts()->references),
        );
    }

    /** @param class-string $type */
    protected function validate(string $type): void
    {
        if (!class_exists($type) || !(new ReflectionClass($type))->isInstantiable()) {
            throw new InvalidArgumentException("MCP capability class [$type] must be instantiable.");
        }
    }
}
