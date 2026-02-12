<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\gql\handlers;

use craft\elements\User;
use craft\gql\base\RelationArgumentHandler;

/**
 * Class RelatedUsers
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.6.0
 */
class RelatedUsers extends RelationArgumentHandler
{
    protected string $argumentName = 'relatedToUsers';

    /**
     * @inheritdoc
     */
    protected function handleArgument($argumentValue): mixed
    {
        $argumentValue = parent::handleArgument($argumentValue);
        return $this->getIds(User::class, $argumentValue);
    }
}
