<?php
namespace craft\gql\resolvers;

use craft\helpers\StringHelper;
use GraphQL\Type\Definition\ResolveInfo;

/**
 * Class BaseResolver
 */
abstract class BaseResolver
{
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
        return [
            'id',
            'uid',
        ];
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
                // todo maybe trim all the new values?
                $value = StringHelper::split($value);
            }
        }

        return $arguments;
    }
}
