<?php

declare(strict_types=1);

namespace CraftCms\Cms\Gql\Types\Generators;

use CraftCms\Cms\Entry\Data\EntryType as EntryTypeData;
use CraftCms\Cms\Entry\Elements\Entry as EntryElement;
use CraftCms\Cms\Field\Contracts\ElementContainerFieldInterface;
use CraftCms\Cms\Gql\Contracts\GeneratorInterface;
use CraftCms\Cms\Gql\Contracts\SingleGeneratorInterface;
use CraftCms\Cms\Gql\GqlEntityRegistry;
use CraftCms\Cms\Gql\GqlHelper;
use CraftCms\Cms\Gql\Interfaces\Elements\Entry as EntryInterface;
use CraftCms\Cms\Gql\Types\Elements\Entry;
use CraftCms\Cms\Gql\Types\ObjectType;
use CraftCms\Cms\Support\Facades\Gql;

class EntryType extends Generator implements GeneratorInterface, SingleGeneratorInterface
{
    public static function generateTypes(mixed $context = null): array
    {
        if ($context instanceof ElementContainerFieldInterface) {
            $entryTypes = [];
            foreach ($context->getFieldLayoutProviders() as $provider) {
                if ($provider instanceof EntryTypeData) {
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

    public static function generateType(mixed $context): ObjectType
    {
        /** @var EntryTypeData $context */
        $typeName = EntryElement::gqlTypeName($context);

        return GqlEntityRegistry::getOrCreate($typeName, fn () => new Entry([
            'name' => $typeName,
            'fields' => function () use ($context, $typeName) {
                $contentFieldGqlTypes = self::getContentFields($context);
                $entryTypeFields = array_merge(EntryInterface::getFieldDefinitions(), $contentFieldGqlTypes);

                return Gql::prepareFieldDefinitions($entryTypeFields, $typeName);
            },
        ]));
    }
}
