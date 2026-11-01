<?php

declare(strict_types=1);

namespace CraftCms\Cms\Gql\Interfaces\Elements;

use CraftCms\Cms\Entry\Elements\Entry as EntryElement;
use CraftCms\Cms\Gql\Arguments\Elements\Entry as EntryArguments;
use CraftCms\Cms\Gql\Gql as GqlService;
use CraftCms\Cms\Gql\GqlEntityRegistry;
use CraftCms\Cms\Gql\GqlHelper;
use CraftCms\Cms\Gql\Interfaces\Elements\Entry as EntryInterface;
use CraftCms\Cms\Gql\Interfaces\Structure;
use CraftCms\Cms\Gql\Types\EntryType as EntryTypeGqlType;
use CraftCms\Cms\Gql\Types\Generators\EntryType;
use CraftCms\Cms\Gql\Types\Section as SectionGqlType;
use CraftCms\Cms\Section\Enums\SectionType;
use CraftCms\Cms\Support\Facades\Gql;
use GraphQL\Type\Definition\Argument;
use GraphQL\Type\Definition\FieldDefinition;
use GraphQL\Type\Definition\InterfaceType;
use GraphQL\Type\Definition\Type;
use Override;

/**
 * @phpstan-import-type ArgumentConfig from Argument
 * @phpstan-import-type FieldDefinitionConfig from FieldDefinition
 *
 * @since 6.0.0
 */
class Entry extends Structure
{
    #[Override]
    public static function getTypeGenerator(): string
    {
        return EntryType::class;
    }

    #[Override]
    public static function getType(): Type
    {
        if ($type = GqlEntityRegistry::getEntity(self::getName())) {
            return $type;
        }

        $type = GqlEntityRegistry::createEntity(self::getName(), new InterfaceType([
            'name' => static::getName(),
            'fields' => self::class.'::getFieldDefinitions',
            'description' => 'This is the interface implemented by all entries.',
            'resolveType' => self::class.'::resolveElementTypeName',
        ]));

        EntryType::generateTypes();

        return $type;
    }

    #[Override]
    public static function getName(): string
    {
        return 'EntryInterface';
    }

    /** @return array<string, FieldDefinitionConfig> */
    #[Override]
    public static function getFieldDefinitions(): array
    {
        $entryArguments = EntryArguments::getArguments();
        $allFieldArguments = EntryArguments::getContentArguments();
        $sectionFieldArguments = [...$entryArguments];
        $structureSectionFieldArguments = [...$entryArguments];

        foreach (GqlHelper::getSchemaContainedSections() as $section) {
            foreach ($section->getEntryTypes() as $entryType) {
                $entryTypeArguments = Gql::getFieldLayoutArguments($entryType->getFieldLayout());
                $sectionFieldArguments += $entryTypeArguments;
                if ($section->type === SectionType::Structure) {
                    $structureSectionFieldArguments += $entryTypeArguments;
                }
            }
        }

        return Gql::prepareFieldDefinitions(array_merge(parent::getFieldDefinitions(), static::getDraftFieldDefinitions(), self::getConditionalFields($sectionFieldArguments), [
            ...static::propertyFields(EntryElement::class, ['canonicalId', 'canonicalUid']),
            'sourceId' => [
                'name' => 'sourceId',
                'type' => Type::int(),
                'description' => 'Returns the entry’s canonical ID.',
                'deprecationReason' => 'this field has been deprecated since Craft 3.7.7. Use `canonicalId` instead.',
            ],
            'sourceUid' => [
                'name' => 'sourceUid',
                'type' => Type::string(),
                'description' => 'Returns the entry’s canonical UUID.',
                'deprecationReason' => 'this field has been deprecated since Craft 3.7.7. Use `canonicalUid` instead.',
            ],
            ...static::propertyFields(EntryElement::class, ['sectionId']),
            'sectionHandle' => [
                'name' => 'sectionHandle',
                'type' => Type::string(),
                'description' => 'The handle of the section that contains the entry.',
                'complexity' => GqlHelper::singleQueryComplexity(),
            ],
            ...static::propertyFields(EntryElement::class, ['fieldId']),
            'fieldHandle' => [
                'name' => 'fieldHandle',
                'type' => Type::string(),
                'description' => 'The handle of the field that contains the entry.',
                'complexity' => GqlHelper::singleQueryComplexity(),
            ],
            ...static::propertyFields(EntryElement::class, ['ownerId', 'sortOrder', 'typeId']),
            'typeHandle' => [
                'name' => 'typeHandle',
                'type' => Type::nonNull(Type::string()),
                'description' => 'The handle of the entry type that contains the entry.',
                'complexity' => GqlHelper::singleQueryComplexity(),
            ],
            ...static::propertyFields(EntryElement::class, ['postDate', 'expiryDate']),
            'children' => [
                'name' => 'children',
                'args' => $structureSectionFieldArguments,
                'type' => Type::nonNull(Type::listOf(Type::nonNull(static::getType()))),
                'description' => 'The entry’s children, if the section is a structure. Accepts the same arguments as the `entries` query.',
                'complexity' => GqlHelper::relatedArgumentComplexity(GqlService::GRAPHQL_COMPLEXITY_EAGER_LOAD),
            ],
            'descendants' => [
                'name' => 'descendants',
                'args' => $structureSectionFieldArguments,
                'type' => Type::nonNull(Type::listOf(Type::nonNull(static::getType()))),
                'description' => 'The entry’s descendants, if the section is a structure. Accepts the same arguments as the `entries` query.',
                'complexity' => GqlHelper::relatedArgumentComplexity(GqlService::GRAPHQL_COMPLEXITY_EAGER_LOAD),
            ],
            'parent' => [
                'name' => 'parent',
                'args' => $structureSectionFieldArguments,
                'type' => EntryInterface::getType(),
                'description' => 'The entry’s parent, if the section is a structure.',
                'complexity' => GqlHelper::relatedArgumentComplexity(GqlService::GRAPHQL_COMPLEXITY_EAGER_LOAD),
            ],
            'ancestors' => [
                'name' => 'ancestors',
                'args' => $structureSectionFieldArguments,
                'type' => Type::nonNull(Type::listOf(Type::nonNull(static::getType()))),
                'description' => 'The entry’s ancestors, if the section is a structure. Accepts the same arguments as the `entries` query.',
                'complexity' => GqlHelper::relatedArgumentComplexity(GqlService::GRAPHQL_COMPLEXITY_EAGER_LOAD),
            ],
            ...static::propertyFields(EntryElement::class, ['url']),
            'localized' => [
                'name' => 'localized',
                'args' => [
                    ...$entryArguments,
                    ...$allFieldArguments,
                ],
                'type' => Type::nonNull(Type::listOf(Type::nonNull(static::getType()))),
                'description' => 'The same element in other locales.',
                'complexity' => GqlHelper::eagerLoadComplexity(),
            ],
            'prev' => [
                'name' => 'prev',
                'type' => self::getType(),
                'args' => [
                    ...$entryArguments,
                    ...$allFieldArguments,
                ],
                'description' => 'Returns the previous element relative to this one, from a given set of criteria.',
                'complexity' => fn ($childrenComplexity, $args) => $childrenComplexity + GqlService::GRAPHQL_COMPLEXITY_NPLUS1 * (int) ! empty($args),
            ],
            'next' => [
                'name' => 'next',
                'type' => self::getType(),
                'args' => [
                    ...$entryArguments,
                    ...$allFieldArguments,
                ],
                'description' => 'Returns the next element relative to this one, from a given set of criteria.',
                'complexity' => fn ($childrenComplexity, $args) => $childrenComplexity + GqlService::GRAPHQL_COMPLEXITY_NPLUS1 * (int) ! empty($args),
            ],
            ...static::propertyFields(EntryElement::class, ['enabledForSite']),
        ]), self::getName());
    }

