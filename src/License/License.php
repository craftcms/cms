<?php

declare(strict_types=1);

namespace CraftCms\Cms\License;

use Craft;
use craft\helpers\UrlHelper;
use CraftCms\Aliases\Aliases;
use CraftCms\Cms\Cms;
use CraftCms\Cms\Config\GeneralConfig;
use CraftCms\Cms\Edition;
use CraftCms\Cms\License\Data\LicenseData;
use CraftCms\Cms\Plugin\Exceptions\InvalidPluginException;
use CraftCms\Cms\Plugin\Plugins;
use CraftCms\Cms\Shared\Enums\LicenseKeyStatus;
use CraftCms\Cms\Support\Api;
use CraftCms\Cms\Support\Html;
use CraftCms\Cms\Support\Json as JsonHelper;
use CraftCms\Cms\Support\Str;
use CraftCms\Cms\Updates\Updates;
use CraftCms\Cms\User\Elements\User;
use Illuminate\Auth\AuthManager;
use Illuminate\Container\Attributes\Singleton;
use Illuminate\Support\Facades\Cache;

use function CraftCms\Cms\t;

/**
 * @internal
 */
#[Singleton]
final readonly class License
{
    public const string CACHE_KEY_LICENSE_INFO = 'licenseInfo';

    public const string CACHE_KEY_LICENSE_INFO_HOST = 'licenseInfoHost';

    public function __construct(
        private GeneralConfig $generalConfig,
        private AuthManager $auth,
        private Plugins $plugins,
    ) {}

    public function key(): ?string
    {
        if (defined('CRAFT_LICENSE_KEY')) {
            $licenseKey = CRAFT_LICENSE_KEY;
        } else {
            $path = $this->keyPath();

            // Check to see if the key exists
            if (! is_file($path)) {
                return null;
            }

            $licenseKey = file_get_contents($path);
        }

        $licenseKey = trim((string) preg_replace('/[\r\n]+/', '', (string) $licenseKey));

        if (strlen($licenseKey) !== 250) {
            return null;
        }

        return $licenseKey;
    }

    /**
     * Returns the path to the license key file.
     */
    public function keyPath(): string
    {
        if (defined('CRAFT_LICENSE_KEY_PATH')) {
            return CRAFT_LICENSE_KEY_PATH;
        }

        return config_path('license.key');
    }

    /**
     * Returns the license_shun cookie name.
     */
    public function shunCookieName(): string
    {
        return sprintf('%s_license_shun', md5('Craft.'.User::class.'.'.config('app.name')));
    }

    /**
     * Returns a hash of the given licensing issues.
     */
    public function issuesHash(array $issues): string
    {
        $resolveItems = array_map(fn ($issue) => JsonHelper::encode($issue[2]), $issues);

        sort($resolveItems);

        return md5(implode('', $resolveItems));
    }

    /**
     * Returns all known licensing issues.
     *
     *
     * @return array{0:string,1:string,2:array|null}[]
     */
    public function issues(array|bool|null $only = null, bool $fetch = false): array
    {
        // maintain BC support for $withUnresolvables
        // todo: remove support for true/false
        if (is_bool($only)) {
            if ($only) {
                $only = null;
            } else {
                $only = [
                    LicenseKeyStatus::Trial->value,
                    LicenseKeyStatus::Astray->value,
                    'wrong_edition',
                ];
            }
        }

        $only ??= [
            LicenseKeyStatus::Invalid->value,
            LicenseKeyStatus::Trial->value,
            LicenseKeyStatus::Mismatched->value,
            LicenseKeyStatus::Astray->value,
            'wrong_edition',
        ];

        $isInfoCached = Cache::has(self::CACHE_KEY_LICENSE_INFO) && app(Updates::class)->isUpdateInfoCached();

        if (! $isInfoCached) {
            if (! $fetch) {
                return [];
            }

            app(Updates::class)->getUpdates(true);
        }

        $issues = [];

        $allLicenseInfo = Cache::get(self::CACHE_KEY_LICENSE_INFO, []);

        foreach ($allLicenseInfo as $handle => $licenseInfo) {
            $licenseData = $this->getLicenseData($handle, $licenseInfo);

            if (! $licenseData) {
                continue;
            }

            $issues[] = match (true) {
                $licenseData->status === LicenseKeyStatus::Invalid => $this->statusIssueInvalid($licenseData, $only),
                $licenseData->status === LicenseKeyStatus::Trial => $this->statusIssueTrial($licenseData, $only),
                $licenseData->status === LicenseKeyStatus::Mismatched => $this->statusIssueMismatched($licenseData, $only),
                $licenseData->status === LicenseKeyStatus::Astray => $this->statusIssueAstray($licenseData, $only),
                $licenseData->licenseEdition !== $licenseData->currentEdition => $this->issueWrongEdition($licenseData, $only),
                default => [],
            };

            $issues = array_filter($issues);
        }

        return $issues;
    }

    private function getLicenseData(string $handle, array $licenseInfo): ?LicenseData
    {
        if ($handle === 'craft') {
            $licenseEdition = isset($licenseInfo['edition'])
                ? Edition::fromHandle($licenseInfo['edition'])
                : Edition::Solo;

            return new LicenseData(
                isCraft: true,
                id: $licenseInfo['id'],
                handle: $handle,
                name: 'Craft',
                editions: array_map(fn (Edition $edition) => $edition->handle(), Edition::cases()),
                currentEdition: Edition::get()->handle(),
                currentEditionName: Edition::get()->name,
                licenseEdition: $licenseInfo['edition'],
                licenseEditionName: $licenseEdition->name,
                version: Cms::VERSION,
                status: $licenseInfo['status'],
            );
        }

        if (! str_starts_with($handle, 'plugin-')) {
            return null;
        }

        $handle = Str::chopStart($handle, 'plugin-');

        try {
            $pluginInfo = $this->plugins->getPluginInfo($handle);
        } catch (InvalidPluginException) {
            return null;
        }

        if (! $plugin = $this->plugins->getPlugin($handle)) {
            return null;
        }

        return new LicenseData(
            isCraft: false,
            id: $licenseInfo['id'],
            handle: $handle,
            name: $plugin->name,
            editions: $plugin::editions(),
            currentEdition: $pluginInfo['edition'],
            currentEditionName: ucfirst((string) $pluginInfo['edition']),
            licenseEdition: $licenseInfo['edition'],
            licenseEditionName: ucfirst($licenseInfo['edition'] ?? 'standard'),
            version: $pluginInfo['version'],
            status: $licenseInfo['status'],
        );
    }

    private function statusIssueInvalid(LicenseData $licenseData, array|bool|null $only = null): array
    {
        if (! in_array(LicenseKeyStatus::Invalid->value, $only)) {
            return [];
        }

        // invalid license
        return [
            $licenseData->name,
            t('The {name} license is invalid.', ['name' => $licenseData->name]),
            null,
        ];
    }

    private function statusIssueTrial(LicenseData $licenseData, array|bool|null $only = null): array
    {
        if (! in_array(LicenseKeyStatus::Trial->value, $only)) {
            return [];
        }

        // trial license
        return [
            $licenseData->isMultiEdition() ? sprintf('%s %s', $licenseData->name, $licenseData->currentEditionName) : $licenseData->name,
            t('{name} requires purchase.', ['name' => $licenseData->name]),
            array_filter([
                'type' => $licenseData->isCraft ? 'cms-edition' : 'plugin-edition',
                'plugin' => ! $licenseData->isCraft ? $licenseData->handle : null,
                'licenseId' => $licenseData->id,
                'edition' => $licenseData->currentEdition,
            ]),
        ];
    }

    private function statusIssueMismatched(LicenseData $licenseData, array|bool|null $only = null): array
    {
        if (! in_array(LicenseKeyStatus::Mismatched->value, $only)) {
            return [];
        }

        $consoleUrl = rtrim(Api::craftIdEndpoint(), '/');

        if (! $licenseData->isCraft) {
            // wrong Craft install
            return [
                $licenseData->name,
                t(
                    'The {name} license is attached to a different Craft CMS license. You can <a class="go" href="{detachUrl}">detach it in Craft Console</a> or <a class="go" href="{buyUrl}">buy a new license</a>.',
                    [
                        'name' => $licenseData->name,
                        'detachUrl' => "$consoleUrl/licenses/plugins/{$licenseData->id}",
                        'buyUrl' => $this->auth->user()?->isAdmin() && $this->generalConfig->allowAdminChanges
                            ? UrlHelper::cpUrl("plugin-store/buy/$licenseData->handle/$licenseData->currentEdition")
                            : "https://plugins.craftcms.com/$licenseData->handle",
                    ]),
                null,
            ];
        }

        // wrong domain. ignore if the cache wasn't saved from the same host name we're currently on
        if (! $licenseInfoHost = Cache::get(self::CACHE_KEY_LICENSE_INFO_HOST)) {
            return [];
        }

        if (app()->runningInConsole()) {
            return [];
        }

        if (request()->getHost() !== $licenseInfoHost) {
            return [];
        }

        $licensedDomain = Cache::get('licensedDomain');
        $domainLink = Html::a($licensedDomain, "http://$licensedDomain", [
            'rel' => 'noopener',
            'target' => '_blank',
        ]);

        if (defined('CRAFT_LICENSE_KEY')) {
            $message = t('The Craft CMS license key in use belongs to {domain}', [
                'domain' => $domainLink,
            ]);
        } else {
            $keyPath = $this->keyPath();

            // If the license key path starts with the root project path, trim the project path off
            $rootPath = Aliases::get('@root');
            if (str_starts_with($keyPath, $rootPath.'/')) {
                $keyPath = substr($keyPath, strlen($rootPath) + 1);
            }

            $message = t('The Craft CMS license located at {file} belongs to {domain}.', [
                'file' => $keyPath,
                'domain' => $domainLink,
            ]);
        }

        $learnMoreLink = Html::a(
            text: t('Learn more'),
            url: 'https://craftcms.com/support/resolving-mismatched-licenses',
            options: [
                'class' => 'go',
            ],
        );

        return [
            $licenseData->name,
            "$message $learnMoreLink",
            null,
        ];
    }

    private function statusIssueAstray(LicenseData $licenseData, array|bool|null $only = null): array
    {
        if (! in_array(LicenseKeyStatus::Astray->value, $only)) {
            return [];
        }

        // updated too far
        return [
            sprintf('%s %s', $licenseData->name, $licenseData->version),
            t('{name} isn’t licensed to run version {version}.', [
                'name' => $licenseData->name,
                'version' => $licenseData->version,
            ]),
            array_filter([
                'type' => $licenseData->isCraft ? 'cms-renewal' : 'plugin-renewal',
                'plugin' => ! $licenseData->isCraft ? $licenseData->handle : null,
                'licenseId' => $licenseData->id,
            ]),
        ];
    }

    private function issueWrongEdition(LicenseData $licenseData, array|bool|null $only = null): array
    {
        if (! in_array('wrong_edition', $only)) {
            return [];
        }

        // wrong edition
        $message = t(
            '{name} is licensed for the {licenseEdition} edition, but the {currentEdition} edition is installed.',
            [
                'name' => $licenseData->name,
                'licenseEdition' => $licenseData->licenseEditionName,
                'currentEdition' => $licenseData->currentEditionName,
            ]);

        $currentEditionIdx = array_search($licenseData->currentEdition, $licenseData->editions);
        $licenseEditionIdx = array_search($licenseData->licenseEdition, $licenseData->editions);

        if ($currentEditionIdx !== false && $licenseEditionIdx !== false && $currentEditionIdx > $licenseEditionIdx) {
            return [
                $licenseData->isMultiEdition() ? sprintf('%s %s', $licenseData->name, $licenseData->currentEditionName) : $licenseData->name,
                $message,
                [
                    'type' => $licenseData->isCraft ? 'cms-edition' : 'plugin-edition',
                    'edition' => $licenseData->currentEdition,
                    'licenseId' => $licenseData->id,
                ],
            ];
        }

        return [];
    }
}
