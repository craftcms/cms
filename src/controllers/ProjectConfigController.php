<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\controllers;

use Craft;
use craft\helpers\FileHelper;
use craft\helpers\ProjectConfig;
use craft\helpers\StringHelper;
use craft\web\Controller;
use Symfony\Component\Yaml\Yaml;
use yii\base\Exception;
use yii\base\Response;
use yii\web\ForbiddenHttpException;
use ZipArchive;

/**
 * Manages the Project Config.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.5.0
 */
class ProjectConfigController extends Controller
{
    /**
     * @inheritdoc
     */
    public function beforeAction($action): bool
    {
        if (!parent::beforeAction($action)) {
            return false;
        }

        $this->requirePermission('utility:project-config');
        return true;
    }

    /**
     * Returns a diff of the pending external project config changes, compared to the currently loaded project config.
     *
     * @param bool $invert Whether to treat the loaded project config as the source of truth, rather than the external config
     * @since 3.5.8
     */
    public function actionDiff(bool $invert = false): string
    {
        return ProjectConfig::diff($invert);
    }

    /**
     * Discards any changes to the project config files.
     *
     * @return Response
     * @throws ForbiddenHttpException if the project config is in read-only mode
     * @since 3.5.6
     */
    public function actionDiscard(): Response
    {
        $this->requirePostRequest();
        $projectConfig = Craft::$app->getProjectConfig();

        if ($projectConfig->readOnly) {
            throw new ForbiddenHttpException('Rebuilding the project config is not allowed while it’s in read-only mode.');
        }

        $projectConfig->regenerateExternalConfig();
        $this->setSuccessFlash(Craft::t('app', 'External project config changes discarded.'));
        return $this->redirectToPostedUrl();
    }

    /**
     * Rebuilds the project config.
     *
     * @return Response
     * @throws ForbiddenHttpException if the project config is in read-only mode
     * @since 3.5.6
     */
    public function actionRebuild(): Response
    {
        $this->requirePostRequest();
        $projectConfig = Craft::$app->getProjectConfig();

        if ($projectConfig->readOnly) {
            throw new ForbiddenHttpException('Rebuilding the project config is not allowed while it’s in read-only mode.');
        }

        $projectConfig->rebuild();
        $this->setSuccessFlash(Craft::t('app', 'Project config rebuilt successfully.'));
        return $this->redirectToPostedUrl();
    }

    /**
     * Downloads the loaded project config as a zip file.
     *
     * @return Response
     * @since 3.5.6
     */
    public function actionDownload(): Response
    {
        $config = Craft::$app->getProjectConfig()->get();
        $splitConfig = ProjectConfig::splitConfigIntoComponents($config);
        $zip = new ZipArchive();
        $zipPath = Craft::$app->getPath()->getTempPath() . '/' . StringHelper::UUID() . '.zip';

        if ($zip->open($zipPath, ZipArchive::CREATE) !== true) {
            throw new Exception('Cannot create zip at ' . $zipPath);
        }

        foreach ($splitConfig as $path => $pathConfig) {
            $content = Yaml::dump(ProjectConfig::cleanupConfig($pathConfig), 20, 2);
            $zip->addFromString($path, $content);
        }

        $zip->close();
        $this->response->sendContentAsFile(file_get_contents($zipPath), 'project.zip');
        FileHelper::unlink($zipPath);

        return $this->response;
    }
}
