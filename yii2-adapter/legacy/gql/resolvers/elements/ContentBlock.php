<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\gql\resolvers\elements;

use craft\base\ElementInterface;
use craft\gql\base\Resolver;
use craft\helpers\Gql as GqlHelper;
use GraphQL\Type\Definition\ResolveInfo;

/**
 * Class ContentBlock
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 5.8.0
 */
class ContentBlock extends Resolver
{
    public static function resolve(mixed $source, array $arguments, mixed $context, ResolveInfo $resolveInfo): mixed
    {
        $fieldName = GqlHelper::getFieldNameWithAlias($resolveInfo, $source, $context);
        /** @var ElementInterface $source */
        return $source->$fieldName;
    }
}
