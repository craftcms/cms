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

        if ($inputType = GqlEntityRegistry::getEntity($typeName)) {
            return $inputType;
        }

        $entryTypes = $context->getEntryTypes();
        $entryInputTypes = [];

        foreach ($entryTypes as $entryType) {
            $fields = $entryType->getCustomFields();
            $entryTypeFields = [
                'id' => [
                    'name' => 'id',
                    'type' => Type::id(),
                ],
            ];

            // Get the field input types
            foreach ($fields as $field) {
                /** @var Field $field */
                $entryTypeFields[$field->handle] = $field->getContentGqlMutationArgumentType();
            }

            $entryTypeGqlName = $context->handle . '_' . $entryType->handle . '_MatrixEntryInput';
            $entryInputTypes[$entryType->handle] = [
                'name' => $entryType->handle,
                'type' => GqlEntityRegistry::createEntity($entryTypeGqlName, new InputObjectType([
                    'name' => $entryTypeGqlName,
                    'fields' => $entryTypeFields,
                ])),
            ];
        }

        // All the different field entry types now get wrapped in a container input.
        // If two different entry types are passed, the selected entry type to parse is undefined.
        $entryTypeContainerName = $context->handle . '_MatrixEntryContainerInput';
        $entryContainerInputType = GqlEntityRegistry::createEntity($entryTypeContainerName, new InputObjectType([
            'name' => $entryTypeContainerName,
            'fields' => function() use ($entryInputTypes) {
                return $entryInputTypes;
            },
        ]));

        return GqlEntityRegistry::createEntity($typeName, new InputObjectType([
            'name' => $typeName,
            'fields' => function() use ($entryContainerInputType) {
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
        $missingId = false;

        if (!empty($value['entries'])) {
            foreach ($value['entries'] as $entry) {
                if (!empty($entry)) {
                    $type = array_key_first($entry);
                    $entry = reset($entry);
                    $missingId = $missingId || empty($entry['id']);
                    $entryId = !empty($entry['id']) ? $entry['id'] : 'new:' . ($entryCounter++);

                    unset($entry['id']);

                    $preparedEntries[$entryId] = [
                        'type' => $type,
                        'fields' => $entry,
                    ];
                }
            }

            if ($missingId) {
                Craft::$app->getDeprecator()->log('MatrixInput::normalizeValue()', 'The `id` field will be required when mutating Matrix fields as of Craft 4.0.');
            }

            $value['entries'] = $preparedEntries;
        }

        return $value;
    }
}
