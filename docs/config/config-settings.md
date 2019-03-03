# General Config Settings

Craft supports several configuration settings that give you control over its behavior.

To set a new config setting, open `config/general.php` and define it in one of the environment config arrays, depending on which environment(s) you want the setting to apply to.

For example, if you want to allow Craft to be updated in dev environments but not on staging or production environments, do this:

```php{4,10}
return [
    // Global settings
    '*' => [
        'allowUpdates' => false,
        // ...
    ],

    // Dev environment settings
    'dev' => [
        'allowUpdates' => true,
        // ...
    ],

    // Staging environment settings
    'staging' => [
        // ...
    ],

    // Production environment settings
    'production' => [
        // ...
    ],
];
```

Here’s the full list of config settings that Craft supports:

<!-- BEGIN SETTINGS -->

### `actionTrigger`

Allowed types

:   [string](http://php.net/language.types.string)

Default value

:   `'actions'`

Defined by

:   [GeneralConfig::$actionTrigger](api:craft\config\GeneralConfig::$actionTrigger)



The URI segment Craft should look for when determining if the current request should first be routed to a
controller action.



### `activateAccountSuccessPath`

Allowed types

:   `mixed`

Default value

:   `''`

Defined by

:   [GeneralConfig::$activateAccountSuccessPath](api:craft\config\GeneralConfig::$activateAccountSuccessPath)



The URI that users without access to the Control Panel should be redirected to after activating their account.

See [craft\helpers\ConfigHelper::localizedValue()](https://docs.craftcms.com/api/v3/craft-helpers-confighelper.html#method-localizedvalue) for a list of supported value types.



### `addTrailingSlashesToUrls`

Allowed types

:   [boolean](http://php.net/language.types.boolean)

Default value

:   `false`

Defined by

:   [GeneralConfig::$addTrailingSlashesToUrls](api:craft\config\GeneralConfig::$addTrailingSlashesToUrls)



Whether auto-generated URLs should have trailing slashes.



### `aliases`

Allowed types

:   [array](http://php.net/language.types.array)

Default value

:   `[]`

Defined by

:   [GeneralConfig::$aliases](api:craft\config\GeneralConfig::$aliases)



Any custom Yii [aliases](https://www.yiiframework.com/doc/guide/2.0/en/concept-aliases) that should be defined for every request.



### `allowAdminChanges`

Allowed types

:   [boolean](http://php.net/language.types.boolean)

Default value

:   `true`

Defined by

:   [GeneralConfig::$allowAdminChanges](api:craft\config\GeneralConfig::$allowAdminChanges)



Whether admins should be allowed to make administrative changes to the system.

If this is disabled, the Settings and Plugin Store sections will be hidden,
the Craft edition and Craft/plugin versions will be locked, and the project config will become read-only.

Therefore you should only disable this in production environments when [useProjectConfigFile](https://docs.craftcms.com/api/v3/craft-config-generalconfig.html#useprojectconfigfile) is enabled,
and you have a deployment workflow that runs `composer install` automatically on deploy.

::: warning
Don’t disable this setting until **all** environments have been updated to Craft 3.1.0 or later.
:::



### `allowSimilarTags`

Allowed types

:   [boolean](http://php.net/language.types.boolean)

Default value

:   `false`

Defined by

:   [GeneralConfig::$allowSimilarTags](api:craft\config\GeneralConfig::$allowSimilarTags)



Whether users should be allowed to create similarly-named tags.



### `allowUpdates`

Allowed types

:   [boolean](http://php.net/language.types.boolean)

Default value

:   `true`

Defined by

:   [GeneralConfig::$allowUpdates](api:craft\config\GeneralConfig::$allowUpdates)



Whether Craft should allow system and plugin updates in the Control Panel, and plugin installation from the Plugin Store.

This setting will automatically be disabled if [allowAdminChanges](https://docs.craftcms.com/api/v3/craft-config-generalconfig.html#allowadminchanges) is disabled.



### `allowUppercaseInSlug`

Allowed types

:   [boolean](http://php.net/language.types.boolean)

Default value

:   `false`

Defined by

:   [GeneralConfig::$allowUppercaseInSlug](api:craft\config\GeneralConfig::$allowUppercaseInSlug)



Whether uppercase letters should be allowed in slugs.



### `allowedFileExtensions`

Allowed types

:   [string](http://php.net/language.types.string)[]

Default value

:   `['7z', 'aiff', 'asf', 'avi', 'bmp', 'csv', 'doc', 'docx', 'fla', 'flv', 'gif', 'gz', 'gzip', 'htm', 'html', 'jp2', 'jpeg', 'jpg', 'jpx', 'js', 'json', 'm2t', 'mid', 'mov', 'mp3', 'mp4', 'm4a', 'm4v', 'mpc', 'mpeg', 'mpg', 'ods', 'odt', 'ogg', 'ogv', 'pdf', 'png', 'potx', 'pps', 'ppsm', 'ppsx', 'ppt', 'pptm', 'pptx', 'ppz', 'pxd', 'qt', 'ram', 'rar', 'rm', 'rmi', 'rmvb', 'rtf', 'sdc', 'sitd', 'svg', 'swf', 'sxc', 'sxw', 'tar', 'tgz', 'tif', 'tiff', 'txt', 'vob', 'vsd', 'wav', 'webm', 'webp', 'wma', 'wmv', 'xls', 'xlsx', 'zip']`

Defined by

:   [GeneralConfig::$allowedFileExtensions](api:craft\config\GeneralConfig::$allowedFileExtensions)



The file extensions Craft should allow when a user is uploading files.



### `autoLoginAfterAccountActivation`

Allowed types

:   [boolean](http://php.net/language.types.boolean)

Default value

:   `false`

Defined by

:   [GeneralConfig::$autoLoginAfterAccountActivation](api:craft\config\GeneralConfig::$autoLoginAfterAccountActivation)



Whether users should automatically be logged in after activating their account or resetting
their password.



### `backupCommand`

Allowed types

:   [string](http://php.net/language.types.string), [null](http://php.net/language.types.null)

Default value

:   `null`

Defined by

:   [GeneralConfig::$backupCommand](api:craft\config\GeneralConfig::$backupCommand)



The shell command that Craft should execute to create a database backup.

By default Craft will run `mysqldump` or `pg_dump`, provided that those libraries are in the `$PATH`
variable for the user the web server  is running as.

There are several tokens you can use that Craft will swap out at runtime:

- `{path}` - the target backup file path
- `{port}` - the current database port
- `{server}` - the current database host name
- `{user}` - the user to connect to the database
- `{database}` - the current database name
- `{schema}` - the current database schema (if any)

This can also be set to `false` to disable database backups completely.



### `backupOnUpdate`

Allowed types

:   [boolean](http://php.net/language.types.boolean)

Default value

:   `true`

Defined by

:   [GeneralConfig::$backupOnUpdate](api:craft\config\GeneralConfig::$backupOnUpdate)



Whether Craft should create a database backup before applying a new system update.



### `baseCpUrl`

Allowed types

:   [string](http://php.net/language.types.string), [null](http://php.net/language.types.null)

Default value

:   `null`

Defined by

:   [GeneralConfig::$baseCpUrl](api:craft\config\GeneralConfig::$baseCpUrl)



The base URL that Craft should use when generating Control Panel URLs.

It will be determined automatically if left blank.

::: tip
The base CP URL should **not** include the [CP trigger word](https://docs.craftcms.com/api/v3/craft-config-generalconfig.html#cptrigger) (e.g. `/admin`).
:::



### `blowfishHashCost`

Allowed types

:   [integer](http://php.net/language.types.integer)

Default value

:   `13`

Defined by

:   [GeneralConfig::$blowfishHashCost](api:craft\config\GeneralConfig::$blowfishHashCost)



The higher the cost value, the longer it takes to generate a password hash and to verify against it. Therefore,
higher cost slows down a brute-force attack.

For best protection against brute force attacks, set it to the highest value that is tolerable on production
servers.

The time taken to compute the hash doubles for every increment by one for this value.
For example, if the hash takes 1 second to compute when the value is 14 then the compute time varies as
2^(value - 14) seconds.



### `cacheDuration`

Allowed types

:   `mixed`

Default value

:   `86400`

Defined by

:   [GeneralConfig::$cacheDuration](api:craft\config\GeneralConfig::$cacheDuration)



The default length of time Craft will store data, RSS feed, and template caches.

If set to `0`, data and RSS feed caches will be stored indefinitely; template caches will be stored for one year.

See [craft\helpers\ConfigHelper::durationInSeconds()](https://docs.craftcms.com/api/v3/craft-helpers-confighelper.html#method-durationinseconds) for a list of supported value types.



### `cacheElementQueries`

Allowed types

:   [boolean](http://php.net/language.types.boolean)

Default value

:   `true`

Defined by

:   [GeneralConfig::$cacheElementQueries](api:craft\config\GeneralConfig::$cacheElementQueries)



Whether Craft should cache element queries that fall inside `{% cache %}` tags.



### `convertFilenamesToAscii`

Allowed types

:   [boolean](http://php.net/language.types.boolean)

Default value

:   `false`

Defined by

:   [GeneralConfig::$convertFilenamesToAscii](api:craft\config\GeneralConfig::$convertFilenamesToAscii)



Whether uploaded filenames with non-ASCII characters should be converted to ASCII (i.e. `ñ` → `n`).



### `cooldownDuration`

Allowed types

:   `mixed`

Default value

:   `300`

Defined by

:   [GeneralConfig::$cooldownDuration](api:craft\config\GeneralConfig::$cooldownDuration)



The amount of time a user must wait before re-attempting to log in after their account is locked due to too many
failed login attempts.

Set to `0` to keep the account locked indefinitely, requiring an admin to manually unlock the account.

See [craft\helpers\ConfigHelper::durationInSeconds()](https://docs.craftcms.com/api/v3/craft-helpers-confighelper.html#method-durationinseconds) for a list of supported value types.



### `cpTrigger`

Allowed types

:   [string](http://php.net/language.types.string)

Default value

:   `'admin'`

Defined by

:   [GeneralConfig::$cpTrigger](api:craft\config\GeneralConfig::$cpTrigger)



The URI segment Craft should look for when determining if the current request should route to the Control Panel rather than
the front-end website.



### `csrfTokenName`

Allowed types

:   [string](http://php.net/language.types.string)

Default value

:   `'CRAFT_CSRF_TOKEN'`

Defined by

:   [GeneralConfig::$csrfTokenName](api:craft\config\GeneralConfig::$csrfTokenName)



The name of CSRF token used for CSRF validation if [enableCsrfProtection](https://docs.craftcms.com/api/v3/craft-config-generalconfig.html#enablecsrfprotection) is set to `true`.



### `defaultCookieDomain`

Allowed types

:   [string](http://php.net/language.types.string)

Default value

:   `''`

Defined by

:   [GeneralConfig::$defaultCookieDomain](api:craft\config\GeneralConfig::$defaultCookieDomain)



The domain that cookies generated by Craft should be created for. If blank, it will be left
up to the browser to determine which domain to use (almost always the current). If you want the cookies to work
for all subdomains, for example, you could set this to `'.domain.com'`.



### `defaultCpLanguage`

Allowed types

:   [string](http://php.net/language.types.string), [null](http://php.net/language.types.null)

Default value

:   `null`

Defined by

:   [GeneralConfig::$defaultCpLanguage](api:craft\config\GeneralConfig::$defaultCpLanguage)



The default language the Control Panel should use for users who haven’t set a preferred language yet.



### `defaultDirMode`

Allowed types

:   `mixed`

Default value

:   `0775`

Defined by

:   [GeneralConfig::$defaultDirMode](api:craft\config\GeneralConfig::$defaultDirMode)



The default permission to be set for newly generated directories.

If set to `null`, the permission will be determined by the current environment.



### `defaultFileMode`

Allowed types

:   [integer](http://php.net/language.types.integer), [null](http://php.net/language.types.null)

Default value

:   `null`

Defined by

:   [GeneralConfig::$defaultFileMode](api:craft\config\GeneralConfig::$defaultFileMode)



The default permission to be set for newly generated files.

If set to `null`, the permission will be determined by the current environment.



### `defaultImageQuality`

Allowed types

:   [integer](http://php.net/language.types.integer)

Default value

:   `82`

Defined by

:   [GeneralConfig::$defaultImageQuality](api:craft\config\GeneralConfig::$defaultImageQuality)



The quality level Craft will use when saving JPG and PNG files. Ranges from 0 (worst quality, smallest file) to
100 (best quality, biggest file).



### `defaultSearchTermOptions`

Allowed types

:   [array](http://php.net/language.types.array)

Default value

:   `[]`

Defined by

:   [GeneralConfig::$defaultSearchTermOptions](api:craft\config\GeneralConfig::$defaultSearchTermOptions)



The default options that should be applied to each search term.

Options include:

- `attribute` – The attribute that the term should apply to (e.g. 'title'), if any. (`null` by default)
- `exact` – Whether the term must be an exact match (only applies if `attribute` is set). (`false` by default)
- `exclude` – Whether search results should *exclude* records with this term. (`false` by default)
- `subLeft` – Whether to include keywords that contain the term, with additional characters before it. (`false` by default)
- `subRight` – Whether to include keywords that contain the term, with additional characters after it. (`true` by default)



### `defaultTemplateExtensions`

Allowed types

:   [string](http://php.net/language.types.string)[]

Default value

:   `['html', 'twig']`

Defined by

:   [GeneralConfig::$defaultTemplateExtensions](api:craft\config\GeneralConfig::$defaultTemplateExtensions)



The template file extensions Craft will look for when matching a template path to a file on the front end.



### `defaultTokenDuration`

Allowed types

:   `mixed`

Default value

:   `86400`

Defined by

:   [GeneralConfig::$defaultTokenDuration](api:craft\config\GeneralConfig::$defaultTokenDuration)



The default amount of time tokens can be used before expiring.

See [craft\helpers\ConfigHelper::durationInSeconds()](https://docs.craftcms.com/api/v3/craft-helpers-confighelper.html#method-durationinseconds) for a list of supported value types.



### `defaultWeekStartDay`

Allowed types

:   [integer](http://php.net/language.types.integer)

Default value

:   `1`

Defined by

:   [GeneralConfig::$defaultWeekStartDay](api:craft\config\GeneralConfig::$defaultWeekStartDay)



The default day that new users should have set as their Week Start Day.

This should be set to one of the following integers:

- `0` – Sunday
- `1` – Monday
- `2` – Tuesday
- `3` – Wednesday
- `4` – Thursday
- `5` – Friday
- `6` – Saturday



### `deferPublicRegistrationPassword`

Allowed types

:   [boolean](http://php.net/language.types.boolean)

Default value

:   `false`

Defined by

:   [GeneralConfig::$deferPublicRegistrationPassword](api:craft\config\GeneralConfig::$deferPublicRegistrationPassword)



By default, Craft will require a 'password' field to be submitted on front-end, public
user registrations. Setting this to `true` will no longer require it on the initial registration form.

If you have email verification enabled, new users will set their password once they've clicked on the
verification link in the email. If you don't, the only way they can set their password is to go
through your "forgot password" workflow.



### `devMode`

Allowed types

:   [boolean](http://php.net/language.types.boolean)

Default value

:   `false`

Defined by

:   [GeneralConfig::$devMode](api:craft\config\GeneralConfig::$devMode)



Whether the system should run in [Dev Mode](https://craftcms.com/support/dev-mode).



### `disabledPlugins`

Allowed types

:   [string](http://php.net/language.types.string)[]

Default value

:   `[]`

Defined by

:   [GeneralConfig::$disabledPlugins](api:craft\config\GeneralConfig::$disabledPlugins)



Array of plugin handles that should be disabled, regardless of what the project config says.



```php
'dev' => [
    'disabledPlugins' => ['webhooks'],
],
```

### `elevatedSessionDuration`

Allowed types

:   `mixed`

Default value

:   `300`

Defined by

:   [GeneralConfig::$elevatedSessionDuration](api:craft\config\GeneralConfig::$elevatedSessionDuration)



The amount of time a user’s elevated session will last, which is required for some sensitive actions (e.g. user group/permission assignment).

Set to `0` to disable elevated session support.

See [craft\helpers\ConfigHelper::durationInSeconds()](https://docs.craftcms.com/api/v3/craft-helpers-confighelper.html#method-durationinseconds) for a list of supported value types.



### `enableCsrfCookie`

Allowed types

:   [boolean](http://php.net/language.types.boolean)

Default value

:   `true`

Defined by

:   [GeneralConfig::$enableCsrfCookie](api:craft\config\GeneralConfig::$enableCsrfCookie)



Whether to use a cookie to persist the CSRF token if [enableCsrfProtection](https://docs.craftcms.com/api/v3/craft-config-generalconfig.html#enablecsrfprotection) is enabled. If false, the CSRF token
will be stored in session under the 'csrfTokenName' config setting name. Note that while storing CSRF tokens in
session increases security, it requires starting a session for every page that a CSRF token is need, which may
degrade site performance.



### `enableCsrfProtection`

Allowed types

:   [boolean](http://php.net/language.types.boolean)

Default value

:   `true`

Defined by

:   [GeneralConfig::$enableCsrfProtection](api:craft\config\GeneralConfig::$enableCsrfProtection)



Whether to enable CSRF protection via hidden form inputs for all forms submitted via Craft.



### `enableTemplateCaching`

Allowed types

:   [boolean](http://php.net/language.types.boolean)

Default value

:   `true`

Defined by

:   [GeneralConfig::$enableTemplateCaching](api:craft\config\GeneralConfig::$enableTemplateCaching)



Whether to enable Craft's template `{% cache %}` tag on a global basis.



### `errorTemplatePrefix`

Allowed types

:   [string](http://php.net/language.types.string)

Default value

:   `''`

Defined by

:   [GeneralConfig::$errorTemplatePrefix](api:craft\config\GeneralConfig::$errorTemplatePrefix)



The prefix that should be prepended to HTTP error status codes when determining the path to look for an error’s
template.

If set to `'_'`, then your site’s 404 template would live at `templates/_404.html`, for example.



### `extraAllowedFileExtensions`

Allowed types

:   [string](http://php.net/language.types.string)[], [null](http://php.net/language.types.null)

Default value

:   `null`

Defined by

:   [GeneralConfig::$extraAllowedFileExtensions](api:craft\config\GeneralConfig::$extraAllowedFileExtensions)



List of file extensions that will be merged into the [allowedFileExtensions](https://docs.craftcms.com/api/v3/craft-config-generalconfig.html#allowedfileextensions) config setting.



### `extraAppLocales`

Allowed types

:   [string](http://php.net/language.types.string)[], [null](http://php.net/language.types.null)

Default value

:   `null`

Defined by

:   [GeneralConfig::$extraAppLocales](api:craft\config\GeneralConfig::$extraAppLocales)



List of extra locale IDs that the application should support, and users should be able to select as their Preferred Language.

Only use this setting if your server has the Intl PHP extension, or if you’ve saved the corresponding
[locale data](https://github.com/craftcms/locales) into your `config/locales/` folder.



### `extraFileKinds`

Allowed types

:   [array](http://php.net/language.types.array)

Default value

:   `[]`

Defined by

:   [GeneralConfig::$extraFileKinds](api:craft\config\GeneralConfig::$extraFileKinds)



List of additional file kinds Craft should support. This array
will get merged with the one defined in `\craft\config\craft\helpers\Assets::_buildFileKinds()`.

```php
'extraFileKinds' => [
    // merge .psb into list of Photoshop file kinds
    'photoshop' => [
        'extensions' => ['psb'],
    ],
    // register new "Stylesheet" file kind
    'stylesheet' => [
        'label' => 'Stylesheet',
        'extensions' => ['css', 'less', 'pcss', 'sass', 'scss', 'styl'],
    ],
],
```

::: tip
File extensions listed here won’t immediately be allowed to be uploaded. You will also need to list them with
the [extraAllowedFileExtensions](https://docs.craftcms.com/api/v3/craft-config-generalconfig.html#extraallowedfileextensions) config setting.
:::



### `filenameWordSeparator`

Allowed types

:   [string](http://php.net/language.types.string), [boolean](http://php.net/language.types.boolean)

Default value

:   `'-'`

Defined by

:   [GeneralConfig::$filenameWordSeparator](api:craft\config\GeneralConfig::$filenameWordSeparator)



The string to use to separate words when uploading Assets. If set to `false`, spaces will be left alone.



### `generateTransformsBeforePageLoad`

Allowed types

:   [boolean](http://php.net/language.types.boolean)

Default value

:   `false`

Defined by

:   [GeneralConfig::$generateTransformsBeforePageLoad](api:craft\config\GeneralConfig::$generateTransformsBeforePageLoad)



Whether images transforms should be generated before page load.



### `imageDriver`

Allowed types

:   `mixed`

Default value

:   `self::IMAGE_DRIVER_AUTO`

Defined by

:   [GeneralConfig::$imageDriver](api:craft\config\GeneralConfig::$imageDriver)



The image driver Craft should use to cleanse and transform images. By default Craft will auto-detect if ImageMagick is installed and fallback to GD if not. You can explicitly set
either `'imagick'` or `'gd'` here to override that behavior.



### `indexTemplateFilenames`

Allowed types

:   [string](http://php.net/language.types.string)[]

Default value

:   `['index']`

Defined by

:   [GeneralConfig::$indexTemplateFilenames](api:craft\config\GeneralConfig::$indexTemplateFilenames)



The template filenames Craft will look for within a directory to represent the directory’s “index” template when
matching a template path to a file on the front end.



### `invalidLoginWindowDuration`

Allowed types

:   `mixed`

Default value

:   `3600`

Defined by

:   [GeneralConfig::$invalidLoginWindowDuration](api:craft\config\GeneralConfig::$invalidLoginWindowDuration)



The amount of time to track invalid login attempts for a user, for determining if Craft should lock an account.

See [craft\helpers\ConfigHelper::durationInSeconds()](https://docs.craftcms.com/api/v3/craft-helpers-confighelper.html#method-durationinseconds) for a list of supported value types.



### `invalidUserTokenPath`

Allowed types

:   `mixed`

Default value

:   `''`

Defined by

:   [GeneralConfig::$invalidUserTokenPath](api:craft\config\GeneralConfig::$invalidUserTokenPath)



The URI Craft should redirect to when user token validation fails. A token is used on things like setting and
resetting user account passwords. Note that this only affects front-end site requests.

See [craft\helpers\ConfigHelper::localizedValue()](https://docs.craftcms.com/api/v3/craft-helpers-confighelper.html#method-localizedvalue) for a list of supported value types.



### `ipHeaders`

Allowed types

:   [string](http://php.net/language.types.string)[], [null](http://php.net/language.types.null)

Default value

:   `null`

Defined by

:   [GeneralConfig::$ipHeaders](api:craft\config\GeneralConfig::$ipHeaders)



List of headers where proxies store the real client IP.

See [yii\web\Request::$ipHeaders](https://www.yiiframework.com/doc/api/2.0/yii-web-request#$ipHeaders-detail) for more details.

If not set, the default [craft\web\Request::$ipHeaders](https://docs.craftcms.com/api/v3/craft-web-request.html#ipheaders) value will be used.



### `isSystemLive`

Allowed types

:   [boolean](http://php.net/language.types.boolean), [null](http://php.net/language.types.null)

Default value

:   `null`

Defined by

:   [GeneralConfig::$isSystemLive](api:craft\config\GeneralConfig::$isSystemLive)



Whether the site is currently live. If set to `true` or `false`, it will take precedence over the
System Status setting in Settings → General.



### `limitAutoSlugsToAscii`

Allowed types

:   [boolean](http://php.net/language.types.boolean)

Default value

:   `false`

Defined by

:   [GeneralConfig::$limitAutoSlugsToAscii](api:craft\config\GeneralConfig::$limitAutoSlugsToAscii)



Whether non-ASCII characters in auto-generated slugs should be converted to ASCII (i.e. ñ → n).

::: tip
This only affects the JavaScript auto-generated slugs. Non-ASCII characters can still be used in slugs if entered manually.
:::



### `loginPath`

Allowed types

:   `mixed`

Default value

:   `'login'`

Defined by

:   [GeneralConfig::$loginPath](api:craft\config\GeneralConfig::$loginPath)



The URI Craft should use for user login on the front-end.

See [craft\helpers\ConfigHelper::localizedValue()](https://docs.craftcms.com/api/v3/craft-helpers-confighelper.html#method-localizedvalue) for a list of supported value types.



### `logoutPath`

Allowed types

:   `mixed`

Default value

:   `'logout'`

Defined by

:   [GeneralConfig::$logoutPath](api:craft\config\GeneralConfig::$logoutPath)



The URI Craft should use for user logout on the front-end.

See [craft\helpers\ConfigHelper::localizedValue()](https://docs.craftcms.com/api/v3/craft-helpers-confighelper.html#method-localizedvalue) for a list of supported value types.



### `maxCachedCloudImageSize`

Allowed types

:   [integer](http://php.net/language.types.integer)

Default value

:   `2000`

Defined by

:   [GeneralConfig::$maxCachedCloudImageSize](api:craft\config\GeneralConfig::$maxCachedCloudImageSize)



The maximum dimension size to use when caching images from external sources to use in transforms. Set to `0` to
never cache them.



### `maxInvalidLogins`

Allowed types

:   [integer](http://php.net/language.types.integer)

Default value

:   `5`

Defined by

:   [GeneralConfig::$maxInvalidLogins](api:craft\config\GeneralConfig::$maxInvalidLogins)



The number of invalid login attempts Craft will allow within the specified duration before the account gets
locked.



### `maxSlugIncrement`

Allowed types

:   [integer](http://php.net/language.types.integer)

Default value

:   `100`

Defined by

:   [GeneralConfig::$maxSlugIncrement](api:craft\config\GeneralConfig::$maxSlugIncrement)



The highest number Craft will tack onto a slug in order to make it unique before giving up and throwing an error.



### `maxUploadFileSize`

Allowed types

:   [integer](http://php.net/language.types.integer), [string](http://php.net/language.types.string)

Default value

:   `16777216`

Defined by

:   [GeneralConfig::$maxUploadFileSize](api:craft\config\GeneralConfig::$maxUploadFileSize)



The maximum upload file size allowed.

See [craft\helpers\ConfigHelper::sizeInBytes()](https://docs.craftcms.com/api/v3/craft-helpers-confighelper.html#method-sizeinbytes) for a list of supported value types.



### `omitScriptNameInUrls`

Allowed types

:   [boolean](http://php.net/language.types.boolean)

Default value

:   `false`

Defined by

:   [GeneralConfig::$omitScriptNameInUrls](api:craft\config\GeneralConfig::$omitScriptNameInUrls)



Whether generated URLs should omit `index.php` (e.g. `http://domain.com/path` instead of `http://domain.com/index.php/path`)

This can only be possible if your server is configured to redirect would-be 404's to `index.php`, for example, with
the redirect found in the `.htaccess` file that came with Craft:

```
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule (.+) /index.php?p= [QSA,L]
```



### `optimizeImageFilesize`

Allowed types

:   [boolean](http://php.net/language.types.boolean)

Default value

:   `true`

Defined by

:   [GeneralConfig::$optimizeImageFilesize](api:craft\config\GeneralConfig::$optimizeImageFilesize)



Whether Craft should optimize images for reduced file sizes without noticeably reducing image quality.
(Only supported when ImageMagick is used.)



### `pageTrigger`

Allowed types

:   [string](http://php.net/language.types.string)

Default value

:   `'p'`

Defined by

:   [GeneralConfig::$pageTrigger](api:craft\config\GeneralConfig::$pageTrigger)



The string preceding a number which Craft will look for when determining if the current request is for a
particular page in a paginated list of pages.

Example Value | Example URI
------------- | -----------
`p` | `/news/p5`
`page` | `/news/page5`
`page/` | `/news/page/5`
`?page` | `/news?page=5`

::: tip
If you want to set this to `?p` (e.g. `/news?p=5`), you will need to change your [pathParam](https://docs.craftcms.com/api/v3/craft-config-generalconfig.html#pathparam) setting as well,
which is set to `p` by default, and if your server is running Apache, you will need to update the redirect code
in your `.htaccess` file to match your new `pathParam` value.
:::



### `pathParam`

Allowed types

:   [string](http://php.net/language.types.string)

Default value

:   `'p'`

Defined by

:   [GeneralConfig::$pathParam](api:craft\config\GeneralConfig::$pathParam)



The query string param that Craft will check when determining the request's path.

::: tip
If you change this and your server is running Apache, don’t forget to update the redirect code in your
`.htaccess` file to match the new value.
:::



### `phpMaxMemoryLimit`

Allowed types

:   [string](http://php.net/language.types.string), [null](http://php.net/language.types.null)

Default value

:   `null`

Defined by

:   [GeneralConfig::$phpMaxMemoryLimit](api:craft\config\GeneralConfig::$phpMaxMemoryLimit)



The maximum amount of memory Craft will try to reserve during memory intensive operations such as zipping,
unzipping and updating. Defaults to an empty string, which means it will use as much memory as it possibly can.

See <http://php.net/manual/en/faq.using.php#faq.using.shorthandbytes> for a list of acceptable values.



### `phpSessionName`

Allowed types

:   [string](http://php.net/language.types.string)

Default value

:   `'CraftSessionId'`

Defined by

:   [GeneralConfig::$phpSessionName](api:craft\config\GeneralConfig::$phpSessionName)



The name of the PHP session cookie.



### `postCpLoginRedirect`

Allowed types

:   `mixed`

Default value

:   `'dashboard'`

Defined by

:   [GeneralConfig::$postCpLoginRedirect](api:craft\config\GeneralConfig::$postCpLoginRedirect)



The path that users should be redirected to after logging in from the Control Panel.

This setting will also come into effect if the user visits the CP’s Login page (`/admin/login`)
or the CP’s root URL (/admin) when they are already logged in.

See [craft\helpers\ConfigHelper::localizedValue()](https://docs.craftcms.com/api/v3/craft-helpers-confighelper.html#method-localizedvalue) for a list of supported value types.



### `postLoginRedirect`

Allowed types

:   `mixed`

Default value

:   `''`

Defined by

:   [GeneralConfig::$postLoginRedirect](api:craft\config\GeneralConfig::$postLoginRedirect)



The path that users should be redirected to after logging in from the front-end site.

This setting will also come into effect if the user visits the Login page (as specified by the loginPath config
setting) when they are already logged in.

See [craft\helpers\ConfigHelper::localizedValue()](https://docs.craftcms.com/api/v3/craft-helpers-confighelper.html#method-localizedvalue) for a list of supported value types.



### `postLogoutRedirect`

Allowed types

:   `mixed`

Default value

:   `''`

Defined by

:   [GeneralConfig::$postLogoutRedirect](api:craft\config\GeneralConfig::$postLogoutRedirect)



The path that users should be redirected to after logging out from the front-end site.

See [craft\helpers\ConfigHelper::localizedValue()](https://docs.craftcms.com/api/v3/craft-helpers-confighelper.html#method-localizedvalue) for a list of supported value types.



### `preserveCmykColorspace`

Allowed types

:   [boolean](http://php.net/language.types.boolean)

Default value

:   `false`

Defined by

:   [GeneralConfig::$preserveCmykColorspace](api:craft\config\GeneralConfig::$preserveCmykColorspace)



Whether CMYK should be preserved as the colorspace when when manipulating images.

Setting this to `true` will prevent Craft from transforming CMYK images to sRGB, but on some ImageMagick versions can cause color
distortion in the image. This will only have effect if ImageMagick is in use.



### `preserveExifData`

Allowed types

:   [boolean](http://php.net/language.types.boolean)

Default value

:   `false`

Defined by

:   [GeneralConfig::$preserveExifData](api:craft\config\GeneralConfig::$preserveExifData)



Whether the EXIF data should be preserved when manipulating images.

Setting this to `true` will result in larger image file sizes.

This will only have effect if ImageMagick is in use.



### `preserveImageColorProfiles`

Allowed types

:   [boolean](http://php.net/language.types.boolean)

Default value

:   `true`

Defined by

:   [GeneralConfig::$preserveImageColorProfiles](api:craft\config\GeneralConfig::$preserveImageColorProfiles)



Whether the embedded Image Color Profile (ICC) should be preserved when manipulating images.

Setting this to `false` will reduce the image size a little bit, but on some ImageMagick versions can cause images to be saved with
an incorrect gamma value, which causes the images to become very dark. This will only have effect if ImageMagick is in use.



### `preventUserEnumeration`

Allowed types

:   [boolean](http://php.net/language.types.boolean)

Default value

:   `false`

Defined by

:   [GeneralConfig::$preventUserEnumeration](api:craft\config\GeneralConfig::$preventUserEnumeration)



When set to `false` and you go through the "forgot password" workflow on the Control Panel login page, for example,
you get distinct messages saying if the username/email didn't exist or the email was successfully sent and to check
your email for further instructions. This can allow for username/email enumeration based on the response. If set
`true`, you will always get a successful response even if there was an error making it difficult to enumerate users.



### `privateTemplateTrigger`

Allowed types

:   [string](http://php.net/language.types.string)

Default value

:   `'_'`

Defined by

:   [GeneralConfig::$privateTemplateTrigger](api:craft\config\GeneralConfig::$privateTemplateTrigger)



The template path segment prefix that should be used to identify "private" templates (templates that aren't
directly accessible via a matching URL).

Set to an empty value to disable public template routing.



### `purgePendingUsersDuration`

Allowed types

:   `mixed`

Default value

:   `null`

Defined by

:   [GeneralConfig::$purgePendingUsersDuration](api:craft\config\GeneralConfig::$purgePendingUsersDuration)



The amount of time to wait before Craft purges pending users from the system that have not activated.

Note that any content assigned to a pending user will be deleted as well when the given time interval passes.

Set to `0` to disable this feature.

See [craft\helpers\ConfigHelper::durationInSeconds()](https://docs.craftcms.com/api/v3/craft-helpers-confighelper.html#method-durationinseconds) for a list of supported value types.



### `rememberUsernameDuration`

Allowed types

:   `mixed`

Default value

:   `31536000`

Defined by

:   [GeneralConfig::$rememberUsernameDuration](api:craft\config\GeneralConfig::$rememberUsernameDuration)



The amount of time Craft will remember a username and pre-populate it on the CP login page.

Set to `0` to disable this feature altogether.

See [craft\helpers\ConfigHelper::durationInSeconds()](https://docs.craftcms.com/api/v3/craft-helpers-confighelper.html#method-durationinseconds) for a list of supported value types.



### `rememberedUserSessionDuration`

Allowed types

:   `mixed`

Default value

:   `1209600`

Defined by

:   [GeneralConfig::$rememberedUserSessionDuration](api:craft\config\GeneralConfig::$rememberedUserSessionDuration)



The amount of time a user stays logged if “Remember Me” is checked on the login page.

Set to `0` to disable the “Remember Me” feature altogether.

See [craft\helpers\ConfigHelper::durationInSeconds()](https://docs.craftcms.com/api/v3/craft-helpers-confighelper.html#method-durationinseconds) for a list of supported value types.



### `requireMatchingUserAgentForSession`

Allowed types

:   [boolean](http://php.net/language.types.boolean)

Default value

:   `true`

Defined by

:   [GeneralConfig::$requireMatchingUserAgentForSession](api:craft\config\GeneralConfig::$requireMatchingUserAgentForSession)



Whether Craft should require a matching user agent string when restoring a user session from a cookie.



### `requireUserAgentAndIpForSession`

Allowed types

:   [boolean](http://php.net/language.types.boolean)

Default value

:   `true`

Defined by

:   [GeneralConfig::$requireUserAgentAndIpForSession](api:craft\config\GeneralConfig::$requireUserAgentAndIpForSession)



Whether Craft should require the existence of a user agent string and IP address when creating a new user
session.



### `resourceBasePath`

Allowed types

:   [string](http://php.net/language.types.string)

Default value

:   `'@webroot/cpresources'`

Defined by

:   [GeneralConfig::$resourceBasePath](api:craft\config\GeneralConfig::$resourceBasePath)



The path to the root directory that should store published CP resources.



### `resourceBaseUrl`

Allowed types

:   [string](http://php.net/language.types.string)

Default value

:   `'@web/cpresources'`

Defined by

:   [GeneralConfig::$resourceBaseUrl](api:craft\config\GeneralConfig::$resourceBaseUrl)



The URL to the root directory that should store published CP resources.



### `restoreCommand`

Allowed types

:   [string](http://php.net/language.types.string), [null](http://php.net/language.types.null)

Default value

:   `null`

Defined by

:   [GeneralConfig::$restoreCommand](api:craft\config\GeneralConfig::$restoreCommand)



The shell command that Craft should execute to restore a database backup.

By default Craft will run `mysql` or `psql`, provided that those libraries are in the `$PATH`
variable for the user the web server  is running as.

There are several tokens you can use that Craft will swap out at runtime:

- `{path}` - the backup file path
- `{port}` - the current database port
- `{server}` - the current database host name
- `{user}` - the user to connect to the database
- `{database}` - the current database name
- `{schema}` - the current database schema (if any)

This can also be set to `false` to disable database restores completely.



### `rotateImagesOnUploadByExifData`

Allowed types

:   [boolean](http://php.net/language.types.boolean)

Default value

:   `true`

Defined by

:   [GeneralConfig::$rotateImagesOnUploadByExifData](api:craft\config\GeneralConfig::$rotateImagesOnUploadByExifData)



Whether Craft should rotate images according to their EXIF data on upload.



### `runQueueAutomatically`

Allowed types

:   [boolean](http://php.net/language.types.boolean)

Default value

:   `true`

Defined by

:   [GeneralConfig::$runQueueAutomatically](api:craft\config\GeneralConfig::$runQueueAutomatically)



Whether Craft should run pending queue jobs automatically over HTTP requests.

This setting should be disabled for servers running Win32, or with Apache’s mod_deflate/mod_gzip installed,
where PHP’s [flush()](http://php.net/manual/en/function.flush.php) method won’t work.

If disabled, an alternate queue runner *must* be set up separately.

Here is an example of how you would setup a queue runner from a cron job that ran every minute:

```text
/1 * * * * /path/to/project/root/craft queue/run
```



### `sanitizeSvgUploads`

Allowed types

:   [boolean](http://php.net/language.types.boolean)

Default value

:   `true`

Defined by

:   [GeneralConfig::$sanitizeSvgUploads](api:craft\config\GeneralConfig::$sanitizeSvgUploads)



Whether Craft should sanitize uploaded SVG files and strip out potential malicious looking content.

This should definitely be enabled if you are accepting SVG uploads from untrusted sources.



### `secureHeaders`

Allowed types

:   [array](http://php.net/language.types.array), [null](http://php.net/language.types.null)

Default value

:   `null`

Defined by

:   [GeneralConfig::$secureHeaders](api:craft\config\GeneralConfig::$secureHeaders)



Lists of headers that are, by default, subject to the trusted host configuration.

See [yii\web\Request::$secureHeaders](https://www.yiiframework.com/doc/api/2.0/yii-web-request#$secureHeaders-detail) for more details.

If not set, the default [yii\web\Request::$secureHeaders](https://www.yiiframework.com/doc/api/2.0/yii-web-request#$secureHeaders-detail) value will be used.



### `secureProtocolHeaders`

Allowed types

:   [array](http://php.net/language.types.array), [null](http://php.net/language.types.null)

Default value

:   `null`

Defined by

:   [GeneralConfig::$secureProtocolHeaders](api:craft\config\GeneralConfig::$secureProtocolHeaders)



List of headers to check for determining whether the connection is made via HTTPS.

See [yii\web\Request::$secureProtocolHeaders](https://www.yiiframework.com/doc/api/2.0/yii-web-request#$secureProtocolHeaders-detail) for more details.

If not set, the default [yii\web\Request::$secureProtocolHeaders](https://www.yiiframework.com/doc/api/2.0/yii-web-request#$secureProtocolHeaders-detail) value will be used.



### `securityKey`

Allowed types

:   [string](http://php.net/language.types.string)

Default value

:   `null`

Defined by

:   [GeneralConfig::$securityKey](api:craft\config\GeneralConfig::$securityKey)



A private, random, cryptographically-secure key that is used for hashing and encrypting
data in [craft\services\Security](api:craft\services\Security).

This value should be the same across all environments. Note that if this key ever changes, any data that
was encrypted with it will be inaccessible.



### `sendPoweredByHeader`

Allowed types

:   [boolean](http://php.net/language.types.boolean)

Default value

:   `true`

Defined by

:   [GeneralConfig::$sendPoweredByHeader](api:craft\config\GeneralConfig::$sendPoweredByHeader)



Whether an `X-Powered-By: Craft CMS` header should be sent, helping services like [BuiltWith](https://builtwith.com/) and [Wappalyzer](https://www.wappalyzer.com/) identify that the site is running on Craft.



### `setPasswordPath`

Allowed types

:   `mixed`

Default value

:   `'setpassword'`

Defined by

:   [GeneralConfig::$setPasswordPath](api:craft\config\GeneralConfig::$setPasswordPath)



The password-reset template path. Note that this only affects front-end site requests.

See [craft\helpers\ConfigHelper::localizedValue()](https://docs.craftcms.com/api/v3/craft-helpers-confighelper.html#method-localizedvalue) for a list of supported value types.



### `setPasswordSuccessPath`

Allowed types

:   `mixed`

Default value

:   `''`

Defined by

:   [GeneralConfig::$setPasswordSuccessPath](api:craft\config\GeneralConfig::$setPasswordSuccessPath)



The URI Craft should redirect users to after setting their password from the front-end.

See [craft\helpers\ConfigHelper::localizedValue()](https://docs.craftcms.com/api/v3/craft-helpers-confighelper.html#method-localizedvalue) for a list of supported value types.



### `siteName`

Allowed types

:   [string](http://php.net/language.types.string), [string](http://php.net/language.types.string)[]

Default value

:   `null`

Defined by

:   [GeneralConfig::$siteName](api:craft\config\GeneralConfig::$siteName)



The site name(s). If set, it will take precedence over the Name settings in Settings → Sites → [Site Name].

This can be set to a string, which will override the primary site’s name only, or an array with site handles used as the keys.



### `siteUrl`

Allowed types

:   [string](http://php.net/language.types.string), [string](http://php.net/language.types.string)[]

Default value

:   `null`

Defined by

:   [GeneralConfig::$siteUrl](api:craft\config\GeneralConfig::$siteUrl)



The base URL to the site(s). If set, it will take precedence over the Base URL settings in Settings → Sites → [Site Name].

This can be set to a string, which will override the primary site’s base URL only, or an array with site handles used as the keys.

The URL(s) must begin with either `http://`, `https://`, `//` (protocol-relative), or an [alias](https://docs.craftcms.com/api/v3/craft-config-generalconfig.html#aliases).

```php
'siteUrl' => [
    'siteA' => 'https://site-a.com/',
    'siteB' => 'https://site-b.com/',
],
```



### `slugWordSeparator`

Allowed types

:   [string](http://php.net/language.types.string)

Default value

:   `'-'`

Defined by

:   [GeneralConfig::$slugWordSeparator](api:craft\config\GeneralConfig::$slugWordSeparator)



The character(s) that should be used to separate words in slugs.



### `softDeleteDuration`

Allowed types

:   `mixed`

Default value

:   `2592000`

Defined by

:   [GeneralConfig::$softDeleteDuration](api:craft\config\GeneralConfig::$softDeleteDuration)



The amount of time before a soft-deleted item will be up for hard-deletion by garbage collection.

Set to `0` if you don’t ever want to delete soft-deleted items.

See [craft\helpers\ConfigHelper::durationInSeconds()](https://docs.craftcms.com/api/v3/craft-helpers-confighelper.html#method-durationinseconds) for a list of supported value types.



### `storeUserIps`

Allowed types

:   [boolean](http://php.net/language.types.boolean)

Default value

:   `false`

Defined by

:   [GeneralConfig::$storeUserIps](api:craft\config\GeneralConfig::$storeUserIps)



Whether user IP addresses should be stored/logged by the system.



### `suppressTemplateErrors`

Allowed types

:   [boolean](http://php.net/language.types.boolean)

Default value

:   `false`

Defined by

:   [GeneralConfig::$suppressTemplateErrors](api:craft\config\GeneralConfig::$suppressTemplateErrors)



Whether Twig runtime errors should be suppressed.

If it is set to `true`, the errors will still be logged to Craft’s log files.



### `testToEmailAddress`

Allowed types

:   [string](http://php.net/language.types.string), [array](http://php.net/language.types.array), [false](http://php.net/language.types.boolean), [null](http://php.net/language.types.null)

Default value

:   `null`

Defined by

:   [GeneralConfig::$testToEmailAddress](api:craft\config\GeneralConfig::$testToEmailAddress)



Configures Craft to send all system emails to a single email address, or an array of email addresses for testing
purposes.

By default the recipient name(s) will be “Test Recipient”, but you can customize that by setting the value with the format `['email@address.com' => 'Name']`.



### `timezone`

Allowed types

:   [string](http://php.net/language.types.string), [null](http://php.net/language.types.null)

Default value

:   `null`

Defined by

:   [GeneralConfig::$timezone](api:craft\config\GeneralConfig::$timezone)



The timezone of the site. If set, it will take precedence over the Timezone setting in Settings → General.

This can be set to one of PHP’s [supported timezones](http://php.net/manual/en/timezones.php).



### `tokenParam`

Allowed types

:   [string](http://php.net/language.types.string)

Default value

:   `'token'`

Defined by

:   [GeneralConfig::$tokenParam](api:craft\config\GeneralConfig::$tokenParam)



The query string parameter name that Craft tokens should be set to.



### `transformGifs`

Allowed types

:   [boolean](http://php.net/language.types.boolean)

Default value

:   `true`

Defined by

:   [GeneralConfig::$transformGifs](api:craft\config\GeneralConfig::$transformGifs)



Whether GIF files should be cleansed/transformed.



### `translationDebugOutput`

Allowed types

:   [boolean](http://php.net/language.types.boolean)

Default value

:   `false`

Defined by

:   [GeneralConfig::$translationDebugOutput](api:craft\config\GeneralConfig::$translationDebugOutput)



Whether translated messages should be wrapped in special characters, to help find any strings that are not
being run through `Craft::t()` or the `|translate` filter.



### `trustedHosts`

Allowed types

:   [array](http://php.net/language.types.array)

Default value

:   `['any']`

Defined by

:   [GeneralConfig::$trustedHosts](api:craft\config\GeneralConfig::$trustedHosts)



The configuration for trusted security-related headers.

See [yii\web\Request::$trustedHosts](https://www.yiiframework.com/doc/api/2.0/yii-web-request#$trustedHosts-detail) for more details.

By default, all hosts are trusted.



### `useCompressedJs`

Allowed types

:   [boolean](http://php.net/language.types.boolean)

Default value

:   `true`

Defined by

:   [GeneralConfig::$useCompressedJs](api:craft\config\GeneralConfig::$useCompressedJs)



Whether Craft should use compressed JavaScript files whenever possible.



### `useEmailAsUsername`

Allowed types

:   [boolean](http://php.net/language.types.boolean)

Default value

:   `false`

Defined by

:   [GeneralConfig::$useEmailAsUsername](api:craft\config\GeneralConfig::$useEmailAsUsername)



Whether Craft should set users’ usernames to their email addresses, rather than let them set their username separately.



### `useFileLocks`

Allowed types

:   [boolean](http://php.net/language.types.boolean), [null](http://php.net/language.types.null)

Default value

:   `null`

Defined by

:   [GeneralConfig::$useFileLocks](api:craft\config\GeneralConfig::$useFileLocks)



Whether to grab an exclusive lock on a file when writing to it by using the `LOCK_EX` flag.

Some file systems, such as NFS, do not support exclusive file locking.

If not set to `true` or `false`, Craft will automatically try to detect if the underlying file system supports exclusive file
locking and cache the results.



### `usePathInfo`

Allowed types

:   [boolean](http://php.net/language.types.boolean)

Default value

:   `false`

Defined by

:   [GeneralConfig::$usePathInfo](api:craft\config\GeneralConfig::$usePathInfo)



Whether Craft should specify the path using `PATH_INFO` or as a query string parameter when generating URLs.

Note that this setting only takes effect if [omitScriptNameInUrls](https://docs.craftcms.com/api/v3/craft-config-generalconfig.html#omitscriptnameinurls) is set to false.



### `useProjectConfigFile`

Allowed types

:   [boolean](http://php.net/language.types.boolean)

Default value

:   `false`

Defined by

:   [GeneralConfig::$useProjectConfigFile](api:craft\config\GeneralConfig::$useProjectConfigFile)



Whether the project config should be saved out to `config/project.yaml`.

If set to `true`, a hard copy of your system’s project config will be saved in `config/project.yaml`,
and any changes to `config/project.yaml` will be applied back to the system, making it possible for
multiple environments to share the same project config despite having separate databases.



### `useSecureCookies`

Allowed types

:   [boolean](http://php.net/language.types.boolean), [string](http://php.net/language.types.string)

Default value

:   `'auto'`

Defined by

:   [GeneralConfig::$useSecureCookies](api:craft\config\GeneralConfig::$useSecureCookies)



Whether Craft will set the "secure" flag when saving cookies when using `Craft::cookieConfig() to create a cookie`.

Valid values are `true`, `false`, and `'auto'`. Defaults to `'auto'`, which will set the secure flag if the page
you're currently accessing is over `https://`. `true` will always set the flag, regardless of protocol and `false`
will never automatically set the flag.



### `useSslOnTokenizedUrls`

Allowed types

:   [boolean](http://php.net/language.types.boolean), [string](http://php.net/language.types.string)

Default value

:   `'auto'`

Defined by

:   [GeneralConfig::$useSslOnTokenizedUrls](api:craft\config\GeneralConfig::$useSslOnTokenizedUrls)



Determines what protocol/schema Craft will use when generating tokenized URLs. If set to `'auto'`,
Craft will check the siteUrl and the protocol of the current request and if either of them are https
will use `https` in the tokenized URL. If not, will use `http`.

If set to `false`, the Craft will always use `http`. If set to `true`, then, Craft will always use `https`.



### `userSessionDuration`

Allowed types

:   `mixed`

Default value

:   `3600`

Defined by

:   [GeneralConfig::$userSessionDuration](api:craft\config\GeneralConfig::$userSessionDuration)



The amount of time before a user will get logged out due to inactivity.

Set to `0` if you want users to stay logged in as long as their browser is open rather than a predetermined
amount of time.

See [craft\helpers\ConfigHelper::durationInSeconds()](https://docs.craftcms.com/api/v3/craft-helpers-confighelper.html#method-durationinseconds) for a list of supported value types.



### `verificationCodeDuration`

Allowed types

:   `mixed`

Default value

:   `86400`

Defined by

:   [GeneralConfig::$verificationCodeDuration](api:craft\config\GeneralConfig::$verificationCodeDuration)



The amount of time a user verification code can be used before expiring.

See [craft\helpers\ConfigHelper::durationInSeconds()](https://docs.craftcms.com/api/v3/craft-helpers-confighelper.html#method-durationinseconds) for a list of supported value types.




<!-- END SETTINGS -->
