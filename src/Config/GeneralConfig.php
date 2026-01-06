<?php

declare(strict_types=1);

namespace CraftCms\Cms\Config;

use Closure;
use Craft;
use craft\helpers\DateTimeHelper;
use CraftCms\Cms\Support\Attributes\EnvName;
use CraftCms\Cms\Support\Config as ConfigHelper;
use CraftCms\Cms\Support\Facades\Deprecator;
use CraftCms\Cms\Support\Facades\I18N;
use CraftCms\Cms\Support\PHP;
use DateInterval;
use Deprecated;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Http\Middleware\TrustProxies;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Traits\Conditionable;
use yii\base\InvalidArgumentException;
use yii\base\InvalidConfigException;

use function CraftCms\Cms\t;

/**
 * General config class
 */
class GeneralConfig extends BaseConfig
{
    use Conditionable;

    public const string IMAGE_DRIVER_AUTO = 'auto';

    public const string IMAGE_DRIVER_GD = 'gd';

    public const string IMAGE_DRIVER_IMAGICK = 'imagick';

    public const string CAMEL_CASE = 'camel';

    public const string PASCAL_CASE = 'pascal';

    public const string SNAKE_CASE = 'snake';

    protected static array $renamedSettings = [
        'activateAccountFailurePath' => 'invalidUserTokenPath',
        'allowAutoUpdates' => 'allowUpdates',
        'backupDbOnUpdate' => 'backupOnUpdate',
        'defaultFilePermissions' => 'defaultFileMode',
        'defaultFolderPermissions' => 'defaultDirMode',
        'enableGraphQlCaching' => 'enableGraphqlCaching',
        'environmentVariables' => 'aliases',
        'isSystemOn' => 'isSystemLive',
        'restoreDbOnUpdateFailure' => 'restoreOnUpdateFailure',
        'useWriteFileLock' => 'useFileLocks',
        'validationKey' => 'securityKey',
    ];

    /**
     * @var array The default user accessibility preferences that should be applied to users that haven’t saved their preferences yet.
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
     * @var string The URI segment Craft should look for when determining if the current request should be routed to a controller action.
     *
     * ::: code
     * ```php Static Config
     * ->actionTrigger('do-it')
     * ```
     * ```shell Environment Override
     * CRAFT_ACTION_TRIGGER=do-it
     * ```
     * :::
     *
     * @group Routing
     */
    public string $actionTrigger = 'actions';

    /**
     * @var mixed The URI that users without access to the control panel should be redirected to after activating their account.
     *
     * See {@see ConfigHelper::localizedValue()} for a list of supported value types.
     *
     * ::: code
     * ```php Static Config
     * ->activateAccountSuccessPath('welcome')
     * ```
     * ```shell Environment Override
     * CRAFT_ACTIVATE_ACCOUNT_SUCCESS_PATH=welcome
     * ```
     * :::
     *
     * @see getActivateAccountSuccessPath()
     *
     * @group Routing
     */
    public mixed $activateAccountSuccessPath = '';

    /**
     * @var bool Whether auto-generated URLs should have trailing slashes.
     *
     * ::: code
     * ```php Static Config
     * ->addTrailingSlashesToUrls(true)
     * ```
     * ```shell Environment Override
     * CRAFT_ADD_TRAILING_SLASHES_TO_URLS=true
     * ```
     * :::
     *
     * @group Routing
     */
    public bool $addTrailingSlashesToUrls = false;

    /**
     * @var array<string,string|null> Any custom Yii [aliases](https://www.yiiframework.com/doc/guide/2.0/en/concept-aliases) that should be defined for every request.
     *
     * ```php Static Config
     * ->aliases([
     *     '@webroot' => '/var/www/',
     * ])
     * ```
     *
     * @group Environment
     */
    public array $aliases = [];

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
     * ::: code
     * ```php Static Config
     * ->allowAdminChanges(false)
     * ```
     * ```shell Environment Override
     * CRAFT_ALLOW_ADMIN_CHANGES=false
     * ```
     * :::
     *
     * @group System
     */
    public bool $allowAdminChanges = true;

    /**
     * @var string[]|null|false The Ajax origins that should be allowed to access the GraphQL API, if enabled.
     *
     * If this is set to an array, then `graphql/api` requests will only include the current request’s [[\yii\web\Request::getOrigin()|origin]]
     * in the `Access-Control-Allow-Origin` response header if it’s listed here.
     *
     * If this is set to `false`, then the `Access-Control-Allow-Origin` response header will never be sent.
     *
     * ::: code
     * ```php Static Config
     * ->allowedGraphqlOrigins(false)
     * ```
     * ```shell Environment Override
     * CRAFT_ALLOW_GRAPHQL_ORIGINS=false
     * ```
     * :::
     *
     * @group GraphQL
     *
     * @since 3.5.0
     * @deprecated in 4.11.0. [[\craft\filters\Cors]] should be used instead.
     * @see https://www.yiiframework.com/doc/api/2.0/yii-filters-cors
     */
    public array|null|false $allowedGraphqlOrigins = null;

    /**
     * @var bool Whether Craft should allow system and plugin updates in the control panel, and plugin installation from the Plugin Store.
     *
     * This setting will automatically be disabled if <config5:allowAdminChanges> is disabled.
     *
     * ::: code
     * ```php Static Config
     * ->allowUpdates(false)
     * ```
     * ```shell Environment Override
     * CRAFT_ALLOW_UPDATES=false
     * ```
     * :::
     *
     * @group System
     */
    public bool $allowUpdates = true;

    /**
     * @var string[] The file extensions Craft should allow when a user is uploading files.
     *
     * ```php Static Config
     * // Nothing bug GIFs!
     * ->allowedFileExtensions([
     *     'gif',
     * ])
     * ```
     *
     * @see extraAllowedFileExtensions
     *
     * @group Assets
     */
    public array $allowedFileExtensions = [
        '7z',
        'aiff',
        'asc',
        'asf',
        'avi',
        'avif',
        'bmp',
        'cap',
        'cin',
        'csv',
        'dfxp',
        'doc',
        'docx',
        'dotm',
        'dotx',
        'fla',
        'flv',
        'gif',
        'gz',
        'gzip',
        'heic',
        'heif',
        'hevc',
        'itt',
        'jp2',
        'jpeg',
        'jpg',
        'jpx',
        'js',
        'json',
        'lrc',
        'm2t',
        'm4a',
        'm4v',
        'mcc',
        'mid',
        'mov',
        'mp3',
        'mp4',
        'mpc',
        'mpeg',
        'mpg',
        'mpsub',
        'ods',
        'odt',
        'ogg',
        'ogv',
        'pdf',
        'png',
        'potx',
        'pps',
        'ppsm',
        'ppsx',
        'ppt',
        'pptm',
        'pptx',
        'ppz',
        'pxd',
        'qt',
        'ram',
        'rar',
        'rm',
        'rmi',
        'rmvb',
        'rt',
        'rtf',
        'sami',
        'sbv',
        'scc',
        'sdc',
        'sitd',
        'smi',
        'srt',
        'stl',
        'sub',
        'svg',
        'swf',
        'sxc',
        'sxw',
        'tar',
        'tds',
        'tgz',
        'tif',
        'tiff',
        'ttml',
        'txt',
        'vob',
        'vsd',
        'vtt',
        'wav',
        'webm',
        'webp',
        'wma',
        'wmv',
        'xls',
        'xlsx',
        'zip',
    ];

    /**
     * @var bool Whether users should be allowed to create similarly-named tags.
     *
     * ::: code
     * ```php Static Config
     * ->allowSimilarTags(true)
     * ```
     * ```shell Environment Override
     * CRAFT_ALLOW_SIMILAR_TAGS=true
     * ```
     * :::
     *
     * @group System
     */
    public bool $allowSimilarTags = false;

    /**
     * @var bool Whether uppercase letters should be allowed in slugs.
     *
     * ::: code
     * ```php Static Config
     * ->allowUppercaseInSlug(true)
     * ```
     * ```shell Environment Override
     * CRAFT_ALLOW_UPPERCASE_IN_SLUG=true
     * ```
     * :::
     *
     * @group Routing
     */
    public bool $allowUppercaseInSlug = false;

    /**
     * @var bool Whether users should automatically be logged in after activating their account.
     *
     * ::: code
     * ```php Static Config
     * ->autoLoginAfterAccountActivation(true)
     * ```
     * ```shell Environment Override
     * CRAFT_AUTO_LOGIN_AFTER_ACCOUNT_ACTIVATION=true
     * ```
     * :::
     *
     * @group System
     */
    public bool $autoLoginAfterAccountActivation = false;

    /**
     * @var bool Whether drafts should be saved automatically as they are edited.
     *
     * Note that drafts *will* be autosaved while Live Preview is open, regardless of this setting.
     *
     * ::: code
     *  ```php Static Config
     *  ->autosaveDrafts(false)
     *  ```
     * ```shell Environment Override
     * CRAFT_AUTOSAVE_DRAFTS=false
     * ```
     * :::
     *
     * @group System
     *
     * @since 3.5.6
     * @deprecated in 4.0.0
     */
    public bool $autosaveDrafts = true;

    /**
     * @var string|null The base URL Craft should use when generating control panel URLs.
     *
     * It will be determined automatically if left blank.
     *
     * ::: tip
     * The base control panel URL should **not** include the [control panel trigger word](config5:cpTrigger) (e.g. `/admin`).
     * :::
     *
     * ::: code
     * ```php Static Config
     * ->baseCpUrl('https://cms.my-project.tld/')
     * ```
     * ```shell Environment Override
     * CRAFT_BASE_CP_URL=https://cms.my-project.tld/
     * ```
     * :::
     *
     * @group Routing
     */
    public ?string $baseCpUrl = null;

    /**
     * @var bool Whether Craft should create a database backup before applying a new system update.
     *
     * ::: code
     * ```php Static Config
     * ->backupOnUpdate(false)
     * ```
     * ```shell Environment Override
     * CRAFT_BACKUP_ON_UPDATE=false
     * ```
     * :::
     *
     * @see backupCommand
     *
     * @group System
     */
    public bool $backupOnUpdate = true;

    /**
     * @var string|null|false|Closure The shell command that Craft should execute to create a database backup.
     *
     * When set to `null` (default), Craft will run `mysqldump` or `pg_dump`, provided that those libraries are in the `$PATH` variable
     * for the system user running the web server.
     *
     * You may provide your own command, which can include several tokens Craft will substitute at runtime:
     *
     * - `{file}` - the target backup file path
     * - `{port}` - the current database port
     * - `{server}` - the current database hostname
     * - `{user}` - user that was used to connect to the database
     * - `{password}` - password for the specified `{user}`
     * - `{database}` - the current database name
     * - `{schema}` - the current database schema (if any)
     *
     * This can also be set to `false` to disable database backups completely.
     *
     * ::: code
     * ```php Static Config
     * ->backupCommand(false)
     * ```
     * ```shell Environment Override
     * CRAFT_BACKUP_COMMAND=false
     * ```
     * :::
     *
     * @group Environment
     */
    public string|null|false|Closure $backupCommand = null;

    /**
     * @var string|null The output format that database backups should use (PostgreSQL only).
     *
     * This setting has no effect with MySQL databases.
     *
     * Valid options are `custom`, `directory`, `tar`, or `plain`.
     * When set to `null` (default), `pg_restore` will default to `plain`
     *
     * @see https://www.postgresql.org/docs/current/app-pgdump.html
     *
     *  ::: code
     *  ```php Static Config
     *  ->backupCommandFormat('custom')
     *  ```
     *  ```shell Environment Override
     *  CRAFT_BACKUP_COMMAND_FORMAT=custom
     *  ```
     *  :::
     *
     * @group Environment
     */
    public ?string $backupCommandFormat = null;

    /**
     * @var int The higher the cost value, the longer it takes to generate a password hash and to verify against it.
     *
     * Therefore, higher cost slows down a brute-force attack.
     *
     * For best protection against brute force attacks, set it to the highest value that is tolerable on production servers.
     *
     * The time taken to compute the hash doubles for every increment by one for this value.
     *
     * For example, if the hash takes 1 second to compute when the value is 14 then the compute time varies as
     * 2^(value - 14) seconds.
     *
     * ::: code
     * ```php Static Config
     * ->blowfishHashCost(15)
     * ```
     * ```shell Environment Override
     * CRAFT_BLOWFISH_HASH_COST=15
     * ```
     * :::
     *
     * @group Security
     */
    public int $blowfishHashCost = 13;

    /**
     * @var string|null The server path to an image file that should be sent when responding to an image request with a
     *                  404 status code.
     *
     * This can be set to an aliased path such as `@webroot/assets/404.svg`.
     *
     * ::: code
     * ```php Static Config
     * ->brokenImagePath('@webroot/assets/404.svg')
     * ```
     * ```shell Environment Override
     * CRAFT_BROKEN_IMAGE_PATH=@webroot/assets/404.svg
     * ```
     * :::
     *
     * @group Image Handling
     */
    public ?string $brokenImagePath = null;

    /**
     * @var string|null A unique ID representing the current build of the codebase.
     *
     * This should be set to something unique to the deployment, e.g. a Git SHA or a deployment timestamp.
     *
     * ::: code
     * ```php Static Config
     * ->buildId(\craft\helpers\App::env('GIT_SHA'))
     * ```
     * ```shell Environment Override
     * CRAFT_BUILD_ID=$GIT_SHA
     * ```
     * :::
     *
     * @group Environment
     */
    public ?string $buildId = null;

    /**
     * @var mixed The default length of time Craft will store data, RSS feed, and template caches.
     *
     * If set to `0`, data and RSS feed caches will be stored indefinitely.
     *
     * See {@see ConfigHelper::durationInSeconds()} for a list of supported value types.
     *
     * ::: code
     * ```php Static Config
     * ->cacheDuration(0)
     * ```
     * ```shell Environment Override
     * CRAFT_CACHE_DURATION=0
     * ```
     * :::
     *
     * @group System
     *
     * @defaultAlt 1 day
     */
    public mixed $cacheDuration = 86400;

    /**
     * @var bool Whether uploaded filenames with non-ASCII characters should be converted to ASCII (i.e. `ñ` → `n`).
     *
     * ::: tip
     * You can run `php craft utils/ascii-filenames` in your terminal to apply ASCII filenames to all existing assets.
     * :::
     *
     * ::: code
     * ```php Static Config
     * ->convertFilenamesToAscii(false)
     * ```
     * ```shell Environment Override
     * CRAFT_CONVERT_FILENAMES_TO_ASCII=false
     * ```
     * :::
     *
     * @group Assets
     */
    public bool $convertFilenamesToAscii = false;

    /**
     * @var mixed The amount of time a user must wait before re-attempting to log in after their account is locked due to too many
     *            failed login attempts.
     *
     * Set to `0` to keep the account locked indefinitely, requiring an admin to manually unlock the account.
     *
     * See {@see ConfigHelper::durationInSeconds()} for a list of supported value types.
     *
     * ::: code
     * ```php Static Config
     * ->cooldownDuration(0)
     * ```
     * ```shell Environment Override
     * CRAFT_COOLDOWN_DURATION=0
     * ```
     * :::
     *
     * @group Security
     *
     * @defaultAlt 5 minutes
     */
    public mixed $cooldownDuration = 300;

    /**
     * @var array List of additional HTML tags that should be included in the `<head>` of control panel pages.
     *
     * Each tag can be specified as an array of the tag name and its attributes.
     *
     * For example, you can give the control panel a custom favicon (etc.) like this:
     *
     * ```php Static Config
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
     * @var string|null The URI segment Craft should look for when determining if the current request should route to the control panel rather than
     *                  the front-end website.
     *
     * This can be set to `null` if you have a dedicated hostname for the control panel (e.g. `cms.my-project.tld`), or you are running Craft in
     * [Headless Mode](config5:headlessMode). If you do that, you will need to ensure that the control panel is being served from its own web root
     * directory on your server, with an `index.php` file that defines the `CRAFT_CP` PHP constant.
     *
     * ```php
     * define('CRAFT_CP', true);
     * ```
     *
     * Alternatively, you can set the <config5:baseCpUrl> config setting, but then you will run the risk of losing access to portions of your
     * control panel due to URI conflicts with actual folders/files in your main web root.
     *
     * (For example, if you have an `assets/` folder, that would conflict with the `/assets` page in the control panel.)
     *
     * ::: code
     * ```php Static Config
     * ->cpTrigger(null)
     * ```
     * ```shell Environment Override
     * CRAFT_CP_TRIGGER=
     * ```
     * :::
     *
     * @group Routing
     */
    public ?string $cpTrigger = 'admin';

    /**
     * @var string The name of CSRF token used for CSRF validation if <config5:enableCsrfProtection> is set to `true`.
     *
     * ::: code
     * ```php Static Config
     * ->csrfTokenName('MY_CSRF')
     * ```
     * ```shell Environment Override
     * CRAFT_CSRF_TOKEN_NAME=MY_CSRF
     * ```
     * :::
     *
     * @group Security
     *
     * @see enableCsrfProtection
     */
    public string $csrfTokenName = '_token';

    /**
     * @var string The domain that cookies generated by Craft should be created for. If blank, it will be left up to the browser to determine
     *             which domain to use (almost always the current). If you want the cookies to work for all subdomains, for example, you could
     *             set this to `'.my-project.tld'`.
     *
     * ::: code
     * ```php Static Config
     * ->defaultCookieDomain('.my-project.tld')
     * ```
     * ```shell Environment Override
     * CRAFT_DEFAULT_COOKIE_DOMAIN=.my-project.tld
     * ```
     * :::
     *
     * @group Environment
     */
    public string $defaultCookieDomain = '';

    /**
     * @var string The two-letter country code that addresses will be set to by default.
     *
     * See <https://en.wikipedia.org/wiki/ISO_3166-1_alpha-2> for a list of acceptable country codes.
     *
     * ::: code
     * ```php Static Config
     * ->defaultCountryCode('GB')
     * ```
     * ```shell Environment Override
     * CRAFT_DEFAULT_COUNTRY_CODE=GB
     * ```
     * :::
     *
     * @group System
     */
    public string $defaultCountryCode = 'US';

    /**
     * @var string|null The default language the control panel should use for users who haven’t set a preferred language yet.
     *
     * ::: code
     * ```php Static Config
     * ->defaultCpLanguage('en-US')
     * ```
     * ```shell Environment Override
     * CRAFT_DEFAULT_CP_LANGUAGE=en-US
     * ```
     * :::
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
     * ::: code
     * ```php Static Config
     * ->defaultCpLocale('en-US')
     * ```
     * ```shell Environment Override
     * CRAFT_DEFAULT_CP_LOCALE=en-US
     * ```
     * :::
     *
     * @group System
     */
    public ?string $defaultCpLocale = null;

    /**
     * @var mixed The default permission to be set for newly-generated directories.
     *
     * If set to `null`, the permission will be determined by the current environment.
     *
     * ::: code
     * ```php Static Config
     * ->defaultDirMode(0744)
     * ```
     * ```shell Environment Override
     * CRAFT_DEFAULT_DIR_MODE=0744
     * ```
     * :::
     *
     * @group System
     */
    public mixed $defaultDirMode = 0775;

    /**
     * @var int|null The default permission to be set for newly-generated files.
     *
     * If set to `null`, the permission will be determined by the current environment.
     *
     * ::: code
     * ```php Static Config
     * ->defaultFileMode(0744)
     * ```
     * ```shell Environment Override
     * CRAFT_DEFAULT_FILE_MODE=0744
     * ```
     * :::
     *
     * @group System
     */
    public ?int $defaultFileMode = null;

    /**
     * @var int The quality level Craft will use when saving JPG and PNG files. Ranges from 1 (worst quality, smallest file) to
     *          100 (best quality, biggest file).
     *
     * ::: code
     * ```php Static Config
     * ->defaultImageQuality(90)
     * ```
     * ```shell Environment Override
     * CRAFT_DEFAULT_IMAGE_QUALITY=90
     * ```
     * :::
     *
     * @group Image Handling
     */
    public int $defaultImageQuality = 82;

    /**
     * @var array The default options that should be applied to each search term.
     *
     * Options include:
     *
     * - `subLeft` – Whether to include keywords that contain the term, with additional characters before it. (`false` by default)
     * - `subRight` – Whether to include keywords that contain the term, with additional characters after it. (`true` by default)
     * - `exclude` – Whether search results should *exclude* records with this term. (`false` by default)
     * - `exact` – Whether the term must be an exact match (only applies if the search term specifies an attribute). (`false` by default)
     *
     * ```php Static Config
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
     * @var string[] The template file extensions Craft will look for when matching a template path to a file on the front end.
     *
     * ::: code
     * ```php Static Config
     * ->defaultTemplateExtensions(['twig', 'html', 'txt'])
     * ```
     * ```shell Environment Override
     * CRAFT_DEFAULT_TEMPLATE_EXTENSIONS=twig,html,txt
     * ```
     * :::
     *
     * @group System
     */
    public array $defaultTemplateExtensions = ['twig', 'html'];

    /**
     * @var mixed The default amount of time tokens can be used before expiring.
     *
     * See {@see ConfigHelper::durationInSeconds()} for a list of supported value types.
     *
     * ::: code
     * ```php Static Config
     * // One week
     * ->defaultTokenDuration(604800)
     * ```
     * ```shell Environment Override
     * # One week
     * CRAFT_DEFAULT_TOKEN_DURATION=604800
     * ```
     * :::
     *
     * @group Security
     *
     * @defaultAlt 1 day
     */
    public mixed $defaultTokenDuration = 86400;

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
     * ::: code
     * ```php Static Config
     * ->defaultWeekStartDay(0)
     * ```
     * ```shell Environment Override
     * CRAFT_DEFAULT_WEEK_START_DAY=0
     * ```
     * :::
     *
     * @group System
     *
     * @defaultAlt Monday
     */
    public int $defaultWeekStartDay = 1;

