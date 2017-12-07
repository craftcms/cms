<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.github.io/license/
 */

namespace craft\controllers\pluginstore;

use Craft;
use craft\controllers\BaseUpdaterController;
use craft\errors\MigrateException;
use craft\errors\MigrationException;
use craft\web\Response as CraftResponse;
use yii\base\Exception as YiiException;
use yii\web\Response as YiiResponse;

/**
 * InstallController handles the plugin installation workflow.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
class InstallController extends BaseUpdaterController
{
    // Constants
    // =========================================================================

    const ACTION_CRAFT_INSTALL = 'craft-install';

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

        return true;
    }

    /**
     * Installs the plugin.
     *
     * @return YiiResponse
     */
    public function actionCraftInstall(): YiiResponse
    {
        // Prevent the plugin from sending any headers, etc.
        $realResponse = Craft::$app->getResponse();
        $tempResponse = new CraftResponse(['isSent' => true]);
        Craft::$app->set('response', $tempResponse);

        try {
            Craft::$app->getPlugins()->installPlugin($this->data['handle']);
        } catch (\Throwable $e) {
            Craft::$app->set('response', $realResponse);
            $migration = $output = null;

            $info = Craft::$app->getPlugins()->getComposerPluginInfo($this->data['handle']);
            $pluginName = $info['name'] ?? $this->data['packageName'];
            $email = $info['developerEmail'] ?? 'support@craftcms.com';

            if ($e instanceof MigrateException) {
                /** @var \Throwable $e */
                $e = $e->getPrevious();

                if ($e instanceof MigrationException) {
                    /** @var \Throwable|null $previous */
                    $previous = $e->getPrevious();
                    $migration = $e->migration;
                    $output = $e->output;
                    $e = $previous ?? $e;
                }
            }

            Craft::error('Plugin installation failed: '.$e->getMessage(), __METHOD__);

            $eName = $e instanceof YiiException ? $e->getName() : get_class($e);

            return $this->send([
                'error' => Craft::t('app', '{name} has been added, but an error occurred when installing it.', ['name' => $pluginName]),
                'errorDetails' => $eName.': '.$e->getMessage().
                    ($migration ? "\n\nMigration: ".get_class($migration) : '').
                    ($output ? "\n\nOutput:\n\n".$output : ''),
                'options' => [
                    $this->finishedState([
                        'label' => Craft::t('app', 'Leave it uninstalled'),
                    ]),
                    $this->actionOption(Craft::t('app', 'Remove it'), self::ACTION_COMPOSER_REMOVE),
                    [
                        'label' => Craft::t('app', 'Send for help'),
                        'submit' => true,
                        'email' => $email,
                        'subject' => $pluginName.' update failure',
                    ],
                ],
            ]);
        }

        // Put the real response back
        Craft::$app->set('response', $realResponse);

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

        return [
            'packageName' => $packageName,
            'handle' => $handle,
            'version' => $version,
            'requirements' => [$packageName => $version],
            'removed' => false,
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
    protected function postComposerOptimizeState(): array
    {
        // Was this after a remove?
        if ($this->data['removed']) {
            return $this->actionState(self::ACTION_FINISH, [
                'status' => Craft::t('app', 'The plugin was removed successfully.'),
            ]);
        }

        return $this->actionState(self::ACTION_CRAFT_INSTALL);
    }

    /**
     * @inheritdoc
     */
    protected function returnUrl(): string
    {
        return $this->_pluginRedirect ?? 'plugin-store';
    }
}
