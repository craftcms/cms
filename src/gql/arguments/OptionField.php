<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\gql\arguments;

use craft\gql\base\Arguments;
use GraphQL\Type\Definition\Type;

/**
 * Class OptionField
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.4.6
 */
class OptionField extends Arguments
{
    /**
     * @inheritdoc
     */
    public static function getArguments(): array
    {
        return [
            'label' => [
                'name' => 'label',
                'type' => Type::boolean(),
                'description' => 'If set to true, will return label instead of the value',
            ],
        ];
    }
}
