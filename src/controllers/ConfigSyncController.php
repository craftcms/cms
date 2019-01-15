<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\controllers;

use Craft;
use craft\base\Plugin;
use craft\db\Table;
use craft\errors\InvalidPluginException;
use craft\helpers\ArrayHelper;
use craft\services\Plugins;
use yii\base\NotSupportedException;
use yii\web\Response;

/**
 * ConfigSyncController handles the Project Config Sync workflow
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.1
 */
class ConfigSyncController extends BaseUpdaterController
{
    // Constants
    // =========================================================================

    const ACTION_RETRY = 'retry';
    const ACTION_APPLY_YAML_CHANGES = 'apply-yaml-changes';
    const ACTION_REGENERATE_YAML = 'regenerate-yaml';
    const ACTION_UNINSTALL_PLUGIN = 'uninstall-plugin';
    const ACTION_INSTALL_PLUGIN = 'install-plugin';

    // Public Methods
    // =========================================================================

    /**
     * Re-kicks off the sync, after the user has had a chance to run `composer install`
     *
     * @return Response
     */
    public function actionRetry(): Response
    {
        return $this->send($this->initialState());
    }

    /**
     * Applies changes in `project.yaml` to the project config.
     *
     * @return Response
     * @throws \Throwable
     */
    public function actionApplyYamlChanges(): Response
    {
        Craft::$app->getProjectConfig()->applyYamlChanges();

        return $this->sendFinished();
    }

    /**
     * Regenerates `project.yaml` based on the loaded project config.
     *
     * @return Response
     * @throws \Throwable
     */
    public function actionRegenerateYaml(): Response
    {
        Craft::$app->getProjectConfig()->regenerateYamlFromConfig();

        return $this->sendFinished();
    }

    /**
     * Uninstalls a plugin.
     *
     * @return Response
     */
    public function actionUninstallPlugin(): Response
    {
        $handle = array_shift($this->data['uninstallPlugins']);

        try {
            Craft::$app->getPlugins()->uninstallPlugin($handle);
        } catch (\Throwable $e) {
            Craft::warning('Could not uninstall plugin "' . $handle . '" that was removed from project.yaml: ' . $e->getMessage());

            // Just remove the row
            Craft::$app->getDb()->createCommand()
                ->delete(Table::PLUGINS, ['handle' => $handle])
                ->execute();
        }

        return $this->sendNextAction($this->_nextApplyYamlAction());
    }

