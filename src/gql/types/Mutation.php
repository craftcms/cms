<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\gql\types;

use craft\errors\GqlException;
use craft\gql\base\GqlTypeTrait;

/**
 * Class Mutation
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.5.0
 */
class Mutation
{
    use GqlTypeTrait;

    /**
     * @inheritdoc
     * @throws GqlException if class called incorrectly.
     */
    public static function getFieldDefinitions(): array
    {
        throw new GqlException('Mutation type should not have any fields listed statically. Fields must be set at type register time.');
    }

    /**
     * Returns the GraphQL type name.
     */
    public static function getName(): string
    {
        return 'Mutation';
    }
}
