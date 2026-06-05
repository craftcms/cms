<?php

declare(strict_types=1);

namespace CraftCms\Cms\Cp\Data;

use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/**
 * The shape of the `window.Craft` configuration object injected into the CP.
 *
 * This is the single source of truth for all config values available on the
 * client. Generated from {@see \CraftCms\Cms\Cp\Cp::config()}.
 */
#[TypeScript]
class CraftConfigData
{
    // ── Always-present keys ──────────────────────────────────────────────────

    public int $Solo;

    public int $Team;

    public int $Pro;

    public int $Enterprise;

    public string $actionTrigger;

    public string $actionUrl;

    /** @var array<string, string> */
    public array $asciiCharMap;

    public string $baseApiUrl;

    public string $baseSiteUrl;

    public string $baseUrl;

    public string $clientOs;

    /** @var array<string, mixed> */
    public array $datepickerOptions;

    /** @var array<string, mixed> */
    public array $defaultCookieOptions;

    /** @var array<string, array<string, string[]>> */
    public array $fileKinds;

    public string $language;

    public string $left;

    public int $maxPasswordLength;

    public int $minPasswordLength;

    public string $orientation;

    public int $pageNum;

    public string $pageTrigger;

    public string $path;

    /** @var string[] */
    public array $registeredAssetBundles;

    /** @var string[] */
    public array $registeredJsFiles;

    public string $right;

    public string $systemUid;

    /** @var array<string, mixed> */
    public array $timepickerOptions;

    public string $timezone;

    public string $tokenParam;

    /** @var object|array<string, array<string, string>> */
    public object|array $translations;

    public bool $useEmailAsUsername;

    // ── CP-request keys ──────────────────────────────────────────────────────

    /** @var array<int, array<string, mixed>>|null */
    public ?array $announcements;

    public ?string $baseCpUrl;

    public ?string $cpTrigger;

    // ── Auth/session keys ────────────────────────────────────────────────────

    public string $csrfTokenName;

    public string $csrfTokenValue;

    // ── Authenticated-user keys ──────────────────────────────────────────────

    public ?bool $allowAdminChanges;

    public ?bool $allowUpdates;

    public ?bool $allowUppercaseInSlug;

    public ?bool $autosaveDrafts;

    /** @var array<string, mixed>|null */
    public ?array $apiParams;

    public ?string $appId;

    public ?bool $autofocusPreferred;

    public ?bool $canAccessQueueManager;

    /** @var string[]|null */
    public ?array $dataAttributes;

    /** @var array<string, mixed>|null */
    public ?array $defaultIndexCriteria;

    public ?bool $disableAutofocus;

    public ?int $edition;

    /** @var array<string, string[]>|null */
    public ?array $elementTypeNames;

    public ?int $elevatedSessionDuration;

    /** @var string[]|null */
    public ?array $fieldsWithoutContent;

    public ?string $handleCasing;

    /** @var array{host: string, port: int, auth?: array{username?: string, password?: string}, protocol?: string}|null */
    public ?array $httpProxy;

    public ?bool $isImagick;

    public ?bool $isMultiSite;

    public ?bool $limitAutoSlugsToAscii;

    public ?int $maxUploadSize;

    public ?int $notificationDuration;

    public ?string $notificationPosition;

    public ?string $slideoutPosition;

    /** @var array<string, mixed>|false|null */
    public array|false|null $previewIframeResizerOptions;

    public ?int $primarySiteId;

    public ?string $primarySiteLanguage;

    /** @var array<int, array<string, mixed>>|null */
    public ?array $publishableSections;

    public ?bool $runQueueAutomatically;

    public ?int $siteId;

    /** @var array<int, array{id: int, name: string, handle: string, uid: string, language: string, enabled: bool, primary: bool}>|null */
    public ?array $sites;

    public ?string $siteToken;

    public ?string $slugWordSeparator;

    public ?string $userEmail;

    public ?bool $userHasPasskeys;

    public ?int $userId;

    public ?bool $userIsAdmin;

    public ?string $username;

    // ── Merged from Cp::config() ─────────────────────────────────────────────

    public ?string $cpLogoUrl;

    public ?string $cpUrl;

    public ?string $defaultCpLocale;

    public ?int $rememberedUserSessionDuration;
}