    /**
     * @var bool By default, Craft requires a front-end “password” field for public user registrations. Setting this to
     *           `true` removes that requirement for the initial registration form. Instead, new users will set their password
     *           once they’ve followed the link in their activation email.
     *
     * ::: code
     * ```php Static Config
     * ->deferPublicRegistrationPassword(true)
     * ```
     * ```shell Environment Override
     * CRAFT_DEFER_PUBLIC_REGISTRATION_PASSWORD=true
     * ```
     * :::
     *
     * @group Security
     */
    public bool $deferPublicRegistrationPassword = false;

    /**
     * @var bool Whether the system should run in [Dev Mode](https://craftcms.com/support/dev-mode).
     *
     * ::: code
     * ```php Static Config
     * ->devMode(true)
     * ```
     * ```shell Environment Override
     * CRAFT_DEV_MODE=true
     * ```
     * :::
     *
     * @group System
     */
    public bool $devMode = false;

    /**
     * @var bool Whether two-step verification features should be disabled.
     *
     * ::: code
     * ```php Static Config
     * ->disable2fa()
     * ```
     * ```shell Environment Override
     * CRAFT_DISABLE_2FA=true
     * ```
     * :::
     *
     * @group Users
     */
    #[EnvName('DISABLE_2FA')]
    public bool $disable2fa = false;

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
     * ::: code
     * ```php Static Config
     * ->disabledPlugins([
     *     'redactor',
     *     'webhooks',
     * ])
     * ```
     * ```shell Environment Override
     * CRAFT_DISABLED_PLUGINS=redactor,webhooks
     * ```
     * :::
     *
     * @group System
     */
    public string|array|null $disabledPlugins = null;

    /**
     * @var string[] Array of utility IDs that should be disabled.
     *
     * ::: code
     * ```php Static Config
     *  ->disabledUtilities([
     *      'updates',
     *      'find-replace',
     *  ])
     * ```
     * ```shell Environment Override
     * CRAFT_DISABLED_UTILITIES=updates,find-replace
     * ```
     * :::
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
     * ::: code
     * ```php Static Config
     * ->disallowRobots(true)
     * ```
     * ```shell Environment Override
     * CRAFT_DISALLOW_ROBOTS=true
     * ```
     * :::
     *
     * @group System
     */
    public bool $disallowRobots = false;

    /**
     * @var bool Whether the `transform` directive should be disabled for the GraphQL API.
     *
     * ::: code
     * ```php Static Config
     * ->disableGraphqlTransformDirective(true)
     * ```
     * ```shell Environment Override
     * CRAFT_DISABLE_GRAPHQL_TRANSFORM_DIRECTIVE=true
     * ```
     * :::
     *
     * @group GraphQL
     */
    public bool $disableGraphqlTransformDirective = false;

    /**
     * @var bool Whether CSRF values should be injected via JavaScript for greater cache-ability. This setting can be overridden by passing an `async` option into the `csrfInput()` function.
     *
     *  ::: code
     *  ```php Static Config
     *  ->asyncCsrfInputs(true)
     *  ```
     *  ```shell Environment Override
     *  CRAFT_ASYNC_CSRF_INPUTS=true
     *  ```
     *  :::
     *
     * @group Security
     */
    public bool $asyncCsrfInputs = false;

    /**
     * @var bool Whether front-end web requests should support basic HTTP authentication.
     *
     * ::: code
     * ```php Static Config
     * ->enableBasicHttpAuth(true)
     * ```
     * ```shell Environment Override
     * CRAFT_ENABLE_BASIC_HTTP_AUTH=true
     * ```
     * :::
     *
     * @group Security
     *
     * @since 3.5.0
     * @deprecated in 4.13.0. [[\craft\filters\BasicHttpAuthLogin]] should be used instead.
     */
    public bool $enableBasicHttpAuth = false;

    /**
     * @var bool Whether to use a cookie to persist the CSRF token if <config5:enableCsrfProtection> is enabled. If false, the CSRF token will be
     *           stored in session under the `csrfTokenName` config setting name. Note that while storing CSRF tokens in session increases security,
     *           it requires starting a session for every page that a CSRF token is needed, which may degrade site performance.
     *
     * ::: code
     * ```php Static Config
     * ->enableCsrfCookie(false)
     * ```
     * ```shell Environment Override
     * CRAFT_ENABLE_CSRF_COOKIE=false
     * ```
     * :::
     *
     * @see enableCsrfProtection
     *
     * @group Security
     */
    public bool $enableCsrfCookie = true;

    /**
     * @var bool Whether to enable CSRF protection via hidden form inputs for all forms submitted via Craft.
     *
     * ::: code
     * ```php Static Config
     * ->enableCsrfProtection(false)
     * ```
     * ```shell Environment Override
     * CRAFT_ENABLE_CSRF_PROTECTION=false
     * ```
     * :::
     *
     * @see csrfTokenName
     * @see enableCsrfCookie
     *
     * @group Security
     */
    public bool $enableCsrfProtection = true;

    /**
     * @var bool Whether GraphQL introspection queries are allowed. Defaults to `true` and is always allowed in the control panel.
     *
     * ::: code
     * ```php Static Config
     * ->enableGraphqlIntrospection(false)
     * ```
     * ```shell Environment Override
     * CRAFT_ENABLE_GRAPHQL_INTROSPECTION=false
     * ```
     * :::
     *
     * @group GraphQL
     */
    public bool $enableGraphqlIntrospection = true;

    /**
     * @var bool Whether the GraphQL API should be enabled.
     *
     * The GraphQL API is only available for Craft Pro.
     *
     * ::: code
     * ```php Static Config
     * ->enableGql(false)
     * ```
     * ```shell Environment Override
     * CRAFT_ENABLE_GQL=false
     * ```
     * :::
     *
     * @group GraphQL
     */
    public bool $enableGql = true;

    /**
     * @var mixed The amount of time a user’s elevated session will last, which is required for some sensitive actions (e.g. user group/permission assignment).
     *
     * Set to `0` to disable elevated session support.
     *
     * See {@see ConfigHelper::durationInSeconds()} for a list of supported value types.
     *
     * ::: code
     * ```php Static Config
     * ->elevatedSessionDuration(0)
     * ```
     * ```shell Environment Override
     * CRAFT_ELEVATED_SESSION_DURATION=0
     * ```
     * :::
     *
     * @group Security
     *
     * @defaultAlt 5 minutes
     */
    public mixed $elevatedSessionDuration = 300;

    /**
     * @var bool Whether Craft should cache GraphQL queries.
     *
     * If set to `true`, Craft will cache the results for unique GraphQL queries per access token. The cache is automatically invalidated any time
     * an element is saved, the site structure is updated, or a GraphQL schema is saved.
     *
     * This setting will have no effect if a plugin is using the [[\craft\services\Gql::EVENT_BEFORE_EXECUTE_GQL_QUERY]] event to provide its own
     * caching logic and setting the `result` property.
     *
     * ::: code
     * ```php Static Config
     * ->enableGraphqlCaching(false)
     * ```
     * ```shell Environment Override
     * CRAFT_ENABLE_GRAPHQL_CACHING=false
     * ```
     * :::
     *
     * @group GraphQL
     */
    public bool $enableGraphqlCaching = true;

    /**
     * @var bool Whether dates returned by the GraphQL API should be set to the system time zone by default, rather than UTC.
     *
     * ::: code
     * ```php Static Config
     * ->setGraphqlDatesToSystemTimeZone(true)
     * ```
     * ```shell Environment Override
     * CRAFT_SET_GRAPHQL_DATES_TO_SYSTEM_TIMEZONE=true
     * ```
     * :::
     *
     * @group GraphQL
     */
    public bool $setGraphqlDatesToSystemTimeZone = false;

    /**
     * @var bool Whether to enable Craft’s template `{% cache %}` tag on a global basis.
     *
     * ::: code
     * ```php Static Config
     * ->enableTemplateCaching(false)
     * ```
     * ```shell Environment Override
     * CRAFT_ENABLE_TEMPLATE_CACHING=false
     * ```
     * :::
     *
     * @see https://craftcms.com/docs/templating/cache
     *
     * @group System
     */
    public bool $enableTemplateCaching = true;

    /**
     * @var string The prefix that should be prepended to HTTP error status codes when determining the path to look for an error’s template.
     *
     * If set to `'_'` your site’s 404 template would live at `templates/_404.twig`, for example.
     *
     * ::: code
     * ```php Static Config
     * ->errorTemplatePrefix('_')
     * ```
     * ```shell Environment Override
     * CRAFT_ERROR_TEMPLATE_PREFIX=_
     * ```
     * :::
     *
     * @group System
     */
    public string $errorTemplatePrefix = '';

    /**
     * @var string[]|null List of file extensions that will be merged into the <config5:allowedFileExtensions> config setting.
     *
     * ::: code
     * ```php Static Config
     * ->extraAllowedFileExtensions(['mbox', 'xml'])
     * ```
     * ```shell Environment Override
     * CRAFT_EXTRA_ALLOWED_FILE_EXTENSIONS=mbox,xml
     * ```
     * :::
     *
     * @see allowedFileExtensions
     *
     * @group System
     */
    public ?array $extraAllowedFileExtensions = null;

    /**
     * @var string[] List of extra locale IDs that the application should support, and users should be able to select as their Preferred Language.
     *
     * ::: code
     * ```php Static Config
     * ->extraAppLocales(['uk'])
     * ```
     * ```shell Environment Override
     * CRAFT_EXTRA_APP_LOCALES=uk
     * ```
     * :::
     *
     * @group System
     */
    public array $extraAppLocales = [];

    /**
     * @var array List of additional file kinds Craft should support. This array will get merged with the one defined in
     *            `\craft\helpers\Assets::_buildFileKinds()`.
     *
     * ```php Static Config
     * ->extraFileKinds([
     *     // merge .psb into list of Photoshop file kinds
     *     'photoshop' => [
     *         'extensions' => ['psb'],
     *     ],
     *     // register new "Stylesheet" file kind
     *     'stylesheet' => [
     *         'label' => 'Stylesheet',
     *         'extensions' => ['css', 'less', 'pcss', 'sass', 'scss', 'styl'],
     *     ],
     * ])
     * ```
     *
     * ::: tip
     * File extensions listed here won’t immediately be allowed to be uploaded. You will also need to list them with
     * the <config5:extraAllowedFileExtensions> config setting.
     * :::
     *
     * @group Assets
     */
    public array $extraFileKinds = [];

    /**
     * @var string[] Any additional last name prefixes that should be supported by the name parser.
     *
     * ::: code
     * ```php Static Config
     * ->extraLastNamePrefixes(['Dal', 'Van Der'])
     * ```
     * ```shell Environment Override
     * CRAFT_EXTRA_LAST_NAME_PREFIXES="Dal,Van Der"
     * ```
     * :::
     *
     * @group Users
     */
    public array $extraLastNamePrefixes = [];

    /**
     * @var string[] Any additional name salutations that should be supported by the name parser.
     *
     * ::: code
     * ```php Static Config
     * ->extraNameSalutations(['Lady', 'Sire'])
     * ```
     * ```shell Environment Override
     * CRAFT_EXTRA_NAME_SALUTATIONS=Lady,Sire
     * ```
     * :::
     *
     * @group Users
     */
    public array $extraNameSalutations = [];

    /**
     * @var string[] Any additional name suffixes that should be supported by the name parser.
     *
     * ::: code
     * ```php Static Config
     * ->extraNameSuffixes(['CCNA', 'OBE'])
     * ```
     * ```shell Environment Override
     * CRAFT_EXTRA_NAME_SUFFIXES=CCNA,OBE
     * ```
     * :::
     *
     * @group Users
     */
    public array $extraNameSuffixes = [];

    /**
     * @var string|false The string to use to separate words when uploading assets. If set to `false`, spaces will be left alone.
     *
     * ::: code
     * ```php Static Config
     * ->filenameWordSeparator(false)
     * ```
     * ```shell Environment Override
     * CRAFT_FILENAME_WORD_SEPARATOR=false
     * ```
     * :::
     *
     * @group Assets
     */
    public string|false $filenameWordSeparator = '-';

    /**
     * @var bool Whether image transforms should be generated before page load.
     *
     * ::: code
     * ```php Static Config
     * ->generateTransformsBeforePageLoad(true)
     * ```
     * ```shell Environment Override
     * CRAFT_GENERATE_TRANSFORMS_BEFORE_PAGE_LOAD=true
     * ```
     * :::
     *
     * @group Image Handling
     */
    public bool $generateTransformsBeforePageLoad = false;

    /**
     * @var string Prefix to use for all type names returned by GraphQL.
     *
     * ::: code
     * ```php Static Config
     * ->gqlTypePrefix('craft_')
     * ```
     * ```shell Environment Override
     * CRAFT_GQL_TYPE_PREFIX=craft_
     * ```
     * :::
     *
     * @group GraphQL
     */
    public string $gqlTypePrefix = '';

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
     * ::: code
     * ```php Static Config
     * ->handleCasing('pascal')
     * ```
     * ```shell Environment Override
     * CRAFT_HANDLE_CASING=pascal
     * ```
     * :::
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
     * ::: code
     * ```php Static Config
     * ->headlessMode(true)
     * ```
     * ```shell Environment Override
     * CRAFT_HEADLESS_MODE=true
     * ```
     * :::
     *
     * @group System
     */
    public bool $headlessMode = false;

    /**
     * @var string|null The proxy server that should be used for outgoing HTTP requests.
     *
     * This can be set to a URL (`http://localhost`) or a URL plus a port (`http://localhost:8125`).
     *
     * ::: code
     * ```php Static Config
     * ->httpProxy('http://localhost')
     * ```
     * ```shell Environment Override
     * CRAFT_HTTP_PROXY=http://localhost
     * ```
     * :::
     *
     * @group System
     */
    public ?string $httpProxy = null;

    /**
     * @var mixed The image driver Craft should use to cleanse and transform images. By default Craft will use ImageMagick if it’s installed
     *            and otherwise fall back to GD. You can explicitly set either `'imagick'` or `'gd'` here to override that behavior.
     *
     * ::: code
     * ```php Static Config
     * ->imageDriver('imagick')
     * ```
     * ```shell Environment Override
     * CRAFT_IMAGE_DRIVER=imagick
     * ```
     * :::
     *
     * @group Image Handling
     */
    public mixed $imageDriver = self::IMAGE_DRIVER_AUTO;

    /**
     * @var array An array containing the selectable image aspect ratios for the image editor. The array must be in the format
     *            of `label` => `ratio`, where ratio must be a float or a string. For string values, only values of “none” and “original” are allowed.
     *
     * ```php Static Config
     * ->imageEditorRatios([
     *     'Unconstrained' => 'none',
     *     'Original' => 'original',
     *     'Square' => 1,
     *     'IMAX' => 1.9,
     *     'Widescreen' => 1.78,
     * ])
     * ```
     *
     * @group Image Handling
     */
    public array $imageEditorRatios = [
        'Unconstrained' => 'none',
        'Original' => 'original',
        'Square' => 1,
        '16:9' => 1.78,
        '10:8' => 1.25,
        '7:5' => 1.4,
        '4:3' => 1.33,
        '5:3' => 1.67,
        '3:2' => 1.5,
    ];

    /**
     * @var string[] The template filenames Craft will look for within a directory to represent the directory’s “index” template when
     *               matching a template path to a file on the front end.
     *
     * ::: code
     * ```php Static Config
     * ->indexTemplateFilenames(['index', 'default'])
     * ```
     * ```shell Environment Override
     * CRAFT_INDEX_TEMPLATE_FILENAMES=index,default
     * ```
     * :::
     *
     * @group System
     */
    public array $indexTemplateFilenames = ['index'];

    /**
     * @var mixed The amount of time to track invalid login attempts for a user, for determining if Craft should lock an account.
     *
     * See {@see ConfigHelper::durationInSeconds()} for a list of supported value types.
     *
     * ::: code
     * ```php Static Config
     * // 1 day
     * ->invalidLoginWindowDuration(86400)
     * ```
     * ```shell Environment Override
     * # 1 day
     * CRAFT_INVALID_LOGIN_WINDOW_DURATION=86400
     * ```
     * :::
     *
     * @group Security
     *
     * @defaultAlt 1 hour
     */
    public mixed $invalidLoginWindowDuration = 3600;

    /**
     * @var mixed The URI Craft should redirect to when user token validation fails. User tokens are used for
     *            email verification and password resets. If `null`, <config5:loginPath> will be used by default.
     *
     * See {@see ConfigHelper::localizedValue()} for a list of supported value types.
     *
     * Note that this only affects front-end site requests.
     *
     * ::: code
     * ```php Static Config
     * // 1 day
     * ->invalidUserTokenPath('nope')
     * ```
     * ```shell Environment Override
     * # 1 day
     * CRAFT_INVALID_USER_TOKEN_PATH=nope
     * ```
     * :::
     *
     * @see getInvalidUserTokenPath()
     *
     * @group Routing
     */
    public mixed $invalidUserTokenPath = null;

    /**
     * @var string[]|null List of headers where proxies store the real client IP.
     *
     * See [[\yii\web\Request::ipHeaders]] for more details.
     *
     * If not set, the default [[\craft\web\Request::ipHeaders]] value will be used.
     *
     * ::: code
     * ```php Static Config
     * ->ipHeaders(['X-Forwarded-For', 'CF-Connecting-IP'])
     * ```
     * ```shell Environment Override
     * CRAFT_IP_HEADERS=X-Forwarded-For,CF-Connecting-IP
     * ```
     * :::
     *
     * @group System
     */
    public ?array $ipHeaders = null;

    /**
     * @var bool|null Whether the site is currently live. If set to `true` or `false`, it will take precedence over the System Status setting
     *                in Settings → General.
     *
     * ::: code
     * ```php Static Config
     * ->isSystemLive(true)
     * ```
     * ```shell Environment Override
     * CRAFT_IS_SYSTEM_LIVE=true
     * ```
     * :::
     *
     * @group System
     */
    public ?bool $isSystemLive = null;

    /**
     * @var bool Whether GraphQL types should be generated lazily.
     *
     * ::: code
     * ```php Static Config
     * ->lazyGqlTypes(true)
     * ```
     * ```shell Environment Override
     * CRAFT_LAZY_GQL_TYPES=true
     * ```
     * :::
     *
     * @group GraphQL
     */
    public bool $lazyGqlTypes = false;

    /**
     * @var bool Whether non-ASCII characters in auto-generated slugs should be converted to ASCII (i.e. ñ → n).
     *
     * ::: tip
     * This only affects the JavaScript auto-generated slugs. Non-ASCII characters can still be used in slugs if entered manually.
     * :::
     *
     * ::: code
     * ```php Static Config
     * ->limitAutoSlugsToAscii(true)
     * ```
     * ```shell Environment Override
     * CRAFT_LIMIT_AUTO_SLUGS_TO_ASCII=true
     * ```
     * :::
     *
     * @group System
     */
    public bool $limitAutoSlugsToAscii = false;

    /**
     * @var array Custom locale aliases, which will be included when fetching all known locales.
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
     * @var mixed The URI Craft should use for user login on the front end.
     *
     * This can be set to `false` to disable front-end login.
     *
     * See {@see ConfigHelper::localizedValue()} for a list of supported value types.
     *
     * ::: code
     * ```php Static Config
     * ->loginPath(false)
     * ```
     * ```shell Environment Override
     * CRAFT_LOGIN_PATH=false
     * ```
     * :::
     *
     * @see getLoginPath()
     *
     * @group Routing
     */
    public mixed $loginPath = 'login';

    /**
     * @var mixed The URI Craft should use for user logout on the front end.
     *
     * This can be set to `false` to disable front-end logout.
     *
     * See {@see ConfigHelper::localizedValue()} for a list of supported value types.
     *
     * ::: code
     * ```php Static Config
     * ->logoutPath(false)
     * ```
     * ```shell Environment Override
     * CRAFT_LOGOUT_PATH=false
     * ```
     * :::
     *
     * @see getLogoutPath()
     *
     * @group Routing
     */
    public mixed $logoutPath = 'logout';

    /**
     * @var int The maximum dimension size to use when caching images from external sources to use in transforms. Set to `0` to never cache them. Defaults to `0` as of 5.9.0. Earlier versions default to `2000`.
     *
     * ::: code
     * ```php Static Config
     * ->maxCachedCloudImageSize(0)
     * ```
     * ```shell Environment Override
     * CRAFT_MAX_CACHED_CLOUD_IMAGE_SIZE=0
     * ```
     * :::
     *
     * @group Image Handling
     */
    public int $maxCachedCloudImageSize = 0;

    /**
     * @var int The maximum allowed GraphQL queries that can be executed in a single batched request. Set to `0` to allow any number of queries.
     *
     *  ::: code
     *  ```php Static Config
     *  ->maxGraphqlBatchSize(5)
     *  ```
     *  ```shell Environment Override
     *  CRAFT_MAX_GRAPHQL_BATCH_SIZE=5
     *  ```
     *  :::
     *
     * @group GraphQL
     */
    public int $maxGraphqlBatchSize = 0;

    /**
     * @var int The maximum allowed complexity a GraphQL query is allowed to have. Set to `0` to allow any complexity.
     *
     * ::: code
     * ```php Static Config
     * ->maxGraphqlComplexity(500)
     * ```
     * ```shell Environment Override
     * CRAFT_MAX_GRAPHQL_COMPLEXITY=500
     * ```
     * :::
     *
     * @group GraphQL
     */
    public int $maxGraphqlComplexity = 0;

    /**
     * @var int The maximum allowed depth a GraphQL query is allowed to reach. Set to `0` to allow any depth.
     *
     * ::: code
     * ```php Static Config
     * ->maxGraphqlDepth(5)
     * ```
     * ```shell Environment Override
     * CRAFT_MAX_GRAPHQL_DEPTH=5
     * ```
     * :::
     *
     * @group GraphQL
     */
    public int $maxGraphqlDepth = 0;

