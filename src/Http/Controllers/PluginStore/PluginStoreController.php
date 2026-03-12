<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Controllers\PluginStore;

use Craft;
use craft\web\Application;
use craft\web\assets\pluginstore\PluginStoreAsset;
use CraftCms\Cms\Config\GeneralConfig;
use CraftCms\Cms\Edition;
use CraftCms\Cms\License\License;
use CraftCms\Cms\Plugin\Plugins;
use CraftCms\Cms\Support\Api;
use CraftCms\Cms\Support\Composer;
use CraftCms\Cms\Support\Facades\HtmlStack;
use CraftCms\Cms\Support\PHP;
use CraftCms\Cms\View\Enums\Position;
use Illuminate\Container\Attributes\Give;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @internal
 */
readonly class PluginStoreController
{
    public function __construct(
        #[Give('Craft')] private Application $craft,
    ) {}

    public function index(License $license, Composer $composer, GeneralConfig $generalConfig): View
    {
        $view = $this->craft->getView();
        HtmlStack::jsFile('https://js.stripe.com/v2/');

        $variables = [
            'craftIdEndpoint' => Api::craftIdEndpoint(),
            'craftApiEndpoint' => Api::craftApiEndpoint(),
            'pluginStoreAppBaseUrl' => $generalConfig->cpTrigger.'/plugin-store',
            'cmsInfo' => [
                'version' => $this->craft->getVersion(),
                'edition' => Edition::get()->handle(),
            ],
            'cmsLicenseKey' => $license->key(),
            'cmsEditions' => array_map(fn (Edition $edition) => $edition->handle(), Edition::cases()),
            'phpVersion' => PHP::version(),
            'composerPhpVersion' => $composer->getConfig()['config']['platform']['php'] ?? null,
        ];

        HtmlStack::jsWithVars(
            fn ($variables) => "Object.assign(window, $variables)",
            [$variables],
            Position::Head,
        );

        $view->registerAssetBundle(PluginStoreAsset::class);

        return view('plugin-store/_index');
    }

    public function craftData(Request $request): Response
    {
        $data = [];

        // Current user
        $data['currentUser'] = $request->user()->email;

        // Craft license/edition info
        $data['licensedEdition'] = Edition::getLicensed()?->value;
        $data['canTestEditions'] = Edition::canTest();
        $data['CraftEdition'] = Edition::get()->value;
        $data['CraftSolo'] = Edition::Solo->value;
        $data['CraftTeam'] = Edition::Team->value;
        $data['CraftPro'] = Edition::Pro->value;
        $data['CraftEnterprise'] = Edition::Enterprise->value;

        // Logos
        $data['craftLogo'] = $this->craft->getAssetManager()->getPublishedUrl(
            path: '@app/web/assets/pluginstore/dist/',
            publish: true,
            filePath: 'images/craft.svg',
        );

        return new JsonResponse($data);
    }

    public function savePluginLicenseKeys(Request $request, Plugins $pluginsService): Response
    {
        $pluginLicenseKeys = $request->input('pluginLicenseKeys', []);
        $plugins = $pluginsService->getAllPlugins();

        foreach ($pluginLicenseKeys as $pluginLicenseKey) {
            if (! isset($plugins[$pluginLicenseKey['handle']])) {
                continue;
            }

            $pluginsService->setPluginLicenseKey(
                handle: $pluginLicenseKey['handle'],
                licenseKey: $pluginLicenseKey['key'],
            );
        }

        return new JsonResponse;
    }
}
