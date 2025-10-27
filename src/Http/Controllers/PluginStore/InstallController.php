<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Controllers\PluginStore;

use Craft;
use CraftCms\Cms\Config\GeneralConfig;
use CraftCms\Cms\Http\Controllers\BaseUpdaterController;
use CraftCms\Cms\Plugin\Plugins;
use CraftCms\Cms\Support\Composer;
use CraftCms\Cms\Updates\Updates;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

use function CraftCms\Cms\t;

/**
 * @internal
 */
final class InstallController extends BaseUpdaterController
{
    public const string ACTION_CRAFT_INSTALL = 'craft-install';

    public const string ACTION_ENABLE = 'enable';

    public const string ACTION_MIGRATE = 'migrate';

    public function __construct(
        Request $request,
        GeneralConfig $generalConfig,
        Composer $composer,
        Plugins $plugins,
        Updates $updates,
    ) {
        parent::__construct($request, $generalConfig, $composer, $plugins, $updates);

        if (! $this->generalConfig->allowUpdates) {
            abort(403, 'Installation of plugins from the Plugin Store is disabled.');
        }
    }

    public function craftInstall(): Response
    {
        [$success, $errorDetails] = $this->installPluginInternal($this->data['handle'], $this->data['edition']);

        if ($success) {
            return $this->sendFinished();
        }

        $info = $this->plugins->getComposerPluginInfo($this->data['handle']);
        $pluginName = $info['name'] ?? $this->data['packageName'];

        return $this->send([
            'error' => t('{name} has been added, but an error occurred when installing it.', ['name' => $pluginName]),
            'errorDetails' => $errorDetails,
            'options' => [
                $this->finishedState([
                    'label' => t('Leave it uninstalled'),
                ]),
                $this->actionOption(t('Remove it'), self::ACTION_COMPOSER_REMOVE),
                [
                    'label' => t('Troubleshoot'),
                    'url' => 'https://craftcms.com/knowledge-base/failed-updates',
                ],
            ],
        ]);
    }

    /**
     * Enables the plugin. Called if the plugin was already Craft-installed
     * before being installed from the Plugin Store, but it was disabled.
     */
    public function enable(): Response
    {
        $this->plugins->enablePlugin($this->data['handle']);

        return $this->sendNextAction(self::ACTION_MIGRATE);
    }

    /**
     * Updates the plugin. Called if the plugin was already Craft-installed
     * before being installed from the Plugin Store.
     */
    public function migrate(): Response
    {
        return $this->runMigrations([$this->data['handle']]) ?? $this->sendFinished();
    }

    /**
     * {@inheritdoc}
     */
    #[\Override]
    protected function pageTitle(): string
    {
        return t('Plugin Installer');
    }

    /**
     * {@inheritdoc}
     */
    #[\Override]
    protected function initialData(): array
    {
        $this->request->validate([
            'packageName' => ['required', 'string'],
            'handle' => ['required', 'string'],
            'edition' => ['required', 'string'],
            'version' => ['required', 'string'],
            'licenseKey' => ['nullable', 'string'],
        ]);

        $packageName = strip_tags((string) $this->request->input('packageName'));
        $handle = strip_tags((string) $this->request->input('handle'));
        $edition = strip_tags((string) $this->request->input('edition'));
        $version = strip_tags((string) $this->request->input('version'));
        $licenseKey = $this->request->input('licenseKey');

        if (
            ($returnUrl = $this->findReturnUrl()) !== null &&
            ! in_array($returnUrl, ['plugin-store', 'settings/plugins'], true)
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
     * {@inheritdoc}
     */
    #[\Override]
    protected function actionStatus(string $action): string
    {
        return match ($action) {
            self::ACTION_CRAFT_INSTALL => t('Installing the plugin…'),
            self::ACTION_ENABLE => t('Enabling the plugin…'),
            self::ACTION_MIGRATE => t('Updating the plugin…'),
            default => parent::actionStatus($action),
        };
    }

    /**
     * {@inheritdoc}
     */
    #[\Override]
    protected function initialState(bool $force = false): array
    {
        // Make sure we can find composer.json
        if (! $this->ensureComposerJson()) {
            return $this->noComposerJsonState();
        }

        return $this->actionState(self::ACTION_COMPOSER_INSTALL);
    }

    /**
     * {@inheritdoc}
     */
    #[\Override]
    protected function postComposerInstallState(): array
    {
        // Was this after a remove?
        if ($this->data['removed']) {
            return $this->actionState(self::ACTION_FINISH, [
                'status' => t('The plugin was removed successfully.'),
            ]);
        }

        // Is the plugin already Craft-installed?
        if ($this->plugins->isPluginInstalled($this->data['handle'])) {
            // Is it disabled?
            if (! $this->plugins->isPluginEnabled($this->data['handle'])) {
                return $this->actionState(self::ACTION_ENABLE);
            }

            return $this->actionState(self::ACTION_MIGRATE);
        }

        return $this->actionState(self::ACTION_CRAFT_INSTALL);
    }

    /**
     * {@inheritdoc}
     */
    #[\Override]
    protected function sendFinished(array $state = []): Response
    {
        // Set the license key
        if ($this->data['licenseKey'] !== null) {
            try {
                $this->plugins->setPluginLicenseKey($this->data['handle'], $this->data['licenseKey']);
            } catch (Throwable $e) {
                Log::error("Could not set the license key on {$this->data['handle']}: {$e->getMessage()}", [__METHOD__]);
                report($e);
            }
        }

        return parent::sendFinished($state);
    }

    /**
     * {@inheritdoc}
     */
    #[\Override]
    protected function returnUrl(): string
    {
        return $this->data['returnUrl'] ?? 'plugin-store';
    }
}
