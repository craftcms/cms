<?php

declare(strict_types=1);

namespace CraftCms\Cms\Support;

use Craft;
use CraftCms\Cms\Support\DateTimeHelper;
use CraftCms\Cms\Cms;
use CraftCms\Cms\Edition;
use CraftCms\Cms\License\License;
use CraftCms\Cms\Plugin\Exceptions\InvalidLicenseKeyException;
use CraftCms\Cms\Plugin\Plugins;
use CraftCms\Cms\Shared\Enums\LicenseKeyStatus;
use CraftCms\Cms\User\Models\User;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\RequestOptions;
use Illuminate\Cache\Repository;
use Illuminate\Container\Attributes\Singleton;
use Illuminate\Database\Connection;
use Illuminate\Foundation\Application;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use Imagick;
use Throwable;

use function CraftCms\Cms\normalizeVersion;

/**
 * The API service provides APIs for calling the Craft API (api.craftcms.com).
 *
 * An instance of the service is available via `app(\CraftCms\Cms\Support\Api::class)`.
 *
 *
 * @internal
 */
#[Singleton]
class Api
{
    private static string $craftApiEndpoint;

    private static string $craftIdEndpoint;

    public function __construct(
        private readonly Application $app,
        private readonly Composer $composer,
        private readonly Connection $db,
        private readonly Repository $cache,
        private readonly Plugins $plugins,
        private readonly License $license,
        public array $apiParams = [],
    ) {}

    public static function craftApiEndpoint(): string
    {
        return self::$craftApiEndpoint ??= Env::get('CRAFT_API_ENDPOINT', 'https://api.craftcms.com/v1');
    }

    public static function craftIdEndpoint(): string
    {
        return self::$craftIdEndpoint ??= Env::get('CRAFT_ID_ENDPOINT', 'https://console.craftcms.com');
    }

    /**
     * Returns info about the current Craft license.
     *
     * @param  string[]  $include
     *
     * @throws GuzzleException if the API gave a non-2xx response
     */
    public function getLicenseInfo(array $include = [], array $headers = []): array
    {
        return $this->request('GET', 'cms-licenses', [
            'query' => ['include' => implode(',', $include)],
            'headers' => $headers,
        ])->json('license');
    }

    /**
     * Checks for Craft and plugin updates.
     *
     * @param  string[]  $maxVersions  The maximum versions that should be allowed
     */
    public function getUpdates(array $maxVersions = []): array
    {
        if ($maxVersions) {
            $maxVersionsStr = Collection::make($maxVersions)
                ->map(fn ($version, $name) => "$name:$version")
                ->join(',');

            $options[RequestOptions::QUERY] = [
                'maxVersions' => $maxVersionsStr,
            ];
        }

        return $this->request('GET', 'updates', $options ?? [])->json();
    }

    public function request(string $method, string $uri, array $options = []): Response
    {
        // Close the PHP session in case this takes a while
        Session::save();

        try {
            $response = Http::create()
                ->baseUrl(self::craftApiEndpoint())
                ->asJson()
                ->acceptJson()
                ->withHeaders(array_merge($this->headers(), Arr::pull($options, 'headers', [])))
                ->send($method, $uri, $options);
        } catch (RequestException $e) {
            $response = $e->getResponse();

            throw $e;
        } finally {
            if (isset($response) && $response->getStatusCode() !== 500) {
                $this->processResponseHeaders($response->getHeaders());
            }
        }

        return $response;
    }

