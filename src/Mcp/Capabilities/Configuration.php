<?php

declare(strict_types=1);

namespace CraftCms\Cms\Mcp\Capabilities;

use CraftCms\Cms\Config\GeneralConfig;
use CraftCms\Cms\Mcp\Attributes\RequiresAdmin;
use CraftCms\Cms\Mcp\Attributes\RequiresAdminChanges;
use CraftCms\Cms\Mcp\CapabilityContainer;
use CraftCms\Cms\Mcp\Enums\ConfigurationOperation;
use CraftCms\Cms\Mcp\Enums\ConfigurationType;
use CraftCms\Cms\Mcp\McpActor;
use CraftCms\Cms\Mcp\Schema\ConfigurationSchema;
use Mcp\Capability\Attribute\McpTool;
use Mcp\Capability\Attribute\Schema;
use Mcp\Capability\Discovery\SchemaValidator;
use Mcp\Capability\Registry\ElementReference;
use Mcp\Capability\Registry\ReferenceHandler;
use Mcp\Exception\ToolCallException;
use Mcp\Schema\Request\CallToolRequest;
use Mcp\Schema\ToolAnnotations;
use Mcp\Server\RequestContext;

/**
 * @since 6.0.0
 */
readonly class Configuration
{
    private const array IdentifierSchema = [
        'type' => 'object',
        'description' => 'Exactly one lookup key from configuration.schema.',
        'additionalProperties' => true,
    ];

    private const array AttributesSchema = [
        'type' => 'object',
        'description' => 'Values accepted by configuration.schema for this type and operation.',
        'additionalProperties' => true,
    ];

    public function __construct(
        private McpActor $actor,
        private GeneralConfig $config,
        private ConfigurationSchema $schemas,
        private SchemaValidator $validator,
        private CapabilityContainer $container,
    ) {}

    /** @return array<string, mixed> */
    #[McpTool(
        name: 'configuration.schema',
        description: 'Lists configuration types and available operations, or returns their detailed input schemas and effects. Omit type to discover types; supply type and operation for one schema.',
        annotations: new ToolAnnotations(readOnlyHint: true),
    )]
    #[RequiresAdmin]
    public function schema(?ConfigurationType $type = null, ?ConfigurationOperation $operation = null): array
    {
        $this->authorize();
        $operations = array_values(array_filter(ConfigurationOperation::cases(), fn (ConfigurationOperation $operation): bool => ! $operation->writes() || $this->config->allowAdminChanges));

        if ($type === null) {
            if ($operation !== null) {
                throw new ToolCallException('Provide type when selecting an operation.');
            }

            return ['types' => array_map(static fn (ConfigurationType $type): array => [
                'type' => $type->value,
                'operations' => array_map(static fn (ConfigurationOperation $operation): string => $operation->value, $operations),
            ], ConfigurationType::cases())];
        }

        if ($operation !== null) {
            $this->authorize($operation->writes());

            return $this->operationSchema($type, $operation);
        }

        return ['type' => $type->value, 'operations' => array_map(fn (ConfigurationOperation $operation): array => $this->operationSchema($type, $operation), $operations)];
    }

    /** @return array<string, mixed> */
    #[McpTool(
        name: 'configuration.list',
        description: 'Lists configuration records of one type.',
        annotations: new ToolAnnotations(readOnlyHint: true),
    )]
    #[RequiresAdmin]
    public function list(RequestContext $context, ConfigurationType $type): array
    {
        return $this->execute($context, $type, ConfigurationOperation::List);
    }

    /**
     * @param  array<string, mixed>  $identifier
     * @return array<string, mixed>
     */
    #[McpTool(
        name: 'configuration.get',
        description: 'Gets a configuration record by its supported identifier. Read configuration.schema for lookup keys.',
        annotations: new ToolAnnotations(readOnlyHint: true),
    )]
    #[RequiresAdmin]
    public function get(
        RequestContext $context,
        ConfigurationType $type,
        #[Schema(definition: self::IdentifierSchema)]
        array $identifier,
    ): array {
        return $this->execute($context, $type, ConfigurationOperation::Get, $identifier);
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    #[McpTool(
        name: 'configuration.create',
        description: 'Creates a configuration record. Read configuration.schema for accepted attributes, required values, and defaults.',
    )]
    #[RequiresAdminChanges]
    public function create(
        RequestContext $context,
        ConfigurationType $type,
        #[Schema(definition: self::AttributesSchema)]
        array $attributes,
    ): array {
        return $this->execute($context, $type, ConfigurationOperation::Create, attributes: $attributes);
    }

    /**
     * @param  array<string, mixed>  $identifier
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    #[McpTool(
        name: 'configuration.update',
        description: 'Updates supplied configuration attributes, preserving omitted values. Read current values and configuration.schema first. Put lookup keys in identifier and new values in attributes.',
        annotations: new ToolAnnotations(destructiveHint: true),
    )]
    #[RequiresAdminChanges]
    public function update(
        RequestContext $context,
        ConfigurationType $type,
        #[Schema(definition: self::IdentifierSchema)]
        array $identifier,
        #[Schema(definition: self::AttributesSchema)]
        array $attributes,
    ): array {
        return $this->execute($context, $type, ConfigurationOperation::Update, $identifier, $attributes);
    }

    /**
     * @param  array<string, mixed>  $identifier
     * @param  array<string, mixed>  $options
     * @return array<string, mixed>
     */
    #[McpTool(
        name: 'configuration.delete',
        description: 'Deletes configuration using the selected type’s rules and content effects. Read configuration.schema for deletion effects and options. No configuration undo tool is provided.',
        annotations: new ToolAnnotations(destructiveHint: true),
    )]
    #[RequiresAdminChanges]
    public function delete(
        RequestContext $context,
        ConfigurationType $type,
        #[Schema(definition: self::IdentifierSchema)]
        array $identifier,
        #[Schema(type: 'object', additionalProperties: true)]
        array $options = [],
    ): array {
        return $this->execute($context, $type, ConfigurationOperation::Delete, $identifier, options: $options);
    }

    /** @return array<string, mixed> */
    private function operationSchema(ConfigurationType $type, ConfigurationOperation $operation): array
    {
        return [
            'type' => $type->value,
            'operation' => $operation->value,
            'inputSchema' => $this->schemas->for($type, $operation),
            'notes' => [...$type->notes(), ...($operation === ConfigurationOperation::Update ? ['Only supplied attributes are updated. Read current values before replacing complete lists or layouts.'] : [])],
            ...($operation === ConfigurationOperation::Delete ? ['effects' => $type->deletionEffects()] : []),
        ];
    }

    /**
     * @param  array<string, mixed>  $identifier
     * @param  array<string, mixed>  $attributes
     * @param  array<string, mixed>  $options
     * @return array<string, mixed>
     */
    private function execute(
        RequestContext $context,
        ConfigurationType $type,
        ConfigurationOperation $operation,
        array $identifier = [],
        array $attributes = [],
        array $options = [],
    ): array {
        $this->authorize($operation->writes());
        $values = ['type' => $type->value];

        if ($operation->needsIdentifier()) {
            $values['identifier'] = (object) $identifier;
        }

        if (in_array($operation, [ConfigurationOperation::Create, ConfigurationOperation::Update], true)) {
            $values['attributes'] = (object) $attributes;
        }

        if ($operation === ConfigurationOperation::Delete) {
            $values['options'] = (object) $options;
        }

        $errors = $this->validator->validateAgainstJsonSchema($values, $this->schemas->for($type, $operation));

        if ($errors !== []) {
            $messages = array_map(static fn (array $error): string => "{$error['pointer']}: {$error['message']}", array_slice($errors, 0, 3));

            throw new ToolCallException("Invalid {$type->value} {$operation->value} input: ".implode('; ', $messages).'. Call configuration.schema for accepted values.');
        }

        $arguments = $this->schemas->arguments($type, $operation, $identifier, $attributes, $options);
        $request = new CallToolRequest($type->value.'.'.$operation->value, $arguments);
        $result = new ReferenceHandler($this->container)->handle(
            new ElementReference([$type->capability(), $operation->value]),
            [...$arguments, '_session' => $context->getSession(), '_request' => $request],
        );
        assert(is_array($result));

        return match ($operation) {
            ConfigurationOperation::List => ['type' => $type->value, 'count' => $result['count'], 'items' => $result[$type->itemsKey()]],
            ConfigurationOperation::Delete => ['type' => $type->value, ...$result],
            default => ['type' => $type->value, 'item' => $result[$type->itemKey()]],
        };
    }

    private function authorize(bool $write = false): void
    {
        if (! $this->actor->user()->isAdmin()) {
            throw new ToolCallException('Admin access is required for configuration.');
        }

        if ($write && ! $this->config->allowAdminChanges) {
            throw new ToolCallException('Admin changes are disabled.');
        }
    }
}
