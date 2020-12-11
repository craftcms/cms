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

/**
 * Class RelatedCategories
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.6.0
 */
class RelatedCategories extends RelationArgumentHandler
{
    protected $argumentName = 'relatedToCategories';

    /**
     * @inheritDoc
     */
    protected function handleArgument($argumentValue)
    {
        $argumentValue = parent::handleArgument($argumentValue);
        return $this->getIds(Craft::$app->getElements()->createElementQuery(Category::class), $argumentValue);
    }
}
