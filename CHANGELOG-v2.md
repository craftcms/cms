# Release Notes for Craft CMS 2.x

## Unreleased

### Fixed
- Fixed a SQL error that could occur when merging two elements together if MySQL was set to a case-sensitive collation. ([#3539](https://github.com/craftcms/cms/issues/3539))

## 2.7.4 - 2018-11-27

### Fixed
- Fixed a PHP error that could occur in some cases when calling `CategoriesService::getAllGroupIds()` and `getAllGroups()` when `getGroupById()` had been called previously with an invalid category group ID.

### Security
- Update jQuery File Upload to 9.28.0.

## 2.7.3 - 2018-10-23

### Changed
- Single sections’ entry types’ handles are now updated to match their section’s handle whenever the section is saved. ([#2824](https://github.com/craftcms/cms/issues/2824))
- Animated GIF thumbnails are no longer animated. ([#3110](https://github.com/craftcms/cms/issues/3110))
- Craft now throws an exception if an asset is uploaded successfully but its record can’t be saved.
- Updated jQuery Touch Events to 2.0.0.
- Updated Garnish to 0.1.29.

### Fixed
- Fixed a bug where the Dev Mode indicator strip wasn’t visible on Chrome/Windows when using a scaled display. ([#3259](https://github.com/craftcms/cms/issues/3259))
- Fixed bug where an error would be logged if `IOHelper::clearFolder()` was called on an empty folder.

## 2.7.2 - 2018-08-24

### Changed
- Updated Garnish to 0.1.27.

### Fixed
- Fixed a PHP error that occurred on servers running PHP 5.4 - 5.5.

## 2.7.1 - 2018-08-23

### Changed
- Craft now throws an exception when validating a custom field that is missing its field type, rather than allowing a PHP error to occur.

### Fixed
- Fixed a PHP error that occurred when compiling templates with `{% cache %}` tags, on servers running PHP 7.2.

## 2.7.0 - 2018-07-31

### Added
- Added PHP 7.2 compatibility.
- Added `phpseclib/mcrypt_compat` as a shim for Mcrypt compatibility for people running PHP 7.2+.

### Changed
- When uploading a file to an Assets field, Craft will automatically sort the file list to show the latest uploads first. ([#2812](https://github.com/craftcms/cms/issues/2812))
- Updated Twig to 1.35.4.
- Updated Yii to 1.1.20.
- Updated Garnish to 0.1.26.
- Updated svg-sanitize to 0.9.0.
- Updated LitEmoji to 1.4.1.

### Fixed
- Fixed a bug where Dropdown fields on an element index view could show an incorrect selected value in limited circumstances.
- Fixed a bug where `JsonHelper::sendJsonHeaders()` was overriding the `Cache-Control` header even if it had already ben explicitly set. ([craftcms/element-api#74](https://github.com/craftcms/element-api/issues/74))

## 2.6.3019 - 2018-06-29

### Fixed
- Fixed a bug where dropdowns in the Control Panel weren’t pre-selecting the correct value.

## 2.6.3018 - 2018-06-25

### Changed
- Updated Garnish to 0.1.24.
- From now on the root folder for Local Asset Sources will be created, if it doesn't exist.
- Leading/trailing whitespace characters are now stripped from element titles on save. ([#3020](https://github.com/craftcms/cms/issues/3020))
- The PHP Info utility no longer displays the original values for settings and only the current environment value. ([#2990](https://github.com/craftcms/cms/issues/2990))

### Fixed
- Fixed a bug where Craft would show a nag alert in the Control Panel when the licensed edition wasn’t cached.
- Fixed a bug where Dropdown fields could show an incorrect selected value in limited circumstances.
- Fixed a PHP error that would occur when trying to access Asset Sources from the command line.

## 2.6.3017 - 2018-06-05

### Changed
- Improved the contrast of success and error notices in the Control Panel to meet WCAG AA requirements. ([#2885](https://github.com/craftcms/cms/issues/2885))
- Craft will no longer discard any preloaded elements when setting the `with` param on an `ElementCriteriaModel`, fixing a bug where disabled Matrix blocks could show up in Live Preview if any nested fields were getting eager-loaded. ([#1576](https://github.com/craftcms/cms/issues/1576))
- `email.beforeSendEmail` events now have a `sent` parameter, which can be set to `true` if a plugin has sent the email, and `EmailService::sendEmail()` should return `true`. ([#2917](https://github.com/craftcms/cms/pull/2917))
- Improved the performance of element queries when a lot of values were passed into a param, such as `id`, by using `IN()` and `NOT IN()` conditions when possible. ([#2937](https://github.com/craftcms/cms/pull/2937))
- Updated Redactor to 2.13.
- Updated Garnish to 0.1.23.

## 2.6.3016 - 2018-05-15

### Added
- Added the `preserveCmykColorspace` config setting, which can be set to `true` to prevent images’ colorspaces from getting converted to sRGB on environments running ImageMagick.
- Added the `transformGifs` config setting, which can be set to `false` to prevent GIFs from getting transformed or cleansed. ([#2845](https://github.com/craftcms/cms/issues/2845))

### Changed
- Edit User pages will now warn editors when leaving the page with unsaved changes. ([#2832](https://github.com/craftcms/cms/issues/2832))
- Rich Text fields with the “Clean up HTML?” setting enabled now convert non-breaking spaces to normal spaces.
- Error text is now orange instead of red. ([#2885](https://github.com/craftcms/cms/issues/2885))
- Updated Garnish to 0.1.22.

### Removed
- Removed `ConfigService::getActivateAccountPath()`.
- Removed `ConfigService::getSetPasswordPath()`.
- Removed `ConfigService::getCpSetPasswordPath()`.

### Fixed
- Fixed an error that occurred when saving a Single entry over Ajax. ([#2687](https://github.com/craftcms/cms/issues/2687))
- Fixed a bug where the `id` param was ignored when used on an eager-loaded elements’ criteria. ([#2717](https://github.com/craftcms/cms/issues/2717))
- Fixed a bug where email verification links weren’t working for publicly-registered users if the registration form contained a Password field and the default user group granted permission to access the Control Panel.

## 2.6.3015 - 2018-04-06

### Changed
- Craft no longer displays an alert in the Control Panel if the currently installed edition is _lower than_ the licensed edition.

### Fixed
- Fixed some UI issues with the upgrade modal.

## 2.6.3014 - 2018-04-04

## Changed
- Renamed the Personal edition to “Solo”.
- Updated Redactor to 2.12.

### Fixed
- Fixed a bug where Rich Text fields weren’t respecting the `toolbarFixed` Redactor config option.
- Fixed a bug where Rich Text fields would not honor the `imageTag` config setting when inserting an image.
- Fixed a bug where the `modifyAssetFilename` hook was being run twice on asset upload. ([#2624](https://github.com/craftcms/cms/issues/2624)

### Security
- The `preventUserEnumeration` config setting is now applied to locked user accounts.
- Fixed a bug where an exception could expose a partial server path in some circumstances.

## 2.6.3013 - 2018-03-23

### Removed
- Removed support for transferring a Craft license to the current domain. (Domain transfers can be done from [Craft ID](https://id.craftcms.com) now.)
- Removed support for transferring a Commerce license to the current Craft license, or unregistering a Commerce license from the current Craft license. (Plugin license registration can be done from [Craft ID](https://id.craftcms.com) now.)

### Fixed
- Fixed a PHP error that could occur on case-sensitive file systems when loading RSS feeds. ([#2514](https://github.com/craftcms/cms/pull/2514))
- Fixed a PHP error that would occur when trying to use POP as an email protocol in Settings → Email in the Control Panel.
- Fixed a PHP error that would occur when trying to delete an Asset with an ID that didn't exist.
- Fixed a bug where any URL segments that only contained the number `0` were ignored, on paginated requests.

## 2.6.3012 - 2018-02-27

### Changed
- Craft now throws an exception if it detects that a `max_input_vars` error occurred. ([#876](https://github.com/craftcms/cms/issues/876))
- Improved styles to support 5 levels of nested user permissions. ([#2467](https://github.com/craftcms/cms/issues/2467))

### Fixed
- Fixed a bug where entry version data was not including newly-created Matrix block IDs, so they would be re-created from scratch when loading the version. ([#2498](https://github.com/craftcms/cms/issues/2498))
- Fixed an error that could occur if an email template included any Twig filters with a single underscore.
- Fixed a bug where lightswitch inputs could trigger a `change` event when they didn’t actually change. ([#2494](https://github.com/craftcms/cms/issues/2494))

## 2.6.3011 - 2018-02-21

### Changed
- Reverted the fix to ([#2433](https://github.com/craftcms/cms/issues/2433)) as it broke backwards compatibility.

### Fixed
- Fixed an error that occurred when displaying run charts in some cases.

## 2.6.3010 - 2018-02-20

### Changed
- The Control Panel now sets the `origin-when-cross-origin` referrer policy. ([#2436](https://github.com/craftcms/cms/pull/2436))
- Rich Text fields no longer parse reference tags that aren’t within a `href` or `src` attribute when displaying their form input, so the tags don’t get lost when the element is re-saved. ([#1643](https://github.com/craftcms/cms/issues/1643))

### Fixed
- Fixed a bug where run charts (e.g. the New Users widget) would always show zero results if MySQL wasn’t configured with time zone data. ([#2433](https://github.com/craftcms/cms/issues/2433))
- Fixed a bug where the New Users widget would show 8 days worth of data when its Date Range setting was set to “Last 7 days” or “Last week”.
- Fixed a bug where the New Users widget could be missing some data if the browser time zone wasn’t the same as the system time zone.

## 2.6.3009 - 2018-02-13

### Added
- Added `StringHelper::encenc()` and `decdec()`.
- Added the `|encenc` Twig filter.

### Changed
- The first column on user index tables is now labeled “User”, and there are now always dedicated “Username” and “Email” columns available. ([#2417](https://github.com/craftcms/cms/issues/2417))

### Fixed
- Fixed a bug where Craft would not save newly-assigned license keys if a `craft/config/license.key` file existed, even if it didn’t contain a valid license key.
- Fixed a bug where the “Save” button wasn’t visible on custom field layout tabs on Edit User pages.
- Fixed a bug where Craft would issue unsaved data warnings when leaving edit pages, if the form data had been modified from the `jQuery(document).ready()` event. ([#2428](https://github.com/craftcms/cms/issues/2428))

### Security
- Email passwords are now encrypted in email settings forms.

## 2.6.3008 - 2018-02-06

### Changed
- The Edit User page now shows the Permissions tab for users that have the “Assign user groups” permission, even if they don’t have the “Assign user permissions” permission.
- Users with the “Assign user groups” permission no longer need explicit permission to assign a user group, if they already belong to it. ([#2087](https://github.com/craftcms/cms/issues/2087))
- Matrix blocks’ “Delete” option is now listed before all of the “New [Block Type] above” options. ([#2400](https://github.com/craftcms/cms/issues/2400))

## 2.6.3007 - 2018-01-31

### Fixed
- Fixed some jQuery deprecation errors in the Control Panel.
- Fixed a bug where Control Panel panes with sidebars weren’t expanding to the height of their content. ([#2379](https://github.com/craftcms/cms/issues/2379))

## 2.6.3006 - 2018-01-30

### Changed
- Updated jQuery to 3.3.1 and added the [jQuery Migrate](https://github.com/jquery/jquery-migrate) plugin to maintain backwards compatibility with jQuery 2.
- Tab and field names in Field Layout Designers are no longer displayed in all-uppercase. ([#2360](https://github.com/craftcms/cms/issues/2360))
- Fields in Field Layout Designers now have tool tips that reveal their handles. ([#2360](https://github.com/craftcms/cms/issues/2360))
- Asset thumbnails can now only be generated on Control Panel requests by logged-in users.
- The Control Panel now prevents referrer information from being sent when following links, on [supporting browsers](https://caniuse.com/#search=referrer).
- Links within the Control Panel that point to a different hostname now open in a new window. ([#1206](https://github.com/craftcms/cms/issues/1206))

### Fixed
- Fixed a bug where Tags fields weren’t getting any spacing between their field labels and inputs. ([#2361](https://github.com/craftcms/cms/issues/2361))
- Fixed a bug where Tags fields were encoding special characters on tag creation, and double/triple-encoding tag names in the UI. ([#2369](https://github.com/craftcms/cms/issues/2369))
- Fixed a bug where Craft might not delete elements for locales that they no longer support if Dev Mode is enabled.

## 2.6.3005 - 2018-01-23

### Changed
- Users’ field layouts can now have multiple tabs. ([#892](https://github.com/craftcms/cms/issues/892))
- Assets fields now fail validation if a file was not uploaded successfully.

### Fixed
- Fixed a bug where replacing an Asset file would not delete the existing file in some cases.

## 2.6.3004 - 2018-01-16

### Added
- Added the [onBeforeAuthenticate](https://craftcms.com/docs/plugins/events-reference#userSession-onBeforeAuthenticate) event. ([#1161](https://github.com/craftcms/cms/issues/1161))
- Added support for most Emoji characters in Plain Text fields, for servers running PHP 5.4 or later. ([#1753](https://github.com/craftcms/cms/issues/1753))
- Added LitEmoji 1.3.

### Changed
- Redactor’s toolbar is not fixed anymore. ([#1745](https://github.com/craftcms/cms/issues/1745))

## 2.6.3003 - 2018-01-09

### Fixed
- Fixed some unexpected behavior when deleting a Matrix block for a field that had recently been made translatable. ([#2245](https://github.com/craftcms/cms/issues/2245))
- Fixed a bug where the Settings → Users → Fields page wasn’t warning users when leaving the page with unsaved changes. ([#2265](https://github.com/craftcms/cms/issues/2265))
- Fixed a bug where Dropdown and Radio Buttons fields were displaying their selected option’s value, rather than label, in element index tables. ([#2282](https://github.com/craftcms/cms/issues/2282))
- Fixed `attribute:*` and `-attribute:*` search queries when the default `subRight` search term option was enabled. ([#2270](https://github.com/craftcms/cms/issues/2270))
- Fixed a bug where native `<select>` menu options weren’t getting white backgrounds in Firefox or Internet Explorer on Windows 7 when using a Classic theme with a custom window color. ([#2272](https://github.com/craftcms/cms/issues/2272))

## 2.6.3002 - 2018-01-02

### Fixed
- Fixed a bug where password reset URL prompts were showing the macOS keyboard shortcut on Windows computers. ([#2258](https://github.com/craftcms/cms/issues/2258))
- Fixed an error that broke Edit Entry HUDs.

## 2.6.3001 - 2018-01-02

### Changed
- URL patterns defined in `craft/config/routes.php` can now begin with a verb (e.g. `POST some/path`) to restrict the route to a specific request type.
- Edit Entry pages for entries without a user-defined title now show the Title field anyway if it has any validation errors. ([#2242](https://github.com/craftcms/cms/issues/2242))
- Updated Twig to 1.35.0.
- Updated SimplePie to 1.5.1.
- Updated PEL to 0.9.6.
- Updated svg-sanitize to 0.8.2.

### Fixed
- Fixed a bug where a PHP error could occur when accessing Category elements through a console command.
- Fixed a bug where some `IOHelper` methods could create a folder with zero permission under specific circumstances.
- Fixed some unexpected behavior when deleting a Matrix block for a field that had recently been made translatable, if the owner element hadn’t been resaved yet. ([#2245](https://github.com/craftcms/cms/issues/2245))

### Security
- Fixed a Remote Code Execution vulnerability for people that have permissions to upload Assets in the Control Panel.
- Fixed a vulnerability where image cleansing was not working for uploaded JPG files under specific conditions.

## 2.6.3000 - 2017-12-07

### Changed
- `Craft.MatrixInput` JavaScript objects are now accessible via `$('.matrix').data('matrix')`. ([#2156](https://github.com/craftcms/cms/issues/2156))

### Fixed
- Fixed a race condition that could cause a PHP error when quickly saving multiple tasks.
- Fixed a bug where `ArrayHelper::stringToArray('0')` would return an empty array instead of `array('0')`. ([#2144](https://github.com/craftcms/cms/issues/2144))
- Improved the performance of some queries to the `templatecaches` tables.

### Security
- Fixed a vulnerability that made it possible to access sensitive files.

## 2.6.2999 - 2017-11-29

### Fixed
- Fixed PHP 5.3 compatibility.

## 2.6.2998 - 2017-11-28

### Changed
- `<select>` inputs in the Control Panel now get the same custom styling in Firefox and IE/Edge that Chrome and Safari get.
- Updated PhpMailer to 5.2.26.
- Improved the performance of some queries to the `templatecaches` tables when the `globally` cache tag parameter was used with large amounts of data. ([#2110](https://github.com/craftcms/cms/issues/2110))
- Plugin settings values are now run through `ModelHelper::packageAttributeValue()` before getting saved, so things like `DateTime` objects get converted to JSON-safe values before getting JSON-encoded. ([#2114](https://github.com/craftcms/cms/issues/2114))

### Fixed
- Fixed a bug where Craft would think that Rich Text field values had changed, even when they hadn’t, when leaving an edit page. ([#2098](https://github.com/craftcms/cms/issues/2098))
- Fixed a bug where Assets fields with large thumbnails were overlapping the following field in element editor HUDs. ([#1802](https://github.com/craftcms/cms/issues/1802))
- Fixed a bug where uppercase non-ASCII characters were not getting converted to their correct ASCII equivalents for element slugs, if the `limitAutoSlugsToAscii` config setting was enabled. ([#2096](https://github.com/craftcms/cms/issues/2096))
- Fixed a bug where Craft would re-install updates after reverting them.

### Security
- Fixed an XSS vulnerability in the Control Panel.

## 2.6.2997 - 2017-11-08

### Fixed
- Fixed a bug where Craft was saving entries when attempting to switch the entry type.

## 2.6.2996 - 2017-11-08

### Added
- Added `UserGroupsService::getAssignableGroups()`.
- Added `UserPermissionsService::getAssignablePermissions()`.

### Changed
- The “Assign user groups and permissions” permission has now been split into “Assign user permissions” and “Assign user groups”, and the latter now has nested permissions for each of the user groups. ([#2087](https://github.com/craftcms/cms/issues/2087))
- Users with the “Assign user permissions” permission are no longer allowed to grant new permissions to user accounts that they themselves don’t already have. ([#915](https://github.com/craftcms/cms/issues/915))
- If a user is not yet activated, but they have a password set on the account, then admins will no longer see the “Copy Activation URL” user administration option.

### Fixed
- Fixed a bug where `DateTimeHelper::wasYesterday()` was returning whether the timestamp was yesterday _in UTC_ rather than in the system time zone. ([#2086](https://github.com/craftcms/cms/issues/2086))
- Fixed a bug where the autocomplete menu in Tags fields would sometimes not go away.
- Fixed a bug where Craft would mistake `users/sendPasswordResetEmail` requests for `users/login` requests, if the Forgot Password form was submitted from the same path as the `loginPath` config setting.

## 2.6.2994 - 2017-10-31

### Added
- Added `HttpRequestService::isSingleActionRequest()`.

### Changed
- Updated Imagine to 0.7.1.3, which now preserves image IPTC data when preserving EXIF data. ([#2034](https://github.com/craftcms/cms/issues/2034))

### Fixed
- Fixed a bug where it was possible for logged-out users to access offline sites.
- Fixed a bug where front-end URLs that were generated in the Control Panel were not getting trailing slashes if the `addTrailingSlashesToUrls` config setting was enabled.
- Fixed a bug where some element rows might have not been deleted when they should have, if multiple elements were saved in a single request.
- Fixed a PHP error that occurred when updating Craft on environments running PHP 7.1 and where ZipArchive wasn’t installed.
- Fixed a PHP 7.1 compatibility issue when uploading some JPEGs while preserving EXIF data, on environments using GD.

## 2.6.2993 - 2017-10-18

### Added
- Added the [preserveExifData](https://craftcms.com/docs/config-settings#preserveExifData) config setting, which determines whether EXIF data should be discarded when transforming an image (defaults to `false`).

### Changed
- Client accounts are now allowed to access the edition upgrade modal.
- Added an `$ensureTempFileExists` argument to `UploadedFile::getInstanceByName()`, which will cause the method to return `null` if the matching file had already been moved out of its temp location (defaults to `true`).
- Added an `$ensureTempFilesExist` argument to `UploadedFile::getInstancesByName()`, which will filter out any files that have already been moved out of their temp locations (defaults to `true`).

### Fixed
- Fixed a PHP error that occurred if an empty array was passed to the `relatedTo` element criteria parameter.
- Fixed a PHP error that occurred when uploading a file to an Assets field on the front-end. ([#2018](https://github.com/craftcms/cms/issues/2018))
- Fixed a bug where `HttpRequestService::getQueryStringWithoutPath()` wasn’t including duplicate param names in the returned string. ([#2041](https://github.com/craftcms/cms/issues/2041))
- Fixed a bug where Categories fields weren’t automatically adding all of a category’s ancestors when selecting a nested category, if any of its ancestors were disabled. ([#2035](https://github.com/craftcms/cms/issues/2035))

## 2.6.2992 - 2017-10-13

### Changed
- Reduced the chance of a deadlock occurring on sites that have a high concurrent volume of element writes.
- Updated Redactor II to 2.11.

### Fixed
- Fixed a bug where any plugin that listened to the `onEndRequest` event would be ignored.
- Fixed a bug where assets uploaded to an Assets field by a front-end form would not get related to the element being saved if `setContentFromPost()` was called more than once. ([#2018](https://github.com/craftcms/cms/issues/2018))
- Fixed a bug where it was not possible to create tags with multiple words. ([#2036](https://github.com/craftcms/cms/issues/2036))

## 2.6.2991 - 2017-09-29

### Fixed
- Fixed a MySQL error that could occur when saving a disabled element with a column value that was too large for its database column.
- Fixed a PHP warning that could occur when submitting a non-numeric value for a Number field, on servers running PHP 7.
- Fixed a bug where color inputs were really narrow in Safari 11. ([#2010](https://github.com/craftcms/cms/issues/2010))
- Fixed some buggy behavior on structured element index views when collapsing/expanding elements, if no elements had been collapsed before.

### Security
- Fixed an XSS vulnerability.

## 2.6.2990 - 2017-09-15

### Changed
- Added support for the `application/font-woff2` MIME type (`.woff2`). ([#1966](https://github.com/craftcms/cms/issues/1966))
- `div.matrixblock` elements in the Control Panel now have a `data-type` attribute set to the Matrix block type’s handle. ([#1915](https://github.com/craftcms/cms/pull/1915))
- Global sets’ global template variables are now available to all templates rendered when the Template Mode is set to `site`. ([#1953](https://github.com/craftcms/cms/issues/1953))

### Fixed
- Fixed a bug where you could get a PHP error uploading some JPG files on PHP 7.1.
- Fixed a bug where user photos and site logos/icons were not taking into account the [sanitizeSvgUploads](https://craftcms.com/docs/config-settings#sanitizeSvgUploads) config setting.
- Fixed a CSRF validation error that would occur when attempting to re-login via the login modal in the Control Panel.
- Fixed a bug where transforms could break sometimes on external asset sources that used path prefix.
- Fixed a bug where transforms would not be deleted when an Asset was being moved in some cases.
- Implemented a workaround for [PHP bug #74980](https://bugs.php.net/bug.php?id=74980) that affected some Craft installs running PHP 7.1+.

### Security
- Fixed an XSS vulnerability.

## 2.6.2989 - 2017-08-15

### Added
- Added the [onLockUser](https://craftcms.com/docs/plugins/events-reference#users-onLockUser) event, which fires when a user account is locked.

### Fixed
- Fixed a bug where the PHP and DB versions the Craft Support widget passed to GitHub would not escape tildes (`~`), potentially having Markdown confuse them for strikethrough markup delimiters.
- Fixed a bug where it was possible for users to be redirected to a 404 in the Control Panel after logging in. ([#1901](https://github.com/craftcms/cms/issues/1901))
- Fixed a bug where users would get one extra login attempt than the [maxInvalidLogins](https://craftcms.com/docs/config-settings#maxInvalidLogins) config setting was set to.

## 2.6.2988 - 2017-07-28

### Changed
- Added `.m2t` to the default [allowedFileExtensions](https://craftcms.com/docs/config-settings#allowedFileExtensions) config setting value.
- `.m2t` files are now treated as videos.
- Images within field instructions are now given a max-width of 100%. ([#1868](https://github.com/craftcms/cms/issues/1868))

### Fixed
- Fixed a PHP error that could occur when logging a deprecation error in `DepreactorService`.
- Fixed a bug where Redactor was losing its custom styling in Live Preview and Element Editor modals. ([#1795](https://github.com/craftcms/cms/issues/1795))
- Fixed a bug where picturefill was not applied to thumbnails within lazy-loaded elements on element index pages.
- Fixed a visual alignment bug on Tags fields.

### Security
- Fixed a bug where admins could download arbitrary zip files from the server.
- Fixed a bug where a full server path would be disclosed if you were able to upload a file with a filename larger than 255 characters.

## 2.6.2987 - 2017-07-14

### Changed
- Added `.jp2` and `.jpx` to the default [allowedFileExtensions](https://craftcms.com/docs/config-settings#allowedFileExtensions) config setting value.
- Plugin settings now get set once all plugin classes have been loaded.

### Fixed
- Fixed a PHP error that would occur when a Rich Text field’s settings referenced an asset source that no longer existed.
- Fixed a PHP error that could occur when using HTML Purifier in a Rich Text field.

### Security
- Fixed an XSS bug in the Control Panel.

## 2.6.2986 - 2017-06-30

### Changed
- Improved the styling of locale menus on Edit Entry and Edit Categories pages. ([#1803](https://github.com/craftcms/cms/issues/1803))
- The Control Panel `font-family` declaration now checks for `"Helvetica Neue"` in addition to `HelveticaNeue`. ([#1805](https://github.com/craftcms/cms/issues/1805))

### Fixed
- Fixed a bug where emails that had inner-word underscores would get converted to `<em>` tags if a HTML body was not provided in the email. ([#1800](https://github.com/craftcms/cms/issues/1800))
- Fixed a bug where the author of a draft could not delete their own draft if they did not have the “Publish Live Changes” permission.
- Fixed a Twig error that could occur when editing a locked user account.
- Fixed a bug where element source labels could get double-encoded.

## 2.6.2985 - 2017-06-27

### Changed
- `DateTime::createFromString()` now supports dates formatted with `DateTime::ISO8601`, which is incorrectly missing the colon between the hours and minutes in the timezone offset declaration (e.g. `+0000` instead of `+00:00`).

### Fixed
- Fixed a bug where users would get an “Invalid Verification Code” error when clicking on the link in a verification email.

## 2.6.2984 - 2017-06-26

### Added
- Added the [sanitizeSvgUploads](https://craftcms.com/docs/config-settings#sanitizeSvgUploads) config setting, which determines whether SVG files should be sanitized on uploads (`true` by default).

### Changed
- The `assets.onReplaceFile` event is now fired whenever a file is replaced, not only if it happens using the `Replace file` Asset action.
- Updated HTML Purifier to 4.9.3.
- Updated Redactor II to 2.7.

### Fixed
- Fixed a bug where changing a user account’s email address to one that is already taken would silently fail.
- Fixed a bug where a validation error would occur when saving two routes with the same URL Pattern in different locales.
- Fixed a JavaScript error that would occur after sending in a support request from the Craft Support widget.
- Fixed a bug where Rackspace Asset Sources would corrupt files with trailing whitespaces when downloading them.
- Fixed a SQL error that would occur when saving a Dropdown or Radio Buttons field if the default option’s value contained quotation marks.
- Fixed a bug where asset upload prompts would not always reset between uploads.

### Security
- Fixed several XSS vulnerabilities in the Control Panel.

## 2.6.2983 - 2017-06-09

### Changed
- Date pickers’ “Previous” and “Next” buttons are now represented as arrows. ([#1538](https://github.com/craftcms/cms/issues/1538))
- Updated Yii to 1.1.19.

### Fixed
- Fixed a bug where doctype and XML declarations were getting stripped out of SVG files on upload. ([#1767](https://github.com/craftcms/cms/issues/1767))

## 2.6.2982 - 2017-06-07 [CRITICAL]

### Changed
- Updated Redactor II to 2.6.
- Updated Imagine to 0.7.1.
- Craft now requires the [PHP DOM extension](http://www.php.net/manual/en/book.dom.php) when uploading SVG files.

### Security
- Fixed a potential user enumeration attack vector when authenticating a user.
- Craft will now sanitize uploaded SVG files to prevent a potential XSS attack vector.

## 2.6.2981 - 2017-05-31

### Changed
- Improved the readability of field instructions.
- Updated jQuery Timepicker to 1.11.11.

### Fixed
- Fixed a bug where clicking Enter/Return on a time field with a manually-entered time would change the value to the closest rounded time value. ([#1720](https://github.com/craftcms/cms/issues/1720))
- Fixed a bug where resaving Elements task would fail in some cases.
- Fixed a bug where entries’ titles weren’t getting updated automatically after saving a Section, for Entry Types with dynamic titles. ([#1728](https://github.com/craftcms/cms/issues/1728))
- Fixed a bug where the Get Help widget would check for the existence of the log file path when trying to zip up site templates, if that option was selected.
- Fixed a bug where the Edit Entry page wouldn’t show the current revision notes for the current entry if it was displaying any validation errors. ([#1747](https://github.com/craftcms/cms/issues/1747))
- Fixed a bug where the revision dropdown on the Edit Entry page would attribute the current version to the entry author, if version history wasn’t known for the entry. ([#1746](https://github.com/craftcms/cms/issues/1746))

## 2.6.2980 - 2017-05-13

### Fixed
- Fixed a bug where action requests on the front-end were getting treated like CP requests in the TemplatesService, breaking Live Preview, and possibly other things.

## 2.6.2979 - 2017-05-12

### Added
- Added the new Craft Support widget.

### Changed
- The Field Layout Designer is now using the default font instead of the Coming Soon font. ([#1537](https://github.com/craftcms/cms/issues/1537))
- The entry revision dropdown on the edit entry page now shows the who edited the “Current” version along with the time. ([#1650](https://github.com/craftcms/cms/issues/1650))
- Craft now checks the template mode when it tries to resolve a template for a plugin.

## 2.6.2978 - 2017-05-02

### Fixed
- Fixed a bug where Title fields on new elements could display the class name of the element by default. ([#1685](https://github.com/craftcms/cms/issues/1685))

## 2.6.2977 - 2017-05-02

### Changed
- Assets fields no longer attempt to guard against `prepValueFromPost()` getting called multiple times.
- Updated Garnish to 0.1.18.

### Fixed
- Fixed a bug where Control Panel breadcrumbs where unclickable when a flash notification was visible. ([#1675](https://github.com/craftcms/cms/issues/1675))
- Fixed a bug where Assets fields could associate the same image to multiple elements, when saving large batches of elements at once. ([#1673](https://github.com/craftcms/cms/issues/1673))
- Fixed a bug where HUDs where briefly showing up in the top-left corner of the window before getting repositioned, particularly in Safari. ([#1647](https://github.com/craftcms/cms/issues/1647))
- Fixed a bug where saving customized element index settings could wipe out all the settings in rare cases.
- Fixed a bug where elements that don’t have titles were incorrectly passing `is empty` tests in Twig.

## 2.6.2976 - 2017-04-27

### Changed
- The `_layouts/cp.html` Control Panel template now defines the `#container` element attributes within a `containerAttributes` block, so they can be overridden or added to from sub-templates. ([#1665](https://github.com/craftcms/cms/issues/1665))

### Fixed
- Fixed a bug where `HttpRequestService::getSegments()` and `getActionSegments()` could return an array that started at a non-0 number allowing for a bypass of the XSS vulnerability fix in 2.6.2974.

### Security
- Fixed a bug where it was possible to view the contents of files in the `craft/app/` folder via resource requests under certain conditions.
- Fixed a potential security vulnerability that made it possible to fire off a forgot password email with a modified URL.

## 2.6.2974 - 2017-04-21

### Changed
- Entry and category edit pages will now show any validation errors attached to the `parent` attribute.
- Updated Yii to 1.1.18.
- Updated Twig to 1.33.2.

### Fixed
- Fixed timezone bug when requesting data for a run chart in `\Craft\ChartHelper::getRunChartDataFromQuery()`

### Security
- Fixed an XSS vulnerability.

## 2.6.2973 - 2017-04-17

### Added
- Added the “HTML Purifier Config” setting to Rich Text fields. ([#1415](https://github.com/craftcms/cms/issues/1415))

### Fixed
- Fixed a bug where Craft would set a hard-coded PHP time limit of 30 seconds when uploading some types of images.
- Fixed a PHP error that occurred when using an Assets field on servers running PHP 5.3.

## 2.6.2972 - 2017-04-12

### Changed
- Images added to Rich Text fields are now nested within a `<figure>` element, for consistency with Redactor’s default image behavior. ([#1611](https://github.com/craftcms/cms/issues/1611))
- Improved the legibility of text in Live Preview in Safari. ([#1578](https://github.com/craftcms/cms/issues/1578))
- It is now possible to override the default element criteria used on index pages in the Control Panel, by modifying `Craft.defaultIndexCriteria`.

### Fixed
- Fixed a bug where clearing all caches could have unintended side effects on temporary upload folders.
- Fixed a bug where `jquery.ui.widget.js` was getting loaded twice in the Control Panel.
- Fixed a bug where submitting a “forgot password” form on the front-end without a username or password would result in an “Invalid username or email” error in addition to “Username or email is required”.
- Fixed a bug where the `users/sendPasswordResetEmail` controller action would act like the password reset email was sent successfully, even if no username/email was submitted, if the `preventUserEnumeration` config setting was enabled. ([#1605](https://github.com/craftcms/cms/issues/1605))
- Fixed a bug where Command/Ctrl/Shift-clicking on multiple elements in an element selector modal in quick succession could be mistaken for a double-click, causing the elements to become selected and the modal to close. ([#1622](https://github.com/craftcms/cms/issues/1622))

### Security
- Fixed a brute force attack vector.

## 2.6.2971 - 2017-04-07

### Fixed
- Fixed a PHP error that occurred when editing elements with a Rich Text field set to show all available asset sources.

## 2.6.2970 - 2017-04-07

### Fixed
- Fixed a bug where the first value of an Assets field within a Matrix field was getting set on all subsequent instances of that Assets field.
- Fixed a bug where Craft would not specify which required PHP extensions were missing when attempting to install.

## 2.6.2969 - 2017-04-07

### Changed
- It’s now possible to execute `AssetsFieldType::prepValueFromPost()` only once per field instance. Subsequent calls will return previous execution result.
- Disabled Matrix blocks are no longer shown in Live Preview. ([#13](https://github.com/craftcms/cms/issues/13))

### Fixed
- Fixed a PHP error that occurred when updating Craft if the OPcache restrict_api config setting was set.
- Fixed a bug where Redactor dialogs would list the Asset Source in the wrong order.
- Fixed a bug where temporary upload folders were not being created correctly.
- Fixed a bug where Craft was not redirecting users to the correct URL after login, if the site homepage had a `{% requireLogin %}` tag, and was accessed with a query string. ([#1577](https://github.com/craftcms/cms/issues/1577))
- Fixed a bug where the DeleteStaleTemplateCaches could potentially miss some caches that should be busted. ([#1593](https://github.com/craftcms/cms/issues/1593))

## 2.6.2968 - 2017-03-24

### Added
- Added Control Panel translations for Polish.

### Changed
- Removed the Delete button from entry version/draft pages. ([#1556](https://github.com/craftcms/cms/issues/1556))
- Assets fields no longer throw an exception if they can’t resolve a dynamic upload path for a disabled element.
- Updated element-resize-detector to 1.1.11.
- Updated Garnish to 0.1.17.

### Fixed
- Fixed a bug where bold text in Rich Text fields was getting set to the system’s default sans-serif font, unlike the rest of the text. ([#1441](https://github.com/craftcms/cms/issues/1441))
- Fixed a bug where jQuery DatePicker was missing some of the locale files that the Craft Control Panel is translated into.
- Fixed some Control Panel messages that weren’t getting translated. ([#1540](https://github.com/craftcms/cms/issues/1540))
- Fixed a PHP error that occurred on console requests if there was a database connection issue.
- Fixed a bug where Table fields weren’t initializing reliably in Firefox. ([#1553](https://github.com/craftcms/cms/issues/1553))

## 2.6.2967 - 2017-03-10

### Fixed
- Fixed a bug where translatable fields on new Matrix blocks would show the current application locale rather than the locale of the element being edited. ([#5](https://github.com/craftcms/cms/issues/5))
- Fixed a PHP error that could occur if you called `craft()->updates->isCriticalUpdateAvailable()` and there wasn't any cached plugin update info.
- Fixed a few bugs that prevented a console request from being able to call `craft()->entries->saveEntry()` successfully in some circumstances.
- Fixed a bug where charts were not properly taking timezone settings into account.

## 2.6.2966 - 2017-03-03

### Fixed
- Fixed a PHP error occurred when saving users on servers running PHP 5.3.

## 2.6.2965 - 2017-03-03

### Changed
- Craft database backups will no longer include the `cache` table created when the `cacheMethod` config setting is set to `'db'`. ([#1447](https://github.com/craftcms/cms/issues/1447))
- Ajax requests to controller actions that require a user session now get a 403 response rather than the Login page HTML, if the user isn’t logged in. ([#1451](https://github.com/craftcms/cms/issues/1451))
- Relational and Matrix fields now check if their values have been eager-loaded when displaying their inputs.

### Fixed
- Fixed a bug where newlines would be replaced with escaped `<br>` tags on the Settings → Email → Messages page after saving a custom email message.
- Fixed a bug where Matrix Block validation might fail when programmatically adding blocks to a Matrix field.
- Fixed a bug where bug where account activation emails were linking to the front-end rather than the Control Panel for users with access to the Control Panel.
- Fixed a bug where `EmailService::sendEmail()` and `sendEmailByKey()` were throwing exceptions if something went wrong, rather than returning `false`.
- Fixed an exception that occurred when registering a user if the email settings weren’t configured correctly.
- Fixed a bug where an “Activation email sent.” message would be displayed even if there was a problem sending the user’s activation email.

## 2.6.2964 - 2017-02-27

### Fixed
- Fixed a bug where it was not possible to access the edit page for entries and categories in the non-primary site, or entry drafts/versions.
- Fixed a bug where unordered lists in Rich Text fields weren’t getting bullets. ([#1435](https://github.com/craftcms/cms/issues/1435))
- Fixed a JavaScript error that occurred when adding a page break to Rich Text fields. ([#1433](https://github.com/craftcms/cms/issues/1433))

## 2.6.2963 - 2017-02-23

### Changed:
- Updated Redactor II to 2.2.
- Updated Garnish to 0.1.15.
- Element indexes now get a reference to their containing element selector modal via `this.settings.modal`.

### Fixed
- Fixed a bug where the `forms/checkboxSelect.html` include template wouldn’t display a checkbox option with the value of `0`. ([#1378](https://github.com/craftcms/cms/issues/1378))
- Fixed a bug where hidden login page inputs were still focusable by pressing Tab after clicking the “Forget your password?” link. ([#1387](https://github.com/craftcms/cms/issues/1387))
- Fixed a bug where the requirements checker would error if the `craft` folder and the public `index.php` file lived at the root of the file system.
- Fixed a bug where modals were super laggy, especially in Safari.
- Fixed a bug where Rich Text field settings were stating that MEDIUMTEXT columns could store 4GB, when in reality they store 16MB.
- Fixed a bug where the URL portion of newly saved routes would show HTML for any tokens that were in the route.

## 2.6.2962 - 2017-02-15

### Added
- The Control Panel now recognizes potentially-stalled tasks, and gives the user the option to retry or cancel them.

### Changed
- The Control Panel now polls for updated background task information at a variable frequency based on the speed of the currently-running task.

### Removed
- Removed `TasksController::actionGetRunningTaskInfo()`.

### Fixed
- Fixed a PHP error that occurred when auto-updating Craft if the OPcache `restrict_api` config setting was set.
- Fixed a bug where SVGs without a `viewbox` were not getting scaled correctly.
- Fixed a warning that occurred when indexing images with no content.
- Fixed a bug where the asset upload progress bar was not staying fixed in the center of the window when scrolling down the page.

### Security
- Fixed four potential XSS vulnerabilities in the Control Panel.

## 2.6.2961 - 2017-02-08

### Fixed
- Fixed an “Invalid UTF-8 sequence” error that occurred on some servers when using the deprecated `depth` element criteria param.
- Fixed a bug where custom Title field labels weren’t getting HTML-encoded on Edit Entry pages.
- Fixed a bug where editable tables may not be responsive until the window had been resized.

## 2.6.2960 - 2017-02-02

### Fixed
- Fixed a bug where `{% exit %}` tags would always result in a 500 error regardless of the exit code passed in, when Dev Mode was disabled.
- Fixed a bug where it was impossible to replace files on Rackspace Sources with whitespaces in the filename. (Thanks Thoai.)

## 2.6.2959 - 2017-01-30

### Changed
- Increased the minimum width of HUD element editors to `380px`.
- Increased the size of number fields to `10`.
- Updated element-resize-detector.js to 1.1.10.
- Updated Garnish to 0.1.12.

### Fixed
- Fixed a bug where users with the “Delete Users” permission would get the button to delete an admin even though the controller would block it.
- Fixed a deprecation error that was getting logged whenever a tag was saved within the Control Panel.
- Fixed a bug where Craft was scaling remote images up if less than 2,000 x 2,000 pixels when caching them locally.
- Fixed a bug where some PHP errors weren’t getting logged to `craft/storage/runtime/logs/phperrors.log` when Dev Mode was disabled.
- Fixed a bug where the Control Panel could become unresponsive when opening nested HUDs.
- Fixed a PHP 5.3 compatibility issue.

### Security
- Craft now catches runtime exceptions thrown by Twig and throws generic ones instead when Dev Mode is disabled, as they may include internal file/directory paths.
- Made it much harder to enumerate valid user accounts from a login page based on timing attacks.

## 2.6.2958 - 2017-01-03

### Changed
- Updated to the latest Redactor translations.
- Re-added `focusable.js` and `scroll-parent.js` to Craft’s bundled jQuery UI.

### Fixed
- Fixed a bug where no classes would get autoloaded if you had the string `vendor` in any path leading up to your Craft installation.
- Fixed JavaScript errors that occurred in the Control Panel.
- Fixed a bug where hourly charts were displaying dates in `mm/YY` format instead of `dd/mm`.

## 2.6.2957 - 2016-12-30

### Changed
- Updated HTMLPurifier to 4.8.
- Updated jQuery to 2.2.4, which fixes a [potential XSS vulnerability](https://github.com/jquery/jquery/issues/2432).
- Updated jQuery UI to 1.12.1.

### Fixed
- Fixed several PHP 7.1 compatibility issues.

## 2.6.2956 - 2016-12-28

### Changed
- Updated PHPMailer to 5.2.21, which fixes a [remote code execution vulnerability](https://legalhackers.com/advisories/PHPMailer-Exploit-Remote-Code-Exec-CVE-2016-10045-Vuln-Patch-Bypass.html).

## 2.6.2955 - 2016-12-27

### Changed
- [TagModel::getName()](https://craftcms.com/classreference/models/TagModel#getName-detail) now logs a deprecation error. Use the `title` property instead.
- The `childOf`, `childField`, `parentOf`, and `parentField` element criteria params now log deprecation errors. Use the `relatedTo` param instead.
- The `depth` element criteria param now logs a deprecation error. Use the `level` param instead.
- The `name` tag criteria param now logs a deprecation error. Use the `title` param instead.
- The `order` tag criteria param now logs a deprecation error if `"name"` is passed into it. Order by `"title"` instead.
- Craft now uses ImageMagick to read and manipulate image EXIF data, when it’s available.
- Updated PHPMailer to 5.2.18, which fixes a [remote code execution vulnerability](https://legalhackers.com/advisories/PHPMailer-Exploit-Remote-Code-Exec-CVE-2016-10033-Vuln.html).
- Updated Garnish to 0.1.9.
- Updated Velocity to 1.4.1.

### Fixed
- Fixed a bug where files could be inadvertently deleted on remote Asset sources in rare circumstances.
- Fixed a bug where Craft would sometimes ignore the [maxCachedCloudImageSize](https://craftcms.com/docs/config-settings#maxCachedCloudImageSize) config setting.
- Fixed a bug where some messages on the self-update page were not getting translated.

## 2.6.2954 - 2016-12-08

### Fixed
- Fixed a PHP error that occurred on servers running PHP 5.3.

## 2.6.2953 - 2016-12-07

### Changed
- OPcache file caches are row cleared during auto-updates, preventing possible PHP/SQL errors from occurring after the new files have been put in place but before OPcache would have cleared its caches on its own. (This will only take effect in future updates, unfortunately. Servers with OPcache enabled may continue to experience unexpected behaviors while auto-updating until they have updated to this release.)
- Updated Redactor to 1.3.2.

### Fixed
- Fixed a bug where [IoHelper::getFiles()](https://craftcms.com/classreference/helpers/IOHelper#getFiles-detail) wasn’t returning files without extensions when the `$suppressErrors` argument was `true`.
- Fixed a bug where [IoHelper::getFiles()](https://craftcms.com/classreference/helpers/IOHelper#getFiles-detail) was returning subfolder paths when the `$suppressErrors` argument was `false`.
- Fixed a bug where [IoHelper::getFiles()](https://craftcms.com/classreference/helpers/IOHelper#getFiles-detail) wasn’t normalizing the returned file paths.
- Fixed a bug where you would get a MySQL data truncation error if you had a template that used a `{% cache %}` tag and there were more than 250 characters in the request path.
- Fixed a JavaScript error that occurred when dragging asset folders.

## 2.6.2952 - 2016-11-21

### Changed
- `craft()->getBuild()`, `craft()->getTrack()`, and `craft()->getReleaseDate()` now somewhat resemble their former behavior, but will now create deprecation logs.

### Fixed
- Fixed a JavaScript error that occurred when selecting an element on an element index page.
- Fixed a bug where the Globals section of the Control Panel was only showing up to 100 global sets.

## 2.6.2951 - 2016-11-21

### Changed
- Craft no longer has the concept of “update tracks”, and is now capable of auto-updating between stable/beta/alpha releases.
- Database backups now have a random string at the end of the filename, fixing a bug where if two separate users created a backup on the exact same second, the backups would get woven together into the same file.
- `craft()->getVersion()` now returns the full version number (e.g. “2.6.2951”) rather than just the X.Y parts.

### Deprecated
- `craft()->getBuild()`, `craft()->getReleaseDate()`, and `craft()->getTrack()` are now deprecated, and return `null`.
- The template functions `craft.getBuild()` and `craft.getReleaseDate()` are now deprecated, and return `null`.

### Fixed
- Fixed a bug where [NumberFormatter::formatCurrency()](https://craftcms.com/classreference/etc/i18n/NumberFormatter#formatCurrency-detail) wasn’t respecting the `$stripZeroCents` argument if the formatted currency string placed the currency symbol at the end.
- Fixed a bug where the Assets index page was assuming all sources were normal asset volumes, which may not be the case if plugins are registering custom sources with the [modifyAssetSources](https://craftcms.com/docs/plugins/hooks-reference#modifyAssetSources) hook.
- Fixed a bug where a previous migration that improves the index on the templatecaches table wasn’t actually executing.
- Fixed a bug where only the first 100 global sets were showing up on Settings → Globals.
- Fixed a PHP error that occurred when calculating the difference between two `DateTime` objects, if one of them crossed from Daylight Savings Time to Standard Time.
- Fixed a z-index conflict that made it impossible to edit a link within a Rich Text field when it was being edited within an Element Editor HUD.
- Fixed a bug where Element Editor HUD positions weren’t responding to browser resizes.
- Fixed a bug where log files were not always rotating out correctly on Windows servers, leading to extremely huge log files.

## 2.6.2950 - 2016-10-31

### Added
- Added [ElementHelper::setNextPrevOnElements()](https://craftcms.com/classreference/helpers/ElementHelper#setNextPrevOnElements-detail).

### Changed
- [getNext()](https://craftcms.com/classreference/models/BaseElementModel#getNext-detail) and [getPrev()](https://craftcms.com/classreference/models/BaseElementModel#getPrev-detail) now work as expected for eager-loaded elements.
- Sped up template cache queries.
- Improved the styling of the Plugins index page on smaller screens.
- Updated Redactor to 1.3.1.

### Fixed
- Fixed a PHP notice that was occurring on some environments.
- Fixed a bug where element titles weren’t getting indexed by the Search service for non-current locales when saving a brand new element.
- Fixed a bug where Live Preview was not updating when Matrix blocks were reordered.
- Fixed a bug where menu option shortcut hints were not aligned correctly in Firefox.

## 2.6.2949 - 2016-10-05

### Added
- Added the [deferPublicRegistrationPassword](https://craftcms.com/docs/config-settings#deferPublicRegistrationPassword) config setting.
- Added the `initSQLs` DB config setting (set from `config/db.php`), which will get passed to [CDbConnection::initSQLs](http://www.yiiframework.com/doc/api/1.1/CDbConnection#initSQLs-detail).
- Added the `attributes` DB config setting (set from `config/db.php`), which will get passed to [CDbConnection::attributes](http://www.yiiframework.com/doc/api/1.1/CDbConnection#attributes-detail). This can be used to enable SSL database connections.

### Changed
- The Edit User form now has `autocomplete="off"` set, which should prevent LastPass from auto-filling the Email field when Settings → Advanced → “Allow pages to disable autofill” is enabled.
- `Craft.BaseElementSelectorModal` and `Craft.BaseElementSelectInput` now have a `showLocaleMenu` setting, which controls whether the Locale menu should be visible within element index modals.

### Fixed
- Fixed a bug where Rich Text fields weren’t loading properly when viewing the Control Panel in Portuguese.
- Fixed a PHP error that occurred when setting the `$ignore` argument on [StringHelper::normalizeKeywords()](https://craftcms.com/classreference/helpers/StringHelper#normalizeKeywords-detail).

## 2.6.2945 - 2016-09-27

### Fixed
- Fixed a bug where [IOHelper::copyFolder()](https://craftcms.com/classreference/helpers/IOHelper#copyFolder-detail) was not working on Windows servers.
- Fixed a bug where Craft could think that content changes had been made on Edit Entry/Category/etc. pages after opening and closing Live Preview, without making any changes.

### Security
- Added the [validateUnsafeRequestParams](https://craftcms.com/docs/config-settings#validateUnsafeRequestParams) config setting, which can be enabled to prevent certain Denial of Service (DoS) attack vectors.
- Added [HttpRequestService::getValidatedPost()](https://craftcms.com/classreference/services/HttpRequestService#getValidatedPost-detail).

## 2.6.2944 - 2016-09-22

### Added
- Added [UserModel::setGroups()](https://craftcms.com/classreference/models/UserModel#setGroups-detail).

### Fixed
- Fixed a bug where [ChartHelper::getRunChartDataFromQuery()](https://craftcms.com/classreference/helpers/ChartHelper#getRunChartDataFromQuery-detail) wasn’t factoring in the system’s Timezone setting.
- Fixed a bug where [UserModel::getGroups()](https://craftcms.com/classreference/models/UserModel#getGroups-detail) was not always respecting the user’s latest group assignments.
- Fixed a bug where animated GIFs were losing their animations when uploaded to a server running an older version of ImageMagick than 6.8.8-3.
- Fixed a bug that broke asset uploading via Assets fields in some cases.

## 2.6.2940 - 2016-09-20

### Added
- The Control Panel is now translated for Brazilian Portuguese (pt_br).

### Changed
- Login forms no longer reveal that a user account is locked, unless the correct username and password was entered.
- Craft no longer exposes full file paths in some image manipulation-related exceptions.
- Craft now suppresses the X-Mailer header when sending emails.

### Fixed
- Fixed a bug where [NumberFormatter::formatCurrency()](https://craftcms.com/classreference/etc/i18n/NumberFormatter#formatCurrency-detail) was incorrectly formatting currencies with a minor value of less than 10, e.g. $1.05.
- Fixed a bug where [IOHelper::copyFolder()](https://craftcms.com/classreference/helpers/IOHelper#copyFolder-detail) was not working.
- Fixed some potential PHP errors due to code that was not prepared for the possibility that [IOHelper::getFolderContents()](https://craftcms.com/classreference/helpers/IOHelper#getFolderContents-detail) could return false.
- Fixed a bug where [FieldsService::getFieldByHandle()](https://craftcms.com/classreference/services/FieldsService#getFieldByHandle-detail) would return `FieldModel` objects for fields that had been deleted earlier in the same request.
- Retroactively fixed a bug where it was possible to have multiple user groups with the same name and/or handle.
- Fixed a bug where translatable fields were not always respecting the element’s locale’s orientation, when it differed from the user’s preferred locale’s orientation.
- Fixed a couple bugs related to double-clicking on elements within the Control Panel.
- Fixed a bug where the “Sensitive Craft folders should not be publicly accessible” requirement at `admin/utils/serverinfo` would incorrectly report that Craft folders were not accessible if they had been uploaded to a subfolder.

### Security
- Added the [preventUserEnumeration](https://craftcms.com/docs/config-settings#preventUserEnumeration) config setting, for the über security-conscious.

## 2.6.2931 - 2016-09-08

### Changed
- Improved the legibility of light-on-dark text
- Double tapping the title bar of a Matrix block collapses/expands the block.
- Double tapping an element in an element selector modal selects the element.
- Tap-holding an element in element select inputs now shows the Element Editor HUD.
- Tap-holding an element in element index views now shows the Element Editor HUD.
- The Control Panel now includes jquery.mobile-events.js for touch events support.
- Updated Redactor to 1.2.6.

### Fixed
- Fixed a bug where HTML entities were getting encoded for plain text emails.

## 2.6.2930 - 2016-09-01

### Fixed
- Fixed a PHP error that occurred when creating a DB backup on environments running PHP 5.3.

## 2.6.2929 - 2016-08-31

### Changed
- It’s now possible to translate Plain Text fields’ placeholder messages with [static translations](https://craftcms.com/support/static-translations).

### Fixed
- Fixed a bug where the self-updater would display a nondescript error message if the environment didn’t meet all of Craft’s server requirements, rather than explaining the actual issue(s).

## 2.6.2923 - 2016-08-30

### Fixed
- Fixed a bug where Craft was not properly indexing assets whose file paths were longer than 255 characters.
- Fixed a bug where animated GIF loop limits were not being respected.
- Fixed a bug where existing compiled Control Panel templates were not always getting cleared properly when updating Craft via a manual update.
- Retroactively fixed a bug where Craft was not fully deleting entries authored by users that were automatically deleted via the [purgePendingUsersDuration](https://craftcms.com/docs/config-settings#purgePendingUsersDuration) config setting.
- Fixed a bug where textareas with auto-adjusted heights were not accounting for trailing newlines.

## 2.6.2922 - 2016-08-26

### Added
- Added `cp.categories.edit` and `cp.categories.edit.right-pane` hooks to the categories/_edit template.
- Added `cp.users.edit` and `cp.users.edit.right-pane` hooks to the `users/_edit` template.

### Fixed
- Fixed a bug where clicking on a menu option would hide the menu before the option had a chance to activate.

### Security
- Fixed a vulnerability that made it possible to execute Craft database backups in other contexts besides restoring from a failed update attempt.

## 2.6.2916 - 2016-08-25

### Changed
- Improved keyboard and accessibility support for the Select All button on element indexes.
- Improved keyboard and accessibility support for Lightswitch fields.
- Improved keyboard and accessibility support for menus.
- The `.hover` class can now be applied to selected elements.
- Improved the height approximation logic for textareas with dynamic heights.
- Improved the styling of elements within metadata panes, such as an entry’s author.
- The `_includes/forms/field` include template now assigns an `id` attribute to the `<label>` if either a `labelId` or `id` variable is passed in.
- The `_includes/forms/lightswitch` include template now assigns an `aria-labelledby` attribute to the container `<div>` if a `labelId` variable is passed in.
- [TemplatesService::namespaceInputs()](https://craftcms.com/classreference/services/TemplatesService#namespaceInputs-detail) now namespaces `aria-labelledby` attributes when `$otherAttributes` is true.

### Fixed
- `EmailService` no longer has Twig render email messages in safe mode, which was a little heavy-handed and prevented email message templates from accessing global variables and other things.
- Really, really, really fixed a bug where [ElementsService::getTotalElements()](https://craftcms.com/classreference/services/ElementsService#getTotalElements-detail) was not working correctly for some plugins that were modifying the element query.
- Fixed a bug where using `|length` on a Matrix field in a Live Preview would return the total number of saved blocks, rather than the number of blocks in the post data.
- Fixed a bug where new entry types would get added to the beginning of the section’s entry type list, if the order of the list had been customized.

## 2.6.2911 - 2016-08-16

### Added
- Added [AppPathCacheDependency](https://craftcms.com/classreference/etc/cache/dependencies/AppPathCacheDependency), which can be used in conjunction with [CacheService::set()](https://craftcms.com/classreference/services/CacheService#set-detail) to create data caches that expire when the file system path to the `craft/app/` folder changes.

### Changed
- Craft no longer flushes the entire data cache when the file system path to the `craft/app/` folder changes.
- Craft no longer sets the Content-Length header when responding to CP resource requests, fixing a potential mismatch between the header value and the actual content size if the response body was compressed its way to the client.
- Craft now disables PHPMailer’s `SMTPAutoTLS` setting, preventing an error on servers that don’t support TLS.

### Fixed
- Fixed a bug where the [email.onSendEmailError](https://craftcms.com/docs/plugins/events-reference#email-onSendEmailError) event was not firing in some cases.
- Fixed a bug where transforms that took longer than 120 seconds to generate would get cut off at 120 seconds.
- Reduced the probability that an animated GIF transform could be double-generated if the transform generation took longer than 30 seconds.
- Fixed a bug that occurred in some cases.

## 2.6.2903 - 2016-08-02

### Added
- Added the [getTableAttributesForSource](https://craftcms.com/docs/plugins/hooks-reference#getTableAttributesForSource) hook, making it possible for plugins to override the visible table attributes for a given element index source.

### Changed
- Password inputs no longer reveal the password when the Alt key is pressed, since some international keyboards require the Alt key to be pressed to type common symbols.
- [TemplatesService::render()](https://craftcms.com/classreference/services/TemplatesService#render-detail) and [renderString()](https://craftcms.com/classreference/services/TemplatesService#renderString-detail) now accept a `$safeMode` argument.
- `EmailService` now has Twig render email messages in safe mode.
- Craft now sets the flash message “User registered.” when a user is created via public registration, rather than “User saved.”.
- Craft now gracefully handles any errors or exceptions that are thrown as a result of a reference tag.

## 2.6.2804 - 2016-07-28

### Fixed
- Fixed a PHP error that was occuring on servers running PHP 5.3.
- Fixed a bug where some textareas in the Control Panel were getting some extra padding below them.

## 2.6.2798 - 2016-07-27

### Changed
- Elevated sessions can now be disabled by setting the [elevatedSessionDuration](https://craftcms.com/docs/config-settings#elevatedSessionDuration) config setting to `false`.

### Fixed
- Fixed a bug where eager-loaded relations weren’t factoring in the source elements’ locale.
- Fixed a bug where some responsive tables were not collapsing when they should on mobile.
- Fixed a MySQL error that occurred when installing Craft if MySQL’s `ENFORCE_GTID_CONSISTENCY` config setting was set to 1.
- Fixed a bug where setting `toolbarFixed: true` in Redactor configs were not working correctly when using the “Redactor I” plugin.

## 2.6.2797 - 2016-07-18

### Fixed
- Fixed a bug where [TemplatesService::getTwig()](https://craftcms.com/classreference/services/TemplatesService#getTwig-detail) was not instantiating new Twig environments for unique sets of options.
- Fixed a PHP error that occurred if a base `DateTime` object was passed into [DateTimeHelper::formatTimeForDb()](https://craftcms.com/classreference/helpers/DateTimeHelper#formatTimeForDb-detail), rather than a Craft\DateTime object.
- Fixed a PHP error that could occur when testing or saving new email settings on case-sensitive file systems.
- Fixed a bug where element thumbnails were getting distorted in IE.

## 2.6.2796 - 2016-07-10

### Changed
- Updated Redactor to 1.2.5.

### Fixed
- Really, really fixed a bug where [ElementsService::getTotalElements()](https://craftcms.com/classreference/services/ElementsService#getTotalElements-detail) was not working correctly for some plugins that were modifying the element query.
- Fixed a bug that could have prevented Craft from restoring the site to normal if a problem occurred during an update.
- Fixed a bug where thumbnails for portrait images were getting stretched into a square.

## 2.6.2795 - 2016-07-08

### Added
- Added [BaseElementModel::getHasFreshContent()](https://craftcms.com/classreference/models/BaseElementModel#getHasFreshContent-detail), making element models the ones to decide whether their content is fresh or not.

### Changed
- HUD headers and footers should now use the classes `.hud-header` and `.hud-footer`, fixing conflicts with `.header` and `.footer` classes.
- Improved the error messages for asset operations involving an invalid asset source.
- Updated jQuery Timepicker to 1.11.1, fixing some issues for users Eastern Australia.

### Fixed
- Fixed a bug where user suspension and unsuspension actions would set flash messages that indicated the action had completed successfully, even if something went wrong.
- Fixed a bug where [ElementsService::getTotalElements()](https://craftcms.com/classreference/services/ElementsService#getTotalElements-detail) was not working correctly for some plugins that were modifying the element query.
- Fixed a bug where [StringHelper::normalizeKeywords()](https://craftcms.com/classreference/helpers/StringHelper#normalizeKeywords-detail) was not mapping some uppercase letters to their ASCII equivalents.
- Fixed some bugs with Control Panel grids.

## 2.6.2794 - 2016-06-29

### Fixed
- Fixed a bug that prevented Asset sources that did not have public URLs from saving.

## 2.6.2793 - 2016-06-28

### Added
- Added the [useSslOnTokenizedUrls](https://craftcms.com/docs/config-settings#useSslOnTokenizedUrls) config setting, providing an explicit way to define whether tokenized URLs should use https.
- Craft now supports [APCu](http://pecl.php.net/package/apcu) for data caching, which can be enabled by setting the [cacheMethod](https://craftcms.com/docs/config-settings#cacheMethod) config setting to `'apc'` and creating a `craft/config/apc.php` config file that returns an array with `useApcu` set to `true`.
- Added [UrlHelper::getProtocolForTokenizedUrl()](https://craftcms.com/classreference/helpers/UrlHelper#getProtocolForTokenizedUrl-detail).

### Changed
- The Twig environment is now registered before plugins have the opportunity to register custom Twig extensions, so calling [TemplatesService::getTwig()](https://craftcms.com/classreference/services/TemplatesService#getTwig-detail) from a plugin’s [addTwigExtension](https://craftcms.com/docs/plugins/hooks-reference#addTwigExtension) will work as expected.
- Updated Imagine to add ImageMagick 7 support.

### Fixed
- Fixed a bug where some element types’ reference tags within Rich Text fields were not getting stored and parsed correctly on case-sensitive file systems.
- Fixed a bug where users would get redirected to /dashboard on the front-end after registering their account, in some cases.
- Fixed a bug where console requests were not respecting the [appId](https://craftcms.com/docs/config-settings#appId) config setting.
- Fixed a MySQL error that occurred when installing a plugin with a record that defined a composite primary key on its table.
- Fixed a bug where the transform button was selectable in asset selection modals if not all selected assets had a thumbnail.
- Fixed a bug where assets were not appearing to get selected in thumb view.
- Fixed a bug where SVGs were not getting resized for thumbnails correctly if the root `<svg>` tag had percentage-based width and height dimensions.
- Fixed a bug where users with permission to upload assets would get a permission error when dragging a file directly onto an Assets field.
- Fixed a Javascript error that occurred when modifying a field layout in Firefox.

## 2.6.2791 - 2016-06-07 [CRITICAL]

### Added
- Added the [allowSimilarTags](https://craftcms.com/docs/config-settings#allowSimilarTags) config setting, which can be set to `true` to allow multiple tags to exist with names that would be identical if converted down to ASCII (e.g. Protéines, Proteines).

### Changed
- Calling [ConsoleApp::on()](https://craftcms.com/classreference/etc/console/ConsoleApp#on-detail) now lazily-attaches event listeners to uninitialized components the same way [WebApp::on()](https://craftcms.com/classreference/etc/web/WebApp#on-detail) does.
- When editing an asset, the Filename input will automatically select the entire value, sans extension, when focused, making it easy to rename a filename without changing the extension.
- When editing an asset, if the file extension is changed in the Filename input, the user will get prompted about it to make sure they intended to do so.
- When editing an asset, if the Filename input is changed and the file extension is removed in the process, it will automatically be re-appended.
- When editing an asset, if the file extension is removed from the Filename input, but the rest of the name was left alone, the user will get prompted to make sure they intended to do so.

### Fixed
- Fixed a bug where Matrix blocks’ action menus were showing “Add [block type] above” options even when the maximum number of blocks was reached.
- Fixed a bug where using the Ctrl/Command + S shortcut on the My Account page when running Craft Client would result in a 404 error.
- Fixed a bug where the unabbreviated month name “May” was being translated to the abbreviated version, for locales where they differ.
- Fixed a bug where linking to an element whose URL included a URL fragment from a Rich Text field would result in multiple URL fragments showing up in the parsed field content on the front end.
- Fixed a bug where long site names in the global sidebar were breaking on random letters, rather than between words.
- Fixed a bug where long words in the site name were not breaking properly on the Login page.
- Fixed a bug where it was not possible to download assets in Firefox. (Oh, Firefox.)
- Fixed a bug where calling [ElementsService::getTotalElements()](https://craftcms.com/classreference/services/ElementsService#getTotalElements-detail) could result in a PHP memory limit error if there was a very large number of matching elements.

### Security
- Fixed a security vulnerability that made it possible for logged-in users to access sensitive information.

## 2.6.2789 - 2016-05-26

### Changed
- It is now possible to eager-load Structure entries’ and categories’ descendants using the `'descendants'` handle.
- It is now possible to eager-load Structure entries’ and categories’ immediate children using the `'children'` handle.
- Custom login logos are now center-aligned on the Login page, rather than left-aligned with the form.
- The global sidebar is now hidden when printing a page in the Control Panel.
- Updated Redactor to 1.2.4.

### Fixed
- Fixed a MySQL error that occurred when updating from Craft 2.2 or earlier.
- Fixed a race condition where if two requests used the same token at the exact same time, and the token had a usage limit, both requests may have only counted as one usage.
- Fixed a PHP error that occurred when passing nested arrays into the `$params` argument on [UrlHelper](https://craftcms.com/classreference/helpers/UrlHelper) methods.
- Fixed a MySQL error that occurred if an empty array was passed to [FieldsService::deleteLayoutById()](https://craftcms.com/classreference/services/FieldsService#deleteLayoutById-detail).

## 2.6.2788 - 2016-05-19

### Changed
- The [preserveImageColorProfiles](https://craftcms.com/docs/config-settings#preserveImageColorProfiles) config setting is now set to `true` by default.
- Email setting tests now wrap any exceptions that occur in the new [EmailTestException](https://craftcms.com/classreference/etc/errors/EmailTestException) class, so they are easier to find in the logs.

### Fixed
- Fixed a bug where related elements within a Matrix block would appear to be selected when the block was selected.
- Fixed a MySQL error that occurred when saving a task with over 64K of settings data.
- Fixed a validation error that occurred when saving Checkboxes fields from front-end forms if the post data contained empty option values.

## 2.6.2785 - 2016-05-06

### Fixed
- Fixed a bug where some Control Panel forms were not showing password prompts for actions that required an elevated user session, resulting in 403 errors.
- Fixed a bug where the Edit User Group page was sometimes showing a password prompt when saving a new user group, even though that action does not require an elevated user session.
- Fixed a bug where `{% requireLogin %}` tags were not being enforced for users that were suspended while they had an active session.
- Fixed a bug where disabled checkboxes were not getting styled correctly in Chrome.
- Fixed a bug where some calls to [IOHelper::fileExists()](https://craftcms.com/classreference/helpers/IOHelper#fileExists-detail) and [folderExists()](https://craftcms.com/classreference/helpers/IOHelper#folderExists-detail) were incorrectly getting set to be case insensitive when the intention was to have them suppress errors.

## 2.6.2784 - 2016-05-04

### Changed
- The requirements report on `admin/utils/serverinfo` now checks if the [Fileinfo](http://php.net/manual/en/book.fileinfo.php) extension is installed.
- Field types that extend [BaseOptionsFieldType](https://craftcms.com/classreference/fieldtypes/BaseOptionsFieldType) now validate that the submitted value is one of the acceptable values.
- Improved the display of extra-long words in site names in the Control Panel sidebar.

### Fixed
- Fixed a bug where email message headings were not getting translated on Settings → Email → Messages.
- Fixed a bug where Craft was not deleting sections’ field layouts when sections were deleted. (Orphaned section field layouts will also be deleted with this update.)

### Security
- Added [Elevated User Sessions](https://craftcms.com/news/new-security-features), reducing the window where potential front-end XSS vulnerabilities can be exploited for high-target actions.
- Added the [elevatedSessionDuration](https://craftcms.com/docs/config-settings#elevatedSessionDuration) config setting.
- Added [BaseController::requireElevatedSession()](https://craftcms.com/classreference/controllers/BaseController#requireElevatedSession-detail).
- Added [ConfigService::getElevatedSessionDuration()](https://craftcms.com/classreference/services/ConfigService#getElevatedSessionDuration-detail).
- Added [UserSessionService::getElevatedSessionTimeout()](https://craftcms.com/classreference/services/UserSessionService#getElevatedSessionTimeout-detail).
- Added [UserSessionService::hasElevatedSession()](https://craftcms.com/classreference/services/UserSessionService#hasElevatedSession-detail).
- Added [UserSessionService::startElevatedSession()](https://craftcms.com/classreference/services/UserSessionService#startElevatedSession-detail).
- Added `Craft.ElevatedSessionManager` and `Craft.ElevatedSessionForm` JS classes.
- Craft now verifies files have an `image/*` MIME type before attempting to perform image operations on them, mitigating an [ImageMagick vulnerability](https://craftcms.com/news/new-security-features).

## 2.6.2783 - 2016-04-29

### Added
- Added support for variable frame delays in animated GIFs.
- Added [JsonHelper::setJsonContentTypeHeader()](https://craftcms.com/classreference/helpers/JsonHelper#setJsonContentTypeHeader-detail).

### Changed
- It is now possible for JS classes that extend `Craft.EditableTable` to customize new rows’ HTML by overriding the new `getRowHtml()` class method.
- Added an `$options` argument to [BaseController::returnJson()](https://craftcms.com/classreference/controllers/BaseController#returnJson-detail), which can be set to an array with an `expires` key, set to the number of seconds that the response should be cached by the client before expiring.
- Reduced the number of file system reads when searching for missing translations.
- The `entries/saveEntry` controller action no longer returns the entry’s CP edit URL for site requests, and no longer returns a bunch of irrelevant information about the entry’s author.

### Fixed
- Fixed a bug where [IOHelper::copyFolder()](https://craftcms.com/classreference/helpers/IOHelper#copyFolder-detail) would incorrectly log that it couldn’t copy a folder in some cases where it actually did.
- Fixed a bug that broke asset uploading when an asset source’s path was set to a UNC network share.
- Fixed a bug where the [maxCachedCloudImageSize](https://craftcms.com/docs/config-settings#maxCachedCloudImageSize) config setting was being ignored.
- Fixed a bug where Redactor’s toolbar would disappear on small screens when it switched to fixed positioning (if `toolbarFixed` hadn’t been overridden to `false` in the Redactor config).
- Fixed a bug where the Edit Category page was showing a Delete button for brand new categories.
- Fixed a bug where SVG thumbnails in the Control Panel were getting stretched in IE11.
- Fixed a bug where Craft was not restoring its old application files properly in the event an auto-update failure.

## 2.6.2781 - 2016-04-20

### Added
- Added a locale menu to the Categories index page.

### Changed
- It is now possible to translate custom element index source headings with [static translations](https://craftcms.com/support/static-translations).
- The `checkboxField` macro in `_includes/forms.html` now supports a `warning` param, for displaying a warning message alongside the checkbox field.
- Improved the performance of some cloud-based asset operations.
- `attributeLabel` is now a reserved field handle.
- The Requirement Report on `admin/utils/serverinfo` now checks if any of the `craft/` subfolders can be accessed directly via HTTP requests, and warns the user if so.
- Cookie info is no longer included in the `admin/utils/phpinfo` page.
- The Polish characters `ź` and `Ź` are now mapped to `z` and `Z` when converting a string to ASCII.
- Updated Redactor II to 1.2.3.

### Fixed
- Fixed a PHP error that occurred on front-end pages after saving a Route that used the same token twice.
- Fixed a case sensitivity issue with the reference tags generated by Rich Text fields for plugin-based element types.
- Fixed a bug where asset filenames would get corrupted in some environments if they contained certain multi-byte characters.

### Security
- The “Purify HTML?” Rich Text field setting is now enabled by default for new Rich Text fields, and now emphasizes the security risk of disabling it with a warning message.
- Fixed a potential XSS vulnerability that could occur when Dev Mode was enabled.

## 2.6.2780 - 2016-04-05

### Added
- Added the [modifyAssetFilename](https://craftcms.com/docs/plugins/hooks-reference#modifyAssetFilename) hook, giving plugins an opportunity to customize asset filenames as they are being cleansed.

### Changed
- The Recent Entries widget now displays entries’ creation dates, rather than post dates.
- [ElementsService::saveElement()](https://craftcms.com/classreference/services/ElementsService#saveElement-detail) now sets the dateCreated and dateUpdated attributes on the passed-in element model.
- [ElementCriteriaModel::nth()](https://craftcms.com/classreference/models/ElementCriteriaModel#nth-detail) no longer duplicates the `ElementCriteriaModel` object before executing the query.
- All table row values get updated after editing an element using the Edit Element action on an index page now, similar to what happens when editing an element by double-clicking on it.
- The deprecated [PathService::getTemplatesPath()](https://craftcms.com/classreference/services/PathService#getTemplatesPath-detail) and [setTemplatesPath()](https://craftcms.com/classreference/services/PathService#setTemplatesPath-detail) functions no longer cause deprecation logs to show up on the Dashboard.

### Fixed
- Fixed a bug where some application files were not being imported with the correct file path on PaaS environments like Heroku.
- Fixed a PHP error that occurred when performing some asset operations on remote sources from console requests.
- Fixed a bug where [DateTime::createFromString()](https://craftcms.com/classreference/etc/dates/DateTime#createFromString-detail) would drop time-of-day info when a `DateTime` object was passed in.
- Fixed a bug where plugin-defined element table attributes with a handle format like `field:123:foo` were getting treated as normal custom field attributes.
- Fixed a bug where relational fields’ error messages were getting floated beyond the input DOM elements.
- Fixed a bug where all elements would get a spinner when using the Edit Element action on element index pages.
- Fixed a bug where eager-loadable field types were not showing any table attribute HTML after editing an element from an index page.
- Fixed a bug where menus within content sidebars (e.g. the Edit Field Group menu on the Settings → Fields page) were not visible in IE 11.

## 2.6.2779 - 2016-03-28

### Fixed
- Fixed a PHP error that occurred when calling [TemplatesService::getTemplatesPath()](https://craftcms.com/classreference/services/TemplatesService#getTemplatesPath-detail).

## 2.6.2778 - 2016-03-28

### Added
- It is now possible to eager-load entry authors from an entry query, by including `author` in the `with` param.
- Field types that implement the [IEagerLoadingFieldType](https://craftcms.com/classreference/fieldtypes/IEagerLoadingFieldType) interface now get their values eager-loaded on element indexes when appropriate.
- Added [TemplatesService::getTemplateMode()](https://craftcms.com/classreference/services/TemplatesService#getTemplateMode-detail) and [setTemplateMode()](https://craftcms.com/classreference/services/TemplatesService#setTemplateMode-detail) methods along with the [TemplateMode](https://craftcms.com/classreference/enums/TemplateMode) enum, which should be used to switch between CP and Site template modes (rather than the now-deprecated [PathService::getTemplatesPath()](https://craftcms.com/classreference/services/PathService#getTemplatesPath-detail) and [setTemplatesPath()](https://craftcms.com/classreference/services/PathService#setTemplatesPath-detail)).
- Added [TemplatesService::getTemplatesPath()](https://craftcms.com/classreference/services/TemplatesService#getTemplatesPath-detail) and [setTemplatePath()](https://craftcms.com/classreference/services/TemplatesService#setTemplatesPath-detail).
- Added [EntryModel::setAuthor()](https://craftcms.com/classreference/models/EntryModel#setAuthor-detail).

### Changed
- The `order` criteria param is now respected when used in conjunction with a `with` param to eager-load elements in a specific order.
- Rich Text fields’ “Available Image Sources” setting has been renamed to “Available Asset Sources”, and it now affects which sources are available when selecting regular files (in addition to images).
- Image color-spaces are now preserved when the [preserveImageColorProfiles](https://craftcms.com/docs/config-settings#preserveImageColorProfiles) config setting is set to `true`.
- Improved Assets performance when loading large folder trees.

### Removed
- Removed the redundant “Link to an asset” Link menu option from Rich Text fields. (The `file` toolbar button should be used to create links to files.)

### Fixed
- Fixed a bug where relations and Matrix blocks weren’t getting eager-loaded in the correct order.
- Fixed a bug where entries that were created from an element editor HUD were not getting an author set.
- Fixed a bug where Rich Text fields would react when files were dragged-and-dropped on them, even though they don’t support drag-uploading.
- Fixed a bug where `$timepickerInput.timepicker('getTime')` would return `null` in some cases.
- Fixed a bug where setting multiple relational fields’ criteria parameters to either `:empty` or `:notempty` would not work as expected.
- Fixed a bug where the System Status setting on the General Settings page was not showing a warning message if the setting was being overridden by the [isSystemOn](https://craftcms.com/docs/config-settings#isSystemOn) config setting.
- Fixed a bug where the [defaultTemplateExtensions](https://craftcms.com/docs/config-settings#defaultTemplateExtensions) and [indexTemplateFilenames](https://craftcms.com/docs/config-settings#indexTemplateFilenames) config settings were not respected when rendering front-end templates from CP requests.
- Fixed a bug where Assets fields with dynamic subfolder paths were creating subfolders even when it wasn’t necessary (for real this time).
- Fixed a bug where images over 2GB in size were not getting indexed properly.

## 2.6.2776 - 2016-03-10

### Fixed
- Fixed a bug where image thumbnails/transforms were not getting generated properly on Rackspace sources with the Subfolder setting set.
- Fixed a bug where tables within modal windows (e.g. element index tables within element selector modals) would get collapsed into a mobile-optimized style when opening the modal a second time.
- Fixed a bug where some Thai characters were getting treated as punctuation/whitespace when generating slugs or converting strings to kebab/camel/snake/pascal-case.

## 2.6.2774 - 2016-03-09

### Added
- Added [AssetFileModel::getPath()](https://craftcms.com/classreference/models/AssetFileModel#getPath-detail).

### Changed
- All table row values get updated after editing an element on an index page now, rather than just the first column’s value.
- Reduced the number of SQL queries needed when displaying assets.
- The cog icon next to the Craft edition logo in the Control Panel footer is now clickable (when it’s there at all).
- Entry revision menus are now a little further away from the entry’s title on Edit Entry pages.
- Field labels within `meta` panes are no longer bold.

### Fixed
- Fixed a PHP error that occurred on servers running PHP 5.3.
- Fixed a bug where newly-created widget settings UIs would not always get fully initialized.
- Fixed a bug where Rich Text fields were not always using the correct text direction.
- Fixed a bug where “Eintragsdatum” was getting wrapped mid-word on Edit Entry pages.

## 2.6.2773 - 2016-03-08

### Added
- Added [JsonHelper::removeComments()](https://craftcms.com/classreference/JsonHelper#removeComments-detail).

### Fixed
- Fixed a Javascript error that occurred when showing Rich Text fields if the associated Redactor config file had any comments in it.
- Fixed a PHP error that occurred on servers running PHP 5.3.

## 2.6.2771 - 2016-03-08

### Added
- Added [eager-loading support](https://craftcms.com/docs/templating/eager-loading-elements) for elements.
- Admins can now choose which permissions the Client account has when running Craft Client.
- Added a “Default Entry Status” setting to Channel and Structure settings for non-localized sites. ([#911](https://github.com/craftcms/cms/issues/911))
- Added a “New Users” Dashboard widget that shows a chart of recently-created user accounts.
- Added a “Download file” action to asset indexes.
- Added a new “Assets in this source have public URLs” setting to asset sources, which when disabled, making it possible to define private asset sources.
- Added a new “Available Image Sources” setting to Rich Text fields, making it possible to customize which asset sources are available to select images from.
- Added a new “Available Image Transforms” setting to Rich Text fields, making it possible to customize which image transforms are available when selecting images.
- Brought back the “Link to an asset” Link menu option in Rich Text fields.
- Added the [with](https://craftcms.com/docs/templating/eager-loading-elements) `ElementCriteriaModel` param, which can be used to eager-loading sub-elements.
- Added the [withTransform](https://craftcms.com/docs/templating/eager-loading-elements#eager-loading-image-transform-indexes) param to asset-targeting `ElementCriteriaModel`s, which can be used to eager-load image transforms for all of the resulting assets.
- Added [AppHelper::normalizeVersionNumber()](https://craftcms.com/classreference/helpers/AppHelper#normalizeVersionNumber-detail) and [getMajorVersion()](https://craftcms.com/classreference/helpers/AppHelper#getMajorVersion-detail).
- Added [ChartHelper](https://craftcms.com/classreference/helpers/ChartHelper).
- Added [AssetSourcesService::getPublicSources()](https://craftcms.com/classreference/services/AssetSourcesService#getPublicSources-detail) and [getPublicSourceIds()](https://craftcms.com/classreference/services/AssetSourcesService#getPublicSourceIds-detail).
- Added [ElementsService::eagerLoadElements()](https://craftcms.com/classreference/services/ElementsService#eagerLoadElements-detail), for eager-loading sub-elements onto the given list of elements.
- Added [ElementsService::populateElements()](https://craftcms.com/classreference/services/ElementsService#populateElements-detail), for populating element models from the results of an elements query generated by [ElementsService::buildElementsQuery()](https://craftcms.com/classreference/services/ElementsService#buildElementsQuery-detail).
- Added [IElementType::getEagerLoadingMap()](https://craftcms.com/classreference/elementtypes/IElementType#getEagerLoadingMap-detail), which gives element types the opportunity to add support for custom eager-loading keywords.
- Added the [IEagerLoadingFieldType](https://craftcms.com/classreference/fieldtypes/IEagerLoadingFieldType) interface, which can be implemented by field types that wish to add eager-loading support.
- Added the [addRichTextLinkOptions](https://craftcms.com/docs/plugins/hooks-reference#addRichTextLinkOptions) hook, which gives plugins a chance to register custom Link menu options in Rich Text fields.
- Added the [elements.onPopulateElements](https://craftcms.com/docs/plugins/events-reference#elements-onPopulateElements) event.
- Added [ElementCriteriaModel::onPopulateElements()](https://craftcms.com/classreference/models/ElementCriteriaModel#onPopulateElements-detail), which is called when the elements queried by the criteria model have been populated.
- Added the [email.onSendEmailError](https://craftcms.com/docs/plugins/events-reference#email-onSendEmailError) event.
- Added some charting Javascript classes, namespaced under `Craft.charts`.
- Included d3.js (3.5.16).

### Changed
- The installed Craft edition is now displayed in the footer of the Control Panel, along with a “trial” indicator if the edition is being tested but not licensed yet.
- The Craft edition upgrade modal has been redesigned to make it much more clear which editions are active/licensed, and what actions are available.
- Edit Entry pages now show the dates the entry was created and last updated.
- Edit Category pages now show the dates the category was created and last updated.
- Improved the look of the metadata pane on Edit User pages.
- Craft will now respect “redirect” params sent on user registration requests, if the site is configured to not require email verification.
- Craft now automatically trims any spaces at the beginning/end of URL format settings.
- Background task-initiating Javascript code is no longer added to front-end Ajax responses.
- The currency symbol for USD in Arabic is now “دولار أمريكي”.
- It is now possible to specify multiple possible values in a single `{% case %}` tag within a `{% switch %}` tag, using the keyword “`or`” (e.g. `{% case "foo" or "bar" %}`).
- Updated Yii to 1.1.17.
- Updated Twig to 1.24.0.
- Updated jQuery to 2.2.1.
- Updated jQuery Timepicker to 1.8.9.
- Updated QUnit to 1.22.0.
- Updated element-resize-detector.js to 1.1.0.
- Updated Picturefill to 3.0.2.
- Updated XRegExp to 3.1.0.

### Fixed
- Fixed a bug where the user photo in the sidebar would be updated after uploading a new photo for a different user.
- Fixed a bug where image thumbnails were not getting vertically centered.
- Fixed a bug where Assets fields with dynamic subfolder paths were creating subfolders even when it wasn’t necessary.
- Fixed a bug where Assets fields with dynamic subfolder paths were not saving files in the correct subfolder is some cases, for Rackspace asset sources.
- Fixed a bug where colors were getting distorted when uploading CMYK images on servers running ImageMagick.
- Fixed a bug where errors that occurred when generating image transforms when [generateTransformsBeforePageLoad](https://craftcms.com/docs/config-settings#generateTransformsBeforePageLoad) was enabled would prevent the entire page from loading.
- Fixed a bug where [NumberFormatter::formatCurrency()](https://craftcms.com/classreference/etc/i18n/NumberFormatter#formatCurrency-detail) wouldn’t `$stripZeroCents` when it should have for locales that display the currency symbol after the number.
- Fixed a bug where Craft was not ending the request successfully before running new background tasks, on some servers with gzip compression enabled.
- Fixed a bug where the site icon had the wrong margins on RTL languages.
- Fixed a bug where the global sidebar’s nav and user account bar were overlapping each other on Internet Explorer.
- Fixed a bug where the [defaultSearchTermOptions](https://craftcms.com/docs/config-settings#defaultSearchTermOptions) config setting wasn’t being respected when passing an object into a `search` criteria param.

## 2.5.2767 - 2016-02-24

### Changed
- Updated Redactor II to _the new_ 1.2.2 (Redactor II 1.2.2 II?), which is just like last week’s 1.2.2 release, except it fixes a bug where the Ordered List button was generating unordered lists.
- Improved mouseclick detection in several areas of the Control Panel, to better-emulate native OS click detection.

### Fixed
- Fixed a bug where clicking the “Add a row” button on editable tables in the Control Panel could add multiple rows at once.
- Fixed a MySQL error that would occur when saving a Matrix field with more than 127 blocks.

## 2.5.2765 - 2016-02-23

### Changed
- Improved the performance of Control Panel pages with several text inputs.

### Fixed
- Fixed a JavaScript error that occurred on Edit Field pages.
- Fixed an error that would occur if a `{% switch %}` tag was used outside of any `{% block %}` tags in a child template, and one of its `{% case %}` tags had no sub-content.

## 2.5.2763 - 2016-02-18

### Added
- Added [FieldsService::doesFieldWithHandleExist()](https://craftcms.com/classreference/services/FieldsService#doesFieldWithHandleExist-detail).
- Added [AssetTransformsService::getUrlForTransformByAssetAndTransformIndex()](https://craftcms.com/classreference/services/AssetTransformsService#getUrlForTransformByAssetAndTransformIndex-detail).

### Changed
- Updated Redactor to 1.2.2.
- Reduced the number of SQL queries required to display already-generated image transforms.
- The Recent Entries widget now caps off at 100 entries if its Limit setting was set to `0`.

### Fixed
- Fixed a bug where Craft would use the auto-generated validation key when creating new cookies, even if the [validationKey](https://craftcms.com/docs/config-settings#validationKey) config setting was set.
- Fixed a SQL error what would occur when saving a Category Group if the Max Levels setting was set to 0.
- Fixed a bug where modals were not showing their sidebars on viewports less than 768px wide.

## 2.5.2762 - 2016-02-09

### Changed
- The [elements.onBeforeSaveElement](https://craftcms.com/docs/plugins/events-reference#elements-onBeforeSaveElement) event now gets fired a little earlier, giving plugins a chance to make some modifications that will affect the element record.

### Fixed
- Fixed a bug where permission checking wasn’t working properly on Windows servers during auto updates.
- Fixed a visual glitch that occurred in Rich Text fields when Redactor’s `structure` setting was set to `true`.
- Fixed a bug where search queries were not being restricted to the same locale as the element query.

## 2.5.2761 - 2016-01-28

### Changed
- Updated Redactor II to 1.2.1.
- Message strings within Redactor plugins are now translated.
- Added `level`, `lft`, `localeEnabled`, `name`, `postDate`, `rgt`, `root`, and `username` to the list of reserved field handles.

### Fixed
- Fixed a bug where it was possible for users to edit categories in non-permitted locales if their account’s Preferred Locale was set to a non-permitted locale.
- Fixed a PHP error on the `admin/utils/logs page if any of the logged requests had a `$_FILES` global variable.
- Fixed a bug where Number fields’ Min/Max Value settings were not allowed to store numbers with decimals, even if the Decimals setting was not set to 0.
- Fixed a bug where the site name was overlapping the site icon in the global Control Panel sidebar in Safari when the site name took up more than one line.
- Fixed a bug where the color picker HUD was not visible from Live Preview in browsers that don’t support native color inputs.
- Fixed a bug where the revision menu was not vertically aligned with the page title on Edit Entry pages.
- Fixed a couple UI glitches on Control Panel pages with both tabs and a sidebar.
- Fixed a bug where section and category group names within the New Entry/Category menus on the Entries and Categories index pages were not getting translated.

## 2.5.2760 - 2016-01-21

### Fixed
- Fixed a bug where single sections’ field layouts were getting wiped when their main section settings were re-saved.
- Fixed a bug where deleting a Matrix block type would leave orphaned columns in the Matrix field’s content table.

### Security
- Fixed a potential XSS vulnerability due to `{{ print }}` tags within `{% switch %}` tags not getting properly escaped.

## 2.5.2759 - 2016-01-14

### Changed
- Single sections’ (non-editable) Entry Type titles are now automatically updated whenever their section’s name changes.
- It is now possible to include custom URL fragments in links to entries within Rich Text fields, alongside the entry’s reference fragment (e.g. `href="my-entry#custom-fragment#entry:123"`).
- Improved the appearance of log entries within `admin/utils/logs`.
- Custom field names are now translated properly within element indexes.

### Fixed
- Fixed some bugs related to lazy-loading new Structure entries or categories within their element indexes.
- Fixed a MySQL error that occurred when creating new database backups on rare occasions.
- Fixed a bug where uploading assets with multi-byte UTF-8 characters in the filename would not work on Windows servers.
- Fixed some styling issues in the Control Panel when using a RTL language.
- Fixed a bug where the number of columns visible in Control Panel grids would stop getting updated if the window was shrunk small enough to only fit a single column.
- Fixed a bug where assets would jump up a few pixels when one was selected in the context of a search against an asset source with subfolders.
- Fixed a bug where HTML tags inside code blocks within field instruction text were getting double-encoded.
- Fixed a PHP error that occurred when `MatrixService` was accessed on console requests.
- Fixed a bug where data caches were getting stored using Yii’s auto-generated state key rather than the [appId](https://craftcms.com/docs/config-settings#appId) config setting, if it was set.

## 2.5.2757 - 2016-01-06

### Changed
- Updated Redactor II to 1.2.
- Added the Arabic, Italian, and Hungarian translations for Redactor.
- Improved the readability of Rich Text field contents.
- Improved PHP 7 support for some dependencies.
- Added support for running Craft on Windows Azure.

### Fixed
- Fixed a bug where some auto-generated asset titles were getting double spaces.
- Fixed an unclosed `<code>` tag in one of the Slovak translation strings.
- Fixed a bug where the word “Password” wasn’t getting translated properly in validation errors.
- Fixed a layout alignment issue that occurred on the Settings index page if a plugin’s name wrapped to two or more lines.
- Fixed a PHP error on the `admin/utils/logs` page that occurred on servers running PHP 5.3.
- Fixed a PHP error that occurred when passing in a value to the `$usageLimit` argument on [TokensService::createToken()](https://craftcms.com/classreference/services/TokensService#createToken-detail).
- Fixed a MySQL error that occurred when calling [TokensService::incrementTokenUsageCountById()](https://craftcms.com/classreference/services/TokensService#incrementTokenUsageCountById-detail).
- Fixed a bug where [AssetFileModel::getWidth()](https://craftcms.com/classreference/models/AssetFileModel#getWidth-detail) and [getHeight()](https://craftcms.com/classreference/models/AssetFileModel#getHeight-detail) were not always returning integers.

## 2.5.2755 - 2015-12-16

### Changed
- It is now possible for plugins to modify the variables that get passed to the email template, by changing the `variables` param on the [email.onBeforeSendEmail](https://craftcms.com/docs/plugins/events-reference#email-onBeforeSendEmail) event.
- Improved the performance and memory optimization of Craft’s database backup script.

### Fixed
- Fixed a bug where Multi-select fields were not getting updated when they were saved without any options selected.
- Fixed a bug where search results were not always getting scored correctly.
- Fixed a PHP error that occurred when viewing logs from /admin/utils/logs if any of the logs did not contain profiling info.
- Fixed a bug where some dropdown menus were getting cut off due to overflow:hidden styling.
- Fixed a Javascript error that occurred after performing batch element actions in some cases.
- Fixed a bug where it was not possible to upgrade to the Craft edition that the license key was actually set to, if the installed Craft edition was less than the licensed edition.
- Fixed a bug where verification and password reset emails were getting sent with the current user’s preferred locale if the target user didn’t have a preferred locale, rather than the site’s primary locale.
- Fixed a Twig error that occurred when registering a new user with all user permissions except “Assign user groups and permissions”, if there were any validation errors.
- Fixed a PHP error that occurred if a plugin’s releases feed was missing a `downloadUrl` or `date` property.
- Fixed a bug where [DbBackup::restore()](https://craftcms.com/classreference/etc/db/DbBackup#restore-detail) was confusing some Twig code-looking content for a database table name, and appending the DB table prefix to it.
- Fixed a PHP 7 compatibility issue.

## 2.5.2754 - 2015-12-08

### Changed
- [ElementsService::getTotalElements()](https://craftcms.com/classreference/services/ElementsService#getTotalElements-detail) now sets [buildElementsQuery()](https://craftcms.com/classreference/services/ElementsService#buildElementsQuery-detail)’s `$justIds` argument to `true` rather than overriding the resulting SELECT clause.

### Fixed
- Fixed a bug where element titles were getting double-HTML-encoded in some areas of the Control Panel.
- Fixed a bug where element indexes could show orphaned source headings for element types whose sources had not been customized yet.
- Fixed a PHP error that occurred when running really old versions of ImageMagick.

## 2.5.2753 - 2015-12-07

### Added
- Added [RichTextData::getParsedContent()](https://craftcms.com/classreference/fieldtypes/RichTextData#getParsedContent-detail), for getting a Rich Text field’s content with parsed reference tags, without having to resort to manually casting the `RichTextData` object to a string like an animal.

### Changed
- Added backwards compatibility for Redactor “I”-formatted configs, so there is no immediate need to [update your Redactor configs](https://craftcms.com/help/redactor-ii-configs) after updating to Craft 2.5.
- Added a link to the Server Info utility page in the Control Panel footer, when the current user is an Admin.
- It is now possible to downgrade to Craft Personal when running Craft Client/Pro on a public domain without being licensed to do so.
- Updated several Control Panel translation strings.

### Fixed
- Fixed a bug where the Dashboard widget manager HUD was always displaying a “You don’t have any widgets yet.” message.
- Fixed a bug where the “Show/Hide” link on the Password input on the Login page was not visible when the input had user focus.
- Fixed a bug where relational fields within Matrix fields that were set to relate elements with the same locale as the source element were not showing elements in the correct locale for brand new Matrix blocks, if the Matrix field’s owner element was being edited in a locale other than the user’s preferred locale.
- Fixed a bug where user profile photos that were uploaded from the front-end were not getting cleansed filenames.

### Security
- Fixed an XSS vulnerability in the Control Panel due to element titles not getting properly HTML-encoded.

## 2.5.2752 - 2015-12-03

### Added
- Added a “Save” button to Live Preview.

### Changed
- Made several subtle UI improvements, improving cross-browser and responsive support.
- Reverted to the pre-2.5 behavior where a CP user must manually trigger a plugin’s migrations via the “Finish up” button each time a plugin’s version changes if the plugin’s [getSchemaVersion()](https://craftcms.com/classreference/etc/plugins/IPlugin#getSchemaVersion-detail) method returns `null`.

### Fixed
- Fixed a PHP 5.3 compatibility issue that could cause a PHP error on some Control Panel pages.
- Fixed a bug that could potentially cause PHP to crash when attempting to process an image on some servers when PHP was running as a FastCGI process.
- Fixed a bug where the Update Asset Indexes tool could potentially skip one or two files per directory in rare cases.
- Fixed a PHP warning that could occur when uploading a new asset from a front-end form.
- Fixed a bug where date-picker menus were not tall enough to display display the last week in moths with five weeks.
- Fixed a JavaScript error that occurred when selecting images from Rich Text fields.
- Fixed a bug where Redactor toolbars weren’t showing up on page load in some browsers.
- Fixed a bug where the “Select transform” button was missing from asset selection modals launched from Rich Text fields.
- Fixed a bug where the “Search in subfolders” checkbox was not showing up when searching in asset sources that had subfolders.
- Fixed a bug where Date/Time fields that were set to show both Date and Time inputs were not displaying those inputs inline together.
- Fixed a z-index issue that affected time-pickers within Date/Time fields within modal windows.
- Fixed a bug where the “Save” and “Cancel” buttons on inline element creation modals could get cut off for elements that didn’t have any/many custom fields.
- Fixed a potential bug where plugin migrations would not get run if a plugin update had been released pre-Craft 2.5 with new migrations, but the update was not installed until after the site had been updated to Craft 2.5.
- Fixed a bug where customizations to element type sources were not getting applied to element selector modals when specific sources were not requested.

## 2.5.2750 - 2015-12-01

### Added
- It is now possible to create unlimited Single, Channel, and Structure sections with Craft Personal.
- It is now possible to upload user photos with Craft Personal and Craft Client.
- It is now possible to specify your business name, business tax ID (e.g. VAT), business address, and purchase notes when purchasing a Craft Client or Pro upgrade.
- It is now possible to apply coupon codes when purchasing a Craft Client or Pro upgrade.
- The entire Control Panel has been redesigned for better utilization of space on small and extra large screens, and improved usability.
- The Control Panel is now translated into Danish, Hungarian and Turkish.
- The Dashboard has been rebuilt, bringing all widget administration functions right onto the main Dashboard page, including the ability to add, edit, reorder, resize, and delete widgets.
- It’s now possible to create new entries right from Entry Selector Modals (like the ones used by Entries fields).
- It’s now possible to create new categories right from Category Selector Modals (like the ones used by Categories fields).
- It’s now possible to create, rename, reorder, and delete the source headings in the sidebar of element indexes, as well as reorder the sources.
- It’s now possible to customize the table columns for each element source.
- Asset indexes now have the option to show table columns for File Kind, Image Size, Image Width, Image Height, ID, Date Created, Date Updated, and certain custom fields.
- Category indexes now have the option to show table columns for Link, ID, Date Created, Date Updated, and certain custom fields.
- Entry indexes now have the option to show table columns for Entry Type, Author, Slug, Link, ID, Date Created, Date Updated, and certain custom fields.
- User indexes now have the option to show table columns for Full Name, Preferred Locale (on localized sites), ID, Date Created, Date Updated, and certain custom fields.
- Added a “View Mode” setting to Assets fields, with “List” and “Large Thumbnails” options.
- Added an “Admins” source to the Users index.
- Added a new “Site Icon” field to Settings → General, where admins can upload an icon image that will appear to the left of the site’s name in the global sidebar.
- The Plugins index now shows plugin icons, descriptions, and links to documentation, when available.
- Added a “Plugins” section to the Settings index page, which has links to plugins’ settings pages.
- The Updates page now shows available plugin updates, for plugins that provide a release feed.
- The Updates page now shows the release date each release was… released.
- Craft now uses the image optimization technique [recommended by Dave Newton](http://www.smashingmagazine.com/2015/06/efficient-image-resizing-with-imagemagick/) when ImageMagick is installed, resulting in a significant reduction in file sizes, without a noticeable effect on quality.
- Added support for uploading assets as data URLs in entry forms, using an input with a name formatted like `fields[myAssetsField][data][]`. It’s also possible to specify the filename, using an input with a name formatted like `fields[myAssetsField][filenames][]`.
- It is now possible to customize the behavior of search terms when setting an element criteria’s `search` param, by setting it to an array rather than just the search query string (e.g. `.search({query: "foo", subRight: true})`).
- Element queries that include the `search` param will now set a `searchScore` attribute on all of the resulting elements.
- It’s now possible to query for elements that are related to _all_ of a given set of elements, by beginning a `relatedTo` param’s `element`, `targetElement`, or `sourceElement` sub-param with `'and'` (e.g. `relatedTo: {elements: ['and', 1, 2, 3]}`).
- It’s now possible to find elements where a Matrix field does/doesn’t have any blocks, by passing `':notempty:'` or `':empty:'` onto the field’s `ElementCriteriaModel` parameter (e.g. `craft.entries.myMatrixField(':notempty:')`).
- It’s now possible to find elements where a relational field does/doesn’t have any related elements, by passing `':notempty:'` or `':empty:'` onto the field’s `ElementCriteriaModel` parameter (e.g. `craft.entries.myEntriesField(':notempty:')`).
- Added the `|camel` filter, for formatting a string in `camelCase`.
- Added the `|pascal` filter, for formatting a string in `PascalCase`.
- Added the `|snake` filter, for formatting a string in `snake_case`.
- Added the `|hash` filter, which prefixes the given string with a keyed-hash message authentication code (HMAC), for securely passing data in forms that should not be tampered with.
- Added the `|values` Twig filter, for getting an array of all the values in the passed-in array, with reset keys.
- Plugins now have “schema versions”, defined by a `getSchemaVersion()` method on the primary plugin class. Schema versions are now the determining factor in whether the site should go into Maintenance Mode when a plugin is updated, until someone runs the plugin’s migrations from the Control Panel. If a plugin’s version number changes but its schema version doesn’t, Craft will silently update its record of the plugin’s version number and get on with the request.
- Plugins can now provide the URL to their documentation via a `getDocumentationUrl()` method on the primary plugin class.
- Plugins’ updates can now be included in Craft’s update notifications, and on the Updates page in the Control Panel, by creating a JSON feed that describes the plugin’s releases, and returning the URL to it from a `getReleaseFeedUrl()` method on the primary plugin class.
- Plugins can now provide descriptions of themselves via a `getDescription()` method on the primary plugin class.
- Plugins can now explicitly declare whether or not they have settings, via the new `hasSettings()` method on the primary plugin class. By default, the method will call the plugin’s [getSettings()](https://craftcms.com/classreference/etc/plugins/IPlugin#getSettings-detail) method, and return whether that came back with anything.
- Plugins can now have icons, by saving an “icon.svg” file within their `resources/` subfolder.
- Plugins with Control Panel sections can customize their icon in the global sidebar by placing an “icon-mask.svg” file within their `resources/` subfolder.
- Plugins can now prevent themselves from being installed by returning `false` from their [onBeforeInstall()](https://craftcms.com/classreference/etc/plugins/IPlugin#onBeforeInstall-detail) method.
- Added `IWidget::getMaxColspan()` (replacing `getColspan()`), for specifying the maximum number of columns the widget is allowed to span across.
- Added `IWidget::getIconPath()`, for specifying the path to the widget’s icon SVG.
- Element types’ [getSources()](https://craftcms.com/classreference/elementtypes/IElementType#getSources-detail) methods can now include a `status` property within the source config arrays they return, which will give the source a status icon using the specified class name.
- Added [IElementType::defineAvailableTableAttributes()](https://craftcms.com/classreference/elementtypes/IElementType#defineAvailableTableAttributes-detail), replacing the now-deprecated `defineTableAttributes()`.
- Added [IElementType::getDefaultTableAttributes()](https://craftcms.com/classreference/elementtypes/IElementType#getDefaultTableAttributes-detail).
- Added [BaseElementFieldType::getAvailableSources()](https://craftcms.com/classreference/fieldtypes/BaseElementFieldType#getAvailableSources-detail).
- Added [BaseElementFieldType::getSourceOptions()](https://craftcms.com/classreference/fieldtypes/BaseElementFieldType#getSourceOptions-detail).
- Added [FileHelper::getExtensionByMimeType()](https://craftcms.com/classreference/helpers/FileHelper#getExtensionByMimeType-detail), which overrides \CFileHelper’s method of the same name, adding support for passing an actual MIME type to the `$file` argument, rather than a file path.
- Added [IOHelper::getFileKindLabel()](https://craftcms.com/classreference/helpers/IOHelper#getFileKindLabel-detail).
- Added [StringHelper::encodeMb4()](https://craftcms.com/classreference/helpers/StringHelper#encodeMb4-detail), for encoding 4-byte UTF-8 characters to avoid MySQL 3-byte character restraints.
- Added [StringHelper::parseMarkdownLine()](https://craftcms.com/classreference/helpers/StringHelper#parseMarkdownLine-detail), for formatting a single line of Markdown code, without wrapping the text in a `<p>` tag.
- Added [StringHelper::toCamelCase()](https://craftcms.com/classreference/helpers/StringHelper#toCamelCase-detail), for formatting a string in camelCase.
- Added [StringHelper::toPascalCase()](https://craftcms.com/classreference/helpers/StringHelper#toPascalCase-detail), for formatting a string in `PascalCase`.
- Added [StringHelper::toSnakeCase()](https://craftcms.com/classreference/helpers/StringHelper#toSnakeCase-detail), for formatting a string in `snake_case`.
- Added [ErrorHandler::logException()](https://craftcms.com/classreference/etc/errors/ErrorHandler#logException-detail).
- Added [FieldsService::getOrderedFieldLayoutFieldsById()](https://craftcms.com/classreference/services/FieldsService#getOrderedFieldLayoutFieldsById-detail).
- Added a `$localeId` argument to [UrlHelper::getSiteUrl()](https://craftcms.com/classreference/helpers/UrlHelper#getSiteUrl-detail) (and the corresponding `siteUrl()` template function), which can be set to a locale ID to generate a URL relative to that locale’s base site URL.
- Added a `$justIds` argument to [ElementsService::buildElementsQuery()](https://craftcms.com/classreference/services/ElementsService#buildElementsQuery-detail), which tells it to only include the `elements.id` column in the SELECT clause of the returned query.
- Added the [elements.onBeforeBuildElementsQuery](https://craftcms.com/docs/plugins/events-reference#elements-onBeforeBuildElementsQuery) event, which handlers can use to cancel the element query by setting `$event->performAction = false`.
- Added the [elements.onBuildElementsQuery](https://craftcms.com/docs/plugins/events-reference#elements-onBuildElementsQuery) event.
- Added the [elements.onBeforePerformAction](https://craftcms.com/docs/plugins/events-reference#elements-onBeforePerformAction) event, which handlers can use to cancel the element action by setting `$event->performAction = false`.
- Added the [elements.onPerformAction](https://craftcms.com/docs/plugins/events-reference#elements-onPerformAction) event.
- Added the [i18n.onAddLocale](https://craftcms.com/docs/plugins/events-reference#i18n-onAddLocale) event.
- Added the [users.onBeforeAssignUserToGroups](https://craftcms.com/docs/plugins/events-reference#users-onBeforeAssignUserToGroups) event.
- Added the [users.onAssignUserToGroups](https://craftcms.com/docs/plugins/events-reference#users-onAssignUserToGroups) event.
- Added the [getCpAlerts](https://craftcms.com/docs/plugins/hooks-reference#getCpAlerts) hook, making it possible for plugins to add alert messages to the top of Control Panel pages.
- Added the [defineAdditionalAssetTableAttributes](https://craftcms.com/docs/plugins/hooks-reference#defineAdditionalAssetTableAttributes) hook, replacing `modifyAssetTableAttributes`.
- Added the [defineAdditionalCategoryTableAttributes](https://craftcms.com/docs/plugins/hooks-reference#defineAdditionalCategoryTableAttributes) hook, replacing `modifyCategoryTableAttributes`.
- Added the [defineAdditionalEntryTableAttributes](https://craftcms.com/docs/plugins/hooks-reference#defineAdditionalEntryTableAttributes) hook, replacing `modifyEntryTableAttributes`.
- Added the [defineAdditionalUserTableAttributes](https://craftcms.com/docs/plugins/hooks-reference#defineAdditionalUserTableAttributes) hook, replacing `modifyUserTableAttributes`.
- Element Indexes now load “Element Index View” classes to control the behavior of the actual elements being displayed, making it easier for element types to customize the built-in views (Table and Thumbs), and supply additional ones.
- Added `BaseElementIndex::getViewParams()`.
- Added `BaseElementIndex::getSelectedElements()`.
- Added `BaseElementIndex::getSelectedElementIds()`.
- Added `BaseElementIndex::getSelectedViewMode()`.
- Added `BaseElementIndex::createView()`.
- Added `BaseElementIndex::selectElementAfterUpdate()`.
- Added `BaseElementIndex::selectSourceByKey()`.
- Added `BaseElementIndex::isShowingSidebar()`.
- Added `BaseElementIndex::getSelectedElements()`.
- Added `BaseElementIndex::getButtonContainer()`.
- `Craft.ElementEditor` can be used to crete new elements now.
- Added `Craft.formatDate()` for formatting dates for the current locale.
- Added some UI generation functions at `Craft.ui`.
- It’s now possible to register Garnish’s custom event types (`activate`, `textchange`, and `resize`) like any other jQuery event (e.g. `$(el).on('resize')`).
- Added `craft.request.isGet`, for determining if it’s a GET request.
- Added `craft.request.isPost`, for determining if it’s a POST request.
- Added `craft.request.isDelete`, for determining if it’s a DELETE request.
- Added `craft.request.isPut`, for determining if it’s a PUT request.
- The `checkboxField` macro in `_includes/forms.html` now checks for a `fieldLabel` param, which will cause it to output using the same field container HTML as other field macros, using the `fieldLabel` param as the main field label. (The `label` param, if it exists, will still be used to define the actual text label that should appear beside the checkbox.)
- The `checkboxSelect` and `checkboxSelectField` macros in `_includes/forms.html` now support an `includeAllOption` parameter, which can be set to `false` if you don’t want the “All” checkbox to be included in the resulting list of checkboxes.
- The `multiselect` and `multiselectField` macros in `_includes/forms.html` now support a `class` param, which can be used to append new classes to the container element.
- The `multiselect` and `multiselectField` macros in `_includes/forms.html` now support optgroups, by adding items to the `options` array in the format `{optgroup: "Optgroup Label"}`.
- The `textField` macro in `_includes/forms.html` now supports a `unit` param, which will get rendered beside the text field in the resulting HTML.
- The `text` and `textField` macros in `_includes/forms.html` now support a `title` param, for specifying the `title` attribute that should be added to the text input.
- Control Panel templates can now set `fullPageForm = true` to wrap the entire page (sans global sidebar) in a `<form>`. A “Save” button will also appear in the page header by default, which can be overridden by extending the `saveButton` block.
- Control Panel templates can now define sub navigations via a `subnav` variable, and can identify the currently selected page via a `selectedSubnavItem` variable.
- Control Panel templates can now add a `meta` class to divs that contain fields, and the fields will be displayed in a new layout optimized for metadata-type fields.
- Control Panel templates can now contain multiple related field inputs in a div with a `flex` class, which will get them to be displayed alongside each other in a single row.
- Added `_includes/nav.html` for creating navigation menus.
- The Control Panel now includes [Selectize.js](https://brianreavis.github.io/selectize.js/). Dropdown and multi-select menu containers can include the `selectize` class and be automatically replaced by Selectize inputs.
- Objects that extend `Garnish.Base` now automatically trigger a `destroy` event when their `destroy()` method is called.
- The `Craft.BaseElementIndex` class now has an `onAppendElements` event that is triggered when new elements are lazy-loaded onto an existing element view.
- The `Craft.Grid` class now has a `maxCols` setting, which defines the maximum number of columns the grid can have, regardless of how wide the viewport gets. (Default is null.)
- The `Craft.Grid` class now has an `onRefreshCols` event, which is triggered whenever grid items are added/removed, or when the number of columns changes.
- The `Garnish.HUD` class now has `minBodyWidth` and `minBodyHeight` settings.
- The `Craft.AdminTable` class now has an `onReorderObjects` event, which is triggered whenever its rows have been reordered.
- Dashboard scripts can now access the `Craft.Grid` object powering the Dashboard via `window.dashboard.grid`.
- Added the “gauge” icon to Craft’s icon font.
- Added support for a new `.btn.loading` class.
- Added support for new `.status` icon color classes, `.green`, `.orange`, `.red`, `.yellow`, `.pink`, `.purple`, `.blue`, `.turquoise`, `.light`, `.grey`, and `.black`.
- Added the [defaultSearchTermOptions](https://craftcms.com/docs/config-settings#defaultSearchTermOptions) config setting, for customizing the default behavior for search terms passed to element criterias’ `search` param.
- Added the [defaultWeekStartDay](https://craftcms.com/docs/config-settings#defaultWeekStartDay) config setting, which defines the default Week Start Day for new users.
- Added the [preserveImageColorProfiles](https://craftcms.com/docs/config-settings#preserveImageColorProfiles) config setting, which prevents Craft from optimizing image color profiles on upload.
- Rich Text fields now encode 4-byte UTF-8 characters (like emojis), allowing them to be saved without causing a MySQL error due to 3-byte character restraints.
- Included the [Source Code](https://imperavi.com/redactor/plugins/source/) plugin for Redactor II.

### Changed
- Craft Client → Pro upgrades cost $100 now, rather than $129.
- The Craft edition upgrade promo now shows in all Control Panel pages’ footers for installs that are testing Craft Client/Pro but still only licensed to use Craft Personal.
- Craft no longer shows an arresting modal window in the Control Panel when running an edition that is greater than the licensed edition. An alert is displayed at the top of the page instead.
- The Control Panel’s Login page now shows the site name if a custom site logo hasn’t been uploaded, rather than the Craft logo.
- Improved the performance of the Control Panel.
- Improved cross-browser support for the Control Panel keyboard shortcuts.
- The Control Panel now animates the horizontal positioning of flash notifications, so that they aren’t as jumpy when multiple notifications are displayed at once.
- Element Editor HUDs can now expand to take up significantly more space, and they present their fields in a much more space-optimized way.
- The table header cells on element indexes can now be clicked on to sort the elements by that column’s values (when applicable).
- The “Upload files” button in Asset selection modals has been moved to the footer.
- Improved the look of element indexes.
- Improved the look of status icons, and added support for more colors.
- Asset thumbnails now have a light checkered background, which will show through any transparency within the image.
- Asset thumbnails now share the same aspect ratio as the source image, rather than a square crop of the image.
- Improved the usability of the Live Preview panel resize handle.
- Added instruction text to Amazon S3 and Google Cloud asset source types’ Cache Duration settings.
- Date pickers now show the first letters of the week day names.
- Updated the default user icon to an SVG.
- Assets, Entries, and Users fields now list selected elements vertically rather than inline.
- Relational fields’ “Add Button Label” settings no longer store the default value if the field was left unchanged.
- The meta panes on Entry, Category, and User edit pages now span the full width of the content area when content area is two columns wide, rather than leaving an awkward gap to the left of that pane.
- Moved the “Login as [User]” action on the Edit User page into the user actions menu, rather than giving it its own button on the main page.
- Control Panel grid items are now animated whenever they change size or position.
- Elements that have both a status icon and a thumbnail now show the status icon first, followed by the thumbnail.
- The auto-generated option values within the settings of Checkboxes, Dropdown, Multi-select, and Radio Buttons fields are now generated using the same formatting rules as Handle generators (camelCase, no spaces, etc.), rather than directly copying the option’s label text.
- Rich Text fields now get the same blue glow style that other text fields get when focused.
- Swapped the previous element resize detection script out for [element-resize-detector](https://github.com/wnr/element-resize-detector), fixing some JS errors and improving cross-browser compatibility.
- Image transforms’ “Quality” settings are now represented as a dropdown menu, with the options “Auto”, “Low” (10), “Medium” (30), “High” (60), “Very High” (82), and “Maximum” (100).
- Improved the way assets’ default titles are generated, to account for dashes and case changes in the filename.
- Craft now attempts to resolve filename conflicts on upload by appending a timestamp to the end of the filename before resorting to adding an incrementing number.
- When an asset is uploaded with a conflicting filename and is automatically renamed, the number appended to the filename no longer makes it into the asset’s default title.
- ImageMagick is no longer required to generate SVG image transforms; they now result in modified SVGs.
- Redesigned the generic file icon to fit in better with the rest of the Control Panel.
- Added a “broken image” icon for images that can’t be loaded.
- The `{% paginate %}` tag no longer requires an `{% endpaginate %}` tag to follow it.
- `{% cache %}` tags are now capable of saving content that includes 4-byte unicode characters.
- It is now possible to customize the name of the page info variable that gets defined by the `{% paginate %}` tag (e.g. `{% paginate craft.entries as pageInfo, pageEntries %}`). (The variable is still called `paginate` by default.)
- The `|replace` filter now supports regular expression modifiers.
- Prevented “1052 Column 'id' in order clause is ambiguous” MySQL errors from occurring on element queries where the `order` param is set to “id” rather than “elements.id”.
- [ElementsService::buildElementsQuery()](https://craftcms.com/classreference/services/ElementsService#buildElementsQuery-detail) now applies the `order`, `limit`, and `offset` element criteria parameters to the resulting query object.
- [UserSessionService::getIsStarted()](https://craftcms.com/classreference/services/UserSessionService#getIsStarted-detail) now uses [session_status()](http://php.net/manual/en/function.session-status.php) to determine if the session has started for servers running PHP 5.4 or later.
- [StringHelper::normalizeKeywords()](https://craftcms.com/classreference/helpers/StringHelper#normalizeKeywords-detail) now removes HTML entities from the passed-in string, rather than decoding them.
- [FieldsService::assembleLayout()](https://craftcms.com/classreference/services/FieldsService#assembleLayout-detail) no longer requires the second parameter to be passed in.
- `BaseElementModel::getIconUrl()` and `getThumbUrl()` fought to the death, and `getThumbUrl()` won.
- `BaseElementIndex` objects only trigger the `onUpdateElements` event when the whole element view changes now; it’s no longer triggered when additional elements are lazy-loaded into view. Use the new `onAppendElements` event for the latter.
- Matrix fields’ container divs now get a `matrix-field` class, to differentiate them from other Matrix-styled controls.
- The custom `textchange` jQuery event now supports passing a `delay` option, preventing the event from firing until at leat a certain number of miliseconds have passed since the last time the input’s value changed.
- The [purgePendingUsersDuration](https://craftcms.com/docs/config-settings#purgePendingUsersDuration) config setting is now `false` by default.
- The [overridePhpSessionLocation](https://craftcms.com/docs/config-settings#overridePhpSessionLocation) config setting can now be set to a custom session save path.
- Craft no longer includes the backtrace when logging forced `[info]` messages.
- URL Format settings now get stored in TEXT-sized database columns, allowing for much longer URL formats.
- Updated Imagine to 0.6.3.
- Updated jQuery.timepicker to 1.8.3.
- Updated PEL to 0.9.3.
- Updated PHPMailer to 5.2.14.
- Updated Picturefill to 3.0.1
- Updated qUnit to 1.20.0
- Updated Redactor to “Redactor II” 1.1.0, along with bundled Redactor plugins.
- Updated Twig to 1.23.1.
- Updated Velocity to 1.2.3
- Updated xRegExp to 3.0.0

### Removed
- Removed the `video` and `image` buttons from the `Standard.json` example Redactor config.
- Removed PHPUnit

### Fixed
- Fixed a bug where [TemplatesService::renderMacro()](https://craftcms.com/classreference/services/TemplatesService#renderMacro-detail) would return a `Twig_Markup` object rather than a string.
- Fixed a bug where element editors were ordering custom fields by their inner-tab sort orders, but ignoring the actual tab sort orders. (So all of the tabs’ first sub-fields would get displayed first, followed by all of the tabs’ second sub-fields, etc.)
- Fixed a bug where user verification and password reset emails that were triggered by someone in the Control Panel would have URLs based on that Control Panel user’s preferred locale, rather than the recipient’s.
- Fixed a bug where PM time values were getting converted to AM for users whose Prefered Locale was Chinese.
- Fixed a bug where JavaScript-encoded transform generation URLs were getting cached by `{% cache %}` tags.
- Fixed a bug where Assets fields would try to resolve dynamic subfolder paths and create missing subfolders at times when there wenen’t even any files to place inside the subfolder to begin with.
- Fixed a bug where some GIF images were not getting transformed correctly.
- Fixed a bug where images weren’t getting uploaded successfuly if they had a width or height greater than 65,535 pixels.
- Fixed a bug where it was possible to upload non-image files to the “Login Page Logo” and “User Photo” fields in the Control Panel.
- Fixed a JavaScript error that would sometimes occur after an auto-update.
- Fixed a JavaScript error that occurred on element indexes for element types that didn’t have any defined sources yet.
- Fixed a bug where the German translation of the category group deletion confirmation message was malformed.
- Fixed potential “ambiguous column” MySQL errors that could occur when joining additional tables that have a `sortOrder` column into a query based on relational field element queries.
- Fixed a bug where some database host names were not resolving correctly due to case-sensitivity issues.
- Fixed a bug where {@services.RequestService::getQueryStringWithoutPath()} was always returning an empty string on requests without a path in the URL (e.g. the homepage).
- Fixed a MySQL error that could occur when uninstalling a plugin if one of its tables had unexpected foreign keys.
- Fixed some UI bugs that affected image cropping modals.
- Fixed some UI bugs that affected RTL languages.

## 2.4.2726 - 2015-11-20

### Added
- Added support for using Live Preview across multiple subdomains. (Requires some [server configuration changes](http://buildwithcraft.com/help/cross-domain-live-preview) as well.)

### Fixed
- Fixed a bug where time-picker inputs would always convert times to “AM” when first initialized for locales whose “AM”/“PM” symbols are made up of multi-byte unicode characters.
- Fixed a bug where entry drafts would not remember changes to the “Parent Entry” field.
- Fixed a bug where `{% cache %}` tags that output an empty string would get duplicated caches in the database on each new request.

## 2.4.2725 - 2015-11-17

### Fixed
- Fixed a PHP error that occurred when attempting to open the Edition Upgrade Modal when Craft was having problems connecting to its licensing web service.
- Fixed a PHP error that occurred when updating Craft from an old version, if the user session was lost during the update.
- Fixed a PHP error that occurred when transforming an image if one of the target dimensions got rounded down to 0.
- Fixed a bug where clicking directly on the down arrow on select inputs in the Control Panel would prevent the select menu from appearing.
- Fixed a bug where calls to Craft’s updates and licensing web server were timing out after 2 seconds.

## 2.4.2723 - 2015-11-11

### Added
- It is now possible to access the front end login page when the site is offline.

### Changed
- Craft now sets a validation error on the `UserModel` when someone attempts to upload a non-image file as their user photo from a front end form.

### Fixed
- Fixed a MySQL error that occurred when [MigrationHelper::renameTable()](https://craftcms.com/classreference/helpers/MigrationHelper#renameTable-detail) was used on a table that had a foreign key to itself.

## 2.4.2702 - 2015-10-28

### Fixed
- Fixed a bug where Assets fields weren’t visible in publish forms.

## 2.4.2701 - 2015-10-28

### Fixed
- Fixed a bug where 8-bit indexed PNGs with transparency would lose quality when uploaded to Craft, or when an image transform was applied.
- Fixed a bug where Assets fields would sometimes move files around for no apparent reason.
- Fixed a bug where Assets fields would throw an exception if they were set to restrict uploads to a single folder, if the subfolder path was invalid. A warning is displayed in place of the field input instead.
- Fixed a bug where assets could be deleted for no apparent reason.
- Fixed a bug where the whole system would come crashing down if a MySQL truncation error occurred while changing a field’s handle and type at the same time.
- Fixed a bug where PHP’s memory limit and max execution time were not being accurately reported from /admin/utils/phpinfo.
- Fixed a bug that prevented the image cropping modal from being opened twice in a row, without reloading the page.

## 2.4.2700 - 2015-10-19

### Added
- Added [ArrayHelper::getFirstKey()](https://craftcms.com/classreference/helpers/ArrayHelper#getFirstKey-detail).

### Fixed
- Fixed an error that occurred when converting a Channel section to a Structure.
- Fixed a bug where menu buttons within modal windows would collapse just as soon as they had been expanded.
- Fixed a protruding shadow next to the Save button on Edit Category pages.

## 2.4.2699 - 2015-10-16

### Fixed
- Fixed a PHP error that could occur when saving a category.

## 2.4.2698 - 2015-10-15

### Fixed
- Fixed a PHP error that occurred in environments running PHP 5.3.

## 2.4.2697 - 2015-10-15

### Added
- Added PHP 7 compatibility.
- Added support for `es_mx` and `es_ve` site locales.
- Added [AssetsService::getRootFolderBySourceId()](https://craftcms.com/classreference/services/AssetsService#getRootFolderBySourceId-detail).
- Added [IOHelper::cleanPath()](https://craftcms.com/classreference/helpers/IOHelper#cleanPath-detail)

### Changed
- Assets fields that are restricted to a single folder, and have dynamic subfolder paths, will now show previously-uploaded assets that live within that folder even for brand new elements (most of the time).
- Images uploaded from iOS now get renamed to `image_YYYYMMDD_HHMMSS.ext` (from `image.ext`), fixing a bug where iOS uploads would fail after the 51st image due to Craft’s limit on allowed same-name uploads.

### Fixed
- Fixed a bug where saving a nested entry in a Structure section that wasn’t enabled for the current locale would bump the entry to the last position among its siblings.
- Fixed a bug where [craft()->getTimezone()](https://craftcms.com/classreference/etc/behaviors/AppBehavior#getTimezone-detail) would always return “UTC” on console requests, rather than the system’s timezone.
- Fixed a bug where [Craft::log()](https://craftcms.com/classreference/Craft#log-detail) wasn’t writing to the `craft/runtime/logs/` folder on console requests.
- Fixed a bug where some profile/trace messages weren’t getting logged properly if they occurred very early in the request.
- Fixed the alignment of “info” icons on the Requirement Report on /admin/utils/serverinfo.

## 2.4.2696 - 2015-10-08

### Changed
- Added `vob` to the default [allowedFileExtensions](https://craftcms.com/docs/config-settings#allowedFileExtensions) config setting value.

### Fixed
- Fixed a bug where tables would always get collapsed, even when there’s plenty of room for them to be expanded, on certain pages.
- Fixed the styling of input placeholder text in IE 10.
- Fixed some layout issues in Assets/Users thumbnail views and other areas of the Control Panel in IE 10.

## 2.4.2695 - 2015-10-07

### Changed
- Craft now recognizes .vob files as videos.
- Craft will now collapse Control Panel tables that are too wide for their content container on desktop browsers, in the same way it already did for mobile browsers.
- Plugins can now modify the duration of the Control Panel’s task tracking interval by overriding `Craft.cp.taskTrackerUpdateInterval` and `Craft.cp.taskTrackerHudUpdateInterval`.

### Fixed
- Fixed a bug where Matrix fields’ settings would get saved even if one of their block type fields’ settings had validation errors.
- Fixed a bug where [DateTime::createFromString()](https://craftcms.com/classreference/etc/dates/DateTime#createFromString-detail) wouldn’t recognize Unix timestamps less than 10 digits long.
- Fixed a bug where the [defaultCpLanguage](https://craftcms.com/docs/config-settings#defaultCpLanguage) config setting could be set to locales that weren’t selected in Settings → Locales.
- Fixed a bug where the Craft edition upgrade banner was being displayed on non-public domains even if Craft was already running (and licensed for) Craft Pro.

## 2.4.2693 - 2015-09-24

### Changed
- Craft now logs whether emails are sent successfully while in Dev Mode.

### Fixed
- Fixed a bug where some requests that created new background tasks would hang until the task completed, on some PHP-FPM FastCGI configurations.
- Fixed a bug where exceptions thrown by Twig on the front end were not getting sent to the site’s custom 500.html template.
- Fixed a bug where Client accounts were unable to purchase Craft Client/Pro through the “Wrong Edition” modal.
- Fixed a bug where Craft was not ensuring that AM/PM strings had the correct casing when outputting dates with `a` or `A` date formatting characters.

## 2.4.2692 - 2015-09-11

### Changed
- Craft now warns you before leaving an Edit Field page with unsaved changes.
- Craft now verifies that newly-generated image transforms were actually saved successfully before updating the index.
- Improved SVG support.

### Fixed
- Fixed a bug where the [testToEmailAddress](https://craftcms.com/docs/config-settings#testToEmailAddress) config setting was not applying to CC’d and BCC’d recipients.
- Fixed a bug where [ElementsService::parseRefs()](https://craftcms.com/classreference/services/ElementsService#parseRefs-detail) was only parsing up to 100 reference tags on the given string.
- Fixed a PHP error that occurred when creating a Single section that wasn’t enabled for the current application locale.
- Fixed a bug where CP notice/error notifications were not sticking around for the intended duration (two/four seconds) on slow internet connections.

## 2.4.2691 - 2015-09-03

### Fixed
- Fixed a bug where the [postCpLoginRedirect](https://craftcms.com/docs/config-settings#postCpLoginRedirect) and [activateAccountSuccessPath](https://craftcms.com/docs/config-settings#activateAccountSuccessPath) config settings weren’t being respected during public registration on sites where the “Verify Email Addresses?” user setting was unchecked.
- Fixed a bug where moving images on remote sources didn’t work as expected in some circumstances.
- Fixed the styling of the Locale menu within Route settings.
- Fixed several Control Panel layout bugs that occurred in Firefox.
- Fixed a “Safari cannot open this page because the address is invalid” notice that would appear in the Control Panel on iPads running iOS 7.

## 2.4.2688 - 2015-08-24

### Added
- Added the [showBetaUpdates](https://craftcms.com/docs/config-settings#showBetaUpdates) config setting.

### Changed
- Added support for some Polish ASCII character mappings.
- The browser window no longer scrolls when selectables are clicked on.
- It is now possible to access `Craft.Grid` objects from their container elements, via `.data('grid')`.
- It is now possible to access `Craft.BaseElementSelectInput` objects from their container elements, via `.data('elementSelect')`.

### Fixed
- Fixed an issue where Control Panel icons weren’t getting positioned correctly in Chrome/Windows.
- Fixed an error that would occur when the [rotateImagesOnUploadByExifData](https://craftcms.com/docs/config-settings#rotateImagesOnUploadByExifData) config setting was set to `false`.

## 2.4.2684 - 2015-08-12

### Changed
- Craft now responds with a 503 status code when there is a database connection error or Craft is not yet installed, rather than 404.
- Rich Text fields will no longer set the `lang` and `direction` Redactor settings if they are already set in the site’s Redactor config.
- Modals now keep other modals open by default.
- Craft is now forgiving of plugin config filename casing on case-sensitive file systems.
- Craft’s record classes that are meant for Craft Client/Pro-based functionality no longer require Craft Client/Pro to instantiate the class.
- Updated Twig to 1.20.0.

### Fixed
- Fixed a bug where some servers would add a white border to images on upload.
- Fixed a bug where translatable field inputs were not respecting the locale of the element currently being edited.
- Fixed a bug where Craft was not factoring in the requested locale when applying search terms to element queries, so all locales’ keywords were being searched.
- Fixed a bug where the [privateTemplateTrigger](https://craftcms.com/docs/config-settings#privateTemplateTrigger) config setting was also affecting the CP.
- Fixed a bug where asset index updating would not pick up missing folders if they didn’t contain any indexed files.
- Fixed a bug where the Edit User Group page wouldn’t load if a section had “`</body>`” in its name. (Thanks Murphy.)
- Fixed a PHP error that would occur if an entry was deleted while the “Updating element slugs and URIs” background task was running.
- Fixed a MySQL error that would occur if ordering an element query based on multiple custom fields.

## 2.4.2682 - 2015-07-29

### Changed
- Updated Redactor to 10.2.2.

## 2.4.2679 - 2015-07-22

### Fixed
- Fixed yet another issue with assets that had uppercased file extensions.

## 2.4.2677 - 2015-07-21

### Changed
- Made it possible to initialize more than one Matrix Configurator on a single page.

### Fixed
- Fixed a bug where Craft wasn’t saving drafts’ Post and Expiry Dates in the system timezone.
- Fixed a bug where image transforms weren’t getting generated properly for images with uppercase file extensions.
- Fixed a bug where images weren’t getting cleansed when uploaded.

### Security
- Craft now requires the [CRYPT_BLOWFISH security fix](https://secure.php.net/security/crypt_blowfish.php) introduced in PHP 5.3.7. Servers without it will need to upgrade PHP before they can update Craft.

## 2.4.2675 - 2015-07-16

### Changed
- Reduced the number of database queries executed when outputting Matrix field content.
- Slugs and other auto-populated fields will now get updated on form submit, so for example, it is no longer possible to submit an entry with an incomplete Slug if the Save button is clicked quickly after the Title is edited.
- Database backup filenames now only include ASCII characters.
- Entry and category titles are no longer checked for static translations on their Edit pages.
- The date picker is now localized for locales `nn`, `no`, `fr_fr`, `ar_sa`, `de_de`, `fr_fr`, `it_it`, `nl_nl` and `nn_no`.
- Improved Craft’s SVG thumbnail support for a broader range of SVG files.
- User photos and login page logos can now be uploaded as SVGs.
- Updated Redactor to 10.2.

### Fixed
- Fixed a bug where users were getting group-based permissions assigned directly to them when their account was edited.
- Fixed a bug where it was possible for users to have a nested permission without its parent permissions, if the parent permissions had been granted via a group assignment, and the group assignment had been removed.
- Fixed a bug that affected asset indexing on cloud-based sources.
- Fixed a bug where the Generate Pending Transforms task would hang if transforms were already generated or the task was restarted.
- Fixed some “double-instantiating” JavaScript notices that could occur in the Control Panel.
- Fixed a bug where PHP warnings, notices, and errors weren’t getting reported correctly when Dev Mode was enabled.
- Fixed a bug where PHP warnings, notices, and errors could display the full file path to the error even when Dev Mode was disabled.

## 2.4.2670 - 2015-06-16

### Changed
- The `now` global templating variable is now set to the system’s timezone.
- Added [static translation support](http://buildwithcraft.com/help/static-translations) to even more user-defined strings in the Control Panel.
- [FieldLayoutBehavior](https://craftcms.com/classreference/etc/behaviors/FieldLayoutBehavior) now has an `$idAttribute` constructor argument, making it possible to customize the name of the field layout’s ID attribute on the owner class.
- Newly-created asset sources are now immediately available from [AssetSourcesService::getAllSources()](https://craftcms.com/classreference/services/AssetSourcesService#getAllSources-detail), etc.
- Updated Redactor to 10.1.3.

### Fixed
- Fixed a couple PHP errors that could occur when calling [DateTime::createFromString()](https://craftcms.com/classreference/etc/dates/DateTime#createFromString-detail).
- Fixed a PHP error that occurred when visiting an Edit Entry page for an entry ID that doesn’t exist.
- Fixed a MySQL error that occurred when updating from pre-Craft 2.3 if there were any asset sources without any ASCII-like characters in their names.

## 2.4.2669 - 2015-06-03

### Changed
- Relational fields’ Selection Label settings can now be translated using Craft’s [static translation support](http://buildwithcraft.com/help/static-translations).

### Fixed
- Fixed a bug that broke non-query-string-based pagination links.
- Fixed a bug that resulted in some assets losing their file content.
- Fixed an error that occurred when using the [{% includecss %}](http://buildwithcraft.com/docs/templating/includecss), [{% includehirescss %}](http://buildwithcraft.com/docs/templating/includehirescss), and [{% includejs %}](http://buildwithcraft.com/docs/templating/includejs) tags as tag pairs could result in a Twig error when used outside of `{% block %}` tags.
- Fixed a bug where Matrix configuration UI would not resize itself after the Field Type settings changed when using Firefox.

## 2.4.2668 - 2015-06-02

### Changed
- The “Link to an entry/category/asset” Rich Text field options are now only shown if the Craft site actually has entries/categories/assets to choose from.

### Fixed
- Fixed a bug where Date/Time fields were saving their values with the wrong timezone.
- Fixed an error that occurred when attempting to select new categories within Categories fields.
- Fixed an error that occurred when Craft attempted to load an SVG image without a proper XML DTD.

## 2.4.2667 - 2015-06-02

### Fixed
- Really fixed an error that occurred when uploading some images.
- Fixed a bug where less specific translation files (e.g. `fr.php`) would override more specific ones (e.g. `fr_ca.php`).

## 2.4.2666 - 2015-06-02

### Added
- Added the [sendPoweredByHeader](https://craftcms.com/docs/config-settings#sendPoweredByHeader) config setting, which can disable the `X-Powered-By: Craft CMS` header.

### Fixed
- Fixed an error that occurred on servers running PHP 5.3.1 and 5.3.3.
- Fixed an error that occurred when uploading some images.

## 2.4.2664 - 2015-06-02

### Added
- Added support for SVG transforms and thumbnails on servers with ImageMagick installed.
- Added the “Selection Label” setting to [Assets](http://buildwithcraft.com/docs/assets-fields), [Categories](http://buildwithcraft.com/docs/categories-fields), [Entries](http://buildwithcraft.com/docs/entries-fields), [Tags](http://buildwithcraft.com/docs/tags-fields), and [Users](http://buildwithcraft.com/docs/users-fields) fields, enabling customization of the selection inputs’ labels.
- Added the `includeSubfolders` asset criteria parameter, for selecting assets in the subfolders of the chosen `folderId`.
- Added support for query string-based pagination, via the [pageTrigger](https://craftcms.com/docs/config-settings#pageTrigger) config setting.
- Added the [convertFilenamesToAscii](https://craftcms.com/docs/config-settings#convertFilenamesToAscii) config setting, which determines whether non-ASCII characters within uploaded filenames should be converted to ASCII (default is `false`).
- Added the [phpSessionName](https://craftcms.com/docs/config-settings#phpSessionName) config setting, which specifies what the PHP session ID cookie name should be (default is “CraftSessionId”).
- Added the [enableTemplateCaching](https://craftcms.com/docs/config-settings#enableTemplateCaching) config setting, which can be set to `false` to disable template caching system-wide.
- Added the [defaultCpLanguage](https://craftcms.com/docs/config-settings#defaultCpLanguage) config setting, which defines the default language that users should see the Control Panel in, until they’ve set their Preferred Locale setting.
- The Control Panel is now translated into Czech (`cs`) and Slovak (`sk`).
- Added a “Search in subfolders” checkbox to asset indexes, which appears when searching within a folder that contains subfolders.
- Added a “Link to a category…” option to Rich Text fields.
- Added new `cp.entries.edit` and `cp.entries.edit.right-pane` template hooks to the Edit Entry page.
- The `_includes/forms.html` template now has a `optionShortcutLabel()` macro.
- `Craft.cp` now triggers a `displayNotification` event when `Craft.cp.displayNotification()` is called.
- Added the [kebab](http://buildwithcraft.com/docs/templating/filters#kebab) Twig filter, for formatting a string in `kebab-case`.
- Added [craft.request.getClientOs()](https://craftcms.com/classreference/variables/HttpRequestVariable#getClientOs-detail).
- Added [craft.categoryGroups](https://craftcms.com/classreference/variables/CategoryGroupsVariable) with [getAllGroupIds()](https://craftcms.com/classreference/variables/CategoryGroupsVariable#getAllGroupIds-detail), [getAllGroups()](https://craftcms.com/classreference/variables/CategoryGroupsVariable#getAllGroups-detail), [getEditableGroupIds()](https://craftcms.com/classreference/variables/CategoryGroupsVariable#getEditableGroupIds-detail), [getEditableGroups()](https://craftcms.com/classreference/variables/CategoryGroupsVariable#getEditableGroups-detail), [getTotalGroups()](https://craftcms.com/classreference/variables/CategoryGroupsVariable#getTotalGroups-detail), [getGroupById()](https://craftcms.com/classreference/variables/CategoryGroupsVariable#getGroupById-detail), and [getGroupByHandle()](https://craftcms.com/classreference/variables/CategoryGroupsVariable#getGroupByHandle-detail) functions.
- Added [TemplateCacheService::deleteCachesByKey()](https://craftcms.com/classreference/services/TemplateCacheService#deleteCachesByKey-detail).
- Added [HttpRequestService::getClientOs()](https://craftcms.com/classreference/services/HttpRequestService#getClientOs-detail).
- Added [ImageHelper::getImageSize()](https://craftcms.com/classreference/helpers/ImageHelper#getImageSize-detail) for determining the size of an image, without relying on GD.
- Added [ImageHelper::parseSvgSize()](https://craftcms.com/classreference/helpers/ImageHelper#parseSvgSize-detail) for determining the size of an SVG (in pixels) from its XML data.
- Added [StringHelper::toKebabCase()](https://craftcms.com/classreference/helpers/StringHelper#toKebabCase-detail) for formatting a string in `kebab-case`.
- Added [StringHelper::splitOnWords()](https://craftcms.com/classreference/helpers/StringHelper#splitOnWords-detail) for splitting a string into an array of its words.
- Added [StringHelper::stripHtml()](https://craftcms.com/classreference/helpers/StringHelper#stripHtml-detail) for stripping HTML tags from a string.
- Added a new [Update Element Slugs and URIs](https://craftcms.com/classreference/tasks/UpdateElementSlugsAndUrisTask) background task.
- Added the [sections.onBeforeDeleteSection](https://craftcms.com/docs/plugins/events-reference#sections-onBeforeDeleteSection) and [onDeleteSection](https://craftcms.com/docs/plugins/events-reference#sections-onDeleteSection) events.
- Added the [categories.onBeforeDeleteGroup](https://craftcms.com/docs/plugins/events-reference#categories-onBeforeDeleteGroup) and [onDeleteGroup](https://craftcms.com/docs/plugins/events-reference#categories-onDeleteGroup) events.
- Added the [onEditionChange](https://craftcms.com/docs/plugins/events-reference#onEditionChange) event.
- Source definitions returned by [IElementType::getSources()](https://craftcms.com/classreference/elementtypes/IElementType#getSources-detail) can now include a `defaultSort` key, specifying the default sort attribute for the source.
- Added [BaseElementModel::createContent()](https://craftcms.com/classreference/models/BaseElementModel#createContent-detail), enabling elements to hook into their `ContentModel` creation.

### Changed
- Improved the performance of saving entries/categories with lots of descendants.
- “contentTable”, “next”, and “prev” are reserved field handles now.
- Craft now sets a `X-Powered-By: Craft CMS` header on all requests.
- Craft now supports the use of `.`, `-`, and `/` characters to separate day, month, and year values within Date fields, in place of the character specified by the active locale’s date format.
- The “Save and continue editing” Save button options on Entry, Category, and User edit pages now show the keyboard shortcut in the UI.
- Reduced the number of database queries needed when looping through Matrix fields with multiple block types.
- Craft no longer provides translations for the default site content that is created by the installer.
- Emails are now sent in the recipient’s preferred language, rather than the current application language.
- The [pageTrigger](https://craftcms.com/docs/config-settings#pageTrigger) config setting value can now begin with a `?` to enable query string-based pagination.
- Redesigned the user permission UI, making it much easier to batch-assign permissions.
- The latest entry revision notes are now displayed when viewing the current version of an entry.
- The Updates page now shows timestamps next to each available update.
- The Control Panel’s `_layouts/base.html` template now supports a `bodyClass` variable, which will set a class name on the `<body>` tag.
- The Control Panel’s Login page now has a `login` class on the `<body>`.
- The Control Panel’s message pages now have a `message` class on the `<body>`.
- The [{% includecss %}](http://buildwithcraft.com/docs/templating/includecss), [{% includecssfile %}](http://buildwithcraft.com/docs/templating/includecssfile), [{% includehirescss %}](http://buildwithcraft.com/docs/templating/includehirescss), [{% includejs %}](http://buildwithcraft.com/docs/templating/includejs), and [{% includejsfile %}](http://buildwithcraft.com/docs/templating/includejsfile) tags now support fully lowercased tag names.
- The [{% includecss %}](http://buildwithcraft.com/docs/templating/includecss), [{% includehirescss %}](http://buildwithcraft.com/docs/templating/includehirescss), and [{% includejs %}](http://buildwithcraft.com/docs/templating/includejs) tags now support a tag pair mode.
- Plugins can now return an array of Twig extensions from the [addTwigExtension](https://craftcms.com/docs/plugins/hooks-reference#addTwigExtension) hook.
- Added an `$asTask` argument to [ElementsService::updateElementSlugAndUri()](https://craftcms.com/classreference/services/ElementsService#updateElementSlugAndUri-detail), which offloads the processing to a background task.
- Added an `$asTask` argument to [ElementsService::updateDescendantSlugsAndUris()](https://craftcms.com/classreference/services/ElementsService#updateDescendantSlugsAndUris-detail), which offloads the processing to a background task.
- Added a `$context` argument to [FieldsService::getAllFields()](https://craftcms.com/classreference/services/FieldsService#getAllFields-detail), which will override [ContentService::$fieldContext](https://craftcms.com/classreference/services/ContentService#fieldContext-detail), if set. It can also be set to an array, so fields from multiple contexts are selected at once.
- [EmailService::sendEmailByKey()](https://craftcms.com/classreference/services/EmailService#sendEmailByKey-detail) now passes an `emailKey` variable to the email template, so the template is aware of which email is being sent.
- [UserSessionService::getIsGuest()](https://craftcms.com/classreference/services/UserSessionService#getIsGuest-detail) now returns true for console requests, if the user identity hasn’t been established.
- [BaseElementType::populateElementModel()](https://craftcms.com/classreference/elementtypes/BaseElementType#populateElementModel-detail) is now abstract.
- [UsersController::actionSaveUser()](https://craftcms.com/classreference/controllers/UsersController#actionSaveUser-detail) will now respond with JSON for Ajax requests.
- Updated Yii to 1.1.16.
- Updated Twig to 1.18.1.
- Updated Guzzle to 3.9.3.
- Updated PHPMailer to 5.2.10.
- Updated PHPUnit to 4.6.9.
- Updated jQuery to 2.1.4.
- Updated jQuery UI to 1.11.4.
- Updated jQuery Placeholder to 2.1.1.
- Updated jQuery Timepicker to 1.7.0.
- Updated Redactor to 10.1.2.
- Updated Velocity to 1.2.2.
- Updated Qunit to 1.18.0.

### Fixed
- Fixed a bug where some dates were being output with the wrong timezone.
- Fixed a PHP error that occurred when using Date/Time fields configured to only show the timepicker.
- Fixed a fatal PHP error that occurred when an admin attempted to log in as a user that was suspended.
- Fixed a bug where background tasks could be triggered by console requests.
- Fixed a bug where the Min Build Required error message was getting HTML-encoded.
- Fixed a bug where saving a Single section’s settings could overwrite the entry’s title.
- Fixed a bug where user photo URLs would not resolve correctly under some conditions.
- Fixed a bug where any URL segments that only contained the number `0` were ignored.
- Fixed an infinite loop that could occur during console requests.
- Fixed a bug where it was possible to tell Craft to send out activation emails to users that already had active accounts.
- Fixed a bug where Google Cloud-based asset sources weren’t getting the correct default URL prefixes.
- Fixed a bug where single-line and numeric Table field cells would retain invalid values that were pasted into them.
- Fixed some dependencies on the GD library, which aren’t necessary if ImageMagick is installed.
- Fixed a bug where `{% cache %}` tags wouldn’t store the URL correctly on pages that had a query string parameter that ended in `p`.
- Fixed a UI glitch where the bottom shadow of entry Save buttons would span the full width of the grid column if entry versioning was disabled or unavailable.
- Fixed a UI glitch where content tabs with long names wouldn’t wrap within the Field Layout Designer.
- Fixed a UI glitch where icons were getting lots of extra padding when viewing the Control Panel with a RTL language.
- Fixed a bug where user group names weren’t being translated on the Edit User page.
- Fixed a bug where exception messages could be displayed with strange characters in some browsers.
- Fixed a bug where passing `0` to [DateTimeHelper::formatTimeForDb()](https://craftcms.com/classreference/helpers/DateTimeHelper#formatTimeForDb-detail) would return the current date/time instead of `1970-01-01 00:00:00`.

## 2.3.2644 - 2015-04-22

### Fixed
- Fixed a bug where the Assets page could hang if the user attempted to move the same asset multiple times.
- Fixed a bug where assets would disappear when moving them to a different source or subfolder if there was a conflict and the user chose to cancel the move.
- Fixed a bug where [TagGroupRecord](https://craftcms.com/classreference/records/TagGroupRecord)’s `tags` relation was pointing to an invalid column on the `craft_tags` table.
- Fixed a bug where less-specific translation files were overriding messages from more-specific translation files, rather than the other way around.
- Fixed a bug where the Norwegian translation’s month and day names were set to numbers.
- Fixed a PHP warning that could occur when calling [CacheService::mget()](https://craftcms.com/classreference/services/CacheService#mget-detail).
- Fixed a PHP error that could occur if Craft needed to determine the application target language before the PHP session had been initiated.
- Fixed a PHP error that could occur when uploading an image with invalid EXIF data.
- Fixed a PHP error that could occur when logging in with a user account that had been locked, but whose `lockoutDate` column was set to `null`.

## 2.3.2643 - 2015-04-06

### Fixed
- Fixed a CSS issue that made it impossible to interact with some Rich Text field modals.
- Fixed a bug where the “Download” button was not interactive on the Updates page when the [allowAutoUpdates](https://craftcms.com/docs/config-settings#allowAutoUpdates) config setting was disabled.

## 2.3.2642 - 2015-04-03

### Changed
- Norwegian Bokmål (nb) and Norwegian Nynorsk (nn) translations now fall back to plain ol’ Norwegian (no) if they aren’t available.
- Reduced the risk of a long-running request locking up the PHP session for other requests from the same browser.
- The [assets.onBeforeDeleteAsset](https://craftcms.com/docs/plugins/events-reference#assets-onBeforeDeleteAsset) now supports setting its `$performAction` property to `false` to prevent the asset from getting deleted.
- Updated Redactor to 10.0.9.

### Fixed
- Fixed a JavaScript error that occurred if there was a validation error when creating a new field group.
- Fixed a bug where an asset transform URL could be set to the generated transform URL before the transform was finished being generated.

## 2.3.2641 - 2015-03-26

### Added
- Added [BaseElementModel::setRawPostContent()](https://craftcms.com/classreference/models/BaseElementModel#setRawPostContent-detail).

### Changed
- `slug` is now a reserved field handle.
- `sortOrder` is now a reserved field handle, fixing a column name conflict that would prevent relational fields from showing the related elements in the user-defined order.
- Added support for a couple of Hungarian ASCII character mappings.

### Fixed
- Fixed a bug where Craft would not allow the auto-updater to run on servers without the iconv extension installed, even though that is an optional dependency.
- Fixed a PHP error that could occur when uploading an image with an invalid EXIF timestamp.
- Fix a bug where the “Your session has ended” login modal would show an “Invalid CSRF token” validation error when the [enableCsrfProtection](https://craftcms.com/docs/config-settings#enableCsrfProtection) config setting was enabled.
- Fixed a bug where uploading a file to an Assets field in a front-end Edit Entry Draft form would result in the asset getting uploaded, but not actually selected in the Assets field.
- Fixed a PHP notice that could occur when saving a nested Structure entry or category if there were any validation errors.
- Fixed a PHP warning that would occur when the `{% paginate %}` tag was passed an `ElementCriteriaModel` whose `limit` parameter was set to an empty value.
- Fixed a PHP error that occurred when saving a new Matrix field with block type fields that were marked as translatable.
- Fixed a bug where a `{% cache %}` tag that was used within a loop, but which was told not to cache the output (e.g. by setting `if false` on the tag), would output the same thing every time.
- Fixed a bug where the `{% redirect %}` tag would append the redirect URL to the current page’s URL if the redirect URL was protocol-relative (e.g. “//example.com”).
- Fixed a bug where Structure entries’ descendants’ URIs would not get updated when a parent entry was re-saved, if the section was not enabled for the user’s preferred locale.
- Fixed a bug where the Region and Container inputs were disabled for Rackspace asset source settings.
- Fixed a bug where Chrome/iOS would not show Control Panel page contents, sometimes.
- Fixed a bug where Matrix fields’ Add Block buttons could be incompletely rendered in Safari after switching content tabs, sometimes.
- Fixed a bug where saving an entry using the Command/Control + S keyboard shortcut while editing a Rich Text field in fullscreen mode would lose any changes made to the Rich Text field.

## 2.3.2640 - 2015-03-12

### Added
- Added the [modifyCpNav](https://craftcms.com/docs/plugins/hooks-reference#modifyCpNav) hook, making it possible for plugins to customize the Control Panel navigation.

### Changed
- When changing an existing relational field to Translatable, Craft will now update all existing relations for that field, preventing a bug where relations could get deleted unexpectedly.

### Fixed
- Fixed a bug where HTML entities in element titles were getting double-encoded on the index pages.
- Fixed a bug where users could disappear when deleting the primary locale.
- Fixed a bug where Live Preview would stop listening for content changes if an error occurred while rendering the preview.
- Fixed a bug where `<sub>` tags within Rich Text fields did not get subscript styling.

## 2.3.2639 - 2015-03-11

### Added
- Added the [useSecureCookies](https://craftcms.com/docs/config-settings#useSecureCookies) config setting, which determines whether Craft should set the secure flag on its cookies. (Default value is `auto`, which is resolved depending on whether the current request is over SSL).
- Added [HtmlHelper::encodeParams()](https://craftcms.com/classreference/helpers/HtmlHelper#encodeParams-detail), for concatenating HTML strings and dynamic, untrusted strings together.

### Changed
- Improved the cryptographic strength of user verification codes, user session tokens, as well as the tokens used in entry draft/version Share URLs.
- All Control Panel requests are now served with a `X-Frame-Options: SAMEORIGIN` header, which prevents browsers from rendering the Control Panel within an iframe.
- Password Reset emails now always link to the Control Panel for users that have access to it, even if requested from a front-end form.
- Users with Control Panel access now get redirected to the CP’s login form after resetting their password, even if they did it on a front-end form.
- Updated Redactor to 10.0.8.

### Fixed
- Fixed a bug where [TemplateCacheService::deleteCachesByElementType()](https://craftcms.com/classreference/services/TemplateCacheService#deleteCachesByElementType-detail) was deleting all template caches.
- Fixed a PHP error that could occur when [TasksService::deleteTaskById()](https://craftcms.com/classreference/services/TasksService#deleteTaskById-detail) was called with a task ID that didn’t exist.
- Fixed a bug where cloud-based asset source settings could not be resaved if their bucket lists hadn’t been loaded yet.
- Fixed a bug where Yii-based error messages were showing up in English when the active locale was `nb_no`.
- Fixed a JavaScript error that would occur when unlinking an existing hyperlink from a Rich Text field.
- Fixed a bug where some edition-specific services wouldn’t be available for scripts that loaded Craft via `craft/app/bootstrap.php` but didn’t call `$app->run()`.

### Security
- Improved CSRF token security, especially for logged-in users, reducing the risk of a man-in-the-middle attack.
- All Control Panel requests are now served with a `X-Content-Type-Options: nosniff` header, which fixes some XSS attack vectors in IE/Ajax requests.
- [FeedsService::getFeedItems()](https://craftcms.com/classreference/services/FeedsService#getFeedItems-detail) now ignores feed items with invalid permalink URLs, which fixes an XSS attack vector.
- The `|json_encode` Twig filter now passes the options `JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_QUOT` to PHP’s [json_encode()](http://php.net/manual/en/function.json-encode.php) function by default on HTML/XHTML requests, which fixes some XSS attack vectors.
- Fixed other possible XSS attack vectors with the help of [HtmlHelper::encodeParams()](https://craftcms.com/classreference/helpers/HtmlHelper#encodeParams-detail).
- Fixed a possible attack vector where a user could use a timing attack on a stale session to figure out the new PHP session ID’s value.

## 2.3.2636 - 2015-02-26

### Changed
- The [addEntryActions](https://craftcms.com/docs/plugins/hooks-reference#addEntryActions), [addCategoryActions](https://craftcms.com/docs/plugins/hooks-reference#addCategoryActions), and [addAssetActions](https://craftcms.com/docs/plugins/hooks-reference#addAssetActions) hooks now get called for custom sources added from the [modifyEntrySources](https://craftcms.com/docs/plugins/hooks-reference#modifyEntrySources), [modifyCategorySources](https://craftcms.com/docs/plugins/hooks-reference#modifyCategorySources), and [modifyAssetSources](https://craftcms.com/docs/plugins/hooks-reference#modifyAssetSources) hooks.
- [AssetFileModel::getWidth()](https://craftcms.com/classreference/models/AssetFileModel#getWidth-detail) and [getHeight()](https://craftcms.com/classreference/models/AssetFileModel#getHeight-detail) no longer check the `$transform` argument if called on a non-manipulatable asset.

### Fixed
- Fixed a PHP error that could occur when a user attempted to log in after their account had become locked and the cooldown duration had passed.
- Fixed a PHP error that could occur when a user with permission to edit other users, but not permission to edit other users’ emails, attempted to edit other users.
- Fixed a bug where assets that were uploaded to a dynamic subfolder for a brand new entry would have incorrect URLs if the entry had validation errors.
- Fixed a bug where an asset transform could get unintentionally deleted in some circumstances.
- Fixed a bug where [BaseEnum::getConstants()](https://craftcms.com/classreference/enums/BaseEnum#getConstants-detail) could return the wrong enum class’ constants.
- Fixed a PHP error that could occur when enabling a new locale for a section in some circumstances.
- Fixed a JavaScript error that could occur in the Control Panel in astronomically rare circumstances (therefore, it occurred).

## 2.3.2635 - 2015-02-19

### Added
- Added the [db.onBackup](https://craftcms.com/docs/plugins/events-reference#db-onBackup) event.

### Changed
- The [allowAutoUpdates](https://craftcms.com/docs/config-settings#allowAutoUpdates) config setting now supports `'minor-only'` and `'build-only'` values.
- The [allowedFileExtensions](https://craftcms.com/docs/config-settings#allowedFileExtensions) and [extraAllowedFileExtensions](https://craftcms.com/docs/config-settings#extraAllowedFileExtensions) config settings are now taken into account when renaming assets.
- [UsersController::actionSendActivationEmail()](https://craftcms.com/classreference/controllers/UsersController#actionSendActivationEmail-detail) now responds with `{'success': true}` when called over Ajax.

### Fixed
- Fixed a PHP notice that could occur if the [cacheMethod](https://craftcms.com/docs/config-settings#cacheMethod) config setting was set to a driver that wasn’t supported by the server.
- Fixed a bug where the Delete Stale Template Caches task created when structured elements were reordered wasn’t getting run until the next Control Panel request.

## 2.3.2632 - 2015-02-12

### Added
- Added the [runTasksAutomatically](https://craftcms.com/docs/config-settings#runTasksAutomatically) config setting, which can be set to `false` to prevent Craft from running tasks automatically, instead relying on a Cron job.
- Added [UrlHelper::stripQueryString()](https://craftcms.com/classreference/helpers/UrlHelper#stripQueryString-detail).

### Changed
- Entry indexes now show entries sorted by Post Date in descending order by default.
- Plugins’ primary Variable classes are no longer re-instantiated each time `craft.pluginName` is called.
- Database backups no longer include data from the `assettransformindex` or `sessions` tables.
- Added support for uploading user photos with filenames up to 100 characters long, rather than 50.

### Fixed
- Fixed a bug where background tasks created by element actions weren’t getting run until the next Control Panel request.
- Fixed a bug where the `DeleteStaleTemplateCaches` task could overlook some template caches that should have been deleted.
- Fixed a bug where changing an existing Asset Source’s “Type” setting would keep the original source type settings in the database.
- Fixed a bug where searching for “0” wouldn’t return any results.
- Fixed a bug where setting the `limit` param to `null` when outputting Matrix blocks would prevent Matrix field changes from showing up in Live Preview.
- Fixed a bug that broke user photo uploading on sites that weren’t masking `index.php` in the URLs, and where `PATH_INFO` wasn’t supported.
- Fixed a bug where asset file names weren’t being cleansed when replacing an existing asset.
- Fixed a PHP error that could occur when interacting with Amazon S3 and Google Cloud asset sources in some cases.
- Fixed a bug where the [users.onBeforeActivateUser](https://craftcms.com/docs/plugins/events-reference#users-onBeforeActivateUser) and [users.onActivateUser](https://craftcms.com/docs/plugins/events-reference#users-onActivateUser) events weren’t getting fired during new user registration on sites where the “Verify email addresses?” setting wasn’t enabled.

## 2.3.2629 - 2015-02-05

### Added
- Added the [modifyAssetSources](https://craftcms.com/docs/plugins/hooks-reference#modifyAssetSources), [modifyCategorySources](https://craftcms.com/docs/plugins/hooks-reference#modifyCategorySources), [modifyEntrySources](https://craftcms.com/docs/plugins/hooks-reference#modifyEntrySources), and [modifyUserSources](https://craftcms.com/docs/plugins/hooks-reference#modifyUserSources) hooks, making it possible for plugins to customize the available sources for assets, categories, entries, and users.

### Changed
- [entries.onBeforeDeleteEntry](https://craftcms.com/docs/plugins/events-reference#entries-onBeforeDeleteEntry) event handlers can now cancel the entry deletion.

### Fixed
- Fixed a bug where HTTP requests that triggered a new background task could hang until the task had completed, if Apache’s `mod_deflate` module was installed.
- Fixed a bug where template caches weren’t getting cleared when changing elements’ statuses from the index pages.
- Fixed a bug where the default section locale status wasn’t getting applied to the current locale on new entries.
- Fixed a couple PHP errors that could occur when saving users in certain circumstances.
- Fixed a PHP error that could occur when uploading assets in certain circumstances.

## 2.3.2627 - 2015-01-29

### Added
- Added support for the “French - Morocco” (fr_ma) locale.

### Changed
- When an entry/category/global is unable to save due to validation errors, a confirmation message will now be displayed if the user attempts to leave the subsequent edit page, even if they haven’t made any new changes.
- When a new site locale is added, the new locale’s initial category/tag/asset/block content is now copied from the site’s primary locale, rather than the user’s preferred locale.
- When a new locale is enabled for a section, the new locale’s initial content is now copied from the site’s primary locale, rather than the user’s preferred locale.
- When a new Matrix block is added, Craft will now scroll the window so that the block is fully visible.
- Improved the `toolbarFixed` behavior in Redactor for Rich Text fields.
- Updated Velocity to 1.2.1.

### Fixed
- Fixed `attribute:*` searches.
- Fixed a couple PHP errors that occurred during public user registration.
- Fixed a Twig error that occurred on CP edit pages when a field type wasn’t installed.

## 2.3.2626 - 2015-01-27

### Added
- The Control Panel is now translated into Russian.
- Added [AppBehavior::getTargetLanguage()](https://craftcms.com/classreference/etc/behaviors/AppBehavior#getTargetLanguage-detail).
- Added [UsersService::getEmailVerifyUrl()](https://craftcms.com/classreference/services/UsersService#getEmailVerifyUrl-detail).

### Changed
- Newly registered users who still need to set their passwords now receive the same activation email as newly registered users who already have a password, rather than a password reset email.
- When a user changes their own username, Craft now updates the cookie that’s responsible for remembering it for the Login page.
- When using the shortcut `{variable}` syntax in settings like URL Format and Title Format, the variable values no longer get HTML-encoded, so there’s no need to add a `|raw` filter (e.g. `{foo|raw}`).
- The [limitAutoSlugsToAscii](https://craftcms.com/docs/config-settings#limitAutoSlugsToAscii) config setting is now enforced when submitting entries and categories with a blank Slug value.
- Added `potx`, `pps`, `ppsm`, `ppsx`, and `pptm` to the default [allowedFileExtensions](https://craftcms.com/docs/config-settings#allowedFileExtensions) config setting value, and Craft now recognizes these extensions as PowerPoint files.
- Updated Redactor to 10.0.6.

### Fixed
- Fixed a bug where some search terms would result in zero matching elements when they shouldn’t have.
- Fixed a bug where Table fields’ Default Values setting would show a single row, even for fields that had been saved without any rows in that setting.
- Fixed a bug where entry/category date fields would get set to the current date in Live Preview, if the user’s preferred locale was different from the `CRAFT_LOCALE` constant.
- Fixed a bug where [StringHelper::asciiString()](https://craftcms.com/classreference/helpers/StringHelper#asciiString-detail) was removing many non-ASCII characters, rather than mapping them to ASCII.
- Fixed a bug where user photos could not be uploaded in some environments.
- Fixed a PHP error that was masking an exception when a plugin was installed that registered a service with the same name as a core Craft service.
- Fixed a bug where the “New block type” button within Matrix fields’ settings was getting sorted along with the block types.

## 2.3.2625 - 2015-01-06

### Changed
- When setting a new password on an account, Craft will only automatically log the user in per the [autoLoginAfterAccountActivation](https://craftcms.com/docs/config-settings#autoLoginAfterAccountActivation) config setting if the account was actually just activated.
- After activating an account, Craft will now redirect the user per the [postCpLoginRedirect](https://craftcms.com/docs/config-settings#postCpLoginRedirect) or [activateAccountSuccessPath](https://craftcms.com/docs/config-settings#activateAccountSuccessPath) config settings, depending on whether the user has access to the Control Panel.
- After setting a new password on an already-activated account, Craft will redirect the user to the Control Panel login page, or per the [setPasswordSuccessPath](https://craftcms.com/docs/config-settings#setPasswordSuccessPath) config setting, depending on whether the user has access to the Control Panel.
- After verifying a new email address on an already-activated account, Craft will redirect the user to the site or Control Panel root, depending on whether the user has access to the Control Panel.
- Pending users will now get a meaningful error message when attempting to log into the Control Panel.
- [TemplatesService::includeJs()](https://craftcms.com/classreference/services/TemplatesService#includeJs-detail) now ensures that all scripts passed to it will end in a semicolon, preventing possible script conflicts.

### Fixed
- Fixed a JavaScript error that occurred when editing an email message from Settings → Email → Messages when [CSRF protection](https://craftcms.com/docs/config-settings#enableCsrfProtection) was enabled.
- Fixed an error that occurred when large amounts of data needed to be logged within a single request.
- Fixed an error that occurred when enabling a plugin that didn’t exist.
- Fixed an error that occurred when disabling a plugin that wasn’t enabled or didn’t exist.
- Fixed an error that occurred when installing a plugin that didn’t exist.
- Fixed an error that occurred when uninstalling a plugin that didn’t exist.
- Fixed an error that occurred when installing a plugin that was already installed.
- Fixed an error that occurred when uninstalling a plugin that wasn’t already installed.
- Fixed an error that occurred when a Matrix block type’s field type class didn’t exist.
- Fixed an error that occurred when a field type within a Matrix block called [$this->element->getContent()](https://craftcms.com/classreference/models/BaseElementModel#getContent-detail) from [getInputHtml()](https://craftcms.com/classreference/fieldtypes/IFieldType#getInputHtml-detail).
- Fixed an error that occurred when running yiic on sites with plugins installed that called [craft()->on()](https://craftcms.com/classreference/etc/web/WebApp#on-detail) from [init()](https://craftcms.com/classreference/etc/plugins/IPlugin#init-detail).
- Fixed an error that occurred when uploading an image that didn’t contain valid copyright metadata.
- Fixed a couple PHP Strict Standards errors.
- Fixed a bug where the selection menu within Tags fields would close prematurely when selecting a tag, if the mouse click took too long.
- Fixed a bug where elements within the Control Panel would get misaligned, and not respond to window/element resizes, properly in IE 10.
- Fixed a JavaScript error that occurred when [Dev Mode](https://craftcms.com/docs/config-settings#devMode) was enabled in Internet Explorer.
- Fixed a bug where weekday names weren’t getting translated within timestamps in the Control Panel.
- Fixed a bug where new Matrix blocks on existing owner elements weren’t getting default values set on their fields.
- Fixed a bug where the same CP resource could get requested multiple times on a single page if its path hadn’t been cached yet.
- Fixed a bug where it wasn’t possible to upload a new site logo under certain circumstances.
- Fixed a typo in the user deletion confirmation modal.

## 2.3.2624 - 2014-12-30

### Fixed
- Fixed a PHP error that could occur on the Dashboard in certain circumstances.

## 2.3.2623 - 2014-12-30

### Added
- Added `Craft.appendHeadHtml()` and `Craft.appendFootHtml()` for appending new head/foot nodes to the CP’s DOM, pruning out any redundant `<link>`/`<script>` tags.

### Changed
- When submitting an entry without a slug, the PHP-generated slug will replace periods, hyphens, and underscores with the [slugWordSeparator](https://craftcms.com/docs/config-settings#slugWordSeparator) config setting value, to mimic the JavaScript-based slug generator.
- Renamed “Asset resources” to “Asset caches” in the Clear Caches tool.
- `$this->element` will now be set for field types when Matrix is calling their [getInputHtml()](https://craftcms.com/classreference/fieldtypes/IFieldType#getInputHtml-detail) method for nonexistent blocks, providing an avenue for getting information about the block type (`$this->element->getType()`) and owner element (`$this->element->getOwner()`), if there is one.

### Fixed
- Fixed an “Invalid Byte Sequence” MySQL error that could occur when saving certain Chinese characters.
- Fixed a bug where [DateTime::localeTime()](https://craftcms.com/classreference/etc/dates/DateTime#localeTime-detail) was not using the locale’s “AM” and “PM” translations.
- Fixed a bug where the time picker was displaying Greek’s “AM” and “PM” labels as “Π.Μ.”/“Μ.Μ.” rather than “π.μ.”/“μ.μ.”.
- Fixed a bug where CP resources that were included by CP CSS files were not getting browser-cached.
- Fixed a bug where some Ajax requests in the Control Panel would end up forcing the browser to re-download CSS and JS files.
- Fixed a bug where disabled checkboxes were not getting reduced opacity.
- Fixed a bug where Categories and Tags fields were outputting existing relations even if their Source setting had been changed to a different Category/Tag Group.

## 2.3.2621 - 2014-12-22

### Added
- Added [UserGroupsService::assignUserToDefaultGroup()](https://craftcms.com/classreference/services/UserGroupsService#assignUserToDefaultGroup-detail).
- Added the [assets.onBeforeReplaceFile](https://craftcms.com/docs/plugins/events-reference#assets-onBeforeReplaceFile) and [assets.onReplaceFile](https://craftcms.com/docs/plugins/events-reference#assets-onReplaceFile) events.

### Changed
- Increased the spacing between element status icons and their labels.
- Included Redactor’s [Table](http://imperavi.com/redactor/plugins/table/) plugin.
- The tool HUDs on the Settings page now each prefer to open above the tool icon if there’s room.
- Assets fields no longer create dynamic subfolders if there weren’t any files selected by the field.

### Fixed
- Fixed a bug where un-generated image transform URLs were getting cached by the `{% cache %}` tag.
- Fixed a bug where deleting a Structure section’s entry or a category from the index page would result in its descendants losing their positioning data within the hierarchy.
- Fixed a bug where Rich Text fields that appeared to be blank could still get some HTML saved to them.
- Fixed a bug where accessing an element’s `title` property was causing Craft to run an unnecessary SQL query.
- Fixed a bug where Matrix fields were getting search keywords based on the preexisting data, rather than the new data.
- Fixed a bug where `getNext()` and `getPrev()` weren’t working for Matrix blocks from Live Preview, or when viewing an entry/category’s Share URL.
- Fixed some issues with Fullscreen mode on Rich Text fields.
- Fixed a bug where the database name was getting included in some SQL statements in Craft’s DB backups.
- Fixed a bug where category titles were getting double-encoded on the Edit Category page.
- Fixed a bug where categories’ content wasn’t getting validated.
- Fixed a bug where users’ Last Login timestamps weren’t getting updated when [userSessionDuration](https://craftcms.com/docs/config-settings#userSessionDuration) was set to `false`.
- Fixed a bug where the `[can](http://buildwithcraft.com/docs/templating/craft.users#can)` user param was getting checked when running Craft Client.
- Fixed a bug where the user deletion confirmation modal was not getting resized after selecting a user with a long username, pushing the rest of the modal’s contents out of view.
- Fixed a bug where the “Author” setting was showing up on the Edit Entry page when running Craft Client.
- Fixed a bug where the Rebuild Search Index tool wasn’t removing some outdated search keywords.
- Fixed a bug where elements’ search keywords weren’t getting removed when elements were deleted.
- Fixed a bug where the error message notifying a logged-in user that they don’t have permission to access the site while it’s offline was showing encoded HTML tags.
- Fixed a bug where Craft would throw an error when connecting to an S3 account that had buckets the user wasn’t allowed to access.
- Fixed a PHP error that occurred when trying to delete an entry that didn’t exist.
- Fixed a bug where the Install/Uninstall buttons on the Plugins index page were getting clipped in Firefox.

## 2.3.2620 - 2014-12-11

### Changed
- Drastically improved the performance of the element index pages when loading additional batches of elements.
- The Updates page now shows the Download button when [allowAutoUpdates](https://craftcms.com/docs/config-settings#allowAutoUpdates) is set to `false`.

### Fixed
- Fixed a couple bugs that could result in entries/categories appearing out of order in Structure view.
- Fixed a bug that could result in an incomplete list of entries/categories getting loaded in Structure view.
- Fixed a bug where Craft was attempting to read EXIF data from files that couldn’t have any.
- Fixed a bug where Craft would sort plugins and components whose names began with a lowercase letter below other plugins/components. (This fix only applies to servers running PHP 5.4 or later; PHP 5.3 is still affected by the bug.)
- Fixed a bug where users would get redirected to the wrong place after setting/resetting their password under certain conditions.
- Fixed a bug where Craft’s JavaScript source maps were loading the same compressed JS files.
- Fixed some incompatibilities with PHP 5.3.

## 2.3.2618 - 2014-12-10

### Added
- Added the ability to expand/collapse Structure section entries and categories with descendants within their index views.
- Added [BaseElementModel::getTotalDescendants()](https://craftcms.com/classreference/models/BaseElementModel#getTotalDescendants-detail).
- Added the [SetStatusElementAction::onSetStatus()](https://craftcms.com/classreference/elementactions/SetStatusElementAction#onSetStatus-detail) event.
- Added support for Portuguese (pt).

### Changed
- Element status icons are positioned to the left of their title, once again.
- Timestamp values in element index views now reveal the full timestamp in a tooltip when hovering over them.
- Renamed the “Asset thumbs” option in the Clear Caches tool to “Asset resources”, as it deletes more than just the generated thumbnails; it also deletes generated file icons, and local copies of remotely-stored images (which are used to generate new thumbnails and transforms).
- When a request’s URI includes `..%2F`s in an attempt to load files outside of the template or resource folders, Craft now responds with a 404 status code, rather than 500 or 403, respectively.
- Email verification URLs will now activate the respective user if they still have a pending status.
- Database backups now support custom views.

### Removed
- Removed `ConfigService::getCpActivateAccountPath()`, as it served no purpose.

### Fixed
- Fixed a bug where front-end user registrations where incorrectly getting the Set Password email instead of the Email Verification email, so they had to set their password twice.
- Fixed a bug where email verification and password reset URLs could have included the CP trigger for users that didn’t actually have access to the Control Panel.
- Fixed a bug where [UsersController::actionVerifyEmail()](https://craftcms.com/classreference/controllers/UsersController#actionVerifyEmail-detail) was not accessible to logged-out users.
- Fixed a bug where the deprecated [UsersController::actionValidate()](https://craftcms.com/classreference/controllers/UsersController#actionValidate-detail) was redirecting to [actionSetPassword()](https://craftcms.com/classreference/controllers/UsersController#actionSetPassword-detail) rather than [actionVerifyEmail()](https://craftcms.com/classreference/controllers/UsersController#actionVerifyEmail-detail).
- Fixed a bug where users were not allowed to upload files to Assets fields that were restricted to a single folder within a source they didn’t have upload permissions, under certain conditions.
- Fixed a bug where users who were not allowed to view any asset sources could still view them if they manually went to /admin/assets. (They wouldn’t have been able to make any changes, though.)
- Fixed a bug where replacing an asset with a new file that had the same name as the old one would result in the new file getting a “_1” appended to its filename.
- Fixed a PHP error that would occur when uploading a two-channel (grayscale + alpha) PNG image when ImageMagick was installed.
- Fixed a bug where there were two asset thumbnail options in the Clear Caches tool.
- Fixed a bug where dragging elements horizontally to change their level was inverted for RTL languages.
- Fixed a bug where entries without a Post Date would continue to not have a Post Date after they were enabled using the Set Status action on the Entries index page.
- Fixed a bug where some strings in the Control Panel weren’t getting translated.
- Fixed a bug where element indexes could get themselves into a state where no source was selected, if the selected source was nested and one of its ancestors was collapsed.
- Fixed a bug where the _Top-Level_ and _Nested_ URL Format inputs in Section and Category Group settings could go out of alignment if there was a validation error on one of them but not the other.
- Fixed a bug where the ancestorOf, descendantOf, siblingOf, prevSiblingOf, nextSiblingOf, positionedBefore, and positionedAfter element params weren’t maintaining the criteria’s locale if passed an ID rather than an element object.
- Fixed a bug where the ancestorOf, descendantOf, siblingOf, prevSiblingOf, nextSiblingOf, positionedBefore, and positionedAfter element params weren’t causing the query to return no results if passed an invalid ID.
- Fixed a PHP error that could occur when backing up very large databases while Dev Mode was enabled.
- Fixed a PHP error that could occur when backing up a database database with custom views.
- Fixed a PHP error in [MigrationHelper::getTables()](https://craftcms.com/classreference/helpers/MigrationHelper#getTables-detail) that occurred on databases with custom views.
- Fixed a bug where Craft’s exception classes were getting logged as errors in `craft/storage/runtime/logs/craft.log` even if they were caught. Now only uncaught exceptions get logged.
- Fixed a JavaScript error that was occurring when running Craft in Dev Mode in IE 8, which doesn’t support `console.groupCollapsed()`.
- Fixed a Twig error that occurred on the default homepage template that comes with Craft.

## 2.3.2617 - 2014-12-04

### Added
- Added the [addUserAdministrationOptions](https://craftcms.com/docs/plugins/hooks-reference#addUserAdministrationOptions) hook.
- Added [StringHelper::lowercaseFirst()](https://craftcms.com/classreference/helpers/StringHelper#lowercaseFirst-detail).
- Added [StringHelper::uppercaseFirst()](https://craftcms.com/classreference/helpers/StringHelper#uppercaseFirst-detail).

### Changed
- Element reference tag parsing (`{entry:section/slug}`) now finds the referenced elements regardless of their statuses.
- When editing an entry within a single-level Structure section, or a category within a single-level category group, the Parent Entry/Category setting is now hidden.
- Matrix block type fields that are translatable but don’t have names will no longer show the locale ID above the field input.
- The [lcfirst](http://buildwithcraft.com/docs/templating/filters#lcfirst) and [ucfirst](http://buildwithcraft.com/docs/templating/filters#ucfirst) Twig filters now support multi-byte strings.
- Element types’ [defineSortableAttributes()](https://craftcms.com/classreference/elementtypes/IElementType#defineSortableAttributes-detail) method can now specify multiple column names for a single sort option, separated by commas. (This also goes for the [modifyEntrySortableAttributes](https://craftcms.com/docs/plugins/hooks-reference#modifyEntrySortableAttributes), [modifyCategorySortableAttributes](https://craftcms.com/docs/plugins/hooks-reference#modifyCategorySortableAttributes), [modifyAssetSortableAttributes](https://craftcms.com/docs/plugins/hooks-reference#modifyAssetSortableAttributes) and [modifyUserSortableAttributes](https://craftcms.com/docs/plugins/hooks-reference#modifyUserSortableAttributes) hooks.)
- [BaseModel::setAttributes()](https://craftcms.com/classreference/models/BaseModel#setAttributes-detail) (and methods that call it, like [BaseModel::populateModel()](https://craftcms.com/classreference/models/BaseModel#populateModel-detail)) will now copy any _eager-loaded_ relations from passed-in records. (Meaning, if that’s the behavior you want you need to make use of [`with()`](http://www.yiiframework.com/doc/api/1.1/CActiveRecord#with-detail) when loading the records).
- The default Homepage single no longer has a custom “Heading” field. Now it just shows a dynamic Title field instead.
- Updated the default [Redactor config files](http://buildwithcraft.com/docs/rich-text-fields#redactor-configs) to set `toolbarFixed: true` rather than `toolbarFixedBox: true`, for Redactor 10 compatibility.

### Fixed
- Fixed a MySQL error that occurred when ordering tags by `name`, which is no longer technically a column as of Craft 2.3.
- Fixed a bug where multi-byte characters in slugs were failing validation.
- Fixed a bug where entries were able to be saved if the slug was entirely composed of invalid characters.
- Fixed a JavaScript error that occurred when using a Rich Text field within a Matrix field, whose Redactor config disabled the toolbar using `toolbar: false`.
- Fixed a bug where users’ Activation and Password Reset links were expiring immediately after they were clicked on, even if the user decided not to complete the password reset task after clicking the link.
- Fixed the look of elements with titles long enough to span two or more rows on Structure section and Category Group index pages.
- Fixed a bug where the Name and Instructions settings were appearing to be required even though they weren’t, for unsaved Matrix block type fields.
- Fixed a bug where validation errors for Matrix block type fields that didn’t have Names would refer to the field as “\__blank__”. Now the field handle is used instead.
- Fixed a bug where [WebApp](https://craftcms.com/classreference/etc/web/WebApp) was getting initialized with config settings for `fieldTypes` and `file` components that don’t actually exist.

## 2.3.2616 - 2014-12-03

### Added
- It is now possible to double-click on entries’, categories’, and users’ status icons/thumbnails to open an editor modal from their respective index pages, the same way it works on the Assets index page.
- Added [ElementsService::setPlaceholderElement()](https://craftcms.com/classreference/services/ElementsService#setPlaceholderElement-detail).
- Added [ElementIndexController::getElementCriteria()](https://craftcms.com/classreference/controllers/ElementIndexController#getElementCriteria-detail).
- Added the [modifyEntrySortableAttributes](https://craftcms.com/docs/plugins/hooks-reference#modifyEntrySortableAttributes) hook.
- Added the [modifyCategorySortableAttributes](https://craftcms.com/docs/plugins/hooks-reference#modifyCategorySortableAttributes) hook.
- Added the [modifyAssetSortableAttributes](https://craftcms.com/docs/plugins/hooks-reference#modifyAssetSortableAttributes) hook.
- Added the [modifyUserSortableAttributes](https://craftcms.com/docs/plugins/hooks-reference#modifyUserSortableAttributes) hook.

### Changed
- Live Preview and Share links will now show the unpublished entry/category changes wherever the entry/category are being referenced in the templates (even areas where the entry/category was fetched from the database).
- Moved [TestApplication](https://craftcms.com/classreference/tests/TestApplication)’s PHP environment normalization code to `craft/app/tests/bootstrap.php`.

### Fixed
- Fixed a bug where Checkboxes and Multi-select fields weren’t showing their saved values.
- Fixed a bug where Checkboxes and Multi-select fields were always causing validation errors when saved.
- Fixed a bug where custom field instructions weren’t getting HTML-encoded properly.
- Fixed a bug where some non-custom fields’ instructions were showing encoded HTML characters.
- Fixed a PHP error that would occur when attempting to save a section with no selected locales.
- Fixed a bug where assets could show their old, cached thumbnail after replacing their file with a new one.
- Fixed a bug where the Control Panel could still show old, cached resources after Craft’s data caches were cleared.
- Fixed a JavaScript error that occurred when choosing a date from a Date/Time field while using Live Preview.
- Fixed a 404 error on a resource request on the Assets index page.
- Fixed a PHP error that could occur when using [TestApplication](https://craftcms.com/classreference/tests/TestApplication).
- Fixed a bug where Craft Client/Pro-only components were not getting initialized when running Craft from its CLI.

## 2.3.2615 - 2014-12-02

### Added
- Added Control Panel translations for Simplified Chinese, Hebrew, Korean, Spanish, and Swedish.
- When deleting a locale, it is now possible to transfer that locale’s content to another locale.
- Added support for Eesti and Serbian locales.
- Element index pages can now have batch actions.
- Added a new Sort menu to all element index views, which includes a “Structure” option when viewing structured elements.
- Structure View has been replaced by a structured Table View on element indexes, making large structure sections and category groups load much more quickly.
- Added a “Save and continue editing” option to Quick Post widgets.
- Added a “Locale” setting to Recent Entries widgets.
- Matrix blocks now display their block type’s name in their titlebar.
- It is now possible to reorder, collapse/expand, disable/enable, and delete multiple Matrix blocks at once.
- Matrix block type fields can now have instruction text.
- Matrix block type fields are no longer required to have names.
- Rich Text fields now have a “Column Type” setting, which can be set to “MediumText” if more than 64K of data needs to be stored.
- Lightswitch fields now have a “Default Value” setting.
- It is now possible to update the status on a batch of entries at once from the Entries index.
- It is now possible to delete a batch of entries at once from the Entries index.
- It is now possible to edit entries without leaving the Entries index.
- It is now possible to edit an entry’s author from the Edit Entry page.
- It is now possible to edit a Structure entry’s parent from the Edit Entry page.
- Added a “Save as a new entry” option to the Save button menu on the Edit Entry page.
- Added the `expiryDate` entry param.
- It is now possible to update the status on a batch of categories at once from the Categories index.
- It is now possible to delete a batch of categories at once from the Entries index.
- Categories now get their own edit pages in the Control Panel.
- Added Live Preview and Sharing support to categories.
- The Categories field type’s input has been completely rewritten for better performance and usability.
- It is now possible to edit selected categories from Categories fields by double-clicking on them, if the logged-in user has permission to do so.
- Tags now have translatable Titles, rather than Names.
- Added a “Replace file” menu option to the Assets index.
- Asset sources now have handles.
- Added the `source` parameter to `craft.assets`, which accepts an asset source’s handle.
- Craft will now rotate photos based on the camera orientation used when the photos were taken.
- It is now possible to clear asset thumbnails from the Clear Caches tool.
- It is now possible to suspend/unsuspend a batch of users at once from the Users index.
- It is now possible to delete a batch of users at once from the Users index.
- It is now possible to edit users without leaving the Users index.
- Added the ability to send out an activation/reset password email from the Edit User page.
- Admins can now copy a user’s activation/reset password URL from the Edit User page directly.
- When deleting a user account, it is now possible to transfer that user’s content to another user.
- Added a Week Start Day user setting (only visible when a user is editing their own account).
- Added a new “Change users’ emails” permission.
- Most element criteria parameters now support partial match searching by beginning and/or ending the value with an asterisk (`*`).
- It is now possible to set custom field parameters on Matrix block queries.
- Added a new `|literal` Twig filter which can be used to escape commas and asterisks within element criteria parameter values.
- Added the `{% requireAdmin %}` tag.
- The `{% redirect %}` tag now optionally accepts a HTTP status code parameter.
- Added `craft.request.getQueryString()`.
- Added `craft.request.getQueryStringWithoutPath()`.
- Added `craft.request.getIpAddress()`.
- Added the [siteName](https://craftcms.com/docs/config-settings#siteName) config setting, which overrides the Settings → General → Site Name setting, and can be set on a per-locale basis.
- Added the [timezone](https://craftcms.com/docs/config-settings#timezone) config setting, which overrides the Settings → General → Timezone setting.
- Added the [logDumpMethod](https://craftcms.com/docs/config-settings#logDumpMethod) config setting, which defines the method that should be used to export variables in Craft’s logs when Dev Mode is enabled.
- Added the [rotateImagesOnUploadByExifData](https://craftcms.com/docs/config-settings#rotateImagesOnUploadByExifData) config setting, which determines whether Craft should automatically rotate images according to their “Orientation” EXIF entry on upload.
- Added the [invalidUserTokenPath](https://craftcms.com/docs/config-settings#invalidUserTokenPath) config setting, which replaces `activateAccountFailurePath` (now deprecated).
- Added support for [Element Actions](http://buildwithcraft.com/docs/plugins/element-actions).
- Added [BaseModel::$strictAttributes](https://craftcms.com/classreference/models/BaseModel#strictAttributes-detail), which subclasses can set to `false` if they want to allow additional attributes to be set on the model besides those defined by `defineAttributes()`.
- Craft will now autoload any classes within plugins’ `enums/` folders.
- Added [UsersController::actionSendPasswordResetEmail()](https://craftcms.com/classreference/controllers/UsersController#actionSendPasswordResetEmail-detail).
- Added [IElementAction](https://craftcms.com/classreference/elementactions/IElementAction) and [BaseElementAction](https://craftcms.com/classreference/elementactions/BaseElementAction), which plugins can implement/extend for providing their own custom Element Action classes.
- Added [IElementType::getAvailableActions()](https://craftcms.com/classreference/elementtypes/IElementType#getAvailableActions-detail), which specifies which actions should be available on the index page for a given source.
- Added [BaseElementType::defineSortableAttributes()](https://craftcms.com/classreference/elementtypes/BaseElementType#defineSortableAttributes-detail).
- Added [AppHelper::getPhpConfigValueAsBool()](https://craftcms.com/classreference/helpers/AppHelper#getPhpConfigValueAsBool-detail).
- Added [AppHelper::getPhpConfigValueInBytes()](https://craftcms.com/classreference/helpers/AppHelper#getPhpConfigValueInBytes-detail).
- Added [DateTimeHelper::uiTimestamp()](https://craftcms.com/classreference/helpers/DateTimeHelper#uiTimestamp-detail).
- Added [DbHelper::getTextualColumnTypeByContentLength()](https://craftcms.com/classreference/helpers/DbHelper#getTextualColumnTypeByContentLength-detail).
- Added [DbHelper::escapeParam()](https://craftcms.com/classreference/helpers/DbHelper#escapeParam-detail).
- Added [DbHelper::getTextualColumnStorageCapacity()](https://craftcms.com/classreference/helpers/DbHelper#getTextualColumnStorageCapacity-detail).
- Added [ImageHelper::getPngImageInfo()](https://craftcms.com/classreference/helpers/ImageHelper#getPngImageInfo-detail).
- Added [FieldModel::hasContentColumn()](https://craftcms.com/classreference/models/FieldModel#hasContentColumn-detail).
- Added [CategoriesService::fillGapsInCategoryIds()](https://craftcms.com/classreference/services/CategoriesService#fillGapsInCategoryIds-detail).
- Added [ImagesService::getExifData()](https://craftcms.com/classreference/services/ImagesService#getExifData-detail).
- Added [PluginsService::callFirst()](https://craftcms.com/classreference/services/PluginsService#callFirst-detail).
- Added [UserSessionService::loginByUserId()](https://craftcms.com/classreference/services/UserSessionService#loginByUserId-detail).
- Added [UserSessionService::authorize()](https://craftcms.com/classreference/services/UserSessionService#authorize-detail).
- Added [UserSessionService::deauthorize()](https://craftcms.com/classreference/services/UserSessionService#deauthorize-detail).
- Added [UserSessionService::checkAuthorization()](https://craftcms.com/classreference/services/UserSessionService#checkAuthorization-detail).
- Added [UserSessionService::requireAuthorization()](https://craftcms.com/classreference/services/UserSessionService#requireAuthorization-detail).
- Added [UsersService::getUserByEmail()](https://craftcms.com/classreference/services/UsersService#getUserByEmail-detail).
- Added [UsersService::getPasswordResetUrl()](https://craftcms.com/classreference/services/UsersService#getPasswordResetUrl-detail).
- Added [UsersService::sendPasswordResetEmail()](https://craftcms.com/classreference/services/UsersService#sendPasswordResetEmail-detail).
- Added [Event::$performAction](https://craftcms.com/classreference/etc/events/Event#performAction-detail), which event callers can check after the event has been fired to see if it should go through with the action, when appropriate.
- Added the [assets.onBeforeDeleteAsset](https://craftcms.com/docs/plugins/events-reference#assets-onBeforeDeleteAsset) event.
- Added the [assets.onDeleteAsset](https://craftcms.com/docs/plugins/events-reference#assets-onDeleteAsset) event.
- Added the [assets.onBeforeUploadAsset](https://craftcms.com/docs/plugins/events-reference#assets-onBeforeUploadAsset) event.
- Added the [elements.onBeforeSaveElement](https://craftcms.com/docs/plugins/events-reference#elements-onBeforeSaveElement) event.
- Added the [elements.onSaveElement](https://craftcms.com/docs/plugins/events-reference#elements-onSaveElement) event.
- Added the [entryRevisions.onDeleteDraft](https://craftcms.com/docs/plugins/events-reference#entryRevisions-onDeleteDraft) event.
- Added the [fields.onSaveFieldLayout](https://craftcms.com/docs/plugins/events-reference#fields-onSaveFieldLayout) event.
- Added the [globals.onBeforeSaveGlobalContent](https://craftcms.com/docs/plugins/events-reference#globals-onBeforeSaveGlobalContent) event.
- Added the [localization.onBeforeDeleteLocale](https://craftcms.com/docs/plugins/events-reference#localization-onBeforeDeleteLocale) event.
- Added the [localization.onDeleteLocale](https://craftcms.com/docs/plugins/events-reference#localization-onDeleteLocale) event.
- Added the [sections.onBeforeSaveEntryType](https://craftcms.com/docs/plugins/events-reference#sections-onBeforeSaveEntryType) event.
- Added the [sections.onSaveEntryType](https://craftcms.com/docs/plugins/events-reference#sections-onSaveEntryType) event.
- Added the [sections.onBeforeSaveSection](https://craftcms.com/docs/plugins/events-reference#sections-onBeforeSaveSection) event.
- Added the [sections.onSaveSection](https://craftcms.com/docs/plugins/events-reference#sections-onSaveSection) event.
- Added the [structures.onBeforeMoveElement](https://craftcms.com/docs/plugins/events-reference#structures-onBeforeMoveElement) event.
- Added the [structures.onMoveElement](https://craftcms.com/docs/plugins/events-reference#structures-onMoveElement) event.
- Added the [users.onBeforeSetPassword](https://craftcms.com/docs/plugins/events-reference#users-onBeforeSetPassword) event.
- Added the [users.onSetPassword](https://craftcms.com/docs/plugins/events-reference#users-onSetPassword) event.
- Added the [users.onVerifyUser](https://craftcms.com/docs/plugins/events-reference#users-onVerifyUser) event.
- Added the [addEntryActions](https://craftcms.com/docs/plugins/hooks-reference#addEntryActions) plugin hook, for adding additional actions to the Entries index.
- Added the [addCategoryActions](https://craftcms.com/docs/plugins/hooks-reference#addCategoryActions) plugin hook, for adding additional actions to the Categories index.
- Added the [addAssetActions](https://craftcms.com/docs/plugins/hooks-reference#addAssetActions) plugin hook, for adding additional actions to the Assets index.
- Added the [addUserActions](https://craftcms.com/docs/plugins/hooks-reference#addUserActions) plugin hook, for adding additional actions to the Users index.
- Added the [getElementRoute](https://craftcms.com/docs/plugins/hooks-reference#getElementRoute) hook, which gives plugins an opportunity to override how element requests should get routed.
- Added the [modifyEntryTableAttributes](https://craftcms.com/docs/plugins/hooks-reference#modifyEntryTableAttributes) hook, for modifying the Entry index table columns.
- Added the [modifyCategoryTableAttributes](https://craftcms.com/docs/plugins/hooks-reference#modifyCategoryTableAttributes) hook, for modifying the Category index table columns.
- Added the [modifyAssetTableAttributes](https://craftcms.com/docs/plugins/hooks-reference#modifyAssetTableAttributes) hook, for modifying the Asset index table columns.
- Added the [modifyUserTableAttributes](https://craftcms.com/docs/plugins/hooks-reference#modifyUserTableAttributes) hook, for modifying the User index table columns.
- Added the [getEntryTableAttributeHtml](https://craftcms.com/docs/plugins/hooks-reference#getEntryTableAttributeHtml) hook, for customizing Entry index table attributes’ HTML.
- Added the [getCategoryTableAttributeHtml](https://craftcms.com/docs/plugins/hooks-reference#getCategoryTableAttributeHtml) hook, for customizing Category index table attributes’ HTML.
- Added the [getAssetTableAttributeHtml](https://craftcms.com/docs/plugins/hooks-reference#getAssetTableAttributeHtml) hook, for customizing Asset index table attributes’ HTML.
- Added the [getUserTableAttributeHtml](https://craftcms.com/docs/plugins/hooks-reference#getUserTableAttributeHtml) hook, for customizing User index table attributes’ HTML.
- Element types can now implement `getFieldsForElementsQuery()` instead of `getContentFieldColumnsForElementsQuery()`, which provides a way to specify the fields’ contexts, so non-global fields can be used as parameters when searching for elements.
- Added `Craft.getCsrfInput()`.
- Added `BaseElementSelectorModal::showFooterSpinner()`.
- Added `BaseElementSelectorModal::hideFooterSpinner()`.
- Added `BaseElementSelectorModal::updateSelectBtnState()`.
- Added `BaseElementSelectorModal::enableSelectBtn()`.
- Added `BaseElementSelectorModal::disableSelectBtn()`.
- Added `BaseElementSelectorModal::enableCancelBtn()`.
- Added `BaseElementSelectorModal::disableCancelBtn()`.
- Added `BaseElementSelectInput::getModalSettings()`.
- Added `BaseElementSelectInput::appendElement()`.
- Added the `_includes/previewbtns.html` include template.
- Added the `selectable` and `multiSelect` settings to `BaseElementIndex`.
- Added the `hideOnSelect` setting to `BaseElementSelectorModal`.
- Added the `disableElementsOnSelect` setting to `BaseElementSelectorModal` (replacing `disableOnSelect`).
- Added the `fieldId`, `modalSettings`, `onSelectElements`, `onRemoveElements`, `sortable`, `selectable`, and `editable` settings to `BaseElementSelectInput`.
- Added a `totalSelected` getter to `BaseElementSelectInput`.
- Added `Garnish.copyInputValues()`.
- Added `checkboxMode` and `allowEmpty` settings to `Garnish.Select`.
- Added `helperBaseZindex`, `helperLagBase`, `helperLagIncrementDividend`, `helperSpacingX`, `helperSpacingY`, `singleHelper`, and `copyDraggeeInputValuesToHelper` settings to `Garnish.Drag`.
- Added `Garnish.Base::disable()` and `enable()`, for disabling/enabling all event listeners that were initiated via `addListener()`.
- Added the `elementSelectField()` macro to `_includes/forms.html`.
- It is now possible to create divs that are styled like checkboxes by giving them a `checkbox` class. They can also mimic the indeterminate checkbox look with an `indeterminate` class.
- Added new “minus”, “asc”, and “desc” icons to Craft’s icon font.
- Added the PEL library.

### Changed
- Craft now automatically clears its data caches when it detects that the craft/app/ folder has been moved.
- Craft now responds with a 503 response code whenever one of its early initialization checks fail, to prevent things like Varnish from caching them.
- After updating Craft to a newer build of the same version, the user is now redirected based on the [postCpLoginRedirect](https://craftcms.com/docs/config-settings#postCpLoginRedirect) config setting.
- The iconv PHP module is no longer a showstopping requirement.
- Craft no longer writes out a log if the server has a buggy version of iconv installed. Instead the buggy version is reported in /admin/utils/serverinfo.
- Improved the performance of element queries that are ordered by the dateCreated column.
- Improved the performance of element queries that use the `search` parameter.
- Craft’s mobile detection script has been updated (affecting [craft.request.isMobileBrowser()](http://buildwithcraft.com/docs/templating/craft.request#isMobileBrowser), [HttpRequestService::isMobileBrowser()](https://craftcms.com/classreference/services/HttpRequestService#isMobileBrowser-detail), and `Garnish.isMobileBrowser()`).
- Element index toolbars now stick to the top of the window while scrolling down the page.
- Sticky sidebars are no longer jerky when scrolling down the page in Safari.
- Element statuses are now positioned to the right of their labels.
- Timestamps are now formatted based on how long ago they occurred. For example, “10:00 AM”, “Yesterday”, “Tuesday”, or “11/5/2013”.
- Live Preview now stores the editor and preview widths in percentages rather than pixels, and adjusts their pixel sizes accordingly when the browser window is resized.
- Improved the usability of admin table row sorting.
- Improved the usability of Craft on mobile devices.
- Improved the look of [Matrix fields](http://buildwithcraft.com/docs/matrix-fields).
- Improved the look of selected elements.
- Checkboxes now have custom styling.
- Plain Text fields that have a Max Length set will now show the remaining character count within the text input rather than below it.
- Rich Text fields will now get a validation error if their value contains more data than their column type can store.
- Multi-select and Checkboxes fields now choose their database column type based on the total possible size of their values. (Fields will need to be re-saved before this change takes effect.)
- Dropdown and Radio Buttons fields now use `varchar` database columns. (Fields will need to be re-saved before this change takes effect.)
- Improved the usability of element sorting in relational fields.
- Relational fields with a Limit of 1 now hide the “Add” button once an element has been selected.
- Position Select field buttons now have tool tips.
- When dragging to reorder multiple items at once, the item that was actually clicked on is no longer repositioned before the others afterwards.
- The Author field is now only visible if the user has permission to edit other authors’ entries in the section.
- The Author and Parent Entry fields on the Edit Entry page now use element selector inputs.
- Category groups can now have multiple content tabs.
- The Parent Category field on the Edit Category page now uses an element selector input.
- The options that were previously tucked away in file context menus on the Assets index have been moved to a toolbar menu button that appears when at least one asset is selected.
- It is now possible to edit the content of assets that have been uploaded to a dynamic subfolder on unsaved elements.
- After moving a file to a new folder, the asset index now stays put, rather than selecting the target folder.
- When uploading a file that exceeds the [maxUploadFileSize](https://craftcms.com/docs/config-settings#maxUploadFileSize) config setting, the error message now includes the max file size in a nicer format.
- Uploaded files that don’t have safe filenames are now handled more gracefully.
- All of the user action buttons are now grouped into a single menu on the Edit User page.
- Users are no longer automatically activated after an admin registers them with “Send verification email” unchecked.
- Craft will now set a username cookie after activating a user account.
- User activation, email verification, and password resetting are now possible from the Control Panel when the system is off.
- It is now possible to edit selected users from Users fields by double-clicking on them, if the logged-in user has permission to do so.
- When users are required to change their passwords, the Password Reset page no longer allows them to set their existing password.
- Admins are no longer allowed to change other users’ passwords.
- The [overridePhpSessionLocation](https://craftcms.com/docs/config-settings#overridePhpSessionLocation) config setting is set to `false` by default now.
- The [testToEmailAddress](https://craftcms.com/docs/config-settings#testToEmailAddress) config setting can now be set to an array of email addresses.
- `craft/app/index.php` now calls `setlocale()`.
- Craft no longer calls `date_default_timezone_set()`, `mb_internal_encoding()`, `mb_http_input()`, `mb_http_output()`, or `mb_detect_order()` when it is loaded via `bootstrap.php` directly.
- The built-in error templates will no longer HTML-encode messages that were passed as `Twig_Markup` objects.
- [DateTimeHelper::isToday()](https://craftcms.com/classreference/helpers/DateTimeHelper#isToday-detail), [wasYesterday()](https://craftcms.com/classreference/helpers/DateTimeHelper#wasYesterday-detail), [isThisYear()](https://craftcms.com/classreference/helpers/DateTimeHelper#isThisYear-detail), [isThisWeek()](https://craftcms.com/classreference/helpers/DateTimeHelper#isThisWeek-detail), [isThisMonth()](https://craftcms.com/classreference/helpers/DateTimeHelper#isThisMonth-detail), [wasInThePast()](https://craftcms.com/classreference/helpers/DateTimeHelper#wasInThePast-detail), and [wasWithinLast()](https://craftcms.com/classreference/helpers/DateTimeHelper#wasWithinLast-detail) now take the system timezone into account.
- [EntriesController::actionSaveEntry()](https://craftcms.com/classreference/controllers/EntriesController#actionSaveEntry-detail) now includes an `id` property in its response JSON when called over Ajax, set to the entry’s ID.
- [FieldsService::assembleLayoutFromPost()](https://craftcms.com/classreference/services/FieldsService#assembleLayoutFromPost-detail) no longer accepts a `$customizableTabs` argument.
- [FieldsService::assembleLayout()](https://craftcms.com/classreference/services/FieldsService#assembleLayout-detail) no longer accepts a `$customizableTabs` argument.
- [FieldsService::saveLayout()](https://craftcms.com/classreference/services/FieldsService#saveLayout-detail) no longer accepts a `$customizableTabs` argument.
- [PluginsService::call()](https://craftcms.com/classreference/services/PluginsService#call-detail) can now omit results from plugins that had the requested method, returned null.
- [UserSessionService::getReturnUrl()](https://craftcms.com/classreference/services/UserSessionService#getReturnUrl-detail) now accepts a `$delete` argument. If set to `true`, the return URL will be cleared automatically.
- [WebApp::on()](https://craftcms.com/classreference/etc/web/WebApp#on-detail) no longer attaches event handlers when Craft is in the middle of updating itself or a plugin, by default.
- [WebApp::on()](https://craftcms.com/classreference/etc/web/WebApp#on-detail) now accepts an `$evenDuringUpdates` argument. If set to `true`, the event handler will get attached even when Craft is in the middle of updating itself or a plugin.
- [assets.onBeforeSaveAsset](https://craftcms.com/docs/plugins/events-reference#assets-onBeforeSaveAsset) event handlers can now prevent the corresponding action from occurring.
- [categories.onBeforeSaveCategory](https://craftcms.com/docs/plugins/events-reference#categories-onBeforeSaveCategory) event handlers can now prevent the corresponding action from occurring.
- [email.onBeforeSendEmail](https://craftcms.com/docs/plugins/events-reference#email-onBeforeSendEmail) event handlers can now prevent the corresponding action from occurring.
- [entries.onBeforeSaveEntry](https://craftcms.com/docs/plugins/events-reference#entries-onBeforeSaveEntry) event handlers can now prevent the corresponding action from occurring.
- [entryRevisions.onBeforeDeleteDraft](https://craftcms.com/docs/plugins/events-reference#entryRevisions-onBeforeDeleteDraft) event handlers can now prevent the corresponding action from occurring.
- [tags.onBeforeSaveTag](https://craftcms.com/docs/plugins/events-reference#tags-onBeforeSaveTag) event handlers can now prevent the corresponding action from occurring.
- [userSession.onBeforeLogin](https://craftcms.com/docs/plugins/events-reference#userSession-onBeforeLogin) event handlers can now prevent the corresponding action from occurring.
- [userSession.onBeforeLogout](https://craftcms.com/docs/plugins/events-reference#userSession-onBeforeLogout) event handlers can now prevent the corresponding action from occurring.
- [users.onBeforeSaveUser](https://craftcms.com/docs/plugins/events-reference#users-onBeforeSaveUser) event handlers can now prevent the corresponding action from occurring.
- [users.onBeforeActivateUser](https://craftcms.com/docs/plugins/events-reference#users-onBeforeActivateUser) event handlers can now prevent the corresponding action from occurring.
- [users.onBeforeUnlockUser](https://craftcms.com/docs/plugins/events-reference#users-onBeforeUnlockUser) event handlers can now prevent the corresponding action from occurring.
- [users.onBeforeSuspendUser](https://craftcms.com/docs/plugins/events-reference#users-onBeforeSuspendUser) event handlers can now prevent the corresponding action from occurring.
- [users.onBeforeUnsuspendUser](https://craftcms.com/docs/plugins/events-reference#users-onBeforeUnsuspendUser) event handlers can now prevent the corresponding action from occurring.
- [users.onBeforeDeleteUser](https://craftcms.com/docs/plugins/events-reference#users-onBeforeDeleteUser) event handlers can now prevent the corresponding action from occurring.
- The [assets.onSaveAsset](https://craftcms.com/docs/plugins/events-reference#assets-onSaveAsset) event now comes with an `isNewAsset` param.
- The [users.onBeforeDeleteUser](https://craftcms.com/docs/plugins/events-reference#users-onBeforeDeleteUser) and [onDeleteUser](https://craftcms.com/docs/plugins/events-reference#users-onDeleteUser) events now come with a `transferContentTo` param, which identifies the user that all of the deleted user’s content should be transferred to (if any).
- `IElementType::getIndexHtml()` now takes two new arguments: `$includeContainer` and `$showCheckboxes`. The former is an bool that indicates whether the method should include the element container HTML in its response, and the latter indicates whether the elements should have checkboxes.
- `IElementType::getSources()` should now include a `structureEditable` boolean within its response for each source that is structure-based and which the current user is allowed to edit.
- Structures no longer have a `movePermission`, or have any need for it.
- `ElementsService` is now forgiving of an element type whose `populateElementModel()` method doesn’t return an actual element model.
- The `Craft.LivePreview` class is no longer entry-specific, so other content types can implement Live Preview functionality.
- `Garnish.Select::selectItem()` no longer sets keyboard focus to the item by default, and now has a second parameter which can be set to `true` if that’s the desired behavior.
- `Garnish.Modal` now fires `show`, `hide`, `fadeIn`, and `fadeOut` events when the corresponding actions take place.
- `Garnish.MenuBtn` now fires an `optionSelect` event when an option is selected.
- Made several performance and stability improvements to `Garnish.DragSort`.
- `ElementsController` and `StructureController` actions are now restricted to Control Panel requests.
- `BaseElementSelectInput` now accepts a single `settings` object passed to its constructor, rather than a long list of arguments.
- The `_layouts/elementindex.html` template now assigns the `BaseElementIndex` instance to `Craft.elementIndex`, so other scripts can reference it.
- Moved `.formsubmit` handling to `Craft.initUiElements()`, so it’s now possible to create form-submitting HTML elements after page load.
- `.formsubmit` HTML elements can now have `data-param` and `data-value` attributes, for adding a custom parameter to the form when clicked on.
- `Garnish.Select` now calls `onSelectionChange` when selected items are removed via `removeItems()`.
- Updated Twig to 1.16.2.
- Updated PHPMailer to 5.2.9.
- Updated PHPUnit to 4.3.5.
- Updated Mockery to 0.9.2.
- Updated jQuery Timepicker to 1.4.13.
- Updated Velocity.js to 1.1.0.
- Updated Redactor to 10.0.5.
- Updated Imagine to 0.6.2.

### Deprecated
- [UserSessionService::impersonate()](https://craftcms.com/classreference/services/UserSessionService#impersonate-detail) is now deprecated. Use [loginByUserId()](https://craftcms.com/classreference/services/UserSessionService#loginByUserId-detail) instead.
- [UsersController::actionForgotPassword()](https://craftcms.com/classreference/controllers/UsersController#actionForgotPassword-detail) is now deprecated. Use [actionSendPasswordResetEmail()](https://craftcms.com/classreference/controllers/UsersController#actionSendPasswordResetEmail-detail) instead.
- [UsersController::actionValidate()](https://craftcms.com/classreference/controllers/UsersController#actionValidate-detail) is now deprecated. Use [actionSetPassword()](https://craftcms.com/classreference/controllers/UsersController#actionSetPassword-detail) instead.
- [UsersService::sendForgotPasswordEmail()](https://craftcms.com/classreference/services/UsersService#sendForgotPasswordEmail-detail) is now deprecated. Use [sendPasswordResetEmail()](https://craftcms.com/classreference/services/UsersService#sendPasswordResetEmail-detail) instead.
- The `entryRevisions.onAfterDeleteDraft` event is now deprecated. Use [onDeleteDraft](https://craftcms.com/docs/plugins/events-reference#entryRevisions-onDeleteDraft) instead.

### Fixed
- Fixed a bug where non-admins could access various Admin-restricted areas of the Control Panel (though they couldn’t actually make any changes).
- Fixed a bug where requests to an action URL could have the specified action route overwritten if the request URI matched a special user path.
- Fixed a bug where Craft’s built-in error templates were encoding the HTML entities within PHP errors when the `html_errors` setting was enabled.
- Fixed a bug where inner-word underscores within error messages were italicizing the text between them in Craft’s built-in error templates.
- Fixed a bug where the Control Panel’s CSS/JS resource requests were getting corrupted when PHP’s `zlib.output_compression` setting was enabled.
- Fixed a bug where the application target language wasn’t getting set properly on console requests.
- Fixed a bug where Craft was holding onto users’ return URLs longer than it should have.
- Fixed a bug where users with permission to administrate users could activate a pending user account by suspending them and then unsuspending them.
- Fixed a bug where CP resources could be broken if the `craft/app/` folder was moved.
- Fixed a MySQL error that could occur a field type’s `getSearchKeywords()` method returned more than 64K worth of data.
- Fixed a bug where Arabic characters in uploaded filenames would get stripped out.
- Fixed a bug where the confirmation dialog that’s supposed to appear when leaving an Edit Entry page with unsaved changes wasn’t working in newer versions of Chrome.
- Fixed a bug where editable table rows would appear to lose their values when getting sorted.
- Fixed a bug where saving an element less than 200 milliseconds after a related element was removed from one of its fields would result in the relation removal getting ignored.
- Fixed a bug where elements not be visible while animating into place after being selected in a relational field from Live Preview.
- Fixed the styling of the Logout Warning modal for RTL languages.
- Fixed a bug where Categories fields had overbearing Limit settings, even applying to categories that had been unchecked.
- Fixed a bug where Categories fields would show the titles of all categories in the group within collapsed Matrix blocks’ field preview text.
- Fixed a bug where image transforms based on 8-bit and 24-bit PNGs were getting saved as 32-bit PNGs. (The fix currently only helps environments with ImageMagick installed; the issue still exists for GD.)
- Fixed a bug where the “Remove files” permission was not being fully enforced when deleting files from an asset source.
- Fixed a bug where the `paginate.first` variable within the `{% paginate %}` tag would have an incorrect value if there were no elements to paginate.
- Fixed a bug where calling `HttpRequestService::getQuery('foo.0')` would not return `$_GET['foo'][0]` as expected (ditto for `getPost()` and `getParam()`).
- Fixed a PHP error that could occur if an invalid path was passed to `IOHelper::getRealPath()`.

## 2.2.2607 - 2014-11-21

### Fixed
- Fixed a JavaScript error that occurred when linking to an entry or asset from a Rich Text field.
- Fixed a bug where error messages that occurred when updating Craft could show up with unparsed Markdown formatting.

## 2.2.2604 - 2014-11-20

### Changed
- Craft now refers to its full list of known MIME types when determining the MIME type to identify an asset as when uploading it to Amazon S3 or Google Cloud.

### Fixed
- Fixed a bug where moving an asset between folders in the same source would not move its transforms.
- Fixed a bug where moving an asset between two sources would break the transform index for that asset.
- Fixed a bug where Craft would attempt to resize images that were not considered manipulatable.
- Fixed a JavaScript error that would occur when attempting to select an image or entry in a Rich Text field in certain circumstances.
- Fixed a PHP error that would occur if you set the `cacheMethod` config setting to “db” in certain circumstances.
- Fixed a bug where the error message that Craft would display when attempting to run an older version of Craft with a newer version’s database would show HTML entities as text.
- Fixed a MySQL error that could occur when the `generateTransformsBeforePageLoad` config setting was set to `true` and MySQL was running in strict mode.
- Fixed a bug where the log viewer at /admin/utils/logs was only showing the first log for each HTTP request.
- Fixed a bug where if an admin with a Get Help widget had their admin status revoked, they would still be able to edit the widget’s settings, and get a fatal error when saving them.

## 2.2.2601 - 2014-11-04

### Changed
- When dragging a subfolder or file, the asset index will now always make it clear where the subfolder/file will end up when dropped.
- Transform names are now HTML-encoded in the “Select transform” menu within asset selector modals.

### Fixed
- Fixed a PHP error that occurred on the Plugins page when two plugins had the same handle but different casing.
- Fixed a JavaScript error that occurred when opening asset selector modals.
- Fixed a bug where some entries could be skipped when converting a Channel to a Structure, resulting in an incomplete tree.
- Fixed a bug where `craft.users` would return all users if the `group` parameter was set to an invalid group handle.
- Fixed a bug where user photos that were uploaded from the front-end weren’t getting properly escaped filenames.
- Fixed a bug where the asset index was getting refreshed in cases where it didn’t need to be.
- Fixed a bug where setting the `maxCachedCloudImageSize` config setting to `0` would prevent transforms from being created.
- Fixed a bug where the “Purify HTML?” Rich Text field setting was stripping page breaks.
- Fixed a bug where custom fields’ data wasn’t getting loaded when querying for elements through the CLI.
- Fixed a MySQL error that occurred after changing the `cacheMethod` config setting to `db`.

## 2.2.2598 - 2014-10-27

### Changed
- Page titles are now HTML-encoded by default in the Control Panel.
- Element index pages in the Control Panel now HTML-encode the element attributes by default.
- The default error templates now HTML-encode the error message and parse it as Markdown.
- [BaseElementModel::getLink()](http://buildwithcraft.com/classreference/models/BaseElementModel#getLink-detail) and [AssetFileModel::getImg()](http://buildwithcraft.com/classreference/models/AssetFileModel#getImg-detail) now HTML-encode the element’s title.
- The Delete Stale Template Caches task now deletes template caches that may involve pending or expired entries.
- Improved the performance of the Edit Entry page on sites with a large number of eligible authors.
- If an error occurs when drag-n-drop uploading to an Assets field, the error message is now displayed.

### Fixed
- Fixed a bug where the Asset Index page would be unable to perform some file actions after moving a file to a different source or subfolder, until a new source/subfolder had been manually selected.
- Fixed a bug where drag-n-drop uploading to Assets fields was not working on sites with CSRF protection enabled.
- Fixed a bug where the First Name field in the Edit User page was getting auto-focused instead of Username.
- Fixed a bug where the “Login as user” button would redirect you to the Control Panel even if the user didn’t have permission to access the Control Panel.
- Fixed a SQL error caused by the Recent Entries widget when using MariaDB.
- Fixed the positioning of spinners that are floated alongside buttons in the Control Panel for RTL languages.
- Fixed a bug where grid items within the Control Panel might not be positioned correctly on page load in Safari.
- Fixed the height of dropdown menus in the Control Panel in Safari.

### Security
- Fixed several minor security vulnerabilities throughout the Control Panel.

## 2.2.2596 - 2014-10-21

### Changed
- Craft no longer automatically creates Quick Post widgets for each section when users visit the Dashboard for the first time.
- Improved the JavaScript initialization performance on the Assets index page for sites with several hundreds of subfolders in their asset sources.
- Windows now auto-scroll at variable speeds when dragging an item close to the window’s edges, depending on how close the item is to the edge.
- Improved the element dragging behavior for relational fields.
- Craft’s bootstrap script now ensures that the `craft/config/` folder is writable if the `license.key` file doesn’t exist yet.

### Fixed
- Fixed a bug where assets were getting timestamps appended to their filenames when they were moved within a source that had cache headers enabled.
- Fixed a bug where relative time strings were reporting inaccurate times for durations greater than 23 minutes.
- Fixed a bug where Field Layout Designers were not getting initialized properly when output within a namespaced template.
- Fixed a bug where the window auto-scrolling while dragging was not working.
- Fixed a JavaScript error that was breaking the Control Panel when running Craft from a private window in Safari 8.

## 2.2.2593 - 2014-10-10

### Changed
- Rich Text fields with the “Clean up HTML?” setting enabled will now remove `<a>` tags that don’t have any attributes or inner HTML.
- Rich Text fields with the “Purify HTML?” setting enabled no longer remove `target="_blank"` attributes on `<a>` tags.
- The `IPlugin` interface no longer requires that plugins have a static `log()` method.
- Improved the reliability of drag-n-drop-based interactions.

### Fixed
- Fixed a PHP error that would occur when passing “:empty:” or “:notempty:” into a date/time parameter.
- Fixed a bug where all assets would appear to be selected after uploding a new file from the Assets index page.
- Fixed a bug where Rackspace asset sources were incorrectly reporting that folders didn’t exist.
- Fixed a bug where entries and assets were losing their reference tags in Rich Text fields when re-edited.
- Fixed a CSS glitch on Rich Text fields where the code view would bleed a few pixels out of the right side of the field.
- Fixed a bug where the Login modal wouldn’t fade in if a user let their session expire from the Control Panel.
- Fixed a bug where Live Preview’s webpage preview pane wasn’t retaining its scroll position between refreshes in Firefox.
- Fixed a bug where the “Resend Activation Email” button wouldn’t work when users clicked an activation link but the token had expired.
- Fixed a Twig error on the email settings page that would occur when Gmail username/password validation failed.
- Fixed a bug where the “Add a block” menu button on Matrix fields could take a while to show the menu.
- Fixed a bug where if at least 10,000 messages had been logged for the same target log file in a single request, all but the last one would be discarded.
- Fixed a bug where the onBeforeVerifyUser event wasn’t getting fired.

## 2.2.2592 - 2014-10-02

### Fixed
- Fixed a bug where images with uppercase extensions were not being considered web-safe.
- Fixed a PHP error that could occur when generating an image transform for a non-web-safe image.
- Fixed a bug where Edit Entry pages would not show the “Parent Entry” field for Structure entries whose sections were not enabled for the current locale.
- Fixed a bug where elements in relational fields’ selection modals would still appear highlighted (rather than just disabled) after getting selected.
- Fixed a UI glitch that occurred when dragging elements within a Structure or Category Group.
- Fixed a bug where entry URIs were not wrapping on the Entries index page in Firefox.
- Fixed a bug where PHP functions that were called from Twig could receive strings as `Twig_Markup` objects.

## 2.2.2591 - 2014-09-26

### Fixed
- Fixed a Javascript error in the Control Panel.

## 2.2.2590 - 2014-09-26

### Fixed
- Fixed a bug where the Control Panel could cause Chrome to crash in rare situations.

## 2.2.2589 - 2014-09-25

### Changed
- The Control Panel is a little zippier now.
- Live Preview now shows the `col-resize` cursor style when the cursor is hovering between editor and preview panes.
- The Find and Replace tool is now a little more defensive against the possibility that its settings are stored as invalid JSON.
- Plugins are no longer required to extend [BasePlugin](http://buildwithcraft.com/classreference/etc/plugins/BasePlugin), so long as they are implementing the [IPlugin](http://buildwithcraft.com/classreference/etc/plugins/IPlugin) interface.

### Fixed
- Fixed a bug where Chrome would get random scrollbars in the Control Panel.
- Fixed a bug where you couldn’t install Craft on MySQL 5.7.
- Fixed a bug where you might get an “invalid compression level” error when trying to upload certain PNG files.
- Fixed a bug where it was not possible to replace a file that existed in an asset source but was not yet indexed by Craft.
- Fixed a bug where asset index records would not be updated in some cases.

## 2.2.2588 - 2014-09-23

### Fixed
- Fixed a MySQL error that could occur when saving elements on sites that have Lightswitch fields.

## 2.2.2587 - 2014-09-23

### Changed
- All Control Panel requests are now served with a “X-Robots-Tag: none” header, which tells Google/Yahoo/Bing to not index the page or follow any links on it.
- The Password input on Edit User pages now gets Show/Hide links like the other password fields in the Control Panel.
- When an element is saved, all of the content table columns that don’t map to fields in the element’s field layout are now explicitly set to NULL.
- `Ø` characters are now mapped to `O` when converting a string to ASCII.
- Improved the performance of CP resource requests.
- The Control Panel no longer checks the user’s authentication status every 5 seconds when they are logged out. (Now just every 60 seconds.)
- The onBeforeSaveEntry and onSaveEntry events now get fired when editing an entry from an Entries field.

### Fixed
- Fixed a bug where the Find and Replace tool could inadvertently delete all of your textual content if its settings somehow got stored as invalid JSON.
- Fixed a bug where some actions that should only be available to admins were not being enforced properly.
- Fixed a bug where element parameters that had multiple values specified in a single string separated by commas were not getting parsed correctly.
- Fixed a bug where file type validation would fail on Assets fields that had a file selected with an uppercase extension.
- Fixed a bug where images with uppercase file extensions were not considered manipulatable, and thus didn’t get thumbnails or image transforms.
- Fixed a bug where Matrix fields that had two Number sub-fields with the same field handle in two different block types could conflict with each other.
- Retroactively fixed a bug where some Matrix block rows would get left behind in the database when an element was deleted that had a Matrix field.
- Fixed a bug where CP notifications that were added via Javascript after the page load were getting 100% width.
- Fixed a PHP error that occurred when `FeedsService` encountered an invalid feed.
- Fixed a PHP error that occurred if the local copy of a remotely-stored image had not been saved successfully.

## 2.2.2586 - 2014-09-18

### Added
- Added [StringHelper::escapeCommas()](http://buildwithcraft.com/classreference/helpers/StringHelper#escapeCommas-detail).
- Added [UserSessionService::shouldExtendSession()](http://buildwithcraft.com/classreference/services/UserSessionService#shouldExtendSession-detail).
- Added [BaseOptionsFieldType::getDefaultValue()](http://buildwithcraft.com/classreference/fieldtypes/BaseOptionsFieldType#getDefaultValue-detail).

### Changed
- `IOHelper::getExtension()` no longer converts the file extension to lowercase.
- `ArrayHelper::stringToArray()` now removes the comma-escaping backslashes before returning the array.
- The CP’s user session timeout-checking Ajax requests no longer get logged in `craft/storage/runtime/logs/craft.log` when Craft is running in Dev Mode.
- Improved the performance of some animations in the Control Panel.

### Fixed
- Fixed a bug where the Page Break button in Rich Text fields wasn’t saving page breaks properly.
- Fixed a bug where publishing a draft of a nested Structure section entry would result in the entry having the URL Format of top level entries.
- Fixed a bug where quickly dragging and releasing a Structure entry or Category would result in the element getting hidden on the page.
- Fixed a bug where saving a user with a new password *and* the “Password Reset Required?” checkbox checked would result in a password reset no longer being required on the user account.
- Fixed a MySQL error that could occur if multiple Matrix block types were saved with the same handle.
- Fixed a PHP error that could occur when `HttpRequestService::close()` was called before the PHP session had been started.
- Fixed a bug where the `overridePhpSessionLocation` config setting would be ignored on case-sensitive file systems.
- Fixed a bug where files with an uppercase file extension could not be uploaded on case-sensitive file systems.
- Fixed a bug where assets with commas in their filenames would have strange results when being indexed.
- Fixed a bug where uploading files to a Rackspace source with an expired security token would not work as expected.
- Fixed a bug where newly-uploaded assets wouldn’t store the default values for any Checkboxes, Multi-select, Dropdown, or Radio Button fields.
- Fixed a bug where “AM” and “PM” might not have been translated properly when formatting a date with the `a` in the date format string.
- Fixed some broken links on the “What’s New” page.

## 2.2.2582 - 2014-09-08

### Changed
- Entry and category search results now place a 5X weight on search term matches within Title fields.
- Included the latest and greatest Control Panel translations.

### Fixed
- Fixed a bug that broke asset moving between two sources.
- Fixed a PHP error that would occur if the defaultImageQuality config setting was set to `0`.
- Fixed a bug where image quality settings were having the opposite effect for PNG files.
- Fixed a bug where asset uploading was broken when CSRF protection was enabled.
- Fixed a Javascript error that would occur when repositioning a category that had just been created.
- Fixed a bug where the words “Title”, “URI”, and “Slug” were not getting translated within validation errors.

## 2.2.2581 - 2014-09-04

### Changed
- Improved the look and usability of the Fatal Error screen in Craft’s updater.
- Craft no longer enforces auto-update permissions during an auto-update once the new files have been put into place.
- Craft no longer throws an exception if the `{% requireLogin %}` tag is called from the Login page. It will vent silently in the logs instead.
- [UserSessionService::getReturnUrl()](http://buildwithcraft.com/classreference/services/UserSessionService#getReturnUrl-detail) will now strip out any curly braces that may have made it into the Return URL.

### Fixed
- Fixed a bug where one of the 2.2 migrations could have changed the wrong Entry Types’ Title-related settings. **If you have already updated to Craft 2.2, you should verify that each of your sections’ Entry Type settings are correct**, lest you find yourself with unexpected entry titles.
- Fixed a bug where users couldn’t log in when Remember Me was checked if the userSessionDuration config setting was empty.
- Fixed a bug where Craft was throwing a “requireLogin was used on the login page, creating an infinite loop.” error when accessing the front-end site’s login path from the CP.
- Fixed an infinite redirect bug that would occur if a user session’s Return URL was set to the login path, somehow.
- Fixed a bug where switching the locale on a new Structure section entry wouldn’t retain the Parent Entry value, if there was one.
- Fixed a bug where field types’ [prepValueFromPost()](http://buildwithcraft.com/classreference/fieldtypes/BaseFieldType#prepValueFromPost-detail) methods could be getting modified values passed in, rather than the raw post data.
- Fixed a bug where the `{% paginate %}` tag was setting a `limit` parameter on the passed-in `ElementCriteriaModel` (rather than a copy), which could affect the output of the `ElementCriteriaModel` further down the page.
- Fixed some PHP deprecation warnings when running Craft from PHP 6+.

## 2.2.2579 - 2014-09-02

### Added
- Added [CSRF Protection](http://buildwithcraft.com/help/csrf-protection).
- Added logout notifications to the Control Panel.
- Single sections’ Entry Types now have “Show the Title field?”, “Title Field Label”, and “Title Format” settings.
- Singles now have a “Status” setting like other entries.
- Added the new [Position Select](http://buildwithcraft.com/docs/position-select-fields) field type.
- Added a “Purify HTML?” setting to [Rich Text](http://buildwithcraft.com/docs/rich-text-fields) fields.
- Added a “Minute Increment” setting to [Date/Time](http://buildwithcraft.com/docs/date-time-fields) fields.
- [Plain Text](http://buildwithcraft.com/docs/plain-text-fields) fields now show the remaining character count if they have a Max Length specified.
- Yii’s validation messages can now be translated using Craft’s [static translation support](http://buildwithcraft.com/help/static-translations).
- Users are now warned when their session is going to expire in less than two minutes, and they’re given the option to log out now or extend their session.
- Users are now notified when their session has ended, and they’re given the option to enter their password and be logged back in.
- Added a “Image Format” setting to [image transforms](http://buildwithcraft.com/docs/image-transforms) for specifying the image format that should be used.
- Craft now automatically generates all pending image transforms that were called in a request, after it is finished serving the page, regardless of whether the browser actually requests the transforms’ temporary URLs (pretty much eliminating the need for the [generateTransformsBeforePageLoad](http://buildwithcraft.com/docs/config-settings#generateTransformsBeforePageLoad) config setting).
- Added support for a “compressed” asset file kind which includes common compression file formats such as .zip and .tar.
- Added a global [getCsrfInput()](http://buildwithcraft.com/docs/templating/functions#getCsrfInput) template function for getting a hidden CSRF input if [CSRF protection](http://buildwithcraft.com/docs/config-settings#enableCsrfProtection) is enabled.
- Added a [`using key`](http://buildwithcraft.com/docs/templating/cache#using-key) param to the `{% cache %}` tag.
- Added an [`if`](http://buildwithcraft.com/docs/templating/cache#if) param to `{% cache %}` tags.
- Added an [nth()](http://buildwithcraft.com/docs/templating/elementcriteriamodel#nth) function to `ElementCriteriaModel` objects for fetching the element at a specific offset.
- Added `craft.userSession.getAuthTimeout()`, a wrapper for [UserSessionService::getAuthTimeout()](http://buildwithcraft.com/classreference/services/UserSessionService#getAuthTimeout-detail).
- Added `positionedBefore` and `positionedAfter` parameters to `ElementCriteriaModel`, for finding elements that are positioned before or after another element in a Structure section or category group.
- Added an [appId](http://buildwithcraft.com/docs/config-settings#appId) config setting, which can be used to prevent users from getting logged out after Capistrano deployments.
- Added a [cacheElementQueries](http://buildwithcraft.com/docs/config-settings#cacheElementQueries) config setting, which can be set to `false` to disable template caches from recording element queries.
- Added a [csrfTokenName](http://buildwithcraft.com/docs/config-settings#csrfTokenName) config setting.
- Added a [defaultCookieDomain](http://buildwithcraft.com/docs/config-settings#defaultCookieDomain) config setting.
- Added a [defaultFilePermissions](http://buildwithcraft.com/docs/config-settings#defaultFilePermissions) config setting, which is set to `0664` by default.
- Added an [enableCsrfProtection](http://buildwithcraft.com/docs/config-settings#enableCsrfProtection) config setting.
- Added an [filenameWordSeparator](http://buildwithcraft.com/docs/config-settings#filenameWordSeparator) config setting.
- Added an [limitAutoSlugsToAscii](http://buildwithcraft.com/docs/config-settings#limitAutoSlugsToAscii) config setting.
- Added a [maxSlugIncrement](http://buildwithcraft.com/docs/config-settings#maxSlugIncrement) config setting.
- Added a [postCpLoginRedirect](http://buildwithcraft.com/docs/config-settings#postCpLoginRedirect) config setting.
- Added a [postLoginRedirect](http://buildwithcraft.com/docs/config-settings#postLoginRedirect) config setting.
- Added a new `craft/app/bootstrap.php` file which holds all of Craft’s initialization code and returns the new Craft application instance without calling its [run()](http://www.yiiframework.com/doc/api/1.1/CApplication#run-detail) method. This makes it easy for other applications to load and interact with Craft without having it take over the HTTP request, by including `bootstrap.php` rather than `index.php`.
- Plugin log files generated with `MyPlugin::log()` can now be viewed in the Log Viewer utility at /admin/utils/logs.
- Added [AssetsHelper::cleanAssetName()](http://buildwithcraft.com/classreference/helpers/AssetsHelper#cleanAssetName-detail).
- Added [AssetTransformsService::getPendingTransformIndexIds()](http://buildwithcraft.com/classreference/services/AssetTransformsService#getPendingTransformIndexIds-detail).
- Added [BaseElementModel::getFieldValue()](http://buildwithcraft.com/classreference/models/BaseElementModel#getFieldValue-detail).
- Added [BaseEnum::getConstants()](http://buildwithcraft.com/classreference/enums/BaseEnum#getConstants-detail).
- Added [ConfigService::getUserSessionDuration()](http://buildwithcraft.com/classreference/services/ConfigService#getUserSessionDuration-detail).
- Added [Craft::dd()](http://buildwithcraft.com/classreference//Craft#dd-detail) for dumping a variable to the browser and immediately ending the request.
- Added [ElementCriteriaModel::setMatchedElements()](http://buildwithcraft.com/classreference/models/ElementCriteriaModel#setMatchedElements-detail).
- Added [EntryTypeModel::getSection()](http://buildwithcraft.com/classreference/models/EntryTypeModel#getSection-detail).
- Added [GeneratePendingTransformsTask](http://buildwithcraft.com/classreference/tasks/GeneratePendingTransformsTask).
- Added [HeaderHelper::getHeader()](http://buildwithcraft.com/classreference/helpers/HeaderHelper#getHeader-detail).
- Added [HeaderHelper::getMimeType()](http://buildwithcraft.com/classreference/helpers/HeaderHelper#getMimeType-detail).
- Added [HttpRequestService::isGetRequest()](http://buildwithcraft.com/classreference/services/HttpRequestService#isGetRequest-detail) / [getIsGetRequest()](http://buildwithcraft.com/classreference/services/HttpRequestService#getIsGetRequest-detail).
- Added [LocaleData::getAllTerritories()](http://buildwithcraft.com/classreference/etc/i18n/LocaleData#getAllTerritories-detail).
- Added [MigrationHelper::getTables()](http://buildwithcraft.com/classreference/helpers/MigrationHelper#getTables-detail).
- Added [MigrationHelper::getTable()](http://buildwithcraft.com/classreference/helpers/MigrationHelper#getTable-detail).
- Added [MigrationHelper::findForeignKeysTo()](http://buildwithcraft.com/classreference/helpers/MigrationHelper#findForeignKeysTo-detail).
- Added [MigrationHelper::dropAllForeignKeysOnTable()](http://buildwithcraft.com/classreference/helpers/MigrationHelper#dropAllForeignKeysOnTable-detail).
- Added [MigrationHelper::dropForeignKey()](http://buildwithcraft.com/classreference/helpers/MigrationHelper#dropForeignKey-detail).
- Added [MigrationHelper::dropAllIndexesOnTable()](http://buildwithcraft.com/classreference/helpers/MigrationHelper#dropAllIndexesOnTable-detail).
- Added [MigrationHelper::dropAllUniqueIndexesOnTable()](http://buildwithcraft.com/classreference/helpers/MigrationHelper#dropAllUniqueIndexesOnTable-detail).
- Added [MigrationHelper::dropIndex()](http://buildwithcraft.com/classreference/helpers/MigrationHelper#dropIndex-detail).
- Added [MigrationHelper::restoreAllIndexesOnTable()](http://buildwithcraft.com/classreference/helpers/MigrationHelper#restoreAllIndexesOnTable-detail).
- Added [MigrationHelper::restoreAllUniqueIndexesOnTable()](http://buildwithcraft.com/classreference/helpers/MigrationHelper#restoreAllUniqueIndexesOnTable-detail).
- Added [MigrationHelper::restoreIndex()](http://buildwithcraft.com/classreference/helpers/MigrationHelper#restoreIndex-detail).
- Added [MigrationHelper::restoreAllForeignKeysOnTable()](http://buildwithcraft.com/classreference/helpers/MigrationHelper#restoreAllForeignKeysOnTable-detail).
- Added [MigrationHelper::restoreForeignKey()](http://buildwithcraft.com/classreference/helpers/MigrationHelper#restoreForeignKey-detail).
- Added [UsersController::actionGetAuthTimeout()](http://buildwithcraft.com/classreference/controllers/UsersController#actionGetAuthTimeout-detail).
- Added [UserSessionService::getAuthTimeout()](http://buildwithcraft.com/classreference/services/UserSessionService#getAuthTimeout-detail).
- Added [UserSessionService::getStateCookie()](http://buildwithcraft.com/classreference/services/UserSessionService#getStateCookie-detail).
- Added [UserSessionService::getStateCookieValue()](http://buildwithcraft.com/classreference/services/UserSessionService#getStateCookieValue-detail).
- Added [UserSessionService::getIdentityCookie()](http://buildwithcraft.com/classreference/services/UserSessionService#getIdentityCookie-detail).
- Added [UserSessionService::getIdentityCookieValue()](http://buildwithcraft.com/classreference/services/UserSessionService#getIdentityCookieValue-detail).
- Added an [onBeforeDeleteElements](http://buildwithcraft.com/classreference/services/ElementsService#onBeforeDeleteElements-detail) event to `ElementsService`.
- Added an [onBeforeSendEmail](http://buildwithcraft.com/classreference/services/EmailService#onBeforeSendEmail-detail) event to `EmailService`.
- Added [onBeforeLogout](http://buildwithcraft.com/classreference/services/UserSessionService#onBeforeLogout-detail) and [onLogout](http://buildwithcraft.com/classreference/services/UserSessionService#onLogout-detail) events to `UserSessionService`.
- Added support for a `warning` parameter on the CP’s `_includes/forms/field.html` template, which will show a warning below the field’s input.
- Added a `Craft.secondsToHumanTimeDuration()` Javascript method that mimics [DateTimeHelper::secondsToHumanTimeDuration()](http://buildwithcraft.com/classreference/helpers/DateTimeHelper#secondsToHumanTimeDuration-detail).
- Added an Alert Modal style to the CP (.modal.alert).
- Added `Garnish.hasAttr()`, for determining if a DOM element has an attribute, regardless of what (if anything) it’s set to.
- Added `Garnish.Menu::addOptions()`, for dynamically adding new options to a menu.
- Added `autoShow`, `hideOnEsc`, `hideOnShadeClick`, and `shadeClass` settings to `Garnish.Modal`.
- Added `showCharsLeft`, `charsLeftClass`, and `negativeCharsLeftClass` settings to `Garnish.NiceText`.
- The CP now uses [Velocity.js](http://julian.com/research/velocity/) for all of its animations.

### Changed
- HTTP 503 errors now check for an `offline.html` template before falling back on 503.html.
- When the system is offline, Craft now responds to front-end site requests with a HTTP 503 error.
- When the system is offline, logged-in users who who don’t have offline access permissions will now get 503 errors rather than being automatically logged-out. The default error message also includes a link to the Logout page.
- “author”, “authorId”, “expiryDate”, “firstName”, “handle”, “lastName”, “img”, “name”, “postDate”, “section”, “sortOrder”, “type”, and “size” are no longer reserved field handles, although they should be used with caution. (An “author” field may result in unexpected behavior on the front end when assigned to entries, for example.)
- Matrix field values are now `ElementCriteriaModel` objects even during Live Preview, so templates no longer need to be defensive about the possibility of them being arrays.
- The Resave Elements task now logs a more helpful error in the event that an exception was thrown while resaving one of the elements.
- Single sections’ Entry Types no longer have editable names and handles, because that’s just silly.
- Translatable field labels are now marked with locale IDs to help identify which fields are translatable.
- Menu options no longer have wrapped text, and menus’ alignment with their trigger buttons will be flipped if necessary to avoid bleeding out of the window.
- If there are multiple Channel/Structure sections, the “New Entry” button on the Entries index now links to the New Entry page for the selected section, and has a small menu button next to it that provides links to create new entries in the other sections.
- Edit Entry URLs now include the entry’s slug, so it’s easier to identify the entry by its URL.
- Any periods that show up in a new entry’s title are no longer included in its auto-generated slug.
- When an admin is attempting to change *another* user’s email or password, the Enter Password modal’s text now reads “Please enter your password.” instead of “Please enter your current password.” to help clarify whose password should be entered.
- The Registration Date, Last Login Date, Last Invalid Login Date, and Last Password Change Date values on Edit User pages are now shown in the site’s timezone, and formatted according to the current user’s preferred locale.
- The Backup Database tool will no longer include the data in the `craft_sessions` table in its backup.
- Improved the performance of the Update Asset Indexes tool.
- Improved the performance of the CP’s layout manager.
- When editing a user, the Command/Ctrl+S shortcut will now keep you on the current user’s edit page after saving them.
- When editing a user account, the First Name field is now auto-focused.
- Adjacent checkbox fields that have instructional text now get a little spacing between them.
- The System Status and Site URL fields in General Settings now show warnings if their values are being overridden in `craft/config/general.php`.
- Asset transforms will now be generated in a web-safe image format if the source image is not.
- Craft now deletes any generated image transforms for an asset when it is being moved to a new asset source.
- When moving assets around, Craft now checks to make sure the target folder actually exists on the file system beforehand, and responds gracefully if it doesn’t.
- The [replace](http://buildwithcraft.com/docs/templating/filters#replace) filter now supports regular expressions, by setting the first argument to a string that begins and ends with forward slashes, e.g. `'/foo/'`.
- Craft no longer gives any special treatment to template paths that include a file extension, making it possible for the template path “readme” to match a file called “readme” (with no extension), or the template path “robots.txt” to match a file called “robots.txt.twig”.
- Craft is now forgiving of [CRAFT_TEMPLATES_PATH](http://buildwithcraft.com/docs/php-constants#craft-templates-path) values that don’t end in a slash.
- It is no longer necessary to have a `redirect` hidden input on [Login forms](http://buildwithcraft.com/docs/templating/login-form). If there isn’t one, Craft will default to the URL the user was originally requesting before [{% requireLogin %}](http://buildwithcraft.com/docs/templating/requirelogin) did its thing, and if that’s not set, it will fall back on the new [postLoginRedirect](http://buildwithcraft.com/docs/config-settings#postLoginRedirect) config setting.
- The [defaultFolderPermissions](http://buildwithcraft.com/docs/config-settings#defaultFolderPermissions) config setting is now set to `0775` by default.
- It is now possible to set the [maxInvalidLogins](http://buildwithcraft.com/docs/config-settings#maxInvalidLogins) config setting to an empty value such as `0` or `false` in order to disable that functionality altogether.
- Got rid of the `writableFilePermissions` and `writableFolderPermissions` config settings.
- [BaseController::redirectToPostedUrl()](http://buildwithcraft.com/classreference/controllers/BaseController#redirectToPostedUrl-detail) now accepts a second argument which defines the default URL to redirect to. If it’s not passed in, the current request’s URL will still be the fallback.
- [BaseElementModel::getPrevSibling()](http://buildwithcraft.com/classreference/models/BaseElementModel#getPrevSibling-detail) and [getNextSibling()](http://buildwithcraft.com/classreference/models/BaseElementModel#getNextSibling-detail) will now return the previous/next sibling even if they are disabled.
- All of the [BaseMigration](http://buildwithcraft.com/classreference/etc/db/BaseMigration) methods that wrap [DbCommand](http://buildwithcraft.com/classreference/etc/db/DbCommand) methods now return whatever their `DbCommand` method returned.
- [DbConnection::backup()](http://buildwithcraft.com/classreference/etc/db/DbConnection#backup-detail) now accepts an `$ignoreDataTables` argument where you can specify table names whose data should be excluded from the resulting backup.
- [DateTimeHelper::secondsToHumanTimeDuration()](http://buildwithcraft.com/classreference/helpers/DateTimeHelper#secondsToHumanTimeDuration-detail) will now return “0 minutes” or “0 seconds” (depending on `$showSeconds`’ value) if it would have otherwise returned an empty string.
- [ElementsService::buildElementsQuery()](http://buildwithcraft.com/classreference/services/ElementsService#buildElementsQuery-detail) now handles the `search` element criteria parameter, rather than leaving it up to [findElements()](http://buildwithcraft.com/classreference/services/ElementsService#findElements-detail).
- [HeaderHelper::setContentTypeByExtension()](http://buildwithcraft.com/classreference/helpers/HeaderHelper#setContentTypeByExtension-detail) will now return `true` or `false` depending on whether a known MIME type exists for the given extension, and the header was set successfully.
- [HeaderHelper::setHeader()](http://buildwithcraft.com/classreference/helpers/HeaderHelper#setHeader-detail) will now return `true` or `false` depending on whether the header was set successfully.
- [HttpRequestService::close()](http://buildwithcraft.com/classreference/services/HttpRequestService#close-detail) will now throw an exception if any content had already been output to the browser before it was called.
- [HttpRequestService::close()](http://buildwithcraft.com/classreference/services/HttpRequestService#close-detail) will now prepend any content it can find the active output buffer(s) to the passed-in `$content` argument.
- [Image::saveAs()](http://buildwithcraft.com/classreference/etc/io/Image#saveAs-detail) now accepts an optional `$sanitizeAndAutoQuality` argument.
- Refactored [ProfileLogRoute](http://buildwithcraft.com/classreference/etc/logging/ProfileLogRoute) and [WebLogRoute](http://buildwithcraft.com/classreference/etc/logging/WebLogRoute).
- [UserModel::__toString()](http://buildwithcraft.com/classreference/models/UserModel#__toString-detail) now outputs the user’s email address, regardless of whether their username has been updated to match it, if the [useEmailAsUsername](http://buildwithcraft.com/docs/config-settings#useEmailAsUsername) config setting is enabled.
- [UserSessionService](http://buildwithcraft.com/classreference/services/UserSessionService) will not extend the user session if a `dontExtendSession` param exists on the request, either in the query string or POST data.
- [UserSessionService::getReturnUrl()](http://buildwithcraft.com/classreference/services/UserSessionService#getReturnUrl-detail) will now return `null` if no default URL was passed into it, rather than the site/CP’s root URL.
- [UserSessionService::saveCookie()](http://buildwithcraft.com/classreference/services/UserSessionService#saveCookie-detail) now returns the cookie it just created.
- [TasksService::runTask()](http://buildwithcraft.com/classreference/services/TasksService#runTask-detail) will now turn the current request into a task runner once it has closed the connection with the browser, if there isn’t an active task runner already.
- `AssetsService`’s [onSaveAsset](http://buildwithcraft.com/classreference/services/AssetsService#onSaveAsset-detail) event now sets an `isNewAsset` param on the event object for any event handlers.
- The [onSaveAsset](http://buildwithcraft.com/classreference/services/AssetsService#onSaveAsset-detail), [onSaveCategory](http://buildwithcraft.com/classreference/services/CategoriesService#onSaveCategory-detail), [onSaveEntry](http://buildwithcraft.com/classreference/services/EntriesService#onSaveEntry-detail), [onSaveTag](http://buildwithcraft.com/classreference/services/TagsService#onSaveTag-detail), and [onSaveUser](http://buildwithcraft.com/classreference/services/UsersService#onSaveUser-detail) events now get after the `try/catch` block has ended, so if an exception gets thrown by one of the event handlers, the asset/category/entry/tag/usesr will still be saved.
- The `users/logout` controller action can now be called over Ajax.
- Added support for a `maxlength` variable in the CP’s `_includes/forms/textarea.html` template.
- You no longer need to add `="1"` when adding the `data-saveshortcut` and `data-confirm-unload` attributes to CP forms.
- The upload icon can now be shown using the ligature “upload” rather than “↑”, and added a similar “download” icon.
- Removed the possibility of field handles conflicting with other element attributes and causing unexpected behavior within the CP.
- Updated Guzzle to 3.9.2.
- Updated Imagine to 0.6.1.
- Updated PHPUnit to 4.2.2.
- Updated PHPUnit-Selenium to 1.4.1.
- Updated Twig to 1.16.0.
- Updated jQuery to 2.1.1.
- Updated jQuery File Upload to 5.41.0.
- Updated jQuery Placeholder to 2.0.8.
- Updated jQuery Timepicker to 1.4.6.
- Updated jQuery UI to 1.11.1.
- Updated Redactor to 9.2.6.

### Deprecated
- [HttpRequestService::getMimeType()](http://buildwithcraft.com/classreference/services/HttpRequestService#getMimeType-detail) is now deprecated. Use [HeaderHelper::getMimeType()](http://buildwithcraft.com/classreference/helpers/HeaderHelper#getMimeType-detail) instead.
- [IOHelper::getDefaultFolderPermissions()](http://buildwithcraft.com/classreference/helpers/IOHelper#getDefaultFolderPermissions-detail) is now deprecated. Use `craft()->config->get('defaultFolderPermissions')` instead.
- [IOHelper::getWritableFilePermissions()](http://buildwithcraft.com/classreference/helpers/IOHelper#getWritableFilePermissions-detail) is now deprecated. Use `craft()->config->get('defaultFilePermissions')` instead.
- [IOHelper::getWritableFolderPermissions()](http://buildwithcraft.com/classreference/helpers/IOHelper#getWritableFolderPermissions-detail) is now deprecated. Use `craft()->config->get('defaultFolderPermissions')` instead.

### Fixed
- Fixed a bug where Quick Post widgets would only show the first entry type, regardless of what the Entry Type setting was set to.
- Fixed a bug where searching for “field:term1 OR field:term2” would return results where *any* field had a matching value.
- Fixed a bug where site logos and user photos with spaces in their filenames would result in broken images in the CP.
- Fixed a bug where user photo and site logo uploading was broken for small images.
- Fixed a MySQL error that would occur if more than 128 elements were selected in a relational field.
- Fixed a bug where front-end /login, /logout, and /setpassword requests would be treated as Login, Logout, and Set Password requests even if the [loginPath](http://buildwithcraft.com/docs/config-settings#loginPath), [logoutPath](http://buildwithcraft.com/docs/config-settings#logoutPath), and [setPasswordPath](http://buildwithcraft.com/docs/config-settings#setPasswordPath) config settings had changed.
- Fixed a bug where front-end Login page requests were redirecting users with CP access to the CP Dashboard if they were already logged in, rather than the site’s homepage.
- Fixed a bug where users that logged in with “Remember Me” checked would get a basically-pointless “rememberMe” cookie that would want to stick around for the next several decades if we had let it.
- Fixed a bug where user identity cookies were not retaining their HTTP-only status.
- Fixed a bug where the “Username or Email” input would be auto-focused after clicking “Forget your password?” on the CP’s Login page when using a mobile device.
- Fixed a bug where browsers would still get user session cookies after they had been logged out due to [requireMatchingUserAgentForSession](http://buildwithcraft.com/docs/config-settings#requireMatchingUserAgentForSession) config setting enforcement.
- Fixed a bug where existing users who changed their email address would get the wrong verification email message.
- Fixed a bug where past versions of Singles weren’t showing their version notes.
- Fixed a PHP error that could occur if an exception was thrown during a resource request, and the error template was relying on plugin-supplied features.
- Fixed a bug where images within Rich Text fields’ bodies were not draggable on pages with an Assets field or where an asset selection modal had been opened (such as the one Rich Text fields’ “Choose image” menu option fires up).
- Fixed a bug where the progress bar in the Update Asset Indexes tool would get progressively slower and less helpful as time went on.
- Fixed a bug where background task progress icons were not getting updated with the latest progress info unless they were clicked on.
- Fixed a bug where new background tasks created over Ajax were not getting progress icons if another task had already run and completed on the same CP page.
- Fixed a bug where disabled lightswitch inputs looked enabled.
- Fixed a bug where the `search` parameter wouldn’t be factored in when calling an `ElementCriteriaModel` object’s [total()](http://buildwithcraft.com/docs/templating/elementcriteriamodel#total) method.
- Fixed a bug where .html and .htm templates were automatically getting injected with any HTML that was queued up for [getHeadHtml()](http://buildwithcraft.com/classreference/services/TemplatesService#getHeadHtml-detail) or [getFootHtml()](http://buildwithcraft.com/classreference/services/TemplatesService#getFootHtml-detail), even if the template had specified a content type other than “text/html” or “application/xhtml+xml” using the [{% header %}](http://buildwithcraft.com/docs/templating/header) tag.
- Fixed a bug where users would get locked out of their accounts one failed login earlier than the “max” in “[maxInvalidLogins](http://buildwithcraft.com/docs/config-settings#maxInvalidLogins)” would imply.
- Fixed a bug where existing user sessions would not be affected by changes to the [userSessionDuration](http://buildwithcraft.com/docs/config-settings#userSessionDuration) and [rememberedUserSessionDuration](http://buildwithcraft.com/docs/config-settings#rememberedUserSessionDuration) config settings.
- Fixed several places in Craft’s code that were assuming there would only ever be One True [DbConnection](http://buildwithcraft.com/classreference/etc/db/DbConnection) instance (located at `craft()->db`).
- Fixed a PHP error that could occur if an unknown extension was passed to [HeaderHelper::setContentTypeByExtension()](http://buildwithcraft.com/classreference/helpers/HeaderHelper#setContentTypeByExtension-detail).
- Fixed a bug where field types’ `prepSettings()` method could be called with already-prepped settings passed in.
- Fixed a bug where [ConfigService::exists()](http://buildwithcraft.com/classreference/services/ConfigService#exists-detail) was returning `false` if the config setting did exist, but was set to `null`.
- Fixed a bug where [HttpRequestService::close()](http://buildwithcraft.com/classreference/services/HttpRequestService#close-detail) wasn’t accounting for the possibility that there were multiple active output butters.
- Fixed a bug where [HttpRequestService::close()](http://buildwithcraft.com/classreference/services/HttpRequestService#close-detail) wasn’t accounting for the possibility that the active output buffer(s) may have been initialized without the `PHP_OUTPUT_HANDLER_CLEANABLE` flag.
- Fixed a PHP error that could occur if [HttpRequestService::close()](http://buildwithcraft.com/classreference/services/HttpRequestService#close-detail) was called after headers had already been sent.
- Fixed a PHP error that could occur if there were multiple components with the same class name, which is possible if a plugin’s handle conflicts with built-in components.
- Fixed a PHP error that could occur when calling [ArrayHelper::getFirstValue()](http://buildwithcraft.com/classreference/helpers/ArrayHelper#getFirstValue-detail) because it wasn’t static.

## 2.1.2570 - 2014-08-26

### Changed
- Prevented redundant DB queries when calling [UserModel::getGroups()](http://buildwithcraft.com/classreference/models/UserModel#getGroups-detail) multiple times on the same `UserModel` instance.

### Fixed
- Fixed a bug where asset indexing was broken for non-image files.
- Fixed a bug where asset indexing session data would not be deleted if only one session was in progress.
- Fixed a bug where setting PHP’s `memory_limit` setting to `-1` would break asset uploading.
- Fixed a bug where the “Users” breadcrumb would show on the My Account page for users that didn’t actually have permission to edit other users.
- Fixed a PHP error that would occur when [FeedsService::getFeedItems()](http://buildwithcraft.com/classreference/services/FeedsService#getFeedItems-detail) would encounter a feed that SimplePie couldn’t parse.

## 2.1.2569 - 2014-08-18

### Fixed
- Fixed a bug where setting the imageDriver config setting to `'gd'` would not work.
- Fixed a Javascript error that could occur if size values in php.ini were specified with lowercase units (e.g. `memory_limit = 128m`).
- Fixed a bug where `MatrixService::getBlockTypesByFieldId()` would return stale information if it was called on the same request that a new block type was created in.
- Fixed a bug that broke Asset uploading on front end forms.

## 2.1.2568 - 2014-08-14

### Changed
- Drastically improved the DocBlock comments throughout all of Craft’s PHP files.
- `AssetsService::insertFileByLocalPath()` now takes an optional argument defining how the function should deal with filename conflicts.
- Added the `AssetConflictResolution` enum class, replacing some constants in `AssetsHelper`.

### Fixed
- Fixed a bug where Assets fields were not disabling the “Add an asset” buttons after they had reached their limit via drag-n-drop file uploading.
- Fixed a bug where the delete buttons in Settings → Fields were not clickable in Safari from time to time.
- Fixed a JS error that occurred in Firefox when there were multiple Rich Text fields on a page using the `iframe` and `css` Redactor settings, breaking all but the first Rich Text field.
- Fixed a bug where some assets might never be moved out of their temporary upload location.
- Fixed a PHP error that would occur when installing Craft if the Multibyte String extension was not installed.
- Fixed a bug where some fields’ values would not save correctly on Matrix blocks within entry drafts.
- Fixed a bug where some requests were treated as paginated requests, when they totally should not have been.
- Fixed a bug where user groups weren’t being ordered alphabetically.

## 2.1.2566 - 2014-07-23

### Added
- Added a new `useWriteFileLock` config setting, which can be set to `false` on servers running NFS, fixing a PHP error that could occur.

### Changed
- Section names now get translated in the Recent Entries widget.
- User custom field validation now occurs at the same time as the regular fields, so all validation errors will be set at the same time.
- Lightswitch inputs in the Control Panel now support `toggle` and `reverseToggle` field-toggling params, like checkboxes and selects.

### Fixed
- Fixed a “Division by zero” PHP warning that could occur when using the `search` element param in some cases.
- Fixed a couple bugs that broke drag-n-drop uploading to Assets fields in certain conditions.
- Fixed an error that could occur when a file was uploaded from the front end that conflicted with another file.
- Fixed a bug that prevented transforms from being created for assets in temporary sources.
- Fixed a bug where Craft update release notes were displayed in RTL in Arabic, even though they’re in English.
- Fixed a Twig error that would occur if there was a comment tag between a `{% switch %}` tag and its first `{% case %}` sub-tag.
- Fixed a Twig error that would occur if a non-DateTime value was passed to the `_includes/time.html` template.
- Fixed a bug where elements in the Control Panel could have multiple editing modals attached to them at the same time.
- Fixed a bug where `{% cache %}` tags were caching templates that included transform-generation URLs.
- Fixed a bug where `TemplatesService::namespaceInputs()` was not accounting for attributes that started with either `#` or `.`.
- Fixed a bug where HTML entities were getting double-encoded in the page `<title>` on the Edit Entry page.

## 2.1.2564 - 2014-07-15

### Added
- Added support for the `cy_gb` locale.

### Changed
- `{% requireLogin %}` and `craft()->userSession->requireLogin()` now throw an exception if called from the actual Login page, rather than creating an infinite redirect loop.

### Fixed
- Fixed a couple bugs that occurred when working with entries in a Structure section that was not enabled for the current locale.
- Fixed a bug where `craft.users` would return no results if the `can` param was set to a permission that hadn’t been assigned before, rather than returning a list of the admins.
- Fixed a bug where HTML entities within entry titles were not being encoded within the page title on the Edit Entry page.
- Fixed a bug where the “Delete Stale Template Caches” task was not actually deleting stale template caches.
- Fixed a bug where drafts and versions would not remember Entry Type changes.
- Fixed a bug where Live Preview wasn’t showing changes to the Entry Type, Slug, Author, Post Date, Expiration Date, and Status settings.
- Fixed a Twig error that could occur if a template had a variable named “nav” defined before using a `{% nav %}` tag.
- Worked around a bug in legacy versions of IE affecting `craft()->request->sendFile()` over SSL.

## 2.1.2563 - 2014-07-07

### Added
- Added `StringHelper::getCharAt()`.
- Added `IOHelper::getLastModifiedFiles()`.

### Changed
- Yii’s Arabic translations are now included.

### Fixed
- Fixed a bug where relational fields were returning search keywords for the previously-selected elements rather than the new ones.
- Fixed a bug where it was not possible to assign permissions directly to a user which were nested under other permissions the user already had via a group they were assigned to.
- Fixed a bug where the Quick Post widget would show the Title field for entry types that had “Show title fields” setting unchecked.
- Fixed a bug where the Get Help widget was not always sending the latest database backups with new support tickets.
- Fixed a bug with the search query parser when the search term contained multibyte characters.
- Fixed a PHP exception that could occur when updating Craft if a plugin was installed that added its own Twig extension.

## 2.1.2562 - 2014-07-01

### Changed
- Added `js` to the default [allowedFileExtensions](https://buildwithcraft.com/docs/config-settings#allowedFileExtensions) config setting value.
- `craft.request.getQuery()` and `craft.request.getPost()` can now be used to return the entire GET and POST param arrays, if a param name is not passed as the first argument.
- Improved the performance of `ResaveElements` tasks.
- Craft now shows a confirmation dialog when leaving a Global Edit page with unsaved changes.
- Disabled the “Clear” buttons on text fields and the “Reveal” buttons on password fields, in Internet Explorer.
- “sortOrder” is now a reserved field handle.

### Fixed
- Fixed a bug where it was not possible to upload files to newly-created asset subfolders.
- Fixed a bug where it was possible to select image transforms on non-images when linking to assets in Rich Text fields.
- Fixed a bug where it was not possible to upload files to Assets fields within Matrix fields from a front-end form.
- Fixed a PHP error that occurred on sites where `craft/config/general.php` did not return an array.
- Fixed a PHP notice that could occur with some versions of PHP.
- Fixed a SQL error that could occur on sites with a custom field named “asc” or “desc”.
- Fixed a bug where entries may have not been updated with new URIs after updating a section’s settings, if the section wasn’t enabled for the current locale.
- Fixed a race condition that could have resulted in multiple requests trying to clean up expired template caches at the same time.
- Fixed a bug where pagination links weren’t taking the `offset` parameter into account.
- Fixed a bug where HTML code within element labels was not getting properly encoded in the CP.
- Fixed a couple UI glitches on field layout designers.

## 2.1.2561 - 2014-06-19

### Changed
- Users with permission to edit entries in a section, but not create new ones, are now allowed to be selected as the author of entries.

### Fixed
- Fixed a bug where Craft could incorrectly determine the amount of bytes available from PHP’s `memory_limit` setting if it was set to a size in gigabytes.
- Fixed a PHP that could occur when an admin attempted to log in as a pending user.
- Fixed a bug where you could drag top-level asset sources.
- Fixed a bug where you could drag an asset folder onto itself or any of its own children.
- Fixed a PHP error that could occur when uploading certain types of PNG files.
- Fixed a PHP error that could occur when the `craft/storage/runtime/` folder existed but was completely empty.
- Fixed a PHP error that could occur on fresh installs of Craft on the very first request.
- Fixed a PHP error that could occur when trying to fetch a config setting for a plugin that didn’t provide a default config file.
- Fixed a bug where passing `true` as a second argument to `NumberFormatter::formatCurrency()` or the `|currency` filter would not work as intended with some numbers.
- Fixed a bug where uploading files to an Assets field via drag-n-drop would not take the field’s Limit setting into account.
- Fixed a couple UI glitches with CP grids.
- Fixed a MySQL error that could occur when ordering elements by a MySQL function that included custom field names.

## 2.1.2559 - 2014-06-09

### Changed
- Setting the `id` criteria parameter to any empty value besides `null` will now return 0 results.
- Added support for the `offset` parameter in conjunction with the `{% paginate %}` tag.
- Invalid asset source path errors now include the invalid path.
- Improved the Javascript performance on most CP pages.

### Fixed
- Fixed a bug where asset subfolders weren’t getting indexed on Windows servers.
- Fixed a bug where it was not possible to move assets between folders on Rackspace sources.
- Fixed a bug where it was not possible for non-admins to upload to Assets fields that were restricted to a single upload location in some cases.
- Fixed a bug where Assets fields weren’t always respecting the “Default Upload Location” setting.
- Fixed a JS error that occurred when selecting images from a Rich Text field.
- Fixed a bug where user photos could get uploaded to the wrong place if the `useEmailAsUsername` config setting was set to true.
- Fixed a bug where some entries could always show a confirmation dialog before leaving the page, even if there weren’t any unsaved changes.
- Fixed some unexpected behavior with CP grids.
- Fixed some minor styling issues.

## 2.1.2557 - 2014-06-04

### Changed
- Plugin classes are now imported before their `getSettingsModel()` function is called, making it possible for plugins to provide their own settings models without manually importing them.

### Fixed
- Fixed a bug where asset content wasn’t saving.
- Fixed a PHP error that occurred when publishing a draft in a section that wasn’t enabled for the current application locale.
- Fixed a PHP error that occurred when reverting a past entry version in a section that wasn’t enabled for the current application locale.
- Fixed a bug where Matrix fields’ menus wouldn’t expand when clicked if the Matrix field was loaded dynamically after switching the Entry Type.
- Fixed a bug where Rich Text fields would not retain their entry/asset reference tags when re-edited.
- Fixed a couple minor CSS glitches.

## 2.1.2556 - 2014-06-03

### Fixed
- Fixed a bug that broke entry type switching when Dev Mode was enabled.

## 2.1.2555 - 2014-06-03

### Fixed
- Fixed a bug that caused an unknown validation error when attempting to save Assets fields.
- Corrected some of the CP’s Norwegian translations.
- Fixed a bug where Craft would leave some user data behind when purging stale users.

## 2.1.2554 - 2014-06-03

### Added
- Admins can now manually activate pending users.
- Craft will now automatically purge users that have been in a pending state for a certain amount of time, determined by the new [purgePendingUsersDuration](http://buildwithcraft.com/docs/config-settings#purgePendingUsersDuration) config setting.
- Added an [isSystemOn](http://buildwithcraft.com/docs/config-settings#isSystemOn) config setting that takes precedence over whatever is set in General Settings.
- Added a [baseCpUrl](http://buildwithcraft.com/docs/config-settings#baseCpUrl) config setting, which can be used to explicitly set the base CP URL if the dynamically-determined URL is not desired for some reason.
- Added a [useEmailAsUsername](http://buildwithcraft.com/docs/config-settings#useEmailAsUsername) config setting, which removes “Username” fields and keeps users’ usernames set to whatever their email is set to.
- Added an [autoLoginAfterAccountActivation](http://buildwithcraft.com/docs/config-settings#autoLoginAfterAccountActivation) config setting, which will automatically log users in after activating their account when set to `true` (default is `false`).
- Added an [allowUppercaseInSlug](http://buildwithcraft.com/docs/config-settings#allowUppercaseInSlug) config setting, which can be set to `true` to allow uppercase letters in slugs (default is `false`).
- Added a [defaultTokenDuration](http://buildwithcraft.com/docs/config-settings#defaultTokenDuration) config setting, for specifying how long tokens should be available by default.
- Added a [tokenParam](http://buildwithcraft.com/docs/config-settings#tokenParam) config setting, for specifying the query string parameter name that tokens should be set to.
- Added a “Clear” button to search bars on element indexes, for quickly clearing the search query.
- Added a “Share” button next to entries’ Live Preview button, which opens the current entry/draft/version on the front end, with a tokened URL that can be shared with others without requiring them to be logged in.
- It is now possible to immediately save a new entry as a draft.
- It is now possible to customize Draft names, and give them notes.
- It is now possible to provide change notes when saving an entry.
- It is now possible to revert an entry to one of its past versions.
- Entry versioning can now be enabled/disabled on a per-section basis.
- Added a [maxUploadFileSize](http://buildwithcraft.com/docs/config-settings#maxUploadFileSize) config setting, which defaults to 16MB.
- Added a [generateTransformsBeforePageLoad](http://buildwithcraft.com/docs/config-settings#generateTransformsBeforePageLoad) config setting, which forces asset transforms to be generated immediately when `getUrl()` is called, rather than during the actual image request.
- Added an [imageDriver](http://buildwithcraft.com/docs/config-settings#imageDriver) config setting, which will force Craft to use a particular image manipulation library if set to `gd` or `imagick`.
- Added support for a `title` parameter when querying for [entries](https://buildwithcraft.com/docs/templating/craft.entries#title), [assets](https://buildwithcraft.com/docs/templating/craft.assets#title), and [categories](https://buildwithcraft.com/docs/templating/craft.categories#title).
- Added support for a `lastLoginDate` parameter when querying for users.
- Added `hasDescendants()` to [EntryModel](https://buildwithcraft.com/docs/templating/entrymodel#hasDescendants) and [CategoryModel](https://buildwithcraft.com/docs/templating/categorymodel#hasDescendants).
- Added [craft.globals.getSetByHandle()](https://buildwithcraft.com/docs/templating/craft.globals#getSetByHandle).
- [craft.globals.getSetById()](https://buildwithcraft.com/docs/templating/craft.globals#getSetById) now supports a second argument for specifying the locale.
- Added `craft.request.getHostInfo()`.
- Added `craft.request.getScriptUrl()`.
- Added `craft.request.getPathInfo()`.
- Added `craft.request.getRequestUri()`.
- Added `craft.request.getServerPort()`.
- Added `craft.request.getUrlReferrer()`.
- Added `craft.request.getUserAgent()`.
- Added `craft.request.getUserHostAddress()`.
- Added `craft.request.getUserHost()`.
- Added `craft.request.getPort()`.
- Added `TokensService`, providing an API for creating time-limited tokens, which map to a given route.
- Added `BaseController::requireToken()` for requiring that the current request has a token in it.
- Added [HttpRequestService::getToken()](https://buildwithcraft.com/docs/apis/httprequestservice#getToken) for fetching the current request’s token, if there is one.
- Added `UrlHelper::getUrlWithToken()` for getting a URL with a given token in its query string.
- Added `UrlHelper::getUrlWithParams()` for returning a URL with additional query string parameters.
- Added `UrlHelper::isAbsoluteUrl()` for determining if a URL is absolute (starts with `http://` or `https://`).
- Added `UrlHelper::isProtocolRelativeUrl()` for determining if a URL is protocol-relative (starts with `//`).
- Added `UrlHelper::isRootRelativeUrl()` for determining if a URL is root-relative (starts with `/`).
- Added `UrlHelper::isFullUrl()` for determining if a URL is either absolute or protocol-relative.
- Added `SectionsService::isSectionTemplateValid()`.
- Added `GlobalsService::getSetByHandle()`.
- Added a [registerSiteRoutes](https://buildwithcraft.com/docs/plugins/hooks-reference#registerSiteRoutes) hook.
- Added a `getSettingsUrl()` method to plugins, so they can provide an alternate location for their settings.
- Added an `onBeforeInstall()` method to plugins so they have a chance to run pre-installation logic.
- Field types can now provide a `getStaticHtml()` method, customizing what their field should look like when viewing a static entry version.
- It is now possible to create a field-less field type; fields that don’t have instructions and don’t have any input HTML will be omitted from edit pages.
- CP forms can now include `data-confirm-unload="1"`, which will show a confirmation dialog if the user tries to leave the form with unsaved changes.
- It is now possible to provide custom instructional text when including the Field Layout Designer in a template.
- The `_layouts/cp.html` template now supports a `docTitle` variable, which can be used to set the document title and page heading separately.

### Changed
- Made several subtle CP UI improvements.
- Matrix blocks now have alternating colored backgrounds.
- The selected locale is now remembered across page loads on the Entries and Assets index pages.
- Global sets without any custom fields will now show a notice rather than an empty form.
- Translations for month names, weekday names, and AM/PM are now automatically available for `Craft::t()` and the `|t` filter.
- Expired user activation links will now display a form for sending a new activation email, rather than an error message.
- Users who are suspended are now immediately locked out of the system.
- The profiling information in the browser console now shows the total query count for the current request, if Dev Mode is enabled.
- `HttpException`s are now log as warnings, not errors, in `craft/storage/runtime/logs/craft.log`.
- If you upload a version of Craft that is incompatible with the installed schema’s version, the error message now includes a link to download a compatible version of Craft.
- Craft no longer backs up the database when updating a plugin if the plugin’s update doesn’t include any database changes.
- Errors that came from external asset sources are now logged in `craft/storage/runtime/logs/`.
- Subfolders are no longer visible in Assets fields that have been restricted to a single folder.
- Users will now get prompted when leaving an Edit Entry page with unsaved changes.
- Drafts’ default names now increment based on the number of existing drafts for the current entry and locale.
- Version numbers now increment based on the number of existing versions for the current entry and locale.
- Versions now show the date they were created in the entry revision menu.
- Versions no longer have strangely-editable fields.
- Entry action buttons have been moved to the right sidebar.
- The “Save as a Draft” button has been moved into the Save button’s context menu.
- The “Delete” button has been moved to the right of the Status Lightswitch input.
- Asset source permissions are no longer factored into Assets fields.
- Assets’ filenames can now be renamed from the same modal that their title and content is edited in.
- Asset folders now show settings icons on hover, which open the folder context menu when clicked on.
- It is no longer necessary to include a `|raw` filter when outputting an image transform URL within `<style>` tags.
- The `|date` filter now automatically translates month names, weekday names, and AM/PM for the current locale.
- `UrlHelper::getUrl()` now applies the `$params` and `$protocol` arguments to full URLs, if they’re set.
- `TemplatesService::findTemplate()` now returns `false` in the event that it can’t find the template, rather than throwing a `TemplateLoaderException`.
- The onSaveAsset, onSaveCategory, onSaveEntry, onSaveTag, and onSaveUser events now get fired after all DB changes associated with the action have been committed.
- `EntryDraftModel` and `EntryVersionModel`s’ `getUrl()` functions now actually return a URL.
- “Mixed” record attribute values now only get JSON-decoded if they look like they were JSON-encoded to begin with.
- Plugin settings are now run through validation.
- Lightswitch container divs now fire a `change` event when the Lightswitch value changes.
- Update PHPMailer to 5.2.8.
- Updated Guzzle to 3.9.1.
- Updated PHPUnit to 4.1.1.
- Updated Mockery to 0.9.1.
- Updated DBUnit to 1.3.1.

### Fixed
- Fixed some styling glitches and other UI issues.
- Fixed a bug where Assets fields created before Craft 2.0 could have stopped working until re-saved.
- `EntryRevisionsService::getDraftByOffset()` and `getVersionByOffset()` are now deprecated, as they should have been long, long ago.

### Security
- Assets’ filenames can now be made up of almost any characters, except those which are known to cause issues on file systems or pose security risks.

## 2.0.2551 - 2014-06-01

### Changed
- Craft now respects the `defaultTemplateExtensions` and `indexTemplateFilenames` config settings when rendering the `offline` template.

### Fixed
- Fixed a PHP error that would occur when generating a transform using the Scale-to-Fit mode

## 2.0.2549 - 2014-05-30

### Fixed
- Fixed a couple bugs that could prevent asset selection modals from displaying anything in some cases.
- Fixed a CSS glitch.

## 2.0.2548 - 2014-05-29

### Fixed
- Fixed a bug that could break edit pages that contained Assets fields when no Assets sources existed.
- Fixed an error that could occur when saving a Matrix field with a long block type handle or field handle.
- Fixed a bug where users that didn’t have permission to edit other authors’ entries within a section were still able to do so through Entries fields.
- Fixed a bug where all template caches would get cleared every 24 hours, regardless of their duration.
- Fixed a bug where long site names would push the CP header navigation out of view on smaller screens.
- Fixed a bug where Matrix “add” buttons weren’t getting collapsed into a menu button when there wasn’t enough room to show them all.
- Fixed a bug where test emails could be sent with the subject `test_email_heading` in some cases.
- Fixed some minor CSS issues.

## 2.0.2543 - 2014-05-23

### Changed
- Made major performance improvements to the Users index, Edit Entry pages, and any templates using the `can`, `group`, and `groupId` params when fetching users, for sites with hundreds of thousands of users.
- The “Delete” buttons next to each selected element in Entries, Assets, Tags, and Users fields no longer no longer serve as part of the drag handle.

### Fixed
- Fixed a bug where Assets fields that were restricted to certain file types would only allow the first file type to save.
- Fixed a bug where the entry revision dropdown would show revisions in chronological order rather than reverse-chronological order.
- Fixed a bug where the current version of an entry would masquerade as a previous version in the entry revision dropdown.
- Fixed a bug where Rich Text fields were not maintaining the correct locale when opening their entry and asset selection modals.
- Fixed a bug where search result scoring would happen after the results had already been limited.
- Fixed some CSS glitches here and there.

## 2.0.2542 - 2014-05-20

### Added
- Added `ArrayHelper::getFirstValue()`, for getting the first value in an array.

### Changed
- The PHP session ID cookie is now HTTP-only for better PCI compliance.
- Made performance improvements to the “DeleteStaleTemplateCaches” task, fixing a possible MySQL deadlock error.

### Fixed
- Fixed a bug where it was possible to register a new user account on the front end without setting a password.
- Fixed a bug where asset indexing would skip folders that had commas in their name.
- Fixed a bug where the test email template was listing all the email settings in a single paragraph.
- Fixed a PHP error in `DbHelper::parseParam()` that could occur if `$values` was an associative array.

## 2.0.2541 - 2014-05-15

### Added
- Singles once again have permissions for publishing live changes and editing/publishing/deleting other authors’ drafts.

### Changed
- The Entries index and Recent Entries widget no longer show entries that the user doesn’t have permission to edit due to the “Edit other authors’ entries” permission.
- It is now possible to save an Assets field from a front-end form with both existing asset IDs and new file uploads at the same time.
- `TemplatesService::namespaceInputs()` now namespaces list= attributes.

### Fixed
- Fixed a bug where element selector modals wouldn’t lazy-load any additional elements if the modal had a horizontal scrollbar.
- Fixed a bug where test emails would get sent out with incorrect subject and body content when Craft was not running in the `en_us` locale.
- Fixed a bug where Arabic tab labels could get unexpected line breaks.
- Fixed a bug that broke Rich Text fields when Craft was running a Norwegian locale.
- Fixed a bug where Single entry drafts could forget their titles in the Edit page.
- Fixed a bug where users could get an authentication error when saving a Single.

## 2.0.2540 - 2014-05-13

### Changed
- It is now possible to get the *Nth*-last URL segment by passing a negative number to [craft.request.getSegment()](http://buildwithcraft.com/docs/templating/craft.request#getSegment). (`-2` will return the second-to-last URL segment, for example.)
- `ArrayHelper::stringToArray()` will no longer split strings on escaped commas, e.g. `"foo\, bar"`.
- It is now possible to get non-resized user photo URLs by passing `"original"` into [getPhotoUrl()](http://buildwithcraft.com/docs/templating/usermodel#getPhotoUrl).
- Added .ogv to the [default list](http://buildwithcraft.com/docs/config-settings#allowedFileExtensions) of allowed file extensions.
- You can now pass `0` to `ElementCriteriaModel`s’ `id` params to force zero results (in addition to `false`.)
- Updated Redactor’s translation files, and included new translation files for Arabic and Norwegian.
- Section titles, Global Set names, Category Group names, Table field labels, and field option labels are now translatable in several areas of the CP.

### Fixed
- Fixed some bugs in the image cropping modals for user photos and login page logos.
- Fixed a bug where non-admins were not allowed to publish edits to Singles.
- Fixed a bug where Checkboxes, Dropdown, Multi-select, and Radio Buttons fields weren’t selecting the default option(s) on new elements
- Fixed an infinite loop that could occur when Craft was trying to determine which language to use for the current request.
- Fixed a PHP error that could occur when a user attempted to delete an entry in a section that was not activated for their preferred locale.
- Fixed a bug where element index pages would always initially show elements in the user’s preferred locale, even if the user doesn’t have permission to edit elements in that locale.
- Fixed a bug where the Recent Entries widget would show entries in the user’s preferred locale, even if the user doesn’t have permission to edit elements in that locale.
- Fixed a bug where the Copyright text in the CP footer would get a line break in Arabic.
- Fixed a bug where Rich Text fields weren’t showing bullets for unordered lists in Arabic.
- Fixed a bug where some strings in the CP were not getting translated.
- Fixed various CSS glitches throughout the CP.

## 2.0.2539 - 2014-05-08

### Changed
- Number fields now default to the minimum allowed value if `0` is outside of the allowed range.

### Fixed
- Fixed a bug where users couldn’t edit Singles created by other users.
- Fixed a bug where Craft Client was not saving entry versions.
- Fixed a bug where Matrix blocks were not showing up on the front end for entry locales that weren’t set to be enabled by default.
- Fixed a bug where setting the [cacheDuration](http://buildwithcraft.com/docs/config-settings#cacheDuration) config setting `0` would disable data caching rather than cache the data indefinitely (like the docs say it will).
- Fixed a bug that prevented `BaseElementType::getSource()` from finding nested sources.
- Fixed a PHP warning that could occur when calling `IOHelper::clearFolder()` if the folder contents could not be read.

## 2.0.2538 - 2014-05-01

### Changed
- The link to Redactor’s settings now opens in a new window.

### Fixed
- Fixed a bug where new (non-Single) entry permissions weren’t getting saved correctly.
- Fixed a PHP warning that could occur when rendering templates in rare conditions.
- Fixed a PHP Warning that could occur when running yiic from the command line in some environments.
- Fixed a bug where the Login button wouldn’t become clickable if you entered your username/password with Chrome’s autofill feature.

## 2.0.2537 - 2014-04-30

### Added
- Added `ElementsService::getElementByUri()` for fetching an element by a given URI.

### Changed
- All singles’ permissions are now grouped together in the permission lists, and no longer have any permissions besides “Edit”, since that’s the only entry permission that makes sense for singles.
- Color fields now default to #000000, fixing an inconsistency between Chrome (which supports HTML5 color inputs) and every other browser.
- Added “webm” to the `video` asset file kind extension list.

### Fixed
- Fixed a bug where it wasn’t possible to publish drafts on a Single.
- Fixed a bug that broke prefixing `relatedTo` param values with “and” in some cases.
- Fixed an error with Rackspace-hosted image transforms.
- Fixed a bug where nested structure entries could use the wrong parent locale when generating new URIs.
- Fixed a bug where Craft would look across all locales when trying to resolve a request URL to an element, rather than just the current app locale.
- Fixed a bug where it was not possible to save a new entry for a different locale without saving it for the current app locale first.
- Fixed a couple 404 errors that would occur when working with entry drafts in a different locale than the current app locale.
- Fixed a fatal PHP error that could occur on a crazy edge case involving resetting a user’s password.
- Fixed a bug where users who registered on the front-end would be able to skip email/password verification, even if the “Verify email addresses?” user setting was checked.
- Fixed a bug where menus within modals could show up behind the modal in some cases.
- Fixed a bug where checkbox labels weren’t toggling their checkboxes when clicked in Firefox.
- Fixed a bug where the German language was getting a few Arabic translation.
- Made some CP strings translatable that weren’t.

## 2.0.2536 - 2014-04-18

### Added
- Added caching options for Google Cloud Storage sources.

### Changed
- Lightened the shadow surrounding the Update button on the Updates page.
- `IOHelper::getMimeType()` can now handle file paths that don’t map to a real file on the server, by looking at the extension instead.

### Fixed
- Fixed a Twig error that occurred when editing an element that had a Categories field set to a deleted category group.
- Fixed a bug in Craft Pro where users without permission to edit users were getting redirected to the user index after editing their own account settings.
- Fixed a bug in Craft Pro where users without permission to edit users were still able to access the user index.
- Fixed a bug where browsers could still load old cached assets if they lived in a remote source with caching enabled, and the asset had been changed more than once in the same day.
- Fixed a PHP error that would occur when calling `AssetFileModel::getMimeType()`.
- Fixed a couple Redis cache connection bugs.

## 2.0.2535 - 2014-04-14

### Added
- Added `ElementsService::deleteElementsByType()`.
- Added `TasksService::getPendingTasks()`.
- Added an optional `$type` param to `TasksService::areTasksPending()` and `getNextPendingTask()`.

### Changed
- The upgrade promo is now only shown on the Settings page if you’re running Craft Client.
- Craft no longer creates additional Delete Stale Template Caches tasks if one is already pending, when saving and deleting elements.
- Craft no longer creates additional Resave All Elements tasks if one is already pending, when adding new site locales.
- The default error message templates will now show the actual error message, if one is provided.
- Updated `MigrationHelper::makeElemental()` for Craft 2, and gave it `$hasContent`, `$isLocalized`, and `$locales` arguments.

### Fixed
- Fixed a bug where the `{% redirect %}` tag would not immediately end the request.
- Fixed a bug where Matrix block type settings could get cut off.
- Fixed a PHP error that broke Resave (All) Elements tasks if there were any orphaned Matrix fields in the database (Part 2).
- Fixed a MySQL error that could occur if a ton of element IDs were passed into a `relatedTo` param.
- Fixed a JS error that would occur when selecting an image with a transform in a Rich Text field.
- Fixed a bug where the default text for a new field group’s name was “undefined”.
- Fixed a bug where uninstalling a plugin that has element types would leave its element’s rows around in the database.

## 2.0.2533 - 2014-04-11

### Fixed
- Fixed a very serious bug where an extra 14px of padding was getting added to the top of some elements in the CP.

## 2.0.2532 - 2014-04-11

### Added
- Added a `slug` route token.

### Changed
- The date-picker for CP date fields is now localized for the same locales the CP is translated into.
- The time-picker for CP time fields is now localized for the same locales the CP is translated into.
- Asset source names, category group names, section names, tag group names, and user group names in sidebar source lists can now be [translated](http://buildwithcraft.com/help/static-translations).
- Field names and instructions on element edit pages can now be [translated](http://buildwithcraft.com/help/static-translations).
- Global set names in Setting → Globals can now be [translated](http://buildwithcraft.com/help/static-translations).
- URLs, URIs, and paths are now always displayed as LTR, even for RTL languages.
- Craft no longer horizontally flips graphics for RTL languages. Dedicated RTL graphics are now being used where appropriate.
- Route tokens are now always displayed in English.
- The `>` icon now uses the “rightangle” ligature.
- The `go` icon now uses the “circlerarr” ligature.
- The `→` icon now uses the “rarr” ligature.
- The `←` icon now uses the “larr” ligature.
- Entry URI validation errors are now exposed below the Slug field.
- Cleaned up button styles throughout the CP.
- Improved credit card error handling in the upgrade modal.

### Fixed
- Fixed a PHP error that broke the Feed widget and `craft.feeds` in the templates on servers with `openbase_dir` restrictions in effect *and* the `idna_convert` class installed in the server’s path.
- Fixed a PHP error that could occur when editing Rackspace asset source settings.
- Fixed a PHP error that broke Resave (All) Elements tasks if there were any orphaned Matrix fields in the database.
- Fixed a PHP error that could occur in some user registration scenarios.
- Fixed a MySQL error that could occur when updating to Craft 2 with a database that already had a `taggroups` table.
- Fixed a bug where the Recent Entries widget wouldn’t show any entries on Craft Personal.
- Fixed a bug where subfolders were not accessible from asset selection modals, even if the field was not set to be restricted to a single folder.
- Fixed a bug where the `nb_no` locale was required to see the Norwegian CP translation, rather than just `nb`.
- Fixed a bug where many locales were getting 2-digit years in CP date fields, rather than 4-digit.
- Fixed several UI glitches throughout the CP for RTL languages.
- Added some missing translation strings.
- Fixed several UI glitches throughout the CP due to a change in Chrome 34.
- Fixed a CSS glitch on the loading state of the submit buttons in the installer.
- Fixed a bug where the Edit User page got a “Users” breadcrumb on Craft Personal and Craft Client, which would lead to a 404 if clicked on.
- Fixed a bug where clicking next to (but outside of) the upgrade promo in the CP footer would still open the upgrade modal.
- Fixed some references to Craft 1.x packages in the code.

## 2.0.2528 - 2014-04-07

### Changed
- Updated Craft’s CP translations to include all the new edition-related strings.

### Fixed
- Fixed a 404 error when loading entry drafts or versions on Craft Pro.
- Fixed a CSS glitch on the Client-to-Pro upgrade purchase screen.
- Fixed a bug where the user’s preferred locale wasn’t being respected in some cases.
- Fixed a couple timezone-related bugs when saving dates/times.
- Fixed a MySQL error about the `name` column being ambiguous when fetching tags with `craft.tags`.
- Fixed a bug where Live Preview would get a black bar between the two panes on Windows browsers.
- Fixed a z-index issue with Rich Text fields that have fixed toolbars.
- Fixed a bug where non-admins could still see the Get Help widget if they already had it selected.
- Re-disabled Twig’s C extension, which causes issues.

### Security
- Passwords can no longer be more than 160 characters, preventing a [potential DDOS attack vector](http://arstechnica.com/security/2013/09/long-passwords-are-good-but-too-much-length-can-be-bad-for-security/) with blowfish hashing.

## 2.0.2527 - 2014-04-04

### Changed
- “section” is now a reserved field handle.
- Lightened up the box shadow around submit buttons so they don’t look blurry.
- It is now possible to zoom in on the Control Panel from mobile devices.
- Added “`toolbarFixedBox: true`” to the default `Simple.json` and `Standard.json` Redactor configs in `craft/config/redactor/`. (Note this will only affect new installs.)

### Removed
- Removed any empty `action=""` attributes from `<form>`s, which it turns out is against HTML spec.

### Fixed
- Fixed a bug where saving an element on a non-primary locale would override the element’s search keywords for the primary locale.
- Fixed the Rebuild Search Indexes tool.
- Fixed a bug that broke dynamic subfolder paths in Assets fields set to upload to a single folder, for new installations of Craft.
- Fixed a Twig error that would occur if saving a user account with a validation error on Craft Personal or Craft Client.
- Fixed a bug where dropdown menus in Rich Text fields would get clipped if they extended beyond their containing pane.
- Fixed several z-index conflicts between Rich Text fields and everything else.
- Fixed a bug where unordered lists wouldn’t get bullets in Rich Text fields.
- Fixed a bug where the Redactor toolbar didn’t get a background or drop shadow when in “fixed” mode.
- Fixed a bug where Category Groups’ Max Levels settings be hidden if the “Categories in this group have their own URLs” setting was unchecked.
- Fixed a bug that broke category selection in Live Preview.
- Fixed a bug where parent categories that were automatically selected in a Categories field due to being ancestors of the actually-selected category would not become disabled in the modal window.
- Fixed a bug where Categories fields were displaying categories in the current application locale, rather than the field’s target locale.
- Fixed a bug where elements that were chosen but not actually selected in a modal window due to the field’s Limit setting would still get disabled in the modal.
- Fixed a bug where Structure entries and categories would still appear “active” after being selected and disabled in a modal window
- Fixed a bug where expanding an asset source’s folder could just give the sidebar a scrollbar, rather than increasing the size of the pane.
- Fixed a bug where some panes could have scrollbars for no good reason.
- Fixed a bug where manually updating Craft or a plugin while logged out and while the system was offline would make it impossible to complete the update, leaving you stuck at the “Finish Up” screen forever.

## 2.0.2525 - 2014-04-02

### Changed
- Craft is now a little more graceful when running its “Resaving all elements” task, if it encounters an entry that doesn’t have a title set.

### Fixed
- Fixed a MySQL error about a missing `craft_{{users}}` table that occurred when some people attempted to update to Craft 2.
- Fixed a bug where a migration might fail during an update if Craft was set to not use a database table prefix.
- Fixed a bug where the database rollback might not work after a failed Craft update if the `craft_info` table schema had changed during the update.
- Fixed a MySQL error about how the `craft_taggroups` table already exists that could occur if a Craft 2 update failed, and the DB rollback failed, and the DB backup was restored on top of the existing database, rather than clearing all the tables first.
- Fixed a MySQL error when updating to Craft 2 if the rackspaceaccess table didn’t exist.
- Fixed a bug where Craft wouldn’t install/run if it was put on a UNC network share that started with “\\”.
- Fixed a bug where the spinner wouldn’t show up when updating Craft or a plugin.
- Fixed a JS error when attempting to use any of the tools in Settings.
- Fixed a JS error when uploading a Logo or User Photo.
- Fixed a JS error when changing the Entry Type on an existing entry.
- Fixed a Twig error on the Recent Entries widget if one of the entries didn’t have an authorId.
- Fixed a PHP error on the Edit Entry page if editing a Channel/Structure entry that didn’t have an authorId.
- Fixed a couple bugs with which options were available to the Craft edition upgrade modal under certain circumstances.
- Fixed a bug where the Save button could be positioned incorrectly after changing switching an Entry Type.
- Fixed a z-index conflict when a Date/Time field was placed right before a Rich Text Field in a field layout.
- Fixed a misleading deprecation error message that was logged if a plugin called `Craft::getBuild()`.
- Fixed the page title on the /admin/whats-new page, which still said, “What’s new in Craft 1.3”.

## 2.0.2524 - 2014-04-01

### Added
- Craft is now available in three editions: Personal, Client, and Pro. See [buildwithcraft.com/pricing](http://buildwithcraft.com/pricing) for the details and a FAQ.
- Craft’s Control Panel has been redesigned, featuring a wider max width, a new layout system that offers more modularity and responsiveness, full right-to-left language support, and countless subtle improvements.
- Craft’s Control Panel is now translated into Arabic and Norwegian.
- Added [Category management](http://buildwithcraft.com/docs/categories) and a [Categories field type](http://buildwithcraft.com/docs/categories-fields).
- Added support for APC, database, eAccelerator, Memcache(d), Redis, WinCache, XCache, and Zend Data-based caching, which can be enabled via the new [cacheMethod](http://buildwithcraft.com/docs/config-settings#cacheMethod) config setting.
- Added partial template caching support via the new [{% cache %}](http://buildwithcraft.com/docs/templating/tags#cache) tag.
- Added background task support.
- Added a localizable [siteUrl](http://buildwithcraft.com/docs/config-settings#siteUrl) config setting, which takes precedence over the Site URL setting in General Settings and the `CRAFT_SITE_URL` constant.
- Added a [slugWordSeparator](http://buildwithcraft.com/docs/config-settings#slugWordSeparator) config setting, for specifying the string used to separate words in auto-generated slugs.
- Added an [addTrailingSlashesToUrls](http://buildwithcraft.com/docs/config-settings#addTrailingSlashesToUrls) config setting.
- Added an [errorTemplatePrefix](http://buildwithcraft.com/docs/config-settings#errorTemplatePrefix) config setting for defining the path prefix to HTTP error code templates, like 404.html.
- Added a [privateTemplateTrigger](http://buildwithcraft.com/docs/config-settings#privateTemplateTrigger) config setting, for customizing the template/folder name prefix that marks a template path as private.
- Added an [extraAllowedFileExtensions](http://buildwithcraft.com/docs/config-settings#extraAllowedFileExtensions) config setting, for specifying additional allowed file extensions without overwriting the default [allowedFileExtensions](http://buildwithcraft.com/docs/config-settings#allowedFileExtensions) list.
- Added an [allowAutoUpdates](http://buildwithcraft.com/docs/config-settings#allowAutoUpdates) config setting, which removes the “Update” button from the Updates page, leaving only the “Download” button.
- Added a `cachePath` file-based caching config setting, for customizing the directory that file caches get saved to. (Note that this would get saved in a new config file, `craft/config/filecache.php`.)
- Added a “Max Blocks” setting to [Matrix fields](http://buildwithcraft.com/docs/matrix-fields).
- Asset and Tag content is now translatable via their editor HUDs.
- Added the ability to disable entries on a per-locale basis.
- Added the ability to choose which sections’ locales should be enabled by default for new entries.
- Added a [localeEnabled](http://buildwithcraft.com/docs/templating/craft.entries#localeEnabled) param to `craft.entries`, which determines whether elements that are disabled for the current locale should be ignored (defaults to `true`).
- Matrix fields are now translatable at the top level, so each locale can have its own set of blocks.
- Relational fields (Entries, Categories, Assets, Users, and Tags) are now translatable at the top level, so each locale can have its own set of relationships.
- Added a “Target Locale” setting to relational fields, for specifying which locale the related elements should be pulled from. (Defaults to the same locale as the source element.)
- Added [Template Localization](http://buildwithcraft.com/docs/templating-overview#template-localization) support.
- Added locale data for `ar`, `ar_sa`, `de_at`, `de_ch`, `es_cl`, `fr_be`, `fr_ch`, `it_ch`, and `nl_be` locales.
- The CP now has full right-to-left language support.
- Added support for multi-column Dashboard widgets.
- The Dashboard will now alert admins about any new deprecation errors occurring in the system.
- Added a new Status menu to the Entries and Users index pages.
- Added a new Locale menu to the Entries and Assets index pages.
- The Sections index now includes direct links to each sections’ entry types’ edit pages.
- Added a new Find and Replace tool in Settings, for finding/replacing text within all Rich/Plain Text fields (including ones in a Matrix field).
- The Clear Caches tool now has options for clearing RSS caches, template caches, asset transform indexes, and asset indexing data.
- Added a new “Server Info” page to the CP’s hidden Utilities section, located at `admin/utils/serverinfo`.
- Added a new “Deprecation Logs” page to the CP’s hidden Utilities section, located at `admin/utils/deprecationlogs`.
- Added the ability to create a “[Client’s account](http://buildwithcraft.com/docs/users#clients-account)” if Craft Client edition is installed.
- Admins can now log in as other users.
- Added a new “Verify email addresses?” user setting, which is enabled by default, and determines whether new user email addresses need to be verified before they get applied to the account, if set by a non-admin. (This also affects new user registration.)
- Admins now have the option to bypass email verification when registering new users.
- Added a new “Assign user groups and permissions” permission.
- Added support for [dynamic entry titles](http://buildwithcraft.com/docs/sections-and-entries#dynamic-entry-titles).
- Double-clicking on entries within Entries fields will now [show an editor HUD](http://buildwithcraft.com/docs/entries-fields#editing-entry-content), just like assets, tags, and categories.
- Each asset source now [has its own field layout](http://buildwithcraft.com/docs/assets#asset-meta-fields).
- Added a Quality setting to [image transforms](http://buildwithcraft.com/docs/image-transforms).
- Added a Cache Duration setting to Amazon S3 asset sources.
- Added “Upload files”, “Create subfolders”, and “Remove files” user permissions for each asset source.
- Added support for animated GIFs (requires [Imagick](http://www.php.net/manual/en/class.imagick.php))
- Added the ability to restrict [Assets fields](http://buildwithcraft.com/docs/assets-fields) to only allow certain types of files.
- Added the ability to restrict [Assets fields](http://buildwithcraft.com/docs/assets-fields) to a single folder, which can include dynamic variables, such as “`{id}`” for the current element’s ID.
- Added support for drag-n-drop file uploading on Assets fields, the Assets index page, and asset selection modals.
- Added the ability to create new subfolders from Asset selection modals.
- Added support for [uploading files](http://buildwithcraft.com/docs/assets-fields#uploading-files-from-front-end-entry-forms) to Assets fields using normal HTML file inputs on front-end forms.
- Added the ability to set a default upload folder for [Assets fields](http://buildwithcraft.com/docs/assets-fields), which is used when uploading via drag-n-drop, or normal HTML file inputs. This setting can also include dynamic variables.
- Added [AssetFileModel::getSource()](http://buildwithcraft.com/docs/templating/assetfilemodel#getSource) for getting information about the asset’s source.
- Added [AssetFileModel::getMimeType()](http://buildwithcraft.com/docs/templating/assetfilemodel#getMimeType).
- Added [AssetFolderModel::getParent()](http://buildwithcraft.com/docs/templating/assetfoldermodel#getParent) for getting the parent folder.
- Files that begin with an underscore can now get indexed.
- Tag Sets are now called [Tag Groups](http://buildwithcraft.com/docs/tags#tag-groups).
- It is now possible to [edit tags’ names](http://buildwithcraft.com/docs/tags-fields#editing-tag-content) from their editor HUDs.
- Added the [{% cache %}](http://buildwithcraft.com/docs/templating/tags#cache) tag.
- Added the [ucwords](http://buildwithcraft.com/docs/templating/filters#ucwords) filter.
- Added [craft.request.getPageNum()](http://buildwithcraft.com/docs/templating/craft.request#getPageNum), which returns the current pagination page.
- Added [craft.config.get()](http://buildwithcraft.com/docs/templating/craft.config#get), which can be used to fetch config settings from alternate config files.
- Added [craft.fields.getFieldByHandle()](http://buildwithcraft.com/docs/templating/craft.fields#getFieldByHandle).
- Added support for passing “`:empty:`” and “`:notempty:`” to [ElementCriteriaModel](http://buildwithcraft.com/docs/templating/elementcriteriamodel) parameters when you’re looking for empty/non-empty values.
- [craft.session.getFlash()](http://buildwithcraft.com/docs/templating/craft.session#getFlash) and [getFlashes()](http://buildwithcraft.com/docs/templating/craft.session#getFlashes) now support a new argument that determines whether the flash messages should be deleted on retrieval (defaults to `true`).
- Added `TasksService`, providing the ability for Craft and plugins to queue up background tasks.
- Added `ResaveElements`, `ResaveAllElements`, `DeleteStaleTemplateCaches`, and `FindAndReplace` tasks.
- Added `DeprecatorService`, for logging when deprecated features are being used.
- Added [ConfigService::exists()](http://buildwithcraft.com/docs/apis/configservice#exists).
- Added support for additional config files, by passing a `$file` argument to [ConfigService::get()](http://buildwithcraft.com/docs/apis/configservice#get), [set()](http://buildwithcraft.com/docs/apis/configservice#set), [getLocalized()](http://buildwithcraft.com/docs/apis/configservice#getLocalized), and [exists()](http://buildwithcraft.com/docs/apis/configservice#exists).
- Plugins can now include a `config.php` file in their plugin folder that defines their default config values.
- Plugins can now specify their source language via a [getSourceLanguage()](http://buildwithcraft.com/docs/plugins/setting-things-up) function on their primary plugin class.
- It is now possible for plugins to provide non-U.S. English translations of their default email messages. If Craft can’t find a default translation for a plugin’s email message in the recipient’s preferred language, Craft will fall back on the plugin’s source language, defined by the new `getSourceLanguage()`.
- Plugins can now provide their own [console commands](http://buildwithcraft.com/docs/plugins/database#adding-custom-commands) to yiic, by saving files in a `consolecommands` folder within their plugin’s folder.
- Plugins can now log messages using `PluginClassName::log()` rather than `Craft::log`, and the messages will get saved to a plugin-specific log file.
- It is now possible to queue up Javascript file/code inclusion for the next CP request, using the new `UserSessionService::addJsResourceFlash()` and `addJsFlash()` functions.
- Added `UploadedFile` (extends [CUploadedFile](http://www.yiiframework.com/doc/api/1.1/CUploadedFile)), which adds support for dot notation in `getInstanceByName()` and `getInstancesByName()`, plus the latter has a new `$lookForSingleInstance` argument.
- Added a `$stripZeroCents` argument to `NumberFormatter::formatCurrency()`.
- Added `UrlFormatValidator`.
- Added [HttpRequestService::isLivePreview()](http://buildwithcraft.com/docs/apis/httprequestservice#isLivePreview).
- Added [HttpRequestService::close()](http://buildwithcraft.com/docs/apis/httprequestservice#close) for closing a request without ending PHP execution.
- Added [HttpRequestService::getHostName()](http://buildwithcraft.com/docs/apis/httprequestservice#getHostName) for getting the domain name without “http(s)://”.
- Added `DateTimeHelper::timeFormatToSeconds()` for converting a PHP time format string to seconds.
- Added [EntriesService::deleteEntry()](http://buildwithcraft.com/docs/apis/entriesservice#deleteEntry) and [deleteEntryById()](http://buildwithcraft.com/docs/apis/entriesservice#deleteEntryById).
- Added [MatrixService::getBlockById()](http://buildwithcraft.com/docs/apis/matrixservice#getBlockById).
- Added [MatrixService::deleteBlockById()](http://buildwithcraft.com/docs/apis/matrixservice#deleteBlockById).
- Added `MigrationsService::hasRun()`.
- Added [TemplatesService::isRendering()](http://buildwithcraft.com/docs/apis/templatesservice#isRendering) and [getRenderingTemplate().](http://buildwithcraft.com/docs/apis/templatesservice#getRenderingTemplate)
- Added the ability to set custom headers on `EmailModel`s before passing them off to [EmailService::sendEmail()](http://buildwithcraft.com/docs/apis/emailservice#sendEmail).
- Added an [elements.onMergeElements](http://buildwithcraft.com/docs/plugins/events-reference#elements-onMergeElement) event.
- Added [entries.onBeforeSaveEntry](http://buildwithcraft.com/docs/plugins/events-reference#entries-onBeforeSaveEntry), [`onBeforeDeleteEntry](http://buildwithcraft.com/docs/plugins/events-reference#entries-onBeforeDeleteEntry), and [onDeleteEntry](http://buildwithcraft.com/docs/plugins/events-reference#entries-onDeleteEntry) events.
- Added [usersession.onBeforeLogin](http://buildwithcraft.com/docs/plugins/events-reference#usersession-onBeforeLogin) and [usersession.onLogin](http://buildwithcraft.com/docs/plugins/events-reference#usersession-onLogin) events.
- Added [users.onActivateUser](http://buildwithcraft.com/docs/plugins/events-reference#users-onActivateUser), [onBeforeUnlockUser](http://buildwithcraft.com/docs/plugins/events-reference#users-onBeforeUnlockUser), [onUnlockUser](http://buildwithcraft.com/docs/plugins/events-reference#users-onUnlockUser), [onBeforeSuspendUser](http://buildwithcraft.com/docs/plugins/events-reference#users-onBeforeSuspendUser), [onSuspendUser](http://buildwithcraft.com/docs/plugins/events-reference#users-onSuspendUser), [onBeforeUnsuspendUser](http://buildwithcraft.com/docs/plugins/events-reference#users-onBeforeUnsuspendUser), and [onUnsuspendUser](http://buildwithcraft.com/docs/plugins/events-reference#users-onUnsuspendUser) events.
- Added an [assets.onBeforeSaveAsset](http://buildwithcraft.com/docs/plugins/events-reference#assets-onBeforeSaveAsset) event.
- Added a [tags.onBeforeSaveTag](http://buildwithcraft.com/docs/plugins/events-reference#tags-onBeforeSaveTag) event.
- Added `BaseModel::copy()` for creating copies of model instances.
- Added `BaseModel::getAllErrors()`.
- Added `BaseRecord::findAllById()` as a wrapper for [CActiveRecord::findAllByPk()](http://www.yiiframework.com/doc/api/1.1/CActiveRecord#findAllByPk-detail).
- Added `BaseFieldType::getContentPostLocation()` for getting the location the field’s content exists in the post data.
- Added [BaseWidget::$colspan and getColspan()](http://buildwithcraft.com/docs/plugins/widgets#getting-your-widget-to-span-multiple-columns) for setting how many columns the widegt should span.
- Added `DbCommand::replace()` for finding/replacing text within a table column.
- Added `DbCommand::getJoinedTables()` for getting an array of all tables that have been joined in the current query.
- Added `DbCommand::isJoined()` for determining if a particular table has been joined in the current query.
- Added `HeaderHelper::isHeaderSet()`.
- Added `StringHelper::toUpperCase()` and `toLowerCase()`, which should be used instead of `mb_strtoupper()` and `mb_strtolower()` due to a [PHP bug](https://bugs.php.net/bug.php?id=47742) when calling the latter functions with some languages.
- Added Added `MigrationHelper::refresh()`.
- Added `TemplateHelper::getRaw()` for converting a string to a `Twig_Markup` object (which can be output in a template without requiring the `|raw` filter).
- Added `craft()->setSiteUrl()`.
- Any pane in the CP can now have a sidebar, and you can even have a pane with tabbed sidebars + content.
- Added “info” icon + HUD support to the CP, via `<span class="info">...</span>`.
- Added a new `table.inputs` CSS class for displaying a row of UI elements.
- Added an `_elements/structure.html` include.
- Added `<h4>` and `<h5>` CSS styles.
- Added `Craft.getElementInfo()` Javascript method.
- Added `Craft.getLocalStorage()` and `setLocalStorage()`, for saving and retrieving Javascript objectFs to the browser’s local storage, in a way that is tied to that specific Craft install in the event that multiple Craft installs live on the same domain.
- The CP orientation (`ltr` or `rtl`) is now available to CP Javascript via `Craft.orientation`.
- Added `Craft.queueActionRequest()`.
- Added `Craft.LivePreview`, replacing `Craft.EntryPreviewMode`.
- Plugins can now provide their own Element Types.
- Added `ElementsService::saveElement()` which handles all of the routine tasks that go along with saving elements, including saving their content and updating search indexes.
- Added `ElementsService::mergeElementsById()`, for merging an element into another, and having the merged element’s relations and reference tags updated to point to the prevailing element.
- Added `ElementsService::getEnabledLocalesForElement()`.
- Added a `$localeId` argument to `ElementsService::getElementById()`, `EntriesService::getEntryById()`, `AssetsService::getFileById()`, `TagsService::getTagById()`, `GlobalsService::getSetById()`, and `MatrixService::getBlockById()`.
- Added an `$indexBy` argument to `AssetTransformService::getAllTransforms()`.
- Added an `elements.onPopulateElement` event that gets fired right after an element is populated from the DB, which passes `element` and `result` parameters to whoever’s listening.
- `ContentService` has been completely refactored and simplified, and is no longer something element types need to worry about if they are using `ElementsService::saveElement()`.
- `RelationsService` has been completely rewritten.
- Added `BaseElementType::getStatuses()`, for defining which statuses should show up in the element’s indexes’ Locale menus.
- Added `BaseElementType::getSource()` for getting info about a source by its key.
- Added `BaseElementType::getIndexHtml()`, giving element types a chance to customize their index HTML.
- Added `BaseElementModel::getContentFromPost()` for getting the raw post data for an element’s content, if it is known.
- Added `BaseElementModel::setContentFromPost()`, which more or less replaced `ContentService::prepElementContentForSave()`, but also allows you to simply pass the location of the fields in the post data (e.g. “fields”).
- Added `BaseElementModel::getContentPostLocation()` and `setContentPostLocation()` for getting/setting the location the element’s content fields exist in the post data.
- Added `BaseElementFieldType::getInputSources()`, giving child classes a chance to customize the sources.
- Added `BaseElementFieldType::getInputSelectionCriteria()` for returning parameters that should limit which elements can be selected.
- Added `BaseElementType::onAfterMoveElementInStructure()`.
- Added `BaseElementFieldType::getInputTemplateVariables()`.
- Added `FieldsService::assembleLayout()`.
- Added `FieldLayoutModel::getFieldIds()`.
- Added `StructuresService`, for creating and managing structures of elements.
- Added `CacheService`, which provides a consistent API for saving and retrieving data caches, regardless of the chosen caching method.
- Added `TemplateCacheService` class, with methods for clearing template caches.
- Added `ElementHelper`, with some handy methods for dealing with elements.
- Added `BaseEnum`, with `isValidName()` and `isValidValue()` static methods.
- Added `craft()->getTranslatedBrowserLanguage()`.
- Added `craft()->db->getNormalizedTablePrefix()`.
- Added the `Craft.Structure` Javascript class.
- Added `Craft.BaseElementSelectorModal::addButton()` for adding new buttons to element-selecting Javascript modals.

### Changed
- The Date field type has been renamed to [Date/Time](http://buildwithcraft.com/docs/date-time-fields), and now has options to show the date only, time only, or date and time.
- Craft now checks [routes](http://buildwithcraft.com/docs/routing#dynamic-routes) defined in both `craft/config/routes.php` and Settings → Routes on front-end site requests (so no more `siteRoutesSource` config setting).
- [Environment-specific config arrays](http://buildwithcraft.com/docs/multi-environment-configs) are now merged together recursively (so each matching environment config’s `environmentVariables` array would be merged together, for example).
- Craft’s default system email messages are now translated into the same languages Craft’s CP is translated into.
- Matrix fields now remember the expanded/collapsed state of newly-created blocks.
- [MatrixBlockModel::getOwner()](http://buildwithcraft.com/docs/templating/matrixblockmodel#getOwner) now returns the owner element in the same locale as the block.
- Inner-word punctuation is now ignored when auto-generating handles, as they are with entry slugs.
- “firstName” and “lastName” are now reserved field handles.
- The [resourceTrigger](http://buildwithcraft.com/docs/config-settings#resourceTrigger) config setting’s default value has been changed from “resources” to “cpresources”.
- [Routes](http://buildwithcraft.com/docs/routing#dynamic-routes) can now be localized (whether they come from `craft/config/routes.php` or Settings → Routes).
- Elements’ `getUrl()` methods now factor in the locale-specific site URL, if it’s set via the new [siteUrl](http://buildwithcraft.com/docs/config-settings#siteUrl) config setting.
- The CP now stretches to 1294px wide, with up to four columns.
- The CP has a new layout engine that provides much more modularity and better responsiveness.
- The update notification badge in the CP header is now automatically refreshed after clicking the “Check Again” button in the Updates widget, and after the Updates page finishes downloading the latest update info.
- The Settings icon in the CP header now comes with a menu that has direct links to each of the Settings sub-pages.
- Element selector modals now take up 2/3 of the browser’s width and height.
- Modals with custom dimensions no longer need to specify a negative top- and left-margin.
- Some modals can now be resized.
- Live Preview’s editor pane can now be resized.
- Redesigned the Edit Entry page.
- After saving an entry, the browser is redirected to the entry’s section’s index, rather than the index of the last-viewed section.
- Redesigned the Edit User page.
- Cleaned up the remote asset source settings.
- Redesigned the Get Help widget, and added the ability to include your `craft/templates/` folder in the support ticket.
- The default value for the “Attach DB backup?” checkbox in the Get Help widget is now based on the `backupDbOnUpdate` config setting.
- Redesigned [Lightswitch fields](http://buildwithcraft.com/docs/lightswitch-fields) to be even simpler.
- The columns within [Matrix field settings](http://buildwithcraft.com/docs/matrix-fields#settings) now auto-expand to match the tallest columns’ height, so they no longer have inner scrollbars.
- Turned that plugin icon’s frown upside down.
- Moved the entry type field layout editor onto the same tab as the other entry type settings.
- The site name in the CP can now be translated using Craft’s [static translation support](http://buildwithcraft.com/help/static-translations).
- The Preferred Locale user setting now shows locales in the current language.
- Locale names are now displayed in English if the requested translation is not available
- Entry and Asset selection modals within [Rich Text fields](http://buildwithcraft.com/docs/rich-text-fields) now respect the currently-selected locale.
- Element selector modals no longer show the sidebar if there’s only one source to choose from.
- Manually downloading Craft updates now happens over SSL if the CP is also being accessed over SSL.
- Added Command/Ctrl + S support to the plugin settings page.
- Users with permission to administrate users are no longer allowed to administrate Admin accounts.
- It is now possible to set a user’s profile fields, group assignments, and permissions, during user registration.
- The Users index page now has dedicated First Name and Last Name columns.
- The string representation of a User is now always their Username. (Previously it would be their First/Last Name, if set.)
- It is now possible to save a user’s account info, profile data, and photo from a single form that posts to the `users/saveUser` action.
- It is now possible to clear out a user’s avatar through the `users/saveUser` action, via an input named “deleteUserPhoto” set to anything.
- Entry URL Formats are no longer required to have a {slug} token.
- Entry slugs no longer have to be unique within a section; only the resulting entry URI does. (Slugs are still automatically adjusted to find a unique URI, though.)
- Improved the auto-slug generation for entries whose titles contain inner-word parentheses, square brackets, curly brackets, and colons.
- Entry content validation no longer gets run when saving a disabled entry.
- When an entry in a Structure section is deleted, its descendants are now moved up a level, rather than getting deleted.
- Singles no longer get a Title field, since that should only be set from the section settings.
- `craft.entries`’ [section param](http://buildwithcraft.com/docs/templating/craft.entries#section) now supports passing in a [SectionModel](http://buildwithcraft.com/docs/templating/sectionmodel) object.
- [EntryModel::getAncestors()](http://buildwithcraft.com/docs/templating/entrymodel#getAncestors), [getDescendants()](http://buildwithcraft.com/docs/templating/entrymodel#getDescendants), [getChildren()](http://buildwithcraft.com/docs/templating/entrymodel#getChildren), and [getSiblings()](http://buildwithcraft.com/docs/templating/entrymodel#getSiblings) now return [ElementCriteriaModels](http://buildwithcraft.com/docs/templating/elementcriteriamodel) prepped to return entries that match what the function names suggest. That means it’s now possible to add additional parameters, such as `entry.getChildren().type('media')`.
- Craft now supports Rackspace’s authentication API 2.0.
- Craft now shows a progress bar while uploading files from within Assets fields.
- Craft now uses clearer wording on the action buttons when dealing with asset filename conflicts.
- When an asset is replaced by another existing asset, any relations or reference tags pointing to the merged asset now get updated to point to the prevailing asset instead.
- Simplified and sped up the image transform process, getting rid of the need for the `generateTransformsAfterPageLoad` config setting.
- It’s no longer possible to accidentally move more than one asset subfolder at the same time.
- `AssetFolderModel`’s “fullPath” attribute has been renamed to “[path](http://buildwithcraft.com/docs/templating/assetfoldermodel#path)”.
- [Tags fields](http://buildwithcraft.com/docs/tags-fields) now make it clear that the top item in the “Add a tag” menu is going to be a new tag, if an exact match wasn’t found.
- [Tags fields](http://buildwithcraft.com/docs/tags-fields) now create new tags right away, so their content is immediately editable.
- The [number](http://buildwithcraft.com/docs/templating/filters#number) filter now accepts an argument which can be set to `false` to disable group symbols (e.g. commas in English) in the formatted number.
- The [currency](http://buildwithcraft.com/docs/templating/filters#currency) filter now accepts a second argument, which when set to `true` will strip any right-of-decimal digits if there are zero cents.
- Added a 4th argument to [craft.feeds.getFeedItems()](http://buildwithcraft.com/docs/templating/craft.feeds#getFeedItems) for customizing how long the results should be cached for. (The default is still the same as the `cacheDuration` config setting.)
- The first pagination page URL no longer has a /p1 at the end.
- Beginning a criteria parameter with “and” or “or” no longer needs to be case sensitive.
- Front end element forms no longer need to include every last field’s input. If a field is omitted, its value will be left alone.
- Refactored the way that plugin components get loaded.
- When setting a model’s attribute via the magic `__call()` function (e.g. `$myModel->attribute('value')`), a copy of that model instance is created, the attribute is set on the copy, and the copy is returned.
- When you pass `true` to the second argument of a model’s `getAttributes()` method (to return a packaged array), any class names are now preserved in `__class__` properties, and `ElementCriteriaModel`s’ element types are preserved in `__criteria__` properties.
- Models’ `setAttribute()` methods now look for `__class__` and `__criteria__` properties, and expand arrays into their original classes if found.
- [ConfigService::getLocalized()](http://buildwithcraft.com/docs/apis/configservice#getLocalized) now accepts a second argument for specifying the locale ID, if it should be different than the current application target locale.
- [ConfigService::maxPowerCaptain()](http://buildwithcraft.com/docs/apis/configservice#maxPowerCaptain) now sets an infinite time limit, so there’s no need to call it more than once per request.
- `DateTimeHelper::formatTimeForDb()` now accepts `DateTime` objects and strings containing valid PHP time formats.
- [TemplatesService::renderObjectTemplate()](http://buildwithcraft.com/docs/apis/templatesservice#renderObjectTemplate) now supports normal Twig tags as well as the shortcut “{varName}” tags.
- `BaseFieldType::modifyElementsQuery()` now gets called on every element query that involves the given field; not just when the `ElementCriteriaModel` has a value set on the field’s parameter.
- Plugin settings now get output within a `{% namespace %}` tag pair, so the namespace is available to `getSettingsHtml()`.
- Modals are now 2/3 the browser’s width and height by default, unless a “fitted” class has been added to their container.
- `<table class="data">`s no longer have 100% width by default. (Use the `fullwidth` class if that’s desired.)
- Checkbox inputs now have a `checkbox` class.
- Collapsible tables are now only responsive on mobile devices (not including tablets).
- It is now possible for a `.fieldtoggle` input to work in normal and reverse mode on separate elements. (Note that the `data-reverse-target` HTML attribute should now be set to the reverse-toggled selector, not just a “1” in conjunction with the `data-target` attribute, like before.)
- Field-toggling dropdown inputs can now target elements based on their class name, by prefixing the option values or the `targetPrefix` setting with a period.
- Renamed `Craft.language` to `Craft.locale` for the CP Javascript.
- `Craft.postActionRequest()`’s `options` argument now accepts a `send` callback function, which gets run immediately after the Ajax call is submitted.
- Columns based on records’ `AttributeType::ClassName` attributes are now `varchar(150)` rather than `char(150)`.
- `Craft.BaseElementSelectInput` now provides several more opportunities for customization by subclasses.
- `ElementsService::getElementTypeById()` now accepts an array of element IDs, and if that’s what it gets, an array of element type classes will be returned.
- Dropped `ElementsService::getElementLocaleRecord()`.
- Slugs and URL formats are now central element concepts, rather than entry-specific.
- Removed excessive punctuation symbols in some of the language.
- Updated jQuery to 2.1.0.
- Updated jQuery UI to 1.10.4.
- Updated jquery.fileUpload to 5.40.1.
- Updated jquery.timepicker to 1.3.5.
- Updated Redactor to 9.2.1.
- Updated qUnit to 1.14.
- Updated Guzzle to 3.8.1.
- Updated Twig to 1.15.1.
- Updated PHPUnit to 4.0.9.
- Updated PHPUnit dependencies to their latest versions.
- Updated Mockery to 0.9.

### Deprecated
- Entries within a Structure section now have a “level” instead of a “depth”. (Referring to it as “depth” will still work, but it has been deprecated.)

### Removed
- Removed the `$category` argument from `Craft::log()`.

### Fixed
- Fixed a bug where relational fields’ Limit settings weren’t getting enforced at the PHP level.
- Fixed a bug where entry versions weren’t saving Matrix field data properly.
- Fixed a bug where Matrix fields wouldn’t use the menu-style “Add Block” buttons on page load, if the Matrix field was on the first content tab.
- Fixed a bug where it wasn’t possible to edit newly-created tags’ content until the form was saved.
- Fixed a bug where Craft would show a new user registration page if accessing the edit page for a user that doesn’t exist.
- Fixed a bug where saving a Single section’s entry type would redirect you to the section’s entry type index, even though there can only be one of them.
- Fixed a bug where if you defined a custom title label other than “Title” for an entry type, any validation error message would still use the word “Title”.
- Fixed a bug where the “Delete other authors’ entries” permission wasn’t being enforced.
- Fixed a CSS glitch with the wildcard route token.
- Fixed several known issues with the CP’s mobile/responsive support.
- Fixed a MySQL error that could occur due to ordering by an ambiguous `dateCreated` column.
- Fixed a bug where `craft.feeds.getFeedItems()` was ignoring the passed-in offset.
- Fixed a PHP error that could occur when passing a “raw” string into an asset’s `getUrl()`, `getWidth()`, or `getHeight()` methods.
- Fixed a bug where Craft could accidentally load a CP template on the front end if a front end template shared the same path, and a plugin had loaded the CP template first.
- Fixed a bug where clicking outside of an HUD in the CP would only close the HUD the first time.
- Fixed a bug where passing `true` into `DbHelper::parseParam()` wouldn’t do anything.
- Fixed a bug where thumbnails would break for external sources, if the original images were missing.
- Fixed a bug where the `maxCachedCloudImageSize` config setting was not being respected.
- Fixed a bug where uploaded images were not getting cached properly.
- Fixed a bug where files in Assets were not being replaced properly unless they were images.
- Fixed a bug where setting parameters on an `ElementCriteriaModel` object could unexpectedly affect the same `ElementCriteriaModel` further down in the template.
- Fixed a bug where if you specified more than 3 digits in a Number field types decimal settings, only 3 would be displayed in the input.

## 1.3.2507 - 2014-03-14

### Fixed
- Fixed a bug where the current user would not be available in the Author menu for entries that were authored by someone else.
- Fixed a PHP error when Craft is connected to Amazon S3 and `open_basedir` restrictions are in place.
- Fixed a bug where underscores in entry slugs would get replaced with hyphens.
- Fixed a PHP error when saving a date field that had a time filled in but no date.
- Fixed a MySQL error that could occur due to ordering by an ambiguous `dateCreated` column.

## 1.3.2496 - 2014-02-27

### Changed
- Made it possible for users to validate their accounts through the CP when the site is offline.
- `craft->on()` now works when running Craft from the command line.

### Fixed
- Fixed a bug where removing a locale from a section would remove that locale from all the sections.
- Fixed infinite scrolling on the Entries, Users, and Assets indexes in some browsers.
- Fixed a bug where Plain Text and Table fields could have incorrect dimensions if they live on a secondary content tab.
- Fixed PHP error that could occur when renaming a file but omitting its extension.
- Fixed a bug where anyone could access the front end login page when the site was offline.

## 1.3.2494 - 2014-02-24

### Added
- The `entries/saveEntry` action now supports passing a “fieldsLocation” GET/POST param, specifying where the fields’ post data can be found. (It’s “fields” by default.)

### Changed
- Number field inputs no longer show group symbols (e.g. commas every 3 digits in English).
- Exact match searching is now case insensitive.
- Improved the performance of image uploading in some scenarios.
- Element selector modals and element indexes now scroll back to the top when loading a fresh batch of elements.
- Updated Craft’s Amazon S3 library to the latest version, which fixed bucket fetching on PHP 5.5.
- craft()->numberFormatter now points to an extended `NumberFormatter` class, which overrides `formatDecimal()` to add a new `$withGroupSymbol` argument (defaults to true).

### Fixed
- Fixed a bug where tags that were first created within a Matrix field would be inaccessible, and result in MySQL errors when used.
- Fixed a bug where tag search results would look beyond just the tag names if multiple words were searched for.
- Fixed a bug where the `actionTrigger` config setting wasn’t being respected in verification emails.
- Fixed an error that could occur after user activation, if the user registered on the front end, but belonged to a user group that granted them CP access.
- Fixed a MySQL error that would occur when 2+ `ElementCriteriaModel`’s were passed into the `relatedTo` element criteria param.
- Pruned some rows out of Matrix fields’ content tables that didn’t belong to Matrix blocks.
- Fixed some Javascript conflicts when two Quick Post widgets were displaying entry forms that shared the same fields.
- Fixed a bug where PHP could time out when image were being uploaded.
- Fixed some box shadow styles for Firefox.

## 1.3.2487 - 2014-02-13

### Added
- The search syntax now supports using asterisks in combination with double colons, for fuzzy searching against an attribute’s full value. For example, a search for “`title::a*`” will give you entries where the *entire* title begins with `a`, not just any word within the title.
- Added `EntriesService::deleteEntry()` and `deleteEntryById()`.
- Added `beforeShowPreviewMode`, `showPreviewMode`, `beforeHidePreviewMode`, and `hidePreviewMode` JS events to `Craft.entryPreviewMode`.

### Changed
- Rich Text fields now disable Redactor’s code view when entering/exiting Live Preview, due to a visual glitch that could occur if code view remained open.
- Added a migration to clean up any potential gaps in structures.
- Models’ and records’ “Number” attributes are no longer automatically converted to the English number format when calling `validate()` or `save()`. It is now up to the business logic to pass localized numbers through `LocalizationHelper::normalizeNumber() on their own.

### Fixed
- Fixed a bug where search results would get returned with the lowest-scoring elements listed first, when setting the `order` parameter to “score”.
- Fixed a bug where the Field Layout Designer wouldn’t get the right font when the CP was loaded over SSL.
- Fixed a bug where `UserModel::getName()` would return the user’s first/last name even if those fields only contained whitespace.
- Fixed a bug where `EntryModel`’s [getNextSibling()](http://buildwithcraft.com/docs/templating/entrymodel#getNextSibling), [getPrevSibling()](http://buildwithcraft.com/docs/templating/entrymodel#getPrevSibling), [isNextSiblingOf()](http://buildwithcraft.com/docs/templating/entrymodel#isNextSiblingOf), and [isPrevSiblingOf()](http://buildwithcraft.com/docs/templating/entrymodel#isPrevSiblingOf) could return incorrect results. (This time we mean it!)
- Fixed a bug where integer-only Number fields could get a false validation error when submitting an integer from a locale that uses a non-comma group symbol (e.g. 1.000 in German).
- Fixed a MySQL error that could occur when saving a Matrix field, if one of its sub-fields had been renamed, and a new sub-field was created with the other’s original name, at the same time.
- Fixed a bug where you couldn’t delete a field if it used a custom field type provided by a plugin that was no longer installed.

## 1.3.2486 - 2014-02-10

### Fixed
- Fixed a PHP error that could occur if Craft was trying to serve a resource which had been moved or deleted.

## 1.3.2485 - 2014-02-07

### Added
- Added `HeaderHelper::isHeaderSet()`.

### Changed
- Made major CP performance improvements when Dev Mode is disabled.
- Number fields now use the user’s preferred locale’s number format.
- Users with permission to administrate other users are no longer allowed to delete, suspend, unsuspend or unlock others’ Admin accounts.
- If the Content-Type header is already set when rendering a template, Craft will no longer override it.
- Included some improved CP translations.

### Fixed
- Fixed a MySQL error that could occur when adding new Dashboard widgets.
- Fixed a bug where `HeaderHelper::removeHeader()` would literally not do anything.
- Fixed a bug where Set Password forms on the front end wouldn’t get a validation error if the password was too short.
- Fixed a bug where Set Password forms on the front end would redirect the user to the CP after setting the password even if they didn’t have CP access.
- Fixed a bug that could prevent asset folders from being moved.
- Fixed a bug where Craft could thinks a tag doesn’t exist when it does.
- Fixed a PHP warning that could occur when a plugin’s folder name didn’t match the identifying part of its class name.
- Fix a bug in `DateTime::diff()` where you would get an invalid time format if the interval was less than one second.
- Fixed a bug where locales would not have a name if the name wasn’t available in the requested locale.
- Fix the sort order of the Locale menu on the edit entry page.
- Fixed a PHP error that could occur when someone attempted to edit an entry in a section where they didn’t have permission to edit the section’s first locale.
- Fixed a bug where the Get Help widget wasn’t validating that the From Email and Message fields were filled in.
- Wired up some CP translations that were previously MIA.

## 1.3.2473 - 2014-01-21

### Changed
- Field instructions are no longer HTML-encoded.

### Fixed
- Fixed a bug where certain authors might not be excluded in the entry Author menu.
- Fixed a bug where `EntryModel`’s [getNextSibling()](http://buildwithcraft.com/docs/templating/entrymodel#getNextSibling), [getPrevSibling()](http://buildwithcraft.com/docs/templating/entrymodel#getPrevSibling), [isNextSiblingOf()](http://buildwithcraft.com/docs/templating/entrymodel#isNextSiblingOf), and [isPrevSiblingOf()](http://buildwithcraft.com/docs/templating/entrymodel#isPrevSiblingOf) could return incorrect results.
- Fixed a MySQL error when converting a Structure section to a Channel when MySQL was in strict mode.
- Fixed a bug where the auto-updater wouldn’t delete old files like it was told.
- Fixed a bug in `admin/utils/phpinfo` where some values were being displayed as “Array” instead of their actual values.

## 1.3.2465 - 2014-01-13

### Changed
- Craft will now attempt to max out PHP’s memory limit if it thinks it needs to in order to load an image.
- `ImagesService::setMemoryForImage()` has been renamed to `checkMemoryForImage()`.
- The translations provided by Yii are now included in Craft’s translation detection script.
- `HttpRequestService::getQuery()`, `getRequiredQuery()`, and `getParam()` (as well as their `craft.request.*` counterparts) can now return arrays, and support the dot notation for fetching nested array values.
- Updated the `month` and `day` route token regular expressions so that 1-9 no longer require a `0` prefix. (Note that existing routes with a `month` or `day` token will need to be edited, with the tokens removed and re-added, before the change will take effect.)
- Updated Redactor to 9.1.9.

### Fixed
- Fixed a bug where if a Matrix field had two block type fields with the same handle, only the last one saved would ever be able to remember its content values.
- Fixed a bug where Lightswitch fields wouldn’t work immediately after switching an Entry Type; you had to re-save the entry and come back to it.
- Fixed a bug where `EntryModel::getAncestors()` and `getDescendants()` would treat the `dist` argument as a limit, contrary to the docs.
- Fix a bug where searching for `fieldHandle:value` would return incorrect results if there was no match found.
- Fixed a bug where Craft would think it had enough memory to load images when it might not have.
- Fixed a bug where `HttpRequestService::getParam()` would return the default value if the desired value was set but equated to false (e.g. `0`).
- Fixed a bug where a `<td class="right">` wouldn’t get right-aligned text if it was within a `<table class="data">`.

## 1.3.2462 - 2013-12-30

### Added
- Added an `admin` user criteria parameter for filtering users by whether they are admins.
- Added a `can` user criteria parameter for filtering users by whether they have a given permission.

### Changed
- Entries’ “Author” settings now only list users that have permission to create entries in current section.
- Rich Text fields now remove `<font>` tags if the “Clean up HTML?” setting is enabled.
- Rich Text fields now remove inline styles from more tags if the “Clean up HTML?” setting is enabled.
- More template-based errors are now showing the misbehaving template file’s source in Dev Mode error reports.
- Lightswitch fields now save a “0” search keyword when saved in the “No” position.
- Craft now displays a more helpful error message on servers without PDO installed.
- Timestamps are no longer a factor when Craft is determining which application/plugin migrations need to be run, fixing a race condition with plugin migrations.
- When adding new entries to an Entries field that’s attached to an entry, the source entry is now visible (but disabled) in the modal, rather than removed altogether. (Ditto for users, tags, and assets.)

### Fixed
- Fixed a style conflict that occurred when a Date field was displayed right before an Entries/Users/Assets/Tags field.
- Fixed Color fields within Matrix blocks and Asset/Tag content modals for browsers that don’t support `<input type="color">`.
- Fixed a visual glitch where entries would go missing if dragged from a Structure view for less than 200 milliseconds.
- Fixed a PHP error when using a `{% nav %}` tag pair without a `{% children %}` tag within it.
- Fixed a bug where users weren’t getting redirected to their originally requested URL after logging in.
- Fixed a bug where users with an expired session had to log in twice.
- Fixed a bug where the “Keep me logged in” checkbox wasn’t being respected.
- Fixed a couple bugs with the default behavior of `getPrev()` on entries, users, assets, tags, and Matrix blocks.
- Fixed a MySQL error when searching for elements with the `offset` param, but no `limit` param.
- Fix a bug where Craft would choke when uploading .bmp files.
- Fixed a MySQL error when searching for something like “title:foo OR title:barr”.
- Fixed the vertical spacing of instructions on Checkbox fields.

## 1.3.2461 - 2013-12-08

### Added
- Added `Craft.getText()`.

### Changed
- Live Preview now gets refreshed after updating an asset or tag’s contents.
- Craft now reattempts to generate image transforms if more than 30 seconds have passed since the previous attempt.

### Fixed
- Fixed a bug that prevented image transforms from getting generated.
- Fixed a PHP notice that would occur when uninstalling a disabled plugin.
- Fixed a bug where Matrix fields’ “Add block” buttons wouldn’t collapse into a menu button when entering Live Preview if they were too wide to fit in the editor pane.
- Fixed a bug where Rich Text field contents weren’t showing up in collapsed Matrix block previews.

## 1.3.2459 - 2013-12-06

### Changed
- Improved the way Craft keeps track of database migrations.
- `IOHelper::getFolderContents()` now accepts a parameter for including hidden files.
- Plugin developer links now open in a new window.

### Fixed
- Fixed a bug where Craft wouldn’t remember the default parent entry when clicking an “Add child” link within a Structure section.
- Fixed a bug where cURL requests that originated from a PHP script would require a user agent string to be set.
- Fixed a bug where Craft would choke if you tried to upload an .svg file.
- Fixed a bug where the `craft/plugins/` and `craft/storage/` folders weren’t getting included in the release.

## 1.3.2456 - 2013-12-02

### Added
- Added `Craft.encodeUriComponent()` for a JS-facing replica of PHP’s `rawurlencode()`.
- Added a `readonly` option to `_includes/forms/text.html`.

### Changed
- If Imagick is installed, but has a version less than 6.2.9, Craft will now try to use GD instead of throwing an error.
- Twig’s `date()` function and `|date`/`|date_modify` filters now use the site’s timezone.
- The Quick Post widget no longer shows the Entry Type setting if there’s only one entry type.

### Fixed
- This update patches missing locale-specific rows in the `elements_i18n` and `content` tables.
- Fixed a bug where editing a Structure entry with a disabled parent would lose its parent on save.
- Fixed a bug where a new tag might not save if there was already an existing tag with a similar (but not identical) name.
- Fixed a bug where newly uploaded assets wouldn’t save properly if assets have a required field.
- Fixed a bug where Checkboxes, Dropdown, Multi-select, Radio Buttons, and Table fields would lose their posted values if there was a validation error when saving a brand new entry.
- Fixed a bug that could result in having two field layout content tabs with the same name, if that name included `!`, `*`, `'`, `(`, or `)` characters.
- Fixed a bug where the `getResourcePath()` plugin hook wasn’t actually working.
- Fixed a bug where Lightswitch inputs were inconsistently posting values of `y` and `on`.
- Fixed a bug where Rich Text fields weren’t stripping out inline styles from `<a>` tags.
- Fixed a bug where Craft wasn’t HTML-encoding titles on the entry index page.
- Fixed a FOUC bug with Firefox on pages with an auto-focused input.

## 1.3.2422 - 2013-11-15

### Changed
- The Revision dropdown on the Edit Entry page only shows the last 10 versions now, preventing the page from getting too memory hungry on entries with lots of past versions.

### Fixed
- Fixed a bug where some textareas throughout the CP could lose their data when saved.
- Fixed a bug where Rich Text fields could lose their changes if the Command/Ctrl+S shortcut was used while editing one in fullscreen mode.
- Fixed a bug where Rich Text field menus were being cut off within Matrix fields.

## 1.3.2420 - 2013-11-14

### Changed
- The Assets index and Asset modals now show filenames with inner-word line breaks, so things don’t get too crazy.
- The current template `$context` array is now passed by reference to template hooks.

### Fixed
- Fixed a PHP error that could occur when running Craft in PHP 5.4 or later.
- Fixed a DB connection error that could occur on servers where `localhost` does not point to 127.0.0.1.

## 1.3.2419 - 2013-11-14

### Fixed
- Fixed a couple issues with Matrix in Safari.
- Fixed a bug where id= attribute values within Plain/Rich Text field content could get prepended with “field-.
- Fixed a bug where the `unix_socket` DB config param was being ignored.
- Fixed a bug where non-admins couldn’t save global content if the Localize package wasn’t installed.
- Fix a bug where an update might fail if it couldn’t delete the contents of storage/runtime/assets/icons/.
- Fixed a bug where passing multiple relation criteria to the `relatedTo` element param, glued together with “and”, might not work.
- Fixed a bug where the Entry Template section setting would get auto-updated when the section name changed on existing sections.
- Fixed a PHP error that would occur when switching the Entry Type on an entry that had a Matrix field.

## 1.3.2418 - 2013-11-13

### Added
- Added the ability to disable Matrix blocks.
- Added the ability to collapse and expand Matrix blocks.
- It is now possible to use the `search` parameter on a Matrix field to find specific blocks using Craft’s [search support](http://buildwithcraft.com/docs/searching).
- Custom field instructions now support Markdown.

### Changed
- Matrix fields now store search keywords based on their blocks’ content.
- Button menus are now at least as wide as their buttons.
- When saving a revision, if there is no logged in user, Craft now defaults the revision creator to the entry’s author.
- Filenames are now shown when viewing assets in the table view.
- If a PHP error is directly caused by something a template is doing, Craft now shows the offending template in the error report when Dev Mode is enabled.

### Fixed
- Fixed a MySQL error that could have occurred when adding a new block type field to a Matrix field, which has the same handle as a field that previously existed on the same block type.
- Fixed a bug where Matrix fields would only show the first 100 blocks in the CP.
- Fixed a bug where entry titles weren’t getting validated.
- Fixed a bug where using `{id}` in a section’s Entry URL Format wasn’t working properly when saving a brand new entry.
- Fixed a bug that could have prevented the Rebuild Search Indexes tool from indexing element content properly.
- Fixed a PHP error that occurred when passing a concatenated string to the `{% header %}` tag.
- Fixed a bug that prevented asset folders from getting renamed properly.
- Fixed a PHP error that could occur if you were to update Craft at the exact moment when Craft decides it’s due for a new requirements check.
- Re-disabled Twig’s C extension, which causes issues.

## 1.3.2416 - 2013-11-07

### Fixed
- Fixed a bug that broke the entry edit page for sites without the Localize package installed.

## 1.3.2415 - 2013-11-07

### Added
- Replaced the Delete icon within Matrix blocks with a Settings menu icon, which has options to create new blocks directly above the current block, and delete the current block.
- Added Command/Ctrl+S support to the Globals section.
- Added a new `beforeSaveShortcut` event to `Craft.cp`.

### Changed
- The “Add block” buttons in Matrix fields now collapse into a menu button if there’s not enough room for them all on the page.
- Added some missing translations.
- Added `ContentService::validateElementContent()`.
- Cleaned up the asset file icons.
- Failed model validations now get logged.

### Fixed
- Fixed a bug that broke Live Preview.
- Fixed a bug where assets wouldn’t show up anywhere after being uploaded in some cases.
- Fixed a bug where you could change existing Matrix sub-field types to Matrix.
- Fixed a bug where clicking Command/Ctrl+S from within Live Preview would lose all changes made on the entry.
- Fixed a bug where field validation would occur on fields that weren’t even in the layout being saved.
- Fixed a PHP error that could occur when saving an element without a Matrix field attached to it
- Fixed a PHP notice that could occur when Dev Mode was enabled and `config/db.php` had invalid credentials.
- Fixed a bug where a structure’s Max Depth setting could prevent other structures from adding new children from the Entries index page if they were both viewed on the same page load.
- Fixed a bug where clicking Command/Ctrl+S when editing an entry draft would redirect to the live entry’s edit page, rather than back to the draft.
- Fixed a bug where Rich Text fields’ “Insert link” and “Unlink” menu options were being labeled as “undefined”.
- Fixed a bug where non-image asset icons were only 30x30px in the thumbnail view.
- Fixed a bug where non-image assets weren’t getting icons at all on retina displays.
- Fixed a bug where a plugins’ services wouldn’t be available if they were listening to the onAfterInstall and onBeforeUninstall events.

## 1.3.2410 - 2013-11-06

### Changed
- Reversed the priority order that translation files are loaded, so more specific locales take precedence.
- Added `size` to the list of reserved field handle words.
- `Garnish.PasswordInput` is now `Craft.PasswordInput`.

### Fixed
- Fixed a bug where Craft’s requirement checker would not display the failed requirements list when attempting to install Craft.
- Fixed some bugs with loading revisions for entries with Matrix fields.
- Fixed a bug where newly-created Global Sets wouldn’t show up anywhere.
- Fixed a bug where two Matrix block type fields with the same handle could prevent one of them from being able to save content.
- Fixed a bug where non-admins would get 500 errors when Craft was attempting to fetch update info over Ajax on CP requests.
- Fixed bug where the Updates widget was selectable for users that didn’t have update permissions.
- Fixed a bug where the “Show” and “Hide” links weren’t getting translated for password inputs.
- Fixed a PHP error when attempting to load .twig templates.
- Fixed a PHP warning that could occur in `IOHelper::getFolders()` if `glob()` returned `false`.

## 1.3.2409 - 2013-11-05

### Added
- [Matrix](http://buildwithcraft.com/features/matrix).
- Added the ability to deep-link directly to a specific section on the Entries index page.
- Added a “secret” new /utils section to the CP with PHP Info and Log Viewer utilities (only accessible by admin accounts).
- Added the ability to order by most custom fields when fetching elements, by passing the field handle into the `order` param.
- Added the ability to filter by most custom fields when fetching elements, by using the field handle as a param name.
- Added the [relatedTo](http://buildwithcraft.com/docs/templating/craft.entries#relatedTo) element param, replacing `chilfOf` and `parentOf`. (The old params are still there, but deprecated.)
- Added a [fixedOrder](http://buildwithcraft.com/docs/templating/craft.entries#fixedOrder) element criteria param. When set to `true`, Craft will return elements in the order defined by the `id` param.
- Added a `last()` [output function](http://buildwithcraft.com/docs/templating/elementcriteriamodel#outputting-elements) to `ElementCriteriaModel`.
- Added the [switch](http://buildwithcraft.com/docs/templating/tags#switch) Twig tag.
- Added the [header](http://buildwithcraft.com/docs/templating/tags#header) Twig tag.
- Added the [indexOf](http://buildwithcraft.com/docs/templating/filters#indexOf) template filter.
- Added support for Norwegian and Scandinavian locales.
- Added a new [Lightswitch](http://buildwithcraft.com/docs/lightswitch-fields) field type.
- Added a new “Select Transform” menu button to the image selection modals in Rich Text fields, for selecting images with a transform.
- Craft now stores a schema version number in the `craft_info` DB table, which is now the key factor that determines whether you can swap to a different build of Craft.
- Added the `HeaderHelper` class.
- Added `IOHelper::getFolders()` and `getFiles()` for getting the immediate folders/files of a given directory.
- Added `MysqlSchema::orderByColumnValues()`.
- Added `ConfigService::getLocalized()` for getting the value of localizable config settings.
- Added `FieldsService::getFieldsWithContent()`.
- Added `FieldsService::validateField()`.
- Added `FieldModel::getFieldType()`.
- Added `BaseComponentModel::isNew()`.
- Added `ElementCriteriaModel::findElementAtOffset()`.
- Added `TemplatesService::namespaceInputName()`.
- Added `TemplatesService::namespaceInputId()`.
- Added `TemplatesService::setNamespace()` for setting a default namespace for `namespaceInputs()`, `namespaceInputName()`, and `namespaceInputId()`.
- Added `TemplatesService::formatInputId()`.
- Added `TemplatesService::startJsBuffer()` and `clearJsBuffer()`, making it possible to capture scripts included with `TemplatesService::includeJs()`.
- Added `TemplatesService::getScriptTag()`, which wraps the passed-in JS in a `<script>` tag.
- Added `TemplatesService::hook()` for plugins to latch onto template hooks (defined by the new `hook` tag).
- Added `BaseFieldType::onBeforeDelete()` and `onAfterDelete()`, which get called before and after a field is deleted.
- Added `BaseFieldType::validate()` for adding custom validation checks when saving an element.
- Added `BaseFieldType::modifyElementsQuery()` function which gets invoked when a template is filtering against a field of its type.
- Added `ElementsService::getElementById()` with an optional `$elementType` parameter that can be left blank if the element type is not known.
- Added `ElementsService::getElementTypeById()` for getting the element type associated with a given element ID.
- Added `HandleValidator::$handlePattern` which exposes the regular expression pattern used to identify valid handles.
- Added new `entryRevisions.onSaveDraft`, `onPublishDraft`, `onBeforeDeleteDraft`, and `onAfterDeleteDraft` events.
- Added support for plugins to provide translation files.
- Added support for fields to live outside of the global context.
- Added support for fields’ content columns to be saved in alternate DB tables.
- Added support for fields to have custom content column prefixes.
- Added `Craft.redirectTo()` (JS).
- Added `Craft.escapeHtml()` (JS).
- Added `Craft.formatInputId()` (JS).
- Added a `hook` Twig tag which can be used by plugins to execute PHP code at given points in the templates.
- Added a `namespace` Twig tag for namespacing all of the nested `name`, `id`, and `for` HTML attributes with a given namespace.
- Added a `|namespaceInputName` Twig filter, which points to `TemplatesService::namespaceInputName()`.
- Added a `|namespaceInputId` Twig filter, which points to `TemplatesService::namespaceInputId()`.
- Added `BaseElementType::getTableAttributeHtml()`, which defines the HTML that should occupy the table cell for the given element and attribute.
- Added `BaseElementType::hasContent()`, which determines whether `ElementsService::buildElementsQuery()` should join in the content table.

### Changed
- Clicking on the Globals tab will now take you to the first global set’s full URL, rather than just /globals.
- Editing global set content for a new locale now defaults to the primary locale’s content.
- Element index pages and selection modals are now exponentially faster than they used to be.
- The Get Help widget is no longer available to non-admins.
- Image transforms are now ordered by name.
- You can now treat [ElementCriteriaModel](http://buildwithcraft.com/docs/templating/elementcriteriamodel) objects as if they were arrays, without calling `find()`.
- Entries, Assets, Users, and Tags fields now return [ElementCriteriaModel](http://buildwithcraft.com/docs/templating/elementcriteriamodel) objects when you access their field values in the templates, so you can append additional parameters onto them before looping through them if you want, or use the `first()`, `last()`, and `ids()` [output functions](http://buildwithcraft.com/docs/templating/elementcriteriamodel#outputting-elements).
- Made it possible to order elements by their relational field-defined order in some cases, by setting the `order` param to “sortOrder”.
- Element content is now loaded up front when fetching elements, reducing the number of SQL queries and page load time on most pages.
- The `dateCreated`, `dateUpdated`, `postDate`, and Date field params now support prefixing the values with “<”, “>”, “<=”, or “>=”.
- Field column names in the craft\_content DB table are now prefixed with “field\_” so they’re easier to distinguish from Craft’s core schema.
- Outputting an `AssetFileModel` with a transform set (e.g. `{{ myAsset.myTransform }}`) now outputs the transform’s URL rather than the asset’s title.
- Craft now looks for translation files that match the target locale’s language in addition to translation files that match the full locale. For example, the `fr\_ca` locale will now load both `craft/translations/fr.php` and `fr_ca.php`.
- The `activateAccountFailurePath`, `activateAccountSuccessPath`, `loginPath`, `logoutPath`, `setPasswordPath`, and `setPasswordSuccessPath` config settings are now localizable, by setting them to an array of paths (indexed by locale ID) rather than a single path. The first path listed will always act as the default if an exact locale match cannot be found.
- Made some major performance improvements when dealing with assets by eliminating redundant DB queries.
- It is now possible to switch a Craft installation between the Stable and Dev tracks.
- Reworded “No sections exist.” to “No sections are available.” in the Quick Post widget settings.
- Updated the documentation links throughout Settings to point to the new URLs on buildwithcraft.com.
- The Get Help widget now accepts file attachments.
- Field names are now properly escaped in the CP.
- Assets of an unknown kind now get their Kind value set to “unknown” rather than null.
- `DbHelper::parseParam()` no longer creates random param names, so it’s possible to see which queries are getting repeated when looking at DB profiling output.
- CP front-end resource requests no longer get logged to `craft.log` when in Dev Mode.
- Console logs and profile data are no longer appended to non-`.html`/`.twig` template requests when in Dev Mode.
- `TemplatesService::namespaceInputs()` no longer requires the `$namespace` argument.
- `FieldsService::saveField()` has a new `$validate` argument which defaults to `true`.
- Field type, asset source type, and widget settings are now output within a `{% namespace %}` tag pair, so the namespace is available to the classes’ `getSettingsHtml()` methods via `craft()->templates->getNamespace()`.
- `_includes/forms/editableTable.html` no longer uses `jsId` and `jsName` variables; the JS-facing input ID and name attributes are now auto-generated based on the `id` and `name` variables, taking the active template namespace into account.
- `BaseModel::defineAttributes()` is no longer abstract, and thus no longer required by classes that extend it.
- `BaseSavableComponentType::setSettings()` now accepts a `BaseModel` instance, which completely replaces the internally stored settings object, rather than just calling `setAttributes()` on it.
- `ElementsService::findElements()` now returns an empty array right away if the `ElementCriteriaModel`’s `id` param is set to `false`.
- `ArrayHelper::stringToArray()` now converts `ArrayObject` instances to normal arrays.
- Craft now uses the `namespace` tag to wrap namespace field inputs, so the namespace is available via `TemplatesService::getNamespace()`, et al.
- `FieldsService::saveField()` now saves the old field handle on the `FieldModel` via a new `oldHandle` attribute.
- Added more strings to the default list of translations available to Javascript.
- The Quick Post widget is now using JS buffering to capture widget JS, rather than the old hacky way.
- Changed the way Rich Text fields get initialized.
- Moved `columnExists()` from `MysqlSchema` to `DbConnection`.
- Got rid of `AttributeType::Version` and `AttributeType::Build`.
- Gutted everything in the `RelationsService` except for `saveRelations()`, as nothing else is necessary anymore.
- `BaseInputGenerator` (JS) now triggers a `textchange` event after updating the target field’s value.
- `BaseElementType::defineTableAttributes()` should now just return an array in which the keys are the attribute to be sorted by, and the values are the table headings.
- `BaseElementType::isTranslatable()` has been renamed to `isLocalized()`.
- `ContentModel`’s locale attribute now defaults to the primary site locale.
- `DbHelper::parseDateParam()` no longer accepts an `$operator` argument, since the operator can now be set directly on the `$value`.
- All built-in field types are now using `prepValueFromPost()` rather than the deprecated `prepPostData()` function.
- Updated Twig to 1.14.2.
- Updated Guzzle to 3.7.4.
- Updated PHPUnit to 3.7.28.
- Updated Redactor to 9.1.6.
- Updated jQuery Timepicker to 1.2.9.

### Deprecated
- Deprecated `BaseElementModel::getRawContent()` in favor of `getContent()->getAttribute()`.

### Removed
- Removed the free disk space requirement, since some hosts allocate disk space on demand. And then they sell out to GoDaddy.

### Fixed
- Fixed a PHP error that could occur if an entry or asset didn’t have a title for some reason.
- Fixed a bug where assets with an .ai file extension were getting stored with the Kind value “illustrato” rather than “illustrator”.
- Fixed some permission enforcement bugs surrounding global set management and entry publishing.
- Fixed a MySQL error in `MigrationHelper::renameColumn()` that would occur if the column shared an index with another column, and that index was used by the other column’s foreign key.
- Fixed a MySQL error when saving a Number field without a value when MySQL is running in Strict Mode.

## 1.2.2399 - 2013-10-31

### Added
- Added a new requirement check for 20MB of free disk space.

### Fixed
- Fixed a bug where Craft was incorrectly returning that MySQL’s InnoDB storage engine was available when it was not.
- Fixed a bug where Mcrypt was listed as required in the docs, but optional in the code. It is now required in the code.

## 1.2.2396 - 2013-10-30

### Fixed
- Fixed a bug where the new requirements checker wouldn’t output any messages when a requirement did not pass.

## 1.2.2392 - 2013-10-29

### Fixed
- Fixed a bug where the new requirement checker would output all requirements, not just the ones that failed.
- Fixed a bug where only the first 100 authors would show up in the Author dropdown on an entry’s Settings tab.
- Fixed a PHP warning could occur on servers with `openbase_dir` restrictions in place.
- Added some missing translations.

## 1.2.2387 - 2013-10-28

### Changed
- The updater now checks for any new requirements before applying the update.
- The updater is now much smarter about when to roll back and when not to if something goes wrong during an update.
- Greatly reduced the chance of an infinite rollback loop during a failed update.
- If a new Craft update was released between the time you visit the Updates page and you click the Update button, the updater will now install the update you expected to get, rather than the latest one.
- The methods in `IOHelper` now have the option to suppress any PHP errors that might occur during file operations.
- When a logged-in user goes to the path specified by the `actionLogin` config setting, Craft now redirects them to the Dashboard or the site’s homepage depending on whether they have CP access.
- The Update Asset Indexes tool is now only visible if there is at least one asset source.

### Fixed
- Fixed a bug where Craft would run system requirements checks on front end requests, when it only should have run them for CP requests.
- Fixed a PHP error that would occur when Craft made calls to its web service, but the web service was unavailable.
- Fixed `attribute:*` searches.
- Fixed a PHP error that could occur if an entry or asset didn’t have a title for some reason.
- Fixed a bug that would occur when a Number field was submitted without a value and MySQL Strict Mode was enabled.
- Fixed various strings that weren’t being translated.

## 1.2.2375 - 2013-10-18

### Changed
- Made it possible to access environment-specific config variables from templates via `craft.config.environmentVariables`.

### Fixed
- Fixed a bug where Craft wouldn’t check to make sure the `craft/storage/userphotos/` folder was writable before generating user photo thumbnails.
- Fixed a bug where Asset modals were not taking user/group permissions into account when deciding what Asset sources to display.
- Fixed a bug where some possible parent entries might get omitted from the “Parent” entry setting withit a Structure section.
- Fixed a bug where you could create a username during installation that had a space in it.
- Fixed a bug where a user that does not have CP access would not get routed to the proper Set Password template.
- Fixed a timezone bug when displaying dates/times.

## 1.2.2371 - 2013-10-14

### Added
- `max_packet_size` setting.

### Changed
- Entries, Assets, and Users fields no longer auto-select elements after they were chosen from the modals.
- Singles are now selectable in Rich Text fields’ “Link to an entry” modals.
- The selected source is now remembered across HTTP requests in the “Choose image”, “Link to an entry”, and “Link to an asset” Rich Text field modals.
- It is now possible to select the title/username text on the Entries, Assets, and Users index pages.
- Made “status” a reserved field handle.
- The “smtpPassword” property now gets redacted from the logs when Dev Mode is enabled.
- Made improvements to the database backup and restoration scripts so there’s now a much smaller chance of running into fatal errors due to queries that are too large for MySQL’s
- Reduced the memory footprint of the database backup and restoration scripts.

### Fixed
- Fix a bug where strings with unrecognized non-ASCII characters would get truncated, causing various problems such as file upload errors.
- Fixed a bug where public user registration forms might not save the password properly.

## 1.2.2367 - 2013-10-09

### Changed
- It is now possible to pass Entries, Assets, Tags, and Users field values directly into `parentOf` and `childOf` params.
- All pages of the CP are now capable of checking for Craft updates when the info isn’t cached, and update notifications are immediately added to the CP header and footer if an update is available.

### Fixed
- Fixed a bug where user sessions weren’t being restored from cookies properly.
- Fixed a bug where the “Current Password” input on the account settings page was broken.

## 1.2.2363 - 2013-10-07

### Changed
- Improved the performance of the `search` param when searching against a specific field or attribute.
- Added translations for a few Javascript-based strings that had been missed.

### Fixed
- Fixed a bug where the “Delete” button would show up for brand new entries.
- Fixed a bug that prevented entry saving on some servers because the slug would always be blank.
- Fixed a bug where Craft’s InnoDB requirement checking could return false positives.
- Fixed PHP notices that could occur on servers where PHP doesn’t have access to `chmod()`, `chgroup()`, and `chown()`.
- Fixed a Twig error caused by the Recent Entries widget when Publish Pro is installed and all sections are Singles.

## 1.2.2358 - 2013-10-01

### Added
- Added `paginate.firstUrl`, `lastUrl`, `getPageUrl()`, `getPrevUrls()`, `getNextUrls()`, and `getRangeUrls()` to the [{% paginate %}](http://buildwithcraft.com/docs/templating/tags#paginate) tag, making it much easier to build [common pagination navigations](http://buildwithcraft.com/help/pagination-nav).
- Added [EntryModel::isAncestorOf()](http://buildwithcraft.com/docs/templating/entrymodel#isAncestorOf).
- Added [EntryModel::isDescendantOf()](http://buildwithcraft.com/docs/templating/entrymodel#isDescendantOf).
- Added [EntryModel::isParentOf()](http://buildwithcraft.com/docs/templating/entrymodel#isParentOf).
- Added [EntryModel::isChildOf()](http://buildwithcraft.com/docs/templating/entrymodel#isChildOf).
- Added [EntryModel::isSiblingOf()](http://buildwithcraft.com/docs/templating/entrymodel#isSiblingOf).
- Added [EntryModel::isPrevSiblingOf()](http://buildwithcraft.com/docs/templating/entrymodel#isPrevSiblingOf).
- Added [EntryModel::isNextSiblingOf()](http://buildwithcraft.com/docs/templating/entrymodel#isNextSiblingOf).
- Added `HttpRequestService::getScriptName()` and `craft.request.getScriptName()`.
- Added `UrlHelper::getUrlWithProtocol()`.

### Changed
- Added support for root-relative Site URLs.
- The `{% nav %}` tag now sets entries’ parents for them, potentially saving lots of DB queries
- Craft now indexes empty field values, improving the reliability of exclude searches.
- Entries, Users, Assets, and Tags fields are no longer required in the post data when saving an entry from the front end.
- `HttpRequestService::getPost()` now supports fetching nested array data via a dot notation, e.g. `craft()->request->getPost('some.nested.value')`.
- `UsersService::saveUser()` now updates the user’s status based on the status set on the passed-in `UserModel`.
- Updated Redactor to 9.1.5.
- Updated the translations.

### Deprecated
- Many of the `Craft` static methods have been moved to `craft()`, e.g. `Craft::getSiteUrl()` is now `craft()->getSiteUrl()`. The old methods are still around, but deprecated.

### Fixed
- Fixed a PHP notice caused by the Recent Entries widget when Publish Pro is not installed and Dev Mode is enabled.
- Fixed a PHP notice that would occurr when saving a top-level entry within a Structure section with Dev Mode enabled.
- Fixed a bug where saving a top-level entry within a Structure section would move the entry down to the last position.
- Fixed a bug where the “Add child” context menus would show up in Structure sections with Max Entry Depth set to `1`.
- Fixed a bug where users with CP access wolud be redirected to /dashboard on the front end after resetting their password.
- Fixed a bug where failed user account activations would redirect the user to /1.
- Fixed a PHP error when calling `SectionModel::getLocales()` when the section doesn’t have a locale in the current app target language.
- Fixed a bug where Chinese characters in slugs could become corrupted.
- Fixed a couple edge case bugs related to URL generation.
- Fixed a PHP error that could occur if a plugin were to call `HttpRequestService::getPost()` without passing any arguments, and the post data were to contain nested arrays.
- Tweaked the style of element thumbnails a little bit to work around a Firefox rendering glitch that occurred on some PCs.

## 1.2.2339 - 2013-09-24

### Changed
- You can now create Singles that map to the same URIs used by your Login, Set Password, and Forgot Password pages, and the `entry` variable will be available to the templates as expected.
- Improved the wording of the Entry Template setting instructions for Singles.
- The Entry Template section setting is now auto-populated based on the section name for new sections, just like Handle and URL Format.

### Fixed
- Fixed a bug that broke new user account validation.
- Fixed a bug that could cause long page load times when Craft is attempting to determine whether the server supports `PATH_INFO` and script name redirects.
- Fixed a bug where `HttpException`s with a status code we don’t have a template for would not fallback on the generic `error.html` template.
- Fixed a bug where you would get conflicts if you gave an Image Transform a handle that was the same as an attribute on `AssetFileModel`.
- Fixed a bug where Live Preview would not reflect any changes to Entries, Tags, Users, or Assets fields if all of the related elements were removed from the field.
- Fixed a bug where users would get caught in an infinite redirect loop if they tried to access the Login page while already logged in.
- Fixed a bug where auto-updating was broken for users on Craft 1.2 build 2337.

## 1.2.2337 - 2013-09-23

### Added
- Added `Craft.BaseInputGenerator::setNewSource()`.

### Changed
- Relaxed username validation to allow any non-whitespace character.
- It is no longer necessary to pass the output of the `|parseRefs` filter through the `|raw` filter.

### Fixed
- Fixed a bug where Rich Text fields could lose data on save if they contained links or images that were selected from the entry/asset/selection modals.
- Fixed a bug that prevented Craft from indexing brand new asset sources.
- Fixed a bug where entry slugs would stop getting auto-generated by Javascript after switching the Entry Type.
- Fixed a PHP error that occurred on servers that already have HTMLPurifier installed and in the PHP path.
- Fixed a PHP error that occurred on servers that don’t allow calling `set_time_limit()`.

## 1.2.2336 - 2013-09-20

### Changed
- Moved the Homepage icon to the left of the site name in the CP header, and made the icon and the site name a link to the site’s homepage.
- Replaced the “Slug” column with “URI” in the Entries index, and now Singles get it too.
- The Get Help widget now shows a spinner while it is submitting tickets.
- The Entries tab now goes away the instant you delete the last section.
- The Assets tab now goes away the instant you delete the last asset source.
- The Globals tab now goes away the instant you delete the last global set.
- `link`, `ref`, and `img` are now reserved field handle words.
- Text input generators (like the entry slug generator) now only update their target fields after no text has been entered for 250 milliseconds, fixing a bug where typing entry titles felt laggy due to our new full UTF-8 slug support.
- Improved the look of the `.go` links.
- Buttons with the `.active` class no longer have a pointer cursor.
- Refactored `Craft.AdminTable` JS class a bit, making it easier to read and extend.
- Moved the `id="nav-whatever"` attributes in the main CP nav up to the `<li>` rather than the `<a>`.
- Inner-word punctuation symbols are now removed completely from auto-generated slugs, rather than hyphenated. (“it’s” would now become “its” rather than “it-s”, for example.)

### Fixed
- Fixed a bug where editing a Single section’s entry would give you the option to “Save and add another”.
- Fixed a bug where the Field edit form would not display inline validation errors.
- Fixed a bug where the `dump()` Twig function no longer worked while Craft is in Dev Mode, due to a misunderstanding of Twig’s documentation.
- Fixed a bug where Craft could accidentally used the “Nested Entries” URL format when re-saving top-level entries in a Structure section.
- Fixed a bug where calling `getUrl()` on the homepage Single entry would return the site URL with `__home__` appended to it.
- Fixed a bug where Singles’ default URIs would be camelCased rather than hyphen-ated.
- Fixed a bug where HTML tags were not getting removed for the auto-generated entry slugs until the entry was actually saved.
- Fixed a bug where “Entry Types” column heading in the Sections index wasn’t getting translated.

## 1.2.2335 - 2013-09-18

### Added
- Added the radioGroupField macro to the CP’s `_includes/forms.html` template.
- Added `ConfigService::getResourceTrigger()`, which now always returns `resources` for CP requests, and the custom `resourceTrigger` config setting for front end requests.

### Changed
- The Slug, Section, Post Date, and Expiry Date columns are no longer visible when viewing Singles in the Entries index.
- iconv is now a recommended dependency; no longer a mandatory requirement.
- It’s possible to set the `CRAFT_BASE_PATH` constant without also setting `CRAFT_APP_PATH`.
- `HttpRequestService` no longer checks whether the current request is a resource/action request until `isResourceRequest()`, `isActionRequest()`, or `getActionSegments()` is actually called.
- Craft no longer adds Twig’s Debug extension when running in Dev Mode, since it was deprecated as of Twig 1.5 in favor of the now-built-in `dump()` function.

### Fixed
- Fixed a “You have a buggy version of iconv” error on servers with… a buggy version of iconv, by falling back on `mb_detect()`, `mb_convert_encoding()`.
- Fixed a PHP notice on servers that don’t have the `CURLOPT_TIMEOUT_MS` and `CURLOPT_CONNECTTIMEOUT_MS` constants predefined.
- Fixed a bug where `IOHelper::copyFolder()` wouldn’t work if the destination folder didn’t already exist.
- Fixed a bug where the `_includes/forms.html` macros (and `_includes/forms/*` templates) would ignore certain attributes when set to `0`.
- Fixed a Twig error that would occur when attempting to save an asset source without any setting values.
- Fixed a bug where setting the `resourceTrigger` config setting to `assets` (or any other top-level CP template folder/file name) would prevent access to that section of the CP.

## 1.2.2333 - 2013-09-17

### Added
- There are now three types of sections: [Singles](http://buildwithcraft.com/docs/sections-and-entries#sections), [Channels](http://buildwithcraft.com/docs/sections-and-entries#sections), and [Structures](http://buildwithcraft.com/docs/sections-and-entries#sections).
- Channel and Structure sections can now have multiple [Entry Types](http://buildwithcraft.com/docs/sections-and-entries#entry-types).
- Added element reference tag support, e.g. `{asset:100}` or `{entry:section/slug:link}`. Templates can parse for element reference tags via the new `|parseRefs` filter.
- Add a link to the site’s homepage in the header menu.
- Added a “Link to an asset” option to Rich Text fields’ Link button menu.
- Added a “Check again” button to the Updates widget.
- Added a new “Copy reference tag” context menu option to assets within the Assets index.
- Added the [{% nav %}](http://buildwithcraft.com/docs/templating/tags#nav) tag.
- Added [EntryModel::depth](http://buildwithcraft.com/docs/templating/entrymodel#depth).
- Added [EntryModel::type](http://buildwithcraft.com/docs/templating/entrymodel#type).
- Added [EntryModel::getLink()](http://buildwithcraft.com/docs/templating/entrymodel#getLink).
- Added [EntryModel::getAncestors()](http://buildwithcraft.com/docs/templating/entrymodel#getAncestors).
- Added [EntryModel::getDescendants()](http://buildwithcraft.com/docs/templating/entrymodel#getDescendants).
- Added [EntryModel::getChildren()](http://buildwithcraft.com/docs/templating/entrymodel#getChildren).
- Added [EntryModel::getSiblings()](http://buildwithcraft.com/docs/templating/entrymodel#getSiblings).
- Added [EntryModel::getPrevSibling()](http://buildwithcraft.com/docs/templating/entrymodel#getPrevSibling).
- Added [EntryModel::getNextSibling()](http://buildwithcraft.com/docs/templating/entrymodel#getNextSibling).
- Added [AssetFileModel::getImg()](http://buildwithcraft.com/docs/templating/assetfilemodel#getImg).
- Added [AssetFileModel::getLink()](http://buildwithcraft.com/docs/templating/assetfilemodel#getLink).
- Added the [parseRefs](http://buildwithcraft.com/docs/templating/filters#parseRefs) filter.
- Added [craft.request.getCookie()](http://buildwithcraft.com/docs/templating/craft.request)
- Added a new [currentUser](http://buildwithcraft.com/docs/templating/global-variables#currentUser) global variable set to the currently logged-in user, if any. (`user` is still available but deprecated indefinitely.)
- Added the `type` entry param.
- Added the `ancestorOf` entry param.
- Added the `ancestorDist` entry param.
- Added the `descendantOf` entry param.
- Added the `descendantDist` entry param.
- Added the `prevSiblingOf` entry param.
- Added the `nextSiblingOf` entry param.
- Added the [maxCachedCloudImageSize](http://buildwithcraft.com/docs/config-settings#maxCachedCloudImageSize) config setting.
- Added the [generateTransformsAfterPageLoad](http://buildwithcraft.com/docs/config-settings#generateTransformsAfterPageLoad) config setting.
- Added the [blowfishHashCost](http://buildwithcraft.com/docs/config-settings#blowfishHashCost) config setting.
- Added the [activateAccountFailurePath](http://buildwithcraft.com/docs/config-settings#activateAccountFailurePath) config setting, which replaces the now-deprecated `activateFailurePath` setting.
- Added `ConfigService::set()`.
- Added `TemplatesService::doesTemplateExist()`.
- Added `SectionsService::getEntryTypesByHandle()`.
- Added `StringHelper::convertToUTF8()`.
- Added `StringHelper::isUTF8()`.
- Added `StringHelper::getEncoding()`.
- Added `Craft::getSiteUid()` (PHP), `craft.app.getSiteUid()` (Twig), and `Craft.siteUid` (JS).
- Added `ElementsService::parseRefs()`.
- Added `EntryModel::getRef()`.
- Added `UserModel::getRef()`.
- Added `UrlManager::getRouteParams()`.
- Added `EntryModel::getSiblings()`.
- Added `Entrymodel::getPrevSibling()`.
- Added `EntryModel::getNextSibling()`.
- Added `onBeginUpdate` and `onEndUpdate` events to `UpdatesService`.
- Added `onBeforeVerifyUser` event to `UsersService`.
- Added a new `registerCachePaths` plugin hook to the Clear Caches tool, enabling plugins to provide custom cache paths.
- Added a new `ref` param to `ElementCriteriaModel`.
- Added support for a `.hover` CSS class to buttons, used to simulate :hover.

### Changed
- Iterated over the entire CP design.
- The Settings and Updates links are now presented as icons on the right-hand side of the header nav, to the left of the My Account menu, and Updates only shows up when an update is available.
- User photos are now visible from the Users index page, Users fields, and user selection modals.
- It is now possible to select multiple images at once in Rich Text fields.
- All “Name” and “Handle” settings can now be up to 255 characters long.
- The Settings → Sections index now shows each sections’ Type and URL Format.
- Sped up the time it takes for the CP to respond to changing sidebar/element selections in element index pages and selection modals.
- The “New Password” input on the Reset Password page now gets auto-focused.
- Craft now auto-scrolls the window when the cursor moves close to a window edge while dragging.
- The view modes for element sources are now stored per source and per context (modal vs. full index), rather than per field.
- The search text input is now cleared when switching sources in the element index pages and element selection modals.
- Error notifications now stay visible for twice as long before fading out.
- Compressed JS files are now even smaller in size.
- Craft now ships with source maps for each of its compressed JS files.
- Replaced the scary “X” button with “Done” in the Live Preview edit pane.
- Rich Text fields now store linked entry/asset URLs as reference tags, so the field data stays updated when the entry/asset URLs change.
- Renamed the “Updated Search Indexes” tool to “Rebuild Search Indexes”.
- Added breadcrumbs to the Tag Set edit pages.
- Asset and tag content modals will now save and close when the Return key is clicked.
- Using the save shortcut (command/ctrl + S) on an entry form now keeps you on the page after saving the entry.
- Simplified the cleanup stage of the updater.
- The `section` and `sectionId` entry params are now respected when Publish Pro is not installed.
- Calling `getNext()` or `getPrev()` on an element within a `craft.entries` (et al.) loop without passing in any parameters or `null` will now default to returning the next/previous element within that loop.
- The installer will now create a Single section for the homepage instead of a Global Set.
- Now `entryId` is the only required param for saving an existing entry via the entries/saveEntry controller action.
- Changed the way Craft downloads update patches, which will improve future update performance.
- Entry slugs can now contain any UTF-8 alphanumeric characters, as well as periods.
- Craft now comes with Source Maps for all first party compressed JS files.
- All GET and POST parameters are now converted to UTF-8 if they weren’t already.
- The site URL now always ends with a slash.
- Front-end user registration forms which supply a password no longer require the user to reset their password after validation.
- Updated the translations.
- Updated the mobile detection script used by `HttpRequestService::isMobileBrowser()`, et al.
- Error template loading (e.g. the 404 template) now respects the [indexTemplateFilenames](http://buildwithcraft.com/docs/config-settings#indexTemplateFilenames) and [defaultTemplateExtensions](http://buildwithcraft.com/docs/config-settings#defaultTemplateExtensions) config settings.
- Craft will now use the CP’s Set Password template by default when a user without CP access is asked to set their password on the front end but a custom Set Password template hasn’t been created for the front end yet.
- Craft no longer logs a user in immediately after they reset their password or after registering an account.
- Added support for a ton more MIME types.
- Craft now requires iconv.
- Craft now requires PCRE to be compiled with UTF-8 support.
- Craft now requires the Multibyte String extension with Function Overloading disabled.
- Route params are no longer getting saved in `$_GET`, drastically reducing the amount of data Craft needs to log at the bottom of every page when Dev Mode is enabled (and fixing a PHP error when there’s too much data).
- `DbHelper::parseParam()` now supports explicitly setting how the conditionals will be joined by passing “and” or “or” as the first param value.
- `Craft.postActionRequest()` now creates an “Unknown error occurred” CP notice when the response comes back with a non-200 status code by default, and calls the callback function regardless of the status code.
- `FieldsService::deleteLayoutById()` now supports batch deletion.
- The `entries/saveEntry` controller action now only the entryId param to be posted when saving an existing entry. All other params are optional and will default to the existing values.
- `BaseElementSelectorModal`’s onSelect callback data now includes the selected elements’ URLs.
- `ElementCriteriaModel::ids()` and `total()` now cache their results until an attribute has changed, saving unneeded trips to the database when `ids()` or `total()` are called more than once.
- `EntryModel::getChildren()` now acts as an alias of `getDescendants(1)` for Structure sections.
- Craft now uses [Guzzle](http://guzzlephp.org/) to make HTTP requests rather than Requests.
- Craft no longer uses phpass to generate Blowfish-encrypted passwords, thanks to the feature now being built into Yii.
- Updated Yii to 1.1.14
- Updated Twig to 1.13.2
- Updated jQuery to 2.0.3
- Updated jQuery UI to 1.10.3
- Updated jQuery Timepicker to 1.2.7
- Updated Redactor to 9.1.4
- Updated PHPMailer to 5.2.7

### Fixed
- Fixed auto-height sizing on Plain Text fields.
- Fixed a bug where images added to a Rich Text field via the “Choose image” option were not immediately editable.
- Fixed a bug where Plain Text fields wouldn’t auto-expand if they didn’t have a placeholder set.
- Fixed a bug that could cause the page to jump when opening a tag or asset content modal.
- Fixed a bug where asset and tag content modals would dissappear if you clicked a button within their own sub-modals, such as a Redactor modal.
- Fixed a bug where transforms would register as being created successfully when the source image didn’t exist anymore.
- Fixed a bug where tabbing to the “Login” button on the login form would reveal an unstyled submit button.
- Fixed a bug where the “Forget your password?” link would receive tab focus before the “Keep me logged in” checkbox on the login form.
- Fixed a bug where disabled submit buttons would still get a hover effect.
- Fixed a bug where asset transforms would not load immediately after being generated on sites that have [omitScriptNameInUrls](http://buildwithcraft.com/docs/config-settings#omitScriptNameInUrls) set to `false` (either automatically or explicitly).
- Fixed a bug where Craft-generated URLs on sites with `omitScriptNameInUrls` set to false would unnecessarily still factor in the [usePathInfo](http://buildwithcraft.com/docs/config-settings#usePathInfo) config setting.
- Fixed a bug where element selection modals would add new items to the browser’s localStorage each time they were opened.
- Fixed a bug where Tags fields could select already-selected tags, if they had at least one tag selected.
- Fixed an “unknown error” when a user attempts to log in while they already have a user session.
- Fixed a bug where templates which aren’t readable by PHP would return a 404.
- Fixed a bug where `YII_DEBUG` wasn’t getting set if devMode was being set in an environment-specific config.
- Fixed several errors/noticed/warnings/wonkiness on servers that have Twig’s C Extension installed.
- Fixed a PHP warning that could occur on servers with `openbase_dir` restrictions in place.

## 1.1.2321 - 2013-09-10

### Fixed
- Fixed a few bugs related to Tags fields when there is more than one Tag Set.
- Fixed a MySQL error when deleting a user who created any entry versions or drafts on an entry that was not authored by the user.
- Fixed a bug where users registered from the front-end would have to enter their password twice to complete account registration.
- Fixed a bug where no entires would show up in the recent entries widget if you had “All” selected in the widget settings.

## 1.1.2314 - 2013-09-05

### Fixed
- Fixed a bug where you could not remove already-selected tags in Tags fields.
- Fixed a bug where validation errors would incorrectly be added to required Tags fields that did in fact have selected tags.
- Fixed a bug where entries could get deleted even after clicking “Cancel” on the entry deletion confirmation dialog.
- Fixed a bug where user account pages would 404.
- Fixed the position of the email icon on the confirmation dialog when a user forgets their password.

## 1.1.2313 - 2013-09-04

### Changed
- Element fields now support `ArrayAccess`
- Model attributes now support `ArrayAccess`
- Craft now checks if the asset source folder exists when uploading and throws an exception if not.

### Fixed
- Fixed a bug where all yiic plugin migration commands were failing.
- Fixed a bug where the Get Help widget might fail to attach logs if you had less than three log files.
- Fixed a bug where `TransformLoader` wasn’t accounting for `blank.gif` URLs that had been encoded by Twig.
- Fixed a bug where Entries → All Entries would include entries in sections that the user did’t have permission to edit.

## 1.1.2305 - 2013-08-27

### Changed
- Improved the handling of failed asset uploads.

### Fixed
- Fixed a bug where entry URLs would not get routed properly if a Route existed that conflicted with the section’s URL Format.
- Fixed a PHP notice that may have occurred if you had a Quick Post widget that was set to a deleted section.
- Fixed a bug where PHP fatal errors would not get logged properly when Dev Mode wasn’t enabled.

## 1.1.2304 - 2013-08-19

### Added
- Added `MigrationHelper::dropForeignKeyIfExists()` and `dropIndexIfExists()`.
- Added Help links to each page in Settings, which take you to the docs for that page.

### Changed
- Added the cURL extension as a requirement to run Craft, due to issues with the fallback HTTP client.
- Tweaked the release note icons.
- If Craft cannot connect to its web services, it will not try again for five minutes.

### Fixed
- Fixed a bug where Craft was not verifying that the current user is an admin before allowing them to make changes to a section.
- Fixed a Twig error when saving a tag set with validation errors.
- Fixed a MySQL `max_packet_limit` error which could occur if Craft tried to restore a large database or that setting was set particularly low.
- Fixed two migrations that might have thrown foreign key constraint violations on some databases when run.
- Fixed a bug in `EmailService` where the From address might be used instead of the `ReplyTo` address when replying in some email clients.

## 1.1.2302 - 2013-08-13

### Changed
- The asset and tag content modals will now shake when you attempt to save them but there are validation errors.

### Fixed
- Fixed a bug that would prevent Craft from being able to update to 1.1.2298 or later if the `craft_relations` table’s foreign keys did not have cascade deletes.
- Fixed a PHP error that would occur when non-admin users with CP access would attempt to log into the CP while the system was off.
- Fixed a bug where uploading files wouldn’t validate if there were any required fields in the assets field layout.
- Fixed a bug where changing a user’s preferred locale in their account settings would have no effect.
- Fixed a PHP error that might occur during asset indexing if an asset source path did not exist.
- Fixed a bug where asset uploading would not work if you were using PHP’s built-in development web server.

## 1.1.2300 - 2013-08-09

### Fixed
- Fixed a PHP notice that could occur during the cleanup stage of an update.
- Fixed a bug that caused Craft to hang if an error occurred during a manual update when the user was not logged in.
- Fixed a PHP notice that could occur when trying to send an email.
- Fixed a bug where you could manually access Craft’s installer after Craft was already installed, even if Dev Mode was disabled.
- Fixed a bug where DB connection error messages would not be styled.

## 1.1.2298 - 2013-08-08

### Fixed
- Fixed some wonkiness with sidebar scrolling.
- Fixed a bug where Users fields wouldn’t output anything.
- Fixed a bug where the Users index and user selection modals could have a thumbnail view.
- Retroactively fixed a bug where rows could remain in the `craft_elements`, `craft_elements_i18n`, and `craft_content` tables after deleting a Section, Asset Source, Asset Folder, Tag Set, or User.
- Fixed a bug where Email validation errors would not show up when saving a user account if there was also a New Password validation error.
- Fixed a PHP error when viewing an entry version or draft in a section that doesn’t have any fields.
- Fixed a bug where all of the cells in the last table row would get a bottom border when the table is collapsed for small screens.

## 1.1.2293 - 2013-08-05

### Changed
- Craft now supports Twig’s `ignore missing` [include](http://twig.sensiolabs.org/doc/tags/include.html) tag param.
- Craft now supports Twig’s [include](http://twig.sensiolabs.org/doc/tags/include.html) tag’s template list syntax.
- Rich Text fields are now translated into the user’s preferred locale.
- Added `.svg` to the default `allowedFileExtensions` config setting value.
- Emails sent through the `EmailService` now get the `sender` attribute set.
- Craft now includes `jquery.min.map` for easier jQuery debugging when the `useCompressedJs` config setting is set to `true`.

### Fixed
- Fixed a bug where Live Preview would use the primary site locale when previewing a non-primary locale’s content.
- Fixed a bug where deleting a section, global set, or tag set wouldn’t delete its field layout in the database.
- Fixed a bug where Live Preview would show the site’s homepage for unsaved entries when running Craft from a subfolder.
- Fixed a bug where clicking “Save and continue editing” wouldn’t remember the locale you’re currently editing.
- Fixed a bug that was causing users to get the incorrect status colors on the Users index page and user selector modals.
- Fixed a bug where Assets fields’ Javascript wouldn’t enforce the Limit setting.
- Fixed a bug where email and password validation errors wouldn’t display on the account settings page.
- Fixed a bug where the sidebar could bleed outside of the main content div in the CP.
- Fixed a bug where entries created with the Quick Post widget would initially show up in the Recent Entries widget as “undefined”.
- Fixed a bug that made it impossible for a plugin to have both `FooController` and `Foo_BarController` classes.
- Fixed a bug where routes that point to a plugin’s controller wouldn’t get resolved correctly.
- Fixed a bug where `craft.tags` would return tags sorted by name descending order instead of ascending order by default.
- Fixed a bug where images saved in the cloud wouldn’t get their thumbnails generated locally.
- Fixed a bug where the updater might fail during the clean-up stage due to missing manifest data on the file system.
- Fixed a bug where database profiling data was getting logged to `craft.log` on every request, even when Craft was not running in Dev Mode.
- Fixed a bug in the installer when MySQL strict mode was enabled.

## 1.1.2291 - 2013-07-29

### Added
- Added the concept of [Tag Sets](http://docs.buildwithcraft.com/diving-in/tag-sets.html), which are collections (or *sets*) of [Tags](http://docs.buildwithcraft.com/diving-in/tags.html)! Each Tag Set gets its own field layout, similar to Sections.
- Added a new [Tags field type](http://docs.buildwithcraft.com/diving-in/fields/types/tags.html), which replaces the old Tags entry setting, and can be applied to entries, users, assets, global sets, and even other tags.
- Assets now have titles, which default to the filename, sans-extension, and with underscores replaced with spaces.
- Added a new Japanese CP translation.
- Added a Max Length setting to the Plain Text field type.
- Added support for the “attribute:*” search syntax for finding elements which have *any* value set for the given attribute.
- Added a `|shuffle` Twig filter.
- Added `craft.request.isLivePreview()`.
- Added a new `ids()` output function to `craft.entries` and `craft.users`.
- Added [craft.assets](http://docs.buildwithcraft.com/templating/reference/globals/craft.assets.html).
- Added [craft.tags](http://docs.buildwithcraft.com/templating/reference/globals/craft.tags.html).
- Added `getNext()` and `getPrev()` template functions to [entries](http://docs.buildwithcraft.com/templating/reference/variables/EntryModel.html#navigational-methods), [users](http://docs.buildwithcraft.com/templating/reference/variables/UserModel.html#navigational-methods), [assets](http://docs.buildwithcraft.com/templating/reference/variables/AssetFileModel.html#navigational-methods), and [tags](http://docs.buildwithcraft.com/templating/reference/variables/TagModel.html#navigational-methods).
- Added support for passing a `craft.entries`, `craft.users`, `craft.assets`, or `craft.tags` variable (sans the output function) into the parentOf and childOf params, e.g. “craft.entries.parentOf(craft.tags)”.
- Added a new `tag` route token.
- Added [multi-environment config support](http://docs.buildwithcraft.com/diving-in/multi-environment-configs.html).
- Added the ability to define environment-specific variables that are available to your Site URL and asset sources’ URL and Path settings, via the new `environmentVariables` [config setting](http://docs.buildwithcraft.com/reference/config-settings.html).
- Added the ability to customize the quality level of image thumbnails and transforms, via the new `defaultImageQuality` [config setting](http://docs.buildwithcraft.com/reference/config-settings.html).
- Added the ability to customize the template file extensions Craft will look for when matching a template path to a file, via the new `defaultTemplateExtensions` [config setting](http://docs.buildwithcraft.com/reference/config-settings.html).
- Added the ability to customize the template file names Craft will look for within a directory to represent the directory’s “index” template when matching a template path to a file, via the new `indexTemplateFilenames` [config setting](http://docs.buildwithcraft.com/reference/config-settings.html).
- Added support for defining the “Reply To” field when sending emails via the `EmailService`.
- Added `BaseMigration::insertOrUpdate()` as a wrapper for `DbCommand::insertOrUpdate()`.
- Added new `users.onBeforeDeleteUser`, `users.onDeleteUser`, `tags.onSaveTag`, and `tags.onSaveTagContent` events.
- Added `LocalizationService::getAppLocaleIds()`.
- Added `MigrationHelper` with some handy methods for migrations.

### Changed
- Gave the installer an awesome new background illustration created by Paul Burton of [Vile, Inc.](http://www.vileinc.com/).
- The CP login screen now uses the new Craft logo by default.
- It is now possible to upload assets directly from Assets field modals.
- Added a new thumbnail view to Assets field modals.
- It is now possible to edit selected assets’ content from Assets fields by double-clicking on them.
- Plain Text fields now get an appropriate MySQL column type based on their Max Length setting value. (They default to TEXT if the setting was left blank.)
- Entries, Assets, and Users fields now only save the related element’s title, filename, or user name in their search keywords.
- The parent element is now excluded from the selection modals in Assets, Entries, and Users fields.
- New Global Set handles are now auto-generated as you’re typing in the Name setting.
- “Save” button context menus now open above the button if there’s not enough room for the menu to expand below without scrolling.
- The CP is now redirected back to Settings → Sections after saving a section.
- The CP is now redirected back to Settings → Assets → Fields after saving the asset field layout.
- The installer now checks if Craft has been translated into one of the browser’s preferred languages, and uses that if so.
- The installer now creates a “Default” Tag Set and a Tags field, and adds the Tag field to the default News section’s field layout.
- Redesigned the Updates page.
- Craft’s updater now runs through the same requirement-checking process as the installer.
- Other minor visual improvements.
- Newly-created `month` and `day` route tokens are now more strict (e.g. `13` would not be accepted as a valid month). Note that this does not affect existing routes.
- Craft will now find templates with a `.twig` extension by default, along with `.html`. (Customizable via the new `defaultTemplateExtensions` config setting.)
- The `loginPath`, `logoutPath`, `setPasswordPath`, and `forgotPasswordPath` config settings now only affect front-end requests.
- Page content types are now determined by the extension of the primary rendered template file, rather than the extension in the URL.
- The site name is now prepended to DB backup filenames.
- DB params that are just plain “not ” are now ignored.
- Craft no longer uses the `PluginRecord` for determining the enabled plugins, removing a SQL query from each request.
- Improved plugin initialization handling.
- Field types’ `onAfterElementSave()` functions are now called before `getSearchKeywords()`.
- Added the `$includeAuditColumns` params to `BaseMigration::insertAll()`, `insert()`, and `update()`.
- All image manipulation functions are now handled by the Imagine PHP library.
- Updated Redactor to 9.0.4.

### Deprecated
- `TemplatesService::includeHeadNode()` and `includeFootNode()` have been renamed to `includeHeadHtml()` and `includeFootHtml()`. (The old functions still exist, but are now deprecated, and will be removed in Craft 2.0.)
- Added a new `prepValueFromPost()` function to field types, which replaced `prepPostData()`. (`prepPostData()` still exists and is used, but has been deprecated and will be removed in Craft 2.0.)

### Fixed
- Fixed some issues with asset PNG transparency.
- Fixed a bug that prevented Live Preview from working on some browsers if the CP is loaded over SSL but the main site URL is not.
- Fixed a bug where the CP would check for the `CRAFT_LOCALE` constant when determining the target app language.
- Fixed a validation bug when a user set their password via a front-end password form.
- Fixed a PHP error if a plugin’s `init()` method loads a template or throws an exception.
- Fixed a bug where calling `AssetSourcesService::getAllSourceTypes()` would throw a 500 error if the Cloud package isn’t installed.
- Fixed a PHP error when installing Craft via the console.

## 1.0.2280 - 2013-07-12

### Changed
- Added a new “powerpoint” file kind that consists of .ppt and .pptx files.
- `@` symbols are row allowed in asset filenames.
- Resource requests now get a 304 Not Modified response when appropriate.

### Fixed
- Fixed a bug where Craft would display a completely uninformative error during installation if the `craft/config/` folder isn’t writable.
- Fixed a bug where some cells in Table fields wouldn’t auto-expand to their proper height on page load.
- Fixed a JS error that would occur when a Rich Text field’s Redactor config file doesn’t exist on the server.
- Fixed a bug where the Last-Modified header was always set to `0`.

## 1.0.2278 - 2013-06-24

### Added
- Added `craft()->urlManager->getMatchedElement()`.

### Changed
- Craft now attempts to match the original image’s quality and file size when saving a cleansed uploaded image.
- Requesting an empty CP resource no longer returns a 404 error.
- The CP now gets redirected to Settings → Globals after saving a global set.

### Fixed
- Fixed a bug where the Get Help widget would not send the logs and DB backup even if instructed to do so.
- Fixed a bug where `DateTime::diff()` would not pass the `invert` property to the returned `DateInterval` instance.
- Fixed a bug where the Settings cog icon would be out-of-place when selecting a new field with a long name in the Field Layout pages.
- Fixed some CSS issues with the default front-end templates.
- Fixed a bug where Redactor toolbars would get extra height if their bottom Y position was higher than the bottom of the sidebar nav.
- Fixed the always-present scrollbars in element selection modals.
- Fixed a bug where Rich Text fields would get horizontal scrollbars if they contained page breaks.
- Fixed a bug where radio buttons would lose their selections when toggling Live Preview.

## 1.0.2277 - 2013-06-21

### Changed
- Craft now only checks to make sure the `craft/config/` folder is writable if the `license.key` file doesn’t exist already.
- Updated Redactor to 9.0.1.

### Fixed
- Fixed a bug where the Get Help widget would always send the DB backup and log files, even when instructed not to.
- Fixed a PHP warning that would occur when using the `|datetime` Twig filter on a `DateTime` object.
- Fixed a bug that prevented CP translations from being applied when manually updating Craft.
- Fixed a bug where submitting a Table field with no rows would retain its previous value.
- Fixed a bug that prevented Users fields from outputting anything in the templates.
- Fixed a bug that broke the User Photo and Logo cropping modals.

## 1.0.2275 - 2013-06-17

### Fixed
- Fixed the `|length` filter for Entries, Assets, Users, Checkboxes, and Multi-select fields.

## 1.0.2274 - 2013-06-17

### Fixed
- Fixed a PHP error when outputting assets in a template.

## 1.0.2273 - 2013-06-17

### Changed
- Added .webm to the default list of allowed upload file extensions.
- Tightened transform settings validation to prevent invalid transform handles from being saved.

### Fixed
- Fixed a MySQL error that would occur when deleting elements that were related to other elements via an Entries, Users, or Assets field.
- Fixed a bug where relation fields wouldn’t remember selected elements that were disabled/not-live.
- Fixed a bug that prevented Dropdown and Radio Button fields’ values from being translatable (e.g. `entry.dropdownField | translate`).
- Fixed a bug where deleting an asset wouldn’t delete its row in `craft_elements`.

## 1.0.2270 - 2013-06-11

### Added
- Added `ArrayHelper::filterEmptyStringsFromArray()`.

### Changed
- Increased PHP’s timeout duration for installing and updating to 120 seconds.
- Craft now auto-focusses the first column’s input when creating a new row in Table fields (and other Table-based settings).
- Login error codes are now returned for non-Ajax login requests.
- Updated the translations.
- Made it possible for plugins to extend `UserSessionService` and call their own extended `login()` method successfully.

### Fixed
- Fixed a PHP error that broke the control panel for the `fr_fr` locale.
- Fixed a bug where slug segments consisting of “0” would get removed when saving an entry.
- Fixed a Javascript “[object Object]” alert box that would pop up if you left the Settings → Packages page before it had finished making its initial Ajax request.
- Fixed a minor visual glitch within Route modals.
- Fixed a bug where `<ul>`s and `<ol>`s in Rich Text fields wouldn’t get the proper bulleted/numbered list styles.
- Fixed a bug where the field layout wasn’t editable on new, unsaved Global Sets.
- Fixed a PHP warning on the Settings → Plugins page if a plugin’s folder was missing or not readable.
- Fixed a bug where locked user accounts wouldn’t reveal the remaining cooldown time when the user attempted to log in.
- Fixed a bug where 404 requests would still get a MIME type based on the request’s URL, rather than text/html.

## 1.0.2267 - 2013-06-06

### Added
- Added the `users.beforeSaveUser` event.

### Changed
- Craft now displays a more user-friendly error page if it cannot connect to the database.
- Updated the CP translations with some strings that were missed initially.

### Fixed
- Fixed a bug where the `cpTrigger` config setting could become exposed on the front-end of your website.
- Fixed a bug where default field values were not getting applied when editing new content.
- Fixed a bug where Checkboxes fields wouldn’t retain their new value if the user deselects all checkboxes.
- Fixed a bug where stuff included by `{% include* %}` tags wouldn’t get included in error templates which didn’t output `getHeadHtml()` and `getFootHtml()` functions.
- Fixed a bug that made it impossible to unlink the selected text within Rich Text fields.
- Fixed a bug when linking some selected text within a Rich Text field to an entry, the text would be replaced by the entry’s title.
- Fixed a bug where Rich Text field dropdown menus might be obscured in Live Preview.
- Fixed a bug where removing all widgets from the Dashboard would trigger the Dashboard into recreating all the default widgets.
- Fixed a bug where Date fields attached to Asset field layouts would not work after closing and re-opening an asset’s content modal.
- Fixed a bug where the “Default Group” user setting was not saving properly.
- Fixed a bug where clicking the “Choose image” or “Link to an entry” dropdown menu options within a Rich Text field within an Asset content modal would close the Asset content modal.

## 1.0.2266 - 2013-06-04

### Added
- Added [Live Preview](http://docs.buildwithcraft.com/diving-in/livepreview.html).
- Added [search support](http://docs.buildwithcraft.com/diving-in/search.html).
- Added the [Color](http://docs.buildwithcraft.com/diving-in/fields/types/color.html) field type.
- Replaced the Links field type with new [Entries](http://docs.buildwithcraft.com/diving-in/fields/types/entries.html), [Assets](http://docs.buildwithcraft.com/diving-in/fields/types/assets.html), and [Users](http://docs.buildwithcraft.com/diving-in/fields/types/users.html) relational field types. (Don’t worry – any existing Links data will get migrated and the template syntax has not changed.)
- Added a new “Choose image” option to the Image toolbar button menu in Rich Text fields which opens the same entry selection modal used by the new Assets field.
- Added a new “Link an entry” option to the Link toolbar button menu in Rich Text fields which opens the same entry selection modal used by the new Entries field.
- Added new [Update Asset Indexes](http://docs.buildwithcraft.com/diving-in/tools/types/update-asset-indexes.html), [Clear Caches](http://docs.buildwithcraft.com/diving-in/tools/types/clear-caches.html), [Backup Database](http://docs.buildwithcraft.com/diving-in/tools/types/backup-database.html), and [Update Search Indexes](http://docs.buildwithcraft.com/diving-in/tools/types/update-search-indexes.html) tools to the [Settings page](http://docs.buildwithcraft.com/cp/settings/index.html).
- Added a context menu to the Save button when editing a user (other than your own account) with the options “Save and continue editing” and “Save and add another”.
- Added the ability to delete users and entry drafts, as well as the corresponding user permissions.
- Added `childOf`, `childField`, `parentOf`, and `parentField` params to [craft.entries](http://docs.buildwithcraft.com/templating/reference/globals/craft.entries.html) and [craft.users](http://docs.buildwithcraft.com/templating/reference/globals/craft.users.html)
- Added `getParents()` and `getChildren()` methods to [EntryModel](http://docs.buildwithcraft.com/templating/reference/variables/EntryModel.html) and [UserModel](http://docs.buildwithcraft.com/templating/reference/variables/UserModel.html).
- Added a new [markdown](http://docs.buildwithcraft.com/templating/reference/filters.html) filter (and an `md` shortcut) for parsing text with Markdown.
- Added [craft.request.isAjax](http://docs.buildwithcraft.com/templating/reference/globals/craft.request.html).
- Added [`craft()->on()`](http://docs.buildwithcraft.com/plugins/advanced/events.html) for attaching events to services without forcing them to be initialized.
- Added new `entries.onSaveEntry`, `users.onSaveUser`, `users.onSaveProfile`, `assets.onSaveFileContent`, `globals.onSaveGlobalContent`, and `content.onSaveContent` [events](http://docs.buildwithcraft.com/plugins/advanced/events.html).
- Added `StringHelper::parseMarkdown()`.
- Added `NumberHelper::makeNumeric()`.
- Added a `Craft.initUiElements()` JS function.
- Added new `Craft.ElementSelectorModal` JS class for initializing element selection modals.
- Added CP translations for German, UK English, French, Italian, and Dutch.

### Changed
- Craft now ships with much more helpful default templates, and the installer actually creates a little content, so you can get a better sense of how the system works.
- Refreshed the CP design.
- The Entry and User index pages now have working search fields, infinite scrolling, table column sorting, and state memory between requests, just like Assets.
- Updated Redactor to 9.0, dramatically improving the HTML output of Rich Text fields.
- Most forms in the CP now support typing Command/Ctrl+S to submit them.
- CP modals are now dismissable by pressing the ESC key.
- When clicking the `lock` button to change a user’s email or password, the current password field is now auto-focused.
- Added a confirmation dialog when uninstalling a plugin.
- Beefed up the templating options for [Checkboxes](http://docs.buildwithcraft.com/diving-in/fields/types/checkboxes.html), [Dropdown](http://docs.buildwithcraft.com/diving-in/fields/types/dropdown.html), [Multi-select](http://docs.buildwithcraft.com/diving-in/fields/types/multiselect.html), and [Radio Buttons](http://docs.buildwithcraft.com/diving-in/fields/types/radio-buttons.html) fields, adding the ability to loop through all options, output option labels, and determine whether specific options are selected.
- When submitting entry/user/asset content on the front-end, any fields that are omitted from the POST data entirely will retain their previous values.
- Text “hints” in the CP are now “placeholders”, and use the HTML5 `placeholder` attribute. A Javascript fallback is in place for browsers that don’t support them yet.
- The Field Layout pages now use a custom font rather than Times New Roman.

### Deprecated
- Added [PluginsService::call()](http://docs.buildwithcraft.com/plugins/apis/plugins.html) to replace the now-deprecated `callHook()`, and deprecated the “hook” method name prefix requirement from the plugin methods called by the function.

### Removed
- Removed the Plain Text field type’s Max Length setting as it doesn’t have any effect yet.
- Removed the Plugin Updates section from the Updates page since we aren’t actually checking for plugin updates yet.
- Removed some old code and DB migrations that are no longer needed thanks to the previous release being a breakpoint release.

### Fixed
- Fixed several IE 8 and 9 issues.

## 0.9.2262 - 2013-06-03

### Fixed
- Fixed a bug that broke the installer.

## 0.9.2260 - 2013-06-02

### Added
- Added `craft.request.isAjax()`.

### Changed
- `ArrayHelper::StringToArray()` now removes any empty array elements.
- Updater enhancements.

### Fixed
- Fixed email settings validation.
- Fixed a PHP error when deleting a field that has a non-existent field type.

## 0.9.2246 - 2013-05-21

### Changed
- Admins are now allowed to change other users’ passwords.
- Now only admins are allowed to change other users’ email addresses.
- When editing a user’s email address or password, you must now provide your own password.
- CP access permissions are now enforced earlier in the request cycle, preventing an error message that did not reveal the real issue when a non-permitted user attempted to access a page in the CP.
- The ESC key now closes active modal windows.
- Apostrophes are now removed, rather than replaced with a dash, when generating entry slugs.

### Fixed
- Fixed a bug that prevented emails from being rendered correctly when the site locale is set to something besides `en_us`.
- Fixed a bug preventing accurate account cooldown time calculation when your account is locked.
- Fixed a bug with two-digit year detection.
- Fixed a few minor front-end registration bugs.

## 0.9.2243 - 2013-05-15

### Changed
- Date fields now support two-digit years.
- Added the current version’s release date to the CP footer.
- Made minor visual improvements and fixes.

### Fixed
- Fixed a bug that would cause a PHP error on many form submissions and the `|group` filter on some versions of PHP.
- Fixed a bug when trying to automatically detect whether the file system supports file locking when writing to a file.
- Fixed a bug where you could not change the case of a file or folder name in Assets.
- Fixed a bug where sections and global sets would be ordered by date modified rather than alphabetically.

## 0.9.2240 - 2013-05-13

### Added
- Added `UserModel::isInGroup()` to check if a user belongs to a particular group.
- Added `TemplatesService::renderObjectTemplate()`, powering entry URI generation, the `|group` filter, and all `redirect` params.
- Added `DateTime::localeDate()` and `DateTime::localeTime()` for accessing the date/time in the current locale’s preferred formats.
- Added a new `|group` Twig filter for grouping the items in an array into sub-arrays, indexed by a common property among all the items.

### Changed
- Added the full power of Twig to section URL Format settings, so you can access any entry property, apply filters, etc..
- `redirect` params when saving entries, etc., also get the full power of Twig.
- `DateTime->__toString()` now returns the W3C date rather than a U.S. date format.
- `redirectToPostedUrl()` now accepts objects and passes the URLs through `renderObjectTemplate()`
- Disabled entries no longer get to reserve a system URL.

### Fixed
- Fixed a regression bug in `IOHelper::writeToFile()` where it would generate a PHP Error.
- Fixed a bug that broke on-demand image transforms when the site has a protocol-relative URL.
- Fixed a bug where `Twig_Error` exceptions thrown on a template that was not loaded with Craft’s default template loader would cause a second exception to get thrown, masking the original error.
- Fixed a bug where the `Slug` column on the Entries index was getting a link to nowhere for entries that don’t have URLs.
- Fixed a bug where the Asset index page wouldn’t load if an image was missing its width or height in the database.
- Fixed a bug that could cause a PHP notice when logging out on rare occasions.

## 0.9.2235 - 2013-05-09

### Added
- Added support for protocol-relative URLs.
- Added the `testToEmailAddress` config variable. If set, `EmailService` will send to it instead of the supplied email address.
- Added config variables `defaultFolderPermissions`, `writableFolderPermissions` and `writableFilePermissions`. Craft will use these when creating files and folders.

### Changed
- If using the date/time picker and you only select time, Craft will now default to the current date.
- If Craft isn’t installed yet and it is a control panel request, we will pretend like it is in devMode so that you can easily see any installation error stack traces and messages.
- Craft now supports automagic detection if your file system supports file locking when writing to a file (looking at you NFS!). Removed the `useLockWhenWritingToFile` config variable.

### Fixed
- Fixed a bug where the last page of a paginated list of elements would suggest it’s showing more elements than there actually are.
- Fixed a bug where times entered via the timepicker wouldn’t be saved in the system timezone.
- Fixed a bug where entry foreign keys might not have CASCADE deletes set properly.
- Fixed a bug when using `yiic` where the runtime path was set incorrectly.
- Fixed an incorrect mime-type message getting logged in Chrome’s console.
- Fixed wonky right-click detection on some browsers.
- Fixed a bug where the entry Save context menu options wouldn’t trigger if an input on the page had focus.

## 0.9.2222 - 2013-04-30

### Changed
- The updater now cleans all .bak files and folders from the app folder after running an update.

### Fixed
- Fixed the installer.
- Fixed a edge case where the installer would break if it happened to find a `SomethingRecord.php.bak` file in the app/records folder.
- Fixed some wonkiness with the field layouts when there is a space in the tab name.
- Fixed a bug where auto-redirection after logging in was broken if the config variable `loginPath` had more than one segment in it.

## 0.9.2219 - 2013-04-29 [CRITICAL]

### Added
- Added a new “Default User Group” setting to Settings → Users → Settings for choosing which group new users should be added to by default.

### Changed
- The “Forgot your password?” link is now visible right away on the Login page.
- Renamed some config settings: `validateAccountPath` to `activateAccountPath`, `validateSuccessPath` to `activateSuccessPath`, `validateFailurePath` to `activateFailurePath` and `resetPasswordPath` to `setPasswordPath`
- When attempting to log in with a user account that’s temporarily locked, the cooldown time duration now includes the number of seconds if it’s less than one minute.
- Increased the max length of entry slugs to 255 characters.

### Removed
- Removed the ability to set other users’ passwords, even if you’re an admin. All new users must now go through account activation and set their own password.

### Fixed
- Fixed a bug where a non-admin with create/administer user permissions was able to modify sensitive admin account details.
- Fixed a permissions error when checking to see if the current user has create/edit entry privileges.
- Fixed a bug where public user registration forms would allow empty password inputs.
- Fixed some template errors if the system email settings get deleted for some reason, and you go to Settings → Email.

## 0.9.2218 - 2013-04-25

### Added
- Added new `forms.time()` and `forms.dateTimeField()` macros.
- Added the `useLockWhenWritingToFile` config setting, which is taken into account whenever the `LOCK_EX` flag is set when calling `IOHelper::writeToFile()`. This fixes PHP warnings when using Craft on NFS filesystems.

### Changed
- Date and time selection now take place in separate inputs
- Date and time formats are now fully localized within date/time fields (e.g. `MM/DD/YY` for `en_us`, `DD/MM/YY` for `en_gb`).
- Date fields now have a “Show time?” setting (disabled by default).
- The Timezone setting in Settings → General now lists all known timezones, not just the ones with unique abbreviations.
- The `forms.date()` and `forms.dateField()` macros only show the date input once again.

### Fixed
- Fixed a bug where escaped HTML entities in Rich Text fields would not continue to be escaped when re-editing.
- Fixed a bug where query strings were dropped when redirecting the user back to the requested page after logging in.
- Fixed a bug where requests would not get routed to the requested controller action if the URL path was equal to the `loginPath` config setting (“login” by default).

## 0.9.2216 - 2013-04-24

### Added
- Added the ability to set the time of day within entry Post/Expiration Date settings as well as Date fields.
- Added [page break support](http://docs.buildwithcraft.com/components/fieldtypes/richtext.html#working-with-page-breaks) to the Rich Text fieldtype.
- Added support for [custom Redactor configs](http://docs.buildwithcraft.com/components/fieldtypes/richtext.html#field-settings) to the Rich Text fieldtype.
- Craft now comes with two default Redactor configs (Simple and Standard).
- Added a “Clean up HTML?” setting to the Rich Text fieldtype, which is enabled by default.
- Added the `validateAccountPath`, `validateSuccessPath`, and `validateFailurePath` config settings.

### Changed
- Asset field modals are now scrollable.
- User registration no longer requires a `username` input. If it’s not present in the POST data, Craft will default to using the email.
- Updated to Redactor 8.2.6.

### Fixed
- Fixed a bug that prevented public user registration from saving the submitted password correctly.
- Fixed a bug where user account validation was incorrectly going through the Reset Password template.
- Fixed a bug on some servers where enabling/disabling an entry would generate a SQL error.
- Fixed a bug where `craft.app.locale` was only returning `en_us` in the templates.`
- Fixed a bug where on-demand asset transforms were not taking the requested crop positions into account.
- Fixed a bug where the Get Help widget might not be able to send a support request on certain server configurations.

## 0.9.2214 - 2013-04-16

### Added
- Added crop position selection support for asset image transformations.

### Changed
- If you log in and have an existing PHP `CraftSessionId`, we no longer manually generate a new one.
- If you log out, we no longer manually destroy your PHP `CraftSessionId`.
- Remove emailFormat settings from users and htmlBody from emailMessages. If only text and no HTML is supplied for an email, we generate the HTML body automatically.
- Redirect back to settings/assets/transforms after saving an asset transformation.

### Fixed
- Fixed a bug where sending HTML emails would generate an incorrect body.
- Fixed a bug where the returnUrl was not getting saved to session when prompted to login thereby always redirecting you to the dashboard.
- Fixed a bug where cleaning an uploaded image for possible embedded PHP/JS would kill the transparency of the image for GIFs and PNGs.
- Fixed a bug where the Assets file modal could stretch beyond the size of the screen with enough fields in it.
- Fixed a bug where the config variable `overridePHPSessionLocation` was not getting respected.

## 0.9.2213 - 2013-04-10

### Added
- Added the ability to force log messages in `Craft::log()` so that they will get logged regardless of `devMode` status or log levels.

### Changed
- All updates now get their diagnostic messages logged regardless of `devMode` status.
- When adding default widgets to the dashboard, Craft takes into account if the user has create entry permissions for the Quick Post widget and update Craft permissions for the Updates widget.

### Fixed
- Fixed a bug that broke Craft on Windows.
- Fixed a bug that prevented nested section permissions from expanding until the user/group was saved and re-edited.
- Fixed a bug where users could create Quick Post widgets on the dashboard for sections that they did not have create permissions for.
- Fixed a bug where all users were being returned for all user groups on the user index page, regardless of the user group selected.
- Fixed a PHP error when passing multiple statuses into an `ElementCriteriaModel`.
- Fixed a bug where if `devMode` was enabled, `YII_DEBUG` was still set to false.
- Fixed a bug where auto-updates might fail in certain scenarios when unzipping the patch file.
- Fixed a bug where, if you had the Users package installed, but not the Publish Pro package and you were logged in with a user that had create entry permissions, you would be missing a “New Entry” button on the entry index page.

## 0.9.2211 - 2013-04-09

### Added
- Added Rackspace Cloud Files and Google Cloud Storage support to the Cloud package

### Changed
- Updates will now suppress any PHP errors when clearing the cache and compiled templates.
- A friendly error message is now displayed when creating a new user with “Require verification” checked and the email settings are invalid.
- Added .m4a and .m4v to the default list of allowed file extensions.
- Numbers are not automatically localized when set on a model, fixing a few bugs.

### Fixed
- Fixed a bug that made it impossible to edit entries in non-primary locales.
- Fixed a PHP error if `config/storage/runtime/` does not have executable permissions
- Fixed a bug that prevented users with permission to create entries but not publish them, from actually creating entries.
- Fixed a PHP error when indexing a local source with an invalid path.
- Fixed a few edge case PHP errors.

## 0.9.2205 - 2013-04-02

### Added
- Added a new Timezone setting in Settings → General for setting the system timezone.
- Added a new `postDate` parameter to `EntryCriteriaModel` (for use in `craft.entries`, etc.).

### Changed
- The system timezone is now factored into entry Post Date and Expiration Date settings before they are saved to the database.
- The system timezone is now factored into date criteria parameters, such as `craft.entries.before()`, `after()`, and `postDate()`.
- Added a `$timezone` argument to `DateTime::createFromString()`, for specifying the timezone the resulting `DateTime` object should be set to (defaults to UTC).
- Added a `$timezone` argument to `DateTime::format()`, for specifying the timezone the returned string should be set to (defaults to the system timezone).

### Fixed
- Fixed a “userId is not defined” error when creating a new entry.
- Fixed a one-click updating bug where the contents of updated files would get duplicated if the user attempts the update more than once.
- Fixed a `FeedsService` error that occurred on some servers with an `open_basedir` restriction in place.
- Fixed some errors on servers that don’t have the BCMath extension installed.

## 0.9.2204 - 2013-04-01

### Added
- Added a new `last()` function to `ElementCriteriaModel`, as an alternative to `first()`
- Added the `phpMaxMemoryLimit` config setting (defaults to 256MB), which is taken into account when updating Craft.

### Changed
- Improved the performance of the entry edit page.
- Emails are now sent with a UTF-8 character set by default.
- `craft()->fileCache->set()` now uses the duration specified by the `cacheDuration` config setting by default.
- Added OpenSSL to the installer’s list of system requirements.

### Fixed
- Fixed a bug where `{% if entry.richTextField %}` conditionals would always return true, regardless of whether the field had a value.
- Fixed a bug where the entry `order` parameter wasn’t being respected when calling `first()`.
- Fixed a bug in the `FeedsService` when the PHP DOM extension is not enabled.
- Fixed a bug that could result in file cache durations not getting set properly.
- Found and fixed a couple places that weren’t being defensive about whether a fieldtype actually exists before instantiating it.
- Fixed a PHP error that occurred on Windows when a `DateTime` object was created with an invalid timestamp.
- Fixed a bug in PclZip that would prevent unzipping on certain server configurations.
- Fixed a bug where updating might run out of memory on certain server configurations.

## 0.9.2202 - 2013-03-28

### Fixed
- Fixed an error when creating or editing a field that occurred if the Users package is installed and at least one user group had been created

## 0.9.2201 - 2013-03-27

### Added
- Added a new “Default Values” setting to Table fields, which gives you a live preview of your table field as you edit its columns, and lets you set the default rows and values.
- Added a Checkbox column type to Table fields.
- Added the ability to specify default options for the Checkboxes, Dropdown, multi-select, and Radio Buttons fieldtypes
- Added a new `forms.hidden()` Twig CP macro
- Added a new `Craft.expandPostArray()` Javascript function

### Changed
- The Checkboxes, Dropdown, Multi-select, and Radio Buttons fieldtypes now use tabular UIs for their Options settings
- Table column handles are now auto-generated in the same way all other Handle settings get auto-generated based on the Name setting.
- Auto-generated Handle settings no longer stop getting auto-generated as soon as they receive focus; they now wait until their value has actually been manually changed.
- The Table field now highlights the full text cell values when they are tabbed-into.
- Added an `initJs` config option to the `forms.editableTable()` Twig CP macro (defaults to true)
- The `forms.select()`, `multiselect()`, `checkboxGroup()`, `checkboxSelect()`, and `radioGroup()` Twig CP macros now support specifying options’ `label`s and `value`s explicitly.
- `UserModel` now has a `getName()` which returns the user’s full name, or their username as a fallback

### Fixed
- Fixed a bug that prevented numeric Table field columns from receiving input
- Fixed an error that occurred when editing an entry with pre-saved Table field data after adding a new column to the Table field
- Fixed a bug where the input names in newly-created Table field rows would get an extra “]” appended to the end.

## 0.9.2200 - 2013-03-26

### Fixed
- Fixed a bug where Links fields would always get a “field required” validation error if they marked as required.
- Fixed a bug where packages would get badged with “UNDEFINED DAYS LEFT” right after initiating their trial.
- Fixed a typo on field settings pages.

## 0.9.2199 - 2013-03-26

### Added
- Added a Table fieldtype.
- Added a Date fieldtype.
- Added 30-day free package trials.

### Changed
- Improved error reporting for the Get Help widget.
- The Get Help widget now creates and includes a new database backup in the event that “Include error logs and database backup” is checked.
- Entry post dates are now only automatically set to the current date if the entry is enabled.
- It’s no longer necessary to add `sectionId` and `slug` inputs on front-end entry edit forms.
- Added a data loss warning next to the Type setting when editing an existing field.
- The `CRAFT_LOCALE` constant can now be set to “auto”, which takes the user’s and browser’s language preferences into account.
- The `CRAFT_LOCALE` constant is now case-insensitive (so setting it to `en_US` is allowed, for example).
- Field values are now stored on a model, which takes the fieldtype’s `defineContentAttribute()` return value into account, doing its best to type-cast the value accordingly.
- Model attributes set to `AttributeType::Mixed` now auto-decode JSON values when set.
- Added an `$includeAuditColumns` param to `DbCommand::insertAll()`, `insert()`, and `update()`
- Added `DbCommand::insertOrUpdate()`
- Added `DateTime::createFromString()`.
- The `forms.date()` and `forms.dateField()` template macros now set `autocomplete` to `false` by default.
- Added a new `--configPath` option to yiic in the event that the `craft/config/` folder has been moved outside of `craft/`.

### Fixed
- Fixed a bug where the entry revision menu would show drafts and versions from the primary site locale, rather than the selected one.
- Fixed a bug where globals would show content in the site’s primary locale when `CRAFT_LOCALE` was set to a different one.
- Fixed a bug where `UserModel::getFullName()` required the Users package to be installed.
- Fixed a bug where the `paginate.currentPage` variable was getting set to a string rather than an integer.
- Fixed an unzipping bug on servers that do not have ZipArchive installed that was preventing one-click updates from running.
- Fixed a syntax error in the generated SQL when performing a database backup, preventing a database rollback from being able to run successfully.
- Fixed a PHP error that could occur in the `PluginsService` when determining the components a plugin has available.

## 0.9.2194 - 2013-03-18

### Added
- Added a new `unixSocket` DB config setting that forces the console app to ignore the database server and port settings and connect via the socket.
- Added new `requireMatchingUserAgentForSession` and `requireUserAgentAndIpForSession` config settings.

### Changed
- Added support for all of the English language locales not previously supported (e.g. `en_IE`).
- The table on Settings → Assets → Image Transforms now includes the transform handles.
- Added support for `{slug}`, `{url}`, and `{cpEditUrl}` tags in the `redirect` param for the `entries/saveEntry` controller action.
- Updated PhpMailer to version 5.2.5.
- Added attachment support to the `EmailService`.

### Fixed
- Fixed Asset indexing.
- Fixed a PHP error that occurred after deleting a field that had been used by entry versions or drafts.
- Fixed a PHP error when accessing the Globals tab when the Localize package is not installed.
- Fixed an auto-update bug for servers that don’t have ZipArchive installed.
- Fixed an auto-update bug that prevented files marked for deletion from getting deleted.
- Fixed a bug when sending HTML emails.
- Fixed a bug when determining the browser’s preferred languages when the request did not specify an Accept-Language header.
- Fixed a bug where the `model` column of the `assettransforms` table might not have the correct value.

## 0.9.2193 - 2013-03-14

### Changed
- Added a `setTransform()` function to `AssetFileModel` for applying a default transform to be used by `getUrl()`, `getWidth()`, and `getHeight()`
- AssetFileModel’s magic getter now checks if the property name matches a transform handle. If it does, a new `AssetFileModel` instance is returned, pre-set with the matched transform.
- Updated to Twig 1.12.2
- Twig now gets initialized with the `Twig_extension_StringLoader` extension, so the [`template_from_string()`](http://twig.sensiolabs.org/doc/functions/template_from_string.html) function is available to all templates.
- The `craft/` folder now comes with `.htaccess` and `web.config` files that prevent direct HTTP access in the event that the folder is in the web root.

### Fixed
- Fixed a bug where fresh installations of Craft did not get the assettransformindex table created in the database.
- Fixed a bug where Assets didn’t know what to do if a transform was requested but a transform mode was not specified. It now defaults to “crop”.
- Fixed a fatal error when setting a section to have URLs where previously it did not.
- Fixed a bug where custom section title labels were not being used on the entry index or edit pages.
- Fixed a bug where people running Craft on a custom port and in a subfolder (e.g. http://localhost:8888/subfolder) would get an extra port number appended to the end of the URL.
- Fixed a bug where `TemplatesService::namespaceInputs()` would get a little over-ambitious, altering the values of any attributes that *ended* in `name`, `id`, etc. (e.g. `data-name`).
- In Assets, fixed a bug where a file could not be replaced if it didn’t exist in the database.
- In Assets, fixed a bug where moving a file between sources would not delete it.
- In Assets, fixed JS errors where the context menu would not work for lazy-loaded page items.

## 0.9.2189 - 2013-03-12

### Added
- Added Global Set translations.
- Added locale user permissions.
- Added the `|intersect` Twig filter.
- Added support for `CRAFT_SITE_URL` constant.
- Added a `web.config` file to the `craft/` folder that prohibits HTTP traffic to the directory.

### Changed
- Asset transforms are now created on demand.
- Asset transforms can now be defined right from the templates.
- S3 asset sources now use a bucket’s subfolder as the source root.
- Improved error handling when Craft cannot connect to the database.
- Craft now adds any queued-up head/foot HTML to the page in the event that `getHeadNodes()` or `getFootNodes()` is not present in the template.
- Improved the Twig `|replace` filter.

### Fixed
- Fixed a JS bug where checking “All” wouldn’t make all the checkboxes appear checked when disabling them.
- Fixed a bug where accessing the login page before installing might give you the “Manual Update Required” template instead of the installer.
- Fixed a bug in `IOHelper::normalizePathSeperators()` that could cause a PHP Notice when `open_basedir` restrictions were in place.

## 0.9.2184 - 2013-03-08

### Changed
- CP resource URLs are now always set to https:// if the current request is served over SSL.

### Removed
- Removed the Links’ Reverse Handle setting for now.

### Fixed
- Fixed a bug where a plugin’s custom fieldtypes would not load... at all.
- Fixed a JS error that prevented Links’ modal window from functioning... at all.
- Fixed a bug when saving a section that previously supported URIs, but no longer does.

## 0.9.2181 - 2013-03-08 [CRITICAL]

### Changed
- Your credit card information now gets cleared out in Settings → Packages after the modal window has been hidden for five minutes.
- Refactored `EmailService` and added support for CC and BCC recipients.
- Increased the default timeout for calls to Craft’s web service to 30 seconds.

### Fixed
- Fixed file renaming for Assets.
- Fixed a bug where missing S3 image transforms would break file CRUD operations.
- Fixed a bug where `S3AssetSourceType` was requiring the wrong package.
- Fixed a couple of issues with the Quick Post widget if you delete the default section and create a new one.
- Fixed a CSS issue with Redactor dropdown menus when it is being displayed in a modal.
- Fixed a bug where some backup files might not get cleaned up during an auto-update.

### Security
- Security improvements.

## 0.9.2177 - 2013-03-05 [CRITICAL]

### Added
- In-app package purchasing
- Global Sets (replaces Singletons/Pages and the former Globals implementation)
- Added a “wildcard” URL routing token

### Changed
- Blocks is now [Craft](http://buildwithcraft.com)
- Major Assets UI cleanup
- Made some significant CP and site performance improvements
- Upon saving an entry, you are now redirected back to the section’s entry index by default
- `isMobileBrowser()` now accepts a boolean argument specifying whether tablets should be included in the detection (defaults to false)
- 1Password is now capable of detecting the username/password when logging in
- Improved the bundled site templates
- Overhauled PHP-based URL routing, making it possible to specify a controller/action rather than just a template
- Fixed some CSS glitches in Firefox and IE 9/10
- Some subtle CP CSS improvements
- The jQuery UI Date Picker is now included on each page of the CP

### Fixed
- Fixed several bugs related to Assets
- Fixed some bugs with entry templating
- Fixed a JS error on Settings → Fields
- Fixed a PHP error when a user had attempted to log in too many times
- Fixed a bug that prevented the Asset Sources Link setting from actually doing anything

### Security
- Overhauled how licensing works with several critical security improvements

## 0.9.2168 - 2013-03-01

### Changed
- Now including jQuery UI DatePicker on each page.
- Disabled autocomplete for Post and Expiry Date settings.

### Fixed
- Fixed a JS error when viewing Assets in list view.
- Fixed a bug where the Post and Expiry Date settings would get set to the current time of day when saving an entry.
- Fixed a bug where entries were sorted by Creation Date rather than Post Date in the Content section.

## 0.9.2167 - 2013-02-25

### Changed
- Added the ability to rename subfolders within Assets.
- `id=`, `for=`, and a couple other attributes within plugin settings HTML are no longer getting prefixed with “settings-”.

### Fixed
- Fixed a MySQL error in The Big Migration when there were orphaned rows in the `craft_links` table.
- Fixed a couple bugs with `craft.entries` and `craft.users` and entry/user statuses.
- Fixed a PHP error when deleting an entry.
- Fixed a PHP error when sending the user verification email.
- Fixed a bug where pending users didn’t have the option to reset their password when attempting to log in.
- Fixed a CSS glitch where the “check your email” password reset instructions weren’t centered on the page.
- Fixed a bug where singletons would forget their Template setting when re-edited.
- Fixed the company logo setting in Settings → General.
- Fixed a bug that prevented some Get Help widget submissions from going through.

## 0.9.2165 - 2013-02-23

### Changed
- Widgets and fieldtypes are now required to implement the new `IWidget` and `IFieldType` interfaces, rather than extend `BaseWidget` and `BaseFieldType`. (Extending those base classes will still get the job done though, as they implement their respective interfaces.)
- Blocks now resets PHP’s time limit to 2 minutes before running each DB migration during a Blocks or plugin update

### Fixed
- Fixed a bug where plugin components weren’t getting loaded
- Fixed a bug where Quick Post widgets wouldn’t show a title if their associated section doesn’t exist anymore

## 0.9.2164 - 2013-02-22

### Fixed
- Fixed an issue with The Big Migration when Blocks shares its database with another app
- Fixed a PHP error when saving a new entry

## 0.9.2163 - 2013-02-22

### Changed
- Renamed Pages to Singletons.
- Singleton settings are now managed in Settings → Singletons, rather than in Content.
- Renamed Blocks to Fields.
- Fields are now created globally, rather than per-use.
- Replaced “Blocks” tabs in section/singleton/asset/user/globals settings with “Field Layout” tabs, which feature the new Field Layout Designer for customizing which tabs and fields should show up for the given element type.
- Redesigned Settings → Languages, and renamed it to “Locales”.
- Sections and Singletons are now enabled on a per-locale basis, and have per-locale URL Format and URI settings, respectively.
- Entries can now be translated into each of their section’s locales.
- Blocks will now display content based on a `BLOCKS_LOCALE` constant, which should be defined in `index.php`. If it’s not, Blocks will default to the site’s primary (first) locale.
- Restructured the `blocks/app/` folder.
- Users can now be logged in from multiple clients at once.
- The Users CP tab is now selected when accessing /myaccount.
- Added enclosure support to the `FeedsService`.
- URL and URI validation now checks for whitespace.
- When Blocks or a plugin is manually updated and a DB migration is needed, Blocks now redirects the browser back to the URL that the user was originally requesting after the update is complete.
- Configured Twig to always recompile CP templates in the event that a new update has been updated manually and is pending its DB migration(s).
- Several service API improvements.
- Added InnoDB engine support to Blocks’ requirements list.
- Normalized the DB schema regardless of which Blocks packages are installed.
- Minor performance improvements.
- Minor CP design iterations.

### Fixed
- Fixed a SimplePie autoload error on some versions of PHP.
- Fixed a PHP error in the `FeedsService` when loading a feed that has missing or malformed item dates.
- Fixed a bug where the `userSessionDuration` and `rememberedUserSessionDuration` config settings were not getting applied properly.
- Fixed a bug where some `Blocks::log()` calls were not being flushed properly.
- Fixed an auto-update errorwhen Blocks was instructed to delete a file that it was dependent on.
- Fixed a bug where database backups had invalid SQL in some scenarios.
- Fixed a CSS glitch in the Route settings modal.

### Security
- Blocks now offers better protection against session hijacking, XSS attacks, account theft via DB read access, and other common attacks.

## 0.9.2157 - 2013-02-15

### Changed
- The `web.config` file that comes with Blocks for IIS servers now hides “index.php” from the URL
- Improved the wording of the error if `blocks/storage/` or `blocks/storage/runtime/` doesn’t exist or isn’t writable
- `blocks/config/blocks.php` has been renamed to `general.php`, however Blocks will still look for `blocks.php` as a fallback if `general.php` doesn’t exist
- Dropped the `craft_activity` table

### Fixed
- Fixed a bug on the Content, Assets, and Users indexes that prevented all links from being clicked on when the page had been scrolled down far enough to trigger fixed sidebar positioning
- Fixed an error when renaming Assets folders

## 0.9.2151 - 2013-02-07

### Added
- Added a `craft()->isConsole()` to determine if the current “request” is from a web app or a console app.

### Changed
- More Assets improvements including subfolder support, deleting folders, fixing an error where stale database records weren’t getting deleted and displaying an icon for files with no thumbnails.
- `Blocks::hasPackage()` now checks the file system *and* the database to determine if a package is installed.
- Migrations run from the console now get echo’d out to the console as well as added to `blocks.log`.
- Remove `DbHelper::getTableNames()` and use `MySqlSchema->getTableNames()` instead.

### Fixed
- Fixed a error in the [FeedsService](https://beta.blockscms.com/docs/plugins/apis/feeds.html) if the feed item’s date or updated date was null.
- Fixed a bug where the SQL generated when backing up and restoring the database during an update wasn’t being quoted properly preventing people with a `-` in their database name from running updates.
- Fixed a bug where the migrations in build 2146 might not have run on some Blocks installs.
- Fixed a bug in the updater where if a PHP fatal error occurred, the database and any files that might have been updated up until that point did not get rolled back properly.

## 0.9.2146 - 2013-02-04

### Added
- Added [craft.feeds](https://beta.blockscms.com/docs/templating/reference/variables/craft.feeds.html) for displaying RSS and Atom feeds on your website
- Added the [FeedsService](https://beta.blockscms.com/docs/plugins/apis/feeds.html)
- Added support for [plugin migrations](http://beta.blockscms.com/docs/plugins/db/migrations.html)

### Changed
- Tons of enhancements and bugfixes for iOS
- Sidebars are now hidden, and their elements moved up above the content, when the browser window is less than 768px wide
- Data tables now collapse into a single column when the browser window is too narrow to contain them horizontally
- Sidebar navigation will now get fixed positioning when the browser window has scrolled passed it
- Updated SimplePie to 1.3.1
- Beefed up Assets’ MIME type detection
- Added .ogg to the list of allowed file upload extensions
- `craft()->request->isMobileBrowser()` and `craft.request.isMobileBrowser` now return `true` for tablets
- Other minor CSS, JS, and accessibility enhancements
- Blocks now checks to make sure you’re updating from at least the last breakpoint during a manual update

### Fixed
- Fixed a MySQL error when renaming a section’s handle
- Fixed a bug that prevented Global link blocks from showing their previously-linked entries
- Fixed top-level image transformation generation for S3 asset sources
- Fixed a bug where assets uploaded to S3 would be marked as private
- Fixed a Javascript error that prevented Dashboard widgets from showing up on iPhone’s when viewing the page in portrait orientation
- Removed a 404’d request for `pages.css` from Content → Pages

## 0.9.2137 - 2013-02-01

### Changed
- Updated the mobile browser detection script
- Added `craft.request.isMobileBrowser`
- Setting the `rememberUserNameDuration` to `0` now takes effect immediately

### Fixed
- Fixed a JS error when adding new links to a Links block when there were already 3 or more links saved on the entry
- Fixed a bug where Assets was attempting to create image transformations on non-image files
- Updated the imageAreaSelect jQuery plugin to 0.9.10 for its jQuery 1.9 compatibility, fixing user photo and company logo uploading
- Fixed a bug where tapping on the field hint text on the login page on a mobile browser would do nothing

## 0.9.2136 - 2013-01-31 [CRITICAL]

### Fixed
- Fixed a bug with the release patch script that was preventing some files from getting updated
- Fixed a bug where “Show/Hide” labels weren’t getting right-aligned with password inputs when the input’s width didn’t span the full width of the container

## 0.9.2135 - 2013-01-30

### Changed
- Added breadcrumbs to all nested pages in the CP
- Added user prompts when uploading files in Assets
- Redesigned the Dev Mode strip in the CP header
- Blocks now forces local asset sources’ Path and URL settings to end with a trailing slash
- Renamed “Profile Blocks” to “Blocks”
- Blocks now respects the `charset` and `collation` DB config values when creating new tables
- Added `dropTableIfExists()`, `addPrimaryKey()`, and `dropPrimaryKey()` to `DbCommand`
- Renamed `DbHelper::normalizeIndexName()` to `normalizeDbObjectName()`
- `DbCommand->insertAll()` now accepts an empty `$vals` argument
- `DbCommand->where()`, `andWhere()`, and `orWhere()` now accept an empty `$conditions` argument
- `DbMigration` now has wrappers for most of `DbCommand`’s methods
- Added a new `yiic querygen all` command for getting the full PHP required to create a record’s table at once

### Fixed
- Fixed a bug where Links blocks wouldn’t pass the linktype-specific settings off to the linktype when displaying its block settings
- Fixed a bug where all previous links would get flushed out whenever a Links block was re-saved
- Fixed a Twig error when viewing Assets in List View, and one of the file’s last modified date is unknown
- Fixed a bug that prevented `craft.entries.before()` and `after()` from working
- Fixed support for relative asset source paths
- Fixed an error when using the `yiic querygen addForeignKeysForRecord` command on a record with relative records whose table doesn’t exist yet

## 0.9.2133 - 2013-01-28

### Changed
- Added a context menu to the Save button when editing entries, with options to save and continue editing, or save and create a new entry.
- Normal (non-submit) buttons are now white rather than blue
- Other subtle CSS tweaks

## 0.9.2131 - 2013-01-28

### Changed
- More work on the CP redesign.
- Updated jQuery UI to 1.10.0, fixing the entry date fields
- Blocks now uses the uncompressed version of jQuery UI when useCompressedJs is set to `false`

### Fixed
- Fixed a Twig syntax error in Content → Pages
- Fixed some JS errors within Settings → Assets

## 0.9.2127 - 2013-01-25

### Added
- Added access permissions for plugin CP sections

### Changed
- Redesigned the CP layout
- Redesigned the Dashboard
- CP section tabs are now defined as an array rather than plain old HTML
- Offloaded the Javascript UI code to Garnish
- Updated jQuery to 1.9.0
- Updated Redactor to 8.2.2
- Blocks will serve the uncompressed versions of jQuery and Redactor when the “useCompressedJs” config setting is `false`
- Added a new parameter to `yiic migrate create` for specifying the plugin name
- The `craft_migrations` table is now created during installation, rather than when first needed
- New users will now get the default set of Dashboard widgets the first time they visit the Dashboard

### Fixed
- Fixed a bug where passing `true` to a `BaseCriteria` class’ magic setter methods would have no effect

## 0.9.2124 - 2013-01-20

### Fixed
- Fixed a bug where a failed migration might not get logged in `blocks.log`.
- Blocks now plays nice with other applications that are sharing the same database.

## 0.9.2123 - 2013-01-18

### Added
- Added support for asset transformations with the options `scaleToFit`, `scaleAndCrop` and `stretchToFit`.
- Added the concept of a “non-patchable” update, in the case that so many files have changed, the entire `app/` folder will get downloaded and auto-updated.
- Added the ability to auto-update entire folders, not just individual files.
- Added `backupDbOnUpdate` and `restoreDbOnUpdateFailure` config vars that both default to true.
- Added the ability for the updater to auto restore the last database backup if something goes wrong during an update.
- Added support for hi-res asset thumbnails.
- POSTed passwords with the keys `password` and `newPassword` are now redacted before they are saved to the Blocks log file in devMode.
- Added new `overridePHPSessionLocation` config variable with a default of `auto` in which Blocks will attempt to determine if PHP sessions are being stored in a distributed memory caching environment (i.e. memcached).
- Added `packages` column to the `craft_info` table with a list of the currently installed packages. `Blocks::hasPackage()` now checks this before it checks `Info.php` for packages.
- Added new `tableExists()` method to `BaseRecord.php` to check to see if a record’s underlying table exists in the database or not.
- Added the concept of “Maintenance Mode” for the updater.
- Added a new `querygen` command to the CLI yiic command that generates querybuilder code for a given record.
- Added support for more mime types in `mimetypes.php`.

### Changed
- New and improved AJAXified updater. Should prevent the need for the majority of manual updates from this point forward.
- Updated to Yii 1.1.13
- Add support for attaching blocks log files and SQL backups when creating a support ticket from the control panel.
- Throw a 503 instead of a 404 from the front-end if the site is waiting on a manual database update to be completed.
- `getHeadNodes()` renamed to `getHeadHtml()` and `getFootNodes()` renamed to `getFootHtml()` in `TemplatesService`.
- Return `Twig_Markup` objects for `getHeadNodes()` and `getFootNodes()` template functions, so `|raw` isn’t necessary.
- Stop automatically appending .js and .css in `includeCssFile()` and `includeJsFile()`.
- Added `allValue` config to checkboxSelect that defaults to `*`.
- Give users a chance to log in with an account that has access to the control panel while the system is “off”.
- Database index and foreign key names are no longer MD5 hashed.
- Dropped the `$name` param from the index/foreign key methods in `DbCommand`.
- Assets validates that the source path exists and returns more accurate error messages.

### Fixed
- Fixed a bug where Blocks couldn’t log in with web hosts that were storing PHP sessions in memcached.
- Fixed a bug in two migrations that would occur if you were upgrading to the latest from a release before 0.9.2090.
- Fixed a bug when performing a rollback on an entire folder after a failed update.
- Bring the site out of maintenance mode if an error has occurred and we have successfully rolled everything back.
- Fixed a bug in `IOHelper` that would skip files that started with a `.` when getting a folder’s contents.
- Make sure `storage/runtime/` is writable very early in the request and bail if it is not.
- Several various past migration fixes.
- Only check for `unsigned` and `zerofill` for numeric column types and check for `charset` and collation` for textual column types.
- Fixed a bug when determining new migrations that need to be applied.
- Fixed a bug when using CLI yiic command and an exception was thrown.
- Fixed a bug where Blocks couldn’t log in with web hosts that were storing PHP sessions in memcached.
- Fixed a bug in two migrations that would occur if you were upgrading to the latest from a release before 0.9.2090.
- Fixed some logic errors when refreshing the bucket list for a new source in assets.

## 0.9.2117 - 2012-12-24

### Fixed
- Fixed a PHP error when accessing the CP while the site is turned off

## 0.9.2116 - 2012-12-24

### Changed
- You can now get the URL of an asset’s resized file using `{{asset.getUrl('sizeHandle')}}`
- Service name and API tweaks

### Fixed
- Fixed an auto-updating error on some versions of PHP
- Fixed an issue where CP resources were getting corrupted on delivery
- Fixed a bug where Assets link blocks would only allow you to choose files from the first asset source
- Fixed the remaining bugs around selecting content based on the current language

## 0.9.2115 - 2012-12-21

### Added
- Added RTE compatibility for Asset blocks.
- Added additional parameters to `DbCommand->createTable()` for defining whether the ID and audit columns should get added to the table.

### Changed
- CP logo changes and user profile image changes no longer reload the whole page.
- Moved `addTablePrefix()` to `DbHelper`.

### Fixed
- Twig no longer captures an underlying Exception and wraps it in its own `Twig_Error_Runtime` exception hiding the original error.
- Fixed several Assets bugs.
- Fixed a syntax error in the backup SQL generated before any database migrations are ran.

## 0.9.2114 - 2012-12-19

### Fixed
- Fixed an error when editing/creating a user group, or administering a user
- Fixed an error in the Assets section when an asset source exists but has not been indexed yet

## 0.9.2112 - 2012-12-19

### Added
- Added user permissions
- Added support for public user registration, logging in, and password resetting from the front end of the site
- Added the `loginPath`, `logoutPath`, `resetPasswordPath`, and `pageTrigger` config settings
- Added the `loginUrl` and `logoutUrl` global template variables
- Added the `requireLogin` and `requirePermission` template tags
- Added Settings → Users → Settings where you can toggle whether to allow public user registration
- Finished Amazon S3 support for Assets
- Added Settings → Assets → Sizes for defining sizes that your uploaded images should be cropped to
- Added Settings → Assets → Operations where you can manually trigger asset indexing and size updating
- Added the list view for assets

### Changed
- HTTP error templates are no longer prefixed with “error” (e.g. “404.html” rather than “error404.html”)
- All dates are now stored as `datetime` columns rather than Unix timestamps
- `AccountService` and `craft()->account` have been renamed to `AccountsService` and `craft()->accounts`
- Added a “Download” menu option for manually downloading updates even if an auto-update is available
- Blocks now backs up the database before running any database migrations in an update
- Blocks will now clear the `storage/runtime/cache/` and `storage/runtime/compiled_templates/` folders when updated
- Users are now automatically logged in after resetting their password
- CP routes provided by plugins can nowow include {handle} tokens for matching handles in URLs
- Section content tables are now named in all lowercase letters, regardless of the section’s handle’s casing

### Fixed
- Fixed an error when accessing the Assets section
- Fixed a bug that prevented entries’ Author settings from saving
- Fixed some bugs around selecting content based on the current language
- Fixed `PATH_INFO` support for Nginx

### Security
- Security improvements

## 0.9.2106 - 2012-12-03

### Added
- Added a new “Reverse Link Handle” setting to the Links blocktype for accessing reverse links
- Added a new [paginate](https://beta.blockscms.com/docs/templating/reference/tags.html#paginate) tag for paginating entries, users, and sections
- Added a new [includeHiResCss](https://beta.blockscms.com/docs/templating/reference/tags.html#includehirescss) tag for including CSS targeted at hi-res displays
- Added new `'siteRoutesSource'` config option, which when set to `'file'` tells Blocks to pull routes from `blocks/config/routes.php` and removes the Settings → Routes page

### Changed
- Included the section name in the page title when authoring a new entry
- Handles now have much more comprehensive reserved word validation
- You can now pass params directly into `craft.entries()`, `craft.sections()`, and `craft.users()` rather than exclusively through their output functions.
- Blocks now supports a new config file format, where the config files just return an array of values, rather than defining `$blocksConfig`. Config files now come with this new format out of the box.
- Beefed up the `BaseCriteria` class and simplified the criteria classes that extend it
- `ArrayHelper::stringToArray()` now returns an empty array when passing in an empty value

### Removed
- Removed Blocks’ custom `|text` filter since it was doing the same thing as Twig’s included (and better-named) `|striptags` filter

### Fixed
- Fixed a bug in the automatic updater that was causing updates to fail with newly added files.
- Fixed a bug where some HTML would get encoded in the installer if the server didn’t meet Blocks’ minimum requirements

## 0.9.2104 - 2012-11-30

### Fixed
- Fixed a regression bug introduced in 0.9.2102 that would cause entries with empty tags to not be published.
- Fixed error notification text color.

## 0.9.2103 - 2012-11-30

### Changed
- Renamed `craft.request.uri` to `craft.request.path` to better mimic the `HttpRequestService`

### Fixed
- Fixed a bug where the “System Status” setting in Settings → General wouldn’t have any effect when turning the system from Off to On

## 0.9.2102 - 2012-11-30

### Added
- The ability to toggle the System Status under General Settings
- Add the Updates widget to the dashboard by default for a new install.
- Added a lightswitch input and added `forms.lightswitch`/`forms.lightswitchField`

### Changed
- Set `UserCriteria->status` to `active` by default
- Renamed global `date` variable to `now`
- `entry.tags` is always an array now

### Fixed
- Suppress the PHP warning that is generated in `IOHelper` when checking if a file is writable and it is not
- Fixed a bug where `entry.getCpUrl()` wouldn’t bring you to the CP from the front-end
- Moved entry URIs into Blocks Core from Blocks PublishPro
- Make sure that a section has a valid URL Format before saving an entry
- Fixed several templates that were pulling in the wrong CP template when certain packages were not installed.
- Fixed a bug when decoding an Elliott response and there are no new releases

## 0.9.2101 - 2012-11-29

### Changed
- Don’t show the Suspend/Archive buttons in the user admin settings for the current user.

### Fixed
- Fixed a bug when trying to access the properties of an empty blockType that was occurring in the build 2100 migration.
- Fixed a bug that was causing performance issues on certain host configurations when detecting if `PATH_INFO` is supported and if the script name should be omitted from the URL.

## 0.9.2100 - 2012-11-28

### Added
- Auto-updating! (for updates going forward)
- Entry deletion
- Added a new `getResourcePath` hook so plugins can listen for additional resource paths

### Changed
- You no longer need to click on a section in the Content index before a “New Entry” button will show up
- Added `authorId`, `authorGroupId`, `authorGroup`, `before`, and `after` params to `craft.entries`
- `craft.entries.find` now returns entries ordered by their Post Date in descending order by default
- When deleting a section, the number of entries that will also be deleted is included in the confirmation dialog
- The “This block is translatable” setting will now always show up if the Languages package is installed
- Woff fonts are now sent with the correct mime-type header
- Vastly improved performance when Blocks is running on PHP’s built-in development server
- Cleaned up the HTTP headers sent from `craft()->request->sendFile()`
- Handles named “uri” or “url” will no longer pass validation
- Added the Fullscreen Redactor plugin to the Rich Text blocktype
- Updated Redactor to 8.2.0
- Updated Yii to 1.1.12
- Dev Mode no longer gets its own cache duration
- Added `DbHelper::parseDateParam` method for parsing date params
- The Javascript method `Blocks.postActionRequest()` now accepts a 4th parameter: an `onError()` callback function
- Updated several API methods to stop returning arrays indexed by the entity IDs, unless explicitly requested
- Records now have the ability to specify what happens when a `BELONGS_TO` related row gets deleted (e.g. CASCADE)

### Fixed
- Fixed the Updates widget
- Fixed a bug that prevented entries from showing up on the site when logged out
- Fixed a PHP error when editing a Checkboxes, Dropdown, Multi-select, or Radio Buttons block that didn’t have any options defined
- Fixed a bug where the logo cropping modal window would show up in General Settings before it was needed
- Fixed several errors when deleting things throughout the system
- Fixed a PHP error when uninstalling and reinstalling a plugin in the same request

### Security
- Added safeguards against some security vulnerabilities when uploading images

## 0.9.2094 - 2012-11-22

### Changed
- Nicer default error templates.
- Just throw a 503 exception when the site is offline rather than loading an _offline template.

### Fixed
- A blocking bug in the installer where it would throw a foreign key constraint violation.
- A bug when CP error templates include/extend another CP template.

## 0.9.2092 - 2012-11-21

### Added
- Added Plugin icon support
- Added `craft()->templates->includeHiResCss()` for including CSS targeted at hi-res displays

### Changed
- Upgraded Twig to 1.11.1

### Fixed
- Fixed some text layout issues in Firefox
- Fixed the height of filler rows in Links blocks
- Fixed image uploading (again)

## 0.9.2090 - 2012-11-20

### Added
- Added a new “Get Help” widget, for quickly posting to Blocks Support
- Gave sections a new setting for customizing the “Title” field’s label for their entries

### Changed
- Templates no longer need to use the `|raw` filter when outputting Rich Text blocks
- Handles now get auto-generated in camelCase
- Admins accessing the CP while Blocks is in Dev Mode will see a black and yellow strip across the top of the CP
- Added support for the HTML5 `autofocus` attribute to all the macros in the CP’s `_includes/forms.html` template
- The bundled htaccess file now prevents /favicon.png and /apple-touch-icon.png requests from getting routed to Blocks
- The bundled htaccess file no longer assumes that Blocks is going to be installed in the web root
- Improved the installer’s PDO driver requirement checking
- `BaseModel`’s static `populateModels()` method now always returns an array

### Removed
- Removed a couple out-of-place “entry” references

### Fixed
- Fixed page routing
- Fixed some wonkiness caused by the jQuery UI Datepicker
- Fixed various errors that occurred throughout the CP after disabling/uninstalling a plugin that provided widgets and/or blocktypes that were in use
- Fixed image uploading when Blocks is not running in Dev Mode
- Fixed a bug where unchecking the section setting “Entries in this section have their own URLs” would have no effect on save

## 0.9.2084 - 2012-11-18

### Fixed
- `phperrors.log` path to `storage/logs/phperrors.log`
- Empty `HttpException` status codes are now allowed which will resolve to the generic `error.html` template.

## 0.9.2083 - 2012-11-18

### Changed
- Changed “URL” to “URI” in the routes UI.
- Checks for PDO and `PDO_MYSQL` now display a more appropriate error message if they are not available.
- In `config/defaults/db.php`, set default collation to `utf8_unicode_ci` instead of `utf8_general_ci`.

### Fixed
- An exception that was getting thrown when adding a `UserLinkType` to a section’s blocks.

## 0.9.2081 - 2012-11-17

### Added
- Entry drafts and versions

### Changed
- Other minor improvements

### Fixed
- Fixed a bug where Blocks would route requests without any URI to the first entry in the database, when there’s no homepage set

## 0.9.2080 - 2012-11-16

### Changed
- Email setting testing can now be performed without actually saving the new email settings, via a new “Test” button

### Fixed
- Fixed a Twig error on any template using `{{ forms.textarea() }}` or `{{ forms.textareaField() }}`
- `UrlHelper::getActionUrl()` now includes the script name (e.g. `index.php`) even if Blocks is configured to omit it from URLs, so that multipart form data is not lost in POST requests

## 0.9.2079 - 2012-11-16

### Fixed
- Fixed an “unknown error” when updating Blocks

## 0.9.2078 - 2012-11-16

### Added
- Added a new `omitScriptNameInUrls` config setting
- Added `{{ craft.session.hasFlash(messageKey) }}` to the templates
- Added `UrlHelper::getCpUrl()` and `UrlHelper::getSiteUrl()` for generating URLs explicitely to the CP or front-end site.
- Added `craft()->request->getCookie($name)`
- Added a new `addTwigExtension` plugin hook

### Changed
- The CP is now accessed via a trigger segment in the URL path (“admin” by default), rather than the `BLOCKS_CP_REQUEST` constant (previously found in `admin.php`)
- Renamed the `logoutTriggerWord` config setting to `logoutTrigger`
- The `index.php` redirect in the bundled htaccess file now includes a `QSA` flag, so query strings aren’t lost in the redirect
- Plugins are now sorted by name in the sidebar and in Settings → Plugins
- `{% includeCssFile %}`, `{% includeJsFile %}`, `{% includeCssResource %}`, and `{% includeJsResource %}` tags no longer require `.css` and `.js` extensions
- Plugin hook methods must now begin with “hook”
- Moved automatic table creation/deletion for plugins into `BasePlugin->createTables()` and `dropTables()`
- Text fields in the CP are now sized using `box-sizing: border-box`, and are made to span the full width of their container using a `.fullwidth` class, rather than `<div class="textwrapper">`

### Removed
- Removed the `urlFormat` config setting in favor of the new `usePathInfo` setting
- `craft()->request->getType()` has been removed in favor of `isCpRequest()`, `isSiteRequest()`, `isResourceRequest()`, `isActionRequest()`, and `isTemplateRequest()`
- Renamed `craft()->config->getItem()` to `get()`, and the magic getter has been removed from the `ConfigService` (so `craft()->config->itemName` is no longer possible)
- Removed `urlFormat` and `useCompressedJs` settings from `blocks/config/blocks.php`

### Fixed
- Fixed URL validation for URLs that don’t have a TLD
- Got rid of that rogue “R” next to the “Delete” button when editing a route
- Fixed The Darth alignment in the Users index
- Fixed a bug where preselected entities would still show up in Add Links modals after a page refresh
- Fixed a bug where entities don’t re-appear in the Add Links modal after being deselected
- Fixed some text encoding issues on the “Can’t install Blocks” screen
- Fixed a MySQL error when deleting a plugin that has record classes representing tables that don’t exist
- `requirePostRequest()` and `requireAjaxRequest()` controller methods no longer get any special treatment when Blocks is in Dev Mode

## 0.9.2071 - 2012-11-13

### Fixed
- PHP error when saving a Links block
- PHP error when PHP < 5.3 is running; now a friendly “PHP 5.3+ is required” message displays instead. Requires that you update `index.php` and `admin.php` in your web root.

## 0.9.2068 - 2012-11-13

### Fixed
- Url route input focussing in Chrome.
- Missing package class errors.

## 0.9.2065 - 2012-11-13

### Fixed
- Entry URLs were relative to the CP URL when outputting them from within the CP
- an error when editing a page block

## 0.9.2064 - 2012-11-13

### Fixed
- Delete buttons for Sections and Content Blocks

## 0.9.2063 - 2012-11-13

### Added
- Private Beta kickoff!
