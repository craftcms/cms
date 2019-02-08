# General Config Settings

Craft comes with a bunch of config settings that give you control over various aspects of its behavior.

All config settings should be placed within the `array()` in your `craft/config/general.php` file.

For example, if you want to enable Dev Mode and set Craft to use uncompressed Javascript files, your array would look like this:

```php
return array(
    'devMode' => true,
    'useCompressedJs' => false,
);
```

Here is the definitive list of config settings you can add:

## General

### `appId`

**Accepts**: A string

**Default**: `null`

**Since**: Craft 2.2

The application ID, which is used for things like storing data caches and user sessions. If it’s not set, Craft will automatically generate one based on the server path. Setting it will help avoid the loss of data caches and user sessions when Craft is deployed using a deployment script that will store Craft in an inconsistent location, such as Capistrano.  If you are using a load-balanced environment, make sure you use the same appId value for every server in the cluster.

```php
'appId' => 'lannister',
```

### `cacheDuration`

**Accepts**: A string set to any valid PHP interval specification, or '0' if you wish to cache data indefinitely.

**Default**: `'P1D'` (one day)

**Since**: Craft 1.0

The length of time Craft will store data caches. Also used by the `{% cache %}` template tag if no expiration time is specified as a parameter.

```php
'cacheDuration' => 'P1W',
```

### `cacheMethod`

**Accepts**: `'apc'`, `'db'`, `'eaccelerator'`, `'file'`, `'memcache'`, `'redis'`, `'wincache'`, `'xcache'`, or `'zenddata'`

**Default**: `'file'`

**Since**: Craft 2.0

The method Craft will use to store data caches.

```php
'cacheMethod' => 'memcache',
```

::: tip
Note that this config setting has no effect on the [{% cache %}](templating/cache.md) template tag, which always stores its data in the database.
:::

::: tip
The DB, file, Memcache(d), and Redis cache methods each have their own additional config settings, which must be set in separate config files in the `craft/config/` folder (`dbcache.php`, `filecache.php`, `memcache.php` and `rediscache.php`, respectively). You can find their default values in craft/app/etc/config/defaults/.
:::

### `customAsciiCharMappings`

**Accepts**: An array of key/value pairs mapping character codes to lower ASCII strings

**Default**: `array()` (an empty array)

**Since**: Craft 1.0

Any custom ASCII character mappings.

This array is merged into the default mapping array in <api:Craft\StringHelper::getAsciiCharMap()>.

The keys should be the HTML decimal code equivalent of the character to search for and the value is the ASCII character used for swapping.

