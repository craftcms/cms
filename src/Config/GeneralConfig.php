<?php

declare(strict_types=1);

namespace CraftCms\Cms\Config;

use Closure;
use CraftCms\Cms\Auth\Enums\CpAuthPath;
use CraftCms\Cms\Support\Attributes\EnvName;
use CraftCms\Cms\Support\Config as ConfigHelper;
use CraftCms\Cms\Support\Env;
use CraftCms\Cms\Support\Facades\I18N;
use CraftCms\Cms\Support\PHP;
use Illuminate\Support\Traits\Conditionable;
use InvalidArgumentException;
use Override;
use RuntimeException;

class GeneralConfig extends BaseConfig
{
    use Conditionable;

    public const string IMAGE_DRIVER_AUTO = 'auto';

    public const string IMAGE_DRIVER_GD = 'gd';

    public const string IMAGE_DRIVER_IMAGICK = 'imagick';

    public const string IMAGE_DRIVER_VIPS = 'vips';

    public const string CAMEL_CASE = 'camel';

    public const string PASCAL_CASE = 'pascal';

    public const string SNAKE_CASE = 'snake';

    #[Override]
    /** @var array<string, string> */
    protected static array $renamedSettings = [];

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
     * @var mixed The maximum age of activity events before garbage collection deletes them.
     *
     * Set to `0` to retain activity indefinitely.
     *
     * See {@see ConfigHelper::durationInSeconds()} for a list of supported value types.
     *
     * ::: code
     * ```php Static Config
     * ->activityRetentionDuration('P90D')
     * ```
     * ```shell Environment Override
     * CRAFT_ACTIVITY_RETENTION_DURATION=P90D
     * ```
     * :::
     *
     * @group Garbage Collection
     *
     * @defaultAlt Unlimited
     */
    public mixed $activityRetentionDuration = 0;

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
     * @var string|null The server path to the directory where Craft should store compiled Twig templates.
     *
     * If this is set to `null`, Craft will store compiled templates in `storage/runtime/compiled_templates`.
     *
     * ::: code
     * ```php Static Config
     * ->compiledTemplatesPath('@storage/runtime/templates')
     * ```
     * ```shell Environment Override
     * CRAFT_COMPILED_TEMPLATES_PATH=@storage/runtime/templates
     * ```
     * :::
     *
     * @group Environment
     *
     * @since 6.0.0
     */
    public ?string $compiledTemplatesPath = null;

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
     * When set to `null` (default), Craft will run `mysqldump`, `pg_dump`, or `sqlite3`, provided that those libraries are in the `$PATH`
     * variable for the system user running the web server.
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
     * ->buildId(\CraftCms\Cms\Support\Env::get('GIT_SHA'))
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
     * @var list<array{0: string, 1?: array<string, mixed>}> List of additional HTML tags that should be included in the `<head>` of control panel pages.
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
     * @var string|null Site icon
     *
     * Square SVG file recommended. The logo will be displayed at 32px by 32px.
     *
     * @group System
     */
    public ?string $cpIconUrl = null;

