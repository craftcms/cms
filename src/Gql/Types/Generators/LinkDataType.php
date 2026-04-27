<?php

declare(strict_types=1);

namespace CraftCms\Cms\Gql\Types\Generators;

use CraftCms\Cms\Field\Data\LinkData as LinkFieldData;
use CraftCms\Cms\Field\Link;
use CraftCms\Cms\Field\LinkTypes\BaseElementLinkType;
use CraftCms\Cms\Gql\Contracts\GeneratorInterface;
use CraftCms\Cms\Gql\Contracts\SingleGeneratorInterface;
use CraftCms\Cms\Gql\GqlEntityRegistry;
use CraftCms\Cms\Gql\Interfaces\Element;
use CraftCms\Cms\Gql\Types\LinkData;
use CraftCms\Cms\Gql\Types\ObjectType;
use CraftCms\Cms\Support\Facades\Gql;
use GraphQL\Type\Definition\Type;

class LinkDataType implements GeneratorInterface, SingleGeneratorInterface
{
    public static function generateTypes(mixed $context = null): array
    {
        return [static::generateType($context)];
    }

    public static function getName(): string
    {
        return 'LinkData';
    }

    public static function generateType(mixed $context): ObjectType
    {
        // @TODO at next breaking change name space the types per field as they can have different settings
        $typeName = self::getName();

        return GqlEntityRegistry::getOrCreate($typeName, fn () => new LinkData([
            'name' => $typeName,
            'fields' => function () use ($context, $typeName) {
                $fields = [
                    'type' => Type::string(),
                    'value' => Type::string(),
                    'label' => Type::string(),
                    'defaultLabel' => Type::string(),
                    'urlSuffix' => Type::string(),
                    'url' => Type::string(),
                    'link' => Type::string(),
                    'target' => Type::string(),
                    'title' => Type::string(),
                    'class' => Type::string(),
                    'id' => Type::string(),
                    'rel' => Type::string(),
                    'ariaLabel' => Type::string(),
                    'download' => Type::boolean(),
                    'filename' => Type::string(),
                    'elementType' => Type::string(),
                    'elementId' => Type::int(),
                    'elementSiteId' => Type::int(),
                    'elementTitle' => Type::string(),
                ];

                if ($context instanceof Link) {
                    $hasElementType = false;

                    // Loop through all types available in the system
                    foreach (Link::types() as $linkType) {
                        if (is_subclass_of($linkType, BaseElementLinkType::class)) {
                            $hasElementType = true;
                            $fields[$linkType::id()] = [
                                'name' => $linkType::id(),
                                'type' => $linkType::elementGqlType(),
                                'resolve' => function (LinkFieldData $value) use ($linkType) {
                                    if ($value->getType() === $linkType::id()) {
                                        return $value->getElement();
                                    }

                                    return null;
                                },
                            ];
                        }
                    }

                    if ($hasElementType) {
                        $fields['element'] = [
                            'name' => 'element',
                            'type' => Element::getType(),
                            'resolve' => fn (LinkFieldData $value) => $value->getElement(),
                        ];
                    }
                }

                return Gql::prepareFieldDefinitions($fields, $typeName);
            },
        ]));
    }
}
