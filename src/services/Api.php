<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\services;

use Composer\Repository\PlatformRepository;
use Composer\Semver\VersionParser;
use Craft;
use craft\base\Plugin;
use craft\errors\ApiException;
use craft\helpers\Json;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\RequestOptions;
use Psr\Http\Message\ResponseInterface;
use yii\base\Component;
use yii\base\Exception;

/**
 * The API service provides APIs for calling the Craft API (api.craftcms.com).
 * An instance of the API service is globally accessible in Craft via [[\craft\base\ApplicationTrait::getApi()|<code>Craft::$app->api</code>]].
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class Api extends Component
{
    // Properties
    // =========================================================================

    /**
     * @var Client
     */
    public $client;

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();

        if ($this->client === null) {
            $this->client = Craft::createGuzzleClient([
                'base_uri' => 'https://api.craftcms.com/v1/'
            ]);
        }
    }

    /**
     * Checks for Craft and plugin updates.
     *
     * @return array
     * @throws ApiException if the API gave a non-2xx response
     * @throws Exception if no one is logged in or there isn't a valid license key
     */
    public function getUpdates(): array
    {
        $request = Craft::$app->getRequest();
        $user = Craft::$app->getUser()->getIdentity();

        if (!$user) {
            throw new Exception('A user must be logged in to check for updates.');
        }

        $requestBody = [
            'request' => [
                'hostname' => $request->getHostName(),
                'port' => $request->getPort(),
            ],
            'user' => [
                'email' => $user->email,
            ],
            'platform' => $this->platformVersions(),
            'cms' => $this->getCmsInfo(),
        ];

        if ($ip = $request->getUserIP(FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
            $requestBody['request']['ip'] = $ip;
        }

        if (!empty($pluginInfo = $this->pluginInfo())) {
            $requestBody['plugins'] = $pluginInfo;
        }

        $response = $this->request('POST', 'updates', [
            RequestOptions::BODY => Json::encode($requestBody),
        ]);

        return Json::decode((string)$response->getBody());
    }

    /**
     * Returns optimized Composer requirements based on what’s currently installed,
     * and the package requirements that should be installed.
     *
     * @param array $install Package name/version pairs to be installed
     * @return array
     * @throws ApiException if the API gave a non-2xx response
     * @throws Exception if no one is logged in or there isn't a valid license key
     */
    public function getOptimizedComposerRequirements(array $install): array
    {
        $composerService = Craft::$app->getComposer();

        // Get the currently-installed packages, if there's a composer.lock
        $installed = [];
        if (($lockPath = $composerService->getLockPath()) !== null) {
            $lockData = Json::decode(file_get_contents($lockPath));
            if (!empty($lockData['packages'])) {
                // Get the installed package versions
                $hashes = [];
                foreach ($lockData['packages'] as $package) {
                    $installed[$package['name']] = $package['version'];

                    // Should we be including the hash as well?
                    if (strpos($package['version'], 'dev-') === 0) {
                        $hashes[$package['name']] = $package['dist']['reference'] ?? $package['source']['reference'];
                    }
                }

                // Check for aliases
                $aliases = [];
                if (!empty($lockData['aliases'])) {
                    $versionParser = new VersionParser();
                    foreach ($lockData['aliases'] as $alias) {
                        // Make sure the package is installed, we haven't already assigned an alias to this package,
                        // and the alias is for the same version as what's installed
                        if (
                            !isset($aliases[$alias['package']]) &&
                            isset($installed[$alias['package']]) &&
                            $alias['version'] === $versionParser->normalize($installed[$alias['package']])
                        ) {
                            $aliases[$alias['package']] = $alias['alias'];
                        }
                    }
                }

                // Append the hashes and aliases
                foreach ($hashes as $name => $hash) {
                    $installed[$name] .= '#'.$hash;
                }

                foreach ($aliases as $name => $alias) {
                    $installed[$name] .= ' as '.$alias;
                }
            }
        }

        $jsonPath = Craft::$app->getComposer()->getJsonPath();
        $composerConfig = Json::decode(file_get_contents($jsonPath));
        $minStability = strtolower($composerConfig['minimum-stability'] ?? 'stable');
        if ($minStability === 'rc') {
            $minStability = 'RC';
        }

        $requestBody = [
            'require' => $composerConfig['require'],
            'platform' => $this->platformVersions(true),
            'install' => $install,
            'minimum-stability' => $minStability,
            'prefer-stable' => (bool)($composerConfig['prefer-stable'] ?? false),
        ];

        if (!empty($installed)) {
            $requestBody['installed'] = $installed;
        }

        $response = $this->request('POST', 'optimize-composer-reqs', [
            RequestOptions::BODY => Json::encode($requestBody),
        ]);

        return Json::decode((string)$response->getBody());
    }

    /**
     * Returns info about the CMS.
     *
     * @return array
     * @throws Exception if there isn't a valid license key
     */
    public function getCmsInfo(): array
    {
        return [
            'version' => Craft::$app->getVersion(),
            'edition' => strtolower(Craft::$app->getEditionName()),
            'licenseKey' => $this->cmsLicenseKey(),
        ];
    }

    // Protected Methods
    // =========================================================================

    /**
     * @param string $method
     * @param string $uri
     * @param array $options
     * @return ResponseInterface
     * @throws ApiException
     */
    protected function request(string $method, string $uri, array $options = []): ResponseInterface
    {
        try {
            return $this->client->request($method, $uri, $options);
        } catch (RequestException $e) {
            throw new ApiException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * Returns platform info.
     *
     * @param bool $useComposerOverrides Whether to factor in any `config.platform` overrides
     * @return array
     */
    protected function platformVersions(bool $useComposerOverrides = false): array
    {
        $versions = [];

        // Let Composer's PlatformRepository do most of the work
        if ($useComposerOverrides) {
            $jsonPath = Craft::$app->getComposer()->getJsonPath();
            $config = Json::decode(file_get_contents($jsonPath));
            $overrides = $config['config']['platform'] ?? [];
        } else {
            $overrides = [];
        }
        $repo = new PlatformRepository([], $overrides);
        foreach ($repo->getPackages() as $package) {
            $versions[$package->getName()] = $package->getPrettyVersion();
        }

        // Also include the DB driver/version
        $db = Craft::$app->getDb();
        $versions[$db->getDriverName()] = $db->getVersion();

        return $versions;
    }

    /**
     * @return string
     * @throws Exception
     */
    protected function cmsLicenseKey(): string
    {
        $path = Craft::$app->getPath()->getLicenseKeyPath();

        // Check to see if the key exists and it's not a temp one.
        if (!is_file($path)) {
            throw new Exception("No license key found at {$path}.");
        }

        $contents = file_get_contents($path);
        if (empty($contents) || $contents === 'temp') {
            throw new Exception("Invalid license key at {$path}.");
        }

        return trim(preg_replace('/[\r\n]+/', '', $contents));
    }

    /**
     * @return array
     */
    protected function pluginInfo(): array
    {
        $info = [];
        $pluginsService = Craft::$app->getPlugins();
        /** @var Plugin[] $plugins */
        $plugins = $pluginsService->getAllPlugins();

        foreach ($plugins as $plugin) {
            $handle = $plugin->getHandle();
            $info[$handle] = [
                'version' => $plugin->getVersion(),
                'licenseKey' => $pluginsService->getPluginLicenseKey($handle),
            ];
        }

        return $info;
    }
}
