<?php
namespace craft\gql\resolvers;

use Craft;
use craft\base\Field;
use craft\base\EagerLoadingFieldInterface;
use craft\helpers\StringHelper;
use GraphQL\Language\AST\FieldNode;
use GraphQL\Language\AST\InlineFragmentNode;
use GraphQL\Language\AST\Node;
use GraphQL\Type\Definition\ResolveInfo;

/**
 * Class BaseResolver
 */
abstract class BaseResolver
{
    /**
     * Cache fields by context.
     *
     * @var array
     */
    protected static $fieldsByContext;

    /**
     * Resolve a field to its value.
     *
     * @param mixed $source The parent data source to use for resolving this field
     * @param array $arguments arguments for resolving this field.
     * @param mixed $context The context shared between all resolvers
     * @param ResolveInfo $resolveInfo The resolve information
     */
    abstract public static function resolve($source, array $arguments, $context, ResolveInfo $resolveInfo);

    /**
     * Returns a list of all the arguments that can be accepted as arrays.
     *
     * @return array
     */
    public static function getArrayableArguments(): array
    {
        return [];
    }

    /**
     * Prepare arguments for use, converting to array where applicable.
     *
     * @param array $arguments
     * @return array
     */
    public static function prepareArguments(array $arguments): array
    {
        $arrayable = static::getArrayableArguments();

        foreach ($arguments as $key => &$value) {
            if (in_array($key, $arrayable, true) && !empty($value) && !is_array($value)) {
                $array = StringHelper::split($value);

                if (count($array) > 1) {
                    $value = $array;
                }
            }
        }

        return $arguments;
    }

    /**
     * Extract sub-selections for a parent field node.
     *
     * @param Node $parentNode
     * @return array
     */
    protected static function extractSubSelections(Node $parentNode) {
        $subSelections = [];
        $subNodes = $parentNode->selectionSet->selections ?? [];

        foreach ($subNodes as $subNode) {
            if ($subNode instanceof FieldNode) {
                $subSelections[$subNode->name->value] = true;
            } else if ($subNode instanceof InlineFragmentNode) {
                $fragmentSubNodes = $subNode->selectionSet->selections ?? [];

                foreach ($fragmentSubNodes as $fragmentSubNode) {
                    $subSelections[$fragmentSubNode->name->value] = true;
                }
            }
        }

        return array_keys($subSelections);
    }

    /**
     * Return a list of preloadable fields from a list of fields and the context
     *
     * @param array $subFields
     * @param string $context
     * @return array
     */
    protected static function getPreloadableFields(array $subFields, $context = 'global')
    {
        if (static::$fieldsByContext === null) {
            self::_loadFields();
        }

        $preloadable = [];

        foreach (self::$fieldsByContext[$context] as $field)
        {
            if ($field instanceof EagerLoadingFieldInterface && in_array($field->handle, $subFields, true)) {
                $preloadable[] = $field->handle;
            }
        }

        return $preloadable;
    }

    // Private methods
    // =========================================================================

    /**
     * Load all the fields
     */
    private static function _loadFields()
    {
        $allFields = Craft::$app->getFields()->getAllFields(false);
        self::$fieldsByContext = [];

        /** @var Field $field */
        foreach ($allFields as $field) {
            self::$fieldsByContext[$field->context][] = $field;
        }
    }
}
