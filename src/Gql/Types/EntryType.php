<?php

declare(strict_types=1);

namespace CraftCms\Cms\Gql\Types;

use CraftCms\Cms\Entry\Data\EntryType as EntryTypeData;
use CraftCms\Cms\Gql\Contracts\SingularTypeInterface;
use CraftCms\Cms\Gql\GqlEntityRegistry;
use CraftCms\Cms\Support\Facades\Gql;
use GraphQL\Type\Definition\FieldDefinition;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;
use Override;

/**
 * @phpstan-import-type FieldDefinitionConfig from FieldDefinition
 *
 * @since 6.0.0
 */
class EntryType extends ObjectType implements SingularTypeInterface
{
    public static function getName(): string
    {
        return 'EntryType';
    }

    public static function getType(): Type
    {
        return GqlEntityRegistry::getOrCreate(self::getName(), fn () => new self([
            'name' => self::getName(),
            'fields' => self::class.'::getFieldDefinitions',
            'description' => 'This is the type for entry types.',
        ]));
    }

    /** @return array<string, FieldDefinitionConfig> */
    public static function getFieldDefinitions(): array
    {
        $fields = [
            'id' => [
                'name' => 'id',
                'type' => Type::nonNull(Type::int()),
                'description' => 'The ID of the entry type.',
            ],
            'uid' => [
                'name' => 'uid',
                'type' => Type::nonNull(Type::string()),
                'description' => 'The UUID of the entry type.',
            ],
            'name' => [
                'name' => 'name',
                'type' => Type::nonNull(Type::string()),
                'description' => 'The name of the entry type.',
            ],
            'handle' => [
                'name' => 'handle',
                'type' => Type::nonNull(Type::string()),
                'description' => 'The handle of the entry type.',
            ],
            'description' => [
                'name' => 'description',
                'type' => Type::string(),
                'description' => 'The description of the entry type.',
            ],
            'icon' => [
                'name' => 'icon',
                'type' => Type::string(),
                'description' => 'The icon of the entry type.',
            ],
            'color' => [
                'name' => 'color',
                'type' => Type::string(),
                'description' => 'The color of the entry type.',
            ],
            'hasTitleField' => [
                'name' => 'hasTitleField',
                'type' => Type::nonNull(Type::boolean()),
                'description' => 'Whether entries of this type have a Title field.',
            ],
        ];

        return Gql::prepareFieldDefinitions($fields, self::getName());
    }

    #[Override]
    protected function resolve(mixed $source, array $arguments, mixed $context, ResolveInfo $resolveInfo): mixed
    {
        /** @var EntryTypeData $source */
        return match ($resolveInfo->fieldName) {
            'color' => $source->color?->value,
            default => parent::resolve($source, $arguments, $context, $resolveInfo),
        };
    }
}