    /**
     * @var int The maximum allowed results for a single GraphQL query. Set to `0` to disable any limits.
     *
     * ::: code
     * ```php Static Config
     * ->maxGraphqlResults(100)
     * ```
     * ```shell Environment Override
     * CRAFT_MAX_GRAPHQL_RESULTS=100
     * ```
     * :::
     *
     * @group GraphQL
     */
    public int $maxGraphqlResults = 0;

    /**
     * @var int|false The number of invalid login attempts Craft will allow within the specified duration before the account gets locked.
     *
     * ::: code
     * ```php Static Config
     * ->maxInvalidLogins(3)
     * ```
     * ```shell Environment Override
     * CRAFT_MAX_INVALID_LOGINS=3
     * ```
     * :::
     *
     * @group Security
     */
    public int|false $maxInvalidLogins = 5;

    /**
     * @var int|false The number of backups Craft should make before it starts deleting the oldest backups. If set to `false`, Craft will
     *                not delete any backups.
     *
     * ::: code
     * ```php Static Config
     * ->maxBackups(5)
     * ```
     * ```shell Environment Override
     * CRAFT_MAX_BACKUPS=5
     * ```
     * :::
     *
     * @group System
     */
    public int|false $maxBackups = 20;

    /**
     * @var int|null The maximum number of revisions that should be stored for each element.
     *
     * Set to `0` if you want to store an unlimited number of revisions.
     *
     * ::: code
     * ```php Static Config
     * ->maxRevisions(25)
     * ```
     * ```shell Environment Override
     * CRAFT_MAX_REVISIONS=25
     * ```
     * :::
     *
     * @group System
     */
    public ?int $maxRevisions = 50;

    /**
     * @var int The highest number Craft will tack onto a slug in order to make it unique before giving up and throwing an error.
     *
     * ::: code
     * ```php Static Config
     * ->maxSlugIncrement(10)
     * ```
     * ```shell Environment Override
     * CRAFT_MAX_SLUG_INCREMENT=10
     * ```
     * :::
     *
     * @group System
     */
    public int $maxSlugIncrement = 100;

    /**
     * @var int|string The maximum upload file size allowed.
     *
     * See [[PHP::sizeToBytes()]] for a list of supported value types.
     *
     * ::: code
     * ```php Static Config
     * // 25MB
     * ->maxUploadFileSize(26214400)
     * ```
     * ```shell Environment Override
     * # 25MB
     * CRAFT_MAX_UPLOAD_FILE_SIZE=26214400
     * ```
     * :::
     *
     * @group Assets
     *
     * @defaultAlt 16MB
     */
    public string|int $maxUploadFileSize = 16777216;

    /**
     * @var bool Whether generated URLs should omit `index.php` (e.g. `http://my-project.tld/path` instead of `http://my-project.tld/index.php/path`)
     *
     * This can only be possible if your server is configured to redirect would-be 404s to `index.php`, for example, with the redirect found
     * in the `.htaccess` file that came with Craft:
     *
     * ```
     * RewriteEngine On
     * RewriteCond %{REQUEST_FILENAME} !-f
     * RewriteCond %{REQUEST_FILENAME} !-d
     * RewriteRule (.+) /index.php?p=$1 [QSA,L]
     * ```
     *
     * ::: code
     * ```php Static Config
     * ->omitScriptNameInUrls(true)
     * ```
     * ```shell Environment Override
     * CRAFT_OMIT_SCRIPT_NAME_IN_URLS=true
     * ```
     * :::
     *
     * ::: tip
     * Even when this is set to `true`, the script name could still be included in some action URLs.
     * If you want to ensure that `index.php` is fully omitted from **all** generated URLs, set the <config5:pathParam>
     * config setting to `null`.
     * :::
     *
     * @group Routing
     */
    public bool $omitScriptNameInUrls = true;

    /**
     * @var bool Whether Craft should optimize images for reduced file sizes without noticeably reducing image quality. (Only supported when
     *           ImageMagick is used.)
     *
     * ::: code
     * ```php Static Config
     * ->optimizeImageFilesize(false)
     * ```
     * ```shell Environment Override
     * CRAFT_OPTIMIZE_IMAGE_FILESIZE=false
     * ```
     * :::
     *
     * @see imageDriver
     *
     * @group Image Handling
     */
    public bool $optimizeImageFilesize = true;

    /**
     * @var string The string preceding a number which Craft will look for when determining if the current request is for a particular page in
     *             a paginated list of pages.
     *
     * | Example Value | Example URI |
     * | --- | --- |
     * | `p` | `/news/p5` |
     * | `page` | `/news/page5` |
     * | `page/` | `/news/page/5` |
     * | `?page` | `/news?page=5` |
     *
     * ::: warning
     * Craft may override this setting if it conflicts with <config5:pathParam>. If you want to set this to `?p` (e.g. `/news?p=5`), you’ll also need to change your <config5:pathParam> setting (which defaults to `p`).
     * Then, if your server is running Apache, you’ll need to update the redirect code in your `.htaccess` file to match your new `pathParam` value.
     * :::
     *
     * ::: code
     * ```php Static Config
     * ->pageTrigger('page')
     * ```
     * ```shell Environment Override
     * CRAFT_PAGE_TRIGGER=page
     * ```
     * :::
     *
     * @see getPageTrigger()
     *
     * @group Routing
     */
    public string $pageTrigger = 'p';

    /**
     * @var string The path within the `templates` folder where element partial templates will live.
     *
     * Partial templates are used to render elements when calling [[\craft\elements\db\ElementQuery::render()]],
     * [[\craft\elements\ElementCollection::render()]], or [[\craft\base\Element::render()]].
     *
     * For example, you could render all the entries within a Matrix field like so:
     *
     * ```twig
     * {{ entry.myMatrixField.render() }}
     * ```
     *
     * The full path to a partial template will also include the element type handle (e.g. `asset` or `entry`) and the
     * field layout provider’s handle (e.g. the volume handle or entry type handle). For an entry of type `article`,
     * that would be: `_partials/entry/article.twig`.
     *
     * ::: code
     * ```php Static Config
     * ->partialTemplatesPath('_cp/partials')
     * ```
     * ```shell Environment Override
     * CRAFT_PARTIAL_TEMPLATES_PATH=_cp/partials
     * ```
     * :::
     *
     * @group System
     */
    public string $partialTemplatesPath = '_partials';

    /**
     * @var string|null The query string param that Craft will check when determining the request’s path.
     *
     * This can be set to `null` if your web server is capable of directing traffic to `index.php` without a query string param.
     * If you’re using Apache, that means you’ll need to change the `RewriteRule` line in your `.htaccess` file to:
     *
     * ```
     * RewriteRule (.+) index.php [QSA,L]
     * ```
     *
     * ::: code
     * ```php Static Config
     * ->pathParam(null)
     * ```
     * ```shell Environment Override
     * CRAFT_PATH_PARAM=
     * ```
     * :::
     *
     * @group Routing
     */
    public ?string $pathParam = null;

    /**
     * @var string|null The `Permissions-Policy` header that should be sent for site responses.
     *
     * ::: code
     * ```php Static Config
     * ->permissionsPolicyHeader('Permissions-Policy: geolocation=(self)')
     * ```
     * ```shell Environment Override
     * CRAFT_PERMISSIONS_POLICY_HEADER=Permissions-Policy: geolocation=(self)
     * ```
     * :::
     *
     * @group System
     *
     * @since 3.6.14
     * @deprecated in 4.11.0. [[\craft\filters\Headers]] should be used instead.
     */
    public ?string $permissionsPolicyHeader = null;

    /**
     * @var string|null The maximum amount of memory Craft will try to reserve during memory-intensive operations such as zipping,
     *                  unzipping and updating. Defaults to an empty string, which means it will use as much memory as it can.
     *
     * See <https://php.net/manual/en/faq.using.php#faq.using.shorthandbytes> for a list of acceptable values.
     *
     * ::: code
     * ```php Static Config
     * ->phpMaxMemoryLimit('512M')
     * ```
     * ```shell Environment Override
     * CRAFT_PHP_MAX_MEMORY_LIMIT=512M
     * ```
     * :::
     *
     * @group System
     */
    public ?string $phpMaxMemoryLimit = null;

    /**
     * @var string The name of the PHP session cookie.
     *
     * ::: code
     * ```php Static Config
     * ->phpSessionName(null)
     * ```
     * ```shell Environment Override
     * CRAFT_PHP_SESSION_NAME=
     * ```
     * :::
     *
     * @see https://php.net/manual/en/function.session-name.php
     *
     * @group Session
     */
    public string $phpSessionName = 'CraftSessionId';

    /**
     * @var mixed The path users should be redirected to after logging into the control panel.
     *
     * This setting will also come into effect if a user visits the control panel’s login page (`/admin/login`) or the control panel’s
     * root URL (`/admin`) when they are already logged in.
     *
     * See {@see ConfigHelper::localizedValue()} for a list of supported value types.
     *
     * ::: code
     * ```php Static Config
     * ->postCpLoginRedirect('entries')
     * ```
     * ```shell Environment Override
     * CRAFT_POST_CP_LOGIN_REDIRECT=entries
     * ```
     * :::
     *
     * @see getPostCpLoginRedirect()
     *
     * @group Routing
     */
    public mixed $postCpLoginRedirect = 'dashboard';

    /**
     * @var mixed The path users should be redirected to after logging in from the front-end site.
     *
     * This setting will also come into effect if the user visits the login page (as specified by the <config5:loginPath> config setting) when
     * they are already logged in.
     *
     * See {@see ConfigHelper::localizedValue()} for a list of supported value types.
     *
     * ::: code
     * ```php Static Config
     * ->postLoginRedirect('welcome')
     * ```
     * ```shell Environment Override
     * CRAFT_POST_LOGIN_REDIRECT=welcome
     * ```
     * :::
     *
     * @see getPostLoginRedirect()
     *
     * @group Routing
     */
    public mixed $postLoginRedirect = '';

    /**
     * @var mixed The path that users should be redirected to after logging out from the front-end site.
     *
     * See {@see ConfigHelper::localizedValue()} for a list of supported value types.
     *
     * ::: code
     * ```php Static Config
     * ->postLogoutRedirect('goodbye')
     * ```
     * ```shell Environment Override
     * CRAFT_POST_LOGOUT_REDIRECT=goodbye
     * ```
     * :::
     *
     * @see getPostLogoutRedirect()
     *
     * @group Routing
     */
    public mixed $postLogoutRedirect = '';

    /**
     * @var bool Whether the <config5:gqlTypePrefix> config setting should have an impact on `query`, `mutation`, and `subscription` types.
     *
     * ::: code
     * ```php Static Config
     * ->prefixGqlRootTypes(false)
     * ```
     * ```shell Environment Override
     * CRAFT_PREFIX_GQL_ROOT_TYPES=false
     * ```
     * :::
     *
     * @group GraphQL
     */
    public bool $prefixGqlRootTypes = true;

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
     * ::: code
     * ```php Static Config
     * ->preloadSingles()
     * ```
     * ```shell Environment Override
     * CRAFT_PRELOAD_SINGLES=true
     * ```
     * :::
     *
     * @group System
     */
    public bool $preloadSingles = false;

    /**
     * @var bool Whether CMYK should be preserved as the colorspace when manipulating images.
     *
     * Setting this to `true` will prevent Craft from transforming CMYK images to sRGB, but on some ImageMagick versions it can cause
     * image color distortion. This will only have an effect if ImageMagick is in use.
     *
     * ::: code
     * ```php Static Config
     * ->preserveCmykColorspace(true)
     * ```
     * ```shell Environment Override
     * CRAFT_PRESERVE_CMYK_COLORSPACE=true
     * ```
     * :::
     *
     * @group Image Handling
     */
    public bool $preserveCmykColorspace = false;

    /**
     * @var bool Whether the EXIF data should be preserved when manipulating and uploading images.
     *
     * Setting this to `true` will result in larger image file sizes.
     *
     * This will only have effect if ImageMagick is in use.
     *
     * ::: code
     * ```php Static Config
     * ->preserveExifData(true)
     * ```
     * ```shell Environment Override
     * CRAFT_PRESERVE_EXIF_DATA=true
     * ```
     * :::
     *
     * @group Image Handling
     */
    public bool $preserveExifData = false;

    /**
     * @var bool Whether the embedded Image Color Profile (ICC) should be preserved when manipulating images.
     *
     * Setting this to `false` will reduce the image size a little bit, but on some ImageMagick versions can cause images to be saved with
     * an incorrect gamma value, which causes the images to become very dark. This will only have effect if ImageMagick is in use.
     *
     * ::: code
     * ```php Static Config
     * ->preserveImageColorProfiles(false)
     * ```
     * ```shell Environment Override
     * CRAFT_PRESERVE_IMAGE_COLOR_PROFILES=false
     * ```
     * :::
     *
     * @group Image Handling
     */
    public bool $preserveImageColorProfiles = true;

    /**
     * @var bool When `true`, Craft will always return a successful response in the “forgot password” flow, making it difficult to enumerate users.
     *
     * When set to `false` and you go through the “forgot password” flow from the control panel login page, you’ll get distinct messages indicating
     * whether the username/email exists and whether an email was sent with further instructions. This can be helpful for the user attempting to
     * log in but allow for username/email enumeration based on the response.
     *
     * ::: code
     * ```php Static Config
     * ->preventUserEnumeration(true)
     * ```
     * ```shell Environment Override
     * CRAFT_PREVENT_USER_ENUMERATION=true
     * ```
     * :::
     *
     * @group Security
     */
    public bool $preventUserEnumeration = false;

    /**
     * @var array Custom [iFrame Resizer options](http://davidjbradshaw.github.io/iframe-resizer/#options) that should be used for preview iframes.
     *
     * ```php Static Config
     * ->previewIframeResizerOptions([
     *     'autoResize' => false,
     * ])
     * ```
     *
     * @group System
     */
    public array $previewIframeResizerOptions = [];

    /**
     * @var mixed The amount of time content preview tokens can be used before expiring.
     *
     * Defaults to <config5:defaultTokenDuration> value.
     *
     * See {@see ConfigHelper::durationInSeconds()} for a list of supported value types.
     *
     * ::: code
     * ```php Static Config
     * // 1 hour
     * ->previewTokenDuration(3600)
     * ```
     * ```shell Environment Override
     * # 1 hour
     * CRAFT_PREVIEW_TOKEN_DURATION=3600
     * ```
     * :::
     *
     * @group Security
     *
     * @defaultAlt 1 day
     */
    public mixed $previewTokenDuration = null;

    /**
     * @var string The template path segment prefix that should be used to identify “private” templates, which are templates that are not
     *             directly accessible via a matching URL.
     *
     * Set to an empty value to disable public template routing.
     *
     * ::: code
     * ```php Static Config
     * ->privateTemplateTrigger('')
     * ```
     * ```shell Environment Override
     * CRAFT_PRIVATE_TEMPLATE_TRIGGER=
     * ```
     * :::
     *
     * @group System
     */
    public string $privateTemplateTrigger = '_';

    /**
     * @var mixed The amount of time to wait before Craft purges pending users from the system that have not activated.
     *
     * Any content assigned to a pending user will be deleted as well when the given time interval passes.
     *
     * Set to `0` to disable this feature.
     *
     * See {@see ConfigHelper::durationInSeconds()} for a list of supported value types.
     *
     * ::: tip
     * Users will only be purged when [garbage collection](https://craftcms.com/docs/5.x/system/gc.html) is run.
     * :::
     *
     * ::: code
     * ```php Static Config
     * // 2 weeks
     * ->purgePendingUsersDuration(1209600)
     * ```
     * ```shell Environment Override
     * # 2 weeks
     * CRAFT_PURGE_PENDING_USERS_DURATION=1209600
     * ```
     * :::
     *
     * @group Garbage Collection
     */
    public mixed $purgePendingUsersDuration = 0;

    /**
     * @var mixed The amount of time to wait before Craft purges unpublished drafts that were never updated with content.
     *
     * Set to `0` to disable this feature.
     *
     * See {@see ConfigHelper::durationInSeconds()} for a list of supported value types.
     *
     * ::: code
     * ```php Static Config
     * ->purgeUnsavedDraftsDuration(0)
     * ```
     * ```shell Environment Override
     * CRAFT_PURGE_UNSAVED_DRAFTS_DURATION=0
     * ```
     * :::
     *
     * @group Garbage Collection
     *
     * @defaultAlt 30 days
     */
    public mixed $purgeUnsavedDraftsDuration = 2592000;

    /**
     * @var mixed The amount of time to wait before Craft purges stale user sessions from the sessions table in the database.
     *
     * Set to `0` to disable this feature.
     *
     * See {@see ConfigHelper::durationInSeconds()} for a list of supported value types.
     *
     * ::: code
     * ```php Static Config
     * // 1 week
     * ->purgeStaleUserSessionDuration(604800)
     * ```
     * ```shell Environment Override
     * # 1 week
     * CRAFT_PURGE_STALE_USER_SESSION_DURATION=604800
     * ```
     * :::
     *
     * @group Garbage Collection
     *
     * @defaultAlt 90 days
     *
     * @since 3.3.0
     */
    public mixed $purgeStaleUserSessionDuration = 7776000;

    /**
     * @var bool Whether SVG thumbnails should be rasterized.
     *
     * This will only work if ImageMagick is installed, and <config5:imageDriver> is set to either `auto` or `imagick`.
     *
     * ::: code
     * ```php Static Config
     * ->rasterizeSvgThumbs(true)
     * ```
     * ```shell Environment Override
     * CRAFT_RASTERIZE_SVG_THUMBS=true
     * ```
     * :::
     *
     * @group Image Handling
     */
    public bool $rasterizeSvgThumbs = false;

    /**
     * @var mixed The amount of time Craft will remember a username and pre-populate it on the control panel’s Login page.
     *
     * Set to `0` to disable this feature altogether.
     *
     * See {@see ConfigHelper::durationInSeconds()} for a list of supported value types.
     *
     * ::: code
     * ```php Static Config
     * ->rememberUsernameDuration(0)
     * ```
     * ```shell Environment Override
     * CRAFT_REMEMBER_USERNAME_DURATION=0
     * ```
     * :::
     *
     * @group Session
     *
     * @defaultAlt 1 year
     */
    public mixed $rememberUsernameDuration = 31536000;

    /**
     * @var mixed The amount of time a user stays logged if “Remember Me” is checked on the login page.
     *
     * Set to `0` to disable the “Remember Me” feature altogether.
     *
     * See {@see ConfigHelper::durationInSeconds()} for a list of supported value types.
     *
     * ::: code
     * ```php Static Config
     * ->rememberedUserSessionDuration(0)
     * ```
     * ```shell Environment Override
     * CRAFT_REMEMBERED_USER_SESSION_DURATION=0
     * ```
     * :::
     *
     * @group Session
     *
     * @defaultAlt 14 days
     */
    public mixed $rememberedUserSessionDuration = 1209600;

    /**
     * @var bool Whether Craft should require a matching user agent string when restoring a user session from a cookie.
     *
     * ::: code
     * ```php Static Config
     * ->requireMatchingUserAgentForSession(false)
     * ```
     * ```shell Environment Override
     * CRAFT_REQUIRE_MATCHING_USER_AGENT_FOR_SESSION=false
     * ```
     * :::
     *
     * @group Session
     */
    public bool $requireMatchingUserAgentForSession = true;

    /**
     * @var bool Whether Craft should require the existence of a user agent string and IP address when creating a new user session.
     *
     * ::: code
     * ```php Static Config
     * ->requireUserAgentAndIpForSession(false)
     * ```
     * ```shell Environment Override
     * CRAFT_REQUIRE_USER_AGENT_AND_IP_FOR_SESSION=false
     * ```
     * :::
     *
     * @group Session
     */
    public bool $requireUserAgentAndIpForSession = true;

    /**
     * @var string The path to the root directory that should store published control panel resources.
     *
     * ::: code
     * ```php Static Config
     * ->resourceBasePath('@webroot/craft-resources')
     * ```
     * ```shell Environment Override
     * CRAFT_RESOURCE_BASE_PATH=@webroot/craft-resources
     * ```
     * :::
     *
     * @group Environment
     */
    public string $resourceBasePath = '@webroot/cpresources';

    /**
     * @var string The URL to the root directory where control panel resources are published.
     *
     * ::: code
     * ```php Static Config
     * ->resourceBaseUrl('@web/craft-resources')
     * ```
     * ```shell Environment Override
     * CRAFT_RESOURCE_BASE_URL=@web/craft-resources
     * ```
     * :::
     *
     * @group Environment
     */
    public string $resourceBaseUrl = '@web/cpresources';

    /**
     * @var string|null|false|Closure The shell command Craft should execute to restore a database backup.
     *
     * By default Craft will run `mysql` or `psql`, provided those libraries are in the `$PATH` variable for the user the web server is running as.
     *
     * There are several tokens you can use that Craft will swap out at runtime:
     *
     * - `{path}` - the backup file path
     * - `{port}` - the current database port
     * - `{server}` - the current database hostname
     * - `{user}` - the user to connect to the database
     * - `{database}` - the current database name
     * - `{schema}` - the current database schema (if any)
     *
     * This can also be set to `false` to disable database restores completely.
     *
     * ::: code
     * ```php Static Config
     * ->restoreCommand(false)
     * ```
     * ```shell Environment Override
     * CRAFT_RESTORE_COMMAND=false
     * ```
     * :::
     *
     * @group Environment
     */
    public string|null|false|Closure $restoreCommand = null;

    /**
     * @var bool Whether asset URLs should be revved so browsers don’t load cached versions when they’re modified.
     *
     * ::: code
     * ```php Static Config
     * ->revAssetUrls(true)
     * ```
     * ```shell Environment Override
     * CRAFT_REV_ASSET_URLS=true
     * ```
     * :::
     *
     * @group Assets
     */
    public bool $revAssetUrls = false;

