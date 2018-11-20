# Release Notes for Craft CMS 3.x

## 3.0.32 - 2018-11-20

### Added
- The `seq()` Twig function now has a `next` argument, which can be set to `false` to have it return the current number in the sequence without incrementing it. ([#3466](https://github.com/craftcms/cms/issues/3466))
- Added `craft\db\MigrationManager::truncateHistory()`.
- Added `craft\helpers\Sequence::current()`.

### Changed
- Edit Entry pages now show the entry’s site in the revision menu label so long as the section is enabled for multiple sites, even if “Propagate entries across all enabled sites?” isn’t checked. ([#3471](https://github.com/craftcms/cms/issues/3471))
- Exact-match search terms (using `::`) now disable `subLeft` and `subRight` attributes by default, regardless of the `defaultSearchTermOptions` config setting says. ([#3474](https://github.com/craftcms/cms/issues/3474))

### Deprecated
- Deprecated `craft\validators\StringValidator::$trim`. Yii’s `'trim'` validator should be used instead.

### Fixed
- Fixed an error that occurred when querying for Matrix blocks if both the `with` and `indexBy` parameters were set.
- Fixed an error that occurred when running the `migrate/fresh` console command. ([#3472](https://github.com/craftcms/cms/issues/3472))

## 3.0.31 - 2018-11-13

### Added
- Added the `seq()` Twig function, for outputting sequential numbers.
- Added `craft\helpers\Sequence`.

### Changed
- Control Panel templates can now customize `#main-form` HTML attributes by overriding the `mainFormAttributes` block. ([#1665](https://github.com/craftcms/cms/issues/1665))
- The default PostgreSQL backup command no longer includes database owner, privilege or ACL information in the backup.
- Craft now attempts to reset OPcache after installing/uninstalling things with Composer. ([#3460](https://github.com/craftcms/cms/issues/3460))
- Gmail and SMTP mail transport types now trim whitespace off of their Username, Password, and Host Name settings. ([#3459](https://github.com/craftcms/cms/issues/3459))

### Fixed
- Fixed an error that could occur when duplicating an element with a Matrix field with “Manage blocks on a per-site basis” disabled.
- Fixed a bug where Matrix blocks wouldn’t retain their content translations when an entry was duplicated from the Edit Entry page.
- Fixed a bug where system message modals could have the wrong language selected by default. ([#3440](https://github.com/craftcms/cms/issues/3440))
- Fixed a bug where an Internal Server Error would occur if a `users/login` request was missing the `loginName` or `password` parameters. ([#3458](https://github.com/craftcms/cms/issues/3458))
- Fixed a bug where `craft\validators\StringValidator` was trimming whitespace off of strings _after_ performing string length validation.
- Fixed an infinite recursion bug that could occur if `config/general.php` had any deprecated config settings, and the database connection settings were invalid.
- Fixed an error that occurred when saving a new entry or category, if its URI format referenced the `level` attribute. ([#3465](https://github.com/craftcms/cms/issues/3465))

## 3.0.30.2 - 2018-11-08

### Fixed
- Fixed an error that could occur on servers running PHP 7.0.32. ([#3453](https://github.com/craftcms/cms/issues/3453))

## 3.0.30.1 - 2018-11-07

### Fixed
- Fixed an error that occurred when saving an element with a new Matrix block, if the Matrix field was set to manage blocks on a per-site basis. ([#3445](https://github.com/craftcms/cms/issues/3445))

## 3.0.30 - 2018-11-06

### Added
- Added “Duplicate” and “Duplicate (with children)” actions to the Entries and Categories index pages. ([#1291](https://github.com/craftcms/cms/issues/1291))
- Added `craft\base\ElementAction::$elementType`, which element action classes can use to reference their associated element type.
- Added `craft\elements\actions\DeepDuplicate`.
- Added `craft\elements\actions\Duplicate`.
- Added `craft\elements\actions\SetStatus::$allowDisabledForSite`, which can be used by localizable element types to enable a “Disabled for Site” status option.

### Changed
- Entries’ “Enabled” setting is now labeled “Enabled Globally” on multi-site installs. ([#2899](https://github.com/craftcms/cms/issues/2899))
- Entries’ “Enabled for site” setting now includes the site name in its label, and only shows up if the “Enabled Globally” setting is checked. ([#2899](https://github.com/craftcms/cms/issues/2899))
- The Set Status action on the Entries index page now includes a “Disabled for Site” option. ([#2899](https://github.com/craftcms/cms/issues/2899))
- Edit Category pages now have `edit-category` and `site--<SiteHandle>` classes on the `<body>`. ([#3439](https://github.com/craftcms/cms/issues/3439))
- Edit Entry pages now have `edit-entry` and `site--<SiteHandle>` classes on the `<body>`. ([#3439](https://github.com/craftcms/cms/issues/3439))
- Edit Global Set pages now have `edit-global-set` and `site--<SiteHandle>` classes on the `<body>`. ([#3439](https://github.com/craftcms/cms/issues/3439))
- Edit User pages now have an `edit-user` class on the `<body>`. ([#3439](https://github.com/craftcms/cms/issues/3439))

### Fixed
- Fixed a bug where the Edit User page could forget which permissions were selected when saving a user with validation errors, if the Username, First Name, and Last name fields were all blank. ([#3412](https://github.com/craftcms/cms/issues/3412))
- Fixed a bug where the Edit User Group page could forget which permissions were selected when saving a user group with validation errors, if the Name field was blank.
- Fixed a bug where the `{% paginate %}` tag wasn’t factoring the `offset` element query param into its total page calculation. ([#3420](https://github.com/craftcms/cms/issues/3420))

### Security
- Fixed a bug where sensitive info could be displayed in the Craft log files if there was a problem connecting to the email server.

## 3.0.29 - 2018-10-30

### Added
- Email and URL fields now have “Placeholder Text” settings. ([#3397](https://github.com/craftcms/cms/issues/3397))

### Changed
- The default HTML Purifier configuration now allows `download` attributes in `<a>` tags. ([craftcms/redactor#86](https://github.com/craftcms/redactor/issues/86))

### Fixed
- Fixed a bug where the `ContentBehaviour` and `ElementQueryBehavior` classes could be missing some field properties. ([#3400](https://github.com/craftcms/cms/issues/3400))
- Fixed a bug where some fields within Matrix fields could lose their values after enabling the “Manage blocks on a per-site basis” setting. ([verbb/super-table#203](https://github.com/verbb/super-table/issues/203))
- Fixed a bug where HTML Purifier wasn’t being initialized with HTML 5 element support.
- Fixed a bug where it was possible to save Assets fields with the “Restrict allowed file types?” setting enabled, but no specific file types selected. ([#3410](https://github.com/craftcms/cms/issues/3410))

## 3.0.28 - 2018-10-23

### Added
- Structure sections now have the ability to disable entry propagation, like Channel sections. ([#2386](https://github.com/craftcms/cms/issues/2386))

### Changed
- `craft\base\Field::supportedTranslationMethods()` now defaults to only returning `none` if the field type doesn’t have a content column. ([#3385](https://github.com/craftcms/cms/issues/3385))
- Craft.EntryTypeSwitcher now fires a `beforeTypeChange` event before swapping the Edit Entry form tabs. ([#3375](https://github.com/craftcms/cms/pull/3375))
- Craft.MatrixInput now fires an `afterInit` event after initialization. ([#3375](https://github.com/craftcms/cms/pull/3375))
- Craft.MatrixInput now fires an `blockAdded` event after adding a new block. ([#3375](https://github.com/craftcms/cms/pull/3375))
- System messages sent from front-end requests are now sent using the current site’s language. ([#3388](https://github.com/craftcms/cms/issues/3388))

### Fixed
- Fixed an error that could occur when acquiring a lock for a file path, if the `mutex` component was swapped out with `yii\mutex\MysqlMutex`.

## 3.0.27.1 - 2018-10-12

### Fixed
- Fixed an error that occurred when deleting an entry from the Edit Entry page. ([#3372](https://github.com/craftcms/cms/issues/3372))
- Fixed an error that could occur when changing a Channel section to Structure. ([#3373](https://github.com/craftcms/cms/issues/3373))
- Fixed an error that occurred when saving Matrix content from console requests.

## 3.0.27 - 2018-10-11

### Added
- Added `craft\helpers\MigrationHelper::findForeignKey()`.
- Added the `cp.globals.edit` and `cp.globals.edit.content` template hooks to the Edit Global Set page. ([#3356](https://github.com/craftcms/cms/pull/3356))

### Changed
- It’s now possible to load a Create Entry page with a specific user preselected in the Author field, using a new `authorId` query string param. ([#3326](https://github.com/craftcms/cms/pull/3326))
- Matrix fields that are set to manage blocks on a per-site basis will now duplicate Matrix blocks across all of the owner element’s supported sites when the element is first created. ([#3082](https://github.com/craftcms/cms/issues/3082))
- Disabled Matrix blocks are no longer visible when sharing an entry draft or version. ([#3338](https://github.com/craftcms/cms/issues/3338))
- Control Panel tabs that have errors now have alert icons.
- The Debug Toolbar is no longer shown in Live Preview iframes.
- The Plugin Store now requires browsers with ES6 support.
- Updated jQuery Touch Events to 2.0.0.
- Updated Garnish to 0.1.29.

### Fixed
- Fixed a bug where enabling the “Propagate entries across all enabled sites?” setting for an existing Channel section (or converting the section to a Structure) wouldn’t update entries that had been created for the non-primary site.
- Fixed a bug where Craft wasn’t detecting and retrying queue jobs that had timed out.
- Fixed a bug where `Craft::$app->locale` could return the wrong locale during Live Preview requests. ([#3336](https://github.com/craftcms/cms/issues/3336))
- Fixed a SQL error that could occur when upgrading to Craft 3, if a foreign key had an unexpected name.
- Fixed a bug where page titles in the Control Panel could be blank when showing validation errors for things that were missing their name or title. ([#3344](https://github.com/craftcms/cms/issues/3344))
- Fixed an error that could occur if a component’s settings were stored as `null`. ([#3342](https://github.com/craftcms/cms/pull/3342))
- Fixed a bug where details panes weren’t visible on browser windows sized between 999 and 1,223 pixels wide.
- Fixed an error that occurred if a Quick Post widget contained a Matrix field that had Min Blocks set and only had one block type.
- Fixed a bug where disabled Matrix blocks were getting validated as live. ([#3354](https://github.com/craftcms/cms/issues/3354))
- Fixed a bug where the `EVENT_AFTER_ACTIVATE_USER` event wasn’t getting triggered on user registration when email verification isn’t required. ([craftcms/commerce-digital-products#18](https://github.com/craftcms/commerce-digital-products/issues/18))
- Added garbage collection for offline storage of remote assets. ([#3335](https://github.com/craftcms/cms/pull/3335))
- Fixed a bug where Twig could end up in a strange state if an error occurred when preparing to render an object template. ([#3364](https://github.com/craftcms/cms/issues/3364))

### Security
- The `svg()` Twig function no longer sanitizes SVGs or namespaces their IDs or class names by default when a file path (or alias) was passed in. ([#3337](https://github.com/craftcms/cms/issues/3337))

## 3.0.26.1 - 2018-09-29

### Changed
- Changed the `yiisoft/yii2-queue` version requirement to `2.1.0`. ([#3332](https://github.com/craftcms/cms/issues/3332))

## 3.0.26 - 2018-09-29

### Changed
- `ancestors`, `descendants`, `nextSibling`, `parent`, and `prevSibling` are now reserved field handles.
- The `svg()` Twig function namespaces class names in addition to IDs now.
- Changed the `yiisoft/yii2-queue` version requirement to `2.0.1`. ([#3332](https://github.com/craftcms/cms/issues/3332))

### Fixed
- Fixed a validation error that could occur when saving an entry as a new entry if the URI format didn’t contain a `{slug}` tag. ([#3320](https://github.com/craftcms/cms/issues/3320))
- Fixed a SQL error that could occur if a deprecation error occurred when attempting to upgrade a Craft 2 project. ([#3324](https://github.com/craftcms/cms/issues/3324))

## 3.0.25 - 2018-09-18

### Added
- Added `craft\log\FileTarget::$includeUserIp` which determines whether users’ IP addresses should be included in the logs (`false` by default). ([#3310](https://github.com/craftcms/cms/pull/3310))

### Fixed
- Fixed an error that could occur when installing or updating something within the Control Panel if `composer.json` required the `roave/security-advisories` package.
- Fixed a SQL error that could occur when searching elements on PostgreSQL installs.
- Fixed a bug where Craft would ignore the last segment of template paths that ended in `/0`. ([#3304](https://github.com/craftcms/cms/issues/3304))
- Fixed a Twig Template Loading Error that would occur when testing email settings, if a custom email template was used and an error occurred when rendering it. ([#3309](https://github.com/craftcms/cms/issues/3309))

## 3.0.24 - 2018-09-11

### Added
- Added the `extraAppLocales` config setting.

### Changed
- The `defaultCpLanguage` config setting no longer needs to be a language that Craft is translated into, as long as it is a valid locale ID.
- Resave Elements jobs that are queued up after saving an entry type now include the section name in the job description. ([#3290](https://github.com/craftcms/cms/issues/3290))
- Updated Garnish to 0.1.28.

### Fixed
- Fixed a SQL error that could occur when an element query’s `orderBy` parameter was set to `dateCreated` or `dateUpdated`.
- Fixed an error that could occur when updating to v3.0.23+ if multiple Matrix fields existed with the same handle, but they had no content tables, somehow.
- Fixed a bug where links in activation and forgot-password emails weren’t hyperlinked, leaving it up to the mail client to hopefully be smart about it. ([#3288](https://github.com/craftcms/cms/issues/3288))

## 3.0.23.1 - 2018-09-04

### Fixed
- Fixed a bug where Matrix fields would get new content tables each time they were saved.

## 3.0.23 - 2018-09-04

### Changed
- Browser-based form validation is now disabled for page forms. ([#3247](https://github.com/craftcms/cms/issues/3247))
- `craft\base\Model::hasErrors()` now supports passing an attribute name with a `.*` suffix, which will return whether any errors exist for the given attribute or any nested model attributes.
- Added `json` to the default `allowedFileExtensions` config setting value. ([#3254](https://github.com/craftcms/cms/issues/3254))
- Exception call stacks now collapse internal Twig methods by default.
- Twig exception call stacks now show all of the steps leading up to the error.
- Live Preview now reloads the preview pane automatically after an asset is saved from the Image Editor. ([#3265](https://github.com/craftcms/cms/issues/3265))

### Deprecated
- Deprecated `craft\services\Matrix::getContentTableName()`. `craft\fields\Matrix::$contentTable` should be used instead.

### Removed
- Removed `craft\services\Matrix::getParentMatrixField()`.

### Fixed
- Fixed a bug where element selection modals could be initialized without a default source selected, if some of the sources were hidden for not being available on the currently-selected site. ([#3227](https://github.com/craftcms/cms/issues/3227))
- Fixed a bug where edit pages for categories, entries, global sets, and users weren’t revealing which tab(s) had errors on it, if the errors occurred within a Matrix field. ([#3248](https://github.com/craftcms/cms/issues/3248))
- Fixed a SQL error that occurred when saving a Matrix field with new sub-fields on PostgreSQL. ([#3252](https://github.com/craftcms/cms/issues/3252))
- Fixed a bug where custom user fields weren’t showing up on the My Account page when running Craft Solo edition. ([#3228](https://github.com/craftcms/cms/issues/3228))
- Fixed a bug where multiple Matrix fields could share the same content table. ([#3249]())
- Fixed a “cache is corrupted” Twig error that could occur when editing or saving an element if it had an Assets field with an unresolvable subfolder path template. ([#3257](https://github.com/craftcms/cms/issues/3257))
- Fixed a bug where the Dev Mode indicator strip wasn’t visible on Chrome/Windows when using a scaled display. ([#3259](https://github.com/craftcms/cms/issues/3259))
- Fixed a SQL error that could occur when validating an attribute using `craft\validators\UniqueValidator`, if the target record’s `find()` method joined in another table.

## 3.0.22 - 2018-08-28

### Changed
- The “Deleting stale template caches” job now ensures all expired template caches have been deleted before it begins processing the caches.
- Text inputs’ `autocomplete` attributes now get set to `off` by default, and they will only not be added if explicitly set to `null`.
- Improved the error response when Composer is unable to perform an update due to a dependency conflict.
- Email fields in the Control Panel now have `type="email"`.
- `craft\helpers\Db::parseParam()` now has a `$caseInnensitive` argument, which can be set to `true` to force case-insensitive conditions on PostgreSQL installs.
- `craft\validators\UniqueValidator` now has a `$caseInsensitive` property, which can be set to `true` to cause the unique validation to be case-insensitive on PostgreSQL installs.
- The CLI setup wizard now detects common database connection errors that occur with MAMP, and automatically retests with adjusted settings.
- The CLI setup wizard now detects common database authentication errors, and lets the user retry the username and password settings, skipping the others.
- Updated Garnish to 0.1.27.

### Fixed
- Fixed a bug where Craft wasn’t reverting `composer.json` to its original state if something went wrong when running a Composer update.
- Fixed a bug where string casing functions in `craft\helpers\StringHelper` were adding extra hyphens to strings that came in as `Upper-Kebab-Case`.
- Fixed a bug where unique validation for element URIs, usernames, and user email address was not case-insensitive on PostgreSQL installs.
- Fixed a bug where element queries’ `uri` params, and user queries’ `firstName`, `lastName`, `username`, and `email` params, were not case-insensitive on PostgreSQL installs.
- Fixed a bug where the CLI setup wizard was allowing empty database names.
- Fixed a bug where it wasn’t possible to clear template caches if template caching was disabled by the `enableTemplateCaching` config setting. ([#3229](https://github.com/craftcms/cms/issues/3229))
- Fixed a bug where element index toolbars weren’t staying fixed to the top of the content area when scrolling down the page. ([#3233](https://github.com/craftcms/cms/issues/3233))
- Fixed an error that could occur when updating Craft if the system was reliant on the SSL certificate provided by the`composer/ca-bundle` package.

## 3.0.21 - 2018-08-21

### Added
- Most element query parameters can now be set to `['not', 'X', 'Y']`, as a shortcut for `['and', 'not X', 'not Y']`.

### Changed
- The “New Password” input on the My Account page now has a “Show” button, like other password inputs in the Control Panel.
- Plugin settings pages now redirect to the Settings index page after save. ([#3216](https://github.com/craftcms/cms/issues/3216))
- It’s now possible to set [autofill detail tokens](https://html.spec.whatwg.org/multipage/form-control-infrastructure.html#autofill-detail-tokens) on the `autocomplete` variable when including the `_includes/forms/text.html` template (e.g. `'name'`).
- Username and password inputs now have the correct `autocomplete` values, increasing the likelihood that tools like 1Password will handle the form correctly. ([#3207](https://github.com/craftcms/cms/issues/3207))

### Fixed
- Fixed a SQL error that occurred when saving a user if a `craft\elements\User::EVENT_BEFORE_SAVE` event listener was setting `$event->isValid = false`. ([#3206](https://github.com/craftcms/cms/issues/3206))
- Fixed a bug where password inputs’ jQuery data was getting erased when the “Show” button was clicked.
- Fixed an error that could occur when upgrading to Craft 3. ([#3208](https://github.com/craftcms/cms/pull/3208))
- Fixed a bug where non-image assets’ file extension icons could bleed out of the preview area within asset editor HUDs. ([#3209](https://github.com/craftcms/cms/issues/3209))
- Fixed a bug where Craft wasn’t saving a new entry version when reverting an entry to a previous version. ([#3210](https://github.com/craftcms/cms/issues/3210))
- Fixed an error that could occur when a Matrix block was saved by a queue job. ([#3217](https://github.com/craftcms/cms/pull/3217))

### Security
- External links in the Control Panel now set `rel="noopener"`. ([#3201](https://github.com/craftcms/cms/issues/3201))

## 3.0.20 - 2018-08-14

### Added
- Added `craft\services\Fields::refreshFields()`.

### Fixed
- Fixed a bug where `DateTime` model attributes were getting converted to ISO-8601 date strings for `craft\web\View::renderObjectTemplate()`. ([#3185](https://github.com/craftcms/cms/issues/3185))
- Fixed a bug where timepicker menus had a higher z-index than session expiration modal shades. ([#3186](https://github.com/craftcms/cms/issues/3186))
- Fixed a bug where users could not log in after upgrading to Craft 3, if there was a custom field named `owner`.
- Fixed a bug where it was not possible to set non-integer values on asset queries’ `width`, `height`, or `size` params. ([#3195](https://github.com/craftcms/cms/issues/3195))
- Fixed a bug where all Asset folders were being initiated at once, resulting in performance issues.

## 3.0.19 - 2018-08-07

### Added
- Added the `craft.query()` template function, for creating new database queries.
- Added `craft\services\Structures::mutexTimeout`. ([#3148](https://github.com/craftcms/cms/issues/3148))
- Added `craft\services\Api::getComposerWhitelist()`.

### Removed
- Removed `craft\services\Api::getOptimizedComposerRequirements()`.

### Fixed
- Craft’s console commands now return the correct exit codes. ([#3175](https://github.com/craftcms/cms/issues/3175))
- Fixed the appearance of checkboxes in IE11 on element index pages. ([#3177](https://github.com/craftcms/cms/issues/3177))
- Fixed a bug where `composer.json` could end up with a bunch of extra dependencies in the `require` object after a failed update or plugin installation.
- Fixed an error that could occur when viewing an entry revision, if it had a Matrix field and one of the sub-fields within the Matrix field had been deleted. ([#3183](https://github.com/craftcms/cms/issues/3183))
- Fixed a bug where thumbnails weren’t loading in relational fields when viewing an entry version.

## 3.0.18 - 2018-07-31

### Added
- Added `craft\helpers\App::assetManagerConfig()`.
- Added `craft\helpers\App::cacheConfig()`.
- Added `craft\helpers\App::dbConfig()`.
- Added `craft\helpers\App::mailerConfig()`.
- Added `craft\helpers\App::mutexConfig()`.
- Added `craft\helpers\App::logConfig()`.
- Added `craft\helpers\App::sessionConfig()`.
- Added `craft\helpers\App::userConfig()`.
- Added `craft\helpers\App::viewConfig()`.
- Added `craft\helpers\App::webRequestConfig()`.
- Added `craft\validators\StringValidator::$trim`, which will cause leading/trailing whitespace to be stripped from model attributes.

### Changed
- User verification and password-reset emails now link them back to the same site they were on when the email was sent, if it was sent from a front-end request. ([#3029](https://github.com/craftcms/cms/issues/3029))
- Dynamic app component configs are now defined by methods on `craft\helpers\App`, making it easier to modify them from `config/app.php`. ([#3152](https://github.com/craftcms/cms/issues/3152))
- Structure operations now ensure that no other operations are being performed on the same structure, reducing the risk of corrupting the structure. ([#3148](https://github.com/craftcms/cms/issues/3148))
- The `{% js %}` tag now supports the following position params: `at POS_HEAD`, `at POS_BEGIN`, `at POS_END`, `on POS_READY`, and `on POS_LOAD` (e.g. `{% js at POS_END %}`).
- Craft once again checks for `X-Forwarded-For` headers when determining the user’s IP. ([#3036](https://github.com/craftcms/cms/issues/3036))
- Leading/trailing whitespace characters are now stripped from element titles on save. ([#3020](https://github.com/craftcms/cms/issues/3020))
- Updated svg-sanitize to ~0.9.0.

### Deprecated
- Deprecated `craft\db\Connection::createFromConfig()`. `craft\helpers\App::dbConfig()` should be used instead.
- Deprecated `craft\helpers\MailerHelper::createMailer()`. `craft\helpers\App::mailerConfig()` should be used instead.

### Fixed
- Fixed a bug where collapsing structure elements would only hide up to 50 of their descendants.
- Fixed a bug where Date/Time fields could lose their value if they were used in an entry type’s Title Format, and the entry’s site’s language was different than the user’s preferred language. ([#3151](https://github.com/craftcms/cms/issues/3151))
- Fixed a bug where Dropdown fields could show an incorrect selected value in limited circumstances.
- Fixed a bug where Dropdown fields on an element index view could show an incorrect selected value in limited circumstances.

## 3.0.17.1 - 2018-07-24

### Fixed
- Really fixed a PHP error that could occur if the PHP’s `set_time_limit()` was added to the php.ini `disable_functions` list.

## 3.0.17 - 2018-07-24

### Added
- The Control Panel is now translated for Norwegian Nynorsk. ([#3135](https://github.com/craftcms/cms/pull/3135))
- Added `craft\elements\db\ElementQuery::anyStatus()`, which can be called when the default `status` and `enabledForSite` filters aren’t desired. ([#3117](https://github.com/craftcms/cms/issues/3117))

### Changed
- The `addTrailingSlashesToUrls` config setting no longer applies to URLs that end with a segment that has a dot (`.`). ([#3123](https://github.com/craftcms/cms/issues/3123))
- Craft now redirects install requests back to the Dashboard if it’s already installed. ([#3143](https://github.com/craftcms/cms/issues/3143))

### Fixed
- Fixed a bug where the Settings → Email → System Messages page would show messages in the current application language rather than the primary site’s language.
- Fixed a bug where system message modals on the Settings → Email → System Messages page would initially show messages in the current application language rather than the primary site’s language, even if the application language wasn’t in use by any sites. ([#3115](https://github.com/craftcms/cms/issues/3115))
- Fixed an error that could occur if `craft\web\View::registerAssetFlashes()` was called on a console request. ([#3124](https://github.com/craftcms/cms/issues/3124))
- Fixed a PHP error that could occur if the PHP’s `set_time_limit()` was added to the php.ini `disable_functions` list.
- Fixed a bug where expanding a disabled element within a structure index view in the Control Panel wouldn’t reveal any descendants. ([#3126](https://github.com/craftcms/cms/issues/3126))
- Fixed a bug thumbnails weren’t loading for element index rows that were revealed after expanding a parent element.
- Fixed an error that occurred if an element’s `getRoute()` method returned a string. ([#3128](https://github.com/craftcms/cms/issues/3128))
- Fixed a bug where the `|without` filter wasn’t working if an object was passed in. ([#3137](https://github.com/craftcms/cms/issues/3137))
- Fixed a bug where users’ Language preference would default to Deutsch if the current application language wasn’t one of the available language options. ([#3142](https://github.com/craftcms/cms/issues/3142))

## 3.0.16.1 - 2018-07-18

### Fixed
- Fixed a bug where the `orderBy` element query param wasn’t being respected when used in conjunction with a `with` param to eager-load elements in a specific order. ([#3109](https://github.com/craftcms/cms/issues/3109))
- Fixed a bug where underscores were getting removed from slugs. ([#3111](https://github.com/craftcms/cms/issues/3111))

## 3.0.16 - 2018-07-17

### Added
- The progress bar on the Asset Indexes utility now shows how many files have been indexed, and how many there are in total. ([#2934](https://github.com/craftcms/cms/issues/2934))
- Added `craft\base\PluginInterface::beforeSaveSettings()`.
- Added `craft\base\PluginInterface::afterSaveSettings()`.
- Added `craft\base\Plugin::EVENT_AFTER_SAVE_SETTINGS`.
- Added `craft\base\Plugin::EVENT_BEFORE_SAVE_SETTINGS`.

### Changed
- Craft no longer relies on ImageMagick or GD to define the image formats that should be considered manipulatable. ([#2408](https://github.com/craftcms/cms/issues/2408))
- Removed the `showBetaUpdates` config setting as it’s no longer being used.
- When uploading a file to an Assets field, Craft will automatically sort the file list to show the latest uploads first. ([#2812](https://github.com/craftcms/cms/issues/2812))
- `dateCreated`, `dateUpdated`, `postDate`, `expiryDate`, `after`, and  `before` element query params can new be set to `DateTime` objects.
- Matrix fields now auto-focus the first text input within newly-created Matrix blocks. ([#3104](https://github.com/craftcms/cms/issues/3104))
- Updated Twig to 2.5.0.
- Updated Garnish to 0.1.26.
- Updated Selectize to 0.12.6.

### Fixed
- Fixed an error that could occur when sending emails to international domains if the Intl extension wasn’t enabled.
- Fixed an exception that was thrown if the `securityKey` config setting was changed and Craft was set to use either the SMTP or Gmail mailer transport type. ([#3083](https://github.com/craftcms/cms/issues/3083))
- Fixed a bug where Asset view was not being refreshed in some cases after using Image Editor. ([#3035](https://github.com/craftcms/cms/issues/3035))
- Fixed a bug where Craft wouldn’t warn before leaving an edit page with unsaved changes, if Live Preview was active. ([#3092](https://github.com/craftcms/cms/issues/3092))
- Fixed a bug where entries, categories, and global sets’ `getCpEditUrl()` methods could omit the site handle on multi-site installs. ([#3089](https://github.com/craftcms/cms/issues/3089))
- Fixed a JavaScript error that occurred when closing Live Preview. ([#3098](https://github.com/craftcms/cms/issues/3098))
- Fixed a bug where Dashboard widgets could be spaced incorrectly if there was only one grid column. ([#3100](https://github.com/craftcms/cms/issues/3100))
- Fixed a bug where modal windows with Field Layout Designers could cause the browser to crash. ([#3096](https://github.com/craftcms/cms/pull/3096))
- Fixed a bug where `craft\services\Fields::getAllGroups()` and `getGroupById()` could return incorrect results. ([#3102](https://github.com/craftcms/cms/issues/3102))

## 3.0.15 - 2018-07-09

### Changed
- It’s now possible to fetch only non-admin users by setting `craft\elements\db\UserQuery::$admin` to `false`.
- `Craft.EntryTypeSwitcher` now triggers a `typeChange` event after switching the entry type. ([#3067](https://github.com/craftcms/cms/pull/3067))
- Reduced the left and right padding in the Control Panel for screens less than 768 pixels wide. ([#3073](https://github.com/craftcms/cms/issues/3073))
- Removed the `useXSendFile` config setting as it’s no longer being used.
- `craft\helpers\StringHelper::toKebabCase()`, `toCamelCase()`, `toPascalCase()`, and `toSnakeCase()` now treat camelCase’d and PascalCale’d strings as multiple words. ([#3090](https://github.com/craftcms/cms/issues/3090))

### Fixed
- Fixed a bug where `craft\i18n\I18N::getPrimarySiteLocale()` and `getPrimarySiteLocaleId()` were returning locale info for the _first_ site, rather than the primary one. ([#3063](https://github.com/craftcms/cms/issues/3063))
- Fixed a bug where element index pages were loading all elements in the view, rather than waiting for the user to scroll to the bottom of the page before loading the next batch. ([#3068](https://github.com/craftcms/cms/issues/3068))
- Fixed a bug where sites listed in the Control Panel weren’t always in the correct sort order. ([#3065](https://github.com/craftcms/cms/issues/3065))
- Fixed an error that occurred when users attempted to create new entries within entry selector modals, for a section they didn’t have permission to publish peer entries in. ([#3069](https://github.com/craftcms/cms/issues/3069))
- Fixed a bug where the “Save as a new asset” button label wasn’t getting translated in the Image Editor. ([#3070](https://github.com/craftcms/cms/pull/3070))
- Fixed a bug where it was impossible to set the filename of assets when uploading them as data strings. ([#2973](https://github.com/craftcms/cms/issues/2973))
- Fixed a bug where the Field Type menu’s options within new Matrix block type settings weren’t getting sorted alphabetically. ([#3072](https://github.com/craftcms/cms/issues/3072))
- Fixed an exception that was thrown when testing email settings if the Template setting was invalid. ([#3074](https://github.com/craftcms/cms/issues/3074))
- Fixed a bug where Dropdown fields’ bottom margin could jump up a bit when an empty option was selected. ([#3075](https://github.com/craftcms/cms/issues/3075))
- Fixed a bug where main content containers in the Control Panel could become too wide in Firefox. ([#3071](https://github.com/craftcms/cms/issues/3071))

## 3.0.14 - 2018-07-03

### Changed
- `craft\events\SiteEvent` now has a `$oldPrimarySiteId` property, which will be set to the previous primary site ID (which may stil be the current site ID, if it didn’t just change).
- `craft\helpers\Search::normalizeKeywords()` now has a `$language` argument, which can be set if the character mappings should be pulled from a different language than the current app language.
- `craft\services\Sites::getEditableSiteIds()` and `getEditableSites()` now return the same things as `getAllSiteIds()` and `getAllSites()` when there’s only one site. ([#3049](https://github.com/craftcms/cms/issues/3049))

### Fixed
- Fixed a bug where user verification links could get mangled when emails were parsed as Markdown, if the verification code contained two or more underscores.
- Fixed a bug where Craft was misinterpreting `X-Forwarded-For` headers as the user’s IP instead of the server’s IP. ([#3036](https://github.com/craftcms/cms/issues/3036))
- Fixed a bug where Craft wasn’t auto-scrolling the content container when dragging items near a window edge. ([#3048](https://github.com/craftcms/cms/issues/3048))
- Fixed a PHP error that occurred when loading a Debug Toolbar panel on a page that contained serialized Checkboxes or Multi-Select field data. ([#3034](https://github.com/craftcms/cms/issues/3034))
- Fixed a bug where elements’ normalized search keywords weren’t always using the correct language-specific character mappings. ([#3046](https://github.com/craftcms/cms/issues/3046))
- Fixed a bug where the `<html lang>` attribute was hard-set to `en-US` rather than the current application language. ([#3053](https://github.com/craftcms/cms/pull/3053))
- Fixed a PHP error that occurred when entering an invalid number into a Number field that was set to have decimal digits. ([#3059](https://github.com/craftcms/cms/issues/3059))

### Security
- Craft no longer shows the installer when it can’t establish a database connection if Dev Mode isn’t enabled.

## 3.0.13.2 - 2018-06-27

### Fixed
- Fixed an error that occurred when deleting users from the Users index page.

## 3.0.13.1 - 2018-06-26

### Fixed
- Fixed a bug where Delete User modals weren’t showing the total number of entries that will be transferred/deleted.

## 3.0.13 - 2018-06-26

### Added
- Craft now includes a summary of the content that will be transferred/deleted in Delete User modals. ([#875](https://github.com/craftcms/cms/issues/875))
- `|date`, `|time`, and `|datetime` filters now support a `locale` argument, for specifying which locale’s formatter should be doing the date/time formatting. ([#3006](https://github.com/craftcms/cms/issues/3006))
- Added `craft\base\ApplicationTrait::getIsInitialized()`.
- Added `craft\base\ClonefixTrait`.
- Added `craft\controllers\AssetsController::actionThumb()`.
- Added `craft\controllers\UsersController::actionUserContentSummary()`.
- Added `craft\controllers\UsersController::EVENT_DEFINE_CONTENT_SUMMARY`.
- Added `craft\helpers\App::backtrace()`.
- Added `craft\queue\jobs\PropagateElements`.
- Added `craft\services\Elements::propagateElement()`.

### Changed
- Editable tables now submit an empty string when they have no rows.
- Reduced the overhead when adding a new site by only resaving existing assets, categories, global sets, and tags once for the newly-created site, rather than for all sites.
- Web-based queue workers now call `craft\helpers\App::maxPowerCaptain()` before running the queue. ([#3011](https://github.com/craftcms/cms/issues/3011))
- The PHP Info utility no longer displays the original values for settings and only the current environment value. ([#2990](https://github.com/craftcms/cms/issues/2990))
- Loosened up most of Craft’s Composer dependency constraints.
- Craft no longer publishes asset thumbnails to the `cpresources/` folder.
- `attributes`, `error`, `errors`, and `scenario` are now reserved field handles. ([#3032](https://github.com/craftcms/cms/issues/3032))
- Improved the look of Control Panel tabs.
- `craft\web\UrlManager::createUrl()`, `createAbsoluteUrl()`, and `getMatchedElement()` now log warnings if they’re called before Craft has been fully initialized. ([#3028](https://github.com/craftcms/cms/issues/3028))

### Deprecated
- Deprecated `craft\controllers\AssetsController::actionGenerateThumb()`.

### Fixed
- Fixed a bug where sidebar meta info on Edit User pages was bleeding over the edge of the page’s content area.
- Fixed a bug where Table fields wouldn’t remember if they had no rows in their Default Values setting. ([#2979](https://github.com/craftcms/cms/issues/2979))
- Fixed a bug where passing `timezone=false` to the `|date`, `|time`, and `|datetime` filters would not preserve the given date’s time zone.
- Fixed a bug where AM/PM strings in formatted dates weren’t respecting the casing specified by the `A`/`a` character in the date format. ([#3007](https://github.com/craftcms/cms/issues/3007))
- Fixed a bug you could get an invalid license warning in cases where web API calls returned a 500 response code.
- Fixed a bug where cloning models and queries would lose any associated behaviors. ([#2857](https://github.com/craftcms/cms/issues/2857))
- Fixed a bug where custom field params were getting forgotten when calling `getNext()` and `getPrev()`, if an element query object was passed in. ([#3019](https://github.com/craftcms/cms/issues/3019))
- Fixed a bug where datepickers were getting scrollbars.
- Fixed a bug where volumes’ field layouts weren’t getting deleted when volumes were deleted. ([#3022](https://github.com/craftcms/cms/pull/3022))
- Fixed a bug where deleting a section or an entry type wouldn’t delete any associated entries that didn’t exist in the primary site. ([#3023](https://github.com/craftcms/cms/issues/3023))
- Fixed a bug where the `svg()` Twig function could convert `id` attributes within the SVG contents to invalid IDs. ([#3025](https://github.com/craftcms/cms/issues/3025))
- Fixed a bug where asset thumbnails wouldn’t load reliably in the Control Panel on load-balanced environments. ([#3026](https://github.com/craftcms/cms/issues/3026))
- Fixed a PHP error that could occur when validating Assets fields if a file was uploaded but no longer exists at the temp location. ([#3033](https://github.com/craftcms/cms/pull/3033))

## 3.0.12 - 2018-06-18

### Added
- Added a `leaves` element query param that limits the selected elements to just the leaves in the structure (elements without children).
- Added `craft\helpers\Db::deleteIfExists()`.
- Added `craft\services\Categories::deleteGroup()`. ([#3000](https://github.com/craftcms/cms/pull/3000))
- Added `craft\services\Tags::deleteTagGroup()`. ([#3000](https://github.com/craftcms/cms/pull/3000))
- Added `craft\services\UserGroups::deleteGroup()`. ([#3000](https://github.com/craftcms/cms/pull/3000))

### Changed
- Improved Control Panel styling. ([#2883](https://github.com/craftcms/cms/issues/2883))

### Removed
- Removed `craft\services\Fields::updateFieldVersionAfterRequest()`.

### Fixed
- Fixed a caching bug where the Fields service could still think a field existed after it had been deleted. ([#2985](https://github.com/craftcms/cms/issues/2985))
- Fixed a bug where Craft would not invalidate the dynamically-generated `craft\behaviors\ContentBehavior` and `craft\behaviors\ElementQueryBehavior` after saving/deleting a custom field, if the request didn’t end normally. ([#2999](https://github.com/craftcms/cms/issues/2999))
- Fixed a PHP error that could occur when saving entries with a URI format that contained certain Twig filters. ([#2995](https://github.com/craftcms/cms/issues/2995))
- Fixed a bug where `{shorthand}` variables in templates rendered by `craft\web\View::renderObjectTemplate()` could end up referencing global variables, if the variable wasn’t a property of the object. ([#3002](https://github.com/craftcms/cms/issues/3002))
- Fixed a bug where the Find and Replace utility wasn’t updating element titles. ([#2996](https://github.com/craftcms/cms/issues/2996))
- Fixed some wonky behavior if one of the custom user profile tabs was called “Account”. ([#2998](https://github.com/craftcms/cms/issues/2998))
- Fixed a bug where dragging a folder on the Assets index page could have unexpected results. ([#2873](https://github.com/craftcms/cms/issues/2873))
- Reduced the likelihood of SQL deadlock errors when saving elements. ([#3003](https://github.com/craftcms/cms/issues/3003))

## 3.0.11 - 2018-06-12

### Changed
- Sort options defined by element types’ `sortOptions()` / `defineSortOptions()` methods can now be specified as sub-arrays with `label`, `orderBy`, and `attribute` keys.
- Entries and categories can now be sorted by their slugs.
- The “Cache remote images?” option in the Asset Indexes utility is now enabled by default. ([#2977](https://github.com/craftcms/cms/issues/2977))

### Fixed
- Fixed a bug where it was not possible to order search results by search score, if the element type didn’t specify any sort options.
- Fixed a bug where clicking on “Date Created” and “Date Updated” column headers on element indexes wouldn’t update the sort order. ([#2975](https://github.com/craftcms/cms/issues/2975))
- Fixed a bug where Edit Entry pages were listing more than the 10 most recent versions. ([#2976](https://github.com/craftcms/cms/issues/2976))
- Fixed a SQL error that occurred when upgrading from Craft 2 to 3 via the terminal. ([#1347](https://github.com/craftcms/cms/issues/1347))
- Fixed the alignment of expand/collapse toggles in asset index sidebars. ([#2981](https://github.com/craftcms/cms/issues/2981))

## 3.0.10.3 - 2018-06-07

### Fixed
- Fixed a bug where the “New Entry” menu on the Entries index page would not contain any options on single-site installs, running MySQL. ([#2961](https://github.com/craftcms/cms/issues/2961))
- Fixed a bug where the `siteName` config setting wasn’t working as expected when set to an array. ([#2968](https://github.com/craftcms/cms/issues/2968))

## 3.0.10.2 - 2018-06-07

### Changed
- Improved the output of `craft\helpers\DateTimeHelper::humanDurationFromInterval()`.
- Updated Garnish to 0.1.24.

### Fixed
- Fixed JavaScript errors that could occur in the Control Panel on pages with Ajax requests. ([#2966](https://github.com/craftcms/cms/issues/2966))
- Fixed a bug where the “New Entry” menu on the Entries index page would not contain any options on single-site installs. ([#2961](https://github.com/craftcms/cms/issues/2961))
- Fixed a bug where JavaScript files registered with `craft\web\View::registerJsFile()` would be ignored if the `depends` option was set. ([#2965](https://github.com/craftcms/cms/issues/2965))

## 3.0.10.1 - 2018-06-06

### Fixed
- Fixed a bug where Craft wasn’t converting empty strings to `null` when saving data to non-textual columns.
- Fixed a bug where Craft would show a Database Connection Error on Install requests, if it couldn’t connect to the database.
- Fixed a bug where Craft wasn’t keeping track of element queries that were executed within `{% cache %}` tags. ([#2959](https://github.com/craftcms/cms/issues/2959))

## 3.0.10 - 2018-06-05

### Added
- Added support for a `CRAFT_LICENSE_KEY` PHP constant, which can be set to the project’s license key, taking precedence over the `license.key` file.
- Added `craft\helpers\Stringy::getLangSpecificCharsArray()`.
- Added `craft\web\View::setRegisteredAssetBundles()`.
- Added `craft\web\View::setRegisteredJsFiles()`.

### Changed
- Generated site URLs now always include full host info, even if the base site URL is root/protocol-relative. ([#2919](https://github.com/craftcms/cms/issues/2919))
- Variables passed into `craft\web\View::renderObjectTemplate()` can now be referenced using the shorthand syntax (e.g. `{foo}`).
- `craft\helpers\StringHelper::asciiCharMap()` now has `$flat` and `$language` arguments.
- Craft no longer saves new versions of entries when absolutely nothing changed about them in the save request. ([#2923](https://github.com/craftcms/cms/issues/2923))
- Craft no longer enforces plugins’ `minVersionRequired` settings if the currently-installed version begins with `
- 
- dev-`.
- Improved the performance of element queries when a lot of values were passed into a param, such as `id`, by using `IN()` and `NOT IN()` conditions when possible. ([#2937](https://github.com/craftcms/cms/pull/2937))
- The Asset Indexes utility no longer skips files with leading underscores. ([#2943](https://github.com/craftcms/cms/issues/2943))
- Updated Garnish to 0.1.23.

### Deprecated
- Deprecated the `customAsciiCharMappings` config setting. (Any corrections to ASCII char mappings should be submitted to [Stringy](https://github.com/danielstjules/Stringy).)

### Fixed
- Fixed a PHP error that could occur when `craft\fields\Number::normalizeValue()` was called without passing an `$element` argument. ([#2913](https://github.com/craftcms/cms/issues/2913))
- Fixed a bug where it was not possible to fetch Matrix blocks with the `relatedTo` param if a specific custom field was specified.
- Fixed a bug where `craft\helpers\UrlHelper::url()` and `siteUrl()` were not respecting the `$scheme` argument for site URLs.
- Fixed a bug where `{id}` tags within element URI formats weren’t getting parsed correctly on first save. ([#2922](https://github.com/craftcms/cms/issues/2922))
- Fixed a bug where `craft\helpers\MigrationHelper::dropAllForeignKeysToTable()` wasn’t working correctly. ([#2897](https://github.com/craftcms/cms/issues/2897))
- Fixed a “Craft is not defined” JavaScript error that could occur on the Forgot Password page in the Control Panel and Dev Toolbar requests.
- Fixed a bug where rotating the screen on iOS would change how the page was zoomed.
- Fixed a bug where `craft\helpers\StringHelper::toAscii()` and the `Craft.asciiString()` JS method weren’t using language-specific character replacements, or any custom replacements defined by the `customAsciiCharMappings` config setting.
- Fixed a bug where the number `0` would not save in a Plain Text field.
- Fixed a bug where Craft could pick the wrong current site if the primary site had a root-relative or protocol-relative URL, and another site didn’t, but was otherwise an equal match.
- Fixed a bug where Control Panel Ajax requests could cause some asset bundles and JavaScript files to be double-registered in the browser.
- Fixed a bug where the “New entry” menu on the Entries index page was including sections that weren’t available in the selected site, and they weren’t linking to Edit Entry pages for the selected site. ([#2925](https://github.com/craftcms/cms/issues/2925))
- Fixed a bug where the `|date`, `|time`, and `|datetime` filters weren’t respecting their `$timezone` arguments. ([#2926](https://github.com/craftcms/cms/issues/2926))
- Fixed a bug where element queries weren’t respecting the `asArray` param when calling `one()`. ([#2940](https://github.com/craftcms/cms/issues/2940))
- Fixed a bug where the Asset Indexes utility wouldn’t work as expected if all of a volume’s assets had been deleted from the file system. ([#2955](https://github.com/craftcms/cms/issues/2955))
- Fixed a SQL error that could occur when a `{% cache %}` tag had no body. ([#2953](https://github.com/craftcms/cms/issues/2953))

## 3.0.9 - 2018-05-22

### Added
- Added a default plugin icon to plugins without an icon in the Plugin Store.
- Added `craft\helpers\ArrayHelper::without()` and `withoutValue()`.
- Added `craft\base\FieldInterface::modifyElementIndexQuery()`.
- Added `craft\elements\db\ElementQueryInterface::andWith()`.

### Changed
- Fixed a bug where Craft was checking the file system when determining if an asset was a GIF, when it should have just been checking the file extension.
- `craft\base\Plugin` now sets the default `$controllerNamespace` value to the plugin class’ namespace + `\controllers` or `\console\controllers`, depending on whether it’s a web or console request.
- Improved the contrast of success and error notices in the Control Panel to meet WCAG AA requirements. ([#2885](https://github.com/craftcms/cms/issues/2885))
- `fieldValue` is now a protected field handle. ([#2893](https://github.com/craftcms/cms/issues/2893))
- Craft will no longer discard any preloaded elements when setting the `with` param on an element query, fixing a bug where disabled Matrix blocks could show up in Live Preview if any nested fields were getting eager-loaded. ([#1576](https://github.com/craftcms/cms/issues/1576))
- Improved memory usage when using the `{% cache %}` tag. ([#2903](https://github.com/craftcms/cms/issues/2903))

### Fixed
- Fixed a bug where the Plugin Store was listing featured plugins (e.g. “Recently Added”) in alphabetical order rather than the API-defined order. ([pixelandtonic/craftnet#83](https://github.com/pixelandtonic/craftnet/issues/83))
- Fixed a SQL error that occurred when programmatically saving a field layout, if the field’s `required` property wasn’t set.
- Fixed a JavaScript error that could occur when multiple Assets fields were present on the same page.
- Fixed an error that could occur when running the `setup` command on some environments.
- Fixed a PHP error that could occur when calling `craft\elements\db\ElementQuery::addOrderBy()` if `$columns` normalized to an empty array. ([#2896](https://github.com/craftcms/cms/issues/2896))
- Fixed a bug where it wasn’t possible to access custom field values on Matrix blocks via `matrixblock` reference tags.
- Fixed a bug where relational fields with only disabled elements selected would get empty table cells on element indexes. ([#2910](https://github.com/craftcms/cms/issues/2910))

## 3.0.8 - 2018-05-15

### Added
- Number fields now have a “Default Value” setting. ([#927](https://github.com/craftcms/cms/issues/927))
- Added the `preserveCmykColorspace` config setting, which can be set to `true` to prevent images’ color spaces from getting converted to sRGB on environments running ImageMagick.

### Changed
- Error text is now orange instead of red. ([#2885](https://github.com/craftcms/cms/issues/2885))
- Detail panes now have a lighter, more saturated background color.

### Fixed
- Fixed a bug where Craft’s default MySQL backup command would not respect the `unixSocket` database config setting. ([#2794](https://github.com/craftcms/cms/issues/2794))
- Fixed a bug where some SVG files were not recognized as SVG files.
- Fixed a bug where Table fields could add the wrong number of default rows if the Min Rows setting was set, and the Default Values setting had something other than one row. ([#2864](https://github.com/craftcms/cms/issues/2864))
- Fixed an error that could occur when parsing asset reference tags. ([craftcms/redactor#47](https://github.com/craftcms/redactor/issues/47))
- Fixed a bug where “Try” and “Buy” buttons in the Plugin Store were visible when the `allowUpdates` config setting was disabled. ([#2781](https://github.com/craftcms/cms/issues/2781))
- Fixed a bug where Number fields would forget their Min/Max Value settings if they were set to 0.
- Fixed a bug where entry versions could be displayed in the wrong order if multiple versions had the same creation date. ([#2889](https://github.com/craftcms/cms/issues/2889))
- Fixed an error that occurred when installing Craft on a domain with an active user session.
- Fixed a bug where email verification links weren’t working for publicly-registered users if the registration form contained a Password field and the default user group granted permission to access the Control Panel.

### Security
- Login errors for locked users now factor in whether the `preventUserEnumeration` config setting is enabled.

## 3.0.7 - 2018-05-10

### Added
- Added the `transformGifs` config setting, which can be set to `false` to prevent GIFs from getting transformed or cleansed. ([#2845](https://github.com/craftcms/cms/issues/2845))
- Added `craft\helpers\FileHelper::isGif()`.

### Changed
- Craft no longer logs warnings about missing translation files when Dev Mode isn’t enabled. ([#1531](https://github.com/craftcms/cms/issues/1531))
- Added `craft\services\Deprecator::$logTarget`. ([#2870](https://github.com/craftcms/cms/issues/2870))
- `craft\services\Deprecator::log()` no longer returns anything.

### Fixed
- Fixed a bug where it was impossible to upload new assets to Assets fields using base64-encoded strings. ([#2855](https://github.com/craftcms/cms/issues/2855))
- Fixed a bug where Assets fields would ignore all submitted asset IDs if any new assets were uploaded as well.
- Fixed a bug where SVG files that were using single quotes instead of double quotes would not be recognized as SVGs.
- Fixed a bug where translated versions of the “It looks like someone is currently performing a system update.” message contained an HTML-encoded `<br/>` tag.
- Fixed a bug where changing an entry’s type could skip adding the new entry type’s tabs, if the previous entry type didn’t have any tabs. ([#2859](https://github.com/craftcms/cms/issues/2859))
- Fixed warnings about missing SVG files that were logged by Control Panel requests.
- Fixed a bug where the `|date` filter would ignore date formatting characters that don’t have ICU counterparts. ([#2867](https://github.com/craftcms/cms/issues/2867))
- Fixed a bug where the global `currentUser` Twig variable could be set to `null` and global sets and could be missing some custom field values when a user was logged-in, if a plugin was loading Twig during or immediately after plugin instantiation. ([#2866](https://github.com/craftcms/cms/issues/2866))

## 3.0.6 - 2018-05-08

### Added
- Error messages about missing plugin-supplied field and volume types now show an Install button when possible.
- Added `craft\base\MissingComponentTrait::getPlaceholderHtml()`.
- Added `craft\db\Migration::EVENT_AFTER_UP` and `EVENT_AFTER_DOWN` events.
- Added `craft\elements\Asset::getContents()`.

### Changed
- Edit User pages will now warn editors when leaving the page with unsaved changes. ([#2832](https://github.com/craftcms/cms/issues/2832))
- Modules are once again loaded before plugins, so they have a chance to register Twig initialization events before a plugin initializes Twig. ([#2831](https://github.com/craftcms/cms/issues/2831))
- `craft\helpers\FileHelper::isSvg()` now returns `true` for files with an `image/svg` MIME type (missing the `+xml`). ([#2837](https://github.com/craftcms/cms/pull/2837))
- The `svg()` Twig function now accepts assets to be passed directly into it. ([#2838](https://github.com/craftcms/cms/pull/2838))
- The “Save and add another” save menu option on Edit Entry and Edit Categories pages now maintain the currently-selected site. ([#2844](https://github.com/craftcms/cms/issues/2844))
- PHP date patterns that are *only* a month name or week day name character will now format the date using the stand-alone month/week day name value. (For example, `'F'` will format a date as “Maggio” instead of “maggio”.)
- Servers without the Intl extension will now use location-agnostic locale data as a fallback if locale data for the specific locale isn’t available.
- The `|date` Twig filter always goes through `craft\i18n\Formatter::asDate()` now, unless formatting a `DateInterval` object.
- The Settings → Plugins page now shows “Buy now” buttons for any commercial plugins that don’t have a license key yet.

### Deprecated
- Deprecated `craft\helpers\DateTimeHelper::translateDate()`. `craft\i18n\Formatter::asDate()` should be used instead.

### Removed
- Removed the `translate` argument from the `|date`, `|time`, and `|datetime` Twig filters; the resulting formatted dates will always be translated now. (Use `myDate.format()` to avoid translations.)

### Fixed
- Fixed an error that could occur in the Plugin Store.
- Fixed a bug where `myDate|date('F')` was returning the short “May” translation rather than the full-length one. ([#2848](https://github.com/craftcms/cms/issues/2848))

## 3.0.5 - 2018-05-01

### Changed
- Fields’ translation icons now reveal the chosen Translation Method in their tooltip. ([#2808](https://github.com/craftcms/cms/issues/2808))
- Improved the error messages displayed when an Assets field has an invalid Upload Location setting. ([#2803](https://github.com/craftcms/cms/issues/2803))
- Craft now logs errors that occur when saving and replacing assets. ([#2814](https://github.com/craftcms/cms/issues/2814))
- Single sections’ entry types’ handles are now updated to match their section’s handle whenever the section is saved. ([#2824](https://github.com/craftcms/cms/issues/2824))
- The Control Panel background color was lightened up a bit.

### Fixed
- Fixed an error that would occur on servers without the Phar PHP extension enabled.
- Fixed an error that could occur if a Matrix block was deleted by a queue job. ([#2813](https://github.com/craftcms/cms/issues/2813))
- Fixed a bug where Twig could be configured to output times in UTC rather than the system timezone, if a bootstrapped module was loading Twig. ([#2761](https://github.com/craftcms/cms/issues/2761))
- Fixed a SQL error that could occur when upgrading from Craft 2 to Craft 3 with an active user session.
- Fixed various SQL errors that could occur when upgrading from Craft 2 to Craft 3, if there were any lingering Craft 3 database tables from a previous upgrade attempt.
- Fixed a bug where the Clear Caches tool was deleting the `.gitignore` file inside `web/cpresources/`. ([#2823](https://github.com/craftcms/cms/issues/2823))
- Fixed the vertical positioning of checkboxes in the Control Panel. ([#2825](https://github.com/craftcms/cms/issues/2825))
- Fixed a JavaScript error that could occur if an element type’s class name contained `\u`. ([#2826](https://github.com/craftcms/cms/issues/2826))

## 3.0.4 - 2018-04-24

### Added
- Added the `craft.globalSets()` template function. ([#2790](https://github.com/craftcms/cms/issues/2790))
- Added the `hasDescendants` element query param. ([#2786](https://github.com/craftcms/cms/issues/2786))
- Added `craft\elements\User::hasDashboard`.

### Changed
- Sections and category groups now ignore posted Template settings for sites that don’t have URI Formats.
- Control Panel resources are once again eager-published. ([#2763](https://github.com/craftcms/cms/issues/2763))
- `entries/save-entries` and `categories/save-category` actions now include the `slug` for responses that accept JSON. ([#2792](https://github.com/craftcms/cms/issues/2792))
- Most `craft\services\Path` methods now have a `$create` argument, which can be set to `false` to prevent the directory from being created if it doesn’t exist yet.
- Craft no longer creates directories when it just needed to clear it. ([#2771](https://github.com/craftcms/cms/issues/2771))
- `craft\services\Config::setDotEnvVar()` now sets the environment variable for the current request, in addition to updating the `.env` file.
- Removed `craft\controllers\AssetsController::actionDownloadTempAsset()`.
- User now must be logged in to use the Asset Preview File functionality.

### Fixed
- Fixed a bug where users would regain all default Dashboard widgets if all widgets were removed. ([#2769](https://github.com/craftcms/cms/issues/2769))
- Fixed a bug where you would get a “not a valid language” error message when creating a new site using certain languages.
- Fixed a bug where database connection settings that were set by the `setup` command weren’t always taking effect in time for the CLI installer. ([#2774](https://github.com/craftcms/cms/issues/2774))
- Fixed a bug where empty Plain Text fields were getting empty string values rather than `null`.
- Fixed a bug where elements within relational fields could have two thumbnails. ([#2785](https://github.com/craftcms/cms/issues/2785))
- Fixed a bug where it was not possible to pass a `--table-prefix` argument to the `setup/db-creds` command. ([#2791](https://github.com/craftcms/cms/pull/2791))
- Fixed an error that occurred for users without permission to perform updates, if available update info wasn’t cached.
- Fixed an error that occurred when `craft\elements\Asset::sources()` was called in a console request. ([#2798](https://github.com/craftcms/cms/issues/2798))
- Fixed JavaScript errors that could occur on the front-end after deleting Matrix blocks. ([#2799](https://github.com/craftcms/cms/pull/2799))

## 3.0.3.1 - 2018-04-18

### Fixed
- Fixed an error that occurred when editing an entry if any of the entry’s revisions were created with an entry type that no longer exists.
- Fixed an error that could occur when saving an asset. ([#2764](https://github.com/craftcms/cms/issues/2764))
- Fixed a bug where Craft assumed an asset was missing if there was an error when indexing it. ([#2763](https://github.com/craftcms/cms/issues/2763))

## 3.0.3 - 2018-04-17

### Added
- Added `craft\elements\Entry::updateTitle()`.
- Added `Yii::alias()`.

### Changed
- New sites’ Base URLs now default to `@web/`.
- Textual custom fields now ensure that they don’t contain 4+ byte characters. ([#2725](https://github.com/craftcms/cms/issues/2725))
- It is no longer expected that all of the `defaultSearchTermOptions` config setting options will be set if any of the default option values need to be overridden. ([#2737](https://github.com/craftcms/cms/issues/2737))
- Control Panel panes now have at least 48 pixels of bottom padding. ([#2744](https://github.com/craftcms/cms/issues/2744))
- Craft now intercepts 404-ing resource requests, and publishes the resources on the fly.
- The Clear Caches utility now has a “Control Panel resources” option.
- The Clear Caches utility now sorts the cache options alphabetically.
- When enabling new sites for a section, the new sites’ content is now based on the primary site’s content, if the section was and still is enabled for the primary site. ([#2748](https://github.com/craftcms/cms/issues/2748))
- Improved the responsiveness of element indexes.
- `Craft.BaseElementIndexView` now has a `loadMoreElementsAction` setting. ([#2762](https://github.com/craftcms/cms/pull/2762))

### Fixed
- Fixed a bug where the Clear Caches utility was not deleting template caches. ([#2720](https://github.com/craftcms/cms/issues/2720))
- Fixed a bug where the Plugin Store was not displaying payment errors on checkout.
- Fixed a bug where Control Panel-defined routes that contained special regular expression characters weren’t working. ([#2721](https://github.com/craftcms/cms/issues/2721))
- Fixed a bug where it was not possible to save system messages in some cases.
- Fixed a bug where static translations within dynamic entry title formats were getting translated using the current site’s language, rather than the entry’s language. ([#2722](https://github.com/craftcms/cms/issues/2722))
- Fixed a bug where deprecation errors for some date formatting methods were not escaping backslashes.
- Fixed a bug where plugins’ “Last update” timestamps in the Plugin Store weren’t getting formatted correctly in Safari. ([#2733](https://github.com/craftcms/cms/issues/2733))
- Fixed references to a nonexistent `Craft.eot` file in the Control Panel CSS. ([#2740](https://github.com/craftcms/cms/issues/2740))
- Fixed a bug where the default PostgreSQL database restore command wasn’t setting the `PGPASSWORD` environment variable. ([#2741](https://github.com/craftcms/cms/pull/2741))
- Fixed an error that could occur if the system time zone was not supported by the ICU library, on environments with the Intl extension loaded.
- Fixed a bug where several administrative fields had translatable icons. ([#2742](https://github.com/craftcms/cms/issues/2742))
- Fixed a bug where `craft\controllers\PluginStoreController::actionSavePluginLicenseKeys()` was trying to set a plugin license key for plugins which were not installed.

### Security
- Fixed a bug assets were not getting cleansed on upload. ([#2709](https://github.com/craftcms/cms/issues/2709))

## 3.0.2 - 2018-04-10

### Added
- Added the `EVENT_BEFORE_DELETE_CACHES` and `EVENT_AFTER_DELETE_CACHES` events to `craft\services\TemplateCaches`.
- Added `craft\events\DeleteTemplateCachesEvent`.

### Changed
- Craft now deletes all compiled templates whenever Craft or a plugin is updated. ([#2686](https://github.com/craftcms/cms/issues/2686))
- The Plugin Store now displays commercial plugins’ renewal prices. ([#2690](https://github.com/craftcms/cms/issues/2690))
- The Plugin Store no longer shows the “Upgrade Craft CMS” link if Craft is already running (and licensed to run) the Pro edition. ([#2713](https://github.com/craftcms/cms/issues/2713))
- Matrix fields now set `$propagating` to `true` when saving Matrix blocks, if the owner element is propagating.
- `craft\helpers\ArrayHelper::toArray()` no longer throws a deprecation error when a string without commas is passed to it. ([#2711](https://github.com/craftcms/cms/issues/2711))
- Editable tables now support an `html` column type, which will output cell values directly without encoding HTML entities. ([#2716](https://github.com/craftcms/cms/pull/2716))
- `Craft.EditableTable` instances are now accessible via `.data('editable-table')` on their `<table>` element. ([#2694](https://github.com/craftcms/cms/issues/2694))
- Updated Composer to 1.6.3. ([#2707](https://github.com/craftcms/cms/issues/2707))
- Updated Garnish to 0.1.22. ([#2689](https://github.com/craftcms/cms/issues/2689))

### Fixed
- Fixed an error that could occur in the Control Panel if any plugins with licensing issues were installed. ([#2691](https://github.com/craftcms/cms/pull/2691))
- Fixed a bug on the Plugin Store’s Payment screen where the “Use a new credit card” radio option would not get selected automatically even if it was the only one available.
- Fixed a bug where `craft\web\assets\vue\VueAsset` didn’t respect the `useCompressedJs` config setting.
- Fixed an error that occurred when saving a Single entry over Ajax. ([#2687](https://github.com/craftcms/cms/issues/2687))
- Fixed an error that could occur when disabling a site on a Single section. ([#2695](https://github.com/craftcms/cms/issues/2695))
- Fixed an error that could occur on requests without a content type on the response. ([#2704](https://github.com/craftcms/cms/issues/2704))
- Fixed a bug where the `includeSubfolders` asset query param wasn’t including results in the parent folder. ([#2706](https://github.com/craftcms/cms/issues/2706))
- Fixed an error that could occur when querying for users eager-loaded with their photos, if any of the resulting users didn’t have a photo. ([#2708](https://github.com/craftcms/cms/issues/2708))
- Fixed a bug where relational fields within Matrix fields wouldn’t save relations to elements that didn’t exist on all of the sites the owner element existed on. ([#2683](https://github.com/craftcms/cms/issues/2683))
- Fixed a bug where relational fields were ignoring disabled related elements in various functions, including required field validation and value serialization.
- Fixed an error that would occur if a new custom field was created and added to an element’s field layout, and its value was accessed, all in the same request. ([#2705](https://github.com/craftcms/cms/issues/2705))
- Fixed a bug where the `id` param was ignored when used on an eager-loaded elements’ criteria. ([#2717](https://github.com/craftcms/cms/issues/2717))
- Fixed a bug where the default restore command for MySQL wouldn’t actually restore the database. ([#2714](https://github.com/craftcms/cms/issues/2714))

## 3.0.1 - 2018-04-04

### Deprecated
- Brought back and deprecated the `Craft::Personal` and `Craft::Client` constants.

### Fixed
- Fixed a bug where elements’ `getNext()` and `getPrev()` methods were modifying the element query passed into them. ([#2160](https://github.com/craftcms/cms/issues/2160))
- Fixed a bug where Table fields could be pre-populated with one too many rows. ([#2680](https://github.com/craftcms/cms/pull/2680))

### Security
- Craft no longer sends exception messages to error templates, unless the exception is an instance of `yii\base\UserException`.

## 3.0.0.2 - 2018-04-04

### Fixed
- Fixed a bug where Craft Pro installs were getting identified as Craft Solo in the Control Panel.

## 3.0.0 - 2018-04-04

### Added
- The codebase has been completely rewritten and refactored to improve performance, maintainability, and extensibility.
- Craft can now be [installed](https://docs.craftcms.com/v3/installation.html) via Composer in addition to a zip file. ([#895](https://github.com/craftcms/cms/issues/895))
- Craft’s setup wizard is now available as a CLI tool in addition to the web-based one.
- [Plugins](https://docs.craftcms.com/v3/plugin-intro.html) are now loaded as Composer dependencies, and implemented as extensions of [Yii modules](http://www.yiiframework.com/doc-2.0/guide-structure-modules.html).
- Added [multi-site](https://docs.craftcms.com/v3/sites.html) support.
- Added the Plugin Store, where plugins can be discovered, trialled, and purchased. ([#808](https://github.com/craftcms/cms/issues/808))
- Plugins can now be updated and removed from within the Control Panel.
- Asset sources are now called “volumes”, and plugins can supply their own volume types.
- Added the Image Editor, which can be used to rotate, crop, and flip images, as well as set focal points on them.
- Added asset previews, which can be triggered via a “Preview file” action on the Assets index, or with a `shift` + `spacebar` keyboard shortcut throughout the Control Panel.
- Asset editor HUDs now show image previews. ([#837](https://github.com/craftcms/cms/issues/837))
- Added the “Utilities” section to the Control Panel, replacing the Tools area of the Settings page.
- Added the Debug Toolbar, powered by the [Debug Extension for Yii 2](http://www.yiiframework.com/doc-2.0/guide-tool-debugger.html).
- Added support for [Content Migrations](https://docs.craftcms.com/v3/content-migrations.html).
- Added support for PostgreSQL.

### Changed
- The Control Panel has been redesigned for better usability, readability and responsiveness.
- Renamed all “URL Format” things to “URI Format”, in the Control Panel UI and in the code.
- Added the “Propagate entries across all enabled sites?” section setting. If disabled, entries will only be associated with the site they were created on. ([#2330](https://github.com/craftcms/cms/issues/2330))
- Structure sections and category groups no longer have Nested URL Format settings. (It’s still possible to achieve the same result with a single URI Format setting.)
- When an entry type is updated, Craft now re-saves all entries of that type.
- When a category is deleted, its nested categories are no longer deleted with it.
- Craft no longer re-saves *all* localizable elements after a new site is created; entries and Matrix blocks are skipped, and plugins that supply custom element types must now re-save their elements manually as well.
- The “New entry” and “New category” buttons on Entries and Categories index pages now load the Edit page for the currently-selected site. ([#2236](https://github.com/craftcms/cms/issues/2236))
- Elements now validate that custom field values will fit within their database columns, for fields with textual or numeric column types.
- User photos are now assets. ([#933](https://github.com/craftcms/cms/issues/933))
- Assets now have a “Link” table attribute option.
- Volumes’ “Base URL” settings can now begin with `@web`, which is an alias for the root URL that Craft is running from.
- Local volumes’ “File System Path” settings can now begin with `@webroot`, which is an alias for the path to the directory that `index.php` lives in.
- Global Sets’ field layouts can now have custom tabs.
- Color inputs can now be left blank.
- Color values within Table fields are now represented by `craft\fields\data\ColorData` objects.
- Element titles now get a validation error if they contain any 4+ byte characters (like emoji), on servers running MySQL. ([#2513](https://github.com/craftcms/cms/issues/2513))
- Lightswitch fields that don’t have a value yet will now be assigned the default field value, even for existing elements. ([#2404](https://github.com/craftcms/cms/issues/2404))
- The system installer now sets the initial admin account’s preferred language to the site language selected in the installation wizard. ([#2480](https://github.com/craftcms/cms/issues/2480))
- Table fields now have “Min Rows”, “Max Rows”, and “Add Row Label” settings. ([#2372](https://github.com/craftcms/cms/issues/2372))
- Table fields now have “Date”, “Time”, “Lightswitch”, and “Color” column type options.
- Color fields now return a `craft\fields\data\ColorData` object, with `hex`, `rgb`, `red`, `green`, `blue`, `r`, `g`, `b`, and `luma` properties.
- Matrix fields now have “Manage blocks on a per-site basis”, “Min Blocks”, and “Max Blocks” settings.
- Matrix fields with only one block type, and equal values for the Min Blocks and Max Blocks settings, now hide the UI for adding and deleting blocks.
- Matrix fields with only one block type will now auto-create the minimum number of blocks required by the field, per the Min Blocks setting, for new elements. ([#850](https://github.com/craftcms/cms/issues/850))
- The `migrate/up` console command will now update the appropriate schema version in the database after successfully completing all migrations. ([#1907](https://github.com/craftcms/cms/issues/1907))
- Users can now set their preferred language to any supported application language. ([#847](https://github.com/craftcms/cms/issues/847))
- Users are no longer logged out when verifying a new email address on their own account. ([#1421](https://github.com/craftcms/cms/issues/1421))
- Users no longer get an exception or error message if they click on an invalid/expired email verification link and are already logged in. Instead they’ll be redirected to wherever they would normally be taken immediately after logging in. ([#1422](https://github.com/craftcms/cms/issues/1422))
- If anything prevents a user from being deleted, any changes that were made in preparation for deleting the user are now rolled back.
- Added `webp` as a web-safe image format.
- Craft now checks if the current installation can manipulate an image instead of checking against a predefined list. ([#1648](https://github.com/craftcms/cms/issues/1648), [#1545](https://github.com/craftcms/cms/issues/1545))
- The `getCsrfInput()` global function has been renamed to `csrfInput()`. (getCsrfInput() still works but produces a deprecation error.)
- The `{% cache %}` tag no longer includes the query string when storing the cache URL.
- Added the `|timestamp` Twig filter, for formatting a date as a user-friendly timestamp.
- Added the `|datetime` Twig filter, for formatting a date with a localized date+time format.
- Added the `|time` Twig filter, for formatting a date with a localized time format.
- Added the `|multisort` Twig filter, which duplicates an array and sorts it with [craft\helpers\ArrayHelper::multisort()](http://www.yiiframework.com/doc-2.0/yii-helpers-basearrayhelper.html#multisort()-detail).
- Added the `|atom` and `|rss` Twig filters, for formatting dates in Atom and RSS date formats, respectively.
- Added the `|column` Twig filter, for capturing the key/property values of a series of arrays/objects.
- Added the `|index` Twig filter, for indexing an array of arrays/objects by one of their keys/values.
- Added the `|filterByValue` Twig filter.
- Added the `|duration` Twig filter, which converts a `DateInterval` object into a human-readable duration.
- The `t` filter now always defaults to translating the given string using the `site` category unless it is otherwise specified (e.g. `myString|t('pluginhandle')`).
- The `|date` filter can be passed `'short'`, `'medium'`, `'long'`, and `'full'`, which will format the date with a localized date format.
- It is now possibly to customize the SQL of [element queries](https://docs.craftcms.com/v3/element-queries.html), and there are more choices on how the data should be returned.
- Element queries are no longer limited to 100 results by default.
- The “Failed” message in the queue HUD in the Control Panel now shows the full error message as alt text. ([#855](https://github.com/craftcms/cms/issues/855))
- Added the `convertFilenamesToAscii` config setting.
- Added the `preserveExifData` config setting, `false` by default and requires Imagick. ([#2034](https://github.com/craftcms/cms/issues/2034))
- Added the `aliases` config setting, providing an easy way to define custom [aliases](http://www.yiiframework.com/doc-2.0/guide-concept-aliases.html).
- Removed support for automatically determining the values for the `omitScriptNameInUrls` and `usePathInfo` config settings.
- It’s now possible to override Craft’s application config via `config/app.php`.
- It’s now possible to override volume settings via `config/volumes.php`.
- It’s now possible to override all plugins’ settings via `config/<plugin-handle>.php`.
- Renamed the `runTasksAutomatically` config setting to `runQueueAutomatically`.
- The `translationDebugOutput` config setting will now wrap strings with `@` characters if the category is `app`, `$` if the category is `site`, and `%` for anything else.
- All user-defined strings in the Control Panel (e.g. section names) are now translated using the `site` category, to prevent translation conflicts with Craft’s own Control Panel translations.
- Routes can now be stored on a per-site basis, rather than per-locale.
- Web requests are now logged to `storage/logs/web.log`.
- Web requests that result in 404 errors are now logged to `storage/logs/web-404s.log`.
- Console requests are now logged to `storage/logs/console.log`.
- Queue requests are now logged to `storage/logs/queue.log`.
- Craft 3 now requires PHP 7.0.0 or later.
- Craft 3 now requires MySQL 5.5+ or PostgreSQL 9.5+.
- Craft now takes advantage of the [PHP Intl extension](http://php.net/manual/en/book.intl.php) when available.
- Craft now uses Stringy for better string processing support.
- Craft now uses Flysystem for better asset volume support.
- Craft now uses Swiftmailer for better email sending support.
- Craft now uses the [Yii 2 Queue Extension](https://github.com/yiisoft/yii2-queue) for managing background tasks.
- Craft now uses the Zend Feed library for better RSS and Atom processing support.
- Updated Yii to 2.0.15.1.
- Updated Twig to 2.4.
- Updated Guzzle to 6.3.

### Deprecated
- Many things have been deprecated. See [Changes in Craft 3](https://docs.craftcms.com/v3/changes-in-craft-3.html) for a complete list.

### Fixed
- Fixed a bug where a PHP session would be started on every template rendering request whether it was needed or not. ([#1765](https://github.com/craftcms/cms/issues/1765))

### Security
- Craft uses OpenSSL for encryption rather than mcrypt, which is far more secure and well-maintained.
