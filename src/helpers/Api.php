<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\helpers;

use Composer\Repository\PlatformRepository;
use Craft;
use craft\enums\LicenseKeyStatus;
use craft\errors\InvalidLicenseKeyException;
use craft\errors\InvalidPluginException;

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
        if ($licenseKey = App::licenseKey()) {
            $headers['X-Craft-License'] = $licenseKey;
        } else if (defined('CRAFT_LICENSE_KEY')) {
            $headers['X-Craft-License'] = '__INVALID__';
        } else if ($user) {
            $headers['X-Craft-License'] = '__REQUEST__';
        }

        // plugin info
        $pluginLicenses = [];
        $pluginsService = Craft::$app->getPlugins();
        foreach ($pluginsService->getAllPluginInfo() as $pluginHandle => $pluginInfo) {
            if ($pluginInfo['isInstalled']) {
                $headers['X-Craft-System'] .= ",plugin-{$pluginHandle}:{$pluginInfo['version']};{$pluginInfo['edition']}";
                try {
                    $licenseKey = $pluginsService->getPluginLicenseKey($pluginHandle);
                } catch (InvalidLicenseKeyException $e) {
                    $licenseKey = '__INVALID__';
                }
                $pluginLicenses[] = "$pluginHandle:" . ($licenseKey ?? '__REQUEST__');
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
        if ($useComposerOverrides) {
            $overrides = Craft::$app->getComposer()->getConfig()['config']['platform'] ?? [];
        } else {
            $overrides = [];
        }

        $repo = new PlatformRepository([], $overrides);

        $versions = [];
        foreach ($repo->getPackages() as $package) {
            $versions[$package->getName()] = $package->getPrettyVersion();
        }

        // Also include the DB driver/version
        $db = Craft::$app->getDb();
        $versions[$db->getDriverName()] = App::normalizeVersion($db->getSchema()->getServerVersion());

        return $versions;
    }

    /**
     * Processes an API response’s headers.
     *
     * @param string[][]|string[] The response headers
     */
    public static function processResponseHeaders(array $headers)
    {
        // Normalize the headers
        $headers = self::_normalizeHeaders(($headers));

        // cache license info from the response
        $cache = Craft::$app->getCache();
        $duration = 86400;
        if (isset($headers['x-craft-allow-trials'])) {
            $cache->set('editionTestableDomain@' . Craft::$app->getRequest()->getHostName(), (bool)reset($headers['x-craft-allow-trials']), $duration);
        }
        if (isset($headers['x-craft-license-status'])) {
            $cache->set('licenseKeyStatus', reset($headers['x-craft-license-status']), $duration);
        }
        if (isset($headers['x-craft-license-domain'])) {
            $cache->set('licensedDomain', reset($headers['x-craft-license-domain']), $duration);
        }
        if (isset($headers['x-craft-license-edition'])) {
            $licensedEdition = reset($headers['x-craft-license-edition']);

            switch ($licensedEdition) {
                case 'solo':
                    $licensedEdition = Craft::Solo;
                    break;
                case 'pro':
                    $licensedEdition = Craft::Pro;
                    break;
                default:
                    Craft::error('Invalid X-Craft-License-Edition header value: ' . $licensedEdition, __METHOD__);
            }

            $cache->set('licensedEdition', $licensedEdition, $duration);
        }

        // did we just get any new plugin license keys?
        $pluginsService = Craft::$app->getPlugins();
        if (isset($headers['x-craft-plugin-licenses'])) {
            $pluginLicenseKeys = explode(',', reset($headers['x-craft-plugin-licenses']));
            foreach ($pluginLicenseKeys as $key) {
                [$pluginHandle, $key] = explode(':', $key);
                $pluginsService->setPluginLicenseKey($pluginHandle, $key);
            }
        }

        $pluginLicenseStatuses = [];
        $pluginLicenseEditions = [];
        foreach ($pluginsService->getAllPluginInfo() as $pluginHandle => $pluginInfo) {
            if ($pluginInfo['isInstalled']) {
                $pluginLicenseStatuses[$pluginHandle] = LicenseKeyStatus::Unknown;
            }
        }
        if (isset($headers['x-craft-plugin-license-statuses'])) {
            $pluginLicenseInfo = explode(',', reset($headers['x-craft-plugin-license-statuses']));
            foreach ($pluginLicenseInfo as $info) {
                [$pluginHandle, $pluginLicenseStatus] = explode(':', $info);
                $pluginLicenseStatuses[$pluginHandle] = $pluginLicenseStatus;
            }
        }
        if (isset($headers['x-craft-plugin-license-editions'])) {
            $pluginLicenseInfo = explode(',', reset($headers['x-craft-plugin-license-editions']));
            foreach ($pluginLicenseInfo as $info) {
                [$pluginHandle, $pluginLicenseEdition] = explode(':', $info);
                $pluginLicenseEditions[$pluginHandle] = $pluginLicenseEdition;
            }
        }
        foreach ($pluginLicenseStatuses as $pluginHandle => $pluginLicenseStatus) {
            $pluginLicenseEdition = $pluginLicenseEditions[$pluginHandle] ?? null;
            try {
                $pluginsService->setPluginLicenseKeyStatus($pluginHandle, $pluginLicenseStatus, $pluginLicenseEdition);
            } catch (InvalidPluginException $pluginException) {
            }
        }

        // did we just get a new license key?
        if (isset($headers['x-craft-license'])) {
            $license = reset($headers['x-craft-license']);
            $path = Craft::$app->getPath()->getLicenseKeyPath();

            //  just in case there's some race condition where two licenses were requested simultaneously...
            if (App::licenseKey() !== null) {
                $i = 0;
                do {
                    $newPath = "{$path}." . ++$i;
                } while (file_exists($newPath));
                $path = $newPath;
                Craft::warning("A new license key was issued, but we already had one. Writing it to {$path} instead.", __METHOD__);
            }

            try {
                FileHelper::writeToFile($path, chunk_split($license, 50));
            } catch (\ErrorException $err) {
                // log and keep going
                Craft::error("Could not write new license key to {$path}: {$err->getMessage()}\nLicense key: {$license}", __METHOD__);
                Craft::$app->getErrorHandler()->logException($err);
            }
        }
    }

    /**
     * Normalizes the header names by converting them to lowercase and ensuring their values are arrays
     *
     * @param string[][]|string[] $headers
     * @return string[][]
     */
    private static function _normalizeHeaders(array $headers): array
    {
        $normalizedHeaders = [];
        foreach ($headers as $name => $value) {
            $normalizedHeaders[strtolower($name)] = (array)$value;
        }
        return $normalizedHeaders;
    }
}
