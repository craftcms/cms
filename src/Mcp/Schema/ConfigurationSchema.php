<?php

declare(strict_types=1);

namespace CraftCms\Cms\Mcp\Schema;

use CraftCms\Cms\Mcp\Enums\ConfigurationOperation;
use CraftCms\Cms\Mcp\Enums\ConfigurationType;
use Mcp\Capability\Discovery\SchemaGenerator;
use Mcp\Schema\Tool;
use ReflectionMethod;

/**
 * Builds the configuration interface from the existing typed operations.
 *
 * @since 6.0.0
 */
readonly class ConfigurationSchema
{
    public function __construct(private SchemaGenerator $generator) {}

    /** @return array<string, mixed> */
    public function for(ConfigurationType $type, ConfigurationOperation $operation): array
    {
        $method = new ReflectionMethod($type->capability(), $operation->value);
        $original = $this->generator->generate($method);
        $parameters = (array) $original['properties'];
        $properties = ['type' => ['const' => $type->value]];
        $required = ['type'];

        if ($operation->needsIdentifier()) {
            $identifier = [];

            foreach ($this->lookupParameters($type, $operation) as $name) {
                $property = $parameters[$name];
                $types = array_values(array_diff((array) $property['type'], ['null']));
                $property['type'] = count($types) === 1 ? $types[0] : $types;
                unset($property['default']);
                $identifier[$name === 'currentHandle' ? 'handle' : $name] = $property;
                unset($parameters[$name]);
            }

            $properties['identifier'] = [
                ...$this->object($identifier),
                'minProperties' => 1,
                'maxProperties' => 1,
            ];
            $required[] = 'identifier';
        }

        if (in_array($operation, [ConfigurationOperation::Create, ConfigurationOperation::Update], true)) {
            $properties['attributes'] = $type === ConfigurationType::Sites
                ? $parameters['attributes']
                : $this->object($parameters, array_values(array_intersect($original['required'] ?? [], array_keys($parameters))));
            $required[] = 'attributes';
        }

        if ($operation === ConfigurationOperation::Delete) {
            $properties['options'] = $this->object($parameters);
        }

        return new Tool(
            name: 'configuration.'.$operation->value,
            title: null,
            inputSchema: $this->object($properties, $required),
            description: null,
            annotations: null,
        )->inputSchema;
    }

    /** @return list<string> */
    private function lookupParameters(ConfigurationType $type, ConfigurationOperation $operation): array
    {
        if ($type === ConfigurationType::Routes) {
            return ['uid', 'uri'];
        }

        if ($type === ConfigurationType::SiteGroups) {
            return ['id', 'uid'];
        }

        return ['id', 'uid', $operation === ConfigurationOperation::Update && $type !== ConfigurationType::Sites ? 'currentHandle' : 'handle'];
    }

    /**
     * @param  array<string, mixed>  $identifier
     * @param  array<string, mixed>  $attributes
     * @param  array<string, mixed>  $options
     * @return array<string, mixed>
     */
    public function arguments(ConfigurationType $type, ConfigurationOperation $operation, array $identifier, array $attributes, array $options): array
    {
        if ($operation === ConfigurationOperation::Update && array_key_exists('handle', $identifier) && $type !== ConfigurationType::Sites) {
            $identifier['currentHandle'] = $identifier['handle'];
            unset($identifier['handle']);
        }

        if (in_array($operation, [ConfigurationOperation::Create, ConfigurationOperation::Update], true)) {
            return $type === ConfigurationType::Sites
                ? [...$identifier, 'attributes' => $attributes]
                : [...$identifier, ...$attributes];
        }

        return [...$identifier, ...$options];
    }

    /**
     * @param  array<string, mixed>  $properties
     * @param  list<string>  $required
     * @return array<string, mixed>
     */
    private function object(array $properties, array $required = []): array
    {
        return [
            'type' => 'object',
            'properties' => $properties,
            'required' => $required,
            'additionalProperties' => false,
        ];
    }
}
