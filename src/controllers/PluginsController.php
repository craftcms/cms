<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\controllers;

use Craft;
use craft\base\PluginInterface;
use craft\web\Controller;
use yii\web\NotFoundHttpException;
use yii\web\Response;

/**
 * The PluginsController class is a controller that handles various plugin related tasks such installing, uninstalling,
 * enabling, disabling and saving plugin settings in the control panel.
 * Note that all actions in the controller require an authenticated Craft session via [[allowAnonymous]].
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 */
class PluginsController extends Controller
{
    /**
     * @inheritdoc
     */
    public function beforeAction($action): bool
    {
        // All plugin actions require an admin
        $this->requireAdmin();

        return parent::beforeAction($action);
    }

    /**
     * Installs a plugin.
     *
     * @return Response|null
     */
    public function actionInstallPlugin(): ?Response
    {
        $this->requirePostRequest();

        $pluginHandle = $this->request->getRequiredBodyParam('pluginHandle');
        $edition = $this->request->getBodyParam('edition');
        $success = Craft::$app->getPlugins()->installPlugin($pluginHandle, $edition);

        return $success ?
            $this->asSuccess(Craft::t('app', 'Plugin installed.')) :
            $this->asFailure(Craft::t('app', 'Couldn’t install plugin.'));
    }

    /**
     * Installs a plugin.
     *
     * @return Response
     */
    public function actionSwitchEdition(): Response
    {
        $this->requirePostRequest();
        $pluginHandle = $this->request->getRequiredBodyParam('pluginHandle');
        $edition = $this->request->getRequiredBodyParam('edition');
        Craft::$app->getPlugins()->switchEdition($pluginHandle, $edition);

        return $this->asSuccess(Craft::t('app', 'Plugin edition changed.'));
    }

    /**
     * Uninstalls a plugin.
     *
     * @return Response|null
     */
    public function actionUninstallPlugin(): ?Response
    {
        $this->requirePostRequest();
        $pluginHandle = $this->request->getRequiredBodyParam('pluginHandle');
        $success = Craft::$app->getPlugins()->uninstallPlugin($pluginHandle);

        return $success ?
            $this->asSuccess(Craft::t('app', 'Plugin uninstalled.')) :
            $this->asFailure(Craft::t('app', 'Couldn’t uninstall plugin.'));
    }

    /**
     * Edits a plugin’s settings.
     *
     * @param string $handle The plugin’s handle
     * @param PluginInterface|null $plugin The plugin, if there were validation errors
     * @return mixed
     * @throws NotFoundHttpException if the requested plugin cannot be found
     */
    public function actionEditPluginSettings(string $handle, ?PluginInterface $plugin = null): mixed
    {
        if (
            $plugin === null &&
            ($plugin = Craft::$app->getPlugins()->getPlugin($handle)) === null
        ) {
            throw new NotFoundHttpException('Plugin not found');
        }

        return $plugin->getSettingsResponse();
    }

    /**
     * Enables a plugin.
     *
     * @return Response|null
     */
    public function actionEnablePlugin(): ?Response
    {
        $this->requirePostRequest();
        $pluginHandle = $this->request->getRequiredBodyParam('pluginHandle');
        $success = Craft::$app->getPlugins()->enablePlugin($pluginHandle);

        return $success ?
            $this->asSuccess(Craft::t('app', 'Plugin enabled.')) :
            $this->asFailure(Craft::t('app', 'Couldn’t enable plugin.'));
    }

    /**
     * Disables a plugin.
     *
     * @return Response|null
     */
    public function actionDisablePlugin(): ?Response
    {
        $this->requirePostRequest();
        $pluginHandle = $this->request->getRequiredBodyParam('pluginHandle');

        $success = Craft::$app->getPlugins()->disablePlugin($pluginHandle);

        return $success ?
            $this->asSuccess(Craft::t('app', 'Plugin disabled.')) :
            $this->asFailure(Craft::t('app', 'Couldn’t disable plugin.'));
    }

    /**
     * Saves a plugin’s settings.
     *
     * @return Response|null
     * @throws NotFoundHttpException if the requested plugin cannot be found
     */
    public function actionSavePluginSettings(): ?Response
    {
        $this->requirePostRequest();
        $pluginHandle = $this->request->getRequiredBodyParam('pluginHandle');
        $settings = $this->request->getBodyParam('settings', []);
        $plugin = Craft::$app->getPlugins()->getPlugin($pluginHandle);

        if ($plugin === null) {
            throw new NotFoundHttpException('Plugin not found');
        }

        $success = Craft::$app->getPlugins()->savePluginSettings($plugin, $settings);

        return $success ?
            $this->asSuccess(Craft::t('app', 'Plugin settings saved.')) :
            $this->asFailure(
                Craft::t('app', 'Couldn’t save plugin settings.'),
                routeParams: ['plugin' => $plugin]
            );
    }
}
