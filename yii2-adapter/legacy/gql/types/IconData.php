<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\gql\types;

use craft\gql\base\ObjectType;
use CraftCms\Cms\Field\Data\IconData as FieldIconData;
use GraphQL\Type\Definition\ResolveInfo;

/**
 * Class IconData
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 5.8.0
 */
class IconData extends ObjectType
{
    /**
     * @inheritdoc
     */
    protected function resolve(mixed $source, array $arguments, mixed $context, ResolveInfo $resolveInfo): mixed
    {
        $fieldName = $resolveInfo->fieldName;
        /** @var FieldIconData $source */
        return $source->$fieldName;
    }
}
