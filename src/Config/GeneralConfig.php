<?php

declare(strict_types=1);

namespace CraftCms\Cms\Config;

use CraftCms\Cms\Support\Config as ConfigHelper;
use CraftCms\Cms\Support\Env;
use CraftCms\Cms\Support\Facades\I18N;
use CraftCms\Cms\Support\PHP;
use InvalidArgumentException;
use Override;
use RuntimeException;

/**
 * @since 6.0.0
 */
class GeneralConfig extends BaseConfig
{
    /** @deprecated in 6.0.0. [[ImagesConfig::DRIVER_AUTO]] should be used instead. */
    public const string IMAGE_DRIVER_AUTO = ImagesConfig::DRIVER_AUTO;

    /** @deprecated in 6.0.0. [[ImagesConfig::DRIVER_GD]] should be used instead. */
    public const string IMAGE_DRIVER_GD = ImagesConfig::DRIVER_GD;

    /** @deprecated in 6.0.0. [[ImagesConfig::DRIVER_IMAGICK]] should be used instead. */
    public const string IMAGE_DRIVER_IMAGICK = ImagesConfig::DRIVER_IMAGICK;

    /** @deprecated in 6.0.0. [[ImagesConfig::DRIVER_VIPS]] should be used instead. */
    public const string IMAGE_DRIVER_VIPS = ImagesConfig::DRIVER_VIPS;

    public const string CAMEL_CASE = 'camel';

    public const string PASCAL_CASE = 'pascal';

    public const string SNAKE_CASE = 'snake';

    /**
     * Settings that have been renamed in Craft 6, mapped to their new names.
     */
    protected const array RENAMED_SETTINGS = [
        'actionTrigger' => 'routing.actionTrigger',
        'activateAccountSuccessPath' => 'routing.activateAccountSuccessPath',
        'activityRetentionDuration' => 'garbageCollection.activityRetentionDuration',
        'addTrailingSlashesToUrls' => 'routing.addTrailingSlashesToUrls',
        'allowedFileExtensions' => 'assets.allowedFileExtensions',
        'allowUppercaseInSlug' => 'slugs.allowUppercase',
        'authGuard' => 'security.authGuard',
        'authPasswordBroker' => 'security.authPasswordBroker',
        'backupCommand' => 'backups.command',
        'backupCommandFormat' => 'backups.commandFormat',
        'backupOnUpdate' => 'backups.onUpdate',
        'baseCpUrl' => 'routing.baseCpUrl',
        'brokenImagePath' => 'images.brokenPath',
        'compiledTemplatesPath' => 'templates.compiledPath',
        'convertFilenamesToAscii' => 'assets.convertFilenamesToAscii',
        'cooldownDuration' => 'security.cooldownDuration',
        'cpTrigger' => 'routing.cpTrigger',
        'defaultImageQuality' => 'images.defaultQuality',
        'defaultRateLimit' => 'security.defaultRateLimit',
        'defaultRateLimitDuration' => 'security.defaultRateLimitDuration',
        'defaultTemplateExtensions' => 'templates.defaultExtensions',
        'defaultTokenDuration' => 'security.defaultTokenDuration',
        'deferPublicRegistrationPassword' => 'security.deferPublicRegistrationPassword',
        'disable2fa' => 'users.disable2fa',
        'enableGql' => 'gql.enabled',
        'enableGraphqlCaching' => 'gql.enableCaching',
        'enableGraphqlIntrospection' => 'gql.enableIntrospection',
        'enableTemplateCaching' => 'templates.enableCaching',
        'extraAllowedFileExtensions' => 'assets.extraAllowedFileExtensions',
        'extraFileKinds' => 'assets.extraFileKinds',
        'extraLastNamePrefixes' => 'users.extraLastNamePrefixes',
        'extraNameSalutations' => 'users.extraNameSalutations',
        'extraNameSuffixes' => 'users.extraNameSuffixes',
        'filenameWordSeparator' => 'assets.filenameWordSeparator',
        'gqlTypePrefix' => 'gql.typePrefix',
        'imageDriver' => 'images.driver',
        'imageEditorRatios' => 'images.editorRatios',
        'indexTemplateFilenames' => 'templates.indexFilenames',
        'invalidLoginWindowDuration' => 'security.invalidLoginWindowDuration',
        'invalidUserTokenPath' => 'routing.invalidUserTokenPath',
        'lazyGqlTypes' => 'gql.lazyTypes',
        'limitAutoSlugsToAscii' => 'slugs.limitAutoToAscii',
        'loginPath' => 'routing.loginPath',
        'logoutPath' => 'routing.logoutPath',
        'lowPriorityQueueName' => 'queue.lowPriorityName',
        'maxBackups' => 'backups.max',
        'maxGraphqlBatchSize' => 'gql.maxBatchSize',
        'maxGraphqlComplexity' => 'gql.maxComplexity',
        'maxGraphqlDepth' => 'gql.maxDepth',
        'maxGraphqlResults' => 'gql.maxResults',
        'maxInvalidLogins' => 'security.maxInvalidLogins',
        'maxSlugIncrement' => 'slugs.maxIncrement',
        'maxUploadFileSize' => 'assets.maxUploadFileSize',
        'oauthProviders' => 'users.oauthProviders',
        'optimizeImageFilesize' => 'images.optimizeFilesize',
        'pageTrigger' => 'routing.pageTrigger',
        'partialTemplatesPath' => 'templates.partialsPath',
        'postCpLoginRedirect' => 'routing.postCpLoginRedirect',
        'postLoginRedirect' => 'routing.postLoginRedirect',
        'postLogoutRedirect' => 'routing.postLogoutRedirect',
        'prefixGqlRootTypes' => 'gql.prefixRootTypes',
        'preserveCmykColorspace' => 'images.preserveCmykColorspace',
        'preserveExifData' => 'images.preserveExifData',
        'preserveImageColorProfiles' => 'images.preserveColorProfiles',
        'preventUserEnumeration' => 'security.preventUserEnumeration',
        'previewTokenDuration' => 'security.previewTokenDuration',
        'privateTemplateTrigger' => 'templates.privateTrigger',
        'purgePendingUsersDuration' => 'garbageCollection.purgePendingUsersDuration',
        'purgeUnsavedDraftsDuration' => 'garbageCollection.purgeUnsavedDraftsDuration',
        'queueName' => 'queue.name',
        'rememberUsernameDuration' => 'security.rememberUsernameDuration',
        'restoreCommand' => 'backups.restoreCommand',
        'revAssetUrls' => 'assets.revUrls',
        'rotateImagesOnUploadByExifData' => 'images.rotateOnUploadByExifData',
        'runQueueAutomatically' => 'queue.runAutomatically',
        'sanitizeCpImageUploads' => 'assets.sanitizeCpImageUploads',
        'sanitizeSvgUploads' => 'assets.sanitizeSvgUploads',
        'setGraphqlDatesToSystemTimeZone' => 'gql.setDatesToSystemTimeZone',
        'setPasswordPath' => 'routing.setPasswordPath',
        'setPasswordRequestPath' => 'routing.setPasswordRequestPath',
        'setPasswordSuccessPath' => 'routing.setPasswordSuccessPath',
        'showFirstAndLastNameFields' => 'users.showFirstAndLastNameFields',
        'siteToken' => 'routing.siteToken',
        'slugWordSeparator' => 'slugs.wordSeparator',
        'softDeleteDuration' => 'garbageCollection.softDeleteDuration',
        'storeUserIps' => 'security.storeUserIps',
        'tempAssetUploadDisk' => 'assets.tempUploadDisk',
        'tokenParam' => 'routing.tokenParam',
        'trackedQueueNames' => 'queue.trackedNames',
        'transformGifs' => 'images.transformGifs',
        'transformSvgs' => 'images.transformSvgs',
        'trashedAssetDisk' => 'assets.trashDisk',
        'uploadChunkSize' => 'assets.uploadChunkSize',
        'uploader' => 'assets.uploader',
        'uploadSessionDisk' => 'assets.uploadSessionDisk',
        'uploadSessionDuration' => 'assets.uploadSessionDuration',
        'upscaleImages' => 'images.upscale',
        'useSslOnTokenizedUrls' => 'routing.useSslOnTokenizedUrls',
        'verifyEmailPath' => 'routing.verifyEmailPath',
        'verifyEmailSuccessPath' => 'routing.verifyEmailSuccessPath',
    ];

    #[Override]
    protected static array $renamedSettings = self::RENAMED_SETTINGS;

    /**
     * @var array<string, bool|int|string> The default user accessibility preferences that should be applied to users that haven’t saved their preferences yet.
     *
     * The array can contain the following keys:
     *
     * - `useShapes` – Whether shapes should be used to represent statuses.
     * - `underlineLinks` – Whether links should be underlined.
     * - `disableAutofocus` – Whether inputs should make use of the `autofocus` attribute.
     * - `notificationDuration` – How long notifications should be shown before they disappear automatically (in
     *   milliseconds). Set to `0` to show them indefinitely.
     * - `notificationPosition` – Where notifications should be shown on the screen (`'start-start'` for top-left,
     *   `'start-end'` for top-right, `'end-start'` for bottom-left, or `'end-end'` for bottom-right, when using an
     *   LTR orientation).
     * - `slideoutPosition` – Where slideouts should be shown on the screen (`'start'` for left, or `'end'`
     *   for right, when using an LTR orientation).
     *
     * ```php
     * ->accessibilityDefaults([
     *     'useShapes' => true,
     * ])
     * ```
     *
     * @group System
     */
    public array $accessibilityDefaults = [
        'useShapes' => false,
        'underlineLinks' => false,
        'disableAutofocus' => false,
        'notificationDuration' => 5000,
        'notificationPosition' => 'end-start',
        'slideoutPosition' => 'end',
    ];

    /**
     * @var array<string,string|null> Any custom Yii [aliases](https://www.yiiframework.com/doc/guide/2.0/en/concept-aliases) that should be defined for every request.
     *
     * ```php
     * ->aliases([
     *     '@webroot' => '/var/www/',
     * ])
     * ```
     *
     * @group Environment
     */
    public array $aliases = [];

    /**
     * @var BackupsConfig Settings for database backups and restores.
     *
     * Supported settings:
     *
     * - `onUpdate` – Whether to back up the database before applying a system update. (Default is `true`.)
     * - `command` – The shell command to run to back up the database, `null` to run `mysqldump`, `pg_dump`, or `sqlite3`, or `false` to disable backups. It can include the `{file}`, `{port}`, `{server}`, `{user}`, `{password}`, `{database}`, and `{schema}` tokens. (Default is `null`.)
     * - `commandFormat` – The output format PostgreSQL backups should use: `'custom'`, `'directory'`, `'tar'`, or `'plain'`. (Default is `null`.)
     * - `restoreCommand` – The shell command to run to restore a backup, `null` to run `mysql`, `psql`, or `sqlite3`, or `false` to disable restores. It can include the `{path}`, `{port}`, `{server}`, `{user}`, `{database}`, and `{schema}` tokens. (Default is `null`.)
     * - `max` – The number of backups to keep before deleting the oldest ones, or `false` to keep them all. (Default is `20`.)
     *
     * ```php
     * ->backups(\CraftCms\Cms\Config\BackupsConfig::create()
     *     ->onUpdate(false)
     *     ->max(5)
     * )
     * ```
     *
     * @group Environment
     *
     * @since 6.0.0
     */
    public BackupsConfig $backups;

