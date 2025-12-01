<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\gql\handlers;

use craft\elements\Entry;
use craft\gql\base\RelationArgumentHandler;

/**
 * Class RelatedEntries
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.6.0
 */
class RelatedEntries extends RelationArgumentHandler
{
    protected string $argumentName = 'relatedToEntries';

    /**
     * @inheritdoc
     */
    protected function handleArgument($argumentValue): mixed
    {
        $argumentValue = parent::handleArgument($argumentValue);
        return $this->getIds(Entry::class, $argumentValue);
    }
}
