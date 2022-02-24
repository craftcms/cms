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
    protected string $argumentName = 'site';

    /**
     * @inheritdoc
     */
    protected function handleArgument($argumentValue): mixed
    {
        if (is_array($argumentValue) && count($argumentValue) == 1 && $argumentValue[0] == '*') {
            $argumentValue = '*';
        }

        return $argumentValue;
    }
}
