<?php

namespace craft\gql\base;


/**
 * Class BaseGenerator
 */
interface GeneratorInterface
{
    /**
     * Generate GQL types.
     *
     * @param mixed $context Context for generated types
     * @return array
     */
    public static function generateTypes($context = null): array;
}