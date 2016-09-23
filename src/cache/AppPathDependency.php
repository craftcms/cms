<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\app\cache;

use Craft;
use yii\caching\Cache;
use yii\caching\Dependency;

/**
 * AppPathDependency is used to determine if the path to the `craft/app` folder has changed.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
class AppPathDependency extends Dependency
{
    /**
     * @var string The path to the `craft/app` folder used to check if the
     *             dependency has been changed.
     */
    public $appPath;

    /**
     * @inheritdoc
     */
    public function init()
    {
        $this->reusable = true;
        parent::init();
    }

    /**
     * Generates the data needed to determine if dependency has been changed.
     *
     * @param Cache $cache The cache component that is currently evaluating this dependency.
     *
     * @return string The data needed to determine if dependency has been changed.
     */
    protected function generateDependencyData($cache)
    {
        return $this->appPath = Craft::$app->getPath()->getAppPath();
    }
}