    /**
     * @var bool Whether admins should be allowed to make administrative changes to the system.
     *
     * When this is disabled, the Settings section will be hidden, the Craft edition and Craft/plugin versions will be locked,
     * and the project config and Plugin Store will become read-only—though Craft and plugin licenses may still be purchased.
     *
     * It’s best to disable this in production environments with a deployment workflow that runs `composer install` and
     * [propagates project config updates](../project-config.md#propagating-changes) on deploy.
     *
     * ::: warning
     * Don’t disable this setting until **all** environments have been updated to Craft 3.1.0 or later.
     * :::
     *
     * ```php
     * ->allowAdminChanges(false)
     * ```
     *
     * @group System
     */
    public bool $allowAdminChanges = true;

    /**
     * @var bool Whether Craft should allow system and plugin updates in the control panel, and plugin installation from the Plugin Store.
     *
     * This setting will automatically be disabled if <config5:allowAdminChanges> is disabled.
     *
     * ```php
     * ->allowUpdates(false)
     * ```
     *
     * @group System
     */
    public bool $allowUpdates = true;

    /**
     * @var AnchorsConfig Settings for the anchor links that the `|anchors` Twig filter and `@anchors` GraphQL directive add to headings.
     *
     * Supported settings:
     *
     * - `anchorClass` – The class name that should be given to named anchors. (Default is `null`.)
     * - `anchorLinkPosition` – Where anchor links should be positioned within headings, relative to the heading text (`'before'` or `'after'`). (Default is `'after'`.)
     * - `anchorLinkClass` – The class name that should be given to anchor links. (Default is `'anchor'`.)
     * - `anchorLinkText` – The visible text that anchor links should have. (Default is `'#'`.)
     * - `anchorLinkTitleText` – The title and `aria-label` text that anchor links should have, translated with the `site` category. `{heading}` is replaced with the heading text. (Default is `'Direct link to {heading}'`.)
     * - `useAdditionalTagToAnchorTo` – Whether to render an empty `<span>` with the anchor `id` before each heading, rather than adding the `id` to the heading itself. (Default is `true`.)
     *
     * ```php
     * ->anchors(\CraftCms\Cms\Config\AnchorsConfig::create()
     *     ->anchorLinkText('¶')
     *     ->useAdditionalTagToAnchorTo(false)
     * )
     * ```
     *
     * @group System
     *
     * @since 6.0.0
     */
    public AnchorsConfig $anchors;

    /**
     * @var AssetsConfig Settings for assets: which files can be uploaded, how they’re uploaded and named, where Craft keeps their files, and which asset transformers volumes can use.
     *
     * Supported settings:
     *
     * - `allowedFileExtensions` – The file extensions that can be uploaded.
     * - `extraAllowedFileExtensions` – File extensions to add to `allowedFileExtensions`.
     * - `extraFileKinds` – Additional file kinds, merged with the ones `\CraftCms\Cms\Asset\AssetFileKinds::fileKinds()` defines. Their extensions still need to be allowed. (Default is `[]`.)
     * - `convertFilenamesToAscii` – Whether uploaded filenames with non-ASCII characters should be converted to ASCII (`ñ` → `n`). (Default is `false`.)
     * - `filenameWordSeparator` – The string that separates words in uploaded filenames, or `false` to leave spaces alone. (Default is `'-'`.)
     * - `revUrls` – Whether asset URLs should be revved, so browsers don’t load cached versions when they’re modified. (Default is `false`.)
     * - `sanitizeSvgUploads` – Whether uploaded SVG files should be sanitized. (Default is `true`.)
     * - `sanitizeCpImageUploads` – Whether images uploaded through the control panel should be sanitized. (Default is `true`.)
     * - `trashDisk` – The Laravel filesystem disk that soft-deleted assets’ files are moved to, so the assets can be restored. If it isn’t set, soft-deleting an asset deletes its file. (Default is `null`.)
     * - `tempUploadDisk` – The Laravel filesystem disk for temporary assets and user photos. (Default is `null`, a local temp folder.)
     * - `uploadSessionDisk` – The Laravel filesystem disk that stages upload sessions. Sites on more than one server must set it to a shared disk. (Default is `null`, the local `craft-tmp` disk.)
     * - `uploader` – The registered uploader to use, or `null` to select one from the upload session disk. (Default is `null`.)
     * - `uploadChunkSize` – The maximum bytes per PHP upload request. (Default is 8 MiB.)
     * - `uploadSessionDuration` – The seconds of inactivity before an upload session can be removed. (Default is 24 hours.)
     * - `maxUploadFileSize` – The maximum upload file size, in bytes or as a size such as `'25M'`. (Default is 16 MiB.)
     * - `defaultTransformer` – The asset transformer for volumes that don’t choose one. (Default is `null`, the `craft` transformer.)
     * - `transformers` – Asset transformers keyed by name, each with a `driver` and that driver’s settings. Add one at a time with `addTransformer()`. (Default is `[]`.)
     *
     * ```php
     * ->assets(\CraftCms\Cms\Config\AssetsConfig::create()
     *     ->extraAllowedFileExtensions(['mbox', 'xml'])
     *     ->maxUploadFileSize('25M')
     *     ->defaultTransformer(env('ASSET_TRANSFORMER'))
     *     ->addTransformer('craft', ['outputDisk' => 'transforms'])
     *     ->addTransformer('imgix', ['driver' => 'imgix', 'domain' => env('IMGIX_DOMAIN')])
     * )
     * ```
     *
     * @group Assets
     *
     * @since 6.0.0
     */
    public AssetsConfig $assets;

    /**
     * @var bool Whether element queries should automatically lazy eager-load relations for the other elements in their result set during site requests.
     *
     * ```php
     * ->autoEagerLoadElements(false)
     * ```
     *
     * @group System
     */
    public bool $autoEagerLoadElements = true;

    /**
     * @var bool Whether users should automatically be logged in after activating their account.
     *
     * ```php
     * ->autoLoginAfterAccountActivation(true)
     * ```
     *
     * @group System
     */
    public bool $autoLoginAfterAccountActivation = false;

    /**
     * @var mixed The default length of time Craft will store data, RSS feed, and template caches.
     *
     * If set to `0`, data and RSS feed caches will be stored indefinitely.
     *
     * See {@see ConfigHelper::durationInSeconds()} for a list of supported value types.
     *
     * ```php
     * ->cacheDuration(0)
     * ```
     *
     * @group System
     *
     * @defaultAlt 1 day
     */
    public mixed $cacheDuration = 86400;

    /**
     * @var list<array{0: string, 1?: array<string, mixed>}> List of additional HTML tags that should be included in the `<head>` of control panel pages.
     *
     * Each tag can be specified as an array of the tag name and its attributes.
     *
     * For example, you can give the control panel a custom favicon (etc.) like this:
     *
     * ```php
     * ->cpHeadTags([
     *     // Traditional favicon
     *     ['link', ['rel' => 'icon', 'href' => '/icons/favicon.ico']],
     *     // Scalable favicon for browsers that support them
     *     ['link', ['rel' => 'icon', 'type' => 'image/svg+xml', 'sizes' => 'any', 'href' => '/icons/favicon.svg']],
     *     // Touch icon for mobile devices
     *     ['link', ['rel' => 'apple-touch-icon', 'sizes' => '180x180', 'href' => '/icons/touch-icon.svg']],
     *     // Pinned tab icon for Safari
     *     ['link', ['rel' => 'mask-icon', 'href' => '/icons/mask-icon.svg', 'color' => '#663399']],
     * ])
     * ```
     *
     * @group System
     */
    public array $cpHeadTags = [];

    /**
     * @var string|null The server path to the control panel’s icon, shown beside the system name in the global sidebar.
     *
     * This can be set to an aliased path such as `@root/branding/icon.svg`.
     *
     * A square SVG file is recommended. It will be displayed at 32px by 32px.
     *
     * SVG files are rendered inline, so they can adapt to the control panel’s light and dark themes. The selected
     * theme may differ from the operating system’s, so use the [`light-dark()`](https://developer.mozilla.org/en-US/docs/Web/CSS/color_value/light-dark)
     * CSS function or `currentColor` within the SVG, rather than a `prefers-color-scheme` media query.
     *
     * ```xml
     * <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 32 32">
     *   <style>.bg { fill: light-dark(#e5422b, #ff7a66); }</style>
     *   <rect class="bg" width="32" height="32" rx="6"/>
     * </svg>
     * ```
     *
     * ```php
     * ->cpIconPath('@root/branding/icon.svg')
     * ```
     *
     * @group System
     *
     * @since 6.0.0
     */
    public ?string $cpIconPath = null;

    /**
     * @var string|null The URL to the control panel’s icon.
     *
     * ::: warning
     * This setting is deprecated. Set `cpIconPath` instead, which takes precedence over it.
     * :::
     *
     * @group System
     */
    public ?string $cpIconUrl = null;

    /**
     * @var string|null The server path to the logo shown on the login page and other sign-in screens.
     *
     * This can be set to an aliased path such as `@root/branding/logo.svg`.
     *
     * An SVG file is recommended. It will be displayed at 288px wide.
     *
     * SVG files are rendered inline, so they can adapt to the control panel’s light and dark themes. Use the
     * [`light-dark()`](https://developer.mozilla.org/en-US/docs/Web/CSS/color_value/light-dark) CSS function or
     * `currentColor` within the SVG, as you would for `cpIconPath`. Once nobody is logged in, sign-in screens
     * show the theme the last user to sign in on the browser had selected.
     *
     * ```php
     * ->cpLogoPath('@root/branding/logo.svg')
     * ```
     *
     * @group System
     *
     * @since 6.0.0
     */
    public ?string $cpLogoPath = null;

    /**
     * @var string|null The URL to the logo shown on the login page and other sign-in screens.
     *
     * ::: warning
     * This setting is deprecated. Set `cpLogoPath` instead, which takes precedence over it.
     * :::
     *
     * @group System
     */
    public ?string $cpLogoUrl = null;

    /**
     * @var string|null The path to a directory of SVG files that should be made available to icon pickers as a “Custom” icon set.
     *
     * This can be set to an aliased path such as `@config/icons`.
     *
     * Each file’s name (sans `.svg` extension) becomes its icon name, and icons selected from it will be stored as
     * `custom:icon-name`.
     *
     * ```php
     * ->customIconPath('@config/icons')
     * ```
     *
     * @group System
     *
     * @since 5.12.0
     */
    public ?string $customIconPath = null;

