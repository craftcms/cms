<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\gql\handlers;

use Craft;
use craft\elements\Tag;
use craft\gql\base\RelationArgumentHandler;

/**
 * Class RelatedTags
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.6.0
 */
class RelatedTags extends RelationArgumentHandler
{
    protected $argumentName = 'relatedToTags';

    /**
     * @inheritDoc
     */
    protected function handleArgument($argumentValue)
    {
        $argumentValue = parent::handleArgument($argumentValue);
        return $this->getIds(Craft::$app->getElements()->createElementQuery(Tag::class), $argumentValue);
    }
}
