<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\gql\resolvers;

use craft\fields\data\MultiOptionsFieldData;
use craft\fields\data\SingleOptionFieldData;
use craft\gql\base\Resolver;
use GraphQL\Type\Definition\ResolveInfo;

/**
 * Class OptionField
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.4.6
 */
class OptionField extends Resolver
{
    /**
     * @inheritdoc
     */
    public static function resolve($source, array $arguments, $context, ResolveInfo $resolveInfo)
    {
        $fieldName = $resolveInfo->fieldName;
        $optionFieldData = $source->{$fieldName};

        $resolvedValue = '';
        $label = !empty($arguments['label']);

        if ($optionFieldData instanceof MultiOptionsFieldData) {
            $resolvedValue = [];

            foreach ($optionFieldData as $optionData) {
                $resolvedValue[] = $label ? $optionData->label : $optionData->value;
            }
        } else if ($optionFieldData instanceof SingleOptionFieldData) {
            $resolvedValue = $label ? $optionFieldData->label : $optionFieldData->value;
        }

        return $resolvedValue;
    }
}
