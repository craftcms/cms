<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\gql\handlers;

use craft\elements\Asset;
use craft\gql\base\RelationArgumentHandler;

/**
 * Class RelatedAssets
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.6.0
 */
class RelatedAssets extends RelationArgumentHandler
{
    protected string $argumentName = 'relatedToAssets';

    /**
     * @inheritdoc
     */
    protected function handleArgument($argumentValue): mixed
    {
        $argumentValue = parent::handleArgument($argumentValue);
        return $this->getIds(Asset::class, $argumentValue);
    }
}
