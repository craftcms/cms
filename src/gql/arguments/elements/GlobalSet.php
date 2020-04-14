<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\gql\arguments\elements;

use Craft;
use craft\elements\GlobalSet as GlobalSetElement;
use craft\gql\base\ElementArguments;
use GraphQL\Type\Definition\Type;

/**
 * Class GlobalSet
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.3.0
 */
class GlobalSet extends ElementArguments
{
    /**
     * @inheritdoc
     */
    public static function getArguments(): array
    {
        return array_merge(parent::getArguments(), self::getContentArguments(), [
            'handle' => [
                'name' => 'handle',
                'type' => Type::listOf(Type::string()),
                'description' => 'Narrows the query results based on the global sets’ handles.'
            ],
        ]);
    }

    /**
     * @inheritdoc
     */
    public static function getContentArguments(): array
    {
        $globalSetFieldArgument = Craft::$app->getGql()->getContentArguments(Craft::$app->getGlobals()->getAllSets(), GlobalSetElement::class);
        return array_merge(parent::getContentArguments(), $globalSetFieldArgument);
    }
}