    /**
     * @var string The two-letter country code that addresses will be set to by default.
     *
     * See <https://en.wikipedia.org/wiki/ISO_3166-1_alpha-2> for a list of acceptable country codes.
     *
     * ```php
     * ->defaultCountryCode('GB')
     * ```
     *
     * @group System
     */
    public string $defaultCountryCode = 'US';

    /**
     * @var string|null The default language the control panel should use for users who haven’t set a preferred language yet.
     *
     * ```php
     * ->defaultCpLanguage('en-US')
     * ```
     *
     * @group System
     */
    public ?string $defaultCpLanguage = null;

    /**
     * @var string|null The default locale the control panel should use for date/number formatting, for users who haven’t set
     *                  a preferred language or formatting locale.
     *
     * If this is `null`, the <config5:defaultCpLanguage> config setting will determine which locale is used for date/number formatting by default.
     *
     * ```php
     * ->defaultCpLocale('en-US')
     * ```
     *
     * @group System
     */
    public ?string $defaultCpLocale = null;

    /**
     * @var mixed The default permission to be set for newly-generated directories.
     *
     * If set to `null`, the permission will be determined by the current environment.
     *
     * ```php
     * ->defaultDirMode(0744)
     * ```
     *
     * @group System
     */
    public mixed $defaultDirMode = 0775;

    /**
     * @var array<string, bool|string> The default options that should be applied to each search term.
     *
     * Options include:
     *
     * - `subLeft` – Whether to include keywords that contain the term, with additional characters before it. (`false` by default)
     * - `subRight` – Whether to include keywords that contain the term, with additional characters after it. (`true` by default)
     * - `exclude` – Whether search results should *exclude* records with this term. (`false` by default)
     * - `exact` – Whether the term must be an exact match (only applies if the search term specifies an attribute). (`false` by default)
     *
     * ```php
     * ->defaultSearchTermOptions([
     *     'subLeft' => true,
     *     'exclude' => 'secret',
     * ])
     * ```
     *
     * @group System
     */
    public array $defaultSearchTermOptions = [];

    /**
     * @var int The default day new users should have set as their Week Start Day.
     *
     * This should be set to one of the following integers:
     *
     * - `0` – Sunday
     * - `1` – Monday
     * - `2` – Tuesday
     * - `3` – Wednesday
     * - `4` – Thursday
     * - `5` – Friday
     * - `6` – Saturday
     *
     * ```php
     * ->defaultWeekStartDay(0)
     * ```
     *
     * @group System
     *
     * @defaultAlt Monday
     */
    public int $defaultWeekStartDay = 1;

    /**
     * @var bool Whether the system should run in [Dev Mode](https://craftcms.com/support/dev-mode).
     *
     * ::: warning
     * This setting is deprecated. Configure `app.debug` or set the `APP_DEBUG` environment variable instead.
     * :::
     *
     * ```shell
     * APP_DEBUG=true
     * ```
     *
     * @group System
     */
    public bool $devMode = false;

    /**
     * @var string[]|string|null Array of plugin handles that should be disabled, regardless of what the project config says.
     *
     * ```php
     * ->disabledPlugins([
     *     'webhooks',
     * ])
     * ```
     *
     * This can also be set to `'*'` to disable **all** plugins.
     *
     * ```php
     * ->disabledPlugins('*')
     * ```
     *
     * ::: warning
     * This should not be set on a per-environment basis, as it could result in plugin schema version mismatches
     * between environments, which will prevent project config changes from getting applied.
     * :::
     *
     * ```php
     * ->disabledPlugins([
     *     'redactor',
     *     'webhooks',
     * ])
     * ```
     *
     * @group System
     */
    public string|array|null $disabledPlugins = null;

    /**
     * @var string[] Array of utility IDs that should be disabled.
     *
     * ```php
     *  ->disabledUtilities([
     *      'updates',
     *      'find-replace',
     *  ])
     * ```
     *
     * @group System
     */
    public array $disabledUtilities = [];

    /**
     * @var bool Whether front end requests should respond with `X-Robots-Tag: none` HTTP headers, indicating that pages should not be indexed,
     *           and links on the page should not be followed, by web crawlers.
     *
     * ::: tip
     * This should be set to `true` for development and staging environments.
     * :::
     *
     * ```php
     * ->disallowRobots(true)
     * ```
     *
     * @group System
     */
    public bool $disallowRobots = false;

    /**
     * @var mixed The amount of time a user’s elevated session will last, which is required for some sensitive actions (e.g. user group/permission assignment).
     *
     * ::: warning
     * This setting is deprecated. Configure `auth.password_timeout` instead, in seconds. Set it to `-1` to disable elevated session support.
     * :::
     *
     * ```php config/auth.php
     * 'password_timeout' => -1,
     * ```
     *
     * @group Security
     *
     * @defaultAlt 5 minutes
     */
    public mixed $elevatedSessionDuration = 300;

    /**
     * @var string[] List of extra locale IDs that the application should support, and users should be able to select as their Preferred Language.
     *
     * ```php
     * ->extraAppLocales(['uk'])
     * ```
     *
     * @group System
     */
    public array $extraAppLocales = [];

    /**
     * @var GarbageCollectionConfig Settings for how long garbage collection keeps old data.
     *
     * Supported settings:
     *
     * - `activityRetentionDuration` – The maximum age of activity events before garbage collection deletes them. (Default is `0`.)
     * - `purgePendingUsersDuration` – The amount of time to wait before Craft purges pending users from the system that have not activated. (Default is `0`.)
     * - `purgeUnsavedDraftsDuration` – The amount of time to wait before Craft purges unpublished drafts that were never updated with content. (Default is `2592000`.)
     * - `softDeleteDuration` – The amount of time before a soft-deleted item will be up for hard-deletion by garbage collection. (Default is `2592000`.)
     *
     * ```php
     * ->garbageCollection(\CraftCms\Cms\Config\GarbageCollectionConfig::create()
     *     ->softDeleteDuration(0)
     *     ->purgePendingUsersDuration('P2W')
     * )
     * ```
     *
     * @group System
     *
     * @since 6.0.0
     */
    public GarbageCollectionConfig $garbageCollection;

    /**
     * @var GqlConfig Settings for the GraphQL API.
     *
     * Supported settings:
     *
     * - `enabled` – Whether the GraphQL API should be enabled. It’s only available for Craft Pro. (Default is `true`.)
     * - `enableIntrospection` – Whether introspection queries are allowed. They’re always allowed in the control panel. (Default is `true`.)
     * - `enableCaching` – Whether Craft should cache the results of unique queries per access token. The cache is invalidated any time an element is saved, the site structure is updated, or a GraphQL schema is saved. (Default is `true`.)
     * - `setDatesToSystemTimeZone` – Whether dates should be set to the system time zone by default, rather than UTC. (Default is `false`.)
     * - `typePrefix` – Prefix to use for all type names. (Default is `''`.)
     * - `prefixRootTypes` – Whether `typePrefix` should apply to the `query`, `mutation`, and `subscription` types. (Default is `true`.)
     * - `lazyTypes` – Whether types should be generated lazily. (Default is `false`.)
     * - `maxBatchSize` – The maximum number of queries in a single batched request, or `0` for no limit. (Default is `0`.)
     * - `maxComplexity` – The maximum complexity a query can have, or `0` for no limit. (Default is `0`.)
     * - `maxDepth` – The maximum depth a query can reach, or `0` for no limit. (Default is `0`.)
     * - `maxResults` – The maximum number of results a single query can return, or `0` for no limit. (Default is `0`.)
     *
     * ```php
     * ->gql(\CraftCms\Cms\Config\GqlConfig::create()
     *     ->enableIntrospection(false)
     *     ->maxDepth(10)
     * )
     * ```
     *
     * @group GraphQL
     *
     * @since 6.0.0
     */
    public GqlConfig $gql;

    /**
     * @var string The casing to use for autogenerated component handles.
     *
     * @phpstan-var self::CAMEL_CASE|self::PASCAL_CASE|self::SNAKE_CASE
     *
     * This can be set to one of the following:
     *
     * - `camel` – for camelCase
     * - `pascal` – for PascalCase (aka UpperCamelCase)
     * - `snake` – for snake_case
     *
     * ```php
     * ->handleCasing('pascal')
     * ```
     *
     * @group System
     */
    public string $handleCasing = self::CAMEL_CASE;

    /**
     * @var bool Whether the system should run in Headless Mode, which optimizes the system and control panel for headless CMS implementations.
     *
     * When this is enabled, the following changes will take place:
     *
     * - Template settings for sections and category groups will be hidden.
     * - Template route management will be hidden.
     * - Front-end routing will skip checks for element and template requests.
     * - Front-end responses will be JSON-formatted rather than HTML by default.
     * - Twig will be configured to escape unsafe strings for JavaScript/JSON rather than HTML by default for front-end requests.
     * - The <config5:loginPath>, <config5:logoutPath>, <config5:setPasswordPath>, and <config5:verifyEmailPath> settings will be ignored.
     *
     * ::: tip
     * With Headless Mode enabled, users may only set passwords and verify email addresses via the control panel. Be sure to grant “Access the control
     * panel” permission to all content editors and administrators. You’ll also need to set the <config5:baseCpUrl> config setting if the control
     * panel is located on a different domain than your front end.
     * :::
     *
     * ```php
     * ->headlessMode(true)
     * ```
     *
     * @group System
     */
    public bool $headlessMode = false;

    /**
     * @var string|null The proxy server that should be used for outgoing HTTP requests.
     *
     * This can be set to a URL (`http://localhost`) or a URL plus a port (`http://localhost:8125`).
     *
     * ```php
     * ->httpProxy('http://localhost')
     * ```
     *
     * @group System
     */
    public ?string $httpProxy = null;

    /**
     * @var bool Whether to automatically generate IDE helper files for custom fields.
     *
     * When enabled, Craft will generate PHPDoc metadata files in the IDE helper path
     * whenever field layouts are saved. This improves IDE autocompletion for custom fields.
     *
     * ```php
     * ->ideHelperEnabled(true)
     * ```
     *
     * @group System
     *
     * @since 6.0.0
     */
    public bool $ideHelperEnabled = true;

    /**
     * @var string The path where IDE helper files should be written, relative to the project root.
     *
     * ```php
     * ->ideHelperPath('vendor/_craft')
     * ```
     *
     * @group System
     *
     * @since 6.0.0
     */
    public string $ideHelperPath = 'vendor/_craft';