    /**
     * @var bool Whether Craft should rotate images according to their EXIF data on upload.
     *
     * ::: code
     * ```php Static Config
     * ->rotateImagesOnUploadByExifData(false)
     * ```
     * ```shell Environment Override
     * CRAFT_ROTATE_IMAGES_ON_UPLOAD_BY_EXIF_DATA=false
     * ```
     * :::
     *
     * @group Image Handling
     */
    public bool $rotateImagesOnUploadByExifData = true;

    /**
     * @var bool Whether Craft should run pending queue jobs automatically when someone visits the control panel.
     *
     * If disabled, an alternate queue worker *must* be set up separately, either as an
     * [always-running daemon](https://github.com/yiisoft/yii2-queue/blob/master/docs/guide/worker.md), or a cron job that runs the
     * `queue/run` command every minute:
     *
     * ```cron
     * * * * * * /path/to/project/craft queue/run
     * ```
     *
     * ::: tip
     * This setting should be disabled for servers running Win32, or with Apache’s mod_deflate/mod_gzip installed,
     * where PHP’s [flush()](https://php.net/manual/en/function.flush.php) method won’t work.
     * :::
     *
     * ::: code
     * ```php Static Config
     * ->runQueueAutomatically(false)
     * ```
     * ```shell Environment Override
     * CRAFT_RUN_QUEUE_AUTOMATICALLY=false
     * ```
     * :::
     *
     * @group System
     */
    public bool $runQueueAutomatically = true;

    /**
     * @var bool Whether the system should run in Safe Mode.
     *
     * Safe Mode disables all plugins and application config that can alter Craft's expected default behavior.
     *
     * ::: code
     * ```php Static Config
     * ->safeMode(true)
     * ```
     * ```shell Environment Override
     * CRAFT_SAFE_MODE=true
     * ```
     * :::
     *
     * @group System
     */
    public bool $safeMode = false;

    /**
     * @var string|null The [SameSite](https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/Set-Cookie/SameSite) value that should be set on Craft cookies, if any.
     *
     * @phpstan-var 'None'|'Lax'|'Strict'|null
     *
     * This can be set to `'None'`, `'Lax'`, `'Strict'`, or `null`.
     *
     * ::: code
     * ```php Static Config
     * ->sameSiteCookieValue('Strict')
     * ```
     * ```shell Environment Override
     * CRAFT_SAME_SITE_COOKIE_VALUE=Strict
     * ```
     * :::
     *
     * @group System
     *
     * @since 3.1.33
     */
    public ?string $sameSiteCookieValue = null;

    /**
     * @var bool Whether images uploaded via the control panel should be sanitized.
     *
     * ::: code
     * ```php Static Config
     * ->sanitizeCpImageUploads(false)
     * ```
     * ```shell Environment Override
     * CRAFT_SANITIZE_CP_IMAGE_UPLOADS=false
     * ```
     * :::
     *
     * @group Security
     */
    public bool $sanitizeCpImageUploads = true;

    /**
     * @var bool Whether Craft should sanitize uploaded SVG files and strip out potential malicious-looking content.
     *
     * This should definitely be enabled if you are accepting SVG uploads from untrusted sources.
     *
     * ::: code
     * ```php Static Config
     * ->sanitizeSvgUploads(false)
     * ```
     * ```shell Environment Override
     * CRAFT_SANITIZE_SVG_UPLOADS=false
     * ```
     * :::
     *
     * @group Security
     */
    public bool $sanitizeSvgUploads = true;

    /**
     * @var array|null Lists of headers that are, by default, subject to the trusted host configuration.
     *
     * See [[\yii\web\Request::secureHeaders]] for more details.
     *
     * If not set, the default [[\yii\web\Request::secureHeaders]] value will be used.
     *
     * ::: code
     * ```php Static Config
     * ->secureHeaders([
     *     'X-Forwarded-For',
     *     'X-Forwarded-Host',
     *     'X-Forwarded-Proto',
     *     'X-Rewrite-Url',
     *     'X-Original-Host',
     *     'CF-Connecting-IP',
     * ])
     * ```
     * ```shell Environment Override
     * CRAFT_SECURE_HEADERS=X-Forwarded-For,X-Forwarded-Host,X-Forwarded-Proto,X-Rewrite-Url,X-Original-Host,CF-Connecting-IP
     * ```
     * :::
     *
     * @group Security
     */
    public ?array $secureHeaders = null;

    /**
     * @var array|null List of headers to check for determining whether the connection is made via HTTPS.
     *
     * See [[\yii\web\Request::secureProtocolHeaders]] for more details.
     *
     * If not set, the default [[\yii\web\Request::secureProtocolHeaders]] value will be used.
     *
     * ```php Static Config
     * ->secureProtocolHeaders([
     *     'X-Forwarded-Proto' => [
     *         'https',
     *     ],
     *     'Front-End-Https' => [
     *         'on',
     *     ],
     *     'CF-Visitor' => [
     *         '{\"scheme\":\"https\"}',
     *     ],
     * ])
     * ```
     *
     * @group Security
     */
    public ?array $secureProtocolHeaders = null;

    /**
     * @var string A private, random, cryptographically-secure key that is used for hashing and encrypting data in [[\craft\services\Security]].
     *
     * ::: warning
     * **Do not** share this key publicly. If exposed, it could lead to a compromised system.
     * :::
     *
     * In the event that the key is compromised, a new secure key can be generated with the command:
     *
     * ```sh
     * php craft setup/security-key
     * ```
     *
     * Note that if the key changes, any data that is encrypted with it (e.g. user session cookies) will be inaccessible.
     *
     * ```php Static Config
     * ->securityKey('2cf24dba5...')
     * ```
     *
     * @see https://craftcms.com/knowledge-base/securing-craft
     *
     * @group Security
     */
    public string $securityKey = '';

    /**
     * @var bool Whether a `Content-Length` header should be sent with responses.
     *
     * ::: code
     * ```php Static Config
     * ->sendContentLengthHeader(true)
     * ```
     * ```shell Environment Override
     * CRAFT_SEND_CONTENT_LENGTH_HEADER=true
     * ```
     * :::
     *
     * @group System
     *
     * @since 3.7.3
     */
    public bool $sendContentLengthHeader = false;

    /**
     * @var bool Whether an `X-Powered-By: Craft CMS` header should be sent, helping services like [BuiltWith](https://builtwith.com/) and
     *           [Wappalyzer](https://www.wappalyzer.com/) identify that the site is running on Craft.
     *
     * ::: code
     * ```php Static Config
     * ->sendPoweredByHeader(false)
     * ```
     * ```shell Environment Override
     * CRAFT_SEND_POWERED_BY_HEADER=false
     * ```
     * :::
     *
     * @group System
     */
    public bool $sendPoweredByHeader = true;

    /**
     * @var mixed The URI or URL that Craft should use for Set Password forms on the front end.
     *
     * See {@see ConfigHelper::localizedValue()} for a list of supported value types.
     *
     * ::: tip
     * You might also want to set <config5:invalidUserTokenPath> in case a user clicks on an expired password reset link.
     * :::
     *
     * ::: code
     * ```php Static Config
     * ->setPasswordPath('set-password')
     * ```
     * ```shell Environment Override
     * CRAFT_SET_PASSWORD_PATH=set-password
     * ```
     * :::
     *
     * @see getSetPasswordPath()
     *
     * @group Routing
     */
    public mixed $setPasswordPath = 'setpassword';

    /**
     * @var mixed The URI to the page where users can request to change their password.
     *
     * See {@see ConfigHelper::localizedValue()} for a list of supported value types.
     *
     * If this is set, Craft will redirect [.well-known/change-password requests](https://w3c.github.io/webappsec-change-password-url/) to this URI.
     *
     * ::: tip
     * You’ll also need to set [setPasswordPath](config5:setPasswordPath), which determines the URI and template path for the Set Password form
     * where the user resets their password after following the link in the Password Reset email.
     * :::
     *
     * ::: code
     * ```php Static Config
     * ->setPasswordRequestPath('request-password')
     * ```
     * ```shell Environment Override
     * CRAFT_SET_PASSWORD_REQUEST_PATH=request-password
     * ```
     * :::
     *
     * @see getSetPasswordRequestPath()
     *
     * @group Routing
     */
    public mixed $setPasswordRequestPath = null;

    /**
     * @var mixed The URI Craft should redirect users to after setting their password from the front end.
     *
     * See {@see ConfigHelper::localizedValue()} for a list of supported value types.
     *
     * ::: code
     * ```php Static Config
     * ->setPasswordSuccessPath('password-set')
     * ```
     * ```shell Environment Override
     * CRAFT_SET_PASSWORD_SUCCESS_PATH=password-set
     * ```
     * :::
     *
     * @see getSetPasswordSuccessPath()
     *
     * @group Routing
     */
    public mixed $setPasswordSuccessPath = '';

    /**
     * @var string The query string parameter name that site tokens should be set to.
     *
     * ::: code
     * ```php Static Config
     * ->siteToken('t')
     * ```
     * ```shell Environment Override
     * CRAFT_SITE_TOKEN=t
     * ```
     * :::
     *
     * @group Routing
     */
    public string $siteToken = 'siteToken';

    /**
     * @var string The character(s) that should be used to separate words in slugs.
     *
     * ::: code
     * ```php Static Config
     * ->slugWordSeparator('.')
     * ```
     * ```shell Environment Override
     * CRAFT_SLUG_WORD_SEPARATOR=.
     * ```
     * :::
     *
     * @group System
     */
    public string $slugWordSeparator = '-';

    /**
     * @var bool Whether “First Name” and “Last Name” fields should be shown in place of “Full Name” fields.
     *
     * ::: code
     * ```php Static Config
     * ->showFirstAndLastNameFields()
     * ```
     * ```shell Environment Override
     * CRAFT_SHOW_FIRST_AND_LAST_NAME_FIELDS=true
     * ```
     * :::
     *
     * @group Users
     */
    public bool $showFirstAndLastNameFields = false;

    /**
     * @var mixed The amount of time before a soft-deleted item will be up for hard-deletion by garbage collection.
     *
     * Set to `0` if you don’t ever want to delete soft-deleted items.
     *
     * See {@see ConfigHelper::durationInSeconds()} for a list of supported value types.
     *
     * ::: code
     * ```php Static Config
     * ->softDeleteDuration(0)
     * ```
     * ```shell Environment Override
     * CRAFT_SOFT_DELETE_DURATION=0
     * ```
     * :::
     *
     * @group Garbage Collection
     *
     * @defaultAlt 30 days
     */
    public mixed $softDeleteDuration = 2592000;

    /**
     * @var bool Whether entries’ statuses should be stored statically, and only get updated on entry save, or when the
     *           `update-statuses` command is executed.
     *
     * ::: code
     * ```php Static Config
     * ->staticStatuses()
     * ```
     * ```shell Environment Override
     * CRAFT_STATIC_STATUSES=true
     * ```
     * :::
     *
     * @group System
     */
    public bool $staticStatuses = false;

    /**
     * @var bool Whether user IP addresses should be stored/logged by the system.
     *
     * ::: code
     * ```php Static Config
     * ->storeUserIps(true)
     * ```
     * ```shell Environment Override
     * CRAFT_STORE_USER_IPS=true
     * ```
     * :::
     *
     * @group Security
     */
    public bool $storeUserIps = false;

    /**
     * @var string|null The URL to a CSS file that should be included when rendering system templates on the front end,
     *                  such as the Login and Set Password templates.
     *
     * ::: code
     * ```php Static Config
     * ->systemTemplateCss('/css/cp-theme.css');
     * ```
     * ```shell Environment Override
     * CRAFT_SYSTEM_TEMPLATE_CSS=/css/cp-theme.css
     * ```
     * :::
     *
     * @group System
     */
    public ?string $systemTemplateCss = null;

    /**
     * @var string|null The handle of the filesystem that should be used for storing temporary asset uploads. A local temp folder will
     *                  be used by default.
     *
     * ::: code
     * ```php Static Config
     * ->tempAssetUploadFs('$TEMP_ASSET_UPLOADS_FS')
     * ```
     * ```shell Environment Override
     * CRAFT_TEMP_ASSET_UPLOAD_FS=tempAssetUploads
     * ```
     * :::
     *
     * @group Assets
     */
    public ?string $tempAssetUploadFs = null;

    /**
     * @var string|array|null|false Configures Craft to send all system emails to either a single email address or an array of email addresses
     *                              for testing purposes.
     *
     * By default, the recipient name(s) will be “Test Recipient”, but you can customize that by setting the value with the format
     * `['me@domain.tld' => 'Name']`.
     *
     * ::: code
     * ```php Static Config
     * ->testToEmailAddress('me@domain.tld')
     * ```
     * ```shell Environment Override
     * CRAFT_TEST_TO_EMAIL_ADDRESS=me@domain.tld
     * ```
     * :::
     *
     * @group System
     */
    public string|array|null|false $testToEmailAddress = null;

    /**
     * @var string|null The timezone of the site. If set, it will take precedence over the Timezone setting in Settings → General.
     *
     * This can be set to one of PHP’s [supported timezones](https://php.net/manual/en/timezones.php).
     *
     * ::: code
     * ```php Static Config
     * ->timezone('Europe/London')
     * ```
     * ```shell Environment Override
     * CRAFT_TIMEZONE=Europe/London
     * ```
     * :::
     *
     * @group System
     *
     * @deprecated in 6.0.0. Laravel's `app.timezone` config variable should be used instead.
     */
    public ?string $timezone = null;

    /**
     * @var bool Whether GIF files should be cleansed/transformed.
     *
     * ::: code
     * ```php Static Config
     * ->transformGifs(false)
     * ```
     * ```shell Environment Override
     * CRAFT_TRANSFORM_GIFS=false
     * ```
     * :::
     *
     * @group Image Handling
     */
    public bool $transformGifs = true;

    /**
     * @var bool Whether SVG files should be transformed.
     *
     * ::: code
     * ```php Static Config
     * ->transformSvgs(false)
     * ```
     * ```shell Environment Override
     * CRAFT_TRANSFORM_SVGS=false
     * ```
     * :::
     *
     * @group Image Handling
     */
    public bool $transformSvgs = true;

    /**
     * @var bool Whether translated messages should be wrapped in special characters to help find any strings that are not being run through
     *           `t()` or the `|translate` filter.
     *
     * ::: code
     * ```php Static Config
     * ->translationDebugOutput(true)
     * ```
     * ```shell Environment Override
     * CRAFT_TRANSLATION_DEBUG_OUTPUT=true
     * ```
     * :::
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
     * @var array The configuration for trusted security-related headers.
     *
     * See [[\yii\web\Request::trustedHosts]] for more details.
     *
     * By default, all hosts are trusted.
     *
     * ::: code
     * ```php Static Config
     * ->trustedHosts(['trusted-one.foo', 'trusted-two.foo'])
     * ```
     * ```shell Environment Override
     * CRAFT_TRUSTED_HOSTS=trusted-one.foo,trusted-two.foo
     * ```
     * :::
     *
     * @group Security
     */
    public ?array $trustedHosts = ['any'];

    /**
     * @var string The query string parameter name that Craft tokens should be set to.
     *
     * ::: code
     * ```php Static Config
     * ->tokenParam('t')
     * ```
     * ```shell Environment Override
     * CRAFT_TOKEN_PARAM=t
     * ```
     * :::
     *
     * @group Routing
     */
    public string $tokenParam = 'token';

    /**
     * @var bool Whether image transforms should allow upscaling by default, for images that are smaller than the transform dimensions.
     *
     * ::: code
     * ```php Static Config
     * ->upscaleImages(false)
     * ```
     * ```shell Environment Override
     * CRAFT_UPSCALE_IMAGES=false
     * ```
     * :::
     *
     * @group Image Handling
     */
    public bool $upscaleImages = true;

    /**
     * @var bool Whether Craft should set users’ usernames to their email addresses, rather than let them set their username separately.
     *
     * If you enable this setting after user accounts already exist, run this terminal command to update existing usernames:
     *
     * ```bash
     * php craft utils/update-usernames
     * ```
     *
     * ::: code
     * ```php Static Config
     * ->useEmailAsUsername(true)
     * ```
     * ```shell Environment Override
     * CRAFT_USE_EMAIL_AS_USERNAME=true
     * ```
     * :::
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
     * ::: code
     * ```php Static Config
     * ->useIdnaNontransitionalToUnicode(true)
     * ```
     * ```shell Environment Override
     * CRAFT_USE_IDNA_NONTRANSITIONAL_TO_UNICODE=true
     * ```
     * :::
     *
     * @group System
     *
     * @since 5.9.0
     */
    public bool $useIdnaNontransitionalToUnicode = false;

    /**
     * @var bool Whether [iFrame Resizer options](http://davidjbradshaw.github.io/iframe-resizer/#options) should be used for Live Preview.
     *
     * Using iFrame Resizer makes it possible for Craft to retain the preview’s scroll position between page loads, for cross-origin web pages.
     *
     * It works by setting the height of the iframe to match the height of the inner web page, and the iframe’s container will be scrolled rather
     * than the iframe document itself. This can lead to some unexpected CSS issues, however, because the previewed viewport height will be taller
     * than the visible portion of the iframe.
     *
     * If you have a [decoupled front end](https://craftcms.com/docs/5.x/reference/element-types/entries.html#previewing-decoupled-front-ends), you will need to include
     * [iframeResizer.contentWindow.min.js](https://raw.github.com/davidjbradshaw/iframe-resizer/master/js/iframeResizer.contentWindow.min.js) on your
     * page as well for this to work. You can conditionally include it for only Live Preview requests by checking if the requested URL contains a
     * `x-craft-live-preview` query string parameter.
     *
     * ::: tip
     * You can customize the behavior of iFrame Resizer via the <config5:previewIframeResizerOptions> config setting.
     * :::
     *
     * ::: code
     * ```php Static Config
     * ->useIframeResizer(true)
     * ```
     * ```shell Environment Override
     * CRAFT_USE_IFRAME_RESIZER=true
     * ```
     * :::
     *
     * @group System
     */
    public bool $useIframeResizer = false;

    /**
     * @var bool Whether Craft should specify the path using `PATH_INFO` or as a query string parameter when generating URLs.
     *
     * This setting only takes effect if <config5:omitScriptNameInUrls> is set to `false`.
     *
     * ::: code
     * ```php Static Config
     * ->usePathInfo(true)
     * ```
     * ```shell Environment Override
     * CRAFT_USE_PATH_INFO=true
     * ```
     * :::
     *
     * @group Routing
     */
    public bool $usePathInfo = false;

    /**
     * @var bool|string Whether Craft will set the “secure” flag when saving cookies when using `Craft::cookieConfig()` to create a cookie.
     *
     * Valid values are `true`, `false`, and `'auto'`. Defaults to `'auto'`, which will set the secure flag if the page you’re currently accessing
     * is over `https://`. `true` will always set the flag, regardless of protocol and `false` will never automatically set the flag.
     *
     * ::: code
     * ```php Static Config
     * ->useSecureCookies(true)
     * ```
     * ```shell Environment Override
     * CRAFT_USE_SECURE_COOKIES=true
     * ```
     * :::
     *
     * @group Security
     */
    public string|bool $useSecureCookies = 'auto';

    /**
     * @var bool|string Determines what protocol/schema Craft will use when generating tokenized URLs. If set to `'auto'`, Craft will check the
     *                  current site’s base URL and the protocol of the current request and if either of them are HTTPS will use `https` in the tokenized URL. If not,
     *                  will use `http`.
     *
     * If set to `false`, Craft will always use `http`. If set to `true`, then, Craft will always use `https`.
     *
     * ::: code
     * ```php Static Config
     * ->useSslOnTokenizedUrls(true)
     * ```
     * ```shell Environment Override
     * CRAFT_USE_SSL_ON_TOKENIZED_URLS=true
     * ```
     * :::
     *
     * @group Routing
     */
    public string|bool $useSslOnTokenizedUrls = 'auto';

    /**
     * @var mixed The amount of time before a user will get logged out due to inactivity.
     *
     * Set to `0` if you want users to stay logged in as long as their browser is open rather than a predetermined amount of time.
     *
     * See {@see ConfigHelper::durationInSeconds()} for a list of supported value types.
     *
     * ::: code
     * ```php Static Config
     * // 3 hours
     * ->userSessionDuration(10800)
     * ```
     * ```shell Environment Override
     * # 3 hours
     * CRAFT_USER_SESSION_DURATION=10800
     * ```
     * :::
     *
     * @group Session
     *
     * @defaultAlt 1 hour
     */
    public mixed $userSessionDuration = 3600;

    /**
     * @var bool|null Whether to grab an exclusive lock on a file when writing to it by using the `LOCK_EX` flag.
     *
     * Some file systems, such as NFS, do not support exclusive file locking.
     *
     * If `null`, Craft will try to detect if the underlying file system supports exclusive file locking and cache the results.
     *
     * ::: code
     * ```php Static Config
     * ->useFileLocks(false)
     * ```
     * ```shell Environment Override
     * CRAFT_USE_FILE_LOCKS=false
     * ```
     * :::
     *
     * @see https://php.net/manual/en/function.file-put-contents.php
     *
     * @group System
     */
    public ?bool $useFileLocks = null;

    /**
     * @var mixed The amount of time a user verification code can be used before expiring.
     *
     * See {@see ConfigHelper::durationInSeconds()} for a list of supported value types.
     *
     * ::: code
     * ```php Static Config
     * // 1 hour
     * ->verificationCodeDuration(3600)
     * ```
     * ```shell Environment Override
     * # 1 hour
     * CRAFT_VERIFICATION_CODE_DURATION=3600
     * ```
     * :::
     *
     * @group Security
     *
     * @defaultAlt 1 day
     */
    public mixed $verificationCodeDuration = 86400;

