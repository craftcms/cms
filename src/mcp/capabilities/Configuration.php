<?php

declare(strict_types=1);

namespace craft\mcp\capabilities;

use craft\config\GeneralConfig;
use craft\helpers\ProjectConfig as ProjectConfigHelper;
use craft\mcp\attributes\RequiresAdmin;
use craft\mcp\attributes\RequiresAdminChanges;
use craft\mcp\CapabilityContainer;
use craft\mcp\enums\ConfigurationOperation;
use craft\mcp\enums\ConfigurationType;
use craft\mcp\McpActor;
use craft\mcp\schema\ConfigurationSchema;
use craft\services\ProjectConfig;
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
 * @since 5.12.0
 */
class Configuration
{
    private const IdentifierSchema = [
        'type' => 'object',
        'description' => 'Exactly one lookup key from configuration_schema.',
        'additionalProperties' => true,
    ];

    private const AttributesSchema = [
        'type' => 'object',
        'description' => 'Values accepted by configuration_schema for this type and operation.',
        'additionalProperties' => true,
    ];

    private const RevisionNote = 'Pass the revision you last read or wrote as expectedRevision. If the record has changed since, nothing is changed; read it again and redo your change. Without expectedRevision, newer changes can be overwritten.';

    public function __construct(
        private McpActor $actor,
        private GeneralConfig $config,
        private ConfigurationSchema $schemas,
        private SchemaValidator $validator,
        private CapabilityContainer $container,
        private ProjectConfig $projectConfig,
    ) {
    }

