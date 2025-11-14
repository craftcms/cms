<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\gql\types\generators;

use Craft;
use craft\fields\data\LinkData as LinkFieldData;
use craft\fields\Link;
use craft\fields\linktypes\BaseElementLinkType;
use craft\gql\base\GeneratorInterface;
use craft\gql\base\ObjectType;
use craft\gql\base\SingleGeneratorInterface;
use craft\gql\GqlEntityRegistry;
use craft\gql\interfaces\Element;
use craft\gql\types\LinkData;
use GraphQL\Type\Definition\Type;

/**
 * Class LinkDataType
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 5.6.0
 */
class LinkDataType implements GeneratorInterface, SingleGeneratorInterface
{
    /**
     * @inheritdoc
     */
    public static function generateTypes(mixed $context = null): array
    {
        return [static::generateType($context)];
    }

    /**
     * Returns the generator name.
     */
    public static function getName(): string
    {
        return 'LinkData';
    }

    /**
     * @inheritdoc
     */
    public static function generateType(mixed $context): ObjectType
    {
        $typeName = self::getName();
        return GqlEntityRegistry::getOrCreate($typeName, fn() => new LinkData([
            'name' => $typeName,
            'fields' => function() use ($context, $typeName) {
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

                    foreach ($context->getLinkTypes() as $linkType) {
                        if ($linkType instanceof BaseElementLinkType) {
                            $hasElementType = true;
                            $fields[$linkType::id()] = [
                                'name' => $linkType::id(),
                                'type' => $linkType::elementGqlType(),
                                'resolve' => function(LinkFieldData $value) use ($linkType) {
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
                            'resolve' => fn(LinkFieldData $value) => $value->getElement(),
                        ];
                    }
                }

                return Craft::$app->getGql()->prepareFieldDefinitions($fields, $typeName);
            },
        ]));
    }
}