    /**
     * Installs a plugin.
     *
     * @return Response
     */
    public function actionInstallPlugin(): Response
    {
        $handle = array_shift($this->data['installPlugins']);
        list($success, , $errorDetails) = $this->installPlugin($handle);

        if (!$success) {
            $info = Craft::$app->getPlugins()->getComposerPluginInfo($handle);
            $pluginName = $info['name'] ?? "`{$handle}`";
            $email = $info['developerEmail'] ?? 'support@craftcms.com';

            return $this->send([
                'error' => Craft::t('app', 'An error occurred when installing {name}.', ['name' => $pluginName]),
                'errorDetails' => $errorDetails,
                'options' => [
                    [
                        'label' => Craft::t('app', 'Send for help'),
                        'submit' => true,
                        'email' => $email,
                        'subject' => $pluginName . ' update failure',
                    ],
                ],
            ]);
        }

        return $this->sendNextAction($this->_nextApplyYamlAction());
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

        // Any plugins need to be installed/uninstalled?
        $projectConfig = Craft::$app->getProjectConfig();
        $loadedConfigPlugins = array_keys($projectConfig->get(Plugins::CONFIG_PLUGINS_KEY) ?? []);
        $yamlPlugins = array_keys($projectConfig->get(Plugins::CONFIG_PLUGINS_KEY, true) ?? []);
        $data['installPlugins'] = array_diff($yamlPlugins, $loadedConfigPlugins);
        $data['uninstallPlugins'] = array_diff($loadedConfigPlugins, $yamlPlugins);

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

        if (!empty($this->data['installPlugins'])) {
            $pluginsService = Craft::$app->getPlugins();
            $badPlugins = [];

            // Make sure that all to-be-installed plugins actually exist,
            // and that they have the same schema as project.yaml
            foreach ($this->data['installPlugins'] as $handle) {
                try {
                    $plugin = $pluginsService->createPlugin($handle);
                } catch (InvalidPluginException $e) {
                    $plugin = null;
                }

                /** @var Plugin|null $plugin */
                if (
                    !$plugin ||
                    $plugin->schemaVersion != $projectConfig->get(Plugins::CONFIG_PLUGINS_KEY . '.' . $handle . '.schemaVersion', true)
                ) {
                    $badPlugins[] = "`{$handle}`";
                }
            }

            if (!empty($badPlugins)) {
                $error = Craft::t('app', 'The following plugins are listed in `project.yaml`, but appear to be missing or installed at the wrong version:') .
                    ' ' . implode(', ', $badPlugins) .
                    "\n\n" . Craft::t('app', 'Try running `composer install` from your terminal to resolve.');

                return [
                    'error' => $error,
                    'options' => [
                        $this->actionOption(Craft::t('app', 'Try again'), self::ACTION_RETRY, ['submit' => true]),
                    ]
                ];
            }
        }

        // Is the loaded project config newer than project.yaml?
        $configModifiedTime = $projectConfig->get('dateModified');
        $yamlModifiedTime = $projectConfig->get('dateModified', true);

        if ($configModifiedTime > $yamlModifiedTime) {
            return [
                'error' => Craft::t('app', 'The loaded project config has more recent changes than `project.yaml`.'),
                'options' => [
                    $this->actionOption(Craft::t('app', 'Use the loaded project config'), self::ACTION_REGENERATE_YAML, ['submit' => true]),
                    $this->actionOption(Craft::t('app', 'Use project.yaml'), $this->_nextApplyYamlAction(), ['submit' => true]),
                ]
            ];
        }

        return $this->actionState($this->_nextApplyYamlAction());
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
            case self::ACTION_RETRY:
                return Craft::t('app', 'Trying again…');
            case self::ACTION_APPLY_YAML_CHANGES:
                return Craft::t('app', 'Applying changes from the config file…');
            case self::ACTION_REGENERATE_YAML:
                return Craft::t('app', 'Regenerating `project.yaml` from the loaded project config…');
            case self::ACTION_UNINSTALL_PLUGIN:
                $handle = ArrayHelper::firstValue($this->data['uninstallPlugins']);
                return Craft::t('app', 'Uninstalling {name}', [
                    'name' => $this->_pluginName($handle),
                ]);
            case self::ACTION_INSTALL_PLUGIN:
                $handle = ArrayHelper::firstValue($this->data['installPlugins']);
                return Craft::t('app', 'Installing {name}', [
                    'name' => $this->_pluginName($handle),
                ]);
            default:
                return parent::actionStatus($action);
        }
    }

    /**
     * Returns the next action that should be run for applying new project.yaml changes.
     *
     * @return string
     */
    private function _nextApplyYamlAction(): string
    {
        if (!empty($this->data['uninstallPlugins'])) {
            return self::ACTION_UNINSTALL_PLUGIN;
        }

        if (!empty($this->data['installPlugins'])) {
            return self::ACTION_INSTALL_PLUGIN;
        }

        return self::ACTION_APPLY_YAML_CHANGES;
    }

    /**
     * Returns a plugin’s name by its handle.
     *
     * @param string $handle
     * @return string
     */
    private function _pluginName(string $handle): string
    {
        $pluginInfo = Craft::$app->getPlugins()->getAllPluginInfo();
        return isset($pluginInfo[$handle]) ? $pluginInfo[$handle]['name'] : $handle;
    }
}
