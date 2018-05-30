<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\cache;

use Craft;
use yii\caching\Cache;
use yii\caching\Dependency;

/**
 * AppPathDependency is used to determine if Craft’s base path has changed.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class AppPathDependency extends Dependency
{
    /**
     * @var string Craft’s base path
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
     * @return string The data needed to determine if dependency has been changed.
     */
    protected function generateDependencyData($cache): string
    {
        return $this->appPath = Craft::$app->getBasePath();
    }
}
