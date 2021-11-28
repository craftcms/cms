<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\console\controllers;

use Craft;
use craft\console\Controller;
use craft\helpers\StringHelper;
use Throwable;
use yii\console\Exception;

/**
 * Takes the system online.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.7.24
 */
abstract class BaseSystemStatusController extends Controller
{
    /**
     * @var bool Whether to update the environment variable referenced by the project config, rather than the
     * project config value directly.
     */
    public $env = false;

    /**
     * @inheritdoc
     */
    public function options($actionID)
    {
        $options = parent::options($actionID);
        $options[] = 'env';
        return $options;
    }

    /**
     * Sets a value in the project config, or the environment variable referenced by it if [[env]] is `true`.
     *
     * @param string $path The project config path
     * @param string|int|bool|null $value The new value
     * @throws Throwable
     */
    protected function set(string $path, $value): void
    {
        $projectConfig = Craft::$app->getProjectConfig();

        // Updating the environment variable?
        if ($this->env) {
            $name = $projectConfig->get($path);
            if ($name === null || !StringHelper::startsWith($name, '$')) {
                throw new Exception("The --env option is only supported when $path is set to an environment variable in the project config.");
            }
            $name = substr($name, 1);

            if (is_bool($value)) {
                Craft::$app->getConfig()->setBooleanDotEnvVar($name, $value);
            } else {
                Craft::$app->getConfig()->setDotEnvVar((string)$name, $value);
            }
        } else {
            // Allow changes to the project config even if it’s supposed to be read only,
            // and prevent changes from getting written to YAML
            $projectConfig->readOnly = false;
            $projectConfig->writeYamlAutomatically = false;
            $projectConfig->set($path, $value, null, false);
        }
    }
}
