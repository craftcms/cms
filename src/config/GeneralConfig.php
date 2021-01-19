<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\config;

use Craft;
use craft\helpers\ConfigHelper;
use craft\helpers\Localization;
use craft\helpers\StringHelper;
use craft\services\Config;
use yii\base\BaseObject;
use yii\base\InvalidArgumentException;
use yii\base\InvalidConfigException;
use yii\base\UnknownPropertyException;

/**
 * General config class
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 */
class GeneralConfig extends BaseObject
{
    const IMAGE_DRIVER_AUTO = 'auto';
    const IMAGE_DRIVER_GD = 'gd';
    const IMAGE_DRIVER_IMAGICK = 'imagick';

    /**
     * @var string The URI segment Craft should look for when determining if the current request should be routed to a controller action.
     * @group Routing
     */
    public $actionTrigger = 'actions';

    /**
     * @var mixed The URI that users without access to the control panel should be redirected to after activating their account.
     *
     * See [[ConfigHelper::localizedValue()]] for a list of supported value types.
     *
     * @see getActivateAccountSuccessPath()
     * @group Routing
     */
    public $activateAccountSuccessPath = '';

    /**
     * @var bool Whether auto-generated URLs should have trailing slashes.
     * @group Routing
     */
    public $addTrailingSlashesToUrls = false;

    /**
     * @var array Any custom Yii [aliases](https://www.yiiframework.com/doc/guide/2.0/en/concept-aliases) that should be defined for every request.
     * @group Environment
     */
    public $aliases = [];

    /**
     * @var bool Whether admins should be allowed to make administrative changes to the system.
     *
     * If this is disabled, the Settings and Plugin Store sections will be hidden, the Craft edition and Craft/plugin versions will be locked,
     * and the project config will become read-only.
     *
     * Therefore you should only disable this in production environments when you have a deployment workflow that runs `composer install`
     * automatically on deploy.
     *
     * ::: warning
     * Don’t disable this setting until **all** environments have been updated to Craft 3.1.0 or later.
     * :::
     *
     * @since 3.1.0
     * @group System
     */
    public $allowAdminChanges = true;

    /**
     * @var string[]|false|null The Ajax origins that should be allowed to access the GraphQL API, if enabled.
     *
     * If this is set to an array, then `graphql/api` requests will only include the current request’s [[\yii\web\Request::getOrigin()|origin]]
     * in the `Access-Control-Allow-Origin` response header if it’s listed here.
     *
     * If this is set to `false`, then the `Access-Control-Allow-Origin` response header will never be sent.
     *
     * @since 3.5.0
     * @group GraphQL
     */
    public $allowedGraphqlOrigins;

    /**
     * @var bool Whether Craft should allow system and plugin updates in the control panel, and plugin installation from the Plugin Store.
     *
     * This setting will automatically be disabled if <config3:allowAdminChanges> is disabled.
     *
     * @group System
     */
    public $allowUpdates = true;

