<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\gql\types\generators;

use Craft;
use craft\base\ElementContainerFieldInterface;
use craft\elements\Entry as EntryElement;
use craft\gql\base\Generator;
use craft\gql\base\GeneratorInterface;
use craft\gql\base\ObjectType;
use craft\gql\base\SingleGeneratorInterface;
use craft\gql\GqlEntityRegistry;
use craft\gql\interfaces\elements\Entry as EntryInterface;
use craft\gql\types\elements\Entry;
use craft\helpers\Gql as GqlHelper;
use craft\models\EntryType as EntryTypeModel;

/**
 * Class EntryType
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.3.0
 */
class EntryType extends Generator implements GeneratorInterface, SingleGeneratorInterface
{
    /**
     * @inheritdoc
     */
    public static function generateTypes(mixed $context = null): array
    {
        if ($context instanceof ElementContainerFieldInterface) {
            $entryTypes = [];
            foreach ($context->getFieldLayoutProviders() as $provider) {
                if ($provider instanceof EntryTypeModel) {
                    $entryTypes[] = $provider;
                }
            }
        } else {
            $entryTypes = GqlHelper::getSchemaContainedEntryTypes();
        }

        $gqlTypes = [];

        foreach ($entryTypes as $entryType) {
            // Generate a type for each entry type
            $type = static::generateType($entryType);
            $gqlTypes[$type->name] = $type;
        }

        return $gqlTypes;
    }

    /**
     * @inheritdoc
     */
    public static function generateType(mixed $context): ObjectType
    {
        /** @var EntryTypeModel $context */
        $typeName = EntryElement::gqlTypeName($context);

        return GqlEntityRegistry::getOrCreate($typeName, fn() => new Entry([
            'name' => $typeName,
            'fields' => function() use ($context, $typeName) {
                $contentFieldGqlTypes = self::getContentFields($context);
                $entryTypeFields = array_merge(EntryInterface::getFieldDefinitions(), $contentFieldGqlTypes);
                return Craft::$app->getGql()->prepareFieldDefinitions($entryTypeFields, $typeName);
            },
        ]));
    }
}