    public function headers(): array
    {
        $allowAdminChanges = Cms::config()->allowAdminChanges;

        $headers = [
            'Accept' => 'application/json',
            'X-Craft-Env' => $this->app->environment(),
            'X-Craft-System' => sprintf('craft:%s;%s', Cms::VERSION, Edition::get()->handle()),
        ];

        // platform
        $headers['X-Craft-Platform'] = $this->platformVersions()
            ->map(fn ($version, $name) => "$name:$version")
            ->join(',');

        // request info
        if (! $this->app->runningInConsole()) {
            $headers['X-Craft-Host'] = request()->host();

            if ($ip = request()->ip()) {
                $headers['X-Craft-User-Ip'] = $ip;
            }
        }

        if ($user = Auth::getUser()) {
            /** @var User $user */
            $headers['X-Craft-User-Email'] = $user->email;
        }

        // Craft license
        $headers['X-Craft-License'] = match (true) {
            ! is_null($this->license->key()) => $this->license->key(),
            defined('CRAFT_LICENSE_KEY') => '__INVALID__',
            $user && $allowAdminChanges => '__REQUEST__',
            default => null,
        };

        if (is_null($headers['X-Craft-License'])) {
            unset($headers['X-Craft-License']);
        }

        // plugin info
        $pluginLicenses = [];
        foreach ($this->plugins->getAllPluginInfo() as $pluginHandle => $pluginInfo) {
            if ($pluginInfo['isInstalled'] && ! $pluginInfo['private']) {
                $headers['X-Craft-System'] .= ",plugin-$pluginHandle:{$pluginInfo['version']};{$pluginInfo['edition']}";
                try {
                    $licenseKey = $this->plugins->getPluginLicenseKey($pluginHandle);
                } catch (InvalidLicenseKeyException) {
                    $licenseKey = '__INVALID__';
                }
                if ($licenseKey || $allowAdminChanges) {
                    $pluginLicenses[] = "$pluginHandle:".($licenseKey ?? '__REQUEST__');
                }
            }
        }
        if (! empty($pluginLicenses)) {
            $headers['X-Craft-Plugin-Licenses'] = implode(',', $pluginLicenses);
        }

        // Craft Cloud
        $craftCloudProjectId = Env::get('CRAFT_CLOUD_PROJECT_ID');
        if ($craftCloudProjectId) {
            $headers['X-Craft-Cloud-Project-Id'] = $craftCloudProjectId;
        }

        return $headers;
    }

    /**
     * @return Collection<string, string>
     */
    public function platformVersions(): Collection
    {
        $versions = Collection::make([
            'php' => PHP::version(),
        ]);

        // loosely based on Composer\Repository\PlatformRepository::initialize()
        foreach (get_loaded_extensions() as $name) {
            if (in_array($name, ['standard', 'Core'])) {
                continue;
            }

            $extName = sprintf('ext-%s', str_replace(' ', '-', strtolower($name)));
            $extVersion = phpversion($name);
            $versions[$extName] = normalizeVersion(is_string($extVersion) ? $extVersion : '0');

            switch ($name) {
                case 'curl':
                    $versions["lib-$name"] = normalizeVersion(curl_version()['version']);
                    break;
                case 'gd':
                    $versions["lib-$name"] = normalizeVersion(GD_VERSION);
                    break;
                case 'iconv':
                    $versions["lib-$name"] = normalizeVersion(ICONV_VERSION);
                    break;
                case 'intl':
                    $versions['lib-icu'] = normalizeVersion(INTL_ICU_VERSION);
                    break;
                case 'imagick':
                    $versions["lib-$name-imagemagick"] = normalizeVersion(Imagick::getVersion()['versionString']);
                    break;
            }
        }

        // Also include the Composer PHP requirement
        $composerConfig = $this->composer->getConfig();
        if (isset($composerConfig['config']['platform']['php'])) {
            $versions['composer-php'] = $composerConfig['config']['platform']['php'];
        }

        // Also include the DB driver/version
        $versions[$this->db->getDriverName()] = normalizeVersion($this->db->getServerVersion());

        return $versions;
    }