    /**
     * @var mixed The URI or URL that Craft should use for email verification links on the front end.
     *
     * See {@see ConfigHelper::localizedValue()} for a list of supported value types.
     *
     * ::: code
     * ```php Static Config
     * ->verifyEmailPath('verify-email')
     * ```
     * ```shell Environment Override
     * CRAFT_VERIFY_EMAIL_PATH=verify-email
     * ```
     * :::
     *
     * @see getVerifyEmailPath()
     *
     * @group Routing
     */
    public mixed $verifyEmailPath = 'verifyemail';

    /**
     * @var mixed The URI that users without access to the control panel should be redirected to after verifying a new email address.
     *
     * See {@see ConfigHelper::localizedValue()} for a list of supported value types.
     *
     * ::: code
     * ```php Static Config
     * ->verifyEmailSuccessPath('verified-email')
     * ```
     * ```shell Environment Override
     * CRAFT_VERIFY_EMAIL_SUCCESS_PATH=verified-email
     * ```
     * :::
     *
     * @see getVerifyEmailSuccessPath()
     *
     * @group Routing
     */
    public mixed $verifyEmailSuccessPath = '';

    protected ?DateInterval $_rememberedUserSessionDuration = null;

    public function __construct()
    {
        // (Re-)normalize everything.
        $this
            // file extensions
            ->allowedFileExtensions($this->allowedFileExtensions)
            ->extraAllowedFileExtensions($this->extraAllowedFileExtensions)
            // durations
            ->cacheDuration($this->cacheDuration)
            ->cooldownDuration($this->cooldownDuration)
            ->defaultTokenDuration($this->defaultTokenDuration)
            ->elevatedSessionDuration($this->elevatedSessionDuration)
            ->invalidLoginWindowDuration($this->invalidLoginWindowDuration)
            ->previewTokenDuration($this->previewTokenDuration ?? $this->defaultTokenDuration)
            ->purgePendingUsersDuration($this->purgePendingUsersDuration)
            ->purgeUnsavedDraftsDuration($this->purgeUnsavedDraftsDuration)
            ->rememberUsernameDuration($this->rememberUsernameDuration)
            ->rememberedUserSessionDuration($this->rememberedUserSessionDuration)
            ->softDeleteDuration($this->softDeleteDuration)
            ->userSessionDuration($this->userSessionDuration)
            ->verificationCodeDuration($this->verificationCodeDuration)
            // locales
            ->defaultCpLanguage($this->defaultCpLanguage)
            ->extraAppLocales($this->extraAppLocales)
            // misc
            ->maxUploadFileSize($this->maxUploadFileSize)
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
     * @see $accessibilityDefaults
     */
    public function accessibilityDefaults(array $value): self
    {
        $this->accessibilityDefaults = $value;

        return $this;
    }

    /**
     * The URI segment Craft should look for when determining if the current request should be routed to a controller action.
     *
     * ```php
     * ->actionTrigger('do-it')
     * ```
     *
     * @group Routing
     *
     * @see $actionTrigger
     * @since 4.2.0
     */
    public function actionTrigger(string $value): self
    {
        $this->actionTrigger = $value;

        return $this;
    }

    /**
     * The URI that users without access to the control panel should be redirected to after activating their account.
     *
     * See {@see ConfigHelper::localizedValue()} for a list of supported value types.
     *
     * ```php
     * ->activateAccountSuccessPath('welcome')
     * ```
     *
     * @group Routing
     *
     * @see $activateAccountSuccessPath
     * @see getActivateAccountSuccessPath()
     */
    public function activateAccountSuccessPath(mixed $value): self
    {
        $this->activateAccountSuccessPath = $value;

        return $this;
    }

    /**
     * Whether auto-generated URLs should have trailing slashes.
     *
     * ```php
     * ->addTrailingSlashesToUrls(true)
     * ```
     *
     * @group Routing
     *
     * @see $addTrailingSlashesToUrls
     */
    public function addTrailingSlashesToUrls(bool $value = true): self
    {
        $this->addTrailingSlashesToUrls = $value;

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
     * The file extensions Craft should allow when a user is uploading files.
     *
     * ```php
     * // Nothing bug GIFs!
     * ->allowedFileExtensions([
     *     'gif',
     * ])
     * ```
     *
     * @group Assets
     *
     * @param  string[]  $value
     *
     * @see $allowedFileExtensions
     */
    public function allowedFileExtensions(array $value): self
    {
        $this->allowedFileExtensions = array_map(strtolower(...), $value);

        return $this;
    }

    /**
     * The Ajax origins that should be allowed to access the GraphQL API, if enabled.
     *
     * If this is set to an array, then `graphql/api` requests will only include the current request’s [[\yii\web\Request::getOrigin()|origin]]
     * in the `Access-Control-Allow-Origin` response header if it’s listed here.
     *
     * If this is set to `false`, then the `Access-Control-Allow-Origin` response header will never be sent.
     *
     * ```php
     * ->allowedGraphqlOrigins(false)
     * ```
     *
     * @group GraphQL
     *
     * @see $allowedGraphqlOrigins
     * @since 4.2.0
     * @see https://www.yiiframework.com/doc/api/2.0/yii-filters-cors
     */
    #[Deprecated(message: 'in 4.11.0. [[\craft\filters\Cors]] should be used instead.')]
    public function allowedGraphqlOrigins(array|null|false $value): self
    {
        $this->allowedGraphqlOrigins = $value;

        return $this;
    }

    /**
     * Whether users should be allowed to create similarly-named tags.
     *
     * ```php
     * ->allowSimilarTags(true)
     * ```
     *
     * @group System
     *
     * @see $allowSimilarTags
     */
    public function allowSimilarTags(bool $value = true): self
    {
        $this->allowSimilarTags = $value;

        return $this;
    }

    /**
     * Whether uppercase letters should be allowed in slugs.
     *
     * ```php
     * ->allowUppercaseInSlug(true)
     * ```
     *
     * @group Routing
     *
     * @see $allowUppercaseInSlug
     */
    public function allowUppercaseInSlug(bool $value = true): self
    {
        $this->allowUppercaseInSlug = $value;

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
     * The base URL Craft should use when generating control panel URLs.
     *
     * It will be determined automatically if left blank.
     *
     * ::: tip
     * The base control panel URL should **not** include the [control panel trigger word](config5:cpTrigger) (e.g. `/admin`).
     * :::
     *
     * ```php
     * ->baseCpUrl('https://cms.my-project.tld/')
     * ```
     *
     * @group Routing
     *
     * @see $baseCpUrl
     * @since 4.2.0
     */
    public function baseCpUrl(?string $value): self
    {
        $this->baseCpUrl = $value;

        return $this;
    }

    /**
     * Whether Craft should create a database backup before applying a new system update.
     *
     * ```php
     * ->backupOnUpdate(false)
     * ```
     *
     * @group System
     *
     * @see $backupOnUpdate
     */
    public function backupOnUpdate(bool $value = true): self
    {
        $this->backupOnUpdate = $value;

        return $this;
    }

    /**
     * The shell command that Craft should execute to create a database backup.
     *
     * When set to `null` (default), Craft will run `mysqldump` or `pg_dump`, provided that those libraries are in the `$PATH` variable
     * for the system user running the web server.
     *
     * You may provide your own command, which can include several tokens Craft will substitute at runtime:
     *
     * - `{file}` - the target backup file path
     * - `{port}` - the current database port
     * - `{server}` - the current database hostname
     * - `{user}` - user that was used to connect to the database
     * - `{password}` - password for the specified `{user}`
     * - `{database}` - the current database name
     * - `{schema}` - the current database schema (if any)
     *
     * This can also be set to `false` to disable database backups completely.
     *
     * ```php
     * ->backupCommand(false)
     * ```
     *
     * @group Environment
     *
     * @see $backupCommand
     */
    public function backupCommand(string|null|false|Closure $value): self
    {
        $this->backupCommand = $value;

        return $this;
    }

    /**
     * The output format that database backups should use (PostgreSQL only).
     *
     * This setting has no effect with MySQL databases.
     *
     * Valid options are `custom`, `directory`, `tar`, or `plain`.
     * When set to `null` (default), `pg_restore` will default to `plain`
     *
     * @see https://www.postgresql.org/docs/current/app-pgdump.html
     *
     * @group Environment
     *
     * @see $backupCommandFormat
     */
    public function backupCommandFormat(string $value): self
    {
        $this->backupCommandFormat = $value;

        return $this;
    }

    /**
     * The higher the cost value, the longer it takes to generate a password hash and to verify against it.
     *
     * Therefore, higher cost slows down a brute-force attack.
     *
     * For best protection against brute force attacks, set it to the highest value that is tolerable on production servers.
     *
     * The time taken to compute the hash doubles for every increment by one for this value.
     *
     * For example, if the hash takes 1 second to compute when the value is 14 then the compute time varies as
     * 2^(value - 14) seconds.
     *
     * ```php
     * ->blowfishHashCost(15)
     * ```
     *
     * @group Security
     *
     * @see $blowfishHashCost
     * @since 4.2.0
     */
    #[Deprecated(message: 'in 6.0.0. Set hashing.bcrypt.rounds or BCRYPT_ROUNDS environment variable instead.')]
    public function blowfishHashCost(int $value): self
    {
        app()->booting(function () use ($value) {
            Config::set('hashing.bcrypt.rounds', $value);
            Deprecator::log('generalConfig.blowfishHashCost', 'blowfishHashCost is deprecated. Set hashing.bcrypt.rounds or BCRYPT_ROUNDS instead.');
        });

        $this->blowfishHashCost = $value;

        return $this;
    }

    /**
     * The server path to an image file that should be sent when responding to an image request with a
     * 404 status code.
     *
     * This can be set to an aliased path such as `@webroot/assets/404.svg`.
     *
     * ```php
     * ->brokenImagePath('@webroot/assets/404.svg')
     * ```
     *
     * @group Image Handling
     *
     * @see $brokenImagePath
     */
    public function brokenImagePath(?string $value): self
    {
        $this->brokenImagePath = $value;

        return $this;
    }

    /**
     * A unique ID representing the current build of the codebase.
     *
     * This should be set to something unique to the deployment, e.g. a Git SHA or a deployment timestamp.
     *
     * ```php
     * ->buildId(\craft\helpers\App::env('GIT_SHA'))
     * ```
     *
     * @group Environment
     *
     * @see $buildId
     */
    public function buildId(?string $value): self
    {
        $this->buildId = $value;

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
     * Whether uploaded filenames with non-ASCII characters should be converted to ASCII (i.e. `ñ` → `n`).
     *
     * ::: tip
     * You can run `php craft utils/ascii-filenames` in your terminal to apply ASCII filenames to all existing assets.
     * :::
     *
     * ```php
     * ->convertFilenamesToAscii(false)
     * ```
     *
     * @group Assets
     *
     * @see $convertFilenamesToAscii
     */
    public function convertFilenamesToAscii(bool $value = true): self
    {
        $this->convertFilenamesToAscii = $value;

        return $this;
    }

    /**
     * The amount of time a user must wait before re-attempting to log in after their account is locked due to too many
     * failed login attempts.
     *
     * Set to `0` to keep the account locked indefinitely, requiring an admin to manually unlock the account.
     *
     * See {@see ConfigHelper::durationInSeconds()} for a list of supported value types.
     *
     * ```php
     * ->cooldownDuration(0)
     * ```
     *
     * @group Security
     *
     * @defaultAlt 5 minutes
     *
     * @see $cooldownDuration
     */
    public function cooldownDuration(mixed $value): self
    {
        $this->cooldownDuration = ConfigHelper::durationInSeconds($value);

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
     * @see $cpHeadTags
     */
    public function cpHeadTags(array $value): self
    {
        $this->cpHeadTags = $value;

        return $this;
    }

    /**
     * The URI segment Craft should look for when determining if the current request should route to the control panel rather than
     * the front-end website.
     *
     * This can be set to `null` if you have a dedicated hostname for the control panel (e.g. `cms.my-project.tld`), or you are running Craft in
     * [Headless Mode](config5:headlessMode). If you do that, you will need to ensure that the control panel is being served from its own web root
     * directory on your server, with an `index.php` file that defines the `CRAFT_CP` PHP constant.
     *
     * ```php
     * define('CRAFT_CP', true);
     * ```
     *
     * Alternatively, you can set the <config5:baseCpUrl> config setting, but then you will run the risk of losing access to portions of your
     * control panel due to URI conflicts with actual folders/files in your main web root.
     *
     * (For example, if you have an `assets/` folder, that would conflict with the `/assets` page in the control panel.)
     *
     * ```php
     * ->cpTrigger(null)
     * ```
     *
     * @group Routing
     *
     * @see $cpTrigger
     */
    public function cpTrigger(?string $value): self
    {
        $this->cpTrigger = $value;

        return $this;
    }

    /**
     * The name of CSRF token used for CSRF validation if <config5:enableCsrfProtection> is set to `true`.
     *
     * ```php
     * ->csrfTokenName('MY_CSRF')
     * ```
     *
     * @group Security
     *
     * @see $csrfTokenName
     * @see enableCsrfProtection
     * @since 4.2.0
     */
    #[Deprecated(message: 'in 6.0.0. Calling csrfTokenName() is deprecated. The token is always named XSRF-TOKEN.')]
    public function csrfTokenName(string $value): self
    {
        app()->booting(fn () => Deprecator::log('generalConfig.csrfTokenName', 'Calling csrfTokenName() is deprecated. The token is always named XSRF-TOKEN.'));

        $this->csrfTokenName = $value;

        return $this;
    }

    /**
     * The domain that cookies generated by Craft should be created for. If blank, it will be left up to the browser to determine
     * which domain to use (almost always the current). If you want the cookies to work for all subdomains, for example, you could
     * set this to `'.my-project.tld'`.
     *
     * ```php
     * ->defaultCookieDomain('.my-project.tld')
     * ```
     *
     * @group Environment
     *
     * @see $defaultCookieDomain
     * @since 4.2.0
     */
    public function defaultCookieDomain(string $value): self
    {
        $this->defaultCookieDomain = $value;

        app()->booting(function () use ($value) {
            Deprecator::log('generalConfig.defaultCookieDomain', 'Calling defaultCookieDomain() is deprecated.');
            Config::set('session.domain', $value);
        });

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
            throw new InvalidConfigException('`defaultCountryCode` cannot be empty', 0);
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
     * @throws InvalidConfigException
     *
     * @see $defaultCpLanguage
     */
    public function defaultCpLanguage(?string $value): self
    {
        if (
            $value !== null &&
            class_exists(Craft::class, false) &&
            isset(Craft::$app)
        ) {
            try {
                $value = I18N::normalizeLanguage($value);
                /** @phpstan-ignore catch.neverThrown */
            } catch (\InvalidArgumentException $e) {
                throw new InvalidConfigException($e->getMessage(), 0, $e);
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
     * The default permission to be set for newly-generated files.
     *
     * If set to `null`, the permission will be determined by the current environment.
     *
     * ```php
     * ->defaultFileMode(0744)
     * ```
     *
     * @group System
     *
     * @see $defaultFileMode
     * @since 4.2.0
     */
    public function defaultFileMode(?int $value): self
    {
        $this->defaultFileMode = $value;

        return $this;
    }

    /**
     * The quality level Craft will use when saving JPG and PNG files. Ranges from 1 (worst quality, smallest file) to
     * 100 (best quality, biggest file).
     *
     * ```php
     * ->defaultImageQuality(90)
     * ```
     *
     * @group Image Handling
     *
     * @see $defaultImageQuality
     */
    public function defaultImageQuality(int $value): self
    {
        $this->defaultImageQuality = $value;

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
     * @see $defaultSearchTermOptions
     */
    public function defaultSearchTermOptions(array $value): self
    {
        $this->defaultSearchTermOptions = $value;

        return $this;
    }

    /**
     * The template file extensions Craft will look for when matching a template path to a file on the front end.
     *
     * ```php
     * ->defaultTemplateExtensions(['twig', 'html', 'txt'])
     * ```
     *
     * @group System
     *
     * @see $defaultTemplateExtensions
     */
    public function defaultTemplateExtensions(array $value): self
    {
        $this->defaultTemplateExtensions = $value;

        return $this;
    }

    /**
     * The default amount of time tokens can be used before expiring.
     *
     * See {@see ConfigHelper::durationInSeconds()} for a list of supported value types.
     *
     * ```php
     * // One week
     * ->defaultTokenDuration(604800)
     * ```
     *
     * @group Security
     *
     * @defaultAlt 1 day
     *
     * @see $defaultTokenDuration
     */
    public function defaultTokenDuration(mixed $value): self
    {
        $this->defaultTokenDuration = ConfigHelper::durationInSeconds($value);

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
     * By default, Craft requires a front-end “password” field for public user registrations. Setting this to
     * `true` removes that requirement for the initial registration form. Instead, new users will set their password
     * once they’ve followed the link in their activation email.
     *
     * ```php
     * ->deferPublicRegistrationPassword(true)
     * ```
     *
     * @group Security
     *
     * @see $deferPublicRegistrationPassword
     */
    public function deferPublicRegistrationPassword(bool $value = true): self
    {
        $this->deferPublicRegistrationPassword = $value;

        return $this;
    }

    /**
     * Whether the system should run in [Dev Mode](https://craftcms.com/support/dev-mode).
     *
     * ```php
     * ->devMode(true)
     * ```
     *
     * @group System
     *
     * @see $devMode
     * @since 4.2.0
     */
    #[Deprecated(message: 'in 6.0.0. Set `app.debug` or `APP_DEBUG` environment variable instead.')]
    public function devMode(bool $value = true): self
    {
        app()->booting(function () use ($value) {
            Deprecator::log('generalConfig.devMode', 'devMode is deprecated. Set `app.debug` or `APP_DEBUG` environment variable instead.');
            Config::set('app.debug', $value);
        });

        $this->devMode = $value;

        return $this;
    }

    /**
     * Whether two-step verification features should be disabled.
     *
     * ::: code
     * ```php Static Config
     * ->disable2fa()
     * ```
     * ```shell Environment Override
     * CRAFT_DISABLE_2FA=true
     * ```
     * :::
     *
     * @group Users
     *
     * @see $disable2fa
     */
    public function disable2fa(bool $value = true): self
    {
        $this->disable2fa = $value;

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
     * ->dev([
     *     'disabledPlugins' => '*',
     * ])
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
     *  ::: code
     *  ```php Static Config
     *   ->disabledUtilities([
     *       'updates',
     *       'find-replace',
     *   ])
     *  ```
     *  ```shell Environment Override
     *  CRAFT_DISABLED_UTILITIES=updates,find-replace
     *  ```
     *  :::
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
     * Whether the `transform` directive should be disabled for the GraphQL API.
     *
     * ```php
     * ->disableGraphqlTransformDirective(true)
     * ```
     *
     * @group GraphQL
     *
     * @see $disableGraphqlTransformDirective
     */
    public function disableGraphqlTransformDirective(bool $value = true): self
    {
        $this->disableGraphqlTransformDirective = $value;

        return $this;
    }

    /**
     * Whether CSRF values should be injected via JavaScript for greater cache-ability.
     *
     *  ```php
     *  ->asyncCsrfInputs(true)
     *  ```
     *
     * @see $asyncCsrfInputs
     */
    public function asyncCsrfInputs(bool $value = true): self
    {
        $this->asyncCsrfInputs = $value;

        return $this;
    }

    /**
     * Whether front-end web requests should support basic HTTP authentication.
     *
     * ```php
     * ->enableBasicHttpAuth(true)
     * ```
     *
     * @group Security
     *
     * @see $enableBasicHttpAuth
     * @since 4.2.0
     */
    public function enableBasicHttpAuth(bool $value = true): self
    {
        $this->enableBasicHttpAuth = $value;

        return $this;
    }

    /**
     * Whether to use a cookie to persist the CSRF token if <config5:enableCsrfProtection> is enabled. If false, the CSRF token will be
     * stored in session under the `csrfTokenName` config setting name. Note that while storing CSRF tokens in session increases security,
     * it requires starting a session for every page that a CSRF token is needed, which may degrade site performance.
     *
     * ```php
     * ->enableCsrfCookie(false)
     * ```
     *
     * @group Security
     *
     * @see $enableCsrfCookie
     * @since 4.2.0
     */
    #[Deprecated(message: 'in 6.0.0. A cookie will always be used to persist the CSRF token.')]
    public function enableCsrfCookie(bool $value = true): self
    {
        app()->booting(fn () => Deprecator::log('generalConfig.enableCsrfCookie', 'A cookie will always be used to persist the CSRF token.'));

        $this->enableCsrfCookie = $value;

        return $this;
    }

    /**
     * Whether to enable CSRF protection via hidden form inputs for all forms submitted via Craft.
     *
     * ```php
     * ->enableCsrfProtection(false)
     * ```
     *
     * @group Security
     *
     * @see $enableCsrfProtection
     * @since 4.2.0
     */
    #[Deprecated(message: 'in 6.0.0. [Configure excluded routes instead](https://laravel.com/docs/12.x/csrf#csrf-excluding-uris)')]
    public function enableCsrfProtection(bool $value = true): self
    {
        app()->booting(fn () => Deprecator::log('generalConfig.enableCsrfProtection', 'Configure excluded routes instead.'));

        $this->enableCsrfProtection = $value;

        if ($value === false) {
            VerifyCsrfToken::except(['*']);
        }

        return $this;
    }

    /**
     * Whether GraphQL introspection queries are allowed. Defaults to `true` and is always allowed in the control panel.
     *
     * ```php
     * ->enableGraphqlIntrospection(false)
     * ```
     *
     * @group GraphQL
     *
     * @see $enableGraphqlIntrospection
     */
    public function enableGraphqlIntrospection(bool $value = true): self
    {
        $this->enableGraphqlIntrospection = $value;

        return $this;
    }

    /**
     * Whether the GraphQL API should be enabled.
     *
     * The GraphQL API is only available for Craft Pro.
     *
     * ```php
     * ->enableGql(false)
     * ```
     *
     * @group GraphQL
     *
     * @see $enableGql
     */
    public function enableGql(bool $value = true): self
    {
        $this->enableGql = $value;

        return $this;
    }

    /**
     * The amount of time a user’s elevated session will last, which is required for some sensitive actions (e.g. user group/permission assignment).
     *
     * Set to `0` to disable elevated session support.
     *
     * See {@see ConfigHelper::durationInSeconds()} for a list of supported value types.
     *
     * ```php
     * ->elevatedSessionDuration(0)
     * ```
     *
     * @group Security
     *
     * @defaultAlt 5 minutes
     *
     * @see $elevatedSessionDuration
     */
    #[Deprecated(message: 'use the `auth.password_timeout` config setting instead.', since: '6.0.0')]
    public function elevatedSessionDuration(mixed $value): self
    {
        $this->elevatedSessionDuration = ConfigHelper::durationInSeconds($value);

        app()->booting(function () {
            Deprecator::log('generalConfig.elevatedSessionDuration', 'Use the `auth.password_timeout` config setting instead.');
            Config::set('auth.password_timeout', $this->elevatedSessionDuration === 0 ? -1 : $this->elevatedSessionDuration);
        });

        return $this;
    }

    /**
     * Whether Craft should cache GraphQL queries.
     *
     * If set to `true`, Craft will cache the results for unique GraphQL queries per access token. The cache is automatically invalidated any time
     * an element is saved, the site structure is updated, or a GraphQL schema is saved.
     *
     * This setting will have no effect if a plugin is using the [[\craft\services\Gql::EVENT_BEFORE_EXECUTE_GQL_QUERY]] event to provide its own
     * caching logic and setting the `result` property.
     *
     * ```php
     * ->enableGraphqlCaching(false)
     * ```
     *
     * @group GraphQL
     *
     * @see $enableGraphqlCaching
     */
    public function enableGraphqlCaching(bool $value = true): self
    {
        $this->enableGraphqlCaching = $value;

        return $this;
    }

    /**
     * Whether dates returned by the GraphQL API should be set to the system time zone by default, rather than UTC.
     *
     * ```php
     * ->setGraphqlDatesToSystemTimeZone(true)
     * ```
     *
     * @group GraphQL
     *
     * @see $setGraphqlDatesToSystemTimeZone
     */
    public function setGraphqlDatesToSystemTimeZone(bool $value = true): self
    {
        $this->setGraphqlDatesToSystemTimeZone = $value;

        return $this;
    }

    /**
     * Whether to enable Craft’s template `{% cache %}` tag on a global basis.
     *
     * ```php
     * ->enableTemplateCaching(false)
     * ```
     *
     * @group System
     *
     * @see $enableTemplateCaching
     * @see https://craftcms.com/docs/templating/cache
     */
    public function enableTemplateCaching(bool $value = true): self
    {
        $this->enableTemplateCaching = $value;

        return $this;
    }

    /**
     * Whether all Twig templates should be sandboxed.
     *
     * ```php
     * ->enableTwigSandbox(false)
     * ```
     *
     * @group Security
     *
     * @see $enableTwigSandbox
     */
    #[Deprecated(message: 'in 6.0.0. Sandbox is always enabled.')]
    public function enableTwigSandbox(bool $value = true): self
    {
        app()->booting(fn () => Deprecator::log('generalConfig.enableTwigSandbox', 'Calling enableTwigSandbox() is deprecated. Sandbox is always enabled.'));

        return $this;
    }

    /**
     * The prefix that should be prepended to HTTP error status codes when determining the path to look for an error’s template.
     *
     * If set to `'_'` your site’s 404 template would live at `templates/_404.twig`, for example.
     *
     * ```php
     * ->errorTemplatePrefix('_')
     * ```
     *
     * @group System
     *
     * @see $errorTemplatePrefix
     */
    public function errorTemplatePrefix(string $value): self
    {
        $this->errorTemplatePrefix = $value;

        return $this;
    }

    /**
     * List of file extensions that will be merged into the <config5:allowedFileExtensions> config setting.
     *
     * ```php
     * ->extraAllowedFileExtensions(['mbox', 'xml'])
     * ```
     *
     * @group System
     *
     * @param  string[]|null  $value
     *
     * @see $extraAllowedFileExtensions
     */
    public function extraAllowedFileExtensions(?array $value): self
    {
        if (is_array($value)) {
            $this->allowedFileExtensions = array_merge($this->allowedFileExtensions, array_map(strtolower(...), $value));
        }

        $this->extraAllowedFileExtensions = null;

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
     * @throws InvalidConfigException
     *
     * @see $extraAppLocales
     */
    public function extraAppLocales(array $value): self
    {
        if (class_exists(Craft::class, false)) {
            foreach ($value as &$localeId) {
                try {
                    $localeId = I18N::normalizeLanguage($localeId);
                    /** @phpstan-ignore catch.neverThrown */
                } catch (\InvalidArgumentException $e) {
                    throw new InvalidConfigException($e->getMessage(), 0, $e);
                }
            }
        }

        $this->extraAppLocales = $value;

        return $this;
    }

    /**
     * List of additional file kinds Craft should support. This array will get merged with the one defined in
     * `\craft\helpers\Assets::_buildFileKinds()`.
     *
     * ```php
     * ->extraFileKinds([
     *     // merge .psb into list of Photoshop file kinds
     *     'photoshop' => [
     *         'extensions' => ['psb'],
     *     ],
     *     // register new "Stylesheet" file kind
     *     'stylesheet' => [
     *         'label' => 'Stylesheet',
     *         'extensions' => ['css', 'less', 'pcss', 'sass', 'scss', 'styl'],
     *     ],
     * ])
     * ```
     *
     * ::: tip
     * File extensions listed here won’t immediately be allowed to be uploaded. You will also need to list them with
     * the <config5:extraAllowedFileExtensions> config setting.
     * :::
     *
     * @group Assets
     *
     * @see $extraFileKinds
     */
    public function extraFileKinds(array $value): self
    {
        $this->extraFileKinds = $value;

        return $this;
    }

    /**
     * Any additional last name prefixes that should be supported by the name parser.
     *
     * ```php
     * ->extraLastNamePrefixes(['Dal', 'Van Der'])
     * ```
     *
     * @group Users
     *
     * @param  string[]  $value
     *
     * @see $extraLastNamePrefixes
     */
    public function extraLastNamePrefixes(array $value): self
    {
        $this->extraLastNamePrefixes = $value;

        return $this;
    }

    /**
     * Any additional name salutations that should be supported by the name parser.
     *
     * ```php
     * ->extraNameSalutations(['Lady', 'Sire'])
     * ```
     *
     * @group Users
     *
     * @param  string[]  $value
     *
     * @see $extraNameSalutations
     */
    public function extraNameSalutations(array $value): self
    {
        $this->extraNameSalutations = $value;

        return $this;
    }

    /**
     * Any additional name suffixes that should be supported by the name parser.
     *
     * ```php
     * ->extraNameSuffixes(['CCNA', 'OBE'])
     * ```
     *
     * @group Users
     *
     * @param  string[]  $value
     *
     * @see $extraNameSuffixes
     */
    public function extraNameSuffixes(array $value): self
    {
        $this->extraNameSuffixes = $value;

        return $this;
    }

    /**
     * The string to use to separate words when uploading assets. If set to `false`, spaces will be left alone.
     *
     * ```php
     * ->filenameWordSeparator(false)
     * ```
     *
     * @group Assets
     *
     * @see $filenameWordSeparator
     */
    public function filenameWordSeparator(string|false $value): self
    {
        $this->filenameWordSeparator = $value;

        return $this;
    }

    /**
     * Whether image transforms should be generated before page load.
     *
     * ```php
     * ->generateTransformsBeforePageLoad(true)
     * ```
     *
     * @group Image Handling
     *
     * @see $generateTransformsBeforePageLoad
     */
    public function generateTransformsBeforePageLoad(bool $value = true): self
    {
        $this->generateTransformsBeforePageLoad = $value;

        return $this;
    }

    /**
     * Prefix to use for all type names returned by GraphQL.
     *
     * ```php
     * ->gqlTypePrefix('craft_')
     * ```
     *
     * @group GraphQL
     *
     * @see $gqlTypePrefix
     */
    public function gqlTypePrefix(string $value): self
    {
        $this->gqlTypePrefix = $value;

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
     * The image driver Craft should use to cleanse and transform images. By default Craft will use ImageMagick if it’s installed
     * and otherwise fall back to GD. You can explicitly set either `'imagick'` or `'gd'` here to override that behavior.
     *
     * ```php
     * ->imageDriver('imagick')
     * ```
     *
     * @group Image Handling
     *
     * @see $imageDriver
     */
    public function imageDriver(mixed $value): self
    {
        $this->imageDriver = $value;

        return $this;
    }

    /**
     * An array containing the selectable image aspect ratios for the image editor. The array must be in the format
     * of `label` => `ratio`, where ratio must be a float or a string. For string values, only values of “none” and “original” are allowed.
     *
     * ```php
     * ->imageEditorRatios([
     *     'Unconstrained' => 'none',
     *     'Original' => 'original',
     *     'Square' => 1,
     *     'IMAX' => 1.9,
     *     'Widescreen' => 1.78,
     * ])
     * ```
     *
     * @group Image Handling
     *
     * @see $imageEditorRatios
     */
    public function imageEditorRatios(array $value): self
    {
        $this->imageEditorRatios = $value;

        return $this;
    }

    /**
     * The template filenames Craft will look for within a directory to represent the directory’s “index” template when
     * matching a template path to a file on the front end.
     *
     * ```php
     * ->indexTemplateFilenames(['index', 'default'])
     * ```
     *
     * @group System
     *
     * @param  string[]  $value
     *
     * @see $indexTemplateFilenames
     */
    public function indexTemplateFilenames(array $value): self
    {
        $this->indexTemplateFilenames = $value;

        return $this;
    }

    /**
     * The amount of time to track invalid login attempts for a user, for determining if Craft should lock an account.
     *
     * See {@see ConfigHelper::durationInSeconds()} for a list of supported value types.
     *
     * ```php
     * // 1 day
     * ->invalidLoginWindowDuration(86400)
     * ```
     *
     * @group Security
     *
     * @defaultAlt 1 hour
     *
     * @see $invalidLoginWindowDuration
     */
    public function invalidLoginWindowDuration(mixed $value): self
    {
        $this->invalidLoginWindowDuration = ConfigHelper::durationInSeconds($value);

        return $this;
    }

    /**
     * The URI Craft should redirect to when user token validation fails. User tokens are used for
     * email verification and password resets. If `null`, <config5:loginPath> will be used by default.
     *
     * See {@see ConfigHelper::localizedValue()} for a list of supported value types.
     *
     * Note that this only affects front-end site requests.
     *
     * ```php
     * // 1 day
     * ->invalidUserTokenPath('nope')
     * ```
     *
     * @group Routing
     *
     * @see $invalidUserTokenPath
     */
    public function invalidUserTokenPath(mixed $value): self
    {
        $this->invalidUserTokenPath = $value;

        return $this;
    }

    /**
     * List of headers where proxies store the real client IP.
     *
     * See [[\yii\web\Request::ipHeaders]] for more details.
     *
     * If not set, the default [[\craft\web\Request::ipHeaders]] value will be used.
     *
     * ```php
     * ->ipHeaders(['X-Forwarded-For', 'CF-Connecting-IP'])
     * ```
     *
     * @group System
     *
     * @param  string[]|null  $value
     *
     * @see $ipHeaders
     */
    public function ipHeaders(?array $value): self
    {
        $this->ipHeaders = $value;

        return $this;
    }

    /**
     * Whether the site is currently live. If set to `true` or `false`, it will take precedence over the System Status setting
     * in Settings → General.
     *
     * ```php
     * ->isSystemLive(true)
     * ```
     *
     * @group System
     *
     * @see $isSystemLive
     */
    public function isSystemLive(?bool $value): self
    {
        $this->isSystemLive = $value;

        return $this;
    }

    /**
     * Whether GraphQL types should be generated lazily.
     *
     * ```php
     * ->lazyGqlTypes(true)
     * ```
     *
     * @group GraphQL
     *
     * @see $lazyGqlTypes
     */
    public function lazyGqlTypes(bool $value = true): self
    {
        $this->lazyGqlTypes = $value;

        return $this;
    }

    /**
     * Whether non-ASCII characters in auto-generated slugs should be converted to ASCII (i.e. ñ → n).
     *
     * ::: tip
     * This only affects the JavaScript auto-generated slugs. Non-ASCII characters can still be used in slugs if entered manually.
     * :::
     *
     * ```php
     * ->limitAutoSlugsToAscii(true)
     * ```
     *
     * @group System
     *
     * @see $limitAutoSlugsToAscii
     */
    public function limitAutoSlugsToAscii(bool $value = true): self
    {
        $this->limitAutoSlugsToAscii = $value;

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
     */
    public function localeAliases(array $value): self
    {
        $this->localeAliases = $value;

        return $this;
    }

    /**
     * The URI Craft should use for user login on the front end.
     *
     * This can be set to `false` to disable front-end login.
     *
     * See {@see ConfigHelper::localizedValue()} for a list of supported value types.
     *
     * ```php
     * ->loginPath(false)
     * ```
     *
     * @group Routing
     *
     * @see $loginPath
     */
    public function loginPath(mixed $value): self
    {
        $this->loginPath = $value;

        return $this;
    }

    /**
     * The URI Craft should use for user logout on the front end.
     *
     * This can be set to `false` to disable front-end logout.
     *
     * See {@see ConfigHelper::localizedValue()} for a list of supported value types.
     *
     * ```php
     * ->logoutPath(false)
     * ```
     *
     * @group Routing
     *
     * @see $logoutPath
     */
    public function logoutPath(mixed $value): self
    {
        $this->logoutPath = $value;

        return $this;
    }

    /**
     * The maximum dimension size to use when caching images from external sources to use in transforms. Set to `0` to never cache them.
     *
     * ```php
     * ->maxCachedCloudImageSize(0)
     * ```
     *
     * @group Image Handling
     *
     * @see $maxCachedCloudImageSize
     */
    public function maxCachedCloudImageSize(int $value): self
    {
        $this->maxCachedCloudImageSize = $value;

        return $this;
    }

    /**
     * The maximum allowed GraphQL queries that can be executed in a single batched request. Set to `0` to allow any number of queries.
     *
     * ```php
     * ->maxGraphqlBatchSize(500)
     * ```
     *
     * @group GraphQL
     *
     * @see $maxGraphqlBatchSize
     */
    public function maxGraphqlBatchSize(int $value): self
    {
        $this->maxGraphqlBatchSize = $value;

        return $this;
    }

    /**
     * The maximum allowed complexity a GraphQL query is allowed to have. Set to `0` to allow any complexity.
     *
     * ```php
     * ->maxGraphqlComplexity(500)
     * ```
     *
     * @group GraphQL
     *
     * @see $maxGraphqlComplexity
     */
    public function maxGraphqlComplexity(int $value): self
    {
        $this->maxGraphqlComplexity = $value;

        return $this;
    }

    /**
     * The maximum allowed depth a GraphQL query is allowed to reach. Set to `0` to allow any depth.
     *
     * ```php
     * ->maxGraphqlDepth(5)
     * ```
     *
     * @group GraphQL
     *
     * @see $maxGraphqlDepth
     */
    public function maxGraphqlDepth(int $value): self
    {
        $this->maxGraphqlDepth = $value;

        return $this;
    }

    /**
     * The maximum allowed results for a single GraphQL query. Set to `0` to disable any limits.
     *
     * ```php
     * ->maxGraphqlResults(100)
     * ```
     *
     * @group GraphQL
     *
     * @see $maxGraphqlResults
     */
    public function maxGraphqlResults(int $value): self
    {
        $this->maxGraphqlResults = $value;

        return $this;
    }

    /**
     * The number of invalid login attempts Craft will allow within the specified duration before the account gets locked.
     *
     * ```php
     * ->maxInvalidLogins(3)
     * ```
     *
     * @group Security
     *
     * @see $maxInvalidLogins
     */
    public function maxInvalidLogins(int|false $value): self
    {
        $this->maxInvalidLogins = $value;

        return $this;
    }

    /**
     * The number of backups Craft should make before it starts deleting the oldest backups. If set to `false`, Craft will
     * not delete any backups.
     *
     * ```php
     * ->maxBackups(5)
     * ```
     *
     * @group System
     *
     * @see $maxBackups
     */
    public function maxBackups(int|false $value): self
    {
        $this->maxBackups = $value;

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
     * The highest number Craft will tack onto a slug in order to make it unique before giving up and throwing an error.
     *
     * ```php
     * ->maxSlugIncrement(10)
     * ```
     *
     * @group System
     *
     * @see $maxSlugIncrement
     */
    public function maxSlugIncrement(int $value): self
    {
        $this->maxSlugIncrement = $value;

        return $this;
    }

    /**
     * The maximum upload file size allowed.
     *
     * See [[PHP::sizeToBytes()]] for a list of supported value types.
     *
     * ```php
     * // 25MB
     * ->maxUploadFileSize(26214400)
     * ```
     *
     * @group Assets
     *
     * @defaultAlt 16MB
     *
     * @see $maxUploadFileSize
     */
    public function maxUploadFileSize(string|int $value): self
    {
        $this->maxUploadFileSize = PHP::sizeToBytes($value);

        return $this;
    }

    /**
     * Whether generated URLs should omit `index.php` (e.g. `http://my-project.tld/path` instead of `http://my-project.tld/index.php/path`)
     *
     * This can only be possible if your server is configured to redirect would-be 404s to `index.php`, for example, with the redirect found
     * in the `.htaccess` file that came with Craft:
     *
     * ```
     * RewriteEngine On
     * RewriteCond %{REQUEST_FILENAME} !-f
     * RewriteCond %{REQUEST_FILENAME} !-d
     * RewriteRule (.+) /index.php?p=$1 [QSA,L]
     * ```
     *
     * ```php
     * ->omitScriptNameInUrls(true)
     * ```
     *
     * @group Routing
     *
     * @see $omitScriptNameInUrls
     * @since 4.2.0
     */
    #[Deprecated(message: 'in 6.0.0. Script name is now always omitted.')]
    public function omitScriptNameInUrls(bool $value = true): self
    {
        app()->booting(fn () => Deprecator::log('generalConfig.omitScriptNameInUrls', 'Calling omitScriptNameInUrls() is deprecated. Script name is now always omitted.'));

        $this->omitScriptNameInUrls = $value;

        return $this;
    }

    /**
     * Whether Craft should optimize images for reduced file sizes without noticeably reducing image quality. (Only supported when
     * ImageMagick is used.)
     *
     * ```php
     * ->optimizeImageFilesize(false)
     * ```
     *
     * @group Image Handling
     *
     * @see $optimizeImageFilesize
     */
    public function optimizeImageFilesize(bool $value = true): self
    {
        $this->optimizeImageFilesize = $value;

        return $this;
    }

    /**
     * The string preceding a number which Craft will look for when determining if the current request is for a particular page in
     * a paginated list of pages.
     *
     * | Example Value | Example URI |
     * | --- | --- |
     * | `p` | `/news/p5` |
     * | `page` | `/news/page5` |
     * | `page/` | `/news/page/5` |
     * | `?page` | `/news?page=5` |
     *
     * ::: warning
     * Craft may override this setting if it conflicts with <config5:pathParam>. If you want to set this to `?p` (e.g. `/news?p=5`), you’ll also need to change your <config5:pathParam> setting (which defaults to `p`).
     * Then, if your server is running Apache, you’ll need to update the redirect code in your `.htaccess` file to match your new `pathParam` value.
     * :::
     *
     * ```php
     * ->pageTrigger('page')
     * ```
     *
     * @group Routing
     *
     * @see $pageTrigger
     */
    public function pageTrigger(string $value): self
    {
        $this->pageTrigger = $value;

        return $this;
    }

    /**
     * The path within the `templates` folder where element partial templates will live.
     *
     * Partial templates are used to render elements when calling [[\craft\elements\db\ElementQuery::render()]],
     * [[\craft\elements\ElementCollection::render()]], or [[\craft\base\Element::render()]].
     *
     * For example, you could render all the entries within a Matrix field like so:
     *
     * ```twig
     * {{ entry.myMatrixField.render() }}
     * ```
     *
     * The full path to a partial template will also include the element type handle (e.g. `asset` or `entry`) and the
     * field layout provider’s handle (e.g. the volume handle or entry type handle). For an entry of type `article`,
     * that would be: `_partials/entry/article.twig`.
     *
     * ::: code
     * ```php Static Config
     * ->partialTemplatesPath('_cp/partials')
     * ```
     * ```shell Environment Override
     * CRAFT_PARTIAL_TEMPLATES_PATH=_cp/partials
     * ```
     * :::
     *
     * @group System
     *
     * @see $partialTemplatesPath
     */
    public function partialTemplatesPath(string $value): self
    {
        $this->partialTemplatesPath = $value;

        return $this;
    }

    /**
     * The query string param that Craft will check when determining the request’s path.
     *
     * This can be set to `null` if your web server is capable of directing traffic to `index.php` without a query string param.
     * If you’re using Apache, that means you’ll need to change the `RewriteRule` line in your `.htaccess` file to:
     *
     * ```
     * RewriteRule (.+) index.php [QSA,L]
     * ```
     *
     * ```php
     * ->pathParam(null)
     * ```
     *
     * @group Routing
     *
     * @see $pathParam
     * @since 4.2.0
     */
    #[Deprecated(message: 'in 6.0.0. This method no longer does anything.')]
    public function pathParam(?string $value): self
    {
        app()->booting(fn () => Deprecator::log('generalConfig.pathParam', 'Calling pathParam() is deprecated.'));

        $this->pathParam = $value;

        return $this;
    }

    /**
     * The `Permissions-Policy` header that should be sent for web responses.
     *
     * ```php
     * ->permissionsPolicyHeader('Permissions-Policy: geolocation=(self)')
     * ```
     *
     * @group System
     *
     * @see $permissionsPolicyHeader
     * @since 4.2.0
     */
    #[Deprecated(message: 'in 4.11.0. [[\craft\filters\Headers]] should be used instead.')]
    public function permissionsPolicyHeader(?string $value): self
    {
        $this->permissionsPolicyHeader = $value;

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
     * The name of the PHP session cookie.
     *
     * ```php
     * ->phpSessionName(null)
     * ```
     *
     * @group Session
     *
     * @see $phpSessionName
     * @see https://php.net/manual/en/function.session-name.php
     * @since 4.2.0
     */
    #[Deprecated(message: 'in 6.0.0. Configure `session.cookie` or set `SESSION_COOKIE` environment variable.')]
    public function phpSessionName(string $value): self
    {
        $this->phpSessionName = $value;

        app()->booting(function () use ($value) {
            Deprecator::log('generalConfig.phpSessionName', 'Calling phpSessionName() is deprecated. Configure `session.cookie` or set `SESSION_COOKIE` environment variable.');
            Config::set('session.cookie', $value);
        });

        return $this;
    }

    /**
     * The path users should be redirected to after logging into the control panel.
     *
     * This setting will also come into effect if a user visits the control panel’s login page (`/admin/login`) or the control panel’s
     * root URL (`/admin`) when they are already logged in.
     *
     * See {@see ConfigHelper::localizedValue()} for a list of supported value types.
     *
     * ```php
     * ->postCpLoginRedirect('entries')
     * ```
     *
     * @group Routing
     *
     * @see $postCpLoginRedirect
     */
    public function postCpLoginRedirect(mixed $value): self
    {
        $this->postCpLoginRedirect = $value;

        return $this;
    }

    /**
     * The path users should be redirected to after logging in from the front-end site.
     *
     * This setting will also come into effect if the user visits the login page (as specified by the <config5:loginPath> config setting) when
     * they are already logged in.
     *
     * See {@see ConfigHelper::localizedValue()} for a list of supported value types.
     *
     * ```php
     * ->postLoginRedirect('welcome')
     * ```
     *
     * @group Routing
     *
     * @see $postLoginRedirect
     */
    public function postLoginRedirect(mixed $value): self
    {
        $this->postLoginRedirect = $value;

        return $this;
    }

    /**
     * The path that users should be redirected to after logging out from the front-end site.
     *
     * See {@see ConfigHelper::localizedValue()} for a list of supported value types.
     *
     * ```php
     * ->postLogoutRedirect('goodbye')
     * ```
     *
     * @group Routing
     *
     * @see $postLogoutRedirect
     */
    public function postLogoutRedirect(mixed $value): self
    {
        $this->postLogoutRedirect = $value;

        return $this;
    }

    /**
     * Whether the <config5:gqlTypePrefix> config setting should have an impact on `query`, `mutation`, and `subscription` types.
     *
     * ```php
     * ->prefixGqlRootTypes(false)
     * ```
     *
     * @group GraphQL
     *
     * @see $prefixGqlRootTypes
     */
    public function prefixGqlRootTypes(bool $value = true): self
    {
        $this->prefixGqlRootTypes = $value;

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
     * ::: code
     * ```php Static Config
     * ->preloadSingles()
     * ```
     * ```shell Environment Override
     * CRAFT_PRELOAD_SINGLES=true
     * ```
     * :::
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

    /**
     * Whether CMYK should be preserved as the colorspace when manipulating images.
     *
     * Setting this to `true` will prevent Craft from transforming CMYK images to sRGB, but on some ImageMagick versions it can cause
     * image color distortion. This will only have an effect if ImageMagick is in use.
     *
     * ```php
     * ->preserveCmykColorspace(true)
     * ```
     *
     * @group Image Handling
     *
     * @see $preserveCmykColorspace
     */
    public function preserveCmykColorspace(bool $value = true): self
    {
        $this->preserveCmykColorspace = $value;

        return $this;
    }

    /**
     * Whether the EXIF data should be preserved when manipulating and uploading images.
     *
     * Setting this to `true` will result in larger image file sizes.
     *
     * This will only have effect if ImageMagick is in use.
     *
     * ```php
     * ->preserveExifData(true)
     * ```
     *
     * @group Image Handling
     *
     * @see $preserveExifData
     */
    public function preserveExifData(bool $value = true): self
    {
        $this->preserveExifData = $value;

        return $this;
    }

    /**
     * Whether the embedded Image Color Profile (ICC) should be preserved when manipulating images.
     *
     * Setting this to `false` will reduce the image size a little bit, but on some ImageMagick versions can cause images to be saved with
     * an incorrect gamma value, which causes the images to become very dark. This will only have effect if ImageMagick is in use.
     *
     * ```php
     * ->preserveImageColorProfiles(false)
     * ```
     *
     * @group Image Handling
     *
     * @see $preserveImageColorProfiles
     */
    public function preserveImageColorProfiles(bool $value = true): self
    {
        $this->preserveImageColorProfiles = $value;

        return $this;
    }

    /**
     * When `true`, Craft will always return a successful response in the “forgot password” flow, making it difficult to enumerate users.
     *
     * When set to `false` and you go through the “forgot password” flow from the control panel login page, you’ll get distinct messages indicating
     * whether the username/email exists and whether an email was sent with further instructions. This can be helpful for the user attempting to
     * log in but allow for username/email enumeration based on the response.
     *
     * ```php
     * ->preventUserEnumeration(true)
     * ```
     *
     * @group Security
     *
     * @see $preventUserEnumeration
     */
    public function preventUserEnumeration(bool $value = true): self
    {
        $this->preventUserEnumeration = $value;

        return $this;
    }

    /**
     * Custom [iFrame Resizer options](http://davidjbradshaw.github.io/iframe-resizer/#options) that should be used for preview iframes.
     *
     * ```php
     * ->previewIframeResizerOptions([
     *     'autoResize' => false,
     * ])
     * ```
     *
     * @group System
     *
     * @see $previewIframeResizerOptions
     */
    public function previewIframeResizerOptions(array $value): self
    {
        $this->previewIframeResizerOptions = $value;

        return $this;
    }

    /**
     * The amount of time content preview tokens can be used before expiring.
     *
     * Defaults to <config5:defaultTokenDuration> value.
     *
     * See {@see ConfigHelper::durationInSeconds()} for a list of supported value types.
     *
     * ```php
     * // 1 hour
     * ->previewTokenDuration(3600)
     * ```
     *
     * @group Security
     *
     * @defaultAlt 1 day
     *
     * @see $previewTokenDuration
     */
    public function previewTokenDuration(mixed $value): self
    {
        $this->previewTokenDuration = ConfigHelper::durationInSeconds($value);

        return $this;
    }

    /**
     * The template path segment prefix that should be used to identify “private” templates, which are templates that are not
     * directly accessible via a matching URL.
     *
     * Set to an empty value to disable public template routing.
     *
     * ```php
     * ->privateTemplateTrigger('')
     * ```
     *
     * @group System
     *
     * @see $privateTemplateTrigger
     */
    public function privateTemplateTrigger(string $value): self
    {
        $this->privateTemplateTrigger = $value;

        return $this;
    }

    /**
     * The amount of time to wait before Craft purges pending users from the system that have not activated.
     *
     * Any content assigned to a pending user will be deleted as well when the given time interval passes.
     *
     * Set to `0` to disable this feature.
     *
     * See {@see ConfigHelper::durationInSeconds()} for a list of supported value types.
     *
     * ::: tip
     * Users will only be purged when [garbage collection](https://craftcms.com/docs/5.x/system/gc.html) is run.
     * :::
     *
     * ```php
     * // 2 weeks
     * ->purgePendingUsersDuration(1209600)
     * ```
     *
     * @group Garbage Collection
     *
     * @see $purgePendingUsersDuration
     */
    public function purgePendingUsersDuration(mixed $value): self
    {
        $this->purgePendingUsersDuration = ConfigHelper::durationInSeconds($value);

        return $this;
    }

    /**
     * The amount of time to wait before Craft purges stale user sessions from the sessions table in the database.
     *
     * Set to `0` to disable this feature.
     *
     * See {@see ConfigHelper::durationInSeconds()} for a list of supported value types.
     *
     * ```php
     * // 1 week
     * ->purgeStaleUserSessionDuration(604800)
     * ```
     *
     * @group Garbage Collection
     *
     * @defaultAlt 90 days
     *
     * @see $purgeStaleUserSessionDuration
     * @since 4.2.0
     */
    #[Deprecated(message: 'in 6.0.0. This method no longer does anything, sessions are cleaned up on a lottery basis when needed.')]
    public function purgeStaleUserSessionDuration(mixed $value): self
    {
        app()->booting(fn () => Deprecator::log('generalConfig.purgeStaleUserSessionDuration', 'Calling purgeStaleUserSessionDuration() is deprecated. Sessions are cleaned up on a lottery basis when needed.'));

        $this->purgeStaleUserSessionDuration = $value;

        return $this;
    }

    /**
     * The amount of time to wait before Craft purges unpublished drafts that were never updated with content.
     *
     * Set to `0` to disable this feature.
     *
     * See {@see ConfigHelper::durationInSeconds()} for a list of supported value types.
     *
     * ```php
     * ->purgeUnsavedDraftsDuration(0)
     * ```
     *
     * @group Garbage Collection
     *
     * @defaultAlt 30 days
     *
     * @see $purgeUnsavedDraftsDuration
     */
    public function purgeUnsavedDraftsDuration(mixed $value): self
    {
        $this->purgeUnsavedDraftsDuration = ConfigHelper::durationInSeconds($value);

        return $this;
    }

    /**
     * Whether SVG thumbnails should be rasterized.
     *
     * This will only work if ImageMagick is installed, and <config5:imageDriver> is set to either `auto` or `imagick`.
     *
     * ```php
     * ->rasterizeSvgThumbs(true)
     * ```
     *
     * @group Image Handling
     *
     * @see $rasterizeSvgThumbs
     */
    public function rasterizeSvgThumbs(bool $value = true): self
    {
        $this->rasterizeSvgThumbs = $value;

        return $this;
    }

    /**
     * The amount of time a user stays logged if “Remember Me” is checked on the login page.
     *
     * Set to `0` to disable the “Remember Me” feature altogether.
     *
     * See {@see ConfigHelper::durationInSeconds()} for a list of supported value types.
     *
     * ```php
     * ->rememberedUserSessionDuration(0)
     * ```
     *
     * @group Session
     *
     * @defaultAlt 14 days
     *
     * @throws InvalidConfigException
     *
     * @see $rememberedUserSessionDuration
     * @see getRememberedUserSessionDuration()
     * @since 4.2.0
     */
    #[Deprecated(message: 'in 6.0.0. Configure `auth.guards.web.remember` in minutes instead.')]
    public function rememberedUserSessionDuration(mixed $value): self
    {
        // Store the DateInterval separately for getRememberedUserSessionDuration()
        try {
            $interval = DateTimeHelper::toDateInterval($value);
        } catch (InvalidArgumentException $e) {
            throw new InvalidConfigException($e->getMessage(), 0, $e);
        }

        $this->rememberedUserSessionDuration = $interval ? ConfigHelper::durationInSeconds($interval) : 0;
        $this->_rememberedUserSessionDuration = $interval ?: null;

        app()->booting(function () {
            Config::set('auth.guards.web.remember', floor($this->rememberedUserSessionDuration / 60));
        });

        return $this;
    }

    /**
     * The amount of time Craft will remember a username and pre-populate it on the control panel’s Login page.
     *
     * Set to `0` to disable this feature altogether.
     *
     * See {@see ConfigHelper::durationInSeconds()} for a list of supported value types.
     *
     * ```php
     * ->rememberUsernameDuration(0)
     * ```
     *
     * @group Session
     *
     * @defaultAlt 1 year
     *
     * @see $rememberUsernameDuration
     */
    public function rememberUsernameDuration(mixed $value): self
    {
        $this->rememberUsernameDuration = ConfigHelper::durationInSeconds($value);

        return $this;
    }

    /**
     * Whether Craft should require a matching user agent string when restoring a user session from a cookie.
     *
     * ```php
     * ->requireMatchingUserAgentForSession(false)
     * ```
     *
     * @group Session
     *
     * @see $requireMatchingUserAgentForSession
     * @since 4.2.0
     */
    #[Deprecated(message: 'in 6.0.0. This method no longer configures anything.')]
    public function requireMatchingUserAgentForSession(bool $value = true): self
    {
        app()->booting(fn () => Deprecator::log('generalConfig.requireMatchingUserAgentForSession', 'Calling requireMatchingUserAgentForSession() is deprecated.'));

        $this->requireMatchingUserAgentForSession = $value;

        return $this;
    }

    /**
     * Whether Craft should require the existence of a user agent string and IP address when creating a new user session.
     *
     * ```php
     * ->requireUserAgentAndIpForSession(false)
     * ```
     *
     * @group Session
     *
     * @see $requireUserAgentAndIpForSession
     * @since 4.2.0
     */
    #[Deprecated(message: 'in 6.0.0. This method no longer configures anything.')]
    public function requireUserAgentAndIpForSession(bool $value = true): self
    {
        app()->booting(fn () => Deprecator::log('generalConfig.requireUserAgentAndIpForSession', 'Calling requireUserAgentAndIpForSession() is deprecated.'));

        $this->requireUserAgentAndIpForSession = $value;

        return $this;
    }

    /**
     * The path to the root directory that should store published control panel resources.
     *
     * ```php
     * ->resourceBasePath('@webroot/craft-resources')
     * ```
     *
     * @group Environment
     *
     * @see $resourceBasePath
     */
    public function resourceBasePath(string $value): self
    {
        $this->resourceBasePath = $value;

        return $this;
    }

    /**
     * The URL to the root directory where control panel resources are published.
     *
     * ```php
     * ->resourceBaseUrl('@web/craft-resources')
     * ```
     *
     * @group Environment
     *
     * @see $resourceBaseUrl
     */
    public function resourceBaseUrl(string $value): self
    {
        $this->resourceBaseUrl = $value;

        return $this;
    }

    /**
     * The shell command Craft should execute to restore a database backup.
     *
     * By default Craft will run `mysql` or `psql`, provided those libraries are in the `$PATH` variable for the user the web server is running as.
     *
     * There are several tokens you can use that Craft will swap out at runtime:
     *
     * - `{path}` - the backup file path
     * - `{port}` - the current database port
     * - `{server}` - the current database hostname
     * - `{user}` - the user to connect to the database
     * - `{database}` - the current database name
     * - `{schema}` - the current database schema (if any)
     *
     * This can also be set to `false` to disable database restores completely.
     *
     * ```php
     * ->restoreCommand(false)
     * ```
     *
     * @group Environment
     *
     * @see $restoreCommand
     */
    public function restoreCommand(string|null|false|Closure $value): self
    {
        $this->restoreCommand = $value;

        return $this;
    }

    /**
     * Whether asset URLs should be revved so browsers don’t load cached versions when they’re modified.
     *
     * ```php
     * ->revAssetUrls(true)
     * ```
     *
     * @group Assets
     *
     * @see $revAssetUrls
     */
    public function revAssetUrls(bool $value = true): self
    {
        $this->revAssetUrls = $value;

        return $this;
    }

    /**
     * Whether Craft should rotate images according to their EXIF data on upload.
     *
     * ```php
     * ->rotateImagesOnUploadByExifData(false)
     * ```
     *
     * @group Image Handling
     *
     * @see $rotateImagesOnUploadByExifData
     */
    public function rotateImagesOnUploadByExifData(bool $value = true): self
    {
        $this->rotateImagesOnUploadByExifData = $value;

        return $this;
    }

    /**
     * Whether Craft should run pending queue jobs automatically when someone visits the control panel.
     *
     * If disabled, an alternate queue worker *must* be set up separately, either as an
     * [always-running daemon](https://github.com/yiisoft/yii2-queue/blob/master/docs/guide/worker.md), or a cron job that runs the
     * `queue/run` command every minute:
     *
     * ```cron
     * * * * * * /path/to/project/craft queue/run
     * ```
     *
     * ::: tip
     * This setting should be disabled for servers running Win32, or with Apache’s mod_deflate/mod_gzip installed,
     * where PHP’s [flush()](https://php.net/manual/en/function.flush.php) method won’t work.
     * :::
     *
     * ```php
     * ->runQueueAutomatically(false)
     * ```
     *
     * @group System
     *
     * @see $runQueueAutomatically
     */
    public function runQueueAutomatically(bool $value = true): self
    {
        $this->runQueueAutomatically = $value;

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
     * The [SameSite](https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/Set-Cookie/SameSite) value that should be set on Craft cookies, if any.
     *
     * This can be set to `'None'`, `'Lax'`, `'Strict'`, or `null`.
     *
     * ```php
     * ->sameSiteCookieValue('Strict')
     * ```
     *
     * @group System
     *
     * @phpstan-param 'None'|'Lax'|'Strict'|null $value
     *
     * @see $sameSiteCookieValue
     * @since 4.2.0
     */
    #[Deprecated(message: 'in 6.0.0. Configure `cookie.same_site` or set `SESSION_SAME_SITE` environment variable instead.')]
    public function sameSiteCookieValue(?string $value): self
    {
        app()->booting(fn () => Deprecator::log('generalConfig.sameSiteCookieValue', 'Calling sameSiteCookieValue() is deprecated. Configure `cookie.same_site` or set `SESSION_SAME_SITE` environment variable instead.'));

        $this->sameSiteCookieValue = $value;

        return $this;
    }

    /**
     * Whether images uploaded via the control panel should be sanitized.
     *
     * ```php
     * ->sanitizeCpImageUploads(false)
     * ```
     *
     * @group Security
     *
     * @see $sanitizeCpImageUploads
     */
    public function sanitizeCpImageUploads(bool $value = true): self
    {
        $this->sanitizeCpImageUploads = $value;

        return $this;
    }

    /**
     * Whether Craft should sanitize uploaded SVG files and strip out potential malicious-looking content.
     *
     * This should definitely be enabled if you are accepting SVG uploads from untrusted sources.
     *
     * ```php
     * ->sanitizeSvgUploads(false)
     * ```
     *
     * @group Security
     *
     * @see $sanitizeSvgUploads
     */
    public function sanitizeSvgUploads(bool $value = true): self
    {
        $this->sanitizeSvgUploads = $value;

        return $this;
    }

    /**
     * Lists of headers that are, by default, subject to the trusted host configuration.
     *
     * See [[\yii\web\Request::secureHeaders]] for more details.
     *
     * If not set, the default [[\yii\web\Request::secureHeaders]] value will be used.
     *
     * ```php
     * ->secureHeaders([
     *     'X-Forwarded-For',
     *     'X-Forwarded-Host',
     *     'X-Forwarded-Proto',
     *     'X-Rewrite-Url',
     *     'X-Original-Host',
     *     'CF-Connecting-IP',
     * ])
     * ```
     *
     * @group Security
     *
     * @see $secureHeaders
     * @since 4.2.0
     */
    #[Deprecated(message: 'in 6.0.0. [Configure trusted proxies instead](https://laravel.com/docs/12.x/requests#configuring-trusted-proxies).')]
    public function secureHeaders(?array $value): self
    {
        app()->booting(fn () => Deprecator::log('generalConfig.secureHeaders', 'Calling secureHeaders() is deprecated. [Configure trusted proxies instead](https://laravel.com/docs/12.x/requests#configuring-trusted-proxies)'));

        $this->secureHeaders = $value;

        if (! $value) {
            return $this;
        }

        $headerMap = [
            'X-Forwarded-For' => Request::HEADER_X_FORWARDED_FOR,
            'X-Forwarded-Host' => Request::HEADER_X_FORWARDED_HOST,
            'X-Forwarded-Port' => Request::HEADER_X_FORWARDED_PORT,
            'X-Forwarded-Proto' => Request::HEADER_X_FORWARDED_PROTO,
            'X-Forwarded-Aws-ELB' => Request::HEADER_X_FORWARDED_AWS_ELB,
            'X-Forwarded-Traefik' => Request::HEADER_X_FORWARDED_TRAEFIK,
        ];

        $bitmask = 0;
        foreach ($value as $header) {
            if (isset($headerMap[$header])) {
                $bitmask |= $headerMap[$header];
            }
        }

        TrustProxies::withHeaders($bitmask);

        return $this;
    }

    /**
     * List of headers to check for determining whether the connection is made via HTTPS.
     *
     * See [[\yii\web\Request::secureProtocolHeaders]] for more details.
     *
     * If not set, the default [[\yii\web\Request::secureProtocolHeaders]] value will be used.
     *
     * ```php
     * ->secureProtocolHeaders([
     *     'X-Forwarded-Proto' => [
     *         'https',
     *     ],
     *     'Front-End-Https' => [
     *         'on',
     *     ],
     *     'CF-Visitor' => [
     *         '{\"scheme\":\"https\"}',
     *     ],
     * ])
     * ```
     *
     * @group Security
     *
     * @see $secureProtocolHeaders
     * @since 4.2.0
     */
    #[Deprecated(message: 'in 6.0.0. This method no longer configures anything.')]
    public function secureProtocolHeaders(?array $value): self
    {
        app()->booting(fn () => Deprecator::log('generalConfig.secureProtocolHeaders', 'Calling secureProtocolHeaders() is deprecated.'));

        $this->secureProtocolHeaders = $value;

        return $this;
    }

    /**
     * A private, random, cryptographically-secure key that is used for hashing and encrypting data in [[\craft\services\Security]].
     *
     * ::: warning
     * **Do not** share this key publicly. If exposed, it could lead to a compromised system.
     * :::
     *
     * In the event that the key is compromised, a new secure key can be generated with the command:
     *
     * ```sh
     * php craft setup/security-key
     * ```
     *
     * Note that if the key changes, any data that is encrypted with it (e.g. user session cookies) will be inaccessible.
     *
     * ```php
     * ->securityKey('2cf24dba5...')
     * ```
     *
     * @group Security
     *
     * @see $securityKey
     * @see https://craftcms.com/knowledge-base/securing-craft
     * @since 4.2.0
     */
    #[Deprecated(message: 'in 6.0.0. Configure `app.key` or set `APP_KEY` in your environment instead.')]
    public function securityKey(string $value): self
    {
        $this->securityKey = $value;

        app()->booting(function () use ($value) {
            Deprecator::log('generalConfig.securityKey', 'Calling securityKey() is deprecated.');
            Config::set('app.key', $value);
        });

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
     * The URI or URL that Craft should use for Set Password forms on the front end.
     *
     * See {@see ConfigHelper::localizedValue()} for a list of supported value types.
     *
     * ::: tip
     * You might also want to set <config5:invalidUserTokenPath> in case a user clicks on an expired password reset link.
     * :::
     *
     * ```php
     * ->setPasswordPath('set-password')
     * ```
     *
     * @group Routing
     *
     * @see $setPasswordPath
     */
    public function setPasswordPath(mixed $value): self
    {
        $this->setPasswordPath = $value;

        return $this;
    }

    /**
     * The URI to the page where users can request to change their password.
     *
     * See {@see ConfigHelper::localizedValue()} for a list of supported value types.
     *
     * If this is set, Craft will redirect [.well-known/change-password requests](https://w3c.github.io/webappsec-change-password-url/) to this URI.
     *
     * ::: tip
     * You’ll also need to set [setPasswordPath](config5:setPasswordPath), which determines the URI and template path for the Set Password form
     * where the user resets their password after following the link in the Password Reset email.
     * :::
     *
     * ```php
     * ->setPasswordRequestPath('request-password')
     * ```
     *
     * @group Routing
     *
     * @see $setPasswordRequestPath
     */
    public function setPasswordRequestPath(mixed $value): self
    {
        $this->setPasswordRequestPath = $value;

        return $this;
    }

    /**
     * The URI Craft should redirect users to after setting their password from the front end.
     *
     * See {@see ConfigHelper::localizedValue()} for a list of supported value types.
     *
     * ```php
     * ->setPasswordSuccessPath('password-set')
     * ```
     *
     * @group Routing
     *
     * @see $setPasswordSuccessPath
     */
    public function setPasswordSuccessPath(mixed $value): self
    {
        $this->setPasswordSuccessPath = $value;

        return $this;
    }

    /**
     * The query string parameter name that site tokens should be set to.
     *
     * ```php
     * ->siteToken('t')
     * ```
     *
     * @group Routing
     *
     * @see $siteToken
     */
    public function siteToken(string $value): self
    {
        $this->siteToken = $value;

        return $this;
    }

    /**
     * The character(s) that should be used to separate words in slugs.
     *
     * ```php
     * ->slugWordSeparator('.')
     * ```
     *
     * @group System
     *
     * @see $slugWordSeparator
     */
    public function slugWordSeparator(string $value): self
    {
        $this->slugWordSeparator = $value;

        return $this;
    }

    /**
     * Whether “First Name” and “Last Name” fields should be shown in place of “Full Name” fields.
     *
     * ```php
     * ->showFirstAndLastNameFields()
     * ```
     *
     * @group Users
     *
     * @see $showFirstAndLastNameFields
     */
    public function showFirstAndLastNameFields(bool $value = true): self
    {
        $this->showFirstAndLastNameFields = $value;

        return $this;
    }

    /**
     * The amount of time before a soft-deleted item will be up for hard-deletion by garbage collection.
     *
     * Set to `0` if you don’t ever want to delete soft-deleted items.
     *
     * See {@see ConfigHelper::durationInSeconds()} for a list of supported value types.
     *
     * ```php
     * ->softDeleteDuration(0)
     * ```
     *
     * @group Garbage Collection
     *
     * @defaultAlt 30 days
     *
     * @see $softDeleteDuration
     */
    public function softDeleteDuration(mixed $value): self
    {
        $this->softDeleteDuration = ConfigHelper::durationInSeconds($value);

        return $this;
    }

    /**
     * Whether entries’ statuses should be stored statically, and only get updated on entry save, or when the
     * `update-statuses` command is executed.
     *
     * ::: code
     * ```php Static Config
     * ->staticStatuses()
     * ```
     * ```shell Environment Override
     * CRAFT_STATIC_STATUSES=true
     * ```
     * :::
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
     * Whether user IP addresses should be stored/logged by the system.
     *
     * ```php
     * ->storeUserIps(true)
     * ```
     *
     * @group Security
     *
     * @see $storeUserIps
     */
    public function storeUserIps(bool $value = true): self
    {
        $this->storeUserIps = $value;

        return $this;
    }

    /**
     * The URL to a CSS file that should be included when rendering system templates on the front end,
     * such as the Login and Set Password templates.
     *
     * ::: code
     * ```php Static Config
     * ->systemTemplateCss('/css/cp-theme.css');
     * ```
     * ```shell Environment Override
     * CRAFT_SYSTEM_TEMPLATE_CSS=/css/cp-theme.css
     * ```
     * :::
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
     * The handle of the filesystem that should be used for storing temporary asset uploads. A local temp folder will
     * be used by default.
     *
     *  ```php
     *  ->tempAssetUploadFs('$TEMP_ASSET_UPLOADS_FS')
     *  ```
     *
     * @group Assets
     *
     * @see $tempAssetUploadFs
     */
    public function tempAssetUploadFs(?string $value): self
    {
        $this->tempAssetUploadFs = $value;

        return $this;
    }

    /**
     * Configures Craft to send all system emails to either a single email address or an array of email addresses
     * for testing purposes.
     *
     * By default, the recipient name(s) will be “Test Recipient”, but you can customize that by setting the value with the format
     * `['me@domain.tld' => 'Name']`.
     *
     * ```php
     * ->testToEmailAddress('me@domain.tld')
     * ```
     *
     * @group System
     *
     * @see $testToEmailAddress
     */
    public function testToEmailAddress(string|array|null|false $value): self
    {
        $this->testToEmailAddress = $value;

        return $this;
    }

    /**
     * The timezone of the site. If set, it will take precedence over the Timezone setting in Settings → General.
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
    #[Deprecated(message: "in 6.0.0. Laravel's `app.timezone` config variable should be used instead.")]
    public function timezone(?string $value): self
    {
        app()->booting(function () use ($value) {
            Deprecator::log('generalConfig.timezone', 'Calling timezone() is deprecated. Laravel\'s `app.timezone` config variable should be used instead.');
            Config::set('app.timezone', $value);
        });

        $this->timezone = $value;

        return $this;
    }

    /**
     * Whether GIF files should be cleansed/transformed.
     *
     * ```php
     * ->transformGifs(false)
     * ```
     *
     * @group Image Handling
     *
     * @see $transformGifs
     */
    public function transformGifs(bool $value = true): self
    {
        $this->transformGifs = $value;

        return $this;
    }

    /**
     * Whether SVG files should be transformed.
     *
     * ```php
     * ->transformSvgs(false)
     * ```
     *
     * @group Image Handling
     *
     * @see $transformSvgs
     */
    public function transformSvgs(bool $value = true): self
    {
        $this->transformSvgs = $value;

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
     * The configuration for trusted security-related headers.
     *
     * See [[\yii\web\Request::trustedHosts]] for more details.
     *
     * By default, all hosts are trusted.
     *
     * ```php
     * ->trustedHosts(['trusted-one.foo', 'trusted-two.foo'])
     * ```
     *
     * @group Security
     *
     * @see $trustedHosts
     * @since 4.2.0
     */
    #[Deprecated(message: 'in 6.0.0. [Configure trusted proxies instead](https://laravel.com/docs/12.x/requests#configuring-trusted-proxies).')]
    public function trustedHosts(array $value): self
    {
        app()->booting(fn () => Deprecator::log('generalConfig.trustedHosts', 'Calling secureProtocolHeaders() is deprecated. [Configure trusted proxies instead](https://laravel.com/docs/12.x/requests#configuring-trusted-proxies).'));

        $this->trustedHosts = $value;

        if (! empty($value)) {
            TrustProxies::at($value);
        }

        return $this;
    }

    /**
     * The query string parameter name that Craft tokens should be set to.
     *
     * ```php
     * ->tokenParam('t')
     * ```
     *
     * @group Routing
     *
     * @see $tokenParam
     */
    public function tokenParam(string $value): self
    {
        $this->tokenParam = $value;

        return $this;
    }

    /**
     * Whether image transforms should allow upscaling by default, for images that are smaller than the transform dimensions.
     *
     * ```php
     * ->upscaleImages(false)
     * ```
     *
     * @group Image Handling
     *
     * @see $upscaleImages
     */
    public function upscaleImages(bool $value = true): self
    {
        $this->upscaleImages = $value;

        return $this;
    }

    /**
     * Whether Craft should set users’ usernames to their email addresses, rather than let them set their username separately.
     *
     * If you enable this setting after user accounts already exist, run this terminal command to update existing usernames:
     *
     * ```bash
     * php craft utils/update-usernames
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
     * Whether [iFrame Resizer options](http://davidjbradshaw.github.io/iframe-resizer/#options) should be used for Live Preview.
     *
     * Using iFrame Resizer makes it possible for Craft to retain the preview’s scroll position between page loads, for cross-origin web pages.
     *
     * It works by setting the height of the iframe to match the height of the inner web page, and the iframe’s container will be scrolled rather
     * than the iframe document itself. This can lead to some unexpected CSS issues, however, because the previewed viewport height will be taller
     * than the visible portion of the iframe.
     *
     * If you have a [decoupled front end](https://craftcms.com/docs/5.x/reference/element-types/entries.html#previewing-decoupled-front-ends), you will need to include
     * [iframeResizer.contentWindow.min.js](https://raw.github.com/davidjbradshaw/iframe-resizer/master/js/iframeResizer.contentWindow.min.js) on your
     * page as well for this to work. You can conditionally include it for only Live Preview requests by checking if the requested URL contains a
     * `x-craft-live-preview` query string parameter.
     *
     * ::: tip
     * You can customize the behavior of iFrame Resizer via the <config5:previewIframeResizerOptions> config setting.
     * :::
     *
     * ```php
     * ->useIframeResizer(true)
     * ```
     *
     * @group System
     *
     * @see $useIframeResizer
     */
    public function useIframeResizer(bool $value = true): self
    {
        $this->useIframeResizer = $value;

        return $this;
    }

    /**
     * Whether Craft should specify the path using `PATH_INFO` or as a query string parameter when generating URLs.
     *
     * This setting only takes effect if <config5:omitScriptNameInUrls> is set to `false`.
     *
     * ```php
     * ->usePathInfo(true)
     * ```
     *
     * @group Routing
     *
     * @see $usePathInfo
     * @since 4.2.0
     */
    #[Deprecated(message: 'in 6.0.0. This setting no longer has any effect.')]
    public function usePathInfo(bool $value = true): self
    {
        app()->booting(fn () => Deprecator::log('generalConfig.usePathInfo', 'Calling usePathInfo() is deprecated. This setting no longer has any effect.'));

        $this->usePathInfo = $value;

        return $this;
    }

    /**
     * Whether Craft will set the “secure” flag when saving cookies when using `Craft::cookieConfig()` to create a cookie.
     *
     * Valid values are `true`, `false`, and `'auto'`. Defaults to `'auto'`, which will set the secure flag if the page you’re currently accessing
     * is over `https://`. `true` will always set the flag, regardless of protocol and `false` will never automatically set the flag.
     *
     * ```php
     * ->useSecureCookies(true)
     * ```
     *
     * @group Security
     *
     * @see $useSecureCookies
     * @since 4.2.0
     */
    #[Deprecated(message: 'in 6.0.0. Configure `session.secure` or set `SESSION_SECURE_COOKIE` in your environment instead.')]
    public function useSecureCookies(string|bool $value): self
    {
        app()->booting(function () use ($value) {
            Deprecator::log('generalConfig.useSecureCookies', 'Calling useSecureCookies() is deprecated. Configure `session.secure` or set `SESSION_SECURE_COOKIE` in your environment instead.');
            Config::set('session.secure', $value === 'auto' ? null : $value);
        });

        $this->useSecureCookies = $value;

        return $this;
    }

    /**
     * Determines what protocol/schema Craft will use when generating tokenized URLs. If set to `'auto'`, Craft will check the
     * current site’s base URL and the protocol of the current request and if either of them are HTTPS will use `https` in the tokenized URL. If not,
     * will use `http`.
     *
     * If set to `false`, Craft will always use `http`. If set to `true`, then, Craft will always use `https`.
     *
     * ```php
     * ->useSslOnTokenizedUrls(true)
     * ```
     *
     * @group Routing
     *
     * @see $useSslOnTokenizedUrls
     */
    public function useSslOnTokenizedUrls(string|bool $value): self
    {
        $this->useSslOnTokenizedUrls = $value;

        return $this;
    }

    /**
     * The amount of time before a user will get logged out due to inactivity.
     *
     * Set to `0` if you want users to stay logged in as long as their browser is open rather than a predetermined amount of time.
     *
     * See {@see ConfigHelper::durationInSeconds()} for a list of supported value types.
     *
     * ```php
     * // 3 hours
     * ->userSessionDuration(10800)
     * ```
     *
     * @group Session
     *
     * @defaultAlt 1 hour
     *
     * @see $userSessionDuration
     */
    public function userSessionDuration(mixed $value): self
    {
        $this->userSessionDuration = ConfigHelper::durationInSeconds($value);

        return $this;
    }

    /**
     * Whether to grab an exclusive lock on a file when writing to it by using the `LOCK_EX` flag.
     *
     * Some file systems, such as NFS, do not support exclusive file locking.
     *
     * If `null`, Craft will try to detect if the underlying file system supports exclusive file locking and cache the results.
     *
     * ```php
     * ->useFileLocks(false)
     * ```
     *
     * @group System
     *
     * @see $useFileLocks
     * @see https://php.net/manual/en/function.file-put-contents.php
     */
    public function useFileLocks(?bool $value): self
    {
        $this->useFileLocks = $value;

        return $this;
    }

    /**
     * The amount of time a user verification code can be used before expiring.
     *
     * See {@see ConfigHelper::durationInSeconds()} for a list of supported value types.
     *
     * ```php
     * // 1 hour
     * ->verificationCodeDuration(3600)
     * ```
     *
     * @group Security
     *
     * @defaultAlt 1 day
     *
     * @see $verificationCodeDuration
     */
    public function verificationCodeDuration(mixed $value): self
    {
        $this->verificationCodeDuration = ConfigHelper::durationInSeconds($value);

        return $this;
    }

    /**
     * The URI or URL that Craft should use for email verification links on the front end.
     *
     * See {@see ConfigHelper::localizedValue()} for a list of supported value types.
     *
     * ```php
     * ->verifyEmailPath('verify-email')
     * ```
     *
     * @group Routing
     *
     * @see $verifyEmailPath
     * @see getVerifyEmailPath()
     */
    public function verifyEmailPath(mixed $value): self
    {
        $this->verifyEmailPath = $value;

        return $this;
    }

    /**
     * The URI that users without access to the control panel should be redirected to after verifying a new email address.
     *
     * See {@see ConfigHelper::localizedValue()} for a list of supported value types.
     *
     * ```php
     * ->verifyEmailSuccessPath('verified-email')
     * ```
     *
     * @group Routing
     *
     * @see $verifyEmailSuccessPath
     * @see getVerifyEmailSuccessPath()
     */
    public function verifyEmailSuccessPath(mixed $value): self
    {
        $this->verifyEmailSuccessPath = $value;

        return $this;
    }

    /**
     * Returns the localized Activate Account Success Path value.
     *
     * @param  string|null  $siteHandle  The site handle the value should be defined for. Defaults to the current site.
     *
     * @see activateAccountSuccessPath
     */
    public function getActivateAccountSuccessPath(?string $siteHandle = null): string
    {
        $path = ConfigHelper::localizedValue($this->activateAccountSuccessPath, $siteHandle);

        return is_string($path) ? trim($path, '/') : $path;
    }

    /**
     * Returns the localized Verify Email Path value.
     *
     * @param  string|null  $siteHandle  The site handle the value should be defined for. Defaults to the current site.
     *
     * @see verifyEmailPath
     */
    public function getVerifyEmailPath(?string $siteHandle = null): string
    {
        $path = ConfigHelper::localizedValue($this->verifyEmailPath, $siteHandle);

        return is_string($path) ? trim($path, '/') : $path;
    }

    /**
     * Returns the localized Verify Email Success Path value.
     *
     * @param  string|null  $siteHandle  The site handle the value should be defined for. Defaults to the current site.
     *
     * @see verifyEmailSuccessPath
     */
    public function getVerifyEmailSuccessPath(?string $siteHandle = null): string
    {
        $path = ConfigHelper::localizedValue($this->verifyEmailSuccessPath, $siteHandle);

        return is_string($path) ? trim($path, '/') : $path;
    }

    /**
     * Returns the localized Invalid User Token Path value.
     *
     * @param  string|null  $siteHandle  The site handle the value should be defined for. Defaults to the current site.
     *
     * @see invalidUserTokenPath
     */
    public function getInvalidUserTokenPath(?string $siteHandle = null): ?string
    {
        $path = ConfigHelper::localizedValue($this->invalidUserTokenPath, $siteHandle);

        return is_string($path) ? trim($path, '/') : $path;
    }

    /**
     * Returns the localized Login Path value.
     *
     * @param  string|null  $siteHandle  The site handle the value should be defined for. Defaults to the current site.
     *
     * @see loginPath
     */
    public function getLoginPath(?string $siteHandle = null): mixed
    {
        $path = ConfigHelper::localizedValue($this->loginPath, $siteHandle);

        return is_string($path) ? trim($path, '/') : $path;
    }

    /**
     * Returns the localized Logout Path value.
     *
     * @param  string|null  $siteHandle  The site handle the value should be defined for. Defaults to the current site.
     *
     * @see logoutPath
     */
    public function getLogoutPath(?string $siteHandle = null): mixed
    {
        $path = ConfigHelper::localizedValue($this->logoutPath, $siteHandle);

        return is_string($path) ? trim($path, '/') : $path;
    }

    /**
     * Returns the localized Post-Login Redirect path for the control panel.
     *
     * @see postCpLoginRedirect
     */
    public function getPostCpLoginRedirect(): string
    {
        return ConfigHelper::localizedValue($this->postCpLoginRedirect);
    }

    /**
     * Returns the localized Post-Login Redirect path.
     *
     * @param  string|null  $siteHandle  The site handle the value should be defined for. Defaults to the current site.
     *
     * @see postLoginRedirect
     */
    public function getPostLoginRedirect(?string $siteHandle = null): string
    {
        return ConfigHelper::localizedValue($this->postLoginRedirect, $siteHandle);
    }

    /**
     * Returns the localized Post-Logout Redirect path.
     *
     * @param  string|null  $siteHandle  The site handle the value should be defined for. Defaults to the current site.
     *
     * @see postLogoutRedirect
     */
    public function getPostLogoutRedirect(?string $siteHandle = null): string
    {
        return ConfigHelper::localizedValue($this->postLogoutRedirect, $siteHandle);
    }

    /**
     * Returns the remembered user session duration as a [[\DateInterval]] object, if it’s set.
     *
     * @since 4.2.1
     */
    public function getRememberedUserSessionDuration(): ?DateInterval
    {
        return $this->_rememberedUserSessionDuration ?: null;
    }

    /**
     * Returns the localized Set Password Path value.
     *
     * @param  string|null  $siteHandle  The site handle the value should be defined for. Defaults to the current site.
     *
     * @see setPasswordPath
     */
    public function getSetPasswordPath(?string $siteHandle = null): string
    {
        $path = ConfigHelper::localizedValue($this->setPasswordPath, $siteHandle);

        return is_string($path) ? trim($path, '/') : $path;
    }

    /**
     * Returns the localized Set Password Request Path value.
     *
     * @param  string|null  $siteHandle  The site handle the value should be defined for. Defaults to the current site.
     *
     * @see setPasswordRequestPath
     */
    public function getSetPasswordRequestPath(?string $siteHandle = null): ?string
    {
        $path = ConfigHelper::localizedValue($this->setPasswordRequestPath, $siteHandle);

        return is_string($path) ? trim($path, '/') : $path;
    }

    /**
     * Returns the localized Set Password Success Path value.
     *
     * @param  string|null  $siteHandle  The site handle the value should be defined for. Defaults to the current site.
     *
     * @see setPasswordSuccessPath
     */
    public function getSetPasswordSuccessPath(?string $siteHandle = null): string
    {
        $path = ConfigHelper::localizedValue($this->setPasswordSuccessPath, $siteHandle);

        return is_string($path) ? trim($path, '/') : $path;
    }

    /**
     * Returns whether the DB should be backed up before running new migrations.
     */
    public function getBackupOnUpdate(): bool
    {
        return $this->backupOnUpdate && $this->backupCommand !== false;
    }

    /**
     * Returns the normalized page trigger.
     *
     * @see pageTrigger
     */
    public function getPageTrigger(): string
    {
        $pageTrigger = $this->pageTrigger;

        if ($pageTrigger === '') {
            $pageTrigger = 'p';
        }

        // Is this query string-based pagination?
        if (str_starts_with($pageTrigger, '?')) {
            $pageTrigger = trim($pageTrigger, '?=');

            return '?'.$pageTrigger.'=';
        }

        return $pageTrigger;
    }

    /**
     * Returns the normalized test email addresses.
     */
    public function getTestToEmailAddress(): array
    {
        $to = [];
        if ($this->testToEmailAddress) {
            foreach ((array) $this->testToEmailAddress as $key => $value) {
                if (is_numeric($key)) {
                    $to[$value] = t('Test Recipient');
                } else {
                    $to[$key] = $value;
                }
            }
        }

        return $to;
    }
}
