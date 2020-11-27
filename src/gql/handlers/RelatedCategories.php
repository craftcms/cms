<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\gql\handlers;


use Craft;
use craft\elements\Category;
use craft\gql\base\RelationArgumentHandler;
use craft\helpers\ArrayHelper;

/**
 * Class RelatedCategories
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.6.0
 */
class RelatedCategories extends RelationArgumentHandler
{
    protected $argumentName = 'relatedCategories';

    /**
     * @inheritDoc
     */
    protected function handleArgument($argumentValue)
    {
        // Recursively parse nested arguments.
        if (ArrayHelper::isAssociative($argumentValue)) {
            $argumentValue = $this->argumentManager->prepareArguments($argumentValue);
        }

        return $this->getIds(Craft::$app->getElements()->createElementQuery(Category::class), $argumentValue);
    }
}
