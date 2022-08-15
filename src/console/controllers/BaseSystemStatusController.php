<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\console\controllers;

use Craft;
use craft\console\Controller;
use Throwable;

/**
 * Takes the system online.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.7.24
 */
abstract class BaseSystemStatusController extends Controller
{
    /**
     * Sets a value in the project config.
     *
     * @param string $path The project config path
     * @param string|int|bool|null $value The new value
     * @throws Throwable
     */
    protected function set(string $path, mixed $value): void
    {
        // Allow changes to the project config even if it’s supposed to be read only,
        // and prevent changes from getting written to YAML
        $projectConfig = Craft::$app->getProjectConfig();
        $projectConfig->readOnly = false;
        $projectConfig->writeYamlAutomatically = false;
        $projectConfig->set($path, $value, null, false);
    }
}