    /**
     * @var ImagesConfig Settings for how Craft cleanses and transforms images.
     *
     * Supported settings:
     *
     * - `driver` – The image driver to use: `'auto'`, `'imagick'`, `'vips'`, or `'gd'`. `'auto'` uses ImageMagick if it’s installed, followed by libvips and GD. (Default is `'auto'`.)
     * - `defaultQuality` – The quality level used when saving JPG and PNG files, from 1 to 100. (Default is `82`.)
     * - `editorRatios` – The aspect ratios the image editor offers, as `label` => `ratio`, where a ratio is a number, `'none'`, or `'original'`.
     * - `optimizeFilesize` – Whether to favor reduced file sizes over lossless encoding where supported. (Default is `true`.)
     * - `preserveCmykColorspace` – Whether CMYK should be preserved as the colorspace. (Default is `false`.)
     * - `preserveExifData` – Whether EXIF data should be preserved. (Default is `false`.)
     * - `preserveColorProfiles` – Whether embedded color profiles (ICC) should be preserved. (Default is `true`.)
     * - `rotateOnUploadByExifData` – Whether uploaded images should be rotated according to their EXIF data. (Default is `true`.)
     * - `transformGifs` – Whether GIF files should be cleansed and transformed. (Default is `true`.)
     * - `transformSvgs` – Whether SVG files should be transformed. (Default is `true`.)
     * - `upscale` – Whether transforms should upscale images smaller than their dimensions by default. (Default is `true`.)
     * - `brokenPath` – The server path to an image file that should be sent when responding to an image request with a 404 status code, such as `@webroot/assets/404.svg`. (Default is `null`.)
     *
     * ```php
     * ->images(\CraftCms\Cms\Config\ImagesConfig::create()
     *     ->driver('imagick')
     *     ->defaultQuality(90)
     * )
     * ```
     *
     * @group Image Handling
     *
     * @since 6.0.0
     */
    public ImagesConfig $images;

    /**
     * @var array<string, array{aliasOf: string, displayName?: string}> Custom locale aliases, which will be included when fetching all known locales.
     *
     * Each locale alias should be defined as an array with the following keys:
     *
     * - `id`: The alias locale ID
     * - `aliasOf`: The original locale ID
     * - `displayName`: The locale alias’s display name _(optional)_
     *
     *  ::: code
     *  ```php Static Config
     *  ->localeAliases([
     *     'smj' => [
     *         'aliasOf' => 'sv',
     *         'displayName' => 'Lule Sámi',
     *     ],
     * ])
     *  ```
     *  :::
     *
     * @group System
     */
    public array $localeAliases = [];

    /**
     * @var int|null The maximum number of revisions that should be stored for each element.
     *
     * Set to `0` if you want to store an unlimited number of revisions.
     *
     * ```php
     * ->maxRevisions(25)
     * ```
     *
     * @group System
     */
    public ?int $maxRevisions = 50;

    /**
     * @var string|null The maximum amount of memory Craft will try to reserve during memory-intensive operations such as zipping,
     *                  unzipping and updating. Defaults to an empty string, which means it will use as much memory as it can.
     *
     * See <https://php.net/manual/en/faq.using.php#faq.using.shorthandbytes> for a list of acceptable values.
     *
     * ```php
     * ->phpMaxMemoryLimit('512M')
     * ```
     *
     * @group System
     */
    public ?string $phpMaxMemoryLimit = null;

    /**
     * @var bool Whether Single section entries should be preloaded for Twig templates.
     *
     * When enabled, Craft will make an educated guess on which Singles should be preloaded for each template based on
     * the variable names that are referenced.
     *
     * ::: warning
     * You will need to clear your compiled templates from the Caches utility before this setting will take effect.
     * :::
     *
     * ```php
     * ->preloadSingles()
     * ```
     *
     * @group System
     */
    public bool $preloadSingles = false;

    /**
     * @var McpConfig Administrative MCP transport and authentication configuration.
     *
     * @group System
     */
    public McpConfig $mcp;

    /**
     * @var QueueConfig Settings for Craft’s queue jobs.
     *
     * Supported settings:
     *
     * - `runAutomatically` – Whether to run pending queue jobs automatically when someone visits the control panel. If disabled, run a Laravel queue worker, such as `php artisan queue:work`, and include any custom queue names in its `--queue` option. (Default is `true`.)
     * - `name` – The name of the queue Craft jobs are sent to. (Default is `'default'`.)
     * - `lowPriorityName` – The name of the queue lower-priority Craft jobs are sent to. (Default is `'default'`.)
     * - `trackedNames` – The names of the queues whose jobs have their progress tracked. (Default is `['default']`.)
     *
     * ```php
     * ->queue(\CraftCms\Cms\Config\QueueConfig::create()
     *     ->runAutomatically(false)
     *     ->name('craft')
     * )
     * ```
     *
     * @group System
     *
     * @since 6.0.0
     */
    public QueueConfig $queue;

    /**
     * @var RoutingConfig Settings for how Craft routes requests and builds URLs.
     *
     * Supported settings:
     *
     * - `actionTrigger` – The URI segment Craft should look for when determining if the current request should be routed to a controller action. (Default is `'actions'`.)
     * - `activateAccountSuccessPath` – The URI that users without access to the control panel should be redirected to after activating their account. (Default is `''`.)
     * - `addTrailingSlashesToUrls` – Whether auto-generated URLs should have trailing slashes. (Default is `false`.)
     * - `baseCpUrl` – The base URL Craft should use when generating control panel URLs. (Default is `null`.)
     * - `cpTrigger` – The URI segment Craft should look for when determining if the current request should route to the control panel rather than the front-end website. (Default is `'admin'`.)
     * - `invalidUserTokenPath` – The URI Craft should redirect to when user token validation fails. User tokens are used for email verification and password resets. If `null`, <config5:loginPath> will be used by default. (Default is `null`.)
     * - `loginPath` – The URI Craft should use for user login on the front end. (Default is `false`.)
     * - `logoutPath` – The URI Craft should use for user logout on the front end. (Default is `'logout'`.)
     * - `pageTrigger` – The query string param name Craft should use for paginated requests. (Default is `'page'`.)
     * - `postCpLoginRedirect` – The path users should be redirected to after logging into the control panel. (Default is `'dashboard'`.)
     * - `postLoginRedirect` – The path users should be redirected to after logging in from the front-end site. (Default is `''`.)
     * - `postLogoutRedirect` – The path that users should be redirected to after logging out from the front-end site. (Default is `''`.)
     * - `setPasswordPath` – The URI or URL that Craft should use for Set Password forms on the front end. (Default is `'set-password'`.)
     * - `setPasswordRequestPath` – The URI to the page where users can request to change their password. (Default is `null`.)
     * - `setPasswordSuccessPath` – The URI Craft should redirect users to after setting their password from the front end. (Default is `''`.)
     * - `siteToken` – The query string parameter name that site tokens should be set to. (Default is `'siteToken'`.)
     * - `tokenParam` – The query string parameter name that Craft tokens should be set to. (Default is `'token'`.)
     * - `useSslOnTokenizedUrls` – Determines what protocol/schema Craft will use when generating tokenized URLs. If set to `'auto'`, Craft will check the current site’s base URL and the protocol of the current request and if either of them are HTTPS will use `https` in the tokenized URL. If not, will use `http`. (Default is `'auto'`.)
     * - `verifyEmailPath` – The URI or URL that Craft should use for email verification links on the front end. (Default is `'verifyemail'`.)
     * - `verifyEmailSuccessPath` – The URI that users without access to the control panel should be redirected to after verifying a new email address. (Default is `''`.)
     *
     * ```php
     * ->routing(\CraftCms\Cms\Config\RoutingConfig::create()
     *     ->cpTrigger('cms')
     *     ->loginPath('login')
     * )
     * ```
     *
     * @group System
     *
     * @since 6.0.0
     */
    public RoutingConfig $routing;

    /**
     * @var bool Whether the system should run in Safe Mode.
     *
     * Safe Mode disables all plugins and application config that can alter Craft's expected default behavior.
     *
     * ```php
     * ->safeMode(true)
     * ```
     *
     * @group System
     */
    public bool $safeMode = false;

    /**
     * @var SecurityConfig Settings for authentication, rate limits, and tokens.
     *
     * Supported settings:
     *
     * - `authGuard` – The Laravel authentication guard Craft should use. (Default is `null`.)
     * - `authPasswordBroker` – The Laravel password broker Craft should use. (Default is `null`.)
     * - `cooldownDuration` – The amount of time a user must wait before re-attempting to log in after their account is locked due to too many failed login attempts. (Default is `300`.)
     * - `defaultRateLimit` – The number of requests Craft will allow from a single IP address within {@see}, for actions that are rate-limited by default, before responding with a 429 error. (Default is `10`.)
     * - `defaultRateLimitDuration` – The amount of time that {@see} applies to. (Default is `60`.)
     * - `defaultTokenDuration` – The default amount of time tokens can be used before expiring. (Default is `86400`.)
     * - `deferPublicRegistrationPassword` – By default, Craft requires a front-end “password” field for public user registrations. Setting this to `true` removes that requirement for the initial registration form. Instead, new users will set their password once they’ve followed the link in their activation email. (Default is `false`.)
     * - `invalidLoginWindowDuration` – The amount of time to track invalid login attempts for a user, for determining if Craft should lock an account. (Default is `3600`.)
     * - `maxInvalidLogins` – The number of invalid login attempts Craft will allow within the specified duration before the account gets locked. (Default is `5`.)
     * - `preventUserEnumeration` – When `true`, Craft will always return a successful response in the “forgot password” flow, making it difficult to enumerate users. (Default is `true`.)
     * - `previewTokenDuration` – The amount of time content preview tokens can be used before expiring. (Default is `null`.)
     * - `rememberUsernameDuration` – The amount of time Craft will remember a username and pre-populate it on the control panel’s Login page. (Default is `31536000`.)
     * - `storeUserIps` – Whether user IP addresses should be stored/logged by the system. (Default is `false`.)
     *
     * ```php
     * ->security(\CraftCms\Cms\Config\SecurityConfig::create()
     *     ->maxInvalidLogins(3)
     *     ->storeUserIps()
     * )
     * ```
     *
     * @group System
     *
     * @since 6.0.0
     */
    public SecurityConfig $security;

    /**
     * @var bool Whether an `X-Powered-By: Craft CMS` header should be sent, helping services like [BuiltWith](https://builtwith.com/) and
     *           [Wappalyzer](https://www.wappalyzer.com/) identify that the site is running on Craft.
     *
     * ```php
     * ->sendPoweredByHeader(false)
     * ```
     *
     * @group System
     */
    public bool $sendPoweredByHeader = true;

    /**
     * @var SlugsConfig Settings for element slugs.
     *
     * Supported settings:
     *
     * - `allowUppercase` – Whether uppercase letters are allowed in slugs. (Default is `false`.)
     * - `limitAutoToAscii` – Whether non-ASCII characters in slugs the control panel generates should be converted to ASCII (`ñ` → `n`). (Default is `false`.)
     * - `wordSeparator` – The characters that separate words in slugs. (Default is `'-'`.)
     * - `maxIncrement` – The highest number Craft adds to a slug to make it unique, before it gives up and throws an error. (Default is `100`.)
     *
     * ```php
     * ->slugs(\CraftCms\Cms\Config\SlugsConfig::create()
     *     ->wordSeparator('.')
     *     ->limitAutoToAscii()
     * )
     * ```
     *
     * @group System
     *
     * @since 6.0.0
     */
    public SlugsConfig $slugs;