For example, the code for `Æ` is `198`. See sites like [Website Builders](https://websitebuilders.com/tools/html-codes/a-z/) to look up additional codes.

```php
'customAsciiCharMappings' => array(
    198 => 'AE',
    216 => 'O',
    197 => 'A',
),
```

### `defaultCookieDomain`

**Accepts**: A string

**Default**: `''`

**Since**: Craft 2.2

The default domain name Craft will use when sending cookies to the browser. If it is left blank, Craft will leave it up to the browser to decide – which will be whatever the current request’s domain name is.

```php
'defaultCookieDomain' => '.example.com',
```

### `defaultSearchTermOptions`

**Accepts**: An array of key/value pairs

**Default**: `array('attribute' => null, 'exact' => false, 'exclude' => false, 'subLeft' => false, 'subRight' => false)`

**Since**: Craft 2.5

The default options Craft will apply to all search terms when [searching for elements](searching.md).

The array can contain the following keys;

* **`attribute`** _(string)_ – The element attribute the search term should apply to (e.g. `'title'`). (Overridden if the search term in the actual query begins with `someAttribute:`.)
* **`exact`** _(boolean)_ – Whether the search term should be an exact match to the attribute’s value. (Only applicable when `attribute` is set, or the search term in the actual query begins with `someAttribute:`.)
* **`exclude`** _(boolean)_ – Whether the search results should _exclude_ results where the search term is a match.
* **`subLeft`** _(boolean)_ – Whether to accept partial matches on keywords that have additional characters before the search term.
* **`subRight`** _(boolean)_ – Whether to accept partial matches on keywords that have additional characters after the search term.

```php
'defaultSearchTermOptions' => array(
    'subLeft' => true,
    'subRight' => true,
),
```

### `devMode`

**Accepts**: `true` or `false`

**Default**: `false`

**Since**: Craft 1.0

Determines whether the system is in Dev Mode or not. (See [What Dev Mode Does](https://craftcms.com/support/dev-mode) for more info.)

```php
'devMode' => true,
```

::: warning
Dev Mode should never be enabled in production environments.
:::

### `defaultWeekStartDay`

**Accepts**: A number from `0` to `6` (where `0` represents Sunday and `6` represents Saturday)

**Default**: `0` (Sunday)

**Since**: Craft 2.5

The number representing the default Week Start Day preference value for new users.

### `environmentVariables`

**Accepts**: An array of key/value string pairs

**Default**: `array()` (an empty array)

**Since**: Craft 1.1

An array of environment-specific variables which can be accessed as tags within URL and Path settings. (See [multi-environment-configs](multi-environment-configs.md) for more info.)

```php
'environmentVariables' => array(
    'baseAssetUrl'  => '//example.com/',
    'baseAssetPath' => './',
),
```

### `isSystemOn`

**Accepts**: `true`, `false`, or `null`

**Default**: `null`

**Since**: Craft 2.1

Overrides the “System Status” setting in Settings → General if set to `true` or `false`.

```php
'isSystemOn' => false,
```

### `logDumpMethod`

**Accepts**: A valid method name, callable, or function.

**Default**: 'var_export'

**Since**: Craft 2.3

A callable or function which will be used to dump context information. This setting will get passed directly to [CLogFilter::$dumper](https://www.yiiframework.com/doc/api/1.1/CLogFilter#dumper-detail).

```php
'logDumpMethod' => 'print_r',
```

### `overridePhpSessionLocation`

**Accepts**: `true`, `false`, `'auto'`, or the path to a custom session save path

**Default**: `false`

**Since**: Craft 1.0

Determines whether Craft should override PHP’s session storage location to your `craft/storage/` folder, or save session files in a custom location.

When set to `true`, Craft will override the location; `false` will tell Craft to leave the location alone and let PHP store the session where it was configured to.

When set to `'auto'`, Craft will check the default session location to see if it contains “://”, indicating that it might be stored with Memcache or the like. If it does, Craft will leave it alone; otherwise Craft will override it.

```php
'overridePhpSessionLocation' => 'tcp://127.0.0.1:1234',
```

::: tip
If you are saving PHP session files to a custom location using memcache (or something similar), you still need to [tell PHP about it](http://php.net/manual/en/session.configuration.php#ini.session.save-handler) from Craft’s `index.php` in your public HTML folder.

```php
ini_set('session.save_handler', 'memcached');
```
:::

### `phpMaxMemoryLimit`

**Accepts**: An integer setting the number of bytes, or a string set to a valid [PHP shorthand byte value](http://php.net/manual/en/faq.using.php#faq.using.shorthandbytes)

**Default**: `'256M'` (256 MB)

**Since**: Craft 1.0

The maximum amount of memory Craft will try to reserve during memory intensive operations such as zipping, unzipping and updating.

```php
'phpMaxMemoryLimit' => '512M',
```

### `phpSessionName`

**Accepts**: A string of alphanumeric characters

**Default**: `'CraftSessionId'`

**Since**: Craft 2.4

The name of the PHP session cookie Craft will use. (This value will get passed to [session_name()](http://php.net/manual/en/function.session-name.php)).

```php
'phpSessionName' => 'PHPSESSID',
```

### `runTasksAutomatically`

**Accepts**: `true` or `false`

**Default**: `true`

**Since**: Craft 2.3.2632

Whether Craft should run pending background tasks automatically over HTTP requests, or leave it up to something like a Cron job to call `index.php/actions/tasks/runPendingTasks` at a regular interval.

This setting should be disabled for servers running Win32, or with Apache’s mod_deflate/mod_gzip installed, where PHP’s [flush()](http://php.net/manual/en/function.flush.php) method won’t work.

If disabled, an alternate task running trigger *must* be set up separately. For example, this Cron command would trigger a task runner once every minute:

```
*/1 * * * * /usr/bin/curl --silent --compressed http://example.com/index.php?p=actions/tasks/runPendingTasks
```

### `sanitizeSvgUploads`

**Accepts**: `true` or `false`

**Default**: `true`

**Since**: Craft 2.6.2984

Whether Craft should sanitize uploaded SVG files and strip out potentially malicious looking content. Should definitely be enabled if you are accepting SVG uploads from untrusted sources.

### `sendPoweredByHeader`

**Accepts**: `true` or `false`

**Default**: `true`

**Since**: Craft 2.4

Whether the `X-Powered-By: Craft CMS` header should be sent along with each request.

```php
'sendPoweredByHeader' => false,
```

### `siteName`

**Accepts**: A string, or an array that maps locale IDs to locale-specific strings.

**Default**: `null`

**Since**: Craft 2.3

Your site’s name. If this is set, it will take precedence over the Site Name setting in Settings → General.

```php
'siteName' => array(
    'en' => 'On the Rocks',
    'es' => 'Con Hielo'
),
```

### `siteUrl`

**Accepts**: A string set to any valid URL, or an array that maps locale IDs to locale-specific URLs

**Default**: `null`

**Since**: Craft 2.0

Your site’s URL. If this is set, it will take precedence over the Site URL setting in Settings → General, as well as the [CRAFT_SITE_URL](php-constants.md#craft-site-url) constant, if set.

This is the recommended way to set the site URL on multi-lingual sites, as it gives Craft a way of knowing the correct URLs it should point localized entries/categories to.

```php
'siteUrl' => array(
    'en' => 'http://example.com/',
    'de' => 'http://example.de/'
),
```

### `timezone`

**Accepts**: A string set to a [valid PHP timezone](http://php.net/manual/en/timezones.php).

**Default**: `null`

**Since**: Craft 2.3

The system timezone. If this is set, it will take precedence over the Timezone setting in Settings → General.

```php
'timezone' => 'America/Los_Angeles',
```

### `translationDebugOutput`

**Accepts**: `true` or `false`

**Default**: `false`

**Since**: Craft 1.0

Tells Craft whether to surround all strings that are ran through `Craft::t()` or the `|translate` filter with “@” symbols, to help find any strings that are not being translated.

```php
'translationDebugOutput' => true,
```

### `useCompressedJs`

**Accepts**: `true` or `false`

**Default**: `true`

**Since**: Craft 1.0

Tells Craft whether to use compressed Javascript files whenever possible, to cut down on page load times.

```php
'useCompressedJs' => false,
```

### `useWriteFileLock`

**Accepts**: `true` or `false`, or `'auto'`

**Default**: `'auto'`

**Since**: Craft 2.0

Whether to grab an exclusive lock on a file when writing to it by using the LOCK_EX flag.

Some file systems, such as NFS, do not support exclusive file locking.

When set to `'auto'`, Craft will automatically try to detect if the underlying file system supports exclusive file locking and cache the results.

```php
'useWriteFileLock' => true,
```

### `useXSendFile`

**Accepts**: `true` or `false`

**Default**: `false`

**Since**: Craft 1.0

Whether Craft should use XSendFile to serve files when possible.

```php
'useXSendFile' => true,
```

## Security

### `csrfTokenName`
**Accepts**: A string

**Default**: `'CRAFT_CSRF_TOKEN'`

**Since**: Craft 2.2

The name that Craft should give CSRF cookies, and which [getCsrfInput()](templating/functions.md#getcsrfinput) will give to the input it returns, if [CSRF Protection](https://craftcms.com/support/csrf-protection) is enabled.

```php
'csrfTokenName' => 'CSRF',
```

### `defaultFilePermissions`

**Accepts**: A valid [PHP file permission mode](http://php.net/manual/en/function.chmod.php)

**Default**: `0664`

**Since**: Craft 2.2

The permissions Craft will use when creating a new file on the file system.

```php
'defaultFilePermissions' => 0744,
```

### `defaultFolderPermissions`

**Accepts**: A valid [PHP file permission mode](http://php.net/manual/en/function.chmod.php)

**Default**: `0775`

**Since**: Craft 1.0

The default permissions Craft will use when creating a new folder on the file system.

```php
'defaultFolderPermissions' => 0744,
```

### `defaultTokenDuration`

**Accepts**: A string set to any valid [PHP interval specification](http://php.net/manual/en/dateinterval.construct.php)

**Default**: `'P1D'` (one day)

**Since**: Craft 2.1

The default duration that system tokens should last for.

```php
'defaultTokenDuration' => 'P1W',
```

### `enableCsrfProtection`

**Accepts**: `true` or `false`

**Default**: `false`

**Since**: Craft 2.2

Whether [CSRF Protection](https://craftcms.com/support/csrf-protection) should be enabled for the site.

```php
'enableCsrfProtection' => true,
```

::: tip
This setting is set to `true` by default in Craft 3.0.
:::

### `preventUserEnumeration`

**Accepts**: `true` or `false`

**Default**: `false`

**Since**: Craft 2.6.2848

Prevents “Forgot Password” forms from revealing whether a valid email address was entered, so even if the email was invalid, a “Password reset email sent” message will be displayed.

```php
'preventUserEnumeration' => true,
```

### `tokenParam`

**Accepts**: A string set to any valid query string parameter name

**Default**: `'token'`

**Since**: Craft 2.1

The query string parameter name that Craft should use for system tokens.

```php
'tokenParam' => 't',
```

### `useSslOnTokenizedUrls`

**Accepts**: `true`, `false`, or `'auto'`

**Default**: `'auto'`

**Since**: Craft 2.6.2793

Whether tokenized URLs (email verification links, entry draft sharing links, etc.) should begin with https or not. When set to `'auto'`, https will be used if either the base site URL or the current URL have https.

```php
'useSslOnTokenizedUrls' => true,
```

### `useSecureCookies`

**Accepts**: `true`, `false`, or `'auto'`

**Default**: `'auto'`

**Since**: Craft 2.3.2639

Whether Craft should set the secure flag on its cookies, limiting them to only be sent on secure (SSL) requests.

If this is set to `'auto'`, it will resolve to `true` or `false` depending on whether the current request is secure.

### `validateUnsafeRequestParams`

**Accepts**: `true` or `false`

**Default**: `false`

**Since**: Craft 2.6.2945

If set to `true`, the following request parameters will need to be hashed to ensure they weren’t tampered with:

- all `redirect` parameters
- possibly 3rd party plugin parameters

```php
'validateUnsafeRequestParams' => true,
```

To hash a value from a Twig template, you can pass it through the `|hash` filter. For example:

```twig
<input type="hidden" name="redirect" value="{{ 'my-page'|hash }}">
```

Enabling this will prevent certain Denial of Service (DoS) attack vectors. As an added benefit, Twig will no longer operate in Safe Mode when otherwise-unsafe input values.

### `validationKey`

**Accepts**: String

**Default**: `null`

**Since**: Craft 2.5

Overrides the auto-generated secure validation key used to verify that hashed values have not been tampered with.

This should be set on load-balanced environments, or servers where the craft/storage/runtime folder is purged on a regular basis. If you are going to set this, make sure to set it to a private, random, cryptographically secure key.  In a load-balanced environment, make sure you use the same key on all servers in the cluster.

```php
'validationKey' => '6#AYD6jW6nUJ3GMfreeXcPTGmBu.V*3Fi?f',
```

::: warning
I know what you’re thinking… “I’ll just copy the key they used in their example and use it as my own.”  Don’t do that. Use a tool like <https://www.grc.com/passwords.htm> to generate a cryptographically secure key just for yourself!
:::

## Updates

### `allowAutoUpdates`

**Accepts**: `true`, `'minor-only'`, `'build-only'`, or `false`

**Default**: `true`

**Since**: Craft 2.0

If set to `true`, all Craft updates will be auto-updatable.

If set to `'minor-only'`, then only minor updates and build updates will be auto-updatable; if a new major version comes out, the Updates page will only have a “Download” button, not an “Updates” button.

If set to `'build-only'`, then only build updates will be auto-updatable; if a new major or minor version comes out, the Updates page will only have a “Download” button, not an “Updates” button.

If set to `false`, no updates will be auto-updatable.

```php
'allowAutoUpdates' => 'minor-only',
```

### `backupDbOnUpdate`

**Accepts**: `true` or `false`

**Default**: `true`

**Since**: Craft 1.0

Whether Craft should backup the database when updating. This applies to both auto and manual updates.

```php
'backupDbOnUpdate' => false,
```

### `restoreDbOnUpdateFailure`

**Accepts**: `true` or `false`

**Default**: `true`

**Since**: Craft 1.0

Whether Craft should attempt to restore the just-created DB backup in the event that there was an error making the database schema changes mandated by the update.

```php
'restoreDbOnUpdateFailure' => false,
```

### `showBetaUpdates`

**Accepts**: `true` or `false`

**Default**: `false`

**Since**: Craft 2.4.2688

Whether Craft should show Beta updates on the Updates page, when available.

```php
'showBetaUpdates' => true,
```

## URLs

### `actionTrigger`

**Accepts**: A string set to any valid URI segment

**Default**: `'actions'`

**Since**: Craft 1.0

The URI segment Craft should look for when determining if the current request should first be routed to a controller action.

```php
'actionTrigger' => 'ACT',
```

### `addTrailingSlashesToUrls`

**Accepts**: `true` or `false`

**Default**: `false`

**Since**: Craft 2.0

Whether dynamically-generated URLs should include a trailing slash.

```php
'addTrailingSlashesToUrls' => true,
```

### `allowUppercaseInSlug`

**Accepts**: `true` or `false`

**Default**: `false`

**Since**: Craft 2.1

Whether uppercase letters should be allowed in entry and category slugs. Note that this will not affect whether auto-generated slugs have uppercase letters (they won’t).

```php
'allowUppercaseInSlug' => true,
```

### `baseCpUrl`

**Accepts**: A string or `null`

**Default**: `null`

**Since**: Craft 2.1

Explicitly sets the base URL to the Control Panel, which may be used if the dynamically-determined URL is not desired for some reason.

```php
'baseCpUrl' => 'http://admin.example.com/',
```

### `cpTrigger`

**Accepts**: A string set to any valid URI segment

**Default**: 'admin'

**Since**: Craft 1.0

The URI segment Craft should look for when determining if the current request should route to the CP rather than the front-end website.

```php
'cpTrigger' => 's3cret',
```

### `limitAutoSlugsToAscii`

**Accepts**: `true` or `false`

**Default**: `false`

**Since**: Craft 2.2

Limits automatically-generated slugs to ASCII characters. When there is an obvious ASCII version of a character (e.g. `ñ` → `n`), it will be used. Other characters will be removed.

```php
'limitAutoSlugsToAscii' => true,
```

### `maxSlugIncrement`

**Accepts**: A positive integer

**Default**: 100

**Since**: Craft 2.2

The maximum number of increments Craft will apply to a slug while searching for one that will result in an element having a unique URL, before giving up and throwing an error.

```php
'maxSlugIncrement' => 200,
```

### `omitScriptNameInUrls`

**Accepts**: `true`, `false`, or `'auto'`

**Default**: `'auto'`

**Since**: Craft 1.0

Whether Craft should omit the script filename currently being used to access Craft (e.g. `index.php`) when generating URLs.

When set to `'auto'`, Craft will do its best to determine whether the server is set up to support [index.php redirects](https://craftcms.com/support/remove-index.php), and cache the test’s results for 24 hours.

```php
'omitScriptNameInUrls' => true,
```

### `pageTrigger`

**Accepts**: A URI-safe string, or a query string parameter name

**Default**: `'p'`

**Since**: Craft 1.0

The string preceding a number which Craft will look for when determining if the current request is for a particular page in a paginated list of pages.

```php
'pageTrigger' => 'page/',
```

If you would prefer for the page number to be specified as a query string parameter, begin the config setting value with a `?`, followed by the desired parameter name.

```php
'pageTrigger' => '?page',
```

Note that `?p` cannot be used here, as Craft already uses a `p` query string parameter to determine the requested path.

### `resourceTrigger`

**Accepts**: A string set to any valid URI segment

**Default**: `'cpresources'`

**Since**: Craft 1.0

The URI segment Craft should look for when determining if the current request should route to a resource file, either in `craft/app/resources/` or a plugin’s `resources/` folder.

```php
'resourceTrigger' => 'assets',
```

::: tip
The default resourceTrigger value changed in Craft 2.0. In Craft 1.x it was 'resources'.
:::

### `slugWordSeparator`

**Accepts**: A URI-safe string

**Default**: `'-'` (a dash)

**Since**: Craft 2.0

The string Craft should use to separate words when generating entry/category slugs.

```php
'slugWordSeparator' => '_',
```

### `usePathInfo`

**Accepts**: `true`, `false`, or `'auto'`

**Default**: `'auto'`

**Since**: Craft 1.0

Whether Craft should specify the path using PATH_INFO or as a query string parameter when generating URLs that include `index.php`. (See [Enabling PATH_INFO](https://craftcms.com/support/enable-path-info) for more info.)

```php
'usePathInfo' => true,
```

## Templating

### `cacheElementQueries`

**Accepts**: `true` or `false`

**Default**: `true`

**Since**: Craft 2.2

Whether element queries should be recorded when the [cache](templating/cache.md) tag is doing its thing. If this is set to `false`, it will be up to you to clear caches manually when making changes that would result in an element showing up within a `{% cache %}` tag where it would not have before (such as creating new entries).

This will also prevent the “Deleting stale template caches” background task from running each time an element is saved.

```php
'cacheElementQueries' => false,
```

### `defaultTemplateExtensions`

**Accepts**: An array of strings identifying the file extensions

**Default**: `array('html', 'twig')`

**Since**: Craft 1.1

The template file extensions Craft will look for when matching a template path to a file on the front end. (Also see [indexTemplateFilenames](#indextemplatefilenames).)

```php
'defaultTemplateExtensions' => array('html', 'htm', 'xhtml'),
```

### `enableTemplateCaching`

**Accepts**: `true` or `false`

**Default**: `true`

**Since**: Craft 2.4

Whether template caching via the [cache](templating/cache.md) tag should be enabled.

```php
'enableTemplateCaching' => false,
```

### `errorTemplatePrefix`

**Accepts**: A string

**Default**: `''`

**Since**: Craft 2.0

The path prefix to HTTP error code templates, like 404.html.

```php
'errorTemplatePrefix' => "_errors/",
```

### `indexTemplateFilenames`

**Accepts**: An array of strings identifying the filenames (sans extensions)

**Default**: `array('index')`

**Since**: Craft 1.1

The template filenames Craft will look for within a directory to represent the directory’s “index” template when matching a template path to a file on the front end. (Also see [defaultTemplateExtensions](#defaulttemplateextensions).)

```php
'indexTemplateFilenames' => array('index', 'default'),
```

### `privateTemplateTrigger`

**Accepts**: A string

**Default**: `'_'`

**Since**: Craft 2.0

The prefix that can be placed before a template folder/file name to forbid direct HTTP traffic to it.

```php
'privateTemplateTrigger' => "."
```

### `suppressTemplateErrors`

**Accepts**: `true` or `false`

**Default**: `false`

**Since**: Craft 2.5

Controls whether or not to show or hide any Twig template runtime errors that occur on the site in the browser. If it is set to `true`, the errors will still be logged to Craft’s log files.

```php
'suppressTemplateErrors' => true
```

## Users

### `activateAccountFailurePath`

**Accepts**: A string set to any valid URI, or an array that maps locale IDs to locale-specific URIs

**Default**: `null`

**Since**: Craft 1.2

The URI Craft should redirect to when user front-end account activation fails.  Note that this only affects front-end site requests.

```php
'activateAccountFailurePath' => 'members/activation-failed',
```

### `activateAccountSuccessPath`

**Accepts**: A string set to any valid URI, or an array that maps locale IDs to locale-specific URIs

**Default**: `null`

**Since**: Craft 2.3

The URI Craft should redirect to when a user is activated. Note that this only affects front-end site requests.

```php
'activateAccountSuccessPath' => 'members/activation-success',
```

### `autoLoginAfterAccountActivation`

**Accepts**: `true` or `false`

**Default**: `false`

**Since**: Craft 2.1

Configures Craft to automatically log users in immediately after they’ve activated their accounts.

```php
'autoLoginAfterAccountActivation' => true,
```

::: tip
If your site allows public registration and users aren’t required to verify their email addresses, this setting will take effect immediately after new users register their accounts.
:::

### `blowfishHashCost`

**Accepts**: An integer

**Default**: `13`

**Since**: Craft 1.2

The higher the cost value, the longer it takes to generate a password hash and to verify against it. Therefore, higher cost slows down a brute-force attack.

For best protection against brute force attacks, set it to the highest value that is tolerable on production servers.

The time taken to compute the hash doubles for every increment by one for this value.  For example, if the hash takes 1 second to compute when the value is 14 then the compute time varies as 2^(value - 14) seconds.

```php
'blowfishHashCost' => 14,
```

### `cooldownDuration`

**Accepts**: A string set to any valid [PHP interval specification](http://php.net/manual/en/dateinterval.construct.php)

**Default**: `'PT5M'` (five minutes)

**Since**: Craft 1.0

The amount of time a user must wait before re-attempting to log in after their account is locked due to too many failed login attempts.

Set to `false` to keep the account locked indefinitely, requiring an admin to manually unlock the account.

```php
'cooldownDuration' => false,
```

### `defaultCpLanguage`

**Accepts**: A string of a locale ID, or `null`

**Default**: `null`

**Since**: Craft 2.4

The default language that the Control Panel should be rendered in, for users that haven’t specified their Preferred Locale.

```php
'defaultCpLanguage' => 'en',
```

### `deferPublicRegistrationPassword`

**Accepts**: `true` or `false`

**Default**: `false`

**Since**: Craft 2.6.2949

When set to `true`, public user registration forms will no longer need to include a `password` input. Users will have the opportunity to set their password after verifying their email address, if “Verify email addresses?” is enabled in Settings → Users → Settings.

```php
'deferPublicRegistrationPassword' => true,
```

### `elevatedSessionDuration`

**Accepts**: A string set to any valid [PHP interval specification](http://php.net/manual/en/dateinterval.construct.php) or `false`

**Default**: `'PT5M'` (Five minutes)

**Since**: Craft 2.6.2784

The amount of time a user’s elevated session will last, which is required for some sensitive actions (e.g. user group/permission assignment).

Elevated Sessions functionality can be disabled entirely by setting this to `false`. We don’t recommend actually doing this unless you have a good reason to!

### `invalidLoginWindowDuration`

**Accepts**: A string set to any valid [PHP interval specification](http://php.net/manual/en/dateinterval.construct.php)

**Default**: `'PT1H'` (one hour)

**Since**: Craft 1.0

The amount of time to track invalid login attempts for a user, for determining if Craft should lock an account.

```php
'invalidLoginWindowDuration' => 'PT10M',
```

### `invalidUserTokenPath`

**Accepts**: A string set to any valid URI, or an array that maps locale IDs to locale-specific URIs

**Default**: `''` (an empty string)

**Since**: Craft 2.3

The URI Craft should redirect to when user token validation fails.

```php
'invalidUserTokenPath' => 'members/invalidtoken',
```

### `loginPath`

**Accepts**: A string set to any valid URI, or an array that maps locale IDs to locale-specific URIs

**Default**: `'login'`

**Since**: Craft 1.0

The URI Craft should use for user login on the front-end.

```php
'loginPath' => 'members/login',
```

### `logoutPath`

**Accepts**: A string set to any valid URI, or an array that maps locale IDs to locale-specific URIs

**Default**: `'logout'`

**Since**: Craft 1.0

The URI Craft should use to log out users on the front-end of the site.

```php
'logoutPath' => 'members/logout',
```

### `maxInvalidLogins`

**Accepts**: Any integer, or `false`

**Default**: `5`

**Since**: Craft 1.0

The number of invalid login attempts Craft will allow within the specified duration before the account gets locked. If it is set to `0` or `false`, Craft will not enforce any maximum (not recommended on production).

```php
'maxInvalidLogins' => false,
```

### `postCpLoginRedirect`

**Accepts**: A string

**Default**: `'dashboard'`

**Since**: Craft 2.2

The path that users should be redirected to after logging in from the Control Panel.

This setting will also come into effect if the user visits the CP’s Login page when they are already logged in, or the CP’s root URL (e.g. /admin).

```php
'postCpLoginRedirect' => 'entries',
```

### `postLoginRedirect`

**Accepts**: A string

**Default**: `''` (An empty string, which will give you the site homepage)

**Since**: Craft 2.2

The URL or path that users should be redirected to after logging in from the front-end site.

This setting will also come into effect if the user visits the site’s Login page (as specified by [loginPath](#loginpath)) when they are already logged in.

```php
'postLoginRedirect' => 'members/home',
```

### `purgePendingUsersDuration`

**Accepts**: A string set to any valid [PHP interval specification](http://php.net/manual/en/dateinterval.construct.php), or `false` to disable the feature

**Default**: `false`

**Since**: Craft 2.1

The amount of time to wait before Craft purges pending users from the system that have not activated. Set to `false` to disable this feature. Note that if you set this to a time interval, then any content assigned to a pending user will be deleted as well when the given time interval passes.

```php
'purgePendingUsersDuration' => 'P1M',
```

### `rememberUsernameDuration`

**Accepts**: A string set to any valid [PHP interval specification](http://php.net/manual/en/dateinterval.construct.php)

**Default**: `'P1Y'` (1 year)

**Since**: Craft 1.0

The amount of time Craft will remember a username and pre-populate it on the CP login page.

Set to `false` to disable this feature altogether.

```php
'rememberUsernameDuration' => false,
```

### `rememberedUserSessionDuration`

**Accepts**: A string set to any valid [PHP interval specification](http://php.net/manual/en/dateinterval.construct.php)

**Default**: `'P2W'` (two weeks)

**Since**: Craft 1.0

The amount of time a user stays logged in if “Remember Me” is checked on the login page.

Set to `false` to disable the “Remember Me” feature altogether.

```php
'rememberedUserSessionDuration' => false,
```

### `requireMatchingUserAgentForSession`

**Accepts**: `true` or `false`

**Default**: `true`

**Since**: Craft 1.0

Whether Craft should require a matching user agent string when restoring a user session from a cookie.

```php
'requireMatchingUserAgentForSession' => false,
```

### `requireUserAgentAndIpForSession`

**Accepts**: `true` or `false`

**Default**: `true`

**Since**: Craft 1.0

Whether Craft should require the existence of a user agent string and IP address when creating a new user session.

```php
'requireUserAgentAndIpForSession' => false,
```

### `setPasswordPath`

**Accepts**: A string set to any valid template, or an array that maps locale IDs to locale-specific templates

**Default**: `'setpassword'`

**Since**: Craft 1.0

The URI and template path that Craft should use for user password resetting. Note that this only affects front-end site requests, and Password Reset emails will only use this setting for users that don’t have access to the Control Panel.

```php
'setPasswordPath' => array(
    'en' => 'password',
    'de' => 'passwort'
),
```

### `setPasswordSuccessPath`

**Accepts**: A string set to any valid template, or an array that maps locale IDs to locale-specific templates

**Default**: `''`

**Since**: Craft 1.1

The URI and template path that Craft should use upon successfully setting a users’s password. Note that this only affects front-end site requests.

```php
'setPasswordSuccessPath' => array(
    'en' => 'password/success',
    'de' => 'passwort/erfolg'
),
```

### `testToEmailAddress`

**Accepts**: A string set to a valid email address, or an array of email addresses.

**Default**: `null`

**Since**: Craft 1.0

Configures Craft to send all system emails to a single email address (or multiple email addresses), for testing purposes.

```php
'testToEmailAddress' => 'me@example.com',
```

### `useEmailAsUsername`

**Accepts**: `true` or `false`

**Default**: `false`

**Since**: Craft 2.1

Removes “Username” fields in the Control Panel, and automatically saves users’ usernames based on their email addresses.

```php
'useEmailAsUsername' => true,
```

### `userSessionDuration`

**Accepts**: `false` or a string set to any valid [PHP interval specification](http://php.net/manual/en/dateinterval.construct.php)

**Default**: `'PT1H'` (one hour)

**Since**: Craft 1.0

The amount of time a user stays logged in.

Set to `false` if you want users to stay logged in as long as their browser is open rather than a predetermined amount of time.

### `verificationCodeDuration`

**Accepts**: A string set to any valid [PHP interval specification](http://php.net/manual/en/dateinterval.construct.php)

**Default**: `'P1D'` (one day)

**Since**: Craft 1.0

The amount of time a user verification code can be used before expiring.

```php
'verificationCodeDuration' => 'P1M',
```

## Assets

### `allowedFileExtensions`

**Accepts**: A string of a comma-separated list of file extensions.

**Default**: `'7z, aiff, asf, avi, bmp, csv, doc, docx, fla, flv, gif, gz, gzip, htm, html, jp2, jpeg, jpg, jpx, js, mid, mov, mp3, mp4, m4a, m4v, mpc, mpeg, mpg, ods, odt, ogg, ogv, pdf, png, potx, pps, ppsm, ppsx, ppt, pptm, pptx, ppz, pxd, qt, ram, rar, rm, rmi, rmvb, rtf, sdc, sitd, svg, swf, sxc, sxw, tar, tgz, tif, tiff, txt, vob, vsd, wav, webm, wma, wmv, xls, xlsx, zip'`

**Since**: Craft 1.0

A list of file extensions that Craft will allow when a user is uploading files. Note that if you only want to add additional file extensions, without overriding the default extensions, you can use [extraAllowedFileExtensions](#extraallowedfileextensions) instead.

```php
'allowedFileExtensions' => 'jpg, jpeg, png, gif',
```

### `convertFilenamesToAscii`

**Accepts**: `true` or `false`

**Default**: `false`

**Since**: Craft 2.4

Whether Craft should convert any non-ASCII characters in uploaded file names to ASCII.

```php
'convertFilenamesToAscii' => true,
```

### `defaultImageQuality`

**Accepts**: A numeric value between 0 and 100, 0 being the lowest quality and smallest file size, and 100 being the highest quality and largest file size

**Default**: `75`

**Since**: Craft 1.1

The default quality Craft will use when creating [image transforms](image-transforms.md). Note that this can be overridden on a per-transform basis.

```php
'defaultImageQuality' => 90,
```

### `extraAllowedFileExtensions`

**Accepts**: A string of a comma-separated list of file extensions.

**Default**: `''`

**Since**: Craft 2.0

A list of additional file extensions that Craft will allow when a user is uploading files, which will get appended to the list specified by [allowedFileExtensions](#allowedfileextensions).

```php
'extraAllowedFileExtensions' => 'log',
```

### `filenameWordSeparator`

**Accepts**: A string or `null`

**Default**: `'-'` (a dash)

**Since**: Craft 2.2

The string that should replace spaces in filenames after a file has been uploaded. If it is set to `null`, spaces will be left alone.

```php
'filenameWordSeparator' => '_',
```

### `generateTransformsBeforePageLoad`

**Accepts**: `true` or `false`

**Default**: `false`

**Since**: Craft 2.1

Configures Craft to generate new image transforms right when [getUrl()](templating/assetfilemodel.md#geturl) is called, rather than when the browser first requests the image.

```php
'generateTransformsBeforePageLoad' => true,
```

### `imageDriver`

**Accepts**: `'gd'`, `'imagick'`, or `null`

**Default**: `null`

**Since**: Craft 2.2

Forces Craft to use a specific image manipulation library.

```php
'imageDriver' => 'gd',
```

### `maxCachedCloudImageSize`

**Accepts**: An integer

**Default**: `2000`

**Since**: Craft 1.2

The maximum size in pixels (length or width) Craft should use when caching images from external sources, which it uses to speed up transform generation. Set to `0` if you don’t want Craft to cache them at all.

```php
'maxCachedCloudImageSize' => 2880,
```

### `maxUploadFileSize`

**Accepts**: An integer

**Default**: `16777216` (16MB)

**Since**: Craft 2.1

The maximum file size (in bytes) Craft should allow users to upload.

```php
'maxUploadFileSize' => 33554432,
```

### `preserveCmykColorspace`

**Accepts**: `true` or `false`

**Default**: `false`

**Since**: Craft 2.6.3016

Whether images with CMYK colorspace should retain it. If false, images will be converted to sRGB colorspace.

Setting this to true will leave CMYK colorspace with images that have it. This will only have effect if Imagick is in use.

### `preserveExifData`

**Accepts**: `true` or `false`

**Default**: `false`

**Since**: Craft 2.6.2993

Whether the EXIF data should be preserved when manipulating images. Setting this to `false` will reduce the image size a little bit, but all EXIF data will be cleared. This setting is only taken into account if Imagick is in use.

### `preserveImageColorProfiles`

**Accepts**: `true` or `false`

**Default**: `true`

**Since**: Craft 2.5

Whether the embedded color profiles should be preserved when uploading an image.

```php
'preserveImageColorProfiles' => false,
```

### `rotateImagesOnUploadByExifData`

**Accepts**: `true` or `false`

**Default**: `true`

**Since**: Craft 2.3

Whether Craft should rotate images according to their EXIF data on upload.

This setting takes effect when images are uploaded with an “Orientation” entry in their EXIF data, which informs Craft that the image was taken by a camera while it was held sideways. When the setting is enabled, Craft will look for that EXIF entry on images as they’re uploaded, and if present, automatically rotate the image accordingly and strip out that Orientation entry.

```php
'rotateImagesOnUploadByExifData' => true,
```

### `transformGifs`

**Accepts**: `true` or `false`

**Default**: `true`

**Since**: Craft 2.6.3016

Tells Craft whether GIF files should be transformed or cleansed. Defaults to true.

```php
'transformGifs' => false,
```

## Tags

### `allowSimilarTags`

**Accepts**: `true` or `false`

**Default**: `false`

**Since**: Craft 2.6.2791

Whether Craft should allow multiple tags to exist with names that would be identical if converted down to ASCII (e.g. Protéines, Proteines).

```php
'allowSimilarTags' => true,
```
