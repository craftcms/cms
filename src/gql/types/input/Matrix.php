<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\gql\types\input;

use Craft;
use craft\base\Field;
use craft\fields\Matrix as MatrixField;
use craft\gql\GqlEntityRegistry;
use craft\gql\types\QueryArgument;
use craft\helpers\ArrayHelper;
use GraphQL\Type\Definition\InputObjectType;
use GraphQL\Type\Definition\Type;

/**
 * Class Matrix
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.5.0
 */
class Matrix extends InputObjectType
{
    /**
     * Create the type for a Matrix field.
     *
     * @param MatrixField $context
     * @return mixed
     */
    public static function getType(MatrixField $context): mixed
    {
        $typeName = $context->handle . '_MatrixInput';

        return GqlEntityRegistry::getOrCreate($typeName, fn() => new InputObjectType([
            'name' => $typeName,
            'description' => sprintf('Defines a block within the “%s” Matrix field’s data.', $context->name),
            'fields' => function() use ($context) {
                // All the different field entry types now get wrapped in a container input.
                // If two different entry types are passed, the selected entry type to parse is undefined.
                $entryTypeContainerName = $context->handle . '_MatrixEntryContainerInput';
                $entryContainerInputType = GqlEntityRegistry::createEntity($entryTypeContainerName, new InputObjectType([
                    'name' => $entryTypeContainerName,
                    'fields' => function() use ($context) {
                        $entryInputTypes = [];
                        foreach ($context->getEntryTypes() as $entryType) {
                            $entryTypeGqlName = $context->handle . '_' . $entryType->handle . '_MatrixEntryInput';
                            $entryInputTypes[$entryType->handle] = [
                                'name' => $entryType->handle,
                                'type' => GqlEntityRegistry::createEntity($entryTypeGqlName, new InputObjectType([
                                    'name' => $entryTypeGqlName,
                                    'fields' => function() use ($entryType) {
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
                    ],
                    'entries' => [
                        'name' => 'entries',
                        'type' => Type::listOf($entryContainerInputType),
                    ],
                ];
            },
            'normalizeValue' => [self::class, 'normalizeValue'],
        ]));
    }

    /**
     * Normalize Matrix GraphQL input data to what Craft expects.
     *
     * @param mixed $value
     * @return mixed
     */
    public static function normalizeValue(mixed $value): mixed
    {
        $preparedEntries = [];
        $entryCounter = 1;

        if (!isset($value['entries']) && isset($value['blocks'])) {
            $value['entries'] = ArrayHelper::remove($value, 'blocks');
        }

        if (!empty($value['entries'])) {
            foreach ($value['entries'] as $entry) {
                if (!empty($entry)) {
                    $type = array_key_first($entry);
                    $entry = reset($entry);
                    $entryId = ArrayHelper::remove($entry, 'id') ?? sprintf('new:%s', $entryCounter++);
                    $title = ArrayHelper::remove($entry, 'title');

                    $preparedEntries[$entryId] = [
                        'type' => $type,
                        'title' => $title,
                        'fields' => $entry,
                    ];
                }
            }

            $value['entries'] = $preparedEntries;
        }

        return $value;
    }
}
