<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\controllers\pluginstore;

use Craft;
use craft\controllers\BaseUpdaterController;
use yii\web\ForbiddenHttpException;
use yii\web\Response as YiiResponse;

/**
 * InstallController handles the plugin installation workflow.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class InstallController extends BaseUpdaterController
{
    // Constants
    // =========================================================================

    const ACTION_CRAFT_INSTALL = 'craft-install';
    const ACTION_ENABLE = 'enable';
    const ACTION_MIGRATE = 'migrate';

    // Properties
    // =========================================================================

    /**
     * @var string|null
     */
    private $_pluginRedirect;

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function beforeAction($action)
    {
        if (!parent::beforeAction($action)) {
            return false;
        }

        // Only admins can install plugins
        $this->requireAdmin();

        if (
            !Craft::$app->getConfig()->getGeneral()->allowUpdates ||
            !Craft::$app->getConfig()->getGeneral()->allowAdminChanges
        ) {
            throw new ForbiddenHttpException('Installation of plugins from the Plugin Store is disabled.');
        }

        return true;
    }

    /**
     * Installs the plugin.
     *
     * @return YiiResponse
     */
    public function actionCraftInstall(): YiiResponse
    {
        list($success, $tempResponse, $errorDetails) = $this->installPlugin($this->data['handle']);

        if (!$success) {
            $info = Craft::$app->getPlugins()->getComposerPluginInfo($this->data['handle']);
            $pluginName = $info['name'] ?? $this->data['packageName'];
            $email = $info['developerEmail'] ?? 'support@craftcms.com';

            return $this->send([
                'error' => Craft::t('app', '{name} has been added, but an error occurred when installing it.', ['name' => $pluginName]),
                'errorDetails' => $errorDetails,
                'options' => [
                    $this->finishedState([
                        'label' => Craft::t('app', 'Leave it uninstalled'),
                    ]),
                    $this->actionOption(Craft::t('app', 'Remove it'), self::ACTION_COMPOSER_REMOVE),
                    [
                        'label' => Craft::t('app', 'Troubleshoot'),
                        'url' => 'https://craftcms.com/guides/failed-updates',
                    ],
                ],
            ]);
        }

        // Did the plugin want to redirect us somewhere?
        $headers = $tempResponse->getHeaders();
        foreach (['Location', 'X-Redirect', 'X-Pjax-Url'] as $name) {
            if (($value = $headers->get($name)) !== null) {
                $this->_pluginRedirect = $value;
                break;
            }
        }

        return $this->sendFinished();
    }

    /**
     * Enables the plugin. Called if the plugin was already Craft-installed
     * before being installed from the Plugin Store, but it was disabled.
     *
     * @return YiiResponse
     */
    public function actionEnable(): YiiResponse
    {
        Craft::$app->getPlugins()->enablePlugin($this->data['handle']);
        return $this->sendNextAction(self::ACTION_MIGRATE);
    }

    /**
     * Updates the plugin. Called if the plugin was already Craft-installed
     * before being installed from the Plugin Store.
     *
     * @return YiiResponse
     */
    public function actionMigrate(): YiiResponse
    {
        return $this->runMigrations([$this->data['handle']]) ?? $this->sendFinished();
    }

    // Protected Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    protected function pageTitle(): string
    {
        return Craft::t('app', 'Plugin Installer');
    }

    /**
     * @inheritdoc
     */
    protected function initialData(): array
    {
        $request = Craft::$app->getRequest();
        $packageName = strip_tags($request->getRequiredBodyParam('packageName'));
        $handle = strip_tags($request->getRequiredBodyParam('handle'));
        $version = strip_tags($request->getRequiredBodyParam('version'));
        $licenseKey = $request->getBodyParam('licenseKey');

        if (
            ($returnUrl = $request->getBodyParam('return')) !== null &&
            !in_array($returnUrl, ['plugin-store', 'settings/plugins'], true)
        ) {
            $returnUrl = null;
        }

        return [
            'packageName' => $packageName,
            'handle' => $handle,
            'version' => $version,
            'requirements' => [$packageName => $version],
            'removed' => false,
            'licenseKey' => $licenseKey,
            'returnUrl' => $returnUrl,
        ];
    }

    /**
     * @inheritdoc
     */
    protected function actionStatus(string $action): string
    {
        switch ($action) {
            case self::ACTION_CRAFT_INSTALL:
                return Craft::t('app', 'Installing the plugin…');
            case self::ACTION_ENABLE:
                return Craft::t('app', 'Enabling the plugin…');
            case self::ACTION_MIGRATE:
                return Craft::t('app', 'Updating the plugin…');
            default:
                return parent::actionStatus($action);
        }
    }

    /**
     * @inheritdoc
     */
    protected function initialState(): array
    {
        // Make sure we can find composer.json
        if (!$this->ensureComposerJson()) {
            return $this->noComposerJsonState();
        }

        return $this->actionState(self::ACTION_COMPOSER_INSTALL);
    }

    /**
     * @inheritdoc
     */
    protected function postComposerInstallState(): array
    {
        // Was this after a remove?
        if ($this->data['removed']) {
            return $this->actionState(self::ACTION_FINISH, [
                'status' => Craft::t('app', 'The plugin was removed successfully.'),
            ]);
        }

        // Is the plugin already Craft-installed?
        $pluginsService = Craft::$app->getPlugins();
        if ($pluginsService->isPluginInstalled($this->data['handle'])) {
            // Is it disabled?
            if (!$pluginsService->isPluginEnabled($this->data['handle'])) {
                return $this->actionState(self::ACTION_ENABLE);
            }

            return $this->actionState(self::ACTION_MIGRATE);
        }

        return $this->actionState(self::ACTION_CRAFT_INSTALL);
    }

    /**
     * @inheritdoc
     */
    protected function sendFinished(array $state = []): YiiResponse
    {
        // Set the license key
        if ($this->data['licenseKey'] !== null) {
            try {
                Craft::$app->getPlugins()->setPluginLicenseKey($this->data['handle'], $this->data['licenseKey']);
            } catch (\Throwable $e) {
                Craft::error("Could not set the license key on {$this->data['handle']}: {$e->getMessage()}", __METHOD__);
                Craft::$app->getErrorHandler()->logException($e);
            }
        }

        return parent::sendFinished($state);
    }

    /**
     * @inheritdoc
     */
    protected function returnUrl(): string
    {
        return $this->_pluginRedirect ?? $this->data['returnUrl'] ?? 'plugin-store';
    }
}
