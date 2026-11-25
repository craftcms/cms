<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\gql\types;

use craft\fields\data\ColorData as FieldColorData;
use craft\gql\base\ObjectType;
use GraphQL\Type\Definition\ResolveInfo;

/**
 * Class ColorData
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 5.12.0
 */
class ColorData extends ObjectType
{
    /**
     * @inheritdoc
     */
    protected function resolve(mixed $source, array $arguments, mixed $context, ResolveInfo $resolveInfo): mixed
    {
        /** @var FieldColorData $source */
        $fieldName = $resolveInfo->fieldName;
        return match ($fieldName) {
            'hex' => $source->getHex($arguments['withAlpha'] ?? true),
            'contrastRatio' => $source->getContrastRatio($arguments['with']),
            default => $source->$fieldName,
        };
    }
}
