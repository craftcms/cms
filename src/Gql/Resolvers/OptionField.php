<?php

declare(strict_types=1);

namespace CraftCms\Cms\Gql\Resolvers;

use CraftCms\Cms\Field\Data\MultiOptionsFieldData;
use CraftCms\Cms\Field\Data\SingleOptionFieldData;
use GraphQL\Type\Definition\ResolveInfo;

class OptionField extends Resolver
{
    public static function resolve(mixed $source, array $arguments, mixed $context, ResolveInfo $resolveInfo): mixed
    {
        $fieldName = $resolveInfo->fieldName;
        $optionFieldData = $source->{$fieldName};

        $resolvedValue = '';
        $label = ! empty($arguments['label']);

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
