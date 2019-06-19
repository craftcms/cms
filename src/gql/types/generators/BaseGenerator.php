<?php

namespace craft\gql\types\generators;


/**
 * Class BaseGenerator
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
}