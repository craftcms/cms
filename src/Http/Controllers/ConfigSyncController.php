<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Controllers;

use craft\web\Application;
use CraftCms\Cms\Config\GeneralConfig;
use CraftCms\Cms\Plugin\Exceptions\InvalidPluginException;
use CraftCms\Cms\Plugin\Plugins;
use CraftCms\Cms\ProjectConfig\Exceptions\BusyResourceException;
use CraftCms\Cms\ProjectConfig\Exceptions\StaleResourceException;
use CraftCms\Cms\ProjectConfig\ProjectConfig;
use CraftCms\Cms\Support\Arr;
use CraftCms\Cms\Support\Composer;
use CraftCms\Cms\Support\Json;
use CraftCms\Cms\Support\URL;
use CraftCms\Cms\Updates\Updates;
use Illuminate\Container\Attributes\Give;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Inertia\Inertia;
use Override;
use RuntimeException;
use Symfony\Component\HttpFoundation\Response;

use function CraftCms\Cms\t;

/**
 * @internal
 */
class ConfigSyncController extends BaseUpdaterController
{
    public const string ACTION_RETRY = 'retry';

    public const string ACTION_APPLY_YAML_CHANGES = 'apply-yaml-changes';

    public const string ACTION_REGENERATE_YAML = 'regenerate-yaml';

    public const string ACTION_UNINSTALL_PLUGIN = 'uninstall-plugin';

    public const string ACTION_INSTALL_PLUGIN = 'install-plugin';

    public function __construct(
        Request $request,
        GeneralConfig $generalConfig,
        Composer $composer,
        Plugins $plugins,
        Updates $updates,
        private readonly ProjectConfig $projectConfig,
    ) {
        parent::__construct($request, $generalConfig, $composer, $plugins, $updates);
    }

    /**
     * Renders the Config Sync page via Inertia.
     */
    #[Override]
    public function index(#[Give('Craft')] Application $craft): Response
    {
        $this->data = $this->initialData();
        $state = $this->realInitialState();
        $state['data'] = Crypt::encrypt(Json::encode($this->data));

        return Inertia::render('Updater', [
            'title' => $this->pageTitle(),
            'initialState' => $state,
            'actionPrefix' => 'config-sync',
            'returnUrl' => $this->returnUrl(),
        ])->toResponse($this->request);
    }

    /**
     * Re-kicks off the sync, after the user has had a chance to run `composer install`
     */
    public function retry(): Response
    {
        return $this->send($this->initialState());
    }

    /**
     * Applies changes in `project.yaml` to the project config.
     */
    public function applyYamlChanges(): Response
    {
        if (! empty($this->data['force'])) {
            $this->projectConfig->forceUpdate = true;
        }

        try {
            $this->projectConfig->applyExternalChanges();
        } catch (BusyResourceException|StaleResourceException $e) {
            return $this->send([
                'error' => $e->getMessage(),
                'options' => [
                    $this->finishedState(['label' => t('Cancel')]),
                    $this->actionOption(t('Try again'), self::ACTION_RETRY, ['submit' => true]),
                ],
            ]);
        }

        return $this->sendFinished();
    }

    /**
     * Regenerates `project.yaml` based on the loaded project config.
     */
    public function regenerateYaml(): Response
    {
        $this->projectConfig->regenerateExternalConfig();

        return $this->sendFinished();
    }

    public function uninstallPlugin(): Response
    {
        $handle = array_shift($this->data['uninstallPlugins']);
        $this->plugins->uninstallPlugin($handle, true);

        return $this->sendNextAction($this->nextApplyYamlAction());
    }

    public function installPlugin(): Response
    {
        $handle = array_shift($this->data['installPlugins']);
        [$success, $errorDetails] = $this->installPluginInternal($handle);

        if ($success) {
            return $this->sendNextAction($this->nextApplyYamlAction());
        }

        $info = $this->plugins->getComposerPluginInfo($handle);
        $pluginName = $info['name'] ?? "`$handle`";
        $email = $info['developerEmail'] ?? 'support@craftcms.com';

        return $this->send([
            'error' => t('An error occurred when installing {name}.', ['name' => $pluginName]),
            'errorDetails' => $errorDetails,
            'options' => [
                [
                    'label' => t('Send for help'),
                    'submit' => true,
                    'email' => $email,
                    'subject' => $pluginName.' update failure',
                ],
            ],
        ]);
    }

    #[Override]
    protected function pageTitle(): string
    {
        return t('Project Config Sync');
    }

    #[Override]
    protected function initialData(): array
    {
        $data = [
            'force' => $this->request->boolean('force'),
        ];

        // Any plugins need to be installed/uninstalled?
        $loadedConfigPlugins = array_keys($this->projectConfig->get(ProjectConfig::PATH_PLUGINS) ?? []);
        $yamlPlugins = array_keys($this->projectConfig->get(ProjectConfig::PATH_PLUGINS, true) ?? []);
        $data['installPlugins'] = array_diff($yamlPlugins, $loadedConfigPlugins);
        $data['uninstallPlugins'] = array_diff($loadedConfigPlugins, $yamlPlugins);

        // Set the return URL, if any
        if (($returnUrl = $this->findReturnUrl()) !== null) {
            $data['returnUrl'] = strip_tags($returnUrl);
        }

        return $data;
    }

    #[Override]
    protected function initialState(bool $force = false): array
    {
        $incompatibilities = [];
        $missingPlugins = [];
        $error = null;

        // Make sure schema required by config files aligns with what we have.
        $compatibilityIssues = [];
        if (! $this->projectConfig->getAreConfigSchemaVersionsCompatible($compatibilityIssues)) {
            foreach ($compatibilityIssues as $issue) {
                $incompatibilities[] = $issue['cause'];
            }
        }

        if (! empty($this->data['installPlugins'])) {
            // Make sure that all to-be-installed plugins actually exist,
            // and that they have the same schema as project.yaml
            foreach ($this->data['installPlugins'] as $handle) {
                try {
                    $plugin = $this->plugins->createPlugin($handle);
                } catch (InvalidPluginException) {
                    $plugin = null;
                }

                if (! $plugin) {
                    $missingPlugins[] = "`$handle`";
                } elseif ($plugin->schemaVersion != $this->projectConfig->get(ProjectConfig::PATH_PLUGINS.'.'.$handle.'.schemaVersion', true)) {
                    $incompatibilities[] = $plugin->name;
                }
            }
        }

        if (! empty($incompatibilities)) {
            $error = t('Your project config YAML files are expecting different versions to be installed for the following:').
                ' '.implode(', ', $incompatibilities);
        } elseif (! empty($missingPlugins)) {
            $error = t('Your project config YAML files are expecting the following plugins to be installed:').
                ' '.implode(', ', $missingPlugins);
        }

        if ($error) {
            return [
                'error' => $error."\n\n".t('Try running `composer install` from your terminal to resolve.'),
                'options' => [
                    $this->finishedState(['label' => t('Cancel')]),
                    $this->actionOption(t('Try again'), self::ACTION_RETRY, ['submit' => true]),
                ],
            ];
        }

        // Is the loaded project config newer than project.yaml?
        $configModifiedTime = $this->projectConfig->get('dateModified');
        $yamlModifiedTime = $this->projectConfig->get('dateModified', true);

        if ($configModifiedTime > $yamlModifiedTime) {
            return [
                'error' => t('The loaded project config has more recent changes than your project config YAML files.'),
                'options' => [
                    $this->actionOption(t('Use the loaded project config'), self::ACTION_REGENERATE_YAML, ['submit' => true]),
                    $this->actionOption(t('Use YAML files'), $this->nextApplyYamlAction(), [
                        'submit' => true,
                    ]),
                ],
            ];
        }

        return $this->actionState($this->nextApplyYamlAction());
    }

    #[Override]
    protected function postComposerInstallState(): array
    {
        throw new RuntimeException('postComposerInstallState() is not supported by '.self::class);
    }

    #[Override]
    protected function returnUrl(): string
    {
        return URL::cpUrl($this->data['returnUrl'] ?? $this->generalConfig->getPostCpLoginRedirect());
    }

    #[Override]
    protected function actionStatus(string $action): string
    {
        switch ($action) {
            case self::ACTION_RETRY:
                return t('Trying again…');
            case self::ACTION_APPLY_YAML_CHANGES:
                return t('Applying changes from the project config YAML files…');
            case self::ACTION_REGENERATE_YAML:
                return t('Regenerating project config YAML files from the loaded project config…');
            case self::ACTION_UNINSTALL_PLUGIN:
                $handle = Arr::first($this->data['uninstallPlugins']);

                return t('Uninstalling {name}', [
                    'name' => $this->pluginName($handle),
                ]);
            case self::ACTION_INSTALL_PLUGIN:
                $handle = Arr::first($this->data['installPlugins']);

                return t('Installing {name}', [
                    'name' => $this->pluginName($handle),
                ]);
            default:
                return parent::actionStatus($action);
        }
    }

    /**
     * Returns the next action that should be run for applying new project.yaml changes.
     */
    private function nextApplyYamlAction(): string
    {
        if (! empty($this->data['installPlugins'])) {
            return self::ACTION_INSTALL_PLUGIN;
        }

        if (! empty($this->data['uninstallPlugins'])) {
            return self::ACTION_UNINSTALL_PLUGIN;
        }

        return self::ACTION_APPLY_YAML_CHANGES;
    }

    private function pluginName(string $handle): string
    {
        return $this->plugins->getAllPluginInfo()[$handle]['name'] ?? $handle;
    }
}
