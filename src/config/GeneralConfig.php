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
use yii\base\BaseObject;
use yii\base\InvalidArgumentException;
use yii\base\InvalidConfigException;
use yii\base\UnknownPropertyException;

/**
 * General config class
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class GeneralConfig extends BaseObject
{
    // Constants
    // =========================================================================

    const IMAGE_DRIVER_AUTO = 'auto';
    const IMAGE_DRIVER_GD = 'gd';
    const IMAGE_DRIVER_IMAGICK = 'imagick';

    // Properties
    // =========================================================================

    /**
     * @var string The URI segment Craft should look for when determining if the current request should first be routed to a
     * controller action.
     */
    public $actionTrigger = 'actions';
    /**
     * @var mixed The URI Craft should use upon successfully activating a user. Note that this only affects front-end site
     * requests.
     * See [[ConfigHelper::localizedValue()]] for a list of supported value types.
     * @see getActivateAccountSuccessPath()
     */
    public $activateAccountSuccessPath = '';
    /**
     * @var bool Determines whether auto-generated URLs should have trailing slashes.
     */
    public $addTrailingSlashesToUrls = false;
    /**
     * @var array Any custom Yii [aliases](http://www.yiiframework.com/doc-2.0/guide-concept-aliases.html) that should be defined for every request.
     */
    public $aliases = [];
    /**
     * @var bool Whether to allow Craft and plugin updates in the Control Panel, and plugin installation in the Plugin Store.
     */
    public $allowUpdates = true;
    /**
     * @var string[] List of file extensions that Craft will allow when a user is uploading files.
     * @see extraAllowedFileExtensions
     */
    public $allowedFileExtensions = ['7z', 'aiff', 'asf', 'avi', 'bmp', 'csv', 'doc', 'docx', 'fla', 'flv', 'gif', 'gz', 'gzip', 'htm', 'html', 'jp2', 'jpeg', 'jpg', 'jpx', 'js', 'm2t', 'mid', 'mov', 'mp3', 'mp4', 'm4a', 'm4v', 'mpc', 'mpeg', 'mpg', 'ods', 'odt', 'ogg', 'ogv', 'pdf', 'png', 'potx', 'pps', 'ppsm', 'ppsx', 'ppt', 'pptm', 'pptx', 'ppz', 'pxd', 'qt', 'ram', 'rar', 'rm', 'rmi', 'rmvb', 'rtf', 'sdc', 'sitd', 'svg', 'swf', 'sxc', 'sxw', 'tar', 'tgz', 'tif', 'tiff', 'txt', 'vob', 'vsd', 'wav', 'webm', 'wma', 'wmv', 'xls', 'xlsx', 'zip'];
    /**
     * @var bool If this is set to true, then a tag name of "Proteines" will also match a tag name of "Protéines". Otherwise,
     * they are treated as the same tag. Note that this
     */
    public $allowSimilarTags = false;
    /**
     * @var bool Whether or not to allow uppercase letters in the slug.
     */
    public $allowUppercaseInSlug = false;
    /**
     * @var bool If set to true, will automatically log the user in after successful account activation.
     */
    public $autoLoginAfterAccountActivation = false;
    /**
     * @var bool Whether Craft should create a database backup before running new migrations.
     * @see backupCommand
     */
    public $backupOnUpdate = true;
    /**
     * @var string|null Craft will use the command line libraries `pg_dump` and `mysqldump` for backing up a database
     * by default. It assumes that those libraries are in the $PATH variable for the user the web server is
     * running as.
     * If you want to use some other library, or want to specify an absolute path to them,
     * or want to specify different parameters than the default, or you want to implement some other backup
     * solution, you can override that behavior here.
     * There are several tokens you can use that Craft will swap out at runtime:
     *     * `{path}` - Swapped with the dynamically generated backup file path.
     *     * `{port}` - Swapped with the current database port.
     *     * `{server}` - Swapped with the current database host name.
     *     * `{user}` - Swapped with the user to connect to the database.
     *     * `{database}` - Swapped with the current database name.
     *     * `{schema}` - Swapped with the current database schema (if any).
     * This can also be set to `false` to disable database backups completely.
     */
    public $backupCommand;
    /**
     * @var string|null Sets the base URL to the CP that Craft should use when generating CP-facing URLs. This will be determined
     * automatically if left blank.
     */
    public $baseCpUrl;
    /**
     * @var int The higher the cost value, the longer it takes to generate a password hash and to verify against it. Therefore,
     * higher cost slows down a brute-force attack.
     * For best protection against brute force attacks, set it to the highest value that is tolerable on production
     * servers.
     * The time taken to compute the hash doubles for every increment by one for this value.
     * For example, if the hash takes 1 second to compute when the value is 14 then the compute time varies as
     * 2^(value - 14) seconds.
     */
    public $blowfishHashCost = 13;
    /**
     * @var bool Whether Craft should cache element queries that fall inside {% cache %} tags.
     */
    public $cacheElementQueries = true;
    /**
     * @var mixed The default length of time Craft will store data, RSS feed, and template caches.
     * If set to `0`, data and RSS feed caches will be stored indefinitely; template caches will be stored for one year.
     * See [[ConfigHelper::durationInSeconds()]] for a list of supported value types.
     */
    public $cacheDuration = 86400;
    /**
     * @var bool If set to true, any uploaded file names will have multi-byte characters (Chinese, Japanese, etc.) stripped
     * and any high-ASCII characters converted to their low ASCII counterparts (i.e. ñ → n).
     */
    public $convertFilenamesToAscii = false;
    /**
     * @var mixed The amount of time a user must wait before re-attempting to log in after their account is locked due to too many
     * failed login attempts.
     * Set to `0` to keep the account locked indefinitely, requiring an admin to manually unlock the account.
     * See [[ConfigHelper::durationInSeconds()]] for a list of supported value types.
     */
    public $cooldownDuration = 300;
    /**
     * @var string The URI segment Craft should look for when determining if the current request should route to the CP rather than
     * the front-end website.
     */
    public $cpTrigger = 'admin';
    /**
     * @var string The name of CSRF token used for CSRF validation if [[enableCsrfProtection]] is set to `true`.
     * @see enableCsrfProtection
     */
    public $csrfTokenName = 'CRAFT_CSRF_TOKEN';
    /**
     * @var array Any custom ASCII character mappings.
     * This array is merged into the default one in StringHelper::asciiCharMap(). The key is the ASCII character to
     * be used for the replacement and the value is an array of non-ASCII characters that the key maps to.
     * For example:
     *     'c'    => ['ç', 'ć', 'č', 'ĉ', 'ċ']
     */
    public $customAsciiCharMappings = [];
    /**
     * @var string Used to set a custom domain on any cookies that Craft creates. Defaults to an empty string, which leaves it
     * up to the browser to determine which domain to use (almost always the current). If you want the cookies to work
     * for all subdomains, for example, you could set this to '.domain.com'.
     */
    public $defaultCookieDomain = '';
    /**
     * @var string|null Defines the default language the Control Panel should get set to if the logged-in user doesn't have a
     * preferred language set.
     */
    public $defaultCpLanguage;
    /**
     * @var mixed The default permission to be set for newly generated directories.
     * If set to null, the permission will be determined by the current environment.
     */
    public $defaultDirMode = 0775;
    /**
     * @var int|null The default permission to be set for newly generated files.
     * If set to null, the permission will be determined by the current environment.
     */
    public $defaultFileMode;
    /**
     * @var int The quality level Craft will use when saving JPG and PNG files. Ranges from 0 (worst quality, smallest file) to
     * 100 (best quality, biggest file).
     */
    public $defaultImageQuality = 82;
    /**
     * @var array The default options that should be applied to each search term.
     * Options include:
     * - `attribute` – The attribute that the term should apply to (e.g. 'title'), if any. (`null` by default)
     * - `exact` – Whether the term must be an exact match (only applies if `attribute` is set). (`false` by default)
     * - `exclude` – Whether search results should *exclude* records with this term. (`false` by default)
     * - `subLeft` – Whether to include keywords that contain the term, with additional characters before it. (`false` by default)
     * - `subRight` – Whether to include keywords that contain the term, with additional characters after it. (`true` by default)
     */
    public $defaultSearchTermOptions = [];
    /**
     * @var string[] The template file extensions Craft will look for when matching a template path to a file on the front end.
     */
    public $defaultTemplateExtensions = ['html', 'twig'];
    /**
     * @var mixed The default amount of time tokens can be used before expiring.
     * See [[ConfigHelper::durationInSeconds()]] for a list of supported value types.
     */
    public $defaultTokenDuration = 86400;
    /**
     * @var int The default day that new users should have set as their “Week Start Day”.
     * This should be set to an int from `0` to `6` where:
     * - `0` represents Sunday
     * - `1` represents Monday
     * - `2` represents Tuesday
     * - `3` represents Wednesday
     * - `4` represents Thursday
     * - `5` represents Friday
     * - `6` represents Saturday
     */
    public $defaultWeekStartDay = 0;
    /**
     * @var bool By default, Craft will require a 'password' field to be submitted on front-end, public
     * user registrations. Setting this to `true` will no longer require it on the initial registration form.
     * If you have email verification enabled, the will set their password once they've clicked on the
     * verification link in the email. If you don't, the only way they can set their password is to go
     * through your "forgot password" workflow.
     */
    public $deferPublicRegistrationPassword = false;
    /**
     * @var bool Determines whether the system is in Dev Mode or not.
     */
    public $devMode = false;
    /**
     * @var bool Whether to use a cookie to persist the CSRF token if [[enableCsrfProtection]] is enabled. If false, the CSRF token
     * will be stored in session under the 'csrfTokenName' config setting name. Note that while storing CSRF tokens in
     * session increases security, it requires starting a session for every page that a CSRF token is need, which may
     * degrade site performance.
     * @see enableCsrfProtection
     */
    public $enableCsrfCookie = true;
    /**
     * @var mixed The amount of time a user’s elevated session will last, which is required for some sensitive actions (e.g. user group/permission assignment).
     * Set to `0` to disable elevated session support.
     * See [[ConfigHelper::durationInSeconds()]] for a list of supported value types.
     */
    public $elevatedSessionDuration = 300;
    /**
     * @var bool Whether to enable CSRF protection via hidden form inputs for all forms submitted via Craft.
     * @see csrfTokenName
     * @see enableCsrfCookie
     */
    public $enableCsrfProtection = true;
    /**
     * @var bool Whether to enable Craft's template `{% cache %}` tag on a global basis.
     * @see http://craftcms.com/docs/templating/cache
     */
    public $enableTemplateCaching = true;
    /**
     * @var string The prefix that should be prepended to HTTP error status codes when determining the path to look for an error’s
     * template.
     * If set to `'_'`, then your site’s 404 template would live at `templates/_404.html`, for example.
     */
    public $errorTemplatePrefix = '';
    /**
     * @var string[]|null List of file extensions that will be merged into the [[allowedFileExtensions]] config setting.
     * @see allowedFileExtensions
     */
    public $extraAllowedFileExtensions;
    /**
     * @var string|bool The string to use to separate words when uploading Assets. If set to `false`, spaces will be left alone.
     */
    public $filenameWordSeparator = '-';
    /**
     * @var bool Whether transforms be generated before loading the page.
     */
    public $generateTransformsBeforePageLoad = false;
    /**
     * @var mixed By default Craft will auto-detect if Imagick is installed and fallback to GD if not. You can explicitly set
     * either 'imagick' or 'gd' here to override that behavior.
     */
    public $imageDriver = self::IMAGE_DRIVER_AUTO;
    /**
     * @var string[] The template filenames Craft will look for within a directory to represent the directory’s “index” template when
     * matching a template path to a file on the front end.
     */
    public $indexTemplateFilenames = ['index'];
    /**
     * @var mixed The amount of time to track invalid login attempts for a user, for determining if Craft should lock an account.
     * See [[ConfigHelper::durationInSeconds()]] for a list of supported value types.
     */
    public $invalidLoginWindowDuration = 3600;
    /**
     * @var mixed The URI Craft should redirect to when user token validation fails. A token is used on things like setting and
     * resetting user account passwords. Note that this only affects front-end site requests.
     * See [[ConfigHelper::localizedValue()]] for a list of supported value types.
     * @see getInvalidUserTokenPath()
     */
    public $invalidUserTokenPath = '';
    /**
     * @var string[]|null List of headers where proxies store the real client IP.
     * See [[\yii\web\Request::ipHeaders]] for more details.
     * If not set, the default [[\craft\web\Request::ipHeaders]] value will be used.
     */
    public $ipHeaders;
    /**
     * @var bool|null Whether the site is currently online or not. If set to `true` or `false`, it will take precedence over the
     * System Status setting in Settings → General.
     */
    public $isSystemOn;
    /**
     * @var bool If set to true, the auto-generated slugs for an entry will strip any multi-byte characters (Chinese, Japanese, etc.)
     * and attempt to convert any high-ASCII to their low ASCII counterparts (i.e. ñ → n).
     * Note that this only affects the JavaScript auto-generated slugs and they still can be manually entered in the slug.
     */
    public $limitAutoSlugsToAscii = false;
    /**
     * @var mixed The URI Craft should use for user login. Note that this only affects front-end site requests.
     * See [[ConfigHelper::localizedValue()]] for a list of supported value types.
     * @see getLoginPath()
     */
    public $loginPath = 'login';
    /**
     * @var mixed The URI Craft should use for user logout. Note that this only affects front-end site requests.
     * See [[ConfigHelper::localizedValue()]] for a list of supported value types.
     * @see getLogoutPath()
     */
    public $logoutPath = 'logout';
    /**
     * @var int The maximum dimension size to use when caching images from external sources to use in transforms. Set to 0 to
     * never cache them.
     */
    public $maxCachedCloudImageSize = 2000;
    /**
     * @var int The number of invalid login attempts Craft will allow within the specified duration before the account gets
     * locked.
     */
    public $maxInvalidLogins = 5;
    /**
     * @var int The highest number Craft will tack onto a slug in order to make it unique before giving up and throwing an error.
     */
    public $maxSlugIncrement = 100;
    /**
     * @var int|string The maximum upload file size allowed.
     * See [[ConfigHelper::sizeInBytes()]] for a list of supported value types.
     */
    public $maxUploadFileSize = 16777216;
    /**
     * @var bool Whether generated URLs should omit 'index.php', e.g. http://domain.com/path as opposed to showing it,
     * e.g. http://domain.com/index.php/path
     * This can only be possible if your server is configured to redirect would-be 404's to index.php, for example, with
     * the redirect found in the 'htaccess' file that came with Craft:
     *     RewriteEngine On
     *     RewriteCond %{REQUEST_FILENAME} !-f
     *     RewriteCond %{REQUEST_FILENAME} !-d
     *     RewriteRule (.+) /index.php?p=$1 [QSA,L]
     */
    public $omitScriptNameInUrls = false;
    /**
     * @var bool If set to true and Imagick is used, Craft will take advantage of Imagick's advanced options to reduce the final
     * image size without losing quality significantly.
     * @see imageDriver
     */
    public $optimizeImageFilesize = true;
    /**
     * @var string The string preceding a number which Craft will look for when determining if the current request is for a
     * particular page in a paginated list of pages.
     */
    public $pageTrigger = 'p';
    /**
     * @var string The query string param that Craft will check when determining the request's path.
     */
    public $pathParam = 'p';
    /**
     * @var string The maximum amount of memory Craft will try to reserve during memory intensive operations such as zipping,
     * unzipping and updating. Defaults to an empty string, which means it will use as much memory as it possibly can.
     * See http://php.net/manual/en/faq.using.php#faq.using.shorthandbytes for a list of acceptable values.
     */
    public $phpMaxMemoryLimit = '';
    /**
     * @var string The name of the PHP session cookie.
     * @see https://php.net/manual/en/function.session-name.php
     */
    public $phpSessionName = 'CraftSessionId';
    /**
     * @var mixed The path that users should be redirected to after logging in from the Control Panel.
     * This setting will also come into effect if the user visits the CP’s Login page (/admin/login)
     * or the CP’s root URL (/admin) when they are already logged in.
     * See [[ConfigHelper::localizedValue()]] for a list of supported value types.
     * @see getPostCpLoginRedirect()
     */
    public $postCpLoginRedirect = 'dashboard';
    /**
     * @var mixed The path that users should be redirected to after logging in from the front-end site.
     * This setting will also come into effect if the user visits the Login page (as specified by the loginPath config
     * setting) when they are already logged in.
     * See [[ConfigHelper::localizedValue()]] for a list of supported value types.
     * @see getPostLoginRedirect()
     */
    public $postLoginRedirect = '';
    /**
     * @var mixed The path that users should be redirected to after logging out from the front-end site.
     * See [[ConfigHelper::localizedValue()]] for a list of supported value types.
     * @see getPostLogoutRedirect()
     */
    public $postLogoutRedirect = '';
    /**
     * @var bool Whether the EXIF data should be preserved when manipulating images.
     * Setting this to false will reduce the image size a little bit, but all EXIF data will be cleared.
     * This will only have effect if Imagick is in use.
     */
    public $preserveExifData = false;
    /**
     * @var bool Whether the embedded Image Color Profile (ICC) should be preserved when manipulating images.
     * Setting this to false will reduce the image size a little bit, but on some Imagick versions can cause images to be saved with
     * an incorrect gamma value, which causes the images to become very dark. This will only have effect if Imagick is in use.
     */
    public $preserveImageColorProfiles = true;
    /**
     * @var string The template path segment prefix that should be used to identify "private" templates -- templates that aren't
     * directly accessible via a matching URL.
     *
     * Set to an empty value to disable public template routing.
     */
    public $privateTemplateTrigger = '_';
    /**
     * @var bool When set to `false` and you go through the "forgot password" workflow on the control panel login page, for example,
     * you get distinct messages saying if the username/email didn't exist or the email was successfully sent and to check
     * your email for further instructions. This can allow for username/email enumeration based on the response. If set
     * `true`, you will always get a successful response even if there was an error making it difficult to enumerate users.
     */
    public $preventUserEnumeration = false;
    /**
     * @var mixed The amount of time to wait before Craft purges pending users from the system that have not activated.
     * Note that any content assigned to a pending user will be deleted as well when the given time interval passes.
     * Set to `0` to disable this feature.
     * See [[ConfigHelper::durationInSeconds()]] for a list of supported value types.
     */
    public $purgePendingUsersDuration = 0;
    /**
     * @var mixed The amount of time Craft will remember a username and pre-populate it on the CP login page.
     * Set to `0` to disable this feature altogether.
     * See [[ConfigHelper::durationInSeconds()]] for a list of supported value types.
     */
    public $rememberUsernameDuration = 31536000;
    /**
     * @var mixed The amount of time a user stays logged if “Remember Me” is checked on the login page.
     * Set to `0` to disable the “Remember Me” feature altogether.
     * See [[ConfigHelper::durationInSeconds()]] for a list of supported value types.
     */
    public $rememberedUserSessionDuration = 1209600;
    /**
     * @var bool Whether Craft should require a matching user agent string when restoring a user session from a cookie.
     */
    public $requireMatchingUserAgentForSession = true;
    /**
     * @var bool Whether Craft should require the existence of a user agent string and IP address when creating a new user
     * session.
     */
    public $requireUserAgentAndIpForSession = true;
    /**
     * @var string The path to the root directory that should store published CP resources.
     */
    public $resourceBasePath = '@webroot/cpresources';
    /**
     * @var string The URL to the root directory that should store published CP resources.
     */
    public $resourceBaseUrl = '@web/cpresources';
    /**
     * @var string|null Craft will use the command line libraries `psql` and `mysql` for restoring a database
     * by default. It assumes that those libraries are in the $PATH variable for the user the web server is
     * running as.
     * If you want to use some other library, or want to specify an absolute path to them,
     * or want to specify different parameters than the default, or you want to implement some other restoration
     * solution, you can override that behavior here.
     * There are several tokens you can use that Craft will swap out at runtime:
     *     * `{path}` - Swapped with the dynamically generated backup file path.
     *     * `{port}` - Swapped with the current database port.
     *     * `{server}` - Swapped with the current database host name.
     *     * `{user}` - Swapped with the user to connect to the database.
     *     * `{database}` - Swapped with the current database name.
     *     * `{schema}` - Swapped with the current database schema (if any).
     * This can also be set to `false` to disable database restores completely.
     */
    public $restoreCommand;
    /**
     * @var bool Whether Craft should rotate images according to their EXIF data on upload.
     */
    public $rotateImagesOnUploadByExifData = true;
    /**
     * @var bool Whether Craft should run pending queue jobs automatically over HTTP requests.
     * This setting should be disabled for servers running Win32, or with Apache’s mod_deflate/mod_gzip installed,
     * where PHP’s [flush()](http://php.net/manual/en/function.flush.php) method won’t work.
     * If disabled, an alternate queue runner *must* be set up separately.
     */
    public $runQueueAutomatically = true;
    /**
     * @var bool Whether Craft should sanitize uploaded SVG files and strip out potential malicious looking content.
     * This should definitely be enabled if you are accepting SVG uploads from untrusted sources.
     */
    public $sanitizeSvgUploads = true;
    /**
     * @var string A private, random, cryptographically-secure key that is used for hashing and encrypting
     * data in [[\craft\services\Security]].
     * This value should be the same across all environments. Note that if this key ever changes, any data that
     * was encrypted with it will be inaccessible.
     */
    public $securityKey;
    /**
     * @var bool Whether the X-Powered-By header should be sent on each request, helping clients identify that the site is powered by Craft.
     */
    public $sendPoweredByHeader = true;
    /**
     * @var mixed The URI Craft should use for user password resetting. Note that this only affects front-end site requests.
     * See [[ConfigHelper::localizedValue()]] for a list of supported value types.
     * @see getSetPasswordPath()
     */
    public $setPasswordPath = 'setpassword';
    /**
     * @var mixed The URI Craft should use upon successfully setting a users’s password. Note that this only affects front-end site
     * requests.
     * See [[ConfigHelper::localizedValue()]] for a list of supported value types.
     * @see getSetPasswordSuccessPath()
     */
    public $setPasswordSuccessPath = '';
    /**
     * @var bool Whether or not to show beta Craft updates from the updates page in the control panel. It is highly recommended
     * that you do not use beta releases of Craft in a production environment.
     */
    public $showBetaUpdates = false;
    /**
     * @var string|string[] The site name(s). If set, it will take precedence over the Name settings in Settings → Sites → [Site Name].
     * This can be set to a string, which will override the primary site’s name only, or an array with site handles used as the keys.
     */
    public $siteName;
    /**
     * @var string|string[] The base URL to the site(s). If set, it will take precedence over the Base URL settings in Settings → Sites → [Site Name].
     * This can be set to a string, which will override the primary site’s base URL only, or an array with site handles used as the keys.
     * The URL(s) must begin with either `http://`, `https://`, `//` (protocol-relative), or an [[aliases|alias]].
     *
     * ```php
     * 'siteUrl' => [
     *     'siteA' => 'https://site-a.com/',
     *     'siteB' => 'https://site-b.com/',
     * ],
     */
    public $siteUrl;
    /**
     * @var string The character(s) that should be used to separate words in slugs.
     */
    public $slugWordSeparator = '-';
    /**
     * @var array|null Lists of headers that are, by default, subject to the trusted host configuration.
     * See [[\yii\web\Request::secureHeaders]] for more details.
     * If not set, the default [[\yii\web\Request::secureHeaders]] value will be used.
     */
    public $secureHeaders;
    /**
     * @var array|null list of headers to check for determining whether the connection is made via HTTPS.
     * See [[\yii\web\Request::secureProtocolHeaders]] for more details.
     * If not set, the default [[\yii\web\Request::secureProtocolHeaders]] value will be used.
     */
    public $secureProtocolHeaders;
    /**
     * @var bool Controls whether or not to show or hide any Twig template runtime errors that occur on the site in the browser.
     * If it is set to `true`, the errors will still be logged to Craft’s log files.
     */
    public $suppressTemplateErrors = false;
    /**
     * @var string|array|null Configures Craft to send all system emails to a single email address, or an array of email addresses for testing
     * purposes.
     * By default the recipient name(s) will be “Test Recipient”, but you can customize that by setting the value with the format `['email@address.com' => 'Name']`.
     */
    public $testToEmailAddress;
    /**
     * @var string|null The timezone of the site. If set, it will take precedence over the Timezone setting in Settings → General.
     * This can be set to one of PHP’s supported timezones (http://php.net/manual/en/timezones.php).
     */
    public $timezone;
    /**
     * @var bool Tells Craft whether GIF files should be transformed or cleansed. Defaults to true.
     */
    public $transformGifs = true;
    /**
     * @var bool Tells Craft whether to surround all translatable strings with “@” symbols, to help find any strings that are not
     * being run through Craft::t() or the |translate filter.
     */
    public $translationDebugOutput = false;
    /**
     * @var string The name of the 'token' query string parameter.
     */
    public $tokenParam = 'token';
    /**
     * @var array The configuration for trusted security-related headers.
     * See [[\yii\web\Request::trustedHosts]] for more details.
     * By default, all hosts are trusted.
     */
    public $trustedHosts = ['any'];
    /**
     * @var bool Tells Craft whether to use compressed JavaScript files whenever possible, to cut down on page load times.
     */
    public $useCompressedJs = true;
    /**
     * @var bool If set to true, Craft will use a user's email address in place of their username and remove username UI from the
     * control panel.
     */
    public $useEmailAsUsername = false;
    /**
     * @var bool Whether Craft should specify the path using PATH_INFO or as a query string parameter when generating URLs.
     * Note that this setting only takes effect if [[omitScriptNameInUrls]] is set to false.
     */
    public $usePathInfo = false;
    /**
     * @var bool|string Determines whether Craft will set the "secure" flag when saving cookies when using `Craft::cookieConfig() to create a cookie`.
     * Valid values are `true`, `false`, and `'auto'`. Defaults to `'auto'`, which will set the secure flag if the page
     * you're currently accessing is over `https://`. `true` will always set the flag, regardless of protocol and `false`
     * will never automatically set the flag.
     */
    public $useSecureCookies = 'auto';
    /**
     * @var bool|string Determines what protocol/schema Craft will use when generating tokenized URLs. If set to 'auto',
     * Craft will check the siteUrl and the protocol of the current request and if either of them are https
     * will use https in the tokenized URL. If not, will use http.
     * If set to `false`, the Craft will always use http. If set to `true`, then, Craft will always use `https`.
     */
    public $useSslOnTokenizedUrls = 'auto';
    /**
     * @var mixed The amount of time a user stays logged in.
     * Set to `0` if you want users to stay logged in as long as their browser is open rather than a predetermined
     * amount of time.
     * See [[ConfigHelper::durationInSeconds()]] for a list of supported value types.
     */
    public $userSessionDuration = 3600;
    /**
     * @var bool|null Whether to grab an exclusive lock on a file when writing to it by using the LOCK_EX flag.
     * Some file systems, such as NFS, do not support exclusive file locking.
     * If not set to `true` or `false`, Craft will automatically try to detect if the underlying file system supports exclusive file
     * locking and cache the results.
     * @see http://php.net/manual/en/function.file-put-contents.php
     */
    public $useFileLocks;
    /**
     * @var bool Whether Craft should use XSendFile to serve files when possible.
     */
    public $useXSendFile = false;
    /**
     * @var mixed The amount of time a user verification code can be used before expiring.
     * See [[ConfigHelper::durationInSeconds()]] for a list of supported value types.
     */
    public $verificationCodeDuration = 86400;

    /**
     * @var array Stores any custom config settings
     */
    private $_customSettings = [];

    // Public Methods
    // =========================================================================

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
        ];

        foreach ($renamedSettings as $old => $new) {
            if (array_key_exists($old, $config)) {
                Craft::$app->getDeprecator()->log($old, "The {$old} config setting has been renamed to {$new}.");
                $config[$new] = $config[$old];
                unset($config[$old]);
            }
        }

        // Check for environmentVariables, but don't actually rename it in case a template is referencing it
        if (array_key_exists('environmentVariables', $config)) {
            Craft::$app->getDeprecator()->log('environmentVariables', "The environmentVariables config setting has been renamed to aliases.");
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
        $this->rememberUsernameDuration = ConfigHelper::durationInSeconds($this->rememberUsernameDuration);
        $this->rememberedUserSessionDuration = ConfigHelper::durationInSeconds($this->rememberedUserSessionDuration);
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

            if (!in_array($this->defaultCpLanguage, Craft::$app->getI18n()->getAppLocaleIds())) {
                throw new InvalidConfigException('Unsupported language: '.$this->defaultCpLanguage);
            }
        }
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
     * @return string
     * @see loginPath
     */
    public function getLoginPath(string $siteHandle = null): string
    {
        return ConfigHelper::localizedValue($this->loginPath, $siteHandle);
    }

    /**
     * Returns the localized Logout Path value.
     *
     * @param string|null $siteHandle The site handle the value should be defined for. Defaults to the current site.
     * @return string
     * @see logoutPath
     */
    public function getLogoutPath(string $siteHandle = null): string
    {
        return ConfigHelper::localizedValue($this->logoutPath, $siteHandle);
    }

    /**
     * Returns the localized Post-CP Login Redirect path.
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
}
