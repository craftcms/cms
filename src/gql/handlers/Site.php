<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\gql\handlers;

use craft\gql\base\ArgumentHandler;

/**
 * Class Site
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.6.2
 */
class Site extends ArgumentHandler
{
    protected $argumentName = 'site';

    /**
     * @inheritDoc
     */
    protected function handleArgument($argumentValue)
    {
        if (is_array($argumentValue) && count($argumentValue) == 1 && $argumentValue[0] == '*') {
            $argumentValue = '*';
        }

        return $argumentValue;
    }
}
