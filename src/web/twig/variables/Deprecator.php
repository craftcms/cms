<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\web\twig\variables;

use Craft;

/**
 * Class Deprecator variable.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 * @deprecated in 3.0.0
 */
class Deprecator
{
    /**
     * Returns the total number of deprecation errors that have been logged.
     *
     * @return int
     */
    public function getTotalLogs(): int
    {
        Craft::$app->getDeprecator()->log('craft.deprecator.getTotalLogs()', '`craft.deprecator.getTotalLogs()` has been deprecated. Use `craft.app.deprecator.totalLogs` instead.');

        return Craft::$app->getDeprecator()->getTotalLogs();
    }
}
