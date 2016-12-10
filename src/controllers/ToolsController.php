<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\controllers;

use Craft;
use craft\base\Tool;
use craft\base\ToolInterface;
use craft\helpers\Component;
use craft\web\Controller;
use yii\web\NotFoundHttpException;
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

        /** @var Tool $tool */
        $tool = Component::createComponent($class, ToolInterface::class);
        try {
            $response = $tool->performAction($params);
        } catch (\Exception $e) {
            Craft::error("An error occurred when executing the \"{$tool::displayName()}\" tool: ".$e->getMessage(), __METHOD__);
            $response = [
                'error' => $e->getMessage()
            ];
        }

        return $this->asJson($response);
    }

    /**
     * Returns a database backup zip file to the browser.
     *
     * @return Response
     * @throws NotFoundHttpException
     */
    public function actionDownloadBackupFile()
    {
        $filename = Craft::$app->getRequest()->getRequiredQueryParam('filename');
        $filePath = Craft::$app->getPath()->getTempPath().DIRECTORY_SEPARATOR.$filename.'.zip';

        if (!is_file($filePath)) {
            throw new NotFoundHttpException(Craft::t('app', 'Invalid backup name: {filename}', [
                'filename' => $filename
            ]));
        }

        return Craft::$app->getResponse()->sendFile($filePath);
    }
}
