<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\controllers;

use Composer\IO\BufferIO;
use Composer\Semver\Comparator;
use Composer\Semver\VersionParser;
use Craft;
use craft\base\Plugin;
use yii\base\NotSupportedException;
use yii\web\BadRequestHttpException;
use yii\web\Response;

/**
 * ConfigSyncController handles the Project Config sync workflow
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.1
 */
class ConfigSyncController extends BaseUpdaterController
{
    // Constants
    // =========================================================================

    const ACTION_CONFIG_USE_YAML = 'apply-config-changes';
    const ACTION_CONFIG_USE_SNAPSHOT = 'regenerate-config';

    // Public Methods
    // =========================================================================

    /**
     * Apply the configuration changes.
     *
     * @return Response
     * @throws \Throwable
     */
    public function actionApplyConfigChanges(): Response
    {
        Craft::$app->getProjectConfig()->applyPendingChanges();

        return $this->sendFinished();
    }

    /**
     * Overwrite the config file with the snapshot data.
     *
     * @return Response
     * @throws \Throwable
     */
    public function actionRegenerateConfig(): Response
    {
        Craft::$app->getProjectConfig()->regenerateConfigFileFromStoredConfig();

        return $this->sendFinished();
    }

    // Protected Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    protected function pageTitle(): string
    {
        return Craft::t('app', 'Project Config Sync');
    }

    /**
     * @inheritdoc
     */
    protected function initialData(): array
    {
        $data = [];

        // Set the return URL, if any
        if (($returnUrl = Craft::$app->getRequest()->getBodyParam('return')) !== null) {
            $data['returnUrl'] = strip_tags($returnUrl);
        }

        return $data;
    }

    /**
     * @inheritdoc
     */
    protected function initialState(): array
    {
        $projectConfig = Craft::$app->getProjectConfig();
        $snapshotModifiedTime = $projectConfig->get('dateModified');
        $configModifiedTime = $projectConfig->get('dateModified', true);

        // Bail if snapshot newer than config
        if ($snapshotModifiedTime > $configModifiedTime) {
            return [
                'error' => str_replace('<br>', "\n\n", Craft::t('app', 'The loaded project config has more recent changes than `project.yaml`.')),
                'options' => [
                    $this->actionOption(Craft::t('app', 'Use the loaded project config'), self::ACTION_CONFIG_USE_SNAPSHOT, ['submit' => true]),
                    $this->actionOption(Craft::t('app', 'Use project.yaml'), self::ACTION_CONFIG_USE_YAML, ['submit' => true]),
                ]
            ];
        }

        return $this->actionState(self::ACTION_CONFIG_USE_YAML);
    }

    /**
     * @inheritdoc
     */
    protected function postComposerInstallState(): array
    {
        throw new NotSupportedException('postComposerInstallState() is not supported by ' . __CLASS__);
    }

    /**
     * @inheritdoc
     */
    protected function returnUrl(): string
    {
        return $this->data['returnUrl'] ?? Craft::$app->getConfig()->getGeneral()->getPostCpLoginRedirect();
    }

    /**
     * @inheritdoc
     */
    protected function actionStatus(string $action): string
    {
        switch ($action) {
            case self::ACTION_CONFIG_USE_YAML:
                return Craft::t('app', 'Applying changes from the config file…');
            case self::ACTION_CONFIG_USE_SNAPSHOT:
                return Craft::t('app', 'Restoring the config file from snapshot…');
            default:
                return parent::actionStatus($action);
        }
    }
}
