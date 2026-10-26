<?php

declare(strict_types=1);

namespace CraftCms\Cms\Gql\Types\Input;

use CraftCms\Cms\Field\Field;
use CraftCms\Cms\Field\Matrix as MatrixField;
use CraftCms\Cms\Gql\GqlEntityRegistry;
use CraftCms\Cms\Gql\Types\QueryArgument;
use CraftCms\Cms\Support\Arr;
use GraphQL\Error\UserError;
use GraphQL\Type\Definition\InputObjectType;
use GraphQL\Type\Definition\Type;

/**
 * @since 6.0.0
 */
class Matrix extends InputObjectType
{
    public static function getType(MatrixField $context): InputObjectType
    {
        $typeName = $context->handle.'_MatrixInput';

        return GqlEntityRegistry::getOrCreate($typeName, fn () => new InputObjectType([
            'name' => $typeName,
            'description' => sprintf('Defines a block within the “%s” Matrix field’s data.', $context->name),
            'fields' => function () use ($context) {
                // All the different field entry types now get wrapped in a container input that accepts one of them.
                $entryTypeContainerName = $context->handle.'_MatrixEntryContainerInput';
                $entryContainerInputType = GqlEntityRegistry::createEntity($entryTypeContainerName, new InputObjectType([
                    'name' => $entryTypeContainerName,
                    'description' => sprintf('An entry within the “%s” Matrix field, keyed by its entry type handle.', $context->name),
                    'isOneOf' => true,
                    'fields' => function () use ($context) {
                        $entryInputTypes = [];
                        foreach ($context->getEntryTypes() as $entryType) {
                            $entryTypeGqlName = $context->handle.'_'.$entryType->handle.'_MatrixEntryInput';
                            $entryInputTypes[$entryType->handle] = [
                                'name' => $entryType->handle,
                                'type' => GqlEntityRegistry::createEntity($entryTypeGqlName, new InputObjectType([
                                    'name' => $entryTypeGqlName,
                                    'fields' => function () use ($entryType) {
                                        $entryTypeFields = [
                                            'id' => [
                                                'name' => 'id',
                                                'type' => Type::id(),
                                            ],
                                        ];

                                        if ($entryType->hasTitleField) {
                                            $entryTypeFields['title'] = [
                                                'name' => 'title',
                                                'type' => Type::string(),
                                                'description' => 'The entry title',
                                            ];
                                        }

                                        // Get the field input types
                                        foreach ($entryType->getCustomFields() as $field) {
                                            /** @var Field $field */
                                            $entryTypeFields[$field->handle] = $field->getContentGqlMutationArgumentType();
                                        }

                                        return $entryTypeFields;
                                    },
                                ])),
                            ];
                        }

                        return $entryInputTypes;
                    },
                ]));

                return [
                    'sortOrder' => [
                        'name' => 'sortOrder',
                        'type' => Type::listOf(QueryArgument::getType()),
                        'description' => 'The entries the field should contain, in order: IDs of existing entries, and `new:1`, `new:2`, etc. for entries without an ID, numbered in the order they appear in `entries`. Entries it omits are removed. Required when adding entries. Without it, existing entries are kept in their order, and only the entries in `entries` change.',
                    ],
                    'entries' => [
                        'name' => 'entries',
                        'type' => Type::listOf($entryContainerInputType),
                        'description' => 'Entry data. Include `id` to update an existing entry; only the values given change.',
                    ],
                ];
            },
            'normalizeValue' => [self::class, 'normalizeValue'],
        ]));
    }

    public static function normalizeValue(mixed $value): mixed
    {
        $preparedEntries = [];
        $entryCounter = 1;

        if (! isset($value['entries']) && isset($value['blocks'])) {
            $value['entries'] = Arr::pull($value, 'blocks');
        }

        if (! empty($value['entries'])) {
            foreach ($value['entries'] as $entry) {
                if (! empty($entry)) {
                    $type = array_key_first($entry);
                    $entry = reset($entry);
                    $entryId = Arr::pull($entry, 'id') ?? sprintf('new:%s', $entryCounter++);
                    // Only given titles are set, so updating an entry’s fields keeps its title.
                    $title = array_key_exists('title', $entry) ? ['title' => Arr::pull($entry, 'title')] : [];

                    $preparedEntries[$entryId] = [
                        'type' => $type,
                        ...$title,
                        'fields' => $entry,
                    ];
                }
            }

            $value['entries'] = $preparedEntries;

            if ($entryCounter > 1 && ! isset($value['sortOrder'])) {
                throw new UserError('New entries must be listed in `sortOrder`, as `new:1`, `new:2`, etc. in the order they appear in `entries`, along with the IDs of any existing entries to keep.');
            }
        }

        return $value;
    }
}
