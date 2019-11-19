<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\helpers;

use Composer\Repository\PlatformRepository;
use Craft;
use craft\errors\InvalidLicenseKeyException;
use yii\base\Exception;

/**
 * Craftnet API helper.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.3.16
 * @internal
 */
abstract class Api
{
    /**
     * Returns the headers that should be sent with API requests.
     *
     * @return array
     */
    public static function headers(): array
    {
        $headers = [
            'Accept' => 'application/json',
            'X-Craft-System' => 'craft:' . Craft::$app->getVersion() . ';' . strtolower(Craft::$app->getEditionName()),
        ];

        // platform
        $platform = [];
        foreach (self::platformVersions() as $name => $version) {
            $platform[] = "{$name}:{$version}";
        }
        $headers['X-Craft-Platform'] = implode(',', $platform);

        // request info
        $request = Craft::$app->getRequest();
        if (!$request->getIsConsoleRequest()) {
            if (($host = $request->getHostInfo()) !== null) {
                $headers['X-Craft-Host'] = $host;
            }
            if (($ip = $request->getUserIP(FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) !== null) {
                $headers['X-Craft-User-Ip'] = $ip;
            }
        }

        // email
        if (($user = Craft::$app->getUser()->getIdentity()) !== null) {
            $headers['X-Craft-User-Email'] = $user->email;
        }

        // Craft license
        $headers['X-Craft-License'] = App::licenseKey() ?? (defined('CRAFT_LICENSE_KEY') ? '😱' : '🙏');

        // plugin info
        $pluginLicenses = [];
        $pluginsService = Craft::$app->getPlugins();
        foreach ($pluginsService->getAllPluginInfo() as $pluginHandle => $pluginInfo) {
            if ($pluginInfo['isInstalled']) {
                $headers['X-Craft-System'] .= ",plugin-{$pluginHandle}:{$pluginInfo['version']};{$pluginInfo['edition']}";
                try {
                    $licenseKey = $pluginsService->getPluginLicenseKey($pluginHandle);
                } catch (InvalidLicenseKeyException $e) {
                    $licenseKey = null;
                }
                if ($licenseKey !== null) {
                    $pluginLicenses[] = "{$pluginHandle}:{$licenseKey}";
                }
            }
        }
        if (!empty($pluginLicenses)) {
            $headers['X-Craft-Plugin-Licenses'] = implode(',', $pluginLicenses);
        }

        return $headers;
    }

    /**
     * Returns platform info.
     *
     * @param bool $useComposerOverrides Whether to factor in any `config.platform` overrides
     * @return array
     */
    public static function platformVersions(bool $useComposerOverrides = false): array
    {
        // Let Composer's PlatformRepository do most of the work
        $overrides = [];
        if ($useComposerOverrides) {
            try {
                $jsonPath = Craft::$app->getComposer()->getJsonPath();
                $config = Json::decode(file_get_contents($jsonPath));
                $overrides = $config['config']['platform'] ?? [];
            } catch (Exception $e) {
                // couldn't locate composer.json - NBD
            }
        }
        $repo = new PlatformRepository([], $overrides);

        $versions = [];
        foreach ($repo->getPackages() as $package) {
            $versions[$package->getName()] = $package->getPrettyVersion();
        }

        // Also include the DB driver/version
        $db = Craft::$app->getDb();
        $versions[$db->getDriverName()] = $db->getVersion();

        return $versions;
    }
}