    /** @return array<string, mixed> */
    #[McpTool(
        name: 'configuration_schema',
        description: 'Lists configuration types and available operations, or returns their detailed input schemas and effects. Omit type to discover types; supply type and operation for one schema.',
        annotations: new ToolAnnotations(readOnlyHint: true),
    )]
    #[RequiresAdmin]
    public function schema(?ConfigurationType $type = null, ?ConfigurationOperation $operation = null): array
    {
        $this->authorize();
        $operations = array_values(array_filter(ConfigurationOperation::cases(), fn(ConfigurationOperation $operation): bool => !$operation->writes() || $this->config->allowAdminChanges));

        if ($type === null) {
            if ($operation !== null) {
                throw new ToolCallException('Provide type when selecting an operation.');
            }

            return ['types' => array_map(static fn(ConfigurationType $type): array => [
                'type' => $type->value,
                'operations' => array_map(static fn(ConfigurationOperation $operation): string => $operation->value, $operations),
            ], ConfigurationType::cases())];
        }

        if ($operation !== null) {
            $this->authorize($operation->writes());

            return $this->operationSchema($type, $operation);
        }

        return ['type' => $type->value, 'operations' => array_map(fn(ConfigurationOperation $operation): array => $this->operationSchema($type, $operation), $operations)];
    }

    /** @return array<string, mixed> */
    #[McpTool(
        name: 'configuration_list',
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
        name: 'configuration_get',
        description: 'Gets a configuration record by its supported identifier. Read configuration_schema for lookup keys.',
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
        name: 'configuration_create',
        description: 'Creates a configuration record. Read configuration_schema for accepted attributes, required values, and defaults.',
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
        name: 'configuration_update',
        description: 'Updates supplied configuration attributes, preserving omitted values. Read current values and configuration_schema first. Put lookup keys in identifier and new values in attributes.',
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
        #[Schema(definition: ConfigurationSchema::ExpectedRevision)]
        ?string $expectedRevision = null,
    ): array {
        return $this->execute($context, $type, ConfigurationOperation::Update, $identifier, $attributes, expectedRevision: $expectedRevision);
    }

    /**
     * @param  array<string, mixed>  $identifier
     * @param  array<string, mixed>  $options
     * @return array<string, mixed>
     */
    #[McpTool(
        name: 'configuration_delete',
        description: 'Deletes configuration using the selected type’s rules and content effects. Read configuration_schema for deletion effects and options. No configuration undo tool is provided.',
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
        #[Schema(definition: ConfigurationSchema::ExpectedRevision)]
        ?string $expectedRevision = null,
    ): array {
        return $this->execute($context, $type, ConfigurationOperation::Delete, $identifier, options: $options, expectedRevision: $expectedRevision);
    }

    /** @return array<string, mixed> */
    private function operationSchema(ConfigurationType $type, ConfigurationOperation $operation): array
    {
        return [
            'type' => $type->value,
            'operation' => $operation->value,
            'inputSchema' => $this->schemas->for($type, $operation),
            'notes' => [...$type->notes(), ...match ($operation) {
                ConfigurationOperation::Update => ['Only supplied attributes are updated. Read current values before replacing complete lists or layouts.', self::RevisionNote],
                ConfigurationOperation::Delete => [self::RevisionNote],
                default => [],
            }],
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
        ?string $expectedRevision = null,
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

        if ($expectedRevision !== null) {
            $values['expectedRevision'] = $expectedRevision;
        }

        $errors = $this->validator->validateAgainstJsonSchema($values, $this->schemas->for($type, $operation));

        if ($errors !== []) {
            $messages = array_map(static fn(array $error): string => "{$error['pointer']}: {$error['message']}", array_slice($errors, 0, 3));

            throw new ToolCallException("Invalid {$type->value} {$operation->value} input: " . implode('; ', $messages) . '. Call configuration_schema for accepted values.');
        }

        if ($expectedRevision !== null) {
            // Hold the project config lock from this check until the change is saved, so nothing can change the record in between.
            $this->projectConfig->acquireLock();
            $current = $this->call($context, $type, ConfigurationOperation::Get, $this->schemas->arguments($type, ConfigurationOperation::Get, $identifier, [], []));

            if ($this->revision($type, $current[$type->itemKey()]) !== $expectedRevision) {
                throw new ToolCallException("The {$type->value} record has changed since revision $expectedRevision was read, so nothing was changed. Read it again with configuration_get, redo your change on the current values, and pass the new revision.");
            }
        }

        $result = $this->call($context, $type, $operation, $this->schemas->arguments($type, $operation, $identifier, $attributes, $options));

        return match ($operation) {
            ConfigurationOperation::List => ['type' => $type->value, 'count' => $result['count'], 'items' => array_map(
                fn(array $item): array => [...$item, 'revision' => $this->revision($type, $item)],
                $result[$type->itemsKey()],
            )],
            ConfigurationOperation::Delete => ['type' => $type->value, ...$result],
            default => ['type' => $type->value, 'item' => $result[$type->itemKey()], 'revision' => $this->revision($type, $result[$type->itemKey()])],
        };
    }

    /**
     * @param array<string, mixed> $arguments
     * @return array<string, mixed>
     */
    private function call(RequestContext $context, ConfigurationType $type, ConfigurationOperation $operation, array $arguments): array
    {
        $request = new CallToolRequest($type->value . '.' . $operation->value, $arguments);
        $result = (new ReferenceHandler($this->container))->handle(
            new ElementReference([$type->capability(), $operation->value]),
            [...$arguments, '_session' => $context->getSession(), '_request' => $request],
        );
        assert(is_array($result));

        return $result;
    }

    /**
     * An opaque revision of a record: a hash of its complete project config, including nested settings and layouts.
     *
     * @param array<string, mixed> $item
     */
    private function revision(ConfigurationType $type, array $item): string
    {
        $path = $type->configPath($item);

        return substr(hash('sha256', ProjectConfigHelper::encodeValueAsString([$path => $this->projectConfig->get($path)])), 0, 32);
    }

    private function authorize(bool $write = false): void
    {
        if (!$this->actor->user()->admin) {
            throw new ToolCallException('Admin access is required for configuration.');
        }

        if ($write && !$this->config->allowAdminChanges) {
            throw new ToolCallException('Admin changes are disabled.');
        }
    }
}