    /**
     * @var bool Whether entries’ statuses should be stored statically, and only get updated on entry save, or when the
     *           `update-statuses` command is executed.
     *
     * ```php
     * ->staticStatuses()
     * ```
     *
     * @group System
     */
    public bool $staticStatuses = false;

    /**
     * @var string|null The URL to a CSS file that should be included when rendering system templates on the front end,
     *                  such as the Login and Set Password templates.
     *
     * ```php
     * ->systemTemplateCss('/css/cp-theme.css');
     * ```
     *
     * @group System
     */
    public ?string $systemTemplateCss = null;

    /**
     * @var TemplatesConfig Settings for how Craft finds, routes, and compiles templates.
     *
     * Supported settings:
     *
     * - `defaultExtensions` – The template file extensions Craft looks for when matching a template path to a file on the front end. (Default is `['twig', 'html', 'blade.php']`.)
     * - `indexFilenames` – The filenames Craft looks for within a directory for its “index” template. (Default is `['index']`.)
     * - `partialsPath` – The path within the `templates` folder where element partial templates live, such as `_partials/entry/article.twig`. (Default is `'_partials'`.)
     * - `privateTrigger` – The template path segment prefix that marks templates that can’t be reached through a matching URL. Set it to `''` to disable public template routing. (Default is `'_'`.)
     * - `enableCaching` – Whether the `{% cache %}` template tag is enabled. (Default is `true`.)
     * - `compiledPath` – The server path where compiled Twig templates are stored, or `null` for `storage/runtime/compiled_templates`. (Default is `null`.)
     *
     * ```php
     * ->templates(\CraftCms\Cms\Config\TemplatesConfig::create()
     *     ->defaultExtensions(['twig', 'html'])
     *     ->privateTrigger('')
     * )
     * ```
     *
     * @group System
     *
     * @since 6.0.0
     */
    public TemplatesConfig $templates;

    /**
     * @var string|null The timezone of the site. If set, it will take precedence over the Timezone setting in Settings → General.
     *
     * This can be set to one of PHP’s [supported timezones](https://php.net/manual/en/timezones.php).
     *
     * ```php
     * ->timezone('Europe/London')
     * ```
     *
     * @group System
     */
    public ?string $timezone = null;

    /**
     * @var bool Whether translated messages should be wrapped in special characters to help find any strings that are not being run through
     *           `t()` or the `|translate` filter.
     *
     * ```php
     * ->translationDebugOutput(true)
     * ```
     *
     * The symbols are as follows:
     *
     * | Symbol | Example | Category |
     * | --- | --- | --- |
     * | `$` | `$Date Field$` | Site (front-end, `site.php`) |
     * | `@` | `@Entry Type@` | Application (Craft, `app.php`) |
     * | `%` | `%Object Template% | Other (plugin or custom source) |
     *
     * Translations _may_ be nested or surrounded by multiple symbols.
     *
     * @group System
     */
    public bool $translationDebugOutput = false;

    /**
     * @var bool Whether Craft should set users’ usernames to their email addresses, rather than let them set their username separately.
     *
     * If you enable this setting after user accounts already exist, run this terminal command to update existing usernames:
     *
     * ```bash
     * php artisan craft:utils:update-usernames
     * ```
     *
     * ```php
     * ->useEmailAsUsername(true)
     * ```
     *
     * @group System
     */
    public bool $useEmailAsUsername = false;

    /**
     * @var bool Whether the [`IDNA_NONTRANSITIONAL_TO_UNICODE`](https://www.php.net/manual/en/intl.constants.php#constant.idna-nontransitional-to-unicode)
     *           flag should be passed to [idn_to_utf8()](https://www.php.net/manual/en/function.idn-to-utf8.php) when converting
     *           email addresses from IDNA ASCII to Unicode.
     *
     * `INTL_IDNA_VARIANT_UTS46` by default, which uses the UTS 46 algorithm, consistent with the requirements of the
     * IDNA2008 protocol and mostly compatible with IDNA2003 (deprecated in PHP 7.2).
     *
     * There are a handful of characters which result in different resolution of IDNs between IDNA2008 and IDNA2003,
     * including ß, ς, and joiner characters (ZWJ and ZWNJ). ([More info](https://unicode.org/reports/tr46/#Deviations))
     *
     * For example, `ß` will be converted to `ss` by default. Enabling this setting will ensure it gets preserved as `ß`.
     *
     * ```php
     * ->useIdnaNontransitionalToUnicode(true)
     * ```
     *
     * @group System
     *
     * @since 5.9.0
     */
    public bool $useIdnaNontransitionalToUnicode = false;

    /**
     * @var UsersConfig Settings for user accounts.
     *
     * Supported settings:
     *
     * - `disable2fa` – Whether two-step verification features should be disabled. (Default is `false`.)
     * - `extraLastNamePrefixes` – Any additional last name prefixes that should be supported by the name parser. (Default is `[]`.)
     * - `extraNameSalutations` – Any additional name salutations that should be supported by the name parser. (Default is `[]`.)
     * - `extraNameSuffixes` – Any additional name suffixes that should be supported by the name parser. (Default is `[]`.)
     * - `oauthProviders` – The OAuth providers that should be available for login. (Default is `[]`.)
     * - `showFirstAndLastNameFields` – Whether “First Name” and “Last Name” fields should be shown in place of “Full Name” fields. (Default is `false`.)
     *
     * ```php
     * ->users(\CraftCms\Cms\Config\UsersConfig::create()
     *     ->showFirstAndLastNameFields()
     *     ->disable2fa()
     * )
     * ```
     *
     * @group System
     *
     * @since 6.0.0
     */
    public UsersConfig $users;

    public function __construct()
    {
        $this->anchors = AnchorsConfig::create();
        $this->assets = AssetsConfig::create();
        $this->backups = BackupsConfig::create();
        $this->garbageCollection = GarbageCollectionConfig::create();
        $this->gql = GqlConfig::create();
        $this->images = ImagesConfig::create();
        $this->queue = QueueConfig::create();
        $this->routing = RoutingConfig::create();
        $this->security = SecurityConfig::create();
        $this->slugs = SlugsConfig::create();
        $this->templates = TemplatesConfig::create();
        $this->mcp = McpConfig::create();
        $this->users = UsersConfig::create();

        // (Re-)normalize everything.
        $this
            // IDE Helper defaults to the same value as devMode
            ->ideHelperEnabled(Env::parseBoolean('$APP_DEBUG') ?? false)
            // durations
            ->cacheDuration($this->cacheDuration)
            // locales
            ->defaultCpLanguage($this->defaultCpLanguage)
            ->extraAppLocales($this->extraAppLocales)
            // misc
            ->disabledPlugins($this->disabledPlugins);
    }

    /**
     * The default user accessibility preferences that should be applied to users that haven’t saved their preferences yet.
     *
     * The array can contain the following keys:
     *
     * - `useShapes` – Whether shapes should be used to represent statuses.
     * - `underlineLinks` – Whether links should be underlined.
     * - `disableAutofocus` – Whether search inputs should be focused on page load.
     * - `notificationDuration` – How long notifications should be shown before they disappear automatically (in
     *   milliseconds). Set to `0` to show them indefinitely.
     *
     * ```php
     * ->accessibilityDefaults([
     *     'useShapes' => true,
     * ])
     * ```
     *
     * @group System
     *
     * @param  array<string, bool|int|string>  $value
     *
     * @see $accessibilityDefaults
     */
    public function accessibilityDefaults(array $value): self
    {
        $this->accessibilityDefaults = $value;

        return $this;
    }

    /**
     * Any custom Yii [aliases](https://www.yiiframework.com/doc/guide/2.0/en/concept-aliases) that should be defined for every request.
     *
     * ```php
     * ->aliases([
     *     '@webroot' => '/var/www/',
     * ])
     * ```
     *
     * @group Environment
     *
     * @param  array<string,string|null>  $value
     *
     * @see $aliases
     */
    public function aliases(array $value): self
    {
        $this->aliases = [];
        foreach ($value as $name => $path) {
            $this->addAlias($name, $path);
        }

        return $this;
    }

    /**
     * Settings for database backups and restores.
     *
     * ```php
     * ->backups(\CraftCms\Cms\Config\BackupsConfig::create()
     *     ->onUpdate(false)
     *     ->max(5)
     * )
     * ```
     *
     * @group Environment
     *
     * @since 6.0.0
     * @see $backups
     *
     * @param  BackupsConfig|array<string, mixed>|string  $value
     */
    public function backups(BackupsConfig|array|string $value): self
    {
        $this->backups->merge($value);

        return $this;
    }

    /**
     * Adds a custom Yii [alias](https://www.yiiframework.com/doc/guide/2.0/en/concept-aliases) that should be defined for every request.
     *
     * ```php
     * ->addAlias('@webroot', '/var/www/')
     * ```
     *
     * @group Environment
     *
     * @see $aliases
     */
    public function addAlias(string $name, ?string $path): self
    {
        if (! str_starts_with($name, '@')) {
            $name = "@$name";
        }
        $this->aliases[$name] = $path;

        return $this;
    }

    /**
     * Whether admins should be allowed to make administrative changes to the system.
     *
     * When this is disabled, the Settings section will be hidden, the Craft edition and Craft/plugin versions will be locked,
     * and the project config and Plugin Store will become read-only—though Craft and plugin licenses may still be purchased.
     *
     * It’s best to disable this in production environments with a deployment workflow that runs `composer install` and
     * [propagates project config updates](../project-config.md#propagating-changes) on deploy.
     *
     * ::: warning
     * Don’t disable this setting until **all** environments have been updated to Craft 3.1.0 or later.
     * :::
     *
     * ```php
     * ->allowAdminChanges(false)
     * ```
     *
     * @group System
     *
     * @see $allowAdminChanges
     */
    public function allowAdminChanges(bool $value = true): self
    {
        $this->allowAdminChanges = $value;

        return $this;
    }

    /**
     * Whether Craft should allow system and plugin updates in the control panel, and plugin installation from the Plugin Store.
     *
     * This setting will automatically be disabled if <config5:allowAdminChanges> is disabled.
     *
     * ```php
     * ->allowUpdates(false)
     * ```
     *
     * @group System
     *
     * @see $allowUpdates
     */
    public function allowUpdates(bool $value = true): self
    {
        $this->allowUpdates = $value;

        return $this;
    }

    /**
     * Settings for the anchor links that the `|anchors` Twig filter and `@anchors` GraphQL directive add to headings.
     *
     * ```php
     * ->anchors(\CraftCms\Cms\Config\AnchorsConfig::create()
     *     ->anchorLinkText('¶')
     * )
     * ```
     *
     * @group System
     *
     * @since 6.0.0
     * @see $anchors
     *
     * @param  AnchorsConfig|array<string, mixed>|string  $value
     */
    public function anchors(AnchorsConfig|array|string $value): self
    {
        $this->anchors->merge($value);

        return $this;
    }

