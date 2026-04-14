<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Controllers\App;

use Craft;
use CraftCms\Cms\Plugin\Plugins;
use CraftCms\Cms\Shared\Enums\LicenseKeyStatus;
use CraftCms\Cms\Support\Api;
use CraftCms\Cms\Support\Arr;
use CraftCms\Cms\Support\Env;
use CraftCms\Cms\Support\Facades\I18N;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

use function CraftCms\Cms\t;

readonly class PluginsController
{
    public function __construct(
        private Api $api,
        private Plugins $plugins,
    ) {}

    public function getLicenseInfo(Request $request): JsonResponse
    {
        $pluginLicenses = $request->input('pluginLicenses');

        $result = $this->pluginLicenseInfo($pluginLicenses);
        $result = Arr::sort($result, 'name');

        return new JsonResponse($result);
    }

    public function updateLicense(Request $request): JsonResponse
    {
        $data = $request->validate([
            'handle' => ['required', 'string'],
            'key' => ['nullable', 'string'],
        ]);

        // Get the current key and set the new one
        $this->plugins->setPluginLicenseKey($data['handle'], $data['key'] ?? null);

        // Return the new plugin license info
        return new JsonResponse(1);
    }

    /**
     * Returns plugin license info.
     */
    private function pluginLicenseInfo(?array $pluginLicenses = null): array
    {
        $result = [];

        if ($pluginLicenses === null) {
            // Update our records and get license info from the API
            $licenseInfo = $this->api->getLicenseInfo(['plugins']);
            $pluginLicenses = $licenseInfo['pluginLicenses'] ?? [];
        }

        $allPluginInfo = $this->plugins->getAllPluginInfo();

        // Update our records & use all licensed plugins as a starting point
        if (! empty($pluginLicenses)) {
            $defaultIconUrl = Craft::$app->getAssetManager()->getPublishedUrl('@appicons/default-plugin.svg', true);
            $formatter = I18N::getFormatter();
            foreach ($pluginLicenses as $pluginLicenseInfo) {
                if (isset($pluginLicenseInfo['plugin'])) {
                    $pluginInfo = $pluginLicenseInfo['plugin'];
                    $handle = $pluginInfo['handle'];

                    // The same plugin could be associated with this Craft license more than once,
                    // so make sure this is the same license they've entered a license key for, if there is one
                    if (
                        ! isset($allPluginInfo[$handle]) ||
                        ! $allPluginInfo[$handle]['licenseKey'] ||
                        $this->plugins->normalizePluginLicenseKey(Env::parse($allPluginInfo[$handle]['licenseKey'])) === $pluginLicenseInfo['key']
                    ) {
                        $result[$handle] = [
                            'edition' => null,
                            'isComposerInstalled' => false,
                            'isInstalled' => false,
                            'isEnabled' => false,
                            'licenseKey' => $pluginLicenseInfo['key'],
                            'licensedEdition' => $pluginLicenseInfo['edition'],
                            'licenseKeyStatus' => LicenseKeyStatus::Valid->value,
                            'licenseIssues' => [],
                            'name' => $pluginInfo['name'],
                            'description' => $pluginInfo['shortDescription'],
                            'iconUrl' => $pluginInfo['icon']['url'] ?? $defaultIconUrl,
                            'documentationUrl' => $pluginInfo['documentationUrl'] ?? null,
                            'packageName' => $pluginInfo['packageName'],
                            'latestVersion' => $pluginInfo['latestVersion'],
                            'expired' => $pluginLicenseInfo['expired'],
                        ];
                        if ($pluginLicenseInfo['expired']) {
                            $result[$handle]['renewalUrl'] = $pluginLicenseInfo['renewalUrl'];
                            $result[$handle]['renewalText'] = t('Renew for {price}', [
                                'price' => $formatter->asCurrency($pluginLicenseInfo['renewalPrice'], $pluginLicenseInfo['renewalCurrency']),
                            ]);
                        }
                    }
                }
            }
        }

        // Override with info for the installed plugins
        foreach ($allPluginInfo as $handle => $pluginInfo) {
            $result[$handle] = array_merge($result[$handle] ?? [], [
                'isComposerInstalled' => true,
                'isInstalled' => $pluginInfo['isInstalled'],
                'isEnabled' => $pluginInfo['isEnabled'],
                'version' => $pluginInfo['version'],
                'hasMultipleEditions' => $pluginInfo['hasMultipleEditions'],
                'edition' => $pluginInfo['edition'],
                'licenseKey' => $this->plugins->normalizePluginLicenseKey(Env::parse($pluginInfo['licenseKey'])),
                'licensedEdition' => $pluginInfo['licensedEdition'],
                'licenseKeyStatus' => $pluginInfo['licenseKeyStatus'],
                'licenseIssues' => $pluginInfo['licenseIssues'],
                'isTrial' => $pluginInfo['isTrial'],
                'upgradeAvailable' => $pluginInfo['upgradeAvailable'],
            ]);
        }

        return $result;
    }
}