    /**
     * Processes an API response’s headers.
     *
     * @param  string[][]|string[]  $headers  The response headers
     */
    public function processResponseHeaders(array $headers): void
    {
        // Normalize the headers
        $headers = $this->normalizeHeaders(($headers));

        // cache license info from the response
        $duration = now()->addYear();
        if (isset($headers['x-craft-allow-trials'])) {
            $cacheKey = sprintf('editionTestableDomain@%s', request()->host());
            $this->cache->put($cacheKey, (int) reset($headers['x-craft-allow-trials']), $duration);
        }

        // did we just get a new license key?
        if (isset($headers['x-craft-license'])) {
            $license = reset($headers['x-craft-license']);
            $path = $this->license->keyPath();

            //  just in case there's some race condition where two licenses were requested simultaneously...
            if ($this->license->key() !== null) {
                $i = 0;
                do {
                    $newPath = "$path.".++$i;
                } while (file_exists($newPath));
                $path = $newPath;
                Log::warning("A new license key was issued, but we already had one. Writing it to $path instead.", [__METHOD__]);
            }

            try {
                File::put($path, chunk_split($license, 50));
            } catch (Throwable $e) {
                // log and keep going
                Log::error("Could not write new license key to $path: {$e->getMessage()}\nLicense key: $license", [__METHOD__]);
                report($e);
            }
        }

        if (isset($headers['x-craft-license-domain'])) {
            $this->cache->put('licensedDomain', reset($headers['x-craft-license-domain']), $duration);
        }

        // did we just get any new plugin license keys?
        if (isset($headers['x-craft-plugin-licenses'])) {
            $pluginLicenseKeys = explode(',', reset($headers['x-craft-plugin-licenses']));
            foreach ($pluginLicenseKeys as $key) {
                [$pluginHandle, $key] = explode(':', $key);
                $this->plugins->setPluginLicenseKey($pluginHandle, $key);
            }
        }

        // license info
        if (isset($headers['x-craft-license-info'])) {
            $oldLicenseInfo = $this->cache->get(License::CACHE_KEY_LICENSE_INFO, []);
            $licenseInfo = [];
            $allCombinedInfo = array_filter(explode(',', reset($headers['x-craft-license-info'])));
            foreach ($allCombinedInfo as $combinedInfo) {
                [$handle, $combinedValues] = explode(':', $combinedInfo, 2);
                if ($combinedValues === LicenseKeyStatus::Invalid->value) {
                    // invalid license
                    $licenseStatus = LicenseKeyStatus::Invalid->value;
                    $licenseId = $licenseEdition = $timestamp = null;
                } else {
                    [$licenseId, $licenseEdition, $licenseStatus] = explode(';', $combinedValues, 3);
                    if (
                        isset($oldLicenseInfo[$handle]) &&
                        $licenseId == $oldLicenseInfo[$handle]['id'] &&
                        $licenseEdition === $oldLicenseInfo[$handle]['edition'] &&
                        $licenseStatus === $oldLicenseInfo[$handle]['status']
                    ) {
                        $timestamp = $oldLicenseInfo[$handle]['timestamp'];
                    } else {
                        $timestamp = DateTimeHelper::currentTimeStamp();
                    }
                }
                $licenseInfo[$handle] = [
                    'id' => $licenseId,
                    'edition' => $licenseEdition,
                    'status' => $licenseStatus,
                    'timestamp' => $timestamp,
                ];
            }

            $this->cache->put(License::CACHE_KEY_LICENSE_INFO, $licenseInfo, $duration);
            if ($this->app->runningInConsole()) {
                $this->cache->forget(License::CACHE_KEY_LICENSE_INFO_HOST);
            } else {
                $this->cache->put(License::CACHE_KEY_LICENSE_INFO_HOST, request()->host(), $duration);
            }
        }
    }

    /**
     * Normalizes the header names by converting them to lowercase and ensuring their values are arrays
     *
     * @param  string[][]|string[]  $headers
     * @return string[][]
     */
    private function normalizeHeaders(array $headers): array
    {
        $normalizedHeaders = [];

        foreach ($headers as $name => $value) {
            $normalizedHeaders[strtolower((string) $name)] = (array) $value;
        }

        return $normalizedHeaders;
    }
}
