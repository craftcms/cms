<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\cache;

use Craft;
use yii\caching\Dependency;

/**
 * AppPathDependency is used to determine if Craft’s base path has changed.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 */
class AppPathDependency extends Dependency
{
    /**
     * @var string Craft’s base path
     */
    public string $appPath;

    /**
     * @inheritdoc
     */
    public function init(): void
    {
        $this->reusable = true;
        parent::init();
    }

    /**
     * @inheritdoc
     */
    protected function generateDependencyData($cache): string
    {
        return $this->appPath = Craft::$app->getBasePath();
    }
}
