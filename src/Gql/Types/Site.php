<?php

declare(strict_types=1);

namespace CraftCms\Cms\Gql\Types;

use CraftCms\Cms\Gql\Contracts\SingularTypeInterface;
use CraftCms\Cms\Gql\GqlEntityRegistry;
use CraftCms\Cms\Site\Data\Site as SiteData;
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
class Site extends ObjectType implements SingularTypeInterface
{
    public static function getName(): string
    {
        return 'Site';
    }

    public static function getType(): Type
    {
        return GqlEntityRegistry::getOrCreate(self::getName(), fn () => new self([
            'name' => self::getName(),
            'fields' => self::class.'::getFieldDefinitions',
            'description' => 'This is the type for sites.',
        ]));
    }

    /** @return array<string, FieldDefinitionConfig> */
    public static function getFieldDefinitions(): array
    {
        $fields = [
            'id' => [
                'name' => 'id',
                'type' => Type::nonNull(Type::int()),
                'description' => 'The ID of the site.',
            ],
            'uid' => [
                'name' => 'uid',
                'type' => Type::nonNull(Type::string()),
                'description' => 'The UUID of the site.',
            ],
            'name' => [
                'name' => 'name',
                'type' => Type::nonNull(Type::string()),
                'description' => 'The name of the site.',
            ],
            'handle' => [
                'name' => 'handle',
                'type' => Type::nonNull(Type::string()),
                'description' => 'The handle of the site.',
            ],
            'language' => [
                'name' => 'language',
                'type' => Type::nonNull(Type::string()),
                'description' => 'The language of the site.',
            ],
            'primary' => [
                'name' => 'primary',
                'type' => Type::nonNull(Type::boolean()),
                'description' => 'Whether this is the primary site.',
            ],
            'enabled' => [
                'name' => 'enabled',
                'type' => Type::nonNull(Type::boolean()),
                'description' => 'Whether the site is enabled.',
            ],
            'hasUrls' => [
                'name' => 'hasUrls',
                'type' => Type::nonNull(Type::boolean()),
                'description' => 'Whether the site has URLs.',
            ],
            'baseUrl' => [
                'name' => 'baseUrl',
                'type' => Type::string(),
                'description' => 'The base URL of the site.',
            ],
        ];

        return Gql::prepareFieldDefinitions($fields, self::getName());
    }

    #[Override]
    protected function resolve(mixed $source, array $arguments, mixed $context, ResolveInfo $resolveInfo): mixed
    {
        /** @var SiteData $source */
        return match ($resolveInfo->fieldName) {
            'enabled' => $source->getEnabled(),
            'baseUrl' => $source->getBaseUrl(),
            default => parent::resolve($source, $arguments, $context, $resolveInfo),
        };
    }
}
