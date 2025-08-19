<?php

namespace CraftCms\Cms\Http\Controllers\Plugins;

use Craft;
use craft\web\Application;
use craft\web\assets\plugins\PluginsAsset;
use CraftCms\Cms\Config\GeneralConfig;
use CraftCms\Cms\Http\RespondsWithFlash;
use CraftCms\Cms\Plugin\Contracts\PluginInterface;
use CraftCms\Cms\Plugin\Plugins;
use Illuminate\Container\Attributes\Give;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/* @since 6.0.0 */
final readonly class PluginsController
{
    use RespondsWithFlash;

    public function __construct(
        protected Plugins $pluginsService,
        protected GeneralConfig $generalConfig,
        #[Give('Craft')] protected Application $craft,
    ) {}

    public function index(): string
    {
        $view = $this->craft->getView();
        $view->registerAssetBundle(PluginsAsset::class);

        $info = $this->pluginsService
            ->getAllPluginInfo()
            ->sortBy([
                ['isEnabled', 'desc'],
                ['isInstalled', 'desc'],
                ['name', 'asc'],
            ]);

        return $view->renderPageTemplate('settings/plugins/_index.twig', [
            'info' => $info,
            'disabledPlugins' => $this->generalConfig->disabledPlugins,
            'readOnly' => ! $this->generalConfig->allowAdminChanges,
        ]);
    }

    public function install(Request $request): Response
    {
        $data = $request->validate([
            'pluginHandle' => ['required', 'string'],
            'edition' => ['nullable', 'string'],
        ]);

        $success = $this->pluginsService->installPlugin(
            handle: $data['pluginHandle'],
            edition: $data['edition'] ?? null,
        );

        return $success
            ? $this->asSuccess(Craft::t('app', 'Plugin installed.'))
            : $this->asFailure(Craft::t('app', 'Couldn’t install plugin.'));
    }

    public function switchEdition(Request $request): Response
    {
        $data = $request->validate([
            'pluginHandle' => ['required', 'string'],
            'edition' => ['required', 'string'],
        ]);

        $this->pluginsService->switchEdition($data['pluginHandle'], $data['edition']);

        return $this->asSuccess(Craft::t('app', 'Plugin edition changed.'));
    }

    public function uninstall(Request $request): Response
    {
        $pluginHandle = $request->validate([
            'pluginHandle' => ['required', 'string'],
        ])['pluginHandle'];

        $success = $this->pluginsService->uninstallPlugin($pluginHandle);

        return $success ?
            $this->asSuccess(Craft::t('app', 'Plugin uninstalled.')) :
            $this->asFailure(Craft::t('app', 'Couldn’t uninstall plugin.'));
    }

    public function enable(Request $request): Response
    {
        $pluginHandle = $request->validate([
            'pluginHandle' => ['required', 'string'],
        ])['pluginHandle'];

        $success = $this->pluginsService->enablePlugin($pluginHandle);

        return $success ?
            $this->asSuccess(Craft::t('app', 'Plugin enabled.')) :
            $this->asFailure(Craft::t('app', 'Couldn’t enable plugin.'));
    }

    public function disable(Request $request): Response
    {
        $pluginHandle = $request->validate([
            'pluginHandle' => ['required', 'string'],
        ])['pluginHandle'];

        $success = $this->pluginsService->disablePlugin($pluginHandle);

        return $success ?
            $this->asSuccess(Craft::t('app', 'Plugin disabled.')) :
            $this->asFailure(Craft::t('app', 'Couldn’t disable plugin.'));
    }

    public function editSettings(string $handle, ?PluginInterface $plugin = null): mixed
    {
        if ($plugin === null && ($plugin = $this->pluginsService->getPlugin($handle)) === null) {
            abort(404, 'Plugin not found.');
        }

        if (! $this->generalConfig->allowAdminChanges) {
            if (! $plugin->hasReadOnlyCpSettings) {
                abort(403, 'Administrative changes are disallowed in this environment.');
            }

            return $plugin->getReadOnlySettingsResponse();
        }

        $response = $plugin->getSettingsResponse();

        if ($response instanceof \craft\web\Response) {
            $response->send();
            $response = $response->getIlluminateResponse();
        }

        return $response;
    }

    public function saveSettings(Request $request): Response
    {
        $data = $request->validate([
            'pluginHandle' => ['required', 'string'],
            'settings' => ['nullable', 'array'],
        ]);

        $plugin = $this->pluginsService->getPlugin($data['pluginHandle']);

        abort_if(is_null($plugin), 404, 'Plugin not found.');

        $success = $this->pluginsService->savePluginSettings($plugin, $data['settings']);

        if ($success) {
            return $this->asSuccess(Craft::t('app', 'Plugin settings saved.'));
        }

        return $this->editSettings($data['pluginHandle'], $plugin);
    }
}
