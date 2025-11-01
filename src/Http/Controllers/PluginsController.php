<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Controllers;

use craft\web\Application;
use craft\web\assets\plugins\PluginsAsset;
use CraftCms\Cms\Config\GeneralConfig;
use CraftCms\Cms\Http\RespondsWithFlash;
use CraftCms\Cms\Plugin\Contracts\PluginInterface;
use CraftCms\Cms\Plugin\Plugins;
use Illuminate\Container\Attributes\Give;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

use function CraftCms\Cms\t;

/* @since 6.0.0 */
final readonly class PluginsController
{
    use RespondsWithFlash;

    public function __construct(
        protected Plugins $plugins,
        protected GeneralConfig $generalConfig,
        #[Give('Craft')] protected Application $craft,
    ) {}

    public function index(): string
    {
        $view = $this->craft->getView();
        $view->registerAssetBundle(PluginsAsset::class);

        $info = $this->plugins
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
        $request->validate([
            'pluginHandle' => ['required', 'string'],
            'edition' => ['nullable', 'string'],
        ]);

        $success = $this->plugins->installPlugin(
            handle: $request->input('pluginHandle'),
            edition: $request->input('edition'),
        );

        return $success
            ? $this->asSuccess(t('Plugin installed.'))
            : $this->asFailure(t('Couldn’t install plugin.'));
    }

    public function switchEdition(Request $request): Response
    {
        $request->validate([
            'pluginHandle' => ['required', 'string'],
            'edition' => ['required', 'string'],
        ]);

        $this->plugins->switchEdition($request->input('pluginHandle'), $request->input('edition'));

        return $this->asSuccess(t('Plugin edition changed.'));
    }

    public function uninstall(Request $request): Response
    {
        $request->validate([
            'pluginHandle' => ['required', 'string'],
        ]);

        $success = $this->plugins->uninstallPlugin($request->input('pluginHandle'));

        return $success ?
            $this->asSuccess(t('Plugin uninstalled.')) :
            $this->asFailure(t('Couldn’t uninstall plugin.'));
    }

    public function enable(Request $request): Response
    {
        $pluginHandle = $request->validate([
            'pluginHandle' => ['required', 'string'],
        ])['pluginHandle'];

        $success = $this->plugins->enablePlugin($pluginHandle);

        return $success ?
            $this->asSuccess(t('Plugin enabled.')) :
            $this->asFailure(t('Couldn’t enable plugin.'));
    }

    public function disable(Request $request): Response
    {
        $request->validate([
            'pluginHandle' => ['required', 'string'],
        ]);

        $success = $this->plugins->disablePlugin($request->input('pluginHandle'));

        return $success ?
            $this->asSuccess(t('Plugin disabled.')) :
            $this->asFailure(t('Couldn’t disable plugin.'));
    }

    public function editSettings(string $handle, ?PluginInterface $plugin = null): mixed
    {
        abort_if($plugin === null && ($plugin = $this->plugins->getPlugin($handle)) === null, 404, 'Plugin not found.');

        if (! $this->generalConfig->allowAdminChanges) {
            abort_unless($plugin->hasReadOnlyCpSettings, 403, 'Administrative changes are disallowed in this environment.');

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
        $request->validate([
            'pluginHandle' => ['required', 'string'],
            'settings' => ['nullable', 'array'],
        ]);

        $plugin = $this->plugins->getPlugin($request->input('pluginHandle'));

        abort_if(is_null($plugin), 404, 'Plugin not found.');

        $success = $this->plugins->savePluginSettings($plugin, $request->input('settings', []));

        return $success
            ? $this->asSuccess(t('Plugin settings saved.'))
            : $this->editSettings($request->input('pluginHandle'), $plugin);
    }
}
