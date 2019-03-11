<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\base;

use craft\graphql\types\DateTimeType;
use craft\helpers\StringHelper;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;
use ReflectionClass;

/**
 * GraphQlTrait implements the common methods and properties for classes that support GraphQL.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.2
 */
trait GraphQlTrait
{
    // Properties
    // =========================================================================

    /**
     * @var ObjectType|null holds this GraphQl Model's type definition, if already defined.
     */
    protected static $graphQlType = null;

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public static function getGqlTypeName(): string
    {
        $className = substr(self::class, strrpos(self::class, '\\') + 1);

        return 'Craft' . $className;
    }

    /**
     * @inheritdoc
     */
    public static function getGqlTypeDefinition(): ObjectType
    {
        if (self::$graphQlType === null) {
            self::$graphQlType = new ObjectType([
                'name' => self::getGqlTypeName(),
                'fields' => self::getGqlTypeProperties()
            ]);
        }

        return self::$graphQlType;
    }

    /**
     * @inheritdoc
     */
    public static function getGqlQueryDefinitions(): array
    {
        return [];
    }

    /**
     * Return a list of all GraphQL properties for this model.
     *
     * @return array
     * @throws \ReflectionException
     */
    protected static function getGqlTypeProperties(): array
    {
        // By default we return a list of all public properties with the type figured out.
        $class = new \ReflectionClass(self::class);
        $properties = [];

        foreach ($class->getProperties(\ReflectionProperty::IS_PUBLIC) as $property) {
            if (!$property->isStatic()) {
                $comment = $property->getDocComment();

                if (!$comment) {
                    continue;
                }

                $hasType = function ($type) use ($comment) {
                    return  preg_match('/(\|' . $type . '|' . $type . '\||\s' . $type . '\s)/i', $comment);
                };

                $type = null;
                $isNullable = $hasType('null');

                // Figure out the type from the docbloc
                if ($property->getName() == 'id') {
                    $type = Type::id();
                } else if ($hasType('int') || $hasType('integer')) {
                    $type = Type::int();
                } else if ($hasType('string')) {
                    $type = Type::string();
                } else if ($hasType('bool') || $hasType('boolean')) {
                    $type = Type::boolean();
                } else if ($hasType('float')) {
                    $type = Type::float();
                } else if ($hasType('datetime')) {
                    $type = new DateTimeType();
                }

                if ($type) {
                    $properties[$property->getName()] = !$isNullable ? Type::nonNull($type) : $type;
                }
            }
        }

        return self::overrideGqlTypeProperties($properties);
    }

    /**
     * This method allows models to override some GraphQL properties.
     *
     * @param array $properties
     * @return array
     */
    protected static function overrideGqlTypeProperties(array $properties): array
    {
        return $properties;
    }


}
