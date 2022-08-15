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
    public static function resolve(mixed $source, array $arguments, mixed $context, ResolveInfo $resolveInfo): mixed
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
        } elseif ($optionFieldData instanceof SingleOptionFieldData) {
            $resolvedValue = $label ? $optionFieldData->label : $optionFieldData->value;
        }

        return $resolvedValue;
    }
}
