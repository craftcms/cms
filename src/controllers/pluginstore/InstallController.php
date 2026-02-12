<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\controllers\pluginstore;

use Craft;
use craft\controllers\BaseUpdaterController;
use craft\web\Response;
use Throwable;
use yii\web\ForbiddenHttpException;
use yii\web\Response as YiiResponse;

/**
 * InstallController handles the plugin installation workflow.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 * @internal
 */
class InstallController extends BaseUpdaterController
{
    public const ACTION_CRAFT_INSTALL = 'craft-install';
    public const ACTION_ENABLE = 'enable';
    public const ACTION_MIGRATE = 'migrate';

    /**
     * @var string|null
     */
    private ?string $_pluginRedirect = null;

    /**
     * @inheritdoc
     */
    public function beforeAction($action): bool
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
        /** @var Response $tempResponse */
        [$success, $tempResponse, $errorDetails] = $this->installPlugin($this->data['handle'], $this->data['edition']);

        if (!$success) {
            $info = Craft::$app->getPlugins()->getComposerPluginInfo($this->data['handle']);
            $pluginName = $info['name'] ?? $this->data['packageName'];

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
                        'url' => 'https://craftcms.com/knowledge-base/failed-updates',
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
        $packageName = strip_tags($this->request->getRequiredBodyParam('packageName'));
        $handle = strip_tags($this->request->getRequiredBodyParam('handle'));
        $edition = strip_tags($this->request->getRequiredBodyParam('edition'));
        $version = strip_tags($this->request->getRequiredBodyParam('version'));
        $licenseKey = $this->request->getBodyParam('licenseKey');

        if (
            ($returnUrl = $this->findReturnUrl()) !== null &&
            !in_array($returnUrl, ['plugin-store', 'settings/plugins'], true)
        ) {
            $returnUrl = null;
        }

        return [
            'packageName' => $packageName,
            'handle' => $handle,
            'edition' => $edition,
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
        return match ($action) {
            self::ACTION_CRAFT_INSTALL => Craft::t('app', 'Installing the plugin…'),
            self::ACTION_ENABLE => Craft::t('app', 'Enabling the plugin…'),
            self::ACTION_MIGRATE => Craft::t('app', 'Updating the plugin…'),
            default => parent::actionStatus($action),
        };
    }

    /**
     * @inheritdoc
     */
    protected function initialState(bool $force = false): array
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
            } catch (Throwable $e) {
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