    /**
     * @var string|null Login page logo
     *
     * SVG file recommended. The logo will be displayed at 288px wide.
     *
     * @group System
     */
    public ?string $cpLogoUrl = null;

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
     * @var array<string, bool|string> The default options that should be applied to each search term.
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
     * ->defaultTemplateExtensions(['twig', 'html', 'blade.php', 'txt'])
     * ```
     * ```shell Environment Override
     * CRAFT_DEFAULT_TEMPLATE_EXTENSIONS=twig,html,blade.php,txt
     * ```
     * :::
     *
     * @group System
     */
    public array $defaultTemplateExtensions = ['twig', 'html', 'blade.php'];

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
     * @var array<string, array<string, mixed>> List of additional file kinds Craft should support. This array will get merged with the one defined in
     *                                          `\craft\helpers\Assets::_buildFileKinds()`.
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
     * @var bool Whether to automatically generate IDE helper files for custom fields.
     *
     * When enabled, Craft will generate PHPDoc metadata files in the IDE helper path
     * whenever field layouts are saved. This improves IDE autocompletion for custom fields.
     *
     * ::: code
     * ```php Static Config
     * ->ideHelperEnabled(true)
     * ```
     * ```shell Environment Override
     * CRAFT_IDE_HELPER_ENABLED=true
     * ```
     * :::
     *
     * @group System
     *
     * @since 6.0.0
     */
    public bool $ideHelperEnabled = true;

    /**
     * @var string The path where IDE helper files should be written, relative to the project root.
     *
     * ::: code
     * ```php Static Config
     * ->ideHelperPath('vendor/_craft')
     * ```
     * ```shell Environment Override
     * CRAFT_IDE_HELPER_PATH=vendor/_craft
     * ```
     * :::
     *
     * @group System
     *
     * @since 6.0.0
     */
    public string $ideHelperPath = 'vendor/_craft';

    /**
     * @var mixed The image driver Craft should use to cleanse and transform images. By default Craft will use ImageMagick if it’s installed,
     *            followed by libvips and GD. You can explicitly set `'imagick'`, `'vips'`, or `'gd'` to override that behavior.
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
     * @var array<string, float|int|string> An array containing the selectable image aspect ratios for the image editor. The array must be in the format
     *                                      of `label` => `ratio`, where ratio must be a float or a string. For string values, only values of “none” and “original” are allowed.
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
     * @var mixed The URI Craft should use for user login on the front end.
     *
     * This is set to `false` by default which disables front-end login.
     *
     * See {@see ConfigHelper::localizedValue()} for a list of supported value types.
     *
     * ::: code
     * ```php Static Config
     * ->loginPath('login')
     * ```
     * ```shell Environment Override
     * CRAFT_LOGIN_PATH=login
     * ```
     * :::
     *
     * @see getLoginPath()
     *
     * @group Routing
     */
    public mixed $loginPath = false;

    /**
     * @var array<int|string, array<string, mixed>|string> The OAuth providers that should be available for login.
     *
     * Each provider should be keyed by its handle, and may be configured as either a driver string shorthand
     * or an array with the following supported keys. Numeric entries are also allowed for simple driver-only providers.
     *
     * - `driver` *(required)*: A registered Socialite driver name, or a Socialite-compatible provider class name.
     * - `clientId`: The provider client ID. Required for provider classes, optional for named drivers if already defined in `config/services.php`.
     * - `clientSecret`: The provider client secret. Required for provider classes, optional for named drivers if already defined in `config/services.php`.
     * - `enabled`: Whether the provider should be available. Defaults to `true`.
     * - `name`: A human-friendly provider name.
     * - `label`: The rendered button label.
     * - `icon`: The control panel brand icon name.
     * - `scopes`: Additional Socialite scopes.
     * - `with`: Additional Socialite request parameters.
     * - `stateless`: Whether the provider should bypass Socialite state checks.
     * - `groups`: User group IDs, UIDs, or handles to assign to new users.
     * - `createsUsers`: Whether the provider may create new users when no existing account can be matched. Defaults to the public registration setting when `null` or omitted.
     * - `activatesUsers`: Whether matched or newly-created users should be activated automatically.
     * - `trustsEmail`: Whether the provider is trusted to verify email ownership, allowing first-time matches to existing users by email. Defaults to `false`.
     * - `identityResolver`: A custom identity resolver class implementing `\CraftCms\Cms\Auth\OAuth\Contracts\ResolvesOAuthIdentity`.
     * - `userResolver`: A custom linked-user resolver class implementing `\CraftCms\Cms\Auth\OAuth\Contracts\ResolvesOAuthUser`.
     * - `userPopulator`: A custom user populator class implementing `\CraftCms\Cms\Auth\OAuth\Contracts\PopulatesOAuthUser`.
     * - `groupResolver`: A custom initial user-group resolver class implementing `\CraftCms\Cms\Auth\OAuth\Contracts\ResolvesOAuthUserGroups`, returning group IDs, UIDs, or handles.
     * - `buttonRenderer`: A custom login-button renderer class implementing `\CraftCms\Cms\Auth\OAuth\Contracts\RendersOAuthButton`.
     *
     * ::: code
     * ```php Static Config
     * ->oauthProviders([
     *     'github',
     *     'google' => [
     *         'driver' => 'google',
     *         'clientId' => '$GOOGLE_CLIENT_ID',
     *         'clientSecret' => '$GOOGLE_CLIENT_SECRET',
     *     ],
     * ])
     * ```
     * :::
     *
     * @group Users
     */
    public array $oauthProviders = [];

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
     * @var bool Whether Craft should favor reduced file sizes over lossless encoding where supported.
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
     * @var string The query string param name Craft should use for paginated requests.
     *
     * | Example Value | Example URI |
     * | --- | --- |
     * | `page` | `/news?page=5` |
     * | `p` | `/news?p=5` |
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
    public string $pageTrigger = 'page';

    /**
     * @var string The path within the `templates` folder where element partial templates will live.
     *
     * Partial templates are used to render elements when calling [[\craft\elements\db\ElementQuery::render()]],
     * [[\CraftCms\Cms\Element\ElementCollection::render()]], or [[\craft\base\Element::render()]].
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
     * Setting this to `true` will prevent Craft from transforming CMYK images to sRGB when the active image driver supports CMYK.
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
     * This will only have an effect if the active image driver supports preserving EXIF data.
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
     * Setting this to `false` will reduce the image size a little bit, but can cause images to be saved with an incorrect gamma value.
     * This will only have an effect if the active image driver supports embedded color profiles.
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
    public bool $preventUserEnumeration = true;

    /**
     * @var array<string, mixed> Custom [iFrame Resizer options](http://davidjbradshaw.github.io/iframe-resizer/#options) that should be used for preview iframes.
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
     * @var bool Whether SVG thumbnails should be rasterized.
     *
     * This requires SVG decoding support from the active image driver.
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
     * By default Craft will run `mysql`, `psql`, or `sqlite3`, provided those libraries are in the `$PATH` variable for the user the web
     * server is running as.
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
     * If disabled, a Laravel queue worker *must* be set up separately, usually as a long-running worker
     * managed by Supervisor or systemd:
     *
     * ```shell
     * php /path/to/project/craft queue:work
     * ```
     *
     * If a long-running worker isn’t possible, schedule Laravel to process the queue every minute by running
     * `queue:work --once` directly, or by running Laravel’s scheduler every minute and scheduling the same
     * queue work there:
     *
     * ```cron
     * * * * * php /path/to/project/craft queue:work --once
     * ```
     *
     * Include any custom queue names from `queueName()` and `lowPriorityQueueName()` in the worker’s `--queue`
     * option.
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
     * @var string The name of the queue that Craft jobs should be sent to.
     *
     * ::: code
     * ```php Static Config
     * ->queueName('craft')
     * ```
     * ```shell Environment Override
     * CRAFT_QUEUE_NAME=craft
     * ```
     * :::
     *
     * @group System
     */
    public string $queueName = 'default';

    /**
     * @var string The name of the queue that Craft lower priority jobs should be sent to.
     *             By default, all jobs go to the same queue. Make sure to update `trackedQueueNames`
     *             as well if you change this setting.
     *
     * ::: code
     * ```php Static Config
     * ->lowPriorityQueueName('craft-low-prio')
     * ->trackedQueueNames(['craft', 'craft-low-prio'])
     * ```
     * ```shell Environment Override
     * CRAFT_LOW_PRIORITY_QUEUE_NAME=craft-low-prio
     * ```
     * :::
     *
     * @group System
     */
    public string $lowPriorityQueueName = 'default';

    /**
     * @var array<string> The queue names that should have their job progress tracked.
     *
     * By default, only jobs on the `craft` queue are tracked. Add additional queue names
     * if you want to track progress for jobs on other queues.
     *
     * ::: code
     * ```php Static Config
     * ->trackedQueueNames(['craft', 'default'])
     * ```
     * :::
     *
     * @group System
     */
    public array $trackedQueueNames = ['default'];

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
     * @var list<string>|null Lists of headers that are, by default, subject to the trusted host configuration.
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
     * @var array<string, list<string>>|null List of headers to check for determining whether the connection is made via HTTPS.
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
    public mixed $setPasswordPath = CpAuthPath::SetPassword->value;

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
     * @var string|null The filesystem target that should be used for storing temporary asset uploads.
     *
     *                  This can be set to a Craft filesystem handle, a Laravel disk in the format `disk:<name>`,
     *                  or a plain legacy value (resolved as Craft FS first, then Laravel disk).
     *
     *                  A local temp folder will be used by default.
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
     */
    public ?string $timezone = null;

    /**
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
     * @var array<int|string, string|list<string>> The configuration for trusted security-related headers.
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

    public function __construct()
    {
        // (Re-)normalize everything.
        $this
            // IDE Helper defaults to the same value as devMode
            ->ideHelperEnabled(Env::parseBoolean('$APP_DEBUG') ?? false)
            // file extensions
            ->allowedFileExtensions($this->allowedFileExtensions)
            ->extraAllowedFileExtensions($this->extraAllowedFileExtensions)
            // durations
            ->activityRetentionDuration($this->activityRetentionDuration)
            ->cacheDuration($this->cacheDuration)
            ->cooldownDuration($this->cooldownDuration)
            ->defaultTokenDuration($this->defaultTokenDuration)
            ->invalidLoginWindowDuration($this->invalidLoginWindowDuration)
            ->previewTokenDuration($this->previewTokenDuration)
            ->purgePendingUsersDuration($this->purgePendingUsersDuration)
            ->purgeUnsavedDraftsDuration($this->purgeUnsavedDraftsDuration)
            ->rememberUsernameDuration($this->rememberUsernameDuration)
            ->softDeleteDuration($this->softDeleteDuration)
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
     * The maximum age of activity events before garbage collection deletes them.
     *
     * Set to `0` to retain activity indefinitely.
     *
     * See {@see ConfigHelper::durationInSeconds()} for a list of supported value types.
     *
     * ```php
     * ->activityRetentionDuration('P90D')
     * ```
     *
     * @group Garbage Collection
     *
     * @defaultAlt Unlimited
     *
     * @see $activityRetentionDuration
     */
    public function activityRetentionDuration(mixed $value): self
    {
        $duration = ConfigHelper::durationInSeconds($value);

        if ($duration < 0) {
            throw new InvalidArgumentException('Activity retention duration must be zero or greater.');
        }

        $this->activityRetentionDuration = $duration;

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
     * The server path to the directory where Craft should store compiled Twig templates.
     *
     * If this is set to `null`, Craft will store compiled templates in `storage/runtime/compiled_templates`.
     *
     * ```php
     * ->compiledTemplatesPath('@storage/runtime/templates')
     * ```
     *
     * @group Environment
     *
     * @see $compiledTemplatesPath
     * @since 6.0.0
     */
    public function compiledTemplatesPath(?string $value): self
    {
        $this->compiledTemplatesPath = $value;

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
     * ->buildId(\CraftCms\Cms\Support\Env::get('GIT_SHA'))
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
     * Site icon
     *
     * Square SVG file recommended. The logo will be displayed at 32px by 32px.
     *
     * @group System
     *
     * @see $cpIconUrl
     */
    public function cpIconUrl(?string $value): self
    {
        $this->cpIconUrl = $value;

        return $this;
    }

    /**
     * Login page logo
     *
     * SVG file recommended. The logo will be displayed at 288px wide.
     *
     * @group System
     *
     * @see $cpLogoUrl
     */
    public function cpLogoUrl(?string $value): self
    {
        $this->cpLogoUrl = $value;

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
     * The template file extensions Craft will look for when matching a template path to a file on the front end.
     *
     * ```php
     * ->defaultTemplateExtensions(['twig', 'html', 'blade.php', 'txt'])
     * ```
     *
     * @group System
     *
     * @param  list<string>  $value
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
     * @param  array<string, array<string, mixed>>  $value
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
     * The image driver Craft should use to cleanse and transform images. By default Craft will use ImageMagick if it's installed,
     * followed by libvips and GD. You can explicitly set `'imagick'`, `'vips'`, or `'gd'` to override that behavior.
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
     * @param  array<string, float|int|string>  $value
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
     *
     * @param  array<string, array{aliasOf: string, displayName?: string}>  $value
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
     * The OAuth providers that should be available for login.
     *
     * @group Users
     *
     * @param  array<int|string, array<string, mixed>|string>  $value
     *
     * @see $oauthProviders
     */
    public function oauthProviders(array $value): self
    {
        $this->oauthProviders = $value;

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
     * Whether Craft should favor reduced file sizes over lossless encoding where supported.
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
     * Sets the query string param name Craft should use for paginated requests.
     *
     * | Example Value | Example URI |
     * | --- | --- |
     * | `page` | `/news?page=5` |
     * | `p` | `/news?p=5` |
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
        $this->pageTrigger = $this->normalizePageTrigger($value);

        return $this;
    }

    /**
     * The path within the `templates` folder where element partial templates will live.
     *
     * Partial templates are used to render elements when calling [[\craft\elements\db\ElementQuery::render()]],
     * [[\CraftCms\Cms\Element\ElementCollection::render()]], or [[\craft\base\Element::render()]].
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
     * Setting this to `true` will prevent Craft from transforming CMYK images to sRGB when the active image driver supports CMYK.
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
     * This will only have an effect if the active image driver supports preserving EXIF data.
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
     * Setting this to `false` will reduce the image size a little bit, but can cause images to be saved with an incorrect gamma value.
     * This will only have an effect if the active image driver supports embedded color profiles.
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
     * @param  array<string, mixed>  $value
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
     * @see $previewTokenDuration
     */
    public function previewTokenDuration(mixed $value): self
    {
        $this->previewTokenDuration = $value !== null ? ConfigHelper::durationInSeconds($value) : null;

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
     * This requires SVG decoding support from the active image driver.
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
     * If disabled, a Laravel queue worker *must* be set up separately, usually as a long-running worker
     * managed by Supervisor or systemd:
     *
     * ```shell
     * php /path/to/project/craft queue:work
     * ```
     *
     * If a long-running worker isn’t possible, schedule Laravel to process the queue every minute by running
     * `queue:work --once` directly, or by running Laravel’s scheduler every minute and scheduling the same
     * queue work there:
     *
     * ```cron
     * * * * * php /path/to/project/craft queue:work --once
     * ```
     *
     * Include any custom queue names from `queueName()` and `lowPriorityQueueName()` in the worker’s `--queue`
     * option.
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
     * The name of the queue that Craft jobs should be sent to.
     *
     * ```php
     * ->queueName('craft')
     * ```
     *
     * @group System
     *
     * @see $queueName
     */
    public function queueName(string $value): self
    {
        $this->queueName = $value;

        return $this;
    }

    /**
     * The name of the queue that Craft jobs should be sent to.
     *
     * ```php
     * ->lowPriorityQueueName('craft-low-prio')
     * ```
     *
     * @group System
     *
     * @see $lowPriorityQueueName
     */
    public function lowPriorityQueueName(string $value): self
    {
        $this->lowPriorityQueueName = $value;

        return $this;
    }

    /**
     * The queue names that should have their job progress tracked.
     *
     * ```php
     * ->trackedQueueNames(['craft', 'default'])
     * ```
     *
     * @group System
     *
     * @param  list<string>  $value
     *
     * @see $trackedQueueNames
     */
    public function trackedQueueNames(array $value): self
    {
        $this->trackedQueueNames = $value;

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
     * The filesystem target that should be used for storing temporary asset uploads.
     *
     * This can be set to a Craft filesystem handle, a Laravel disk in the format `disk:<name>`,
     * or a plain legacy value (resolved as Craft FS first, then Laravel disk).
     *
     * A local temp folder will be used by default.
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
     * Returns the normalized page trigger in query-string form.
     *
     * @see pageTrigger
     */
    public function getPageTrigger(): string
    {
        return '?'.$this->getPageTriggerParam().'=';
    }

    public function getPageTriggerParam(): string
    {
        return $this->normalizePageTrigger($this->pageTrigger);
    }

    private function normalizePageTrigger(string $pageTrigger): string
    {
        $pageTrigger = trim($pageTrigger);

        if ($pageTrigger === '') {
            return 'page';
        }

        $pageTrigger = trim($pageTrigger, '?=');
        $pageTrigger = rtrim($pageTrigger, '/');

        return $pageTrigger !== '' ? $pageTrigger : 'page';
    }
}
