<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\gql\handlers;

use craft\gql\base\ArgumentHandler;
use craft\helpers\Gql as GqlHelper;

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
        $allowedSites = GqlHelper::getAllowedSites();
        $prop = $this->argumentName == 'site' ? 'handle' : 'id';

        $allowedValues = [];

        foreach ($allowedSites as $site) {
            $allowedValues[] = $site->{$prop};
        }

        // Normalize to array.
        if (!empty($argumentValue) && !is_array($argumentValue)) {
            $argumentValue = [$argumentValue];
        }

        // If all values requested, narrow to allowed values.
        if (count($argumentValue) == 1 && $argumentValue[0] == '*') {
            $argumentValue = $allowedValues;
        } else {
            if ($argumentValue[0] == 'not') {
                // get rid of not
                array_unshift($argumentValue);

                // Set the argument value to the diff, subtracting the values that were requested to be excluded from the list of allowed values
                $argumentValue = array_diff($allowedValues, $argumentValue);
            } else {
                // Compute allowed values.
                $argumentValue = array_intersect($argumentValue, $allowedValues);
            }

            // If we're left with nothing, create values that will hopefully net 0 results.
            if (empty($argumentValue)) {
                $argumentValue[] = $prop === 'id' ? 99999 : uniqid('site', true);
            }
        }

        return $argumentValue;
    }
}
