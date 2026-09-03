<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Controllers;

use CraftCms\Cms\Config\GeneralConfig;
use CraftCms\Cms\Form\FormContext;
use CraftCms\Cms\Form\FormResolver;
use CraftCms\Cms\Http\RespondsWithFlash;
use CraftCms\Cms\Http\Responses\CpScreenResponse;
use CraftCms\Cms\Plugin\Contracts\PluginInterface;
use CraftCms\Cms\Plugin\Plugins;
use CraftCms\Cms\Support\Url;
use CraftCms\Cms\View\LegacyAssets\InternalAssetRegistry;
use CraftCms\Cms\View\LegacyAssets\PluginsAsset;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use LogicException;
use Symfony\Component\HttpFoundation\Response;

use function CraftCms\Cms\t;

readonly class PluginsController
{
    use RespondsWithFlash;

    public function __construct(
        private Plugins $plugins,
        private GeneralConfig $generalConfig,
        private FormResolver $formResolver,
    ) {}

    public function index(): CpScreenResponse
    {
        app(InternalAssetRegistry::class)->register(PluginsAsset::class);

        $info = $this->plugins
            ->getAllPluginInfo()
            ->sortBy([
                ['isEnabled', 'desc'],
                ['isInstalled', 'desc'],
                ['name', 'asc'],
            ]);

        return new CpScreenResponse()
            ->title(t('Plugins'))
            ->crumbs([
                ['label' => t('Settings'), 'href' => Url::cpUrl('settings')],
                ['label' => t('Plugins')],
            ])
            ->inertiaPage('settings/Plugins', [
                'pluginInfo' => fn () => $info,
            ]);
    }

    public function install(Request $request, string $handle): Response
    {
        $request->validate([
            'edition' => ['nullable', 'string'],
        ]);

        $success = $this->plugins->installPlugin(
            handle: $handle,
            edition: $request->input('edition'),
        );

        return $success
            ? $this->asSuccess(t('Plugin installed.'))
            : $this->asFailure(t('Couldn’t install plugin.'));
    }

    public function switchEdition(Request $request, string $handle): Response
    {
        $request->validate([
            'edition' => ['required', 'string'],
        ]);

        $this->plugins->switchEdition($handle, $request->input('edition'));

        return $this->asSuccess(t('Plugin edition changed.'));
    }

    public function uninstall(string $handle): Response
    {
        $success = $this->plugins->uninstallPlugin($handle);

        return $success ?
            $this->asSuccess(t('Plugin uninstalled.')) :
            $this->asFailure(t('Couldn’t uninstall plugin.'));
    }

    public function enable(string $handle): Response
    {
        $success = $this->plugins->enablePlugin($handle);

        return $success ?
            $this->asSuccess(t('Plugin enabled.')) :
            $this->asFailure(t('Couldn’t enable plugin.'));
    }

    public function disable(string $handle): Response
    {
        $success = $this->plugins->disablePlugin($handle);

        return $success ?
            $this->asSuccess(t('Plugin disabled.')) :
            $this->asFailure(t('Couldn’t disable plugin.'));
    }

    public function editSettings(string $handle, ?PluginInterface $plugin = null): mixed
    {
        if ($plugin === null && ($plugin = $this->plugins->getPlugin($handle)) === null) {
            abort(404, 'Plugin not found.');
        }

        if (! $this->generalConfig->allowAdminChanges) {
            if (! $plugin->hasReadOnlyCpSettings) {
                abort(403, 'Administrative changes are disallowed in this environment.');
            }

            return $plugin->getReadOnlySettingsResponse();
        }

        return $plugin->getSettingsResponse();
    }

    public function saveSettings(Request $request, string $handle): Response
    {
        $request->validate([
            'settings' => ['nullable', 'array'],
        ]);

        $plugin = $this->plugins->getPlugin($handle);

        abort_if(is_null($plugin), 404, 'Plugin not found.');

        $requestClass = $plugin->getSettingsRequestClass();
        $settings = $request->input('settings', []);

        if (is_subclass_of($requestClass, FormRequest::class)) {
            $request = app($requestClass);
            $settings = $request->safe()->input('settings', []);
        }

        $success = $this->plugins->savePluginSettings($plugin, $settings);

        return $success
            ? $this->asSuccess(t('Plugin settings saved.'))
            : $this->editSettings($handle, $plugin);
    }

    public function renderSettingsForm(Request $request, string $handle): JsonResponse
    {
        $data = $request->validate([
            'values' => ['required', 'array'],
            'scope' => ['required', 'array', 'min:1'],
            'scope.0' => ['required', 'in:settings'],
            'scope.*' => ['string'],
        ]);
        $plugin = $this->plugins->getPlugin($handle);

        abort_if(is_null($plugin), 404, 'Plugin not found.');

        $scope = $data['scope'];
        $settings = $plugin->getSettings()?->validationData() ?? [];
        $settings = $scope === ['settings']
            ? $data['values']
            : data_set($settings, array_slice($scope, 1), $data['values']);
        $plugin->setSettings($settings);
        $context = new FormContext(
            namespace: 'settings',
            values: ['settings' => $settings],
            refreshable: true,
        );
        $form = $plugin->settingsForm($context);

        if ($form === null) {
            throw new LogicException("Plugin [{$plugin->handle}] must return a Form from settingsForm().");
        }

        return new JsonResponse([
            'form' => $this->formResolver->resolve($form, $context)->forScope($scope),
        ]);
    }
}
