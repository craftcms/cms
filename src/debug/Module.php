<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\debug;

use Craft;
use craft\base\FsInterface;
use craft\models\FsListing;
use craft\services\Fs;
use craft\web\View;
use Illuminate\Support\Collection;
use yii\base\Application;

/**
 * @inheritdoc
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 */
class Module extends \yii\debug\Module
{

    public $controllerNamespace = 'craft\debug\controllers';
    public ?FsInterface $fs = null;

    /**
     * @param $app Application
     * @return void
     */
    public function bootstrap($app): void
    {
        parent::bootstrap($app);

        $this->logTarget = $app->getLog()->targets['debug'] = new LogTarget($this);
    }

    /**
     * @inheritdoc
     */
    public function renderToolbar($event): void
    {
        if (!$this->checkAccess() || Craft::$app->getRequest()->getIsAjax()) {
            return;
        }

        /** @var View $view */
        $view = $event->sender;
        echo $this->getToolbarHtml();

        echo '<style>' . $view->renderPhpFile($this->getBasePath() . '/assets/css/toolbar.css') . '</style>';
        echo '<script>' . $view->renderPhpFile($this->getBasePath() . '/assets/js/toolbar.js') . '</script>';
    }
}
