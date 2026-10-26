<?php

declare(strict_types=1);

namespace CraftCms\Cms\Gql\Types;

use CraftCms\Cms\Gql\Contracts\SingularTypeInterface;
use CraftCms\Cms\Gql\GqlEntityRegistry;
use CraftCms\Cms\Release\Models\Release as ReleaseModel;
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
class Release extends ObjectType implements SingularTypeInterface
{
    public static function getName(): string
    {
        return 'Release';
    }

    public static function getType(): Type
    {
        return GqlEntityRegistry::getOrCreate(self::getName(), fn () => new self([
            'name' => self::getName(),
            'fields' => self::class.'::getFieldDefinitions',
            'description' => 'This is the type for releases.',
        ]));
    }

    /** @return array<string, FieldDefinitionConfig> */
    public static function getFieldDefinitions(): array
    {
        $fields = [
            'id' => [
                'name' => 'id',
                'type' => Type::nonNull(Type::int()),
                'description' => 'The ID of the release.',
            ],
            'uid' => [
                'name' => 'uid',
                'type' => Type::nonNull(Type::string()),
                'description' => 'The UUID of the release.',
            ],
            'name' => [
                'name' => 'name',
                'type' => Type::nonNull(Type::string()),
                'description' => 'The name of the release.',
            ],
            'description' => [
                'name' => 'description',
                'type' => Type::string(),
                'description' => 'The description of the release.',
            ],
            'status' => [
                'name' => 'status',
                'type' => Type::nonNull(Type::string()),
                'description' => 'The status of the release (`draft`, `scheduled`, `publishing`, `published`, or `failed`).',
            ],
            'targetDate' => [
                'name' => 'targetDate',
                'type' => DateTime::getType(),
                'description' => 'The date the release is targeted or scheduled to be published.',
            ],
            'publishedAt' => [
                'name' => 'publishedAt',
                'type' => DateTime::getType(),
                'description' => 'The date the release was published.',
            ],
            'dateCreated' => [
                'name' => 'dateCreated',
                'type' => DateTime::getType(),
                'description' => 'The date the release was created.',
            ],
            'dateUpdated' => [
                'name' => 'dateUpdated',
                'type' => DateTime::getType(),
                'description' => 'The date the release was last updated.',
            ],
        ];

        return Gql::prepareFieldDefinitions($fields, self::getName());
    }

    #[Override]
    protected function resolve(mixed $source, array $arguments, mixed $context, ResolveInfo $resolveInfo): mixed
    {
        /** @var ReleaseModel $source */
        return match ($resolveInfo->fieldName) {
            'status' => $source->status->value,
            default => parent::resolve($source, $arguments, $context, $resolveInfo),
        };
    }
}
