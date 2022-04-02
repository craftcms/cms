<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\gql\handlers;

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
    protected string $argumentName = 'relatedToTags';

    /**
     * @inheritdoc
     */
    protected function handleArgument($argumentValue): mixed
    {
        $argumentValue = parent::handleArgument($argumentValue);
        return $this->getIds(Tag::class, $argumentValue);
    }
}
