<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Controllers\PluginStore;

use CraftCms\Cms\Cms;
use CraftCms\Cms\Config\GeneralConfig;
use CraftCms\Cms\Edition;
use CraftCms\Cms\License\License;
use CraftCms\Cms\Plugin\Plugins;
use CraftCms\Cms\Support\Api;
use CraftCms\Cms\Support\Composer;
use CraftCms\Cms\Support\Facades\HtmlStack;
use CraftCms\Cms\Support\PHP;
use CraftCms\Cms\View\Enums\Position;
use CraftCms\Cms\View\LegacyAssets\InternalAssetRegistry;
use CraftCms\Cms\View\LegacyAssets\PluginStoreAsset;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Symfony\Component\HttpFoundation\Response;

use function CraftCms\Cms\craftAsset;
use function CraftCms\Cms\t;

/**
 * @internal
 *
 * @since 6.0.0
 */
readonly class PluginStoreController
{
    public function index(): InertiaResponse
    {
        return $this->render('plugin-store/Index');
    }

    public function category(int $id): InertiaResponse
    {
        return $this->render('plugin-store/Category', ['categoryId' => $id]);
    }

    public function featured(string $handle): InertiaResponse
    {
        return $this->render('plugin-store/Featured', ['handle' => $handle]);
    }

    public function developer(int $id): InertiaResponse
    {
        return $this->render('plugin-store/Developer', ['developerId' => $id]);
    }

    public function search(Request $request): InertiaResponse
    {
        return $this->render('plugin-store/Search', ['query' => trim((string) $request->query('q', ''))]);
    }

    public function plugin(string $handle, string $tab = 'overview'): InertiaResponse
    {
        return $this->render('plugin-store/Plugin', ['handle' => $handle, 'tab' => $tab]);
    }

    /**
     * Adds a plugin edition to the cart, for links from license notices.
     */
    public function buy(string $handle, ?string $edition = null): InertiaResponse
    {
        return $this->render('plugin-store/Buy', ['handle' => $handle, 'edition' => $edition]);
    }

    public function upgradeCraft(): InertiaResponse
    {
        return $this->render('plugin-store/UpgradeCraft', ['title' => t('Upgrade Craft CMS')]);
    }

    public function buyAllTrials(): InertiaResponse
    {
        return $this->render('plugin-store/Buy', ['handle' => null, 'edition' => null]);
    }

    /**
     * Serves the screens that haven't moved out of the standalone Plugin Store app yet.
     */
    public function legacy(License $license, Composer $composer, GeneralConfig $generalConfig): View
    {
        HtmlStack::jsFile('https://js.stripe.com/v2/');

        $variables = [
            'craftIdEndpoint' => Api::craftIdEndpoint(),
            'craftApiEndpoint' => Api::craftApiEndpoint(),
            'pluginStoreAppBaseUrl' => $generalConfig->cpTrigger.'/plugin-store',
            'cmsInfo' => [
                'version' => Cms::VERSION,
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

        app(InternalAssetRegistry::class)->register(PluginStoreAsset::class);

        return view('plugin-store/_index');
    }

    /**
     * @param  array<string, mixed>  $props
     */
    private function render(string $component, array $props = []): InertiaResponse
    {
        return Inertia::render($component, [
            'title' => t('Plugin Store'),
            'context' => $this->context(),
            ...$props,
        ]);
    }

    /**
     * What the store needs to know about this install to decide which plugins it can offer.
     *
     * @return array{edition: string, licensedEdition: string|null, editions: list<string>, canTestEditions: bool, phpVersion: string, composerPhpVersion: string|null, allowUpdates: bool, cmsLicenseKey: string|null, craftIdEndpoint: string}
     */
    private function context(): array
    {
        $generalConfig = app(GeneralConfig::class);

        return [
            'edition' => Edition::get()->handle(),
            'licensedEdition' => Edition::getLicensed()?->handle(),
            'editions' => array_map(fn (Edition $edition) => $edition->handle(), Edition::cases()),
            'canTestEditions' => Edition::canTest(),
            'phpVersion' => PHP::version(),
            'composerPhpVersion' => app(Composer::class)->getConfig()['config']['platform']['php'] ?? null,
            'allowUpdates' => $generalConfig->allowUpdates && $generalConfig->allowAdminChanges,
            // Ties cart items to this install's license, and checkout hands the cart to Craft Console.
            'cmsLicenseKey' => app(License::class)->key(),
            'craftIdEndpoint' => Api::craftIdEndpoint(),
        ];
    }

    public function craftData(Request $request): Response
    {
        $data = [];

        // Current user
        $data['currentUser'] = $request->craftUser()?->asElement()->email;

        // Craft license/edition info
        $data['licensedEdition'] = Edition::getLicensed()?->value;
        $data['canTestEditions'] = Edition::canTest();
        $data['CraftEdition'] = Edition::get()->value;
        $data['CraftSolo'] = Edition::Solo->value;
        $data['CraftTeam'] = Edition::Team->value;
        $data['CraftPro'] = Edition::Pro->value;
        $data['CraftEnterprise'] = Edition::Enterprise->value;

        // Logos
        $data['craftLogo'] = craftAsset('legacy/pluginstore/dist/images/craft.svg');

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