    /**
     * @param  array<string, ArgumentConfig>  $sectionFieldArguments
     * @return array<string, FieldDefinitionConfig>
     */
    private static function getConditionalFields(array $sectionFieldArguments): array
    {
        $fields = [];
        if (GqlHelper::canSchema('settings.sections')) {
            $fields['section'] = [
                'name' => 'section',
                'type' => SectionGqlType::getType(),
                'description' => 'The section that contains the entry.',
            ];
        }

        if (GqlHelper::canSchema('settings.entrytypes')) {
            $fields['type'] = [
                'name' => 'type',
                'type' => Type::nonNull(EntryTypeGqlType::getType()),
                'description' => 'The entry’s entry type.',
            ];
        }

        if (GqlHelper::canQueryAllUsers()) {
            $fields = array_merge($fields, [
                ...static::propertyFields(EntryElement::class, ['authorId']),
                'author' => [
                    'name' => 'author',
                    'type' => User::getType(),
                    'description' => 'The primary entry author.',
                    'complexity' => GqlHelper::eagerLoadComplexity(),
                ],
                ...static::propertyFields(EntryElement::class, ['authorIds']),
                'authors' => [
                    'name' => 'authors',
                    'type' => Type::listOf(User::getType()),
                    'description' => 'The entry authors.',
                    'complexity' => GqlHelper::eagerLoadComplexity(),
                ],
            ]);
        }

        if (GqlHelper::canQueryDrafts()) {
            $fields = array_merge($fields, array_filter([
                'draftCreator' => GqlHelper::canQueryAllUsers() ? [
                    'name' => 'draftCreator',
                    'type' => User::getType(),
                    'description' => 'The creator of a given draft.',
                    'complexity' => GqlHelper::relatedArgumentComplexity(GqlService::GRAPHQL_COMPLEXITY_EAGER_LOAD),
                ] : null,
                'drafts' => [
                    'name' => 'drafts',
                    'args' => $sectionFieldArguments,
                    'type' => Type::listOf(EntryInterface::getType()),
                    'description' => 'The drafts for the entry.',
                    'complexity' => GqlHelper::relatedArgumentComplexity(GqlService::GRAPHQL_COMPLEXITY_EAGER_LOAD),
                ],
            ]));
        }

        if (GqlHelper::canQueryRevisions()) {
            return array_merge($fields, array_filter([
                'revisionCreator' => GqlHelper::canQueryAllUsers() ? [
                    'name' => 'revisionCreator',
                    'type' => User::getType(),
                    'description' => 'The creator of a given revision.',
                    'complexity' => GqlHelper::relatedArgumentComplexity(GqlService::GRAPHQL_COMPLEXITY_EAGER_LOAD),
                ] : null,
                'currentRevision' => [
                    'name' => 'currentRevision',
                    'type' => EntryInterface::getType(),
                    'description' => 'The current revision for the entry.',
                    'complexity' => GqlHelper::relatedArgumentComplexity(GqlService::GRAPHQL_COMPLEXITY_EAGER_LOAD),
                ],
                'revisions' => [
                    'name' => 'revisions',
                    'args' => $sectionFieldArguments,
                    'type' => Type::listOf(EntryInterface::getType()),
                    'description' => 'The revisions for the entry.',
                    'complexity' => GqlHelper::relatedArgumentComplexity(GqlService::GRAPHQL_COMPLEXITY_EAGER_LOAD),
                ],
            ]));
        }

        return $fields;
    }
}
