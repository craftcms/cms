<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\app\controllers;

use Craft;
use craft\app\base\ToolInterface;
use craft\app\helpers\Component;
use craft\app\helpers\Io;
use craft\app\web\Controller;
use yii\web\Response;

/**
 * The ToolsController class is a controller that handles various tools related tasks such as trigger tool actions.
 *
 * Note that all actions in this controller require administrator access in order to execute.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
class ToolsController extends Controller
{
    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function init()
    {
        // All tool actions require an admin.
        $this->requireAdmin();

        // Any actions here require all we can get.
        Craft::$app->getConfig()->maxPowerCaptain();
    }

    /**
     * Performs a tool's action.
     *
     * @return Response
     */
    public function actionPerformAction()
    {
        $this->requirePostRequest();

        $class = Craft::$app->getRequest()->getRequiredBodyParam('tool');
        $params = Craft::$app->getRequest()->getBodyParam('params', []);

        /** @var ToolInterface $tool */
        $tool = Component::createComponent($class, \craft\app\base\ToolInterface::class);
        $response = $tool->performAction($params);

        return $this->asJson($response);
    }

    /**
     * Returns a database backup zip file to the browser.
     *
     * @return void
     */
    public function actionDownloadBackupFile()
    {
        $filename = Craft::$app->getRequest()->getRequiredQueryParam('filename');

        if (($filePath = Io::fileExists(Craft::$app->getPath()->getTempPath().'/'.$filename.'.zip')) == true) {
            Craft::$app->getResponse()->sendFile(Io::getFilename($filePath), Io::getFileContents($filePath), ['forceDownload' => true]);
        }
    }
}
