<?php

namespace craft\gql\types\generators;


/**
 * Class AssetTypeGenerator
 */
interface BaseGenerator
{
    /**
     * Generate GQL types.
     *
     * @param mixed $context Context for generated types
     * @return array
     */
    public static function generateTypes($context = null): array;

    /**
     * Get type name for the context
     *
     * @param mixed $context Context for type name
     * @return string
     */
    public static function getName($context = null): string;
}