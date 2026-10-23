<?php

declare(strict_types=1);

namespace CraftCms\Cms\Gql\Types;

use CraftCms\Cms\Gql\Contracts\SingularTypeInterface;
use CraftCms\Cms\Gql\GqlEntityRegistry;
use CraftCms\Cms\Gql\GqlHelper;
use CraftCms\Cms\Section\Data\Section as SectionData;
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
class Section extends ObjectType implements SingularTypeInterface
{
    public static function getName(): string
    {
        return 'Section';
    }

    public static function getType(): Type
    {
        return GqlEntityRegistry::getOrCreate(self::getName(), fn () => new self([
            'name' => self::getName(),
            'fields' => self::class.'::getFieldDefinitions',
            'description' => 'This is the type for sections.',
        ]));
    }

    /** @return array<string, FieldDefinitionConfig> */
    public static function getFieldDefinitions(): array
    {
        $fields = [
            'id' => [
                'name' => 'id',
                'type' => Type::nonNull(Type::int()),
                'description' => 'The ID of the section.',
            ],
            'uid' => [
                'name' => 'uid',
                'type' => Type::nonNull(Type::string()),
                'description' => 'The UUID of the section.',
            ],
            'name' => [
                'name' => 'name',
                'type' => Type::nonNull(Type::string()),
                'description' => 'The name of the section.',
            ],
            'handle' => [
                'name' => 'handle',
                'type' => Type::nonNull(Type::string()),
                'description' => 'The handle of the section.',
            ],
            'type' => [
                'name' => 'type',
                'type' => Type::nonNull(Type::string()),
                'description' => 'The type of the section (`single`, `channel`, or `structure`).',
            ],
            'maxLevels' => [
                'name' => 'maxLevels',
                'type' => Type::int(),
                'description' => 'The maximum number of levels the section’s structure can have, if it’s a structure.',
            ],
        ];

        if (GqlHelper::canSchema('settings.entrytypes')) {
            $fields['entryTypes'] = [
                'name' => 'entryTypes',
                'type' => Type::nonNull(Type::listOf(Type::nonNull(EntryType::getType()))),
                'description' => 'The section’s entry types.',
            ];
        }

        return Gql::prepareFieldDefinitions($fields, self::getName());
    }

    #[Override]
    protected function resolve(mixed $source, array $arguments, mixed $context, ResolveInfo $resolveInfo): mixed
    {
        /** @var SectionData $source */
        return match ($resolveInfo->fieldName) {
            'type' => $source->type?->value,
            'entryTypes' => $source->getEntryTypes(),
            default => parent::resolve($source, $arguments, $context, $resolveInfo),
        };
    }
}
