<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\app\controllers;

use Craft;
use craft\app\base\PluginInterface;
use craft\app\web\Controller;
use yii\web\NotFoundHttpException;
use yii\web\Response;

/**
 * The PluginsController class is a controller that handles various plugin related tasks such installing, uninstalling,
 * enabling, disabling and saving plugin settings in the control panel.
 *
 * Note that all actions in the controller require an authenticated Craft session via [[Controller::allowAnonymous]].
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
class PluginsController extends Controller
{
    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function init()
    {
        // All plugin actions require an admin
        $this->requireAdmin();
    }

    /**
     * Installs a plugin.
     *
     * @return Response
     */
    public function actionInstallPlugin()
    {
        $this->requirePostRequest();
        $pluginHandle = Craft::$app->getRequest()->getRequiredBodyParam('pluginHandle');

        if (Craft::$app->getPlugins()->installPlugin($pluginHandle)) {
            Craft::$app->getSession()->setNotice(Craft::t('app', 'Plugin installed.'));
        } else {
            Craft::$app->getSession()->setError(Craft::t('app', 'Couldn’t install plugin.'));
        }

        return $this->redirectToPostedUrl();
    }

    /**
     * Uninstalls a plugin.
     *
     * @return Response
     */
    public function actionUninstallPlugin()
    {
        $this->requirePostRequest();
        $pluginHandle = Craft::$app->getRequest()->getRequiredBodyParam('pluginHandle');

        if (Craft::$app->getPlugins()->uninstallPlugin($pluginHandle)) {
            Craft::$app->getSession()->setNotice(Craft::t('app', 'Plugin uninstalled.'));
        } else {
            Craft::$app->getSession()->setError(Craft::t('app', 'Couldn’t uninstall plugin.'));
        }

        return $this->redirectToPostedUrl();
    }

    /**
     * Enables a plugin.
     *
     * @return Response
     */
    public function actionEnablePlugin()
    {
        $this->requirePostRequest();
        $pluginHandle = Craft::$app->getRequest()->getRequiredBodyParam('pluginHandle');

        if (Craft::$app->getPlugins()->enablePlugin($pluginHandle)) {
            Craft::$app->getSession()->setNotice(Craft::t('app', 'Plugin enabled.'));
        } else {
            Craft::$app->getSession()->setError(Craft::t('app', 'Couldn’t enable plugin.'));
        }

        return $this->redirectToPostedUrl();
    }

    /**
     * Disables a plugin.
     *
     * @return Response
     */
    public function actionDisablePlugin()
    {
        $this->requirePostRequest();
        $pluginHandle = Craft::$app->getRequest()->getRequiredBodyParam('pluginHandle');

        if (Craft::$app->getPlugins()->disablePlugin($pluginHandle)) {
            Craft::$app->getSession()->setNotice(Craft::t('app', 'Plugin disabled.'));
        } else {
            Craft::$app->getSession()->setError(Craft::t('app', 'Couldn’t disable plugin.'));
        }

        return $this->redirectToPostedUrl();
    }

    /**
     * Edits a plugin’s settings.
     *
     * @param string               $pluginHandle The plugin’s handle
     * @param PluginInterface|null $plugin       The plugin, if there were validation errors
     *
     * @return string The plugin page HTML
     * @throws NotFoundHttpException if the requested plugin cannot be found
     */
    public function actionEditPluginSettings($pluginHandle, PluginInterface $plugin = null)
    {
        if ($plugin === null) {
            $plugin = Craft::$app->getPlugins()->getPlugin($pluginHandle);

            if ($plugin === null) {
                throw new NotFoundHttpException('Plugin not found');
            }
        }

        return $plugin->getSettingsResponse();
    }

    /**
     * Saves a plugin’s settings.
     *
     * @return Response|null
     * @throws NotFoundHttpException if the requested plugin cannot be found
     */
    public function actionSavePluginSettings()
    {
        $this->requirePostRequest();
        $pluginHandle = Craft::$app->getRequest()->getRequiredBodyParam('pluginHandle');
        $settings = Craft::$app->getRequest()->getBodyParam('settings');
        $plugin = Craft::$app->getPlugins()->getPlugin($pluginHandle);

        if ($plugin === null) {
            throw new NotFoundHttpException('Plugin not found');
        }

        if (Craft::$app->getPlugins()->savePluginSettings($plugin, $settings)) {
            Craft::$app->getSession()->setNotice(Craft::t('app', 'Plugin settings saved.'));

            return $this->redirectToPostedUrl();
        }

        Craft::$app->getSession()->setError(Craft::t('app', 'Couldn’t save plugin settings.'));

        // Send the plugin back to the template
        Craft::$app->getUrlManager()->setRouteParams([
            'plugin' => $plugin
        ]);

        return null;
    }
}