    /**
     * @var string[] The file extensions Craft should allow when a user is uploading files.
     * @see extraAllowedFileExtensions
     * @group Assets
     */
    public $allowedFileExtensions = [
        '7z',
        'aiff',
        'asf',
        'avi',
        'bmp',
        'csv',
        'doc',
        'docx',
        'fla',
        'flv',
        'gif',
        'gz',
        'gzip',
        'htm',
        'html',
        'jp2',
        'jpeg',
        'jpg',
        'jpx',
        'js',
        'json',
        'm2t',
        'm4a',
        'm4v',
        'mid',
        'mov',
        'mp3',
        'mp4',
        'mpc',
        'mpeg',
        'mpg',
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
        'rtf',
        'sdc',
        'sitd',
        'svg',
        'swf',
        'sxc',
        'sxw',
        'tar',
        'tgz',
        'tif',
        'tiff',
        'txt',
        'vob',
        'vsd',
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
     * @group System
     */
    public $allowSimilarTags = false;

    /**
     * @var bool Whether uppercase letters should be allowed in slugs.
     * @group Routing
     */
    public $allowUppercaseInSlug = false;

    /**
     * @var bool Whether users should automatically be logged in after activating their account or resetting their password.
     * @group System
     */
    public $autoLoginAfterAccountActivation = false;

    /**
     * @var bool Whether drafts should be saved automatically as they are edited.
     *
     * Note that drafts *will* be autosaved while Live Preview is open, regardless of this setting.
     *
     * @since 3.5.6
     * @group System
     */
    public $autosaveDrafts = true;

    /**
     * @var bool Whether Craft should create a database backup before applying a new system update.
     * @see backupCommand
     * @group System
     */
    public $backupOnUpdate = true;

    /**
     * @var string|false|null The shell command that Craft should execute to create a database backup.
     *
     * When set to `null` (default), Craft will run `mysqldump` or `pg_dump`, provided that those libraries are in the `$PATH` variable
     * for the system user running the web server.
     *
     * You may provide your own command optionally using several tokens Craft will swap out at runtime:
     *
     * - `{path}` - the target backup file path
     * - `{port}` - the current database port
     * - `{server}` - the current database host name
     * - `{user}` - the user to connect to the database
     * - `{database}` - the current database name
     * - `{schema}` - the current database schema (if any)
     *
     * This can also be set to `false` to disable database backups completely.
     *
     * @group Environment
     */
    public $backupCommand;

    /**
     * @var string|null The base URL Craft should use when generating control panel URLs.
     *
     * It will be determined automatically if left blank.
     *
     * ::: tip
     * The base control panel URL should **not** include the [control panel trigger word](config3:cpTrigger) (e.g. `/admin`).
     * :::
     *
     * @group Routing
     */
    public $baseCpUrl;

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
     * @group Security
     */
    public $blowfishHashCost = 13;

    /**
     * @var string|null The server path to an image file that should be sent when responding to an image request with a
     * 404 status code.
     *
     * This can be set to an aliased path such as `@webroot/assets/404.svg`.
     * @since 3.5.0
     * @group Image Handling
     */
    public $brokenImagePath;

    /**
     * @var mixed The default length of time Craft will store data, RSS feed, and template caches.
     *
     * If set to `0`, data and RSS feed caches will be stored indefinitely; template caches will be stored for one year.
     *
     * See [[ConfigHelper::durationInSeconds()]] for a list of supported value types.
     *
     * @group System
     * @defaultAlt 1 day
     */
    public $cacheDuration = 86400;

    /**
     * @var bool Whether uploaded filenames with non-ASCII characters should be converted to ASCII (i.e. `ñ` → `n`).
     *
     * ::: tip
     * You can run `php craft utils/ascii-filenames` in your terminal to apply ASCII filenames to all existing assets.
     * :::
     *
     * @group Assets
     */
    public $convertFilenamesToAscii = false;

    /**
     * @var mixed The amount of time a user must wait before re-attempting to log in after their account is locked due to too many
     * failed login attempts.
     *
     * Set to `0` to keep the account locked indefinitely, requiring an admin to manually unlock the account.
     *
     * See [[ConfigHelper::durationInSeconds()]] for a list of supported value types.
     *
     * @group Security
     * @defaultAlt 5 minutes
     */
    public $cooldownDuration = 300;

    /**
     * @var array List of additional HTML tags that should be included in the `<head>` of control panel pages.
     *
     * Each tag can be specified as an array of the tag name and its attributes.
     *
     * For example, you can give the control panel a custom favicon (etc.) like this:
     *
     * ```php
     * 'cpHeadTags' => [
     *     // Traditional favicon
     *     ['link', ['rel' => 'icon', 'href' => '/icons/favicon.ico']],
     *     // Scalable favicon for browsers that support them
     *     ['link', ['rel' => 'icon', 'type' => 'image/svg+xml', 'sizes' => 'any', 'href' => '/icons/favicon.svg']],
     *     // Touch icon for mobile devices
     *     ['link', ['rel' => 'apple-touch-icon', 'sizes' => '180x180', 'href' => '/icons/touch-icon.svg']],
     *     // Pinned tab icon for Safari
     *     ['link', ['rel' => 'mask-icon', 'href' => '/icons/mask-icon.svg', 'color' => '#663399']],
     * ],
     * ```
     *
     * @since 3.5.0
     * @group System
     */
    public $cpHeadTags = [];

    /**
     * @var string|null The URI segment Craft should look for when determining if the current request should route to the control panel rather than
     * the front-end website.
     *
     * This can be set to `null` if you have a dedicated host name for the control panel (e.g. `cms.example.com`), or you are running Craft in
     * [Headless Mode](config3:headlessMode). If you do that, you will need to ensure that the control panel is being served from its own webroot
     * directory on your server, with an `index.php` file that defines the `CRAFT_CP` PHP constant.
     *
     * ```php
     * define('CRAFT_CP', true);
     * ```
     *
     * Alternatively, you can set the <config3:baseCpUrl> config setting, but then you will run the risk of losing access to portions of your
     * control panel due to URI conflicts with actual folders/files in your main webroot.
     *
     * (For example, if you have an `assets/` folder, that would conflict with the `/assets` page in the control panel.)
     *
     * @group Routing
     */
    public $cpTrigger = 'admin';

    /**
     * @var string The name of CSRF token used for CSRF validation if <config3:enableCsrfProtection> is set to `true`.
     * @see enableCsrfProtection
     * @group Security
     */
    public $csrfTokenName = 'CRAFT_CSRF_TOKEN';

    /**
     * @var array Any custom ASCII character mappings.
     *
     * This array is merged into the default one in StringHelper::asciiCharMap(). The key is the ASCII character to be used for the replacement
     * and the value is an array of non-ASCII characters that the key maps to.
     * ---
     * ```php
     * 'customAsciiCharMappings' => [
     *     'c' => ['ç', 'ć', 'č', 'ĉ', 'ċ'],
     * ],
     * ```
     *
     * @deprecated in 3.0.10. Any corrections to ASCII char mappings should be submitted to [Stringy](https://github.com/voku/Stringy).
     * @group System
     */
    public $customAsciiCharMappings = [];

    /**
     * @var string The domain that cookies generated by Craft should be created for. If blank, it will be left up to the browser to determine
     * which domain to use (almost always the current). If you want the cookies to work for all subdomains, for example, you could
     * set this to `'.domain.com'`.
     *
     * @group Environment
     */
    public $defaultCookieDomain = '';

    /**
     * @var string|null The default language the control panel should use for users who haven’t set a preferred language yet,
     * as well as for console requests.
     * @group System
     * @todo Rename to `defaultLanguage` in Craft 4, since it also determines the language for console requests
     */
    public $defaultCpLanguage;

    /**
     * @var string|null The default locale the control panel should use for date/number formatting, for users who haven’t set
     * a preferred language or formatting locale.
     *
     * If this is `null`, the <config3:defaultCpLanguage> config setting will determine which locale is used for date/number formatting by default.
     *
     * @since 3.5.0
     * @group System
     */
    public $defaultCpLocale;

    /**
     * @var mixed The default permission to be set for newly-generated directories.
     *
     * If set to `null`, the permission will be determined by the current environment.
     *
     * @group System
     */
    public $defaultDirMode = 0775;

    /**
     * @var int|null The default permission to be set for newly-generated files.
     *
     * If set to `null`, the permission will be determined by the current environment.
     *
     * @group System
     */
    public $defaultFileMode;

    /**
     * @var int The quality level Craft will use when saving JPG and PNG files. Ranges from 1 (worst quality, smallest file) to
     * 100 (best quality, biggest file).
     * @group Image Handling
     */
    public $defaultImageQuality = 82;

    /**
     * @var array The default options that should be applied to each search term.
     *
     * Options include:
     *
     * - `attribute` – The attribute that the term should apply to (e.g. 'title'), if any. (`null` by default)
     * - `exact` – Whether the term must be an exact match (only applies if `attribute` is set). (`false` by default)
     * - `exclude` – Whether search results should *exclude* records with this term. (`false` by default)
     * - `subLeft` – Whether to include keywords that contain the term, with additional characters before it. (`false` by default)
     * - `subRight` – Whether to include keywords that contain the term, with additional characters after it. (`true` by default)
     *
     * @group System
     */
    public $defaultSearchTermOptions = [];

    /**
     * @var string[] The template file extensions Craft will look for when matching a template path to a file on the front end.
     * @group System
     */
    public $defaultTemplateExtensions = ['html', 'twig'];

    /**
     * @var mixed The default amount of time tokens can be used before expiring.
     *
     * See [[ConfigHelper::durationInSeconds()]] for a list of supported value types.
     *
     * @group Security
     * @defaultAlt 1 day
     */
    public $defaultTokenDuration = 86400;

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
     * @group System
     * @defaultAlt Monday
     */
    public $defaultWeekStartDay = 1;

    /**
     * @var bool By default, Craft will require a ‘password’ field to be submitted on front-end, public user registrations. Setting this to `true`
     * will no longer require it on the initial registration form.
     *
     * If you have email verification enabled, new users will set their password once they’ve clicked on the verification link in the email.
     * If you don’t, the only way they can set their password is to go through your “forgot password” workflow.
     *
     * @group Security
     */
    public $deferPublicRegistrationPassword = false;

    /**
     * @var bool Whether the system should run in [Dev Mode](https://craftcms.com/support/dev-mode).
     * @group System
     */
    public $devMode = false;

    /**
     * @var string[]|string|null Array of plugin handles that should be disabled, regardless of what the project config says.
     *
     * ```php
     * 'dev' => [
     *     'disabledPlugins' => ['webhooks'],
     * ],
     * ```
     *
     * This can also be set to `'*'` to disable **all** plugins.
     *
     * ```php
     * 'dev' => [
     *     'disabledPlugins' => '*',
     * ],
     * ```
     *
     * @since 3.1.9
     * @group System
     */
    public $disabledPlugins;

    /**
     * @var bool Whether front end requests should respond with `X-Robots-Tag: none` HTTP headers, indicating that pages should not be indexed,
     * and links on the page should not be followed, by web crawlers.
     *
     * ::: tip
     * This should be set to `true` for development and staging environments.
     * :::
     *
     * @since 3.5.10
     * @group System
     */
    public $disallowRobots = false;

    /**
     * @var bool Whether front-end web requests should support basic HTTP authentication.
     * @since 3.5.0
     * @group Security
     */
    public $enableBasicHttpAuth = false;

    /**
     * @var bool Whether to use a cookie to persist the CSRF token if <config3:enableCsrfProtection> is enabled. If false, the CSRF token will be
     * stored in session under the `csrfTokenName` config setting name. Note that while storing CSRF tokens in session increases security,
     * it requires starting a session for every page that a CSRF token is needed, which may degrade site performance.
     * @see enableCsrfProtection
     * @group Security
     */
    public $enableCsrfCookie = true;

    /**
     * @var bool Whether the GraphQL API should be enabled.
     *
     * Note that the GraphQL API is only available for Craft Pro.
     *
     * @since 3.3.1
     * @group GraphQL
     */
    public $enableGql = true;

    /**
     * @var mixed The amount of time a user’s elevated session will last, which is required for some sensitive actions (e.g. user group/permission assignment).
     *
     * Set to `0` to disable elevated session support.
     *
     * See [[ConfigHelper::durationInSeconds()]] for a list of supported value types.
     *
     * @group Security
     * @defaultAlt 5 minutes
     */
    public $elevatedSessionDuration = 300;

    /**
     * @var bool Whether to enable CSRF protection via hidden form inputs for all forms submitted via Craft.
     * @see csrfTokenName
     * @see enableCsrfCookie
     * @group Security
     */
    public $enableCsrfProtection = true;

    /**
     * @var bool Whether Craft should cache GraphQL queries.
     *
     * If set to `true`, Craft will cache the results for unique GraphQL queries per access token. The cache is automatically invalidated any time
     * an element is saved, the site structure is updated, or a GraphQL schema is saved.
     *
     * This setting will have no effect if a plugin is using the [[\craft\services\Gql::EVENT_BEFORE_EXECUTE_GQL_QUERY]] event to provide its own
     * caching logic and setting the `result` property.
     *
     * @since 3.3.12
     * @group GraphQL
     */
    public $enableGraphQlCaching = true;

    /**
     * @var bool Whether to enable Craft’s template `{% cache %}` tag on a global basis.
     * @see http://craftcms.com/docs/templating/cache
     * @group System
     */
    public $enableTemplateCaching = true;

    /**
     * @var string The prefix that should be prepended to HTTP error status codes when determining the path to look for an error’s template.
     *
     * If set to `'_'` your site’s 404 template would live at `templates/_404.html`, for example.
     *
     * @group System
     */
    public $errorTemplatePrefix = '';

    /**
     * @var string[]|null List of file extensions that will be merged into the <config3:allowedFileExtensions> config setting.
     * @see allowedFileExtensions
     * @group System
     */
    public $extraAllowedFileExtensions;

    /**
     * @var string[]|null List of extra locale IDs that the application should support, and users should be able to select as their Preferred Language.
     *
     * Only use this setting if your server has the Intl PHP extension, or if you’ve saved the corresponding
     * [locale data](https://github.com/craftcms/locales) into your `config/locales/` folder.
     *
     * @since 3.0.24
     * @group System
     */
    public $extraAppLocales;

    /**
     * @var array List of additional file kinds Craft should support. This array will get merged with the one defined in
     * `\craft\helpers\Assets::_buildFileKinds()`.
     *
     * ```php
     * 'extraFileKinds' => [
     *     // merge .psb into list of Photoshop file kinds
     *     'photoshop' => [
     *         'extensions' => ['psb'],
     *     ],
     *     // register new "Stylesheet" file kind
     *     'stylesheet' => [
     *         'label' => 'Stylesheet',
     *         'extensions' => ['css', 'less', 'pcss', 'sass', 'scss', 'styl'],
     *     ],
     * ],
     * ```
     *
     * ::: tip
     * File extensions listed here won’t immediately be allowed to be uploaded. You will also need to list them with
     * the <config3:extraAllowedFileExtensions> config setting.
     * :::
     *
     * @since 3.0.37
     * @group Assets
     */
    public $extraFileKinds = [];

    /**
     * @var string|bool The string to use to separate words when uploading Assets. If set to `false`, spaces will be left alone.
     * @group Assets
     */
    public $filenameWordSeparator = '-';

    /**
     * @var bool Whether image transforms should be generated before page load.
     * @group Image Handling
     */
    public $generateTransformsBeforePageLoad = false;

    /**
     * @var string Prefix to use for all type names returned by GraphQL.
     * @group GraphQL
     */
    public $gqlTypePrefix = '';

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
     * - The <config3:loginPath>, <config3:logoutPath>, <config3:setPasswordPath>, and <config3:verifyEmailPath> settings will be ignored.
     *
     * ::: tip
     * With Headless Mode enabled, users may only set passwords and verify email addresses via the control panel or controller
     * actions. Be sure to grant “Access the control panel” permission to content editors and administrators that should be able to
     * log into the control panel unless you’re providing your own auth forms.
     * :::
     *
     * @since 3.3.0
     * @group System
     */
    public $headlessMode = false;

    /**
     * @var mixed The image driver Craft should use to cleanse and transform images. By default Craft will use ImageMagick if it’s installed
     * and otherwise fall back to GD. You can explicitly set either `'imagick'` or `'gd'` here to override that behavior.
     * @group Image Handling
     */
    public $imageDriver = self::IMAGE_DRIVER_AUTO;

    /**
     * @var array An array containing the selectable image aspect ratios for the image editor. The array must be in the format
     * of `label` => `ratio`, where ratio must be a float or a string. For string values, only values of “none” and “original” are allowed.
     * @group Image Handling
     */
    public $imageEditorRatios = [
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
     * matching a template path to a file on the front end.
     * @group System
     */
    public $indexTemplateFilenames = ['index'];

    /**
     * @var mixed The amount of time to track invalid login attempts for a user, for determining if Craft should lock an account.
     *
     * See [[ConfigHelper::durationInSeconds()]] for a list of supported value types.
     *
     * @group Security
     * @defaultAlt 5 minutes
     */
    public $invalidLoginWindowDuration = 3600;

    /**
     * @var mixed The URI Craft should redirect to when user token validation fails. A token is used on things like setting and resetting user account
     * passwords. Note that this only affects front-end site requests.
     *
     * See [[ConfigHelper::localizedValue()]] for a list of supported value types.
     *
     * @see getInvalidUserTokenPath()
     * @group Routing
     */
    public $invalidUserTokenPath = '';

    /**
     * @var string[]|null List of headers where proxies store the real client IP.
     *
     * See [[\yii\web\Request::ipHeaders]] for more details.
     *
     * If not set, the default [[\craft\web\Request::ipHeaders]] value will be used.
     *
     * @group System
     */
    public $ipHeaders;

    /**
     * @var bool|null Whether the site is currently live. If set to `true` or `false`, it will take precedence over the System Status setting
     * in Settings → General.
     * @group System
     */
    public $isSystemLive;

    /**
     * @var bool Whether non-ASCII characters in auto-generated slugs should be converted to ASCII (i.e. ñ → n).
     *
     * ::: tip
     * This only affects the JavaScript auto-generated slugs. Non-ASCII characters can still be used in slugs if entered manually.
     * :::
     *
     * @group System
     */
    public $limitAutoSlugsToAscii = false;

    /**
     * @var mixed The URI Craft should use for user login on the front end.
     *
     * This can be set to `false` to disable front-end login.
     *
     * See [[ConfigHelper::localizedValue()]] for a list of supported value types.
     *
     * @see getLoginPath()
     * @group Routing
     */
    public $loginPath = 'login';

    /**
     * @var mixed The URI Craft should use for user logout on the front end.
     *
     * This can be set to `false` to disable front-end logout.
     *
     * See [[ConfigHelper::localizedValue()]] for a list of supported value types.
     *
     * @see getLogoutPath()
     * @group Routing
     */
    public $logoutPath = 'logout';

    /**
     * @var int The maximum dimension size to use when caching images from external sources to use in transforms. Set to `0` to never cache them.
     * @group Image Handling
     */
    public $maxCachedCloudImageSize = 2000;

    /**
     * @var int The number of invalid login attempts Craft will allow within the specified duration before the account gets locked.
     * @group Security
     */
    public $maxInvalidLogins = 5;

    /**
     * @var int|false The number of backups Craft should make before it starts deleting the oldest backups. If set to `false`, Craft will
     * not delete any backups.
     * @group System
     */
    public $maxBackups = 20;

    /**
     * @var int|null The maximum number of revisions that should be stored for each element.
     *
     * Set to `0` if you want to store an unlimited number of revisions.
     *
     * @since 3.2.0
     * @group System
     */
    public $maxRevisions = 50;

    /**
     * @var int The highest number Craft will tack onto a slug in order to make it unique before giving up and throwing an error.
     * @group System
     */
    public $maxSlugIncrement = 100;

    /**
     * @var int|string The maximum upload file size allowed.
     *
     * See [[ConfigHelper::sizeInBytes()]] for a list of supported value types.
     * @group Assets
     * @defaultAlt 16MB
     */
    public $maxUploadFileSize = 16777216;

    /**
     * @var bool Whether generated URLs should omit `index.php` (e.g. `http://domain.com/path` instead of `http://domain.com/index.php/path`)
     *
     * This can only be possible if your server is configured to redirect would-be 404's to `index.php`, for example, with the redirect found
     * in the `.htaccess` file that came with Craft:
     *
     * ```
     * RewriteEngine On
     * RewriteCond %{REQUEST_FILENAME} !-f
     * RewriteCond %{REQUEST_FILENAME} !-d
     * RewriteRule (.+) /index.php?p=$1 [QSA,L]
     * ```
     *
     * @group Routing
     */
    public $omitScriptNameInUrls = false;

    /**
     * @var bool Whether Craft should optimize images for reduced file sizes without noticeably reducing image quality. (Only supported when
     * ImageMagick is used.)
     * @see imageDriver
     * @group Image Handling
     */
    public $optimizeImageFilesize = true;

    /**
     * @var string The string preceding a number which Craft will look for when determining if the current request is for a particular page in
     * a paginated list of pages.
     *
     * Example Value | Example URI
     * ------------- | -----------
     * `p` | `/news/p5`
     * `page` | `/news/page5`
     * `page/` | `/news/page/5`
     * `?page` | `/news?page=5`
     *
     * ::: tip
     * If you want to set this to `?p` (e.g. `/news?p=5`), you’ll also need to change your <config3:pathParam> setting which defaults to `p`.
     * If your server is running Apache, you’ll need to update the redirect code in your `.htaccess` file to match your new `pathParam` value.
     * :::
     *
     * @see getPageTrigger()
     * @group Routing
     */
    public $pageTrigger = 'p';

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
     * @group Routing
     */
    public $pathParam = 'p';

    /**
     * @var string|null The maximum amount of memory Craft will try to reserve during memory-intensive operations such as zipping,
     * unzipping and updating. Defaults to an empty string, which means it will use as much memory as it can.
     *
     * See <http://php.net/manual/en/faq.using.php#faq.using.shorthandbytes> for a list of acceptable values.
     *
     * @group System
     */
    public $phpMaxMemoryLimit;

    /**
     * @var string The name of the PHP session cookie.
     * @see https://php.net/manual/en/function.session-name.php
     * @group Session
     */
    public $phpSessionName = 'CraftSessionId';

    /**
     * @var mixed The path users should be redirected to after logging into the control panel.
     *
     * This setting will also come into effect if a user visits the control panel’s login page (`/admin/login`) or the control panel’s
     * root URL (`/admin`) when they are already logged in.
     *
     * See [[ConfigHelper::localizedValue()]] for a list of supported value types.
     *
     * @see getPostCpLoginRedirect()
     * @group Routing
     */
    public $postCpLoginRedirect = 'dashboard';

    /**
     * @var mixed The path users should be redirected to after logging in from the front-end site.
     *
     * This setting will also come into effect if the user visits the login page (as specified by the <config3:loginPath> config setting) when
     * they are already logged in.
     *
     * See [[ConfigHelper::localizedValue()]] for a list of supported value types.
     *
     * @see getPostLoginRedirect()
     * @group Routing
     */
    public $postLoginRedirect = '';

    /**
     * @var mixed The path that users should be redirected to after logging out from the front-end site.
     *
     * See [[ConfigHelper::localizedValue()]] for a list of supported value types.
     *
     * @see getPostLogoutRedirect()
     * @group Routing
     */
    public $postLogoutRedirect = '';

    /**
     * @var bool Whether CMYK should be preserved as the colorspace when manipulating images.
     *
     * Setting this to `true` will prevent Craft from transforming CMYK images to sRGB, but on some ImageMagick versions it can cause
     * image color distortion. This will only have an effect if ImageMagick is in use.
     *
     * @since 3.0.8
     * @group Image Handling
     */
    public $preserveCmykColorspace = false;

    /**
     * @var bool Whether the EXIF data should be preserved when manipulating and uploading images.
     *
     * Setting this to `true` will result in larger image file sizes.
     *
     * This will only have effect if ImageMagick is in use.
     *
     * @group Image Handling
     */
    public $preserveExifData = false;

    /**
     * @var bool Whether the embedded Image Color Profile (ICC) should be preserved when manipulating images.
     *
     * Setting this to `false` will reduce the image size a little bit, but on some ImageMagick versions can cause images to be saved with
     * an incorrect gamma value, which causes the images to become very dark. This will only have effect if ImageMagick is in use.
     *
     * @group Image Handling
     */
    public $preserveImageColorProfiles = true;

    /**
     * @var string The template path segment prefix that should be used to identify “private” templates, which are templates that are not
     * directly accessible via a matching URL.
     *
     * Set to an empty value to disable public template routing.
     *
     * @group System
     */
    public $privateTemplateTrigger = '_';

    /**
     * @var bool When `true`, Craft will always return a successful response in the “forgot password” flow, making it difficult to enumerate users.
     *
     * When set to `false` and you go through the “forgot password” flow from the control panel login page, you’ll get distinct messages indicating
     * whether the username/email exists and whether an email was sent with further instructions. This can be helpful for the user attempting to
     * log in but allow for username/email enumeration based on the response.
     *
     * @group Security
     */
    public $preventUserEnumeration = false;

    /**
     * @var array Custom [iFrame Resizer options](http://davidjbradshaw.github.io/iframe-resizer/#options) that should be used for preview iframes.
     *
     * ```php
     * 'previewIframeResizerOptions' => [
     *     'autoResize' => false,
     * ],
     * ```
     *
     * @since 3.5.0
     * @group System
     */
    public $previewIframeResizerOptions = [];

    /**
     * @var mixed The amount of time to wait before Craft purges pending users from the system that have not activated.
     *
     * Any content assigned to a pending user will be deleted as well when the given time interval passes.
     *
     * Set to `0` to disable this feature.
     *
     * See [[ConfigHelper::durationInSeconds()]] for a list of supported value types.
     *
     * ::: tip
     * Users will only be purged when [garbage collection](https://craftcms.com/docs/3.x/gc.html) is run.
     * :::
     *
     * @group Garbage Collection
     */
    public $purgePendingUsersDuration = 0;

    /**
     * @var mixed The amount of time to wait before Craft purges stale user sessions from the sessions table in the database.
     *
     * Set to `0` to disable this feature.
     *
     * See [[ConfigHelper::durationInSeconds()]] for a list of supported value types.
     *
     * @since 3.3.0
     * @group Garbage Collection
     * @defaultAlt 90 days
     */
    public $purgeStaleUserSessionDuration = 7776000;

    /**
     * @var mixed The amount of time to wait before Craft purges drafts of new elements that were never formally saved.
     *
     * Set to `0` to disable this feature.
     *
     * See [[ConfigHelper::durationInSeconds()]] for a list of supported value types.
     *
     * @since 3.2.0
     * @group Garbage Collection
     * @defaultAlt 30 days
     */
    public $purgeUnsavedDraftsDuration = 2592000;

    /**
     * @var mixed The amount of time Craft will remember a username and pre-populate it on the control panel’s Login page.
     *
     * Set to `0` to disable this feature altogether.
     *
     * See [[ConfigHelper::durationInSeconds()]] for a list of supported value types.
     *
     * @group Session
     * @defaultAlt 1 year
     */
    public $rememberUsernameDuration = 31536000;

    /**
     * @var mixed The amount of time a user stays logged if “Remember Me” is checked on the login page.
     *
     * Set to `0` to disable the “Remember Me” feature altogether.
     *
     * See [[ConfigHelper::durationInSeconds()]] for a list of supported value types.
     *
     * @group Session
     * @defaultAlt 14 days
     */
    public $rememberedUserSessionDuration = 1209600;

    /**
     * @var bool Whether Craft should require a matching user agent string when restoring a user session from a cookie.
     * @group Session
     */
    public $requireMatchingUserAgentForSession = true;

    /**
     * @var bool Whether Craft should require the existence of a user agent string and IP address when creating a new user session.
     * @group Session
     */
    public $requireUserAgentAndIpForSession = true;

    /**
     * @var string The path to the root directory that should store published control panel resources.
     * @group Environment
     */
    public $resourceBasePath = '@webroot/cpresources';

    /**
     * @var string The URL to the root directory that should store published control panel resources.
     * @group Environment
     */
    public $resourceBaseUrl = '@web/cpresources';

    /**
     * @var string|null The shell command Craft should execute to restore a database backup.
     *
     * By default Craft will run `mysql` or `psql`, provided those libraries are in the `$PATH` variable for the user the web server is running as.
     *
     * There are several tokens you can use that Craft will swap out at runtime:
     *
     * - `{path}` - the backup file path
     * - `{port}` - the current database port
     * - `{server}` - the current database host name
     * - `{user}` - the user to connect to the database
     * - `{database}` - the current database name
     * - `{schema}` - the current database schema (if any)
     *
     * This can also be set to `false` to disable database restores completely.
     *
     * @group Environment
     */
    public $restoreCommand;

    /**
     * @var bool Whether Craft should rotate images according to their EXIF data on upload.
     * @group Image Handling
     */
    public $rotateImagesOnUploadByExifData = true;

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
     * where PHP’s [flush()](http://php.net/manual/en/function.flush.php) method won’t work.
     * :::
     * @group System
     */
    public $runQueueAutomatically = true;

    /**
     * @var string The [SameSite](https://www.owasp.org/index.php/SameSite) value that should be set on Craft cookies, if any.
     *
     * This can be set to `'Lax'`, `'Strict'`, or `null`.
     *
     * ::: tip
     * This setting requires PHP 7.3 or later.
     * :::
     *
     * @since 3.1.33
     * @group System
     */
    public $sameSiteCookieValue = null;

    /**
     * @var bool Whether Craft should sanitize uploaded SVG files and strip out potential malicious-looking content.
     *
     * This should definitely be enabled if you are accepting SVG uploads from untrusted sources.
     *
     * @group Security
     */
    public $sanitizeSvgUploads = true;

    /**
     * @var string A private, random, cryptographically-secure key that is used for hashing and encrypting data in [[\craft\services\Security]].
     *
     * This value should be the same across all environments. If this key ever changes, any data that was encrypted with it will be inaccessible.
     *
     * @group Security
     */
    public $securityKey;

    /**
     * @var bool Whether an `X-Powered-By: Craft CMS` header should be sent, helping services like [BuiltWith](https://builtwith.com/) and
     * [Wappalyzer](https://www.wappalyzer.com/) identify that the site is running on Craft.
     * @group System
     */
    public $sendPoweredByHeader = true;

    /**
     * @var mixed The URI Craft should use for Set Password forms on the front end.
     *
     * See [[ConfigHelper::localizedValue()]] for a list of supported value types.
     *
     * ::: tip
     * You might also want to set <config3:invalidUserTokenPath> in case a user clicks on an expired password reset link.
     * :::
     *
     * @see getSetPasswordPath()
     * @group Routing
     */
    public $setPasswordPath = 'setpassword';

    /**
     * @var mixed The URI to the page where users can request to change their password.
     *
     * See [[ConfigHelper::localizedValue()]] for a list of supported value types.
     *
     * If this is set, Craft will redirect [.well-known/change-password requests](https://w3c.github.io/webappsec-change-password-url/) to this URI.
     *
     * ::: tip
     * You’ll also need to set [setPasswordPath](config3:setPasswordPath), which determines the URI and template path for the Set Password form
     * where the user resets their password after following the link in the Password Reset email.
     * :::
     *
     * @see getSetPasswordRequestPath()
     * @group Routing
     * @since 3.5.14
     */
    public $setPasswordRequestPath;

    /**
     * @var mixed The URI Craft should redirect users to after setting their password from the front end.
     *
     * See [[ConfigHelper::localizedValue()]] for a list of supported value types.
     *
     * @see getSetPasswordSuccessPath()
     * @group Routing
     */
    public $setPasswordSuccessPath = '';

    /**
     * @var string|string[] The site name(s). If set, it will take precedence over the Name settings in Settings → Sites → [Site Name].
     *
     * This can be set to a string, which will override the primary site’s name only, or an array with site handles used as the keys.
     *
     * @group System
     */
    public $siteName;

    /**
     * @var string The query string parameter name that site tokens should be set to.
     * @since 3.5.0
     * @group Routing
     */
    public $siteToken = 'siteToken';

    /**
     * @var string|string[] The base URL to the site(s). If set, it will take precedence over the Base URL settings in Settings → Sites → [Site Name].
     *
     * This can be set to a string, which will override the primary site’s base URL only, or an array with site handles used as the keys.
     *
     * The URL(s) must begin with either `http://`, `https://`, `//` (protocol-relative), or an [alias](config3:aliases).
     *
     * ```php
     * 'siteUrl' => [
     *     'siteA' => 'https://site-a.com/',
     *     'siteB' => 'https://site-b.com/',
     * ],
     * ```
     *
     * @group Routing
     */
    public $siteUrl;

    /**
     * @var string The character(s) that should be used to separate words in slugs.
     * @group System
     */
    public $slugWordSeparator = '-';

    /**
     * @var array|null Lists of headers that are, by default, subject to the trusted host configuration.
     *
     * See [[\yii\web\Request::secureHeaders]] for more details.
     *
     * If not set, the default [[\yii\web\Request::secureHeaders]] value will be used.
     *
     * @group Security
     */
    public $secureHeaders;

    /**
     * @var array|null list of headers to check for determining whether the connection is made via HTTPS.
     *
     * See [[\yii\web\Request::secureProtocolHeaders]] for more details.
     *
     * If not set, the default [[\yii\web\Request::secureProtocolHeaders]] value will be used.
     *
     * @group Security
     */
    public $secureProtocolHeaders;

    /**
     * @var mixed The amount of time before a soft-deleted item will be up for hard-deletion by garbage collection.
     *
     * Set to `0` if you don’t ever want to delete soft-deleted items.
     *
     * See [[ConfigHelper::durationInSeconds()]] for a list of supported value types.
     *
     * @since 3.1.0
     * @group Garbage Collection
     * @defaultAlt 30 days
     */
    public $softDeleteDuration = 2592000;

    /**
     * @var bool Whether user IP addresses should be stored/logged by the system.
     * @since 3.1.0
     * @group Security
     */
    public $storeUserIps = false;

    /**
     * @var bool Whether Twig runtime errors should be suppressed.
     *
     * If it is set to `true`, the errors will still be logged to Craft’s log files.
     *
     * @deprecated in 3.3.0
     * @group System
     */
    public $suppressTemplateErrors = false;

    /**
     * @var string|array|false|null Configures Craft to send all system emails to either a single email address or an array of email addresses
     * for testing purposes.
     *
     * By default, the recipient name(s) will be “Test Recipient”, but you can customize that by setting the value with the format
     * `['email@address.com' => 'Name']`.
     *
     * @group System
     */
    public $testToEmailAddress;

    /**
     * @var string|null The timezone of the site. If set, it will take precedence over the Timezone setting in Settings → General.
     *
     * This can be set to one of PHP’s [supported timezones](http://php.net/manual/en/timezones.php).
     *
     * @group System
     */
    public $timezone;

    /**
     * @var bool Whether GIF files should be cleansed/transformed.
     * @since 3.0.7
     * @group Image Handling
     */
    public $transformGifs = true;

    /**
     * @var bool Whether translated messages should be wrapped in special characters to help find any strings that are not being run through
     * `Craft::t()` or the `|translate` filter.
     * @group System
     */
    public $translationDebugOutput = false;

    /**
     * @var string The query string parameter name that Craft tokens should be set to.
     * @group Routing
     */
    public $tokenParam = 'token';

    /**
     * @var array The configuration for trusted security-related headers.
     *
     * See [[\yii\web\Request::trustedHosts]] for more details.
     *
     * By default, all hosts are trusted.
     *
     * @group Security
     */
    public $trustedHosts = ['any'];

    /**
     * @var bool Whether images should be upscaled if the provided transform size is larger than the image.
     * @since 3.4.0
     * @group Image Handling
     */
    public $upscaleImages = true;

    /**
     * @var bool Whether Craft should include minified JavaScript files whenever possible, and minify JavaScript code passed to
     * [[\craft\web\View::includeJs()]] or `{% js %}` Twig tags.
     * @deprecated in 3.5.0
     */
    public $useCompressedJs = true;

    /**
     * @var bool Whether Craft should set users’ usernames to their email addresses, rather than let them set their username separately.
     *
     * If you enable this setting after user accounts already exist, run this terminal command to update existing usernames:
     *
     * ```bash
     * php craft utils/update-usernames
     * ```
     *
     * @group System
     */
    public $useEmailAsUsername = false;

    /**
     * @var bool Whether [iFrame Resizer options](http://davidjbradshaw.github.io/iframe-resizer/#options) should be used for Live Preview.
     *
     * Using iFrame Resizer makes it possible for Craft to retain the preview’s scroll position between page loads, for cross-origin web pages.
     *
     * It works by setting the height of the iframe to match the height of the inner web page, and the iframe’s container will be scrolled rather
     * than the iframe document itself. This can lead to some unexpected CSS issues, however, because the previewed viewport height will be taller
     * than the visible portion of the iframe.
     *
     * If you have a [decoupled front-end](https://craftcms.com/docs/3.x/entries.html#previewing-decoupled-front-ends), you will need to include
     * [iframeResizer.contentWindow.min.js](https://raw.github.com/davidjbradshaw/iframe-resizer/master/js/iframeResizer.contentWindow.min.js) on your
     * page as well for this to work. You can conditionally include it for only Live Preview requests by checking if the requested URL contains a
     * `x-craft-live-preview` query string parameter.
     *
     * ::: tip
     * You can customize the behavior of iFrame Resizer via the <config3:previewIframeResizerOptions> config setting.
     * :::
     *
     * @since 3.5.5
     * @group System
     */
    public $useIframeResizer = false;

    /**
     * @var bool Whether Craft should specify the path using `PATH_INFO` or as a query string parameter when generating URLs.
     *
     * Note that this setting only takes effect if <config3:omitScriptNameInUrls> is set to `false`.
     *
     * @group Routing
     */
    public $usePathInfo = false;

    /**
     * @var bool|string Whether Craft will set the “secure” flag when saving cookies when using `Craft::cookieConfig()` to create a cookie.
     *
     * Valid values are `true`, `false`, and `'auto'`. Defaults to `'auto'`, which will set the secure flag if the page you’re currently accessing
     * is over `https://`. `true` will always set the flag, regardless of protocol and `false` will never automatically set the flag.
     *
     * @group Security
     */
    public $useSecureCookies = 'auto';

    /**
     * @var bool|string Determines what protocol/schema Craft will use when generating tokenized URLs. If set to `'auto'`, Craft will check the
     * <config3:siteUrl> and the protocol of the current request and if either of them are https will use `https` in the tokenized URL. If not,
     * will use `http`.
     *
     * If set to `false`, Craft will always use `http`. If set to `true`, then, Craft will always use `https`.
     *
     * @group Routing
     */
    public $useSslOnTokenizedUrls = 'auto';

    /**
     * @var mixed The amount of time before a user will get logged out due to inactivity.
     *
     * Set to `0` if you want users to stay logged in as long as their browser is open rather than a predetermined amount of time.
     *
     * See [[ConfigHelper::durationInSeconds()]] for a list of supported value types.
     *
     * @group Session
     * @defaultAlt 1 hour
     */
    public $userSessionDuration = 3600;

    /**
     * @var bool|null Whether to grab an exclusive lock on a file when writing to it by using the `LOCK_EX` flag.
     *
     * Some file systems, such as NFS, do not support exclusive file locking.
     *
     * If not set to `true` or `false`, Craft will try to detect if the underlying file system supports exclusive file locking and cache the results.
     *
     * @see http://php.net/manual/en/function.file-put-contents.php
     * @group System
     */
    public $useFileLocks;

    /**
     * @var bool Whether the project config should be saved to the `config/` folder.
     * @since 3.1.0
     * @deprecated in 3.5.0. Craft now always saves the project config out to the `config/` folder.
     */
    public $useProjectConfigFile = true;

    /**
     * @var mixed The amount of time a user verification code can be used before expiring.
     *
     * See [[ConfigHelper::durationInSeconds()]] for a list of supported value types.
     *
     * @group Security
     * @defaultAlt 1 day
     */
    public $verificationCodeDuration = 86400;

    /**
     * @var mixed The URI Craft should use for email verification links on the front end.
     *
     * See [[ConfigHelper::localizedValue()]] for a list of supported value types.
     *
     * @see getVerifyEmailPath()
     * @since 3.4.0
     * @group Routing
     */
    public $verifyEmailPath = 'verifyemail';

    /**
     * @var mixed The URI that users without access to the control panel should be redirected to after verifying a new email address.
     *
     * See [[ConfigHelper::localizedValue()]] for a list of supported value types.
     *
     * @see getVerifyEmailSuccessPath()
     * @since 3.1.20
     * @group Routing
     */
    public $verifyEmailSuccessPath = '';

    /**
     * @var array Stores any custom config settings
     */
    private $_customSettings = [];

    /**
     * @inheritdoc
     */
    public function __construct(array $config = [])
    {
        // Check for renamed settings
        $renamedSettings = [
            'allowAutoUpdates' => 'allowUpdates',
            'defaultFilePermissions' => 'defaultFileMode',
            'defaultFolderPermissions' => 'defaultDirMode',
            'useWriteFileLock' => 'useFileLocks',
            'backupDbOnUpdate' => 'backupOnUpdate',
            'restoreDbOnUpdateFailure' => 'restoreOnUpdateFailure',
            'activateAccountFailurePath' => 'invalidUserTokenPath',
            'validationKey' => 'securityKey',
            'isSystemOn' => 'isSystemLive',
        ];

        $configFilePath = null;
        foreach ($renamedSettings as $old => $new) {
            if (array_key_exists($old, $config)) {
                $configFilePath = $configFilePath ?? Craft::$app->getConfig()->getConfigFilePath(Config::CATEGORY_GENERAL);
                Craft::$app->getDeprecator()->log($old, "The `{$old}` config setting has been renamed to `{$new}`.", $configFilePath);
                $config[$new] = $config[$old];
                unset($config[$old]);
            }
        }

        // Check for environmentVariables, but don't actually rename it in case a template is referencing it
        if (array_key_exists('environmentVariables', $config)) {
            Craft::$app->getDeprecator()->log('environmentVariables', "The `environmentVariables` config setting has been renamed to `aliases`.");
        }

        parent::__construct($config);
    }

    /**
     * @inheritdoc
     */
    public function __get($name)
    {
        if (array_key_exists($name, $this->_customSettings)) {
            return $this->_customSettings[$name];
        }

        return parent::__get($name);
    }

    /**
     * @inheritdoc
     */
    public function __set($name, $value)
    {
        try {
            parent::__set($name, $value);
        } catch (UnknownPropertyException $e) {
            $this->_customSettings[$name] = $value;
        }
    }

    /**
     * @inheritdoc
     */
    public function __isset($name)
    {
        if (array_key_exists($name, $this->_customSettings)) {
            return $this->_customSettings[$name] !== null;
        }

        return parent::__isset($name);
    }

    /**
     * @inheritdoc
     */
    public function init()
    {
        // Merge extraAllowedFileExtensions into allowedFileExtensions
        if (is_string($this->allowedFileExtensions)) {
            $this->allowedFileExtensions = StringHelper::split($this->allowedFileExtensions);
        }
        if (is_string($this->extraAllowedFileExtensions)) {
            $this->extraAllowedFileExtensions = StringHelper::split($this->extraAllowedFileExtensions);
        }
        if (is_array($this->extraAllowedFileExtensions)) {
            $this->allowedFileExtensions = array_merge($this->allowedFileExtensions, $this->extraAllowedFileExtensions);
            $this->extraAllowedFileExtensions = null;
        }
        $this->allowedFileExtensions = array_map('strtolower', $this->allowedFileExtensions);

        // Normalize time duration settings
        $this->cacheDuration = ConfigHelper::durationInSeconds($this->cacheDuration);
        $this->cooldownDuration = ConfigHelper::durationInSeconds($this->cooldownDuration);
        $this->defaultTokenDuration = ConfigHelper::durationInSeconds($this->defaultTokenDuration);
        $this->elevatedSessionDuration = ConfigHelper::durationInSeconds($this->elevatedSessionDuration);
        $this->invalidLoginWindowDuration = ConfigHelper::durationInSeconds($this->invalidLoginWindowDuration);
        $this->purgePendingUsersDuration = ConfigHelper::durationInSeconds($this->purgePendingUsersDuration);
        $this->purgeUnsavedDraftsDuration = ConfigHelper::durationInSeconds($this->purgeUnsavedDraftsDuration);
        $this->rememberUsernameDuration = ConfigHelper::durationInSeconds($this->rememberUsernameDuration);
        $this->rememberedUserSessionDuration = ConfigHelper::durationInSeconds($this->rememberedUserSessionDuration);
        $this->softDeleteDuration = ConfigHelper::durationInSeconds($this->softDeleteDuration);
        $this->userSessionDuration = ConfigHelper::durationInSeconds($this->userSessionDuration);
        $this->verificationCodeDuration = ConfigHelper::durationInSeconds($this->verificationCodeDuration);

        // Normalize size settings
        $this->maxUploadFileSize = ConfigHelper::sizeInBytes($this->maxUploadFileSize);

        // Normalize the default CP language
        if ($this->defaultCpLanguage !== null) {
            try {
                $this->defaultCpLanguage = Localization::normalizeLanguage($this->defaultCpLanguage);
            } catch (InvalidArgumentException $e) {
                throw new InvalidConfigException($e->getMessage(), 0, $e);
            }
        }

        // Normalize the extra app locales
        if (!empty($this->extraAppLocales)) {
            foreach ($this->extraAppLocales as $i => $localeId) {
                try {
                    $this->extraAppLocales[$i] = Localization::normalizeLanguage($localeId);
                } catch (InvalidArgumentException $e) {
                    throw new InvalidConfigException($e->getMessage(), 0, $e);
                }
            }
        }

        if ($this->suppressTemplateErrors) {
            Craft::$app->getDeprecator()->log('suppressTemplateErrors', "The `suppressTemplateErrors` config setting has been deprecated because it relies on a deprecated Twig feature.");
        }

        // Always use project config files
        $this->useProjectConfigFile = true;
    }

    /**
     * Returns the localized Activate Account Success Path value.
     *
     * @param string|null $siteHandle The site handle the value should be defined for. Defaults to the current site.
     * @return string
     * @see activateAccountSuccessPath
     */
    public function getActivateAccountSuccessPath(string $siteHandle = null): string
    {
        return ConfigHelper::localizedValue($this->activateAccountSuccessPath, $siteHandle);
    }

    /**
     * Returns the localized Verify Email Path value.
     *
     * @param string|null $siteHandle The site handle the value should be defined for. Defaults to the current site.
     * @return string
     * @see verifyEmailPath
     * @since 3.4.0
     */
    public function getVerifyEmailPath(string $siteHandle = null): string
    {
        return ConfigHelper::localizedValue($this->verifyEmailPath, $siteHandle);
    }

    /**
     * Returns the localized Verify Email Success Path value.
     *
     * @param string|null $siteHandle The site handle the value should be defined for. Defaults to the current site.
     * @return string
     * @see verifyEmailSuccessPath
     * @since 3.1.20
     */
    public function getVerifyEmailSuccessPath(string $siteHandle = null): string
    {
        return ConfigHelper::localizedValue($this->verifyEmailSuccessPath, $siteHandle);
    }

    /**
     * Returns the localized Invalid User Token Path value.
     *
     * @param string|null $siteHandle The site handle the value should be defined for. Defaults to the current site.
     * @return string
     * @see invalidUserTokenPath
     */
    public function getInvalidUserTokenPath(string $siteHandle = null): string
    {
        return ConfigHelper::localizedValue($this->invalidUserTokenPath, $siteHandle);
    }

    /**
     * Returns the localized Login Path value.
     *
     * @param string|null $siteHandle The site handle the value should be defined for. Defaults to the current site.
     * @return mixed
     * @see loginPath
     */
    public function getLoginPath(string $siteHandle = null)
    {
        return ConfigHelper::localizedValue($this->loginPath, $siteHandle);
    }

    /**
     * Returns the localized Logout Path value.
     *
     * @param string|null $siteHandle The site handle the value should be defined for. Defaults to the current site.
     * @return mixed
     * @see logoutPath
     */
    public function getLogoutPath(string $siteHandle = null)
    {
        return ConfigHelper::localizedValue($this->logoutPath, $siteHandle);
    }

    /**
     * Returns the localized Post-Login Redirect path for the control panel.
     *
     * @return string
     * @see postCpLoginRedirect
     */
    public function getPostCpLoginRedirect(): string
    {
        return ConfigHelper::localizedValue($this->postCpLoginRedirect, null);
    }

    /**
     * Returns the localized Post-Login Redirect path.
     *
     * @param string|null $siteHandle The site handle the value should be defined for. Defaults to the current site.
     * @return string
     * @see postLoginRedirect
     */
    public function getPostLoginRedirect(string $siteHandle = null): string
    {
        return ConfigHelper::localizedValue($this->postLoginRedirect, $siteHandle);
    }

    /**
     * Returns the localized Post-Logout Redirect path.
     *
     * @param string|null $siteHandle The site handle the value should be defined for. Defaults to the current site.
     * @return string
     * @see postLogoutRedirect
     */
    public function getPostLogoutRedirect(string $siteHandle = null): string
    {
        return ConfigHelper::localizedValue($this->postLogoutRedirect, $siteHandle);
    }

    /**
     * Returns the localized Set Password Path value.
     *
     * @param string|null $siteHandle The site handle the value should be defined for. Defaults to the current site.
     * @return string
     * @see setPasswordPath
     */
    public function getSetPasswordPath(string $siteHandle = null): string
    {
        return ConfigHelper::localizedValue($this->setPasswordPath, $siteHandle);
    }

    /**
     * Returns the localized Set Password Request Path value.
     *
     * @param string|null $siteHandle The site handle the value should be defined for. Defaults to the current site.
     * @return string|null
     * @see setPasswordRequestPath
     * @since 3.5.14
     */
    public function getSetPasswordRequestPath(string $siteHandle = null)
    {
        return ConfigHelper::localizedValue($this->setPasswordRequestPath, $siteHandle);
    }

    /**
     * Returns the localized Set Password Success Path value.
     *
     * @param string|null $siteHandle The site handle the value should be defined for. Defaults to the current site.
     * @return string
     * @see setPasswordSuccessPath
     */
    public function getSetPasswordSuccessPath(string $siteHandle = null): string
    {
        return ConfigHelper::localizedValue($this->setPasswordSuccessPath, $siteHandle);
    }

    /**
     * Returns whether the DB should be backed up before running new migrations.
     *
     * @return bool
     */
    public function getBackupOnUpdate(): bool
    {
        return ($this->backupOnUpdate && $this->backupCommand !== false);
    }

    /**
     * Returns the normalized page trigger.
     *
     * @return string
     * @see pageTrigger
     * @since 3.2.0
     */
    public function getPageTrigger(): string
    {
        $pageTrigger = $this->pageTrigger;

        if (!is_string($pageTrigger) || $pageTrigger === '') {
            $pageTrigger = 'p';
        }

        // Is this query string-based pagination?
        if (strpos($pageTrigger, '?') === 0) {
            $pageTrigger = trim($pageTrigger, '?=');

            // Avoid conflict with the path param
            if ($pageTrigger === $this->pathParam) {
                $pageTrigger = $this->pathParam === 'p' ? 'pg' : 'p';
            }

            return '?' . $pageTrigger . '=';
        }

        return $pageTrigger;
    }

    /**
     * Returns the normalized test email addresses.
     *
     * @return array
     * @since 3.5.0
     */
    public function getTestToEmailAddress(): array
    {
        $to = [];
        if ($this->testToEmailAddress) {
            foreach ((array)$this->testToEmailAddress as $key => $value) {
                if (is_numeric($key)) {
                    $to[$value] = Craft::t('app', 'Test Recipient');
                } else {
                    $to[$key] = $value;
                }
            }
        }
        return $to;
    }
}
