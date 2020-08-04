<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\debug;

use Craft;
use craft\web\View;

/**
 * @inheritdoc
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 */
class Module extends \yii\debug\Module
{
    /**
     * @inheritdoc
     */
    public function renderToolbar($event)
    {
        if (!$this->checkAccess() || Craft::$app->getRequest()->getIsAjax()) {
            return;
        }

        /* @var $view View */
        $view = $event->sender;
        echo $this->getToolbarHtml();

        echo '<style>' . $view->renderPhpFile($this->getBasePath() . '/assets/css/toolbar.css') . '</style>';
        echo '<script>' . $view->renderPhpFile($this->getBasePath() . '/assets/js/toolbar.js') . '</script>';
    }
}
