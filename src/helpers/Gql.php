<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\helpers;

use craft\gql\directives\BaseDirective;
use craft\gql\GqlEntityRegistry;
use GraphQL\Type\Definition\ResolveInfo;

/**
 * Class Gql
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.3
 */
class Gql
{
    /**
     * A helper function for applying directives to a field.
     *
     * @param mixed $source The parent data source to use for resolving this field
     * @param array $arguments arguments for resolving this field.
     * @param mixed $context The context shared between all resolvers
     * @param ResolveInfo $resolveInfo The resolve information
     */
    public static function applyDirectivesToField($value, ResolveInfo $resolveInfo)
    {
        if (isset($resolveInfo->fieldNodes[0])) {
            foreach ($resolveInfo->fieldNodes[0]->directives as $directive) {
                /** @var BaseDirective $directiveEntity */
                $directiveEntity = GqlEntityRegistry::getEntity($directive->name->value);
                $arguments = [];

                if (isset($directive->arguments[0])) {
                    foreach ($directive->arguments as $argument) {
                        $arguments[$argument->name->value] = $argument->value->value;
                    }
                }

                $value = $directiveEntity::applyDirective($value, $arguments);
            }
        }

        return $value;
    }
}