    /**
     * Settings for assets: which files can be uploaded, how they’re uploaded and named, where Craft keeps their files, and which asset transformers volumes can use.
     *
     * ```php
     * ->assets(\CraftCms\Cms\Config\AssetsConfig::create()
     *     ->extraAllowedFileExtensions(['mbox', 'xml'])
     *     ->maxUploadFileSize('25M')
     *     ->defaultTransformer(env('ASSET_TRANSFORMER'))
     *     ->addTransformer('craft', ['outputDisk' => 'transforms'])
     *     ->addTransformer('imgix', ['driver' => 'imgix', 'domain' => env('IMGIX_DOMAIN')])
     * )
     * ```
     *
     * @group Assets
     *
     * @since 6.0.0
     * @see $assets
     *
     * @param  AssetsConfig|array<string, mixed>|string  $value
     */
    public function assets(AssetsConfig|array|string $value): self
    {
        $this->assets->merge($value);

        return $this;
    }

    /**
     * Whether element queries should automatically lazy eager-load relations for the other elements in their result set during site requests.
     *
     * ```php
     * ->autoEagerLoadElements(false)
     * ```
     *
     * @group System
     *
     * @see $autoEagerLoadElements
     */
    public function autoEagerLoadElements(bool $value = true): self
    {
        $this->autoEagerLoadElements = $value;

        return $this;
    }

    /**
     * Whether users should automatically be logged in after activating their account.
     *
     * ```php
     * ->autoLoginAfterAccountActivation(true)
     * ```
     *
     * @group System
     *
     * @see $autoLoginAfterAccountActivation
     */
    public function autoLoginAfterAccountActivation(bool $value = true): self
    {
        $this->autoLoginAfterAccountActivation = $value;

        return $this;
    }

    /**
     * The default length of time Craft will store data, RSS feed, and template caches.
     *
     * If set to `0`, data and RSS feed caches will be stored indefinitely.
     *
     * See {@see ConfigHelper::durationInSeconds()} for a list of supported value types.
     *
     * ```php
     * ->cacheDuration(0)
     * ```
     *
     * @group System
     *
     * @defaultAlt 1 day
     *
     * @see $cacheDuration
     */
    public function cacheDuration(mixed $value): self
    {
        $this->cacheDuration = ConfigHelper::durationInSeconds($value);

        return $this;
    }

    /**
     * List of additional HTML tags that should be included in the `<head>` of control panel pages.
     *
     * Each tag can be specified as an array of the tag name and its attributes.
     *
     * For example, you can give the control panel a custom favicon (etc.) like this:
     *
     * ```php
     * ->cpHeadTags([
     *     // Traditional favicon
     *     ['link', ['rel' => 'icon', 'href' => '/icons/favicon.ico']],
     *     // Scalable favicon for browsers that support them
     *     ['link', ['rel' => 'icon', 'type' => 'image/svg+xml', 'sizes' => 'any', 'href' => '/icons/favicon.svg']],
     *     // Touch icon for mobile devices
     *     ['link', ['rel' => 'apple-touch-icon', 'sizes' => '180x180', 'href' => '/icons/touch-icon.svg']],
     *     // Pinned tab icon for Safari
     *     ['link', ['rel' => 'mask-icon', 'href' => '/icons/mask-icon.svg', 'color' => '#663399']],
     * ])
     * ```
     *
     * @group System
     *
     * @param  list<array{0: string, 1?: array<string, mixed>}>  $value
     *
     * @see $cpHeadTags
     */
    public function cpHeadTags(array $value): self
    {
        $this->cpHeadTags = $value;

        return $this;
    }

    /**
     * The server path to the control panel’s icon, shown beside the system name in the global sidebar.
     *
     * This can be set to an aliased path such as `@root/branding/icon.svg`.
     *
     * A square SVG file is recommended. It will be displayed at 32px by 32px.
     *
     * SVG files are rendered inline, so they can adapt to the control panel’s light and dark themes. The selected
     * theme may differ from the operating system’s, so use the `light-dark()` CSS function or `currentColor` within
     * the SVG, rather than a `prefers-color-scheme` media query.
     *
     * ```php
     * ->cpIconPath('@root/branding/icon.svg')
     * ```
     *
     * @group System
     *
     * @since 6.0.0
     * @see $cpIconPath
     */
    public function cpIconPath(?string $value): self
    {
        $this->cpIconPath = $value;

        return $this;
    }

    /**
     * The URL to the control panel’s icon.
     *
     * @group System
     *
     * @deprecated in 6.0.0. {@see cpIconPath()} should be used instead.
     * @see $cpIconUrl
     */
    public function cpIconUrl(?string $value): self
    {
        $this->cpIconUrl = $value;

        return $this;
    }

    /**
     * The server path to the logo shown on the login page and other sign-in screens.
     *
     * This can be set to an aliased path such as `@root/branding/logo.svg`.
     *
     * An SVG file is recommended. It will be displayed at 288px wide.
     *
     * SVG files are rendered inline, so they can adapt to the control panel’s light and dark themes. Use the
     * `light-dark()` CSS function or `currentColor` within the SVG, as you would for {@see cpIconPath()}.
     *
     * ```php
     * ->cpLogoPath('@root/branding/logo.svg')
     * ```
     *
     * @group System
     *
     * @since 6.0.0
     * @see $cpLogoPath
     */
    public function cpLogoPath(?string $value): self
    {
        $this->cpLogoPath = $value;

        return $this;
    }

    /**
     * The URL to the logo shown on the login page and other sign-in screens.
     *
     * @group System
     *
     * @deprecated in 6.0.0. {@see cpLogoPath()} should be used instead.
     * @see $cpLogoUrl
     */
    public function cpLogoUrl(?string $value): self
    {
        $this->cpLogoUrl = $value;

        return $this;
    }

    /**
     * The path to a directory of SVG files that should be made available to icon pickers as a “Custom” icon set.
     *
     * ```php
     * ->customIconPath('@config/icons')
     * ```
     *
     * @group System
     *
     * @see $customIconPath
     * @since 5.12.0
     */
    public function customIconPath(?string $value): self
    {
        $this->customIconPath = $value;

        return $this;
    }

    /**
     * The two-letter country code that addresses will be set to by default.
     *
     * See <https://en.wikipedia.org/wiki/ISO_3166-1_alpha-2> for a list of acceptable country codes.
     *
     * ```php
     * ->defaultCountryCode('GB')
     * ```
     *
     * @group System
     *
     * @see $defaultCountryCode
     */
    public function defaultCountryCode(string $value): self
    {
        if (empty($value)) {
            throw new RuntimeException('`defaultCountryCode` cannot be empty', 0);
        }

        $this->defaultCountryCode = $value;

        return $this;
    }

    /**
     * The default language the control panel should use for users who haven’t set a preferred language yet.
     *
     * ```php
     * ->defaultCpLanguage('en-US')
     * ```
     *
     * @group System
     *
     * @throws RuntimeException
     *
     * @see $defaultCpLanguage
     */
    public function defaultCpLanguage(?string $value): self
    {
        if ($value !== null) {
            try {
                $value = I18N::normalizeLanguage($value);
                /** @phpstan-ignore catch.neverThrown */
            } catch (InvalidArgumentException $e) {
                throw new RuntimeException($e->getMessage(), 0, $e);
            }
        }

        $this->defaultCpLanguage = $value;

        return $this;
    }

    /**
     * The default locale the control panel should use for date/number formatting, for users who haven’t set
     * a preferred language or formatting locale.
     *
     * If this is `null`, the <config5:defaultCpLanguage> config setting will determine which locale is used for date/number formatting by default.
     *
     * ```php
     * ->defaultCpLocale('en-US')
     * ```
     *
     * @group System
     *
     * @see $defaultCpLocale
     */
    public function defaultCpLocale(?string $value): self
    {
        $this->defaultCpLocale = $value;

        return $this;
    }

    /**
     * The default permission to be set for newly-generated directories.
     *
     * If set to `null`, the permission will be determined by the current environment.
     *
     * ```php
     * ->defaultDirMode(0744)
     * ```
     *
     * @group System
     *
     * @see $defaultDirMode
     * @since 4.2.0
     */
    public function defaultDirMode(mixed $value): self
    {
        $this->defaultDirMode = $value;

        return $this;
    }

    /**
     * The default options that should be applied to each search term.
     *
     * Options include:
     *
     * - `subLeft` – Whether to include keywords that contain the term, with additional characters before it. (`false` by default)
     * - `subRight` – Whether to include keywords that contain the term, with additional characters after it. (`true` by default)
     * - `exclude` – Whether search results should *exclude* records with this term. (`false` by default)
     * - `exact` – Whether the term must be an exact match (only applies if the search term specifies an attribute). (`false` by default)
     *
     * ```php
     * ->defaultSearchTermOptions([
     *     'subLeft' => true,
     *     'exclude' => 'secret',
     * ])
     * ```
     *
     * @group System
     *
     * @param  array<string, bool|string>  $value
     *
     * @see $defaultSearchTermOptions
     */
    public function defaultSearchTermOptions(array $value): self
    {
        $this->defaultSearchTermOptions = $value;

        return $this;
    }

    /**
     * The default day new users should have set as their Week Start Day.
     *
     * This should be set to one of the following integers:
     *
     * - `0` – Sunday
     * - `1` – Monday
     * - `2` – Tuesday
     * - `3` – Wednesday
     * - `4` – Thursday
     * - `5` – Friday
     * - `6` – Saturday
     *
     * ```php
     * ->defaultWeekStartDay(0)
     * ```
     *
     * @group System
     *
     * @defaultAlt Monday
     *
     * @see $defaultWeekStartDay
     */
    public function defaultWeekStartDay(int $value): self
    {
        $this->defaultWeekStartDay = $value;

        return $this;
    }

    /**
     * Array of plugin handles that should be disabled, regardless of what the project config says.
     *
     * ```php
     * ->disabledPlugins([
     *     'webhooks',
     * ])
     * ```
     *
     * This can also be set to `'*'` to disable **all** plugins.
     *
     * ```php
     * ->disabledPlugins('*')
     * ```
     *
     * ::: warning
     * This should not be set on a per-environment basis, as it could result in plugin schema version mismatches
     * between environments, which will prevent project config changes from getting applied.
     * :::
     *
     * ```php
     * ->disabledPlugins(['redactor', 'webhooks'])
     * ```
     *
     * @group System
     *
     * @param  list<string>|string|null  $value
     *
     * @see $disabledPlugins
     */
    public function disabledPlugins(string|array|null $value): self
    {
        if (is_string($value) && $value !== '*') {
            $value = str($value)->explode(',')->all();
        }

        $this->disabledPlugins = $value;

        return $this;
    }

