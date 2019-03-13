<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\base;

use craft\gql\types\DateTimeType;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;

/**
 * GqlTrait implements the common methods and properties for classes that support GraphQL.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.2
 */
trait GqlTrait
{
    // Properties
    // =========================================================================

    /**
     * @var ObjectType[] holds this GraphQl Model's type definition, if already defined.
     */
    private static $gqlTypes = null;

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public static function getGqlTypeName(): string
    {
        $className = substr(static::class, strrpos(static::class, '\\') + 1);

        return 'Craft' . $className;
    }

    /**
     * @inheritdoc
     */
    public static function getGqlTypeDefinition(): array
    {
        if (static::$gqlTypes === null) {
            static::$gqlTypes = [
                static::getGqlTypeName() => new ObjectType([
                        'name' => static::getGqlTypeName(),
                        'fields' => static::getGqlTypeProperties()
                    ]
                )
            ];
        }

        return static::$gqlTypes;
    }

    /**
     * A helper method to retrieve the first GraphQL type definition for models that define multiple definitions.
     *
     * @return ObjectType
     */
    public static function getFirstGqlTypeDefinition(): Type
    {
        $typeDefinitions = static::getGqlTypeDefinition();
        return reset($typeDefinitions);
    }

    /**
     * A helper method to retrieve the first GraphQL type definition for models that define multiple definitions.
     *
     * @param string $name name of the type
     * @return ObjectType
     */
    public static function getGqlTypeDefinitionByName(string $name): Type
    {
        $typeDefinitions = static::getGqlTypeDefinition();

        if (!isset($typeDefinitions[$name])) {
            throw new GqlException('Type "' . $name . '" not found!');
        }
        return $typeDefinitions[$name];
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
        $class = new \ReflectionClass(static::class);
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
                    $type = DateTimeType::instance();
                }

                if ($type) {
                    $properties[$property->getName()] = !$isNullable ? Type::nonNull($type) : $type;
                }
            }
        }

        return static::overrideGqlTypeProperties($properties);
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