    /**
     * Array of utility IDs that should be disabled.
     *
     * ```php
     *  ->disabledUtilities([
     *      'updates',
     *      'find-replace',
     *  ])
     * ```
     *
     * @group System
     *
     * @param  string[]  $value
     *
     * @see $disabledUtilities
     */
    public function disabledUtilities(array $value): self
    {
        $this->disabledUtilities = $value;

        return $this;
    }

    /**
     * Whether front end requests should respond with `X-Robots-Tag: none` HTTP headers, indicating that pages should not be indexed,
     * and links on the page should not be followed, by web crawlers.
     *
     * ::: tip
     * This should be set to `true` for development and staging environments.
     * :::
     *
     * ```php
     * ->disallowRobots(true)
     * ```
     *
     * @group System
     *
     * @see $disallowRobots
     */
    public function disallowRobots(bool $value = true): self
    {
        $this->disallowRobots = $value;

        return $this;
    }

    /**
     * List of extra locale IDs that the application should support, and users should be able to select as their Preferred Language.
     *
     * ```php
     * ->extraAppLocales(['uk'])
     * ```
     *
     * @group System
     *
     * @param  string[]  $value
     *
     * @throws RuntimeException
     *
     * @see $extraAppLocales
     */
    public function extraAppLocales(array $value): self
    {
        foreach ($value as &$localeId) {
            try {
                $localeId = I18N::normalizeLanguage($localeId);
                /** @phpstan-ignore catch.neverThrown */
            } catch (InvalidArgumentException $e) {
                throw new RuntimeException($e->getMessage(), 0, $e);
            }
        }

        $this->extraAppLocales = $value;

        return $this;
    }

    /**
     * Settings for how long garbage collection keeps old data.
     *
     * ```php
     * ->garbageCollection(\CraftCms\Cms\Config\GarbageCollectionConfig::create()
     *     ->softDeleteDuration(0)
     *     ->purgePendingUsersDuration('P2W')
     * )
     * ```
     *
     * @group System
     *
     * @since 6.0.0
     * @see $garbageCollection
     *
     * @param  GarbageCollectionConfig|array<string, mixed>|string  $value
     */
    public function garbageCollection(GarbageCollectionConfig|array|string $value): self
    {
        $this->garbageCollection->merge($value);

        return $this;
    }

    /**
     * Settings for the GraphQL API.
     *
     * ```php
     * ->gql(\CraftCms\Cms\Config\GqlConfig::create()
     *     ->enableIntrospection(false)
     *     ->maxDepth(10)
     * )
     * ```
     *
     * @group GraphQL
     *
     * @since 6.0.0
     * @see $gql
     *
     * @param  GqlConfig|array<string, mixed>|string  $value
     */
    public function gql(GqlConfig|array|string $value): self
    {
        $this->gql->merge($value);

        return $this;
    }

    /**
     * The casing to use for autogenerated component handles.
     *
     * This can be set to one of the following:
     *
     * - `camel` – for camelCase
     * - `pascal` – for PascalCase (aka UpperCamelCase)
     * - `snake` – for snake_case
     *
     * ```php
     * ->handleCasing('pascal')
     * ```
     *
     * @group System
     *
     * @phpstan-param self::CAMEL_CASE|self::PASCAL_CASE|self::SNAKE_CASE $value
     *
     * @see $handleCasing
     */
    public function handleCasing(string $value): self
    {
        $this->handleCasing = $value;

        return $this;
    }

    /**
     * Whether the system should run in Headless Mode, which optimizes the system and control panel for headless CMS implementations.
     *
     * When this is enabled, the following changes will take place:
     *
     * - Template settings for sections and category groups will be hidden.
     * - Template route management will be hidden.
     * - Front-end routing will skip checks for element and template requests.
     * - Front-end responses will be JSON-formatted rather than HTML by default.
     * - Twig will be configured to escape unsafe strings for JavaScript/JSON rather than HTML by default for front-end requests.
     * - The <config5:loginPath>, <config5:logoutPath>, <config5:setPasswordPath>, and <config5:verifyEmailPath> settings will be ignored.
     *
     * ::: tip
     * With Headless Mode enabled, users may only set passwords and verify email addresses via the control panel. Be sure to grant “Access the control
     * panel” permission to all content editors and administrators. You’ll also need to set the <config5:baseCpUrl> config setting if the control
     * panel is located on a different domain than your front end.
     * :::
     *
     * ```php
     * ->headlessMode(true)
     * ```
     *
     * @group System
     *
     * @see $headlessMode
     */
    public function headlessMode(bool $value = true): self
    {
        $this->headlessMode = $value;

        return $this;
    }

    /**
     * The proxy server that should be used for outgoing HTTP requests.
     *
     * This can be set to a URL (`http://localhost`) or a URL plus a port (`http://localhost:8125`).
     *
     * ```php
     * ->httpProxy('http://localhost')
     * ```
     *
     * @group System
     *
     * @see $httpProxy
     */
    public function httpProxy(?string $value): self
    {
        $this->httpProxy = $value;

        return $this;
    }

    /**
     * Whether to automatically generate IDE helper files for custom fields.
     *
     * When enabled, Craft will generate PHPDoc metadata files in the IDE helper path
     * whenever field layouts are saved. This improves IDE autocompletion for custom fields.
     *
     * ```php
     * ->ideHelperEnabled(false)
     * ```
     *
     * @group System
     *
     * @see $ideHelperEnabled
     * @since 6.0.0
     */
    public function ideHelperEnabled(bool $value = true): self
    {
        $this->ideHelperEnabled = $value;

        return $this;
    }

    /**
     * The path where IDE helper files should be written, relative to the project root.
     *
     * ```php
     * ->ideHelperPath('vendor/_craft')
     * ```
     *
     * @group System
     *
     * @see $ideHelperPath
     * @since 6.0.0
     */
    public function ideHelperPath(string $value): self
    {
        $this->ideHelperPath = $value;

        return $this;
    }

    /**
     * Settings for how Craft cleanses and transforms images.
     *
     * ```php
     * ->images(\CraftCms\Cms\Config\ImagesConfig::create()
     *     ->driver('imagick')
     *     ->defaultQuality(90)
     * )
     * ```
     *
     * @group Image Handling
     *
     * @since 6.0.0
     * @see $images
     *
     * @param  ImagesConfig|array<string, mixed>|string  $value
     */
    public function images(ImagesConfig|array|string $value): self
    {
        $this->images->merge($value);

        return $this;
    }

    /**
     * Custom locale aliases, which will be included when fetching all known locales.
     *
     * Each item in the array should have a key that defines the custom locale ID, and its value should be set
     * to an array with the following keys:
     *
     * - `aliasOf`: The original locale ID
     * - `displayName`: The locale alias’s display name _(optional)_
     *
     * @group System
     *
     * @param  array<string, array{aliasOf: string, displayName?: string}>  $value
     */
    public function localeAliases(array $value): self
    {
        $this->localeAliases = $value;

        return $this;
    }

    /**
     * The maximum number of revisions that should be stored for each element.
     *
     * Set to `0` if you want to store an unlimited number of revisions.
     *
     * ```php
     * ->maxRevisions(25)
     * ```
     *
     * @group System
     *
     * @see $maxRevisions
     */
    public function maxRevisions(?int $value): self
    {
        $this->maxRevisions = $value;

        return $this;
    }

    /**
     * The maximum amount of memory Craft will try to reserve during memory-intensive operations such as zipping,
     * unzipping and updating. Defaults to an empty string, which means it will use as much memory as it can.
     *
     * See <https://php.net/manual/en/faq.using.php#faq.using.shorthandbytes> for a list of acceptable values.
     *
     * ```php
     * ->phpMaxMemoryLimit('512M')
     * ```
     *
     * @group System
     *
     * @see $phpMaxMemoryLimit
     */
    public function phpMaxMemoryLimit(?string $value): self
    {
        $this->phpMaxMemoryLimit = $value;

        return $this;
    }

    /**
     * Whether Single section entries should be preloaded for Twig templates.
     *
     * When enabled, Craft will make an educated guess on which Singles should be preloaded for each template based on
     * the variable names that are referenced.
     *
     * ::: warning
     * You will need to clear your compiled templates from the Caches utility before this setting will take effect.
     * :::
     *
     * ```php
     * ->preloadSingles()
     * ```
     *
     * @group System
     *
     * @see $preloadSingles
     */
    public function preloadSingles(bool $value = true): self
    {
        $this->preloadSingles = $value;

        return $this;
    }

    /** @param array<string, mixed>|string $value */
    public function mcp(McpConfig|array|string $value): self
    {
        $this->mcp->merge($value);

        return $this;
    }

    /**
     * Settings for Craft’s queue jobs.
     *
     * ```php
     * ->queue(\CraftCms\Cms\Config\QueueConfig::create()
     *     ->runAutomatically(false)
     *     ->name('craft')
     * )
     * ```
     *
     * @group System
     *
     * @since 6.0.0
     * @see $queue
     *
     * @param  QueueConfig|array<string, mixed>|string  $value
     */
    public function queue(QueueConfig|array|string $value): self
    {
        $this->queue->merge($value);

        return $this;
    }

    /**
     * Settings for how Craft routes requests and builds URLs.
     *
     * ```php
     * ->routing(\CraftCms\Cms\Config\RoutingConfig::create()
     *     ->cpTrigger('cms')
     *     ->loginPath('login')
     * )
     * ```
     *
     * @group System
     *
     * @since 6.0.0
     * @see $routing
     *
     * @param  RoutingConfig|array<string, mixed>|string  $value
     */
    public function routing(RoutingConfig|array|string $value): self
    {
        $this->routing->merge($value);

        return $this;
    }

    /**
     * Whether the system should run in Safe Mode.
     *
     * Safe Mode disables all plugins and application config that can alter Craft's expected default behavior.
     *
     * ```php
     * ->safeMode(true)
     * ```
     *
     * @group System
     *
     * @see $safeMode
     */
    public function safeMode(bool $value = true): self
    {
        $this->safeMode = $value;

        return $this;
    }

    /**
     * Settings for authentication, rate limits, and tokens.
     *
     * ```php
     * ->security(\CraftCms\Cms\Config\SecurityConfig::create()
     *     ->maxInvalidLogins(3)
     *     ->storeUserIps()
     * )
     * ```
     *
     * @group System
     *
     * @since 6.0.0
     * @see $security
     *
     * @param  SecurityConfig|array<string, mixed>|string  $value
     */
    public function security(SecurityConfig|array|string $value): self
    {
        $this->security->merge($value);

        return $this;
    }

    /**
     * Whether an `X-Powered-By: Craft CMS` header should be sent, helping services like [BuiltWith](https://builtwith.com/) and
     * [Wappalyzer](https://www.wappalyzer.com/) identify that the site is running on Craft.
     *
     * ```php
     * ->sendPoweredByHeader(false)
     * ```
     *
     * @group System
     *
     * @see $sendPoweredByHeader
     */
    public function sendPoweredByHeader(bool $value = true): self
    {
        $this->sendPoweredByHeader = $value;

        return $this;
    }

    /**
     * Settings for element slugs.
     *
     * ```php
     * ->slugs(\CraftCms\Cms\Config\SlugsConfig::create()
     *     ->wordSeparator('.')
     *     ->limitAutoToAscii()
     * )
     * ```
     *
     * @group System
     *
     * @since 6.0.0
     * @see $slugs
     *
     * @param  SlugsConfig|array<string, mixed>|string  $value
     */
    public function slugs(SlugsConfig|array|string $value): self
    {
        $this->slugs->merge($value);

        return $this;
    }

    /**
     * Whether entries’ statuses should be stored statically, and only get updated on entry save, or when the
     * `update-statuses` command is executed.
     *
     * ```php
     * ->staticStatuses()
     * ```
     *
     * @group System
     *
     * @see $staticStatuses
     */
    public function staticStatuses(bool $value = true): self
    {
        $this->staticStatuses = $value;

        return $this;
    }

    /**
     * The URL to a CSS file that should be included when rendering system templates on the front end,
     * such as the Login and Set Password templates.
     *
     * ```php
     * ->systemTemplateCss('/css/cp-theme.css');
     * ```
     *
     * @group System
     *
     * @see $systemTemplateCss
     */
    public function systemTemplateCss(?string $value): self
    {
        $this->systemTemplateCss = $value;

        return $this;
    }

    /**
     * Settings for how Craft finds, routes, and compiles templates.
     *
     * ```php
     * ->templates(\CraftCms\Cms\Config\TemplatesConfig::create()
     *     ->defaultExtensions(['twig', 'html'])
     *     ->privateTrigger('')
     * )
     * ```
     *
     * @group System
     *
     * @since 6.0.0
     * @see $templates
     *
     * @param  TemplatesConfig|array<string, mixed>|string  $value
     */
    public function templates(TemplatesConfig|array|string $value): self
    {
        $this->templates->merge($value);

        return $this;
    }

    /**
     * The timezone of the site. If set, it will take precedence over the Timezone setting in Settings → General
     * (stored in project config).
     *
     * This can be set to one of PHP’s [supported timezones](https://php.net/manual/en/timezones.php).
     *
     * ```php
     * ->timezone('Europe/London')
     * ```
     *
     * @group System
     *
     * @see $timezone
     */
    public function timezone(?string $value): self
    {
        $this->timezone = $value;

        return $this;
    }

    /**
     * Whether translated messages should be wrapped in special characters to help find any strings that are not being run through
     * `t()` or the `|translate` filter.
     *
     * ```php
     * ->translationDebugOutput(true)
     * ```
     *
     * The symbols are as follows:
     *
     * | Symbol | Example | Category |
     * | --- | --- | --- |
     * | `$` | `$Date Field$` | Site (front-end, `site.php`) |
     * | `@` | `@Entry Type@` | Application (Craft, `app.php`) |
     * | `%` | `%Object Template% | Other (plugin or custom source) |
     *
     * Translations _may_ be nested or surrounded by multiple symbols.
     *
     * @group System
     *
     * @see $translationDebugOutput
     */
    public function translationDebugOutput(bool $value = true): self
    {
        $this->translationDebugOutput = $value;

        return $this;
    }

    /**
     * Whether Craft should set users’ usernames to their email addresses, rather than let them set their username separately.
     *
     * If you enable this setting after user accounts already exist, run this terminal command to update existing usernames:
     *
     * ```bash
     * php artisan craft:utils:update-usernames
     * ```
     *
     * ```php
     * ->useEmailAsUsername(true)
     * ```
     *
     * @group System
     *
     * @see $useEmailAsUsername
     */
    public function useEmailAsUsername(bool $value = true): self
    {
        $this->useEmailAsUsername = $value;

        return $this;
    }

    /**
     * Whether the [`IDNA_NONTRANSITIONAL_TO_UNICODE`](https://www.php.net/manual/en/intl.constants.php#constant.idna-nontransitional-to-unicode)
     * flag should be passed to [idn_to_utf8()](https://www.php.net/manual/en/function.idn-to-utf8.php) when converting
     * email addresses from IDNA ASCII to Unicode.
     *
     * `INTL_IDNA_VARIANT_UTS46` by default, which uses the UTS 46 algorithm, consistent with the requirements of the
     * IDNA2008 protocol and mostly compatible with IDNA2003 (deprecated in PHP 7.2).
     *
     * There are a handful of characters which result in different resolution of IDNs between IDNA2008 and IDNA2003,
     * including ß, ς, and joiner characters (ZWJ and ZWNJ). ([More info](https://unicode.org/reports/tr46/#Deviations))
     *
     * For example, `ß` will be converted to `ss` by default. Enabling this setting will ensure it gets preserved as `ß`.
     *
     * ```php
     * ->useIdnaNontransitionalToUnicode(true)
     * ```
     *
     * @group System
     *
     * @see $useIdnaNontransitionalToUnicode
     * @since 5.9.0
     */
    public function useIdnaNontransitionalToUnicode(bool $value = false): self
    {
        $this->useIdnaNontransitionalToUnicode = $value;

        return $this;
    }

    /**
     * Settings for user accounts.
     *
     * ```php
     * ->users(\CraftCms\Cms\Config\UsersConfig::create()
     *     ->showFirstAndLastNameFields()
     *     ->disable2fa()
     * )
     * ```
     *
     * @group System
     *
     * @since 6.0.0
     * @see $users
     *
     * @param  UsersConfig|array<string, mixed>|string  $value
     */
    public function users(UsersConfig|array|string $value): self
    {
        $this->users->merge($value);

        return $this;
    }

    /**
     * Returns the temporary asset upload disk name.
     *
     * @deprecated in 6.0.0. [[AssetsConfig::getTempUploadDisk()]] should be used instead.
     */
    public function getTempAssetUploadDisk(): string
    {
        return $this->assets->getTempUploadDisk();
    }

    /**
     * Returns the upload session disk name.
     *
     * @deprecated in 6.0.0. [[AssetsConfig::getUploadSessionDisk()]] should be used instead.
     */
    public function getUploadSessionDisk(): string
    {
        return $this->assets->getUploadSessionDisk();
    }

    /**
     * Returns the disk name that soft-deleted assets’ files should be moved to, if one is set.
     *
     * @deprecated in 6.0.0. [[AssetsConfig::getTrashDisk()]] should be used instead.
     */
    public function getTrashedAssetDisk(): ?string
    {
        return $this->assets->getTrashDisk();
    }

    /** @deprecated in 6.0.0. [[SecurityConfig::getAuthGuard()]] should be used instead. */
    public function getAuthGuard(): string
    {
        return $this->security->getAuthGuard();
    }

    /** @deprecated in 6.0.0. [[SecurityConfig::getAuthPasswordBroker()]] should be used instead. */
    public function getAuthPasswordBroker(): string
    {
        return $this->security->getAuthPasswordBroker();
    }

    /** @deprecated in 6.0.0. [[SecurityConfig::getPasswordConfirmationKey()]] should be used instead. */
    public function getPasswordConfirmationKey(): string
    {
        return $this->security->getPasswordConfirmationKey();
    }

    /** @deprecated in 6.0.0. [[RoutingConfig::getActivateAccountSuccessPath()]] should be used instead. */
    public function getActivateAccountSuccessPath(?string $siteHandle = null): string
    {
        return $this->routing->getActivateAccountSuccessPath($siteHandle);
    }

    /** @deprecated in 6.0.0. [[RoutingConfig::getVerifyEmailPath()]] should be used instead. */
    public function getVerifyEmailPath(?string $siteHandle = null): string
    {
        return $this->routing->getVerifyEmailPath($siteHandle);
    }

    /** @deprecated in 6.0.0. [[RoutingConfig::getVerifyEmailSuccessPath()]] should be used instead. */
    public function getVerifyEmailSuccessPath(?string $siteHandle = null): string
    {
        return $this->routing->getVerifyEmailSuccessPath($siteHandle);
    }

    /** @deprecated in 6.0.0. [[RoutingConfig::getInvalidUserTokenPath()]] should be used instead. */
    public function getInvalidUserTokenPath(?string $siteHandle = null): ?string
    {
        return $this->routing->getInvalidUserTokenPath($siteHandle);
    }

    /** @deprecated in 6.0.0. [[RoutingConfig::getLoginPath()]] should be used instead. */
    public function getLoginPath(?string $siteHandle = null): mixed
    {
        return $this->routing->getLoginPath($siteHandle);
    }

    /** @deprecated in 6.0.0. [[RoutingConfig::getLogoutPath()]] should be used instead. */
    public function getLogoutPath(?string $siteHandle = null): mixed
    {
        return $this->routing->getLogoutPath($siteHandle);
    }

    /** @deprecated in 6.0.0. [[RoutingConfig::getPostCpLoginRedirect()]] should be used instead. */
    public function getPostCpLoginRedirect(): string
    {
        return $this->routing->getPostCpLoginRedirect();
    }

    /** @deprecated in 6.0.0. [[RoutingConfig::getPostLoginRedirect()]] should be used instead. */
    public function getPostLoginRedirect(?string $siteHandle = null): string
    {
        return $this->routing->getPostLoginRedirect($siteHandle);
    }

    /** @deprecated in 6.0.0. [[RoutingConfig::getPostLogoutRedirect()]] should be used instead. */
    public function getPostLogoutRedirect(?string $siteHandle = null): string
    {
        return $this->routing->getPostLogoutRedirect($siteHandle);
    }

    /** @deprecated in 6.0.0. [[RoutingConfig::getSetPasswordPath()]] should be used instead. */
    public function getSetPasswordPath(?string $siteHandle = null): string
    {
        return $this->routing->getSetPasswordPath($siteHandle);
    }

    /** @deprecated in 6.0.0. [[RoutingConfig::getSetPasswordRequestPath()]] should be used instead. */
    public function getSetPasswordRequestPath(?string $siteHandle = null): ?string
    {
        return $this->routing->getSetPasswordRequestPath($siteHandle);
    }

    /** @deprecated in 6.0.0. [[RoutingConfig::getSetPasswordSuccessPath()]] should be used instead. */
    public function getSetPasswordSuccessPath(?string $siteHandle = null): string
    {
        return $this->routing->getSetPasswordSuccessPath($siteHandle);
    }

    /**
     * Returns whether the DB should be backed up before running new migrations.
     *
     * @deprecated in 6.0.0. [[BackupsConfig::shouldBackUpOnUpdate()]] should be used instead.
     */
    public function getBackupOnUpdate(): bool
    {
        return $this->backups->shouldBackUpOnUpdate();
    }

    /** @deprecated in 6.0.0. [[RoutingConfig::getPageTrigger()]] should be used instead. */
    public function getPageTrigger(): string
    {
        return $this->routing->getPageTrigger();
    }

    /** @deprecated in 6.0.0. [[RoutingConfig::getPageTriggerParam()]] should be used instead. */
    public function getPageTriggerParam(): string
    {
        return $this->routing->getPageTriggerParam();
    }
}
