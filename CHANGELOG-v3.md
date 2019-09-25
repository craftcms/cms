# Release Notes for Craft CMS 3.x

## Unreleased

### Added
- Added `craft\helpers\ArrayHelper::whereMultiple()`
- Added `craft\helpers\ElementHelper::sourceElement()`.
- Added `craft\helpers\UrlHelper::buildQuery()`.

### Changed
- `craft\test\TestSetup::setupCraftDb()` no longer accepts a second argument. Ensure that `craft\test\Craft::$testConfig` is set before calling this function. ([#4804](https://github.com/craftcms/cms/pull/4804))
- Element arrays no longer include `hasDescendants` or `totalDescendants` keys by default. ([#4820](https://github.com/craftcms/cms/issues/4820))
- Relational fields without a specific target site will now only return related elements from the same site as the source element by default, as they did before Craft 3.2. ([#4751](https://github.com/craftcms/cms/issues/4751))
- Improved the performance of element duplication on multi-site installs.
- Edit Entry pages now get updated preview target URLs after saving a draft, in case the URLs have changed.
- Improved the performance of `craft\web\View::renderString()` for templates that don’t contain any Twig code.
- Matrix block queries no longer include blocks owned by drafts or revisions by default. ([#4790](https://github.com/craftcms/cms/issues/4790))
- The confirmation dialog that can appear after running the Asset Indexes utility no longer will close by pressing the <kbd>Esc</kbd> key or clicking outside of the modal. ([#4795](https://github.com/craftcms/cms/issues/4795))
- Section and Matrix “Propagation Method” settings now display warnings about the potential for data loss when appropriate.
- Entries’ drafts and revisions are now soft-deleted and restored along with their source elements. ([#4797](https://github.com/craftcms/cms/issues/4797))
- `craft\behaviors\DraftBehavior::getCreator()` can now return `null`.

### Removed
- Removed `craft\base\ElementInterface::getSource()`. ([#4754](https://github.com/craftcms/cms/issues/4754))

### Fixed
- Fixed an error that could occur if garbage collection was run while Craft 3.2 migrations were pending. ([#4720](https://github.com/craftcms/cms/issues/4720))
- Fixed a validation error that occurred when duplicating an entry, if the URI format was based on a custom field value. ([#4759](https://github.com/craftcms/cms/issues/4759))
- Fixed a bug where the “Publish live changes for other authors’ entries” permission was being enforced when saving another author’s entry as a new entry. ([#4758](https://github.com/craftcms/cms/issues/4758))
- Fixed a bug where `craft\helpers\UrlHelper` methods would strip out array params in the query string. ([#4778](https://github.com/craftcms/cms/issues/4778))
- Fixed a SQL error that occurred when a `{% cache %}` tag was used on a page with a 4-byte character in the URI. ([#4780](https://github.com/craftcms/cms/issues/4780))
- Fixed a bug where Craft could show a nondescript error when navigating away from a Control Panel page if an Ajax request was currently in progress. ([#4796](https://github.com/craftcms/cms/issues/4796))
- Fixed an error that occurred when editing an entry with a draft that was created by a soft-deleted user. ([#4800](https://github.com/craftcms/cms/issues/4800))
- Fixed a bug where entry revisions and drafts would be deleted when the user that created them was hard-deleted.
- Fixed a SQL error that could occur when executing an element query that had custom `JOIN` and `WHERE` clauses if the `search` param was also set. ([#4788](https://github.com/craftcms/cms/issues/4788))
- Fixed a bug where default field values weren’t being applied to Matrix blocks that were autocreated per the Min Blocks setting. ([#4806](https://github.com/craftcms/cms/issues/4806))
- Fixed Plugin Store dropdowns which were not working properly with Windows Edge browsers.
- Fixed a bug where Dropdown and Multi-select fields’ Dropdown Options settings weren’t autofocussing on the first input when adding a new row with the keyboard. ([#4823](https://github.com/craftcms/cms/issues/4823))

## 3.2.10 - 2019-08-13

### Added
- Added `craft\fields\BaseRelationField::settingsTemplateVariables()`. ([#4732](https://github.com/craftcms/cms/issues/4732))
- Added `craft\services\Search::deleteOrphanedIndexes()`.
- Added `craft\validators\UriFormatValidator::$disallowTriggers`.
- Added the `Craft.startsWith()` JavaScript method.

### Changed
- Improved garbage collection performance when hard-deleting hundreds of thousands of elements. ([#4735](https://github.com/craftcms/cms/issues/4735))
- Element queries’ `title` param will now accept a value of `'0'`.
- `craft\services\Elements::deleteElementById()` now has a `$hardDelete` argument. ([#4747](https://github.com/craftcms/cms/pull/4747))
- It’s no longer possible to save routes or URI formats that begin with the `actionTrigger` or `cpTrigger` config settings. ([#4154](https://github.com/craftcms/cms/issues/4154))
- Categories fields’ selection modals now show the site menu. ([#4749](https://github.com/craftcms/cms/issues/4749))

### Removed
- Removed `craft\records\Route`.

### Fixed
- Fixed a bug where Entry fixtures wouldn’t get unloaded. ([#4663](https://github.com/craftcms/cms/issues/4663))
- Fixed a bug where entry content wouldn’t get propagated to other sites if an entry was created and then saved before Craft had finished autosaving the draft. ([#4423](https://github.com/craftcms/cms/issues/4423))
- Fixed a bug where entry forms could miss the fact that a Matrix block had been deleted. ([#4727](https://github.com/craftcms/cms/issues/4727))
- Fixed a PHP error that could occur on environments where the Intl PHP extension was installed but the `IDNA_NONTRANSITIONAL_TO_ASCII` or `INTL_IDNA_VARIANT_UTS46` constants weren’t defined. ([#4722](https://github.com/craftcms/cms/issues/4722))
- Fixed a PHP error that could occur if a plugin was configured with settings even though it didn’t support settings. ([#4706](https://github.com/craftcms/cms/issues/4706))
- Fixed an error that occurred when a validation error occurred on an entry while it was being created or updated from a draft. ([#4733](https://github.com/craftcms/cms/issues/4733))
- Fixed an infinite recursion bug that could occur when validating circular relations. ([#4482](https://github.com/craftcms/cms/issues/4482))
- Fixed a bug where elements with a title of “0” would show their ID instead of their title in element indexes and relational fields. ([#4745](https://github.com/craftcms/cms/issues/4745))
- Fixed a bug where Craft was redirecting to the Dashboard when attempting to export elements, if the `tokenParam` config setting was set to something besides `token`. ([#4737](https://github.com/craftcms/cms/issues/4737))

## 3.2.9 - 2019-08-06

### Added
- Added the `ignorePlaceholders` element query param.
- Added the `cp.entries.edit.meta` and `cp.entries.edit.settings` template hooks to the Edit Entry page.
- Added `craft\base\ElementInterface::getSource()`.
- Added `craft\base\ElementTrait::$newSiteIds`.
- Added `craft\models\Site::$dateCreated` and `$dateUpdated`. ([#4703](https://github.com/craftcms/cms/issues/4703))

### Changed
- Improved the Control Panel header styling for mobile and on pages with long titles. ([#4548](https://github.com/craftcms/cms/issues/4548))
- Element references in the Control Panel now reveal the site the element was fetched from in their tooltips, on multi-site installs. ([#4690](https://github.com/craftcms/cms/issues/4690))
- Element editor HUDs now always show a header with the element’s site name on multi-site installs, even if the element is only editable in one site. ([#4690](https://github.com/craftcms/cms/issues/4690))
- Entry preview tokens now respect the `defaultTokenDuration` config setting, rather than always expiring after 24 hours. ([#4683](https://github.com/craftcms/cms/pull/4683))
- Improved disabled select field styling. ([#4709](https://github.com/craftcms/cms/pull/4709))

### Deprecated
- Deprecated `craft\behaviors\DraftBehavior::getSource()`.
- Deprecated `craft\behaviors\RevisionBehavior::getSource()`.

### Fixed
- Fixed a bug where elements listed in a Structure view could be missing their descendant toggles even if all of their descendants were disabled. ([#4685](https://github.com/craftcms/cms/issues/4685))
- Fixed a bug where element CSV exports were limited to 50 elements if no limit was set. ([#4692](https://github.com/craftcms/cms/issues/4692))
- Fixed a 400 error that occurred when submitting an entry form that didn’t have an `entryId` param. ([#4693](https://github.com/craftcms/cms/issues/4693))
- Fixed a bug where `craft\base\Element::getDescendants()` and other structure methods could return the wrong results when called on a draft. ([#4694](https://github.com/craftcms/cms/issues/4694))
- Fixed a bug where Matrix blocks weren’t getting duplicated to newly-enabled sites for elements if the field’s Propagation Method setting wasn’t set to “Save blocks to all sites the owner element is saved in”. ([#4698](https://github.com/craftcms/cms/issues/4698))
- Fixed a bug where the Database Backup could result in a 404 error on load-balanced environments. ([#4699](https://github.com/craftcms/cms/issues/4699))
- Fixed a bug where the “Current” entry revision link wouldn’t always work. ([#4705](https://github.com/craftcms/cms/issues/4705))
- Fixed a bug where the `craft\services\Search::EVENT_AFTER_SEARCH` event wasn’t always firing. ([#4710](https://github.com/craftcms/cms/issues/4710))
- Fixed a bug where `craft\services\Users::purgeExpiredPendingUsers()` was attempting to delete already-trashed users.

### Security
- Fixed an XSS vulnerability.

## 3.2.8 - 2019-07-30

### Added
- Element indexes with unsaved drafts now show a “Drafts” option in the status menu.
- Added the `utils/fix-element-uids` command, which ensures all elements have unique UIDs. ([#4653](https://github.com/craftcms/cms/issues/4653))

### Fixed
- Fixed a bug where it wasn’t possible to create a homepage Single section if a prior entry revisions’ URI had been set to `__home__`. ([#4657](https://github.com/craftcms/cms/issues/4657))
- Fixed a bug where the user deletion confirmation dialog was including revisions and drafts when counting entries for the content summary.
- Fixed an error that occurred when deleting a user, if another user had been chosen to inherit their content. ([#4670](https://github.com/craftcms/cms/issues/4670))
- Fixed a bug where users could be warned about losing unsaved changes when updating an entry from a draft, while the draft was being autosaved. ([#4614](https://github.com/craftcms/cms/issues/4614))
- Fixed a bug where Categories fields weren’t always getting updated when a category they were related to got moved under another category. ([#4672](https://github.com/craftcms/cms/issues/4672))
- Fixed an error that occurred on the Settings → Routes page, if one of the routes didn’t have a URI pattern. ([#4676](https://github.com/craftcms/cms/issues/4676))
- Fixed some styling and behavior issues on the Settings → Routes page.

## 3.2.7 - 2019-07-25

### Fixed
- Fixed an error where it wasn’t possible to scale SVGs using only height. ([#4643](https://github.com/craftcms/cms/pull/4643))
- Fixed a bug where the content area of some Control Panel pages weren’t getting any bottom padding. ([#4644](https://github.com/craftcms/cms/issues/4644))
- Fixed a bug where installing a plugin immediately after installing Craft from the console could corrupt the project config if `useProjectConfigFile` was enabled. ([#3870](https://github.com/craftcms/cms/issues/3870))
- Fixed a bug where entry forms could overlook changes made to Categories fields. ([#4648](https://github.com/craftcms/cms/issues/4648))
- Fixed a bug where element search indexes weren’t being updated right away after an element was created or updated from an element editor HUD.
- Fixed a bug where back-end slug validation wasn’t working correctly for slugs with some unicode characters. ([#1535](https://github.com/craftcms/cms/issues/1535))
- Fixed a bug where Craft was attempting to delete template caches even when saving a draft or revision.

## 3.2.6 - 2019-07-23

### Changed
- When enabling a new site for a Single section, Craft now uses the primary site’s content as the starting point for the new site’s content, if the section was already enabled for it.
- Swapped the position of the “Save as a Draft” and “Save Entry” buttons. ([#4622](https://github.com/craftcms/cms/issues/4622))
- `craft\helpers\DateTimeHelper::toDateTime()` now supports arrays created from `DateTime` objects. ([#4627](https://github.com/craftcms/cms/issues/4627))
- Plugin license key inputs are no longer limited to 29 characters, to make room for long environment variable names. ([#4393](https://github.com/craftcms/cms/issues/4393))
- Updated Imagine to 1.2.2.1.

### Fixed
- Fixed a bug where Craft could load the same JavaScript and CSS files multiple times when opening element editor HUDs. ([#4620](https://github.com/craftcms/cms/issues/4620))
- Fixed a bug where each animated GIF frame would still be parsed when generating a thumbnail, even if the `transformGifs` setting was set to `false`. ([#4588](https://github.com/craftcms/cms/issues/4588))
- Fixed a bug where back-end slug validation wasn’t working correctly for slugs with unicode characters. ([#4628](https://github.com/craftcms/cms/issues/4628))
- Fixed a bug where it wasn’t possible to create new entries if the section handle matched the `pageTrigger` config setting, and the `pageTrigger` config setting had a trailing slash. ([#4631](https://github.com/craftcms/cms/issues/4631))
- Fixed a bug where the `sections.previewTargets` database column was getting created as a `varchar` instead of `text` column for new Craft installs. ([#4638](https://github.com/craftcms/cms/issues/4638))

### Security
- Fixed a bug where the `preserveExifData` config setting wasn’t being respected on image upload.

## 3.2.5.1 - 2019-07-19

### Fixed
- Fixed an error that occurred if a plugin license key was set to an environment variable, which was set to an invalid key. ([#4604](https://github.com/craftcms/cms/issues/4604))
- Fixed an error that prevented image thumbnails from generating in the Control Panel when using ImageMagick. ([#4609](https://github.com/craftcms/cms/issues/4609))

## 3.2.5 - 2019-07-19

### Added
- Added `craft\services\Elements::getPlaceholderElements()`.

### Changed
- If an invalid entry draft or revision edit URL is accessed, but the source entry does exist, Craft now redirects the browser to the source entry’s edit page. ([#4574](https://github.com/craftcms/cms/issues/4574))
- Preview requests now include the previewed entry in element queries even if the `status`, `drafts`, or `revisions` parameters are set to exclude it. ([#4581](https://github.com/craftcms/cms/issues/4581))
- Back-end slug generation now follows the same rules as JavaScript. ([#4607](https://github.com/craftcms/cms/issues/4607))
- Unsaved entry drafts now get assigned a new ID when they are fully saved, so they are treated as new elements. ([#4589](https://github.com/craftcms/cms/issues/4589))

### Fixed
- Fixed some bugs with the “Save Entry” menu options, when editing an unsaved draft. ([#4614](https://github.com/craftcms/cms/issues/4614))
- Fixed a bug where Craft could forget which site was being edited when updating an entry from a draft. ([#4615](https://github.com/craftcms/cms/issues/4615))

## 3.2.4.1 - 2019-07-17

### Fixed
- Fixed an error that occurred when attempting to share a disabled entry. ([#4596](https://github.com/craftcms/cms/issues/4596))
- Fixed a bug where new Email and URL cells in Table fields weren’t getting the correct input type. ([#4595](https://github.com/craftcms/cms/issues/4595))

## 3.2.4 - 2019-07-17

### Changed
- Brought back the “Preview” button for the Current revision of entries, which now creates a draft before activating the entry preview. ([#4584](https://github.com/craftcms/cms/issues/4584))
- The “Save as a Draft” button now creates the draft over Ajax, when it’s not the primary submit button for the page.
- When Craft isn’t able to sync incoming `project.yaml` changes due to schema version conflicts, Craft now lists which packages are conflicting.. ([#4568](https://github.com/craftcms/cms/issues/4568))

### Fixed
- Fixed a JavaScript error that could occur after uploading a file directly onto an Assets field when editing the Current revision of an entry.
- Fixed a bug where draft forms could become unresponsive if the user attempted to navigate away from the page or submit the form in the middle of an autosave. ([#4578](https://github.com/craftcms/cms/issues/4578))
- Fixed a SQL error that could occur when passing `:empty:` or `:notempty:` to a relational field’s element query param. ([#4529](https://github.com/craftcms/cms/issues/4529))
- Fixed a bug where Number fields weren’t getting set to their default values for new entries. ([#4586](https://github.com/craftcms/cms/issues/4586))
- Fixed a bug query string parameters were getting URL-encoded when applied to generated pagination URLs.
- Fixed a bug where Single entries had the option to be duplicated or deleted. ([#4590](https://github.com/craftcms/cms/issues/4590))

## 3.2.3 - 2019-07-16

### Added
- Added `craft\controllers\EntriesController::actionDuplicateEntry()`.
- Added `craft\web\UrlManager::setMatchedElement()`.

### Changed
- Craft no longer creates drafts automatically when editing entries. The user must click a “Save as a Draft” button to create one. ([#4549](https://github.com/craftcms/cms/issues/4549))
- Entries are now immediately savable, whether or not any changes were made. ([#4535](https://github.com/craftcms/cms/issues/4535))
- The “Save Entry” button now redirects the user to the Entries index page. ([#4575](https://github.com/craftcms/cms/issues/4575))
- Brought back the “Save and continue editing” and “Save and add another” options for entries.
- It’s no longer possible to preview entries’ Current revision. A draft must be created first.

### Fixed
- Fixed a bug where it wasn’t possible to delete Matrix blocks if Min Blocks and Max Blocks were set to the same value, and an element already had more than that many blocks. ([#4562](https://github.com/craftcms/cms/issues/4562))
- Fixed a bug where `craft\web\UrlManager::getMatchedElement()` could return the incorrect result on preview requests. ([#4542](https://github.com/craftcms/cms/issues/4542))
- Fixed an error that occurred on the Settings → Email page if email settings were missing from the project config. ([#4552](https://github.com/craftcms/cms/issues/4552))
- Fixed a bug where it wasn’t possible to toggle site-specific entry statuses when editing drafts. ([#4577](https://github.com/craftcms/cms/issues/4577))

## 3.2.2 - 2019-07-14

### Added
- Added `craft\helpers\ElementHelper::isTempSlug()`.
- Added `craft\helpers\ElementHelper::tempSlug()`.
- Added `craft\helpers\UrlHelper::removeParam()`.

### Changed
- Craft no longer ensures a recent revision exists before creating a draft for an element.
- Element exports are limited to CSV files now, to avoid the GD requirement imposed by the PHPSpreadsheet library. ([#4553](https://github.com/craftcms/cms/issues/4553))

### Fixed
- Fixed a bug where multi-site element queries with the `unique` and `offset` params set weren’t returning any results. ([#4560](https://github.com/craftcms/cms/issues/4560))
- Fixed an error that could occur when creating a draft. ([#4515](https://github.com/craftcms/cms/issues/4515))
- Fixed a bug where Craft wasn’t generating a new slug for entries that were saved with a blank Slug field. ([#4518](https://github.com/craftcms/cms/issues/4518))
- Fixed a bug where disabled select options could lose their disabled text styling in Firefox. ([#4526](https://github.com/craftcms/cms/issues/4526))
- Fixed a bug where entry forms could miss the fact that a file had been uploaded to an Assets field. ([#4534](https://github.com/craftcms/cms/issues/4534))
- Fixed a bug where selecting “Create a new child entry” in a Structure section on a multi-site install would result in a 404 error. ([#4541](https://github.com/craftcms/cms/issues/4541))
- Fixed a bug where it wasn’t possible to set test-specific config settings. ([#4539](https://github.com/craftcms/cms/pull/4539))
- Fixed an error that occurred when exporting elements if Limit was set to `0`. ([#4547](https://github.com/craftcms/cms/issues/4547))
- Fixed a bug where the `{% paginate %}` tag wouldn’t generate links to the first page correctly when using query string pagination. ([#4550](https://github.com/craftcms/cms/issues/4550))
- Fixed an error that occurred when indexing assets from a console request, if no volumes were defined yet. ([#2798](https://github.com/craftcms/cms/issues/2798))
- Fixed a bug where the “Delete” link could show up in the draft meta HUD for unsaved drafts. ([#4557](https://github.com/craftcms/cms/issues/4557))

## 3.2.1 - 2019-07-11

### Added
- Added `craft\console\Request::getIsPreview()`.
- Added `craft\web\Request::getIsPreview()`.

### Changed
- If a draft can’t be saved, an alert icon is now shown in the Control Panel header, which can be clicked on to reveal more information.
- Element revisions no longer store snapshot data.

### Fixed
- Fixed a bug where Feed widget items weren’t getting hyperlinked.
- Fixed a bug where the `app/migrate` controller wasn’t applying new `project.yaml` changes if there were no pending migrations.
- Fixed a SQL error that could occur when saving an entry or entry draft. ([#4508](https://github.com/craftcms/cms/issues/4508))
- Fixed a bug where Assets fields set to restrict uploads to a single folder could have empty selector modals. ([#4522](https://github.com/craftcms/cms/issues/4522))
- Fixed an error that could occur if a template was accessing the deprecated `locale` property of an element query, but `siteId` wasn’t set to an integer. ([#4531](https://github.com/craftcms/cms/issues/4531))
- Fixed a bug where users without the “Publish live changes” permission for a section weren’t able to create new entries. ([#4528](https://github.com/craftcms/cms/issues/4529))
- Fixed a PHP error that could occur when uploading files to Assets fields on the front-end. ([#4382](https://github.com/craftcms/cms/issues/4382))
- Fixed a bug where elements listed in a Structure view could show descendant toggles even if they had no descendants. ([#4504](https://github.com/craftcms/cms/issues/4504))
- Fixed a backwards compatibility issue. ([#4523](https://github.com/craftcms/cms/issues/4523))

## 3.2.0 - 2019-07-09

> {warning} If you’ve ever run the `project-config/rebuild` command, it’s highly recommended that you run it again with Craft 3.1.34.2, before updating to Craft 3.2.

> {warning} Custom login controllers must now explicitly set their `$allowAnonymous` values to include `self::ALLOW_ANONYMOUS_OFFLINE` if they wish to be available when the system is offline.

> {tip} If you have Super Table or Neo installed, you should update those **at the same time** as Craft, to avoid unnecessary search index jobs from being added to the queue.

### Added
- All element types now have the option to support drafts and revisions.
- Drafts are now autocreated when content is modified, and autosaved whenever the content changes. ([#1034](https://github.com/craftcms/cms/issues/1034))
- Drafts and revisions now store content across all sites supported by the element. ([#2669](https://github.com/craftcms/cms/issues/2669))
- Content previewing is now draft-based, and drafts are stored as specialized elements, so it’s no longer necessary to add special cases in templates for preview requests. ([#1787](https://github.com/craftcms/cms/issues/1787), [#2801](https://github.com/craftcms/cms/issues/2801))
- Sections now have a “Preview Targets” setting when running Craft Pro, which can be used to configure additional locations that entries can be previewed from. ([#1489](https://github.com/craftcms/cms/issues/1489))
- Sections now have a “Propagation Method” setting, enabling entries to only be propagated to other sites in the same site group, or with the same language. ([#3554](https://github.com/craftcms/cms/issues/3554))
- Matrix fields now have a “Propagation Method” setting, enabling blocks to only be propagated to other sites in the same site group, or with the same language. ([#3554](https://github.com/craftcms/cms/issues/3554))
- Single entries now have editable slugs. ([#3368](https://github.com/craftcms/cms/issues/3368))
- Headless content previewing is now possible by forwarding request tokens off to content API requests. ([#1231](https://github.com/craftcms/cms/issues/1231))
- Preview iframes are now created with a `src` attribute already in place, improving SPA support. ([#2120](https://github.com/craftcms/cms/issues/2120))
- Entry “Share” buttons are now visible on mobile. ([#4408](https://github.com/craftcms/cms/issues/4408))
- Added the “Temp Uploads Location” system setting (available from Settings → Assets → Settings), which makes it possible to choose the volume and path that temporary asset uploads should be stored. ([#4010](https://github.com/craftcms/cms/issues/4010))
- Added the `maxRevisions` config setting. ([#926](https://github.com/craftcms/cms/issues/926))
- Added the `purgeUnsavedDraftsDuration` config setting, which determines how long unsaved drafts should be allowed to exist before getting deleted via garbage collection.
- Added the “Edit images” permission. ([#3349](https://github.com/craftcms/cms/issues/3349))
- Added the “Impersonate users” permission. ([#3501](https://github.com/craftcms/cms/issues/3501))
- Added the `drafts`, `draftId`, `draftOf`, `draftCreator`, `revisions`, `revisionId`, `revisionOf`, and `revisionCreator` element query params.
- The `site` element query params now support passing multiple site handles, or `'*'`, to query elements across multiple sites at once. ([#2854](https://github.com/craftcms/cms/issues/2854))
- Relational fields now have a “Validate related elements” setting, which ensures that the related elements pass validation before the source element can be saved with them selected. ([#4095](https://github.com/craftcms/cms/issues/4095))
- Table fields can now have Dropdown, Email, and URL columns. ([#811](https://github.com/craftcms/cms/issues/811), [#4180](https://github.com/craftcms/cms/pull/4180))
- Dropdown and Multi-select fields can now have optgroups. ([#4236](https://github.com/craftcms/cms/issues/4236))
- Date/Time, Dropdown, Lightswitch, Number, and Radio Buttons fields are now listed as sort options in element indexes. ([#2818](https://github.com/craftcms/cms/issues/2818))
- Asset, category, entry, and user indexes can now have “UID” columns. ([#4433](https://github.com/craftcms/cms/issues/4433))
- Added the `unique` element query param, which can be used to prevent duplicate elements when querying elements across multiple sites.
- Added the `preferSites` element query param, which can be used to set the preferred sites that should be used for multi-site element queries, when the `unique` param is also enabled.
- Element index pages are now paginated for non-Structure views. ([#818](https://github.com/craftcms/cms/issues/818))
- Element index pages now have an “Export…” button that will export all of the elements in the current view (across all pages) or up to a custom limit, in either CSV, XLS, XLSX, or ODS format. ([#994](https://github.com/craftcms/cms/issues/994))
- Added the `{% dd %}` Twig tag. ([#4399](https://github.com/craftcms/cms/issues/4399))
- Added the `attr()` Twig function, which can generate a list of HTML/XML attributes. ([#4237](https://github.com/craftcms/cms/pull/4237))
- Added the `|withoutKey` Twig filter.
- Added the `resave/matrix-blocks` console command.
- The `index-assets/*` commands now support a `--create-missing-assets=0` option, which prevents Craft from creating asset records when they don’t exist yet, and offers an opportunity to fix the location of any asset records that are missing their associated files, when the filename matches one of the files missing an index.
- Added the `mailer/test` command. ([#4020](https://github.com/craftcms/cms/issues/4020))
- Added the `tests/setup` command, which generates a test suite for the current Craft project.
- Jobs can now set progress labels, which will be shown below their description and progress bar in the queue HUD. ([#1931](https://github.com/craftcms/cms/pull/1931))
- Added the `_layouts/element` template, which can be extended by element edit pages that wish to support drafts, revisions, and content previewing.
- Added the `_special/sitepicker` template.
- It’s now possible for plugins and modules to define custom actions on console controllers.
- Added a testing framework for Craft and plugins, powered by Codeception. ([#3382](https://github.com/craftcms/cms/pull/3382), [#1485](https://github.com/craftcms/cms/issues/1485), [#944](https://github.com/craftcms/cms/issues/944))
- Added `craft\base\ApplicationTrait::getInstalledSchemaVersion()`.
- Added `craft\base\BlockElementInterface`.
- Added `craft\base\Element::EVENT_AFTER_PROPAGATE`.
- Added `craft\base\Element::EVENT_REGISTER_PREVIEW_TARGETS`.
- Added `craft\base\Element::previewTargets()`.
- Added `craft\base\ElementInterface::afterPropagate()`.
- Added `craft\base\ElementInterface::getCurrentRevision()`.
- Added `craft\base\ElementInterface::getIsDraft()`.
- Added `craft\base\ElementInterface::getIsRevision()`.
- Added `craft\base\ElementInterface::getIsUnsavedDraft()`.
- Added `craft\base\ElementInterface::getPreviewTargets()`.
- Added `craft\base\ElementInterface::getSourceId()`.
- Added `craft\base\ElementInterface::getSourceUid()`.
- Added `craft\base\ElementInterface::getUiLabel()`, which is now used to define what an element will be called in the Control Panel. ([#4211](https://github.com/craftcms/cms/pull/4211))
- Added `craft\base\ElementInterface::pluralDisplayName()`, which element type classes can use to define the plural of their display name.
- Added `craft\base\ElementInterface::setRevisionCreatorId()`.
- Added `craft\base\ElementInterface::setRevisionNotes()`.
- Added `craft\base\ElementTrait::$dateDeleted`. ([#4493](https://github.com/craftcms/cms/issues/4493))
- Added `craft\base\ElementTrait::$draftId`.
- Added `craft\base\ElementTrait::$hardDelete`.
- Added `craft\base\ElementTrait::$previewing`.
- Added `craft\base\ElementTrait::$propagateAll`.
- Added `craft\base\ElementTrait::$revisionId`.
- Added `craft\base\Field::EVENT_AFTER_ELEMENT_PROPAGATE`.
- Added `craft\base\Field::getSortOption()`.
- Added `craft\base\FieldInterface::afterElementPropagate()`.
- Added `craft\base\FieldInterface::valueType()`. ([#3894](https://github.com/craftcms/cms/issues/3894))
- Added `craft\base\SortableFieldInterface`, which can be implemented by field classes that should be sortable in element indexes.
- Added `craft\behaviors\DraftBehavior`.
- Added `craft\behaviors\RevisionBehavior`.
- Added `craft\console\CallableAction`.
- Added `craft\console\Controller`.
- Added `craft\console\controllers\ResaveController::saveElements()`.
- Added `craft\console\ControllerTrait`.
- Added `craft\console\Request::getToken()`.
- Added `craft\controllers\PreviewController`.
- Added `craft\errors\MissingAssetException`.
- Added `craft\events\BatchElementActionEvent`.
- Added `craft\events\DefineConsoleActionsEvent`.
- Added `craft\events\ElementQueryEvent`.
- Added `craft\events\RegisterPreviewTargetsEvent`.
- Added `craft\events\RevisionEvent`.
- Added `craft\helpers\Component::validateComponentClass()`.
- Added `craft\helpers\ElementHelper::isDraftOrRevision()`.
- Added `craft\helpers\ElementHelper::rootElement()`.
- Added `craft\models\Section::$propagationMethod`.
- Added `craft\queue\jobs\UpdateSearchIndex`.
- Added `craft\services\Drafts`, accessible via `Craft::$app->drafts`.
- Added `craft\services\Elements::propagateElements()` along with `EVENT_BEFORE_PROPAGATE_ELEMENTS`, `EVENT_AFTER_PROPAGATE_ELEMENTS`, `EVENT_BEFORE_PROPAGATE_ELEMENT`, and `EVENT_AFTER_PROPAGATE_ELEMENT` events. ([#4139](https://github.com/craftcms/cms/issues/4139))
- Added `craft\services\Elements::resaveElements()` along with `EVENT_BEFORE_RESAVE_ELEMENTS`, `EVENT_AFTER_RESAVE_ELEMENTS`, `EVENT_BEFORE_RESAVE_ELEMENT`, and `EVENT_AFTER_RESAVE_ELEMENT` events. ([#3482](https://github.com/craftcms/cms/issues/3482))
- Added `craft\services\Matrix::duplicateBlocks()`.
- Added `craft\services\Matrix::getSupportedSiteIdsForField()`.
- Added `craft\services\Revisions`, accessible via `Craft::$app->revisions`.
- Added `craft\services\Users::canImpersonate()`.
- Added `craft\web\Request::getIsLoginRequest()` and `craft\console\Request::getIsLoginRequest()`.
- Added `craft\web\UrlManager::$checkToken`.
- Added the `Craft.isSameHost()` JavaScript method.
- Added the `Craft.parseUrl()` JavaScript method.
- Added the `Craft.randomString()` JavaScript method.
- Added the `Craft.DraftEditor` JavaScript class.
- Added the `Craft.Preview` JavaScript class.

### Changed
- Relational fields are now capable of selecting elements from multiple sites, if they haven’t been locked down to only related elements from a single site. ([#3584](https://github.com/craftcms/cms/issues/3584))
- Element selector modals now always show source headings, and list sources in the configured order. ([#4494](https://github.com/craftcms/cms/issues/4494))
- Reference tags can now specify the site to load the element from. ([#2956](https://github.com/craftcms/cms/issues/2956))
- Improved the button layout of Edit Entry pages. ([#2325](https://github.com/craftcms/cms/issues/2325))
- Improved the performance of saving elements.
- The Control Panel now shows the sidebar on screens that are at least 1,000 pixels wide. ([#4079](https://github.com/craftcms/cms/issues/4079))
- The `_layouts/cp` template now supports a `showHeader` variable that can be set to `false` to remove the header.
- The `_layouts/cp` Control Panel template now supports a `footer` block, which will be output below the main content area.
- Renamed `craft\helpers\ArrayHelper::filterByValue()` to `where()`.
- Anonymous/offline/Control Panel access validation now takes place from `craft\web\Controller::beforeAction()` rather than `craft\web\Application::handleRequest()`, giving controllers a chance to do things like set CORS headers before a `ForbiddenHttpException` or `ServiceUnavailableHttpException` is thrown. ([#4008](https://github.com/craftcms/cms/issues/4008))
- Controllers can now set `$allowAnonymous` to a combination of bitwise integers `self::ALLOW_ANONYMOUS_LIVE` and `self::ALLOW_ANONYMOUS_OFFLINE`, or an array of action ID/bitwise integer pairs, to define whether their actions should be accessible anonymously even when the system is offline.
- Improved the error message when Project Config reaches the maximum deferred event count.
- Craft now deletes expired template caches as part of its garbage collection routine.
- Craft no longer warns about losing unsaved changes when leaving the page while previewing entries, if the changes were autosaved. ([#4439](https://github.com/craftcms/cms/issues/4439))
- `fieldValues` is a now reserved field handle. ([#4453](https://github.com/craftcms/cms/issues/4453))
- Improved the reliability of `craft\helpers\UrlHelper::rootRelativeUrl()` and `cpUrl()`.
- `craft\base\ElementInterface::eagerLoadingMap()` and `craft\base\EagerLoadingFieldInterface::getEagerLoadingMap()` can now return `null` to opt out of eager-loading. ([#4220](https://github.com/craftcms/cms/pull/4220))
- `craft\db\ActiveRecord` no longer sets the `uid`, `dateCreated`, or `dateUpdated` values for new records if they were already explicitly set.
- `craft\db\ActiveRecord` no longer updates the `dateUpdated` value for existing records if nothing else changed or if `dateUpdated` had already been explicitly changed.
- `craft\helpers\UrlHelper::siteUrl()` and `url()` will now include the current request’s token in the generated URL’s query string, for site URLs.
- `craft\events\MoveElementEvent` now extends `craft\events\ElementEvent`. ([#4315](https://github.com/craftcms/cms/pull/4315))
- `craft\queue\BaseJob::setProgress()` now has a `$label` argument.
- `craft\queue\jobs\PropagateElements` no longer needs to be configured with a `siteId`, and no longer propagates elements to sites if they were updated in the target site more recently than the source site.
- `craft\queue\QueueInterface::setProgress()` now has a `$label` argument.
- `craft\services\Assets::getUserTemporaryUploadFolder()` now returns the current user’s temporary upload folder by default if no user is provided.
- `craft\services\Elements::deleteElement()` now has a `$hardDelete` argument.
- `craft\services\Elements::deleteElement()` now has a `$hardDelete` argument. ([#3392](https://github.com/craftcms/cms/issues/3392))
- `craft\services\Elements::getElementById()` now has a `$criteria` argument.
- `craft\services\Elements::propagateElement()` now has a `$siteElement` argument.
- `craft\services\Elements::saveElement()` now preserves existing elements’ current `dateUpdated` value when propagating or auto-resaving elements.
- `craft\services\Elements::saveElement()` now preserves the `uid`, `dateCreated`, and `dateUpdated` values on new elements if they were explicitly set. ([#2909](https://github.com/craftcms/cms/issues/2909))
- `craft\services\Elements::setPlaceholderElement()` now throws an exception if the element that was passed in doesn’t have an ID.
- `craft\services\Matrix::saveField()` is no longer is responsible for duplicating blocks from other elements.
- `craft\web\twig\variables\CraftVariable` no longer triggers the `defineComponents` event. ([#4416](https://github.com/craftcms/cms/issues/4416))
- `craft\web\UrlManager::setRouteParams()` now has a `$merge` argument, which can be set to `false` to completely override the route params.
- It’s now possible to pass a `behaviors` key to the `$newAttributes` argument of `craft\services\Elements::duplicateElement()`, to preattach behaviors to the cloned element before it’s saved.

### Removed
- Removed the Search Indexes utility. ([#3698](https://github.com/craftcms/cms/issues/3698))
- Removed the `--batch-size` option from `resave/*` actions.
- Removed the `craft.entryRevisions` Twig component.
- Removed `craft\controllers\EntriesController::actionPreviewEntry()`.
- Removed `craft\controllers\EntriesController::actionShareEntry()`.
- Removed `craft\controllers\EntriesController::actionViewSharedEntry()`.
- Removed `craft\events\VersionEvent`.
- Removed `craft\records\Entry::getVersions()`.
- Removed `craft\records\EntryDraft`.
- Removed `craft\records\EntryVersion`.
- Removed `craft\services\EntryRevisions::saveDraft()`.
- Removed `craft\services\EntryRevisions::publishDraft()`.
- Removed `craft\services\EntryRevisions::deleteDraft()`.
- Removed `craft\services\EntryRevisions::saveVersion()`.
- Removed `craft\services\EntryRevisions::revertEntryToVersion()`.
- Removed the `Craft.EntryDraftEditor` JavaScript class.

### Deprecated
- Deprecated the `ownerSite` and `ownerSiteId` Matrix block query params.
- Deprecated `craft\controllers\EntriesController::EVENT_PREVIEW_ENTRY`.
- Deprecated `craft\controllers\LivePreviewController`.
- Deprecated `craft\elements\MatrixBlock::$ownerSiteId`.
- Deprecated `craft\events\DefineComponentsEvent`.
- Deprecated `craft\helpers\ArrayHelper::filterByValue()`. Use `where()` instead.
- Deprecated `craft\models\BaseEntryRevisionModel`.
- Deprecated `craft\models\EntryDraft`.
- Deprecated `craft\models\EntryVersion`.
- Deprecated `craft\models\Section::$propagateEntries`. Use `$propagationMethod` instead.
- Deprecated `craft\services\Assets::getCurrentUserTemporaryUploadFolder()`.
- Deprecated `craft\services\EntryRevisions`.
- Deprecated `craft\web\Request::getIsLivePreview()`.
- Deprecated `craft\web\Request::getIsSingleActionRequest()` and `craft\console\Request::getIsSingleActionRequest()`.
- Deprecated the `Craft.LivePreview` JavaScript class.

### Fixed
- Fixed a bug where `craft\helpers\UrlHelper` methods could add duplicate query params on generated URLs.
- Fixed a bug where Matrix blocks weren’t getting duplicated for other sites when creating a new element. ([#4449](https://github.com/craftcms/cms/issues/4449))

## 3.1.34.3 - 2019-08-21

### Fixed
- Fixed a bug where the `project-config/rebuild` command wasn’t discarding unused user groups or user field layouts in the project config. ([#4781](https://github.com/craftcms/cms/pull/4781))

## 3.1.34.2 - 2019-07-23

### Fixed
- Fixed a bug where the `project-config/rebuild` command was discarding email and user settings.

## 3.1.34.1 - 2019-07-22

### Fixed
- Fixed a bug where the `project-config/rebuild` command was ignoring entry types that didn’t have a field layout. ([#4600](https://github.com/craftcms/cms/issues/4600))

## 3.1.34 - 2019-07-09

### Changed
- The `project-config/rebuild` command now rebuilds the existing project config wherever possible, instead of merging database data with the existing project config.

## 3.1.33 - 2019-07-02

### Added
- Added `craft\base\ApplicationTrait::saveInfoAfterRequest()`.

### Changed
- Craft no longer strips some punctuation symbols from slugs.
- Improved the performance of saving project config updates. ([#4459](https://github.com/craftcms/cms/issues/4459))
- Improved the performance of saving fields. ([#4459](https://github.com/craftcms/cms/issues/4459))
- The `craft update` command no longer updates Craft or plugins if not specified.

### Removed
- Removed `craft\services\ProjectConfig::saveDataAfterRequest()`.
- Removed `craft\services\ProjectConfig::preventSavingDataAfterRequest()`.

### Fixed
- Fixed a PHP error that occurred when deleting an asset transform. ([#4473](https://github.com/craftcms/cms/issues/4473))

### Security
- Fixed an XSS vulnerability.
- Fixed a path disclosure vulnerability. ([#4468](https://github.com/craftcms/cms/issues/4468))
- Added the `sameSiteCookieValue` config setting. ([#4462](https://github.com/craftcms/cms/issues/4462))

## 3.1.32.1 - 2019-06-25

### Fixed
- Fixed a couple Windows compatibility issues.

## 3.1.32 - 2019-06-25

### Changed
- Project Config now sorts arrays when all of the keys are UIDs. ([#4425](https://github.com/craftcms/cms/issues/4425))

### Fixed
- Fixed a bug where Craft might not match a domain to the proper site if it had a non-ASCII character in the host name.
- Fixed an error that could occur when using the `|filter` Twig filter. ([#4437](https://github.com/craftcms/cms/issues/4437))
- Fixed a bug where pagination URL could get repeated page params added to the query string if using query string-based pagination.

## 3.1.31 - 2019-06-18

### Added
- It’s now possible to set plugin license keys to environment variables using the `$VARIABLE_NAME` syntax. ([#4393](https://github.com/craftcms/cms/issues/4393))
- Added `craft\services\Elements::mergeElements()`. ([#4404](https://github.com/craftcms/cms/pull/4404))

### Changed
- Pagination URLs now include any query string parameters set on the current request.
- The default email template no longer sets text or background colors, so emails look better in dark mode. ([#4396](https://github.com/craftcms/cms/pull/4396))
- Improved the error message that gets logged when Craft isn’t able to finish processing project config changes, due to unresolved dependencies.
- Craft will no longer log errors and warnings arising from `yii\i18n\PhpMessageSource`. ([#4109](https://github.com/craftcms/cms/issues/4109))
- Improved the performance and reliability of user queries when the `group` param is set to a user group with a large number of users.
- Updated Yii to 2.0.21.

### Fixed
- Fixed a bug where `Craft::dd()` wouldn’t work properly if output buffering was enabled. ([#4399](https://github.com/craftcms/cms/issues/4399))
- Fixed a bug where `Craft::alias()` wasn’t working on Windows servers. ([#4405](https://github.com/craftcms/cms/issues/4405))
- Fixed a bug where Craft wasn't parsing the `dsn` DB connection setting properly if it was supplied.

### Security
- Fixed an XSS vulnerability.

## 3.1.30 - 2019-06-11

### Changed
- Improved query performance. ([yiisoft/yii2#17344](https://github.com/yiisoft/yii2/pull/17344), [yiisoft/yii2#17345](https://github.com/yiisoft/yii2/pull/17345), [yiisoft/yii2#17348](https://github.com/yiisoft/yii2/pull/17348))
- `craft\services\Elements::saveElement()` now always propagates elements regardless of the `$propagate` argument value, when saving new elements. ([#4370](https://github.com/craftcms/cms/issues/4370))

### Fixed
- Fixed a bug where new elements weren’t assigned a UID in time if their URI format contained a `{uid}` token. ([#4364](https://github.com/craftcms/cms/issues/4364))
- Fixed a bug where Craft was modifying custom log target configs before executing queue jobs. ([#3766](https://github.com/craftcms/cms/issues/3766))
- Fixed a bug where `craft\helpers\ChartHelper::getRunChartDataFromQuery()` assumed that the value would be integers. ([craftcms/commerce#849](https://github.com/craftcms/commerce/issues/849))
- Fixed a bug where `craft\services\Security::validateData()` was returning an empty string instead of `false` when the data didn’t validate. ([#4387](https://github.com/craftcms/cms/issues/4387))
- Fixed a bug where Craft could inject unexpected JavaScript into front-end requests. ([#4390](https://github.com/craftcms/cms/issues/4390))

## 3.1.29 - 2019-06-04

### Added
- Added the `restore` command, which restores a database backup.
- Added the `Craft.escapeRegex()` JavaScript method.

### Changed
- Asset indexes now sort assets by Date Uploaded in descending order by default. ([#1153](https://github.com/craftcms/cms/issues/1153))
- `craft\db\Paginator` no longer assumes that the application’s database connection should be used.
- Updated Twig to 2.11. ([#4342](https://github.com/craftcms/cms/issues/4342))

### Fixed
- Fixed a bug where the Status menu wasn’t visible for the “All users” source on user indexes. ([#4306](https://github.com/craftcms/cms/pull/4306))
- Fixed a bug where pressing the <kbd>Esc</kbd> key in the setup wizard would close the modal window. ([#4307](https://github.com/craftcms/cms/issues/4307))
- Fixed a bug where `craft\validators\ArrayValidator::validate()` didn’t work. ([#4309](https://github.com/craftcms/cms/pull/4309))
- Fixed an error that could occur when rendering templates with a `loop.parent.loop` reference in a nested for-loop. ([#4271](https://github.com/craftcms/cms/issues/4271))
- Fixed a bug where publishing a Single entry’s draft, or reverting a Single entry to a prior version, would overwrite its title to the section name. ([#4323](https://github.com/craftcms/cms/pull/4323))
- Fixed a bug where Craft wasn’t invalidating existing asset transforms when changing the dimensions of a named transform.
- Fixed a bug where `craft\services\Fields::getFieldsByElementType()` would return duplicate results if a field was used in more than one field layout for the element type. ([#4336](https://github.com/craftcms/cms/issues/4336))
- Fixed a bug where Craft wasn’t respecting the `allowUppercaseInSlug` config setting when generating slugs in the Control Panel. ([#4330](https://github.com/craftcms/cms/issues/4330))
- Fixed a bug where Control Panel Ajax requests weren’t working if a custom `pathParam` config setting value was set. ([#4334](https://github.com/craftcms/cms/issues/4334))
- Fixed a JavaScript error that could occur when saving a new entry, if the selected entry type didn’t have a Title field. ([#4353](https://github.com/craftcms/cms/issues/4353))

## 3.1.28 - 2019-05-21

### Added
- Added the “Customize element sources” user permission. ([#4282](https://github.com/craftcms/cms/pull/4282))
- Matrix sub-fields now have a “Use this field’s values as search keywords?” setting. ([#4291](https://github.com/craftcms/cms/issues/4291))
- Added `craft\web\twig\variables::setBasePath()`. ([#4286](https://github.com/craftcms/cms/issues/4286))

### Changed
- Craft now requires Yii 2.0.19.

### Fixed
- Fixed a bug where slugs could get double-hyphenated. ([#4266](https://github.com/craftcms/cms/issues/4266))
- Fixed an error that would occur when installing Craft if the `allowAdminChanges` config setting was disabled. ([#4267](https://github.com/craftcms/cms/issues/4267))
- Fixed a bug where Matrix fields would return the wrong set of Matrix blocks on new or duplicated elements, immediately after they were saved.
- Fixed a bug where users could not assign additional user groups to their own account if their permission to do so was granted by another user group they belonged to.
- Fixed a bug where Number fields would attempt to save non-numeric values. ([craftcms/feed-me#527](https://github.com/craftcms/feed-me/issues/527))
- Fixed a bug where it was possible to assign a Structure entry or category to a new parent, even if that would cause its descendants to violate the Max Levels setting. ([#4279](https://github.com/craftcms/cms/issues/4279))
- Fixed an error that could occur when rendering a template from a console request, if the template contained any non-global `{% cache %}` tags. ([#4284](https://github.com/craftcms/cms/pull/4284))

## 3.1.27 - 2019-05-14

### Added
- Added `craft\fields\Matrix::EVENT_SET_FIELD_BLOCK_TYPES`. ([#4252](https://github.com/craftcms/cms/issues/4252))

### Changed
- Pressing <kbd>Shift</kbd> + <kbd>Return</kbd> (or <kbd>Shift</kbd> + <kbd>Ctrl</kbd>/<kbd>Command</kbd> + <kbd>Return</kbd>) when a textual cell is focused in an editable table will now change the focus to the same cell in the previous row (after creating a new row if necessary.) ([#4259](https://github.com/craftcms/cms/issues/4259))
- Craft no longer shows the status menu for element sources that define a status. ([#4249](https://github.com/craftcms/cms/issues/4249))
- Element URI formats can now conditionally output an empty string, opting the element out of getting its own system URI. ([#4254](https://github.com/craftcms/cms/issues/4254))
- Table fields now get validation errors if any column handles are entered in the format of “colX”.
- Craft no longer clear out users’ verification codes after login. ([#4257](https://github.com/craftcms/cms/issues/4257))
- The `users/upload-user-photo` and `users/delete-user-photo` actions are now available to front-end requests. ([#3932](https://github.com/craftcms/cms/issues/3932))

### Fixed
- Fixed a bug where rebuilding the project config could set an incorrect value for the user field layout.
- Fixed a bug Craft wouldn’t allow users to edit their own photos if they didn’t have upload/remove asset permissions.
- Fixed a bug where Craft wasn’t removing newline characters when pasting text into some single-line Table column types.
- Fixed a bug where project config syncing could have inconsistent results on load-balanced environments. ([#4136](https://github.com/craftcms/cms/issues/4136))
- Fixed a bug where the Plugin Store was not able to load developer details. ([#4241](https://github.com/craftcms/cms/issues/4241))
- Fixed a bug that could occur when Craft generated URLs with multi-byte characters in the query string.
- Fixed a bug where you could get some character encoding issues in some environments when using PHP 7.3.
- Fixed a bug where Craft wasn’t attempting to set a unique URI on duplicated elements. ([#4253](https://github.com/craftcms/cms/issues/4253))
- Fixed a bug where Table fields could copy cell values to other cells if a column had a handle in the format of “colX”. ([#4200](https://github.com/craftcms/cms/issues/4200))
- Fixed an error that could occur on the Login page if a custom Login Page Logo was selected. ([#4261](https://github.com/craftcms/cms/issues/4261))

## 3.1.26 - 2019-05-08

### Changed
- The “Update all” button on the Updates utility is now shown even if the page contains some uninstallable updates. ([#4230](https://github.com/craftcms/cms/issues/4230))
- Craft now stores the Default User Group’s UID in the project config, in case the group’s ID is different across environments.
- `craft\services\Assets::EVENT_BEFORE_REPLACE_ASSET` event handlers can now change the filename of the replaced asset before it is saved.
- Improved the performance of background jobs. ([#4219](https://github.com/craftcms/cms/pull/4219))
- Improved the Plugin Store’s screenshots with arrows for navigation and pinch-to-zoom capability for touch devices.

### Fixed
- Fixed an error that could occur when saving a Single section if one of its sites had been disabled.
- Fixed an error that could occur when deleting a site.
- Fixed a PHP compile error that could occur when paginating a query. ([#4208](https://github.com/craftcms/cms/pull/4208))
- Fixed an error that could occur on the Settings → Users → Settings page if the project config was missing its `users` key. ([#4206](https://github.com/craftcms/cms/issues/4206))
- Fixed a bug where Craft wasn’t requiring email verification for new user accounts if the project config was missing its `users` key.
- Fixed a bug where Craft wasn’t eager-loading elements in the same site as the source element, if that was different than the currently requested site. ([#3954](https://github.com/craftcms/cms/issues/3954))

## 3.1.25 - 2019-04-30

### Added
- Added the `|ascii` Twig filter. ([#4193](https://github.com/craftcms/cms/issues/4193))

### Changed
- Craft now registers its project config event handlers before loading plugins. ([#3943](https://github.com/craftcms/cms/issues/3943))
- The Control Panel now uses jQuery 3.4.0. ([#4183](https://github.com/craftcms/cms/issues/4183))
- `behavior` and `behaviors` are now reserved field handles. ([#4184](https://github.com/craftcms/cms/issues/4184))
- The Updates utility no longer shows notices for expired plugins if no updates are actually available. ([#4186](https://github.com/craftcms/cms/issues/4186))

### Fixed
- Fixed an error where rebuilding the project config would not typecast the `propagateEntries` and `enableVersioning` section settings correctly. ([#3695](https://github.com/craftcms/cms/issues/3695))
- Fixed a bug where the Edit Draft HUD would include the current site name in the default Draft Name value for multi-site entries. ([#4171](https://github.com/craftcms/cms/issues/4171))
- Fixed a bug where resource requests could send a 500 response if the resource didn’t exist. ([#4197](https://github.com/craftcms/cms/pull/4197))

## 3.1.24 - 2019-04-23

### Added
- Added `craft\services\Fields::getFieldIdsByLayoutId()`.

### Changed
- Craft now correctly typecasts all core boolean and integer values saved to the project config. ([#3695](https://github.com/craftcms/cms/issues/3695))
- Craft now saves new entry versions every time an entry is saved, unless it’s being propagated or resaved.
- `users/save-user` and `users/start-elevated-session` requests now check for a `currentPassword` body param in addition to `password`, when looking for the user’s current password. ([#4169](https://github.com/craftcms/cms/issues/4169))
- `craft\services\Path::getStoragePath()` now has a `$create` argument.
- Updated Twig to 2.8.

### Fixed
- Fixed an error where re-saving a site would reset its sorting order. ([#4147](https://github.com/craftcms/cms/issues/4147))
- Fixed a SQL error that could occur when updating to Craft 3.1. ([#3663](https://github.com/craftcms/cms/issues/3663))
- Fixed an error that occurred when an SVG with `/` characters in its `id` attributes was passed to the `svg()` Twig function. ([#4155](https://github.com/craftcms/cms/issues/4155))
- Fixed a bug where passing `:empty:` or `:notempty:` to a Matrix field param on an element query could return incorrect results for fields that had soft-deleted blocks. ([#4161](https://github.com/craftcms/cms/issues/4161))
- Fixed a bug where Craft wasn’t returning a `1` exit code for console requests if the server was running under PHP 7. ([#4153](https://github.com/craftcms/cms/issues/4153))
- Fixed a “World-writable config file 'my.cnf' is ignored” warning that could occur when creating a database backup. ([#4163](https://github.com/craftcms/cms/pull/4163))
- Fixed a bug where `craft\services\Elements::duplicateElements()` would only ignore non-safe attributes passed to the `$newAttributes` argument.
- Fixed a bug where `craft\elements\db\ElementQuery::exists()` and `offsetExists()` were ignoring cached query results.

## 3.1.23 - 2019-04-16

### Added
- The `project-config/sync` command now has a `--force` option, which forces the project config to treat all preexisting config values as new. ([#4126](https://github.com/craftcms/cms/issues/4126))
- Added `craft\base\LogTargetTrait`, which can be used by custom `log` components, to gain security and privacy features provided by Craft’s built-in file target. ([#4127](https://github.com/craftcms/cms/pull/4127))

### Changed
- When creating a new site, global sets are now propagated to it before other element types. ([#3446](https://github.com/craftcms/cms/issues/3446))
- Locked Twig down to 2.7, to avoid a bug in 2.8.0. ([twigphp/Twig#2942](https://github.com/twigphp/Twig/issues/2942))

### Fixed
- Fixed an error that occurred when installing a missing plugin from the Settings → Plugins page. ([#4140](https://github.com/craftcms/cms/issues/4140))
- Fixed PHP type errors that could occur when calling some deprecated `craft.request` methods in templates. ([#4124](https://github.com/craftcms/cms/issues/4124))
- Fixed performance issues that could occur where uploading GIFs in the Control Panel. ([#4131](https://github.com/craftcms/cms/pull/4131))
- Fixed a bug where it wasn’t possible to create a new global set with the same name or handle as a soft-deleted one. ([#4091](https://github.com/craftcms/cms/issues/4091))
- Fixed a bug where pending users’ verification codes were getting deleted if they were impersonated by an admin. ([#4130](https://github.com/craftcms/cms/issues/4130))

## 3.1.22 - 2019-04-10

### Added
- Added `craft\base\ElementTrait::$resaving`, which indicates whether the element is currently being resaved via a `ResaveElements` job or a `resave` command. ([#3482](https://github.com/craftcms/cms/issues/3482))
- Added `craft\db\Paginator::setPageResults()`. ([#4120](https://github.com/craftcms/cms/issues/4120))

### Changed
- Changed the way Craft updates search indexes, to reduce the likelihood of a deadlock. ([#3197](https://github.com/craftcms/cms/issues/3197))
- Improved styles and behavior of the Plugin Store.
- The Settings → Plugins page now notes which plugins are expired, with links to renew them on [id.craftcms.com](https://id.craftcms.com).
- Improved the styling of info HUDs that contain long text or tables. ([#4107](https://github.com/craftcms/cms/pull/4107))

### Fixed
- Fixed a PHP error that could occur during asset indexing in some cases.
- Fixed a bug where entry drafts weren’t showing previous changes to Matrix fields on the draft. ([#4105](https://github.com/craftcms/cms/issues/4105))
- Fixed a bug where `project.yaml` changes weren’t always getting picked up. ([#4028](https://github.com/craftcms/cms/issues/4028))
- Fixed a bug where the `project-config/rebuild` command would restore soft-deleted components. ([#4100](https://github.com/craftcms/cms/issues/4100))
- Fixed a bug where the `project-config/sync` command was not performing schema checks.
- Fixed an error that occurred when backing up the database if the database password contained a `$` character. ([#4115](https://github.com/craftcms/cms/issues/4115))

## 3.1.21.1 - 2019-04-04

### Fixed
- Fixed a bug where underscores were getting stripped from element slugs. ([#4096](https://github.com/craftcms/cms/issues/4096))

## 3.1.21 - 2019-04-03

### Added
- Added the `backup` command, which creates a new database backup. ([#4075](https://github.com/craftcms/cms/issues/4075))
- Added the `queue/retry` command, which can be passed a failed job ID, or `all` to retry all failed jobs. ([#4072](https://github.com/craftcms/cms/issues/4072))
- Added `craft\queue\Queue::retryAll()`.
- Added `craft\services\sections::$autoResaveEntries`, which can be set to `false` from `config/app.php` to prevent Craft from auto-resaving entries after sections and entry types are updated. ([#3482](https://github.com/craftcms/cms/issues/3482))

### Changed
- It’s now possible to double-click on asset sources to expand/collapse their subfolders. ([#4070](https://github.com/craftcms/cms/issues/4070))
- Craft no longer auto-resaves entries after saving a section or entry type if nothing changed of any significance to entries. ([#3482](https://github.com/craftcms/cms/issues/3482))
- Craft now formats filesizes using metric units (e.g. MB instead of MiB).
- The updater is now capable of handling package name changes.
- Craft now requires Yii 2.0.17.

### Fixed
- Fixed a bug where the Asset Indexes utility wasn’t logging exceptions.
- Fixed a SQL error that could occur when using the Asset Indexes utility, if any filenames contained 4+ byte characters.
- Fixed a bug where entry queries could return duplicate results for any entries that belong to a section that has soft-deleted structures associated with it. ([#4066](https://github.com/craftcms/cms/issues/4066))
- Fixed a bug where rebuilding project config would not work with Matrix fields with no block types. ([#4074](https://github.com/craftcms/cms/issues/4074)
- Fixed an error that occurred when sending emails if the `testToEmailAddress` config setting was set. ([#4076](https://github.com/craftcms/cms/issues/4076))
- Fixed a bug where it wasn’t possible to pass the `--element-id` option on `resave/*` commands.
- Fixed a bug where Matrix fields were including disabled blocks if any changes had been made to the Matrix block query params.
- Fixed SQL errors that could occur if the table prefix had ever changed.

## 3.1.20.1 - 2019-03-27

### Fixed
- Fixed an error that occurred when regenerating the project config, if there were any fields without settings. ([#4062](https://github.com/craftcms/cms/issues/4062))
- Fixed an error that occurred when loading the `_includes/forms/date` template without passing a `value` variable. ([#4063](https://github.com/craftcms/cms/issues/4063))

## 3.1.20 - 2019-03-27

### Added
- Added the `project-config/rebuild` console command.
- Added the `verifyEmailSuccessPath` config setting.
- Added the “Prefix” and “Suffix” settings for Number fields. ([#4055](https://github.com/craftcms/cms/issues/4055))
- Added the “Max Length” setting for URL fields. ([#4019](https://github.com/craftcms/cms/issues/4019))
- Added the `devMode` global Twig variable. ([#4038](https://github.com/craftcms/cms/issues/4038))
- Added `craft\config\GeneralConfig::getVerifyEmailSuccessPath()`.
- Added `craft\events\RebuildConfigEvent`.
- Added `craft\services\ProjectConfig::rebuild()`.
- Added `craft\services\Sections::pruneDeletedField()`.

### Changed
- Textareas within the Control Panel can now be manually vertically resized. ([#4030](https://github.com/craftcms/cms/issues/4030))
- The Craft Support widget now includes a “More Resources” section. ([#4058](https://github.com/craftcms/cms/issues/4058))
- The `_includes/forms/text` Control Panel template now supports `step`, `min`, and `max` attributes.
- Users without access to the Control Panel are now redirected according to the `verifyEmailSuccessPath` config setting after verifying a new email address. ([#1998](https://github.com/craftcms/cms/issues/1998))
- The `_includes/forms/text` Control Panel template now supports passing `autocorrect: false` and `autocapitalize: false`, to disable autocorrect and auto-capitalization on iOS devices.
- iOS autocorrect and auto-capitalization has been disabled for all core “Handle” and “Slug” fields in the Control Panel. ([#4009](https://github.com/craftcms/cms/issues/4009))
- Number fields now format their values for element index tables. ([#4059](https://github.com/craftcms/cms/issues/4059))
- When installing Craft using a `project.yaml`, Craft now backups the existing config to the config backup folder if there are errors. ([#4017](https://github.com/craftcms/cms/issues/4017))
- Craft now prunes entry type layouts when deleting a field.
- Craft no longer modifies the DSN string if set explicitly with the `dsn` database config setting.
- Craft no longer throws an `InvalidConfigException` when the `dsn` database config setting is set and contains an unexpected parameter.

### Fixed
- Fixed a bug where Craft wasn’t removing hyphens and other symbols from auto-generated asset titles. ([#4011](https://github.com/craftcms/cms/issues/4011))
- Fixed a PHP error that occurred when calling `craft\services\EntryRevisions::getDraftById()` or `getVersionById()` for a draft/version that belonged to a soft-deleted entry. ([#4013](https://github.com/craftcms/cms/issues/4013))
- Fixed a bug where Craft wasn’t respecting the site selection for routes defined in Settings → Routes. ([#4021](https://github.com/craftcms/cms/issues/4021))
- Fixed a bug where the `project-config/sync` command wasn’t logging exceptions. ([#4015](https://github.com/craftcms/cms/issues/4015))
- Fixed an error that occurred when attempting to use Live Preview with a pending user account. ([#4025](https://github.com/craftcms/cms/issues/4025))
- Fixed an error when displaying a date input in the Control Panel if the value passed wasn’t a `DateTime` object. ([#4041](https://github.com/craftcms/cms/issues/4041))
- Fixed a PHP error that occurred when passing an array of `craft\elements\User` objects to `craft\mail\Message::setTo()`. ([#4048](https://github.com/craftcms/cms/issues/4048))
- Fixed a bug where Craft was applying the `offset` param to both ends of the result set when paginating queries. ([#4052](https://github.com/craftcms/cms/issues/4052))
- Fixed a PHP error that occurred if `true` or `false` was passed to the third argument of `craft\db\Command::upsert()`. ([#4054](https://github.com/craftcms/cms/pull/4054))
- Fixed a bug where deleting fields via `project.yaml` could prevent other changes from being applied.
- Fixed a bug where field UIDs could be overwritten in some cases.

## 3.1.19 - 2019-03-19

### Added
- Added the `_includes/pagination` Control Panel template.
- Added `craft\db\Paginator`.
- Added `craft\web\twig\variables\Paginate::create()`.

### Changed
- The `{% paginate %}` tag now accepts any query object, not just element queries.
- The `_includes/forms/autosuggest` template now has `data` and `methods` blocks that can be overridden by sub-templates to customize the autosuggest behavior.

### Fixed
- Fixed a bug where sidebar badge counts in the Control Panel were getting formatted with two decimals if the Intl extension wasn’t loaded. ([#4002](https://github.com/craftcms/cms/issues/4002))
- Fixed a bug where entry drafts would forget that certain field values had been cleared out, and continue using the live revision’s content instead. ([#3981](https://github.com/craftcms/cms/issues/3981))
- Fixed an error that occurred if a Table field was created with a Date or Time column and no rows in the Default Values setting. ([#4005](https://github.com/craftcms/cms/issues/4005))
- Fixed a bug where Table fields would forget that they had been saved without any rows in the Default Values setting.
- Fixed a SQL error that could occur when saving non-UTF-8 characters to the project config. ([#4007](https://github.com/craftcms/cms/issues/4007))

## 3.1.18 - 2019-03-14

### Added
- Added `craft\services\Deprecator::$throwExceptions`. ([#3972](https://github.com/craftcms/cms/pull/3972))

### Changed
- `Craft::parseEnv()` will now boolean values for environment variables set to `true` or `false`. ([#3975](https://github.com/craftcms/cms/issues/3975))
- Nested project config keys are no longer sorted alphabetically.
- Craft now requires Twig 2.7+.

### Fixed
- Fixed a SQL error that occurred when using a token with a usage limit, if using PostgreSQL. ([#3969](https://github.com/craftcms/cms/issues/3969))
- Fixed a bug where the Edit User page would forget user group selection changes if there was a validation error. ([#3971](https://github.com/craftcms/cms/issues/3971))
- Fixed a bug where the updater would get an unexpected response when updating from 3.1.14 - 3.1.16 to 3.1.17+.
- Fixed a bug where it wasn’t possible to switch plugin editions when the `allowUpdates` config setting was disabled. ([#3987](https://github.com/craftcms/cms/issues/3987))
- Fixed a bug where multiple consecutive newlines in field instructions would result in multiple `<br>` tags rather than new paragraphs.
- Fixed a bug where Table fields weren’t always remembering the sort order for their Default Values settings. ([#3947](https://github.com/craftcms/cms/issues/3947))
- Fixed a bug where Table fields weren’t always remembering the sort order for their Table Columns settings. ([#3997](https://github.com/craftcms/cms/issues/3997))

## 3.1.17.2 - 2019-03-12

### Changed
- Craft now requires Twig 2.6.

## 3.1.17.1 - 2019-03-08

### Added
- Added `craft\helpers\ArrayHelper::ensureNonAssociative()`.

### Fixed
- Fixed a bug where commercial plugin editions weren’t showing up in the Plugin Store.
- Fixed a bug where installing a plugin from the Plugin Store would not respect the selected edition.
- Fixed a bug where plugins with free and commercial editions weren’t getting license key inputs on the Setting → Plugins page.
- Fixed a bug where the Setting → Plugins page wasn’t linking plugins’ edition badge to their page in the Plugin Store for plugins with free and commercial editions, if the free edition was currently active.

## 3.1.17 - 2019-03-08

### Changed
- When installing Craft using a `project.yaml`, Craft now processes all sites before installing any plugins. ([craftcms/commerce#752](https://github.com/craftcms/commerce/issues/752))
- The Plugin Store now shows “Report an issue” links on plugin screens.
- The Plugin Store now includes a “Package Name” section on plugin screens. ([#2757](https://github.com/craftcms/cms/issues/2757))
- The Plugin Store now shows discounted upgrade prices for plugins when a lower edition is already licensed.
- Craft now requires Yii 2.0.16.1.

### Fixed
- Fixed a bug where the `positionedBefore` element query param was not including direct ancestors in the results.
- Fixed a bug where HTML in plugin-supplied field instructions was getting encoded. ([#3928](https://github.com/craftcms/cms/issues/3928))
- Fixed a bug where Craft would prompt for a user’s current password when registering a new user, even if they weren’t assigning any groups or permissions to that user
- Fixed a bug where asset indexing could yield inconsistent results in some cases. ([#3450](https://github.com/craftcms/cms/issues/3450))
- Fixed a bug where the Plugin Store was showing info icons in the feature matrix of multi-edition plugins, even for features that didn’t have an extended description.
- Fixed a bug where entries weren’t getting new versions when edited from element editor HUDs. ([#3959](https://github.com/craftcms/cms/issues/3959))

## 3.1.16 - 2019-03-05

### Added
- The Plugin Store now shows Repository links on plugin screens.
- Added the `create()` Twig function. ([#3921](https://github.com/craftcms/cms/pull/3921))
- Added the `--type` option to the `resave/entries` command. ([#3939](https://github.com/craftcms/cms/issues/3939))
- Added `craft\helers\Assets::getAllowedFileKinds()`.

### Changed
- Line breaks in field instructions now get converted to `<br>` tags. ([#3928](https://github.com/craftcms/cms/issues/3928))
- Assets field settings no longer list file kinds that aren’t allowed to be uploaded, per the `allowedFileExtensions` and `extraAllowedFileExtensions` config settings. ([#3917](https://github.com/craftcms/cms/issues/3917))
- The `{% exit %}` tag now throws a more specific exception depending on the status code passed to it (e.g. `yii\web\NotFoundHttpException` for 404s). ([#3915](https://github.com/craftcms/cms/issues/3915))
- `craft\helpers\MigrationHelper::dropAllIndexesOnTable()` is no longer deprecated.
- The `--id` option on `resave/*` console commands is now named `--element-id`. ([#3940](https://github.com/craftcms/cms/issues/3940))
- The `_includes/forms/autosuggest.html` template now supports passing `disabled: true`. ([#3925](https://github.com/craftcms/cms/issues/3925))

### Fixed
- Fixed a bug where Control Panel content areas weren’t getting their bottom padding applied in Firefox. ([#3874](https://github.com/craftcms/cms/issues/3874))
- Fixed a PHP error that occurred on the front-end if two routes defined in Settings → Routes had the same URI pattern. ([#3922](https://github.com/craftcms/cms/issues/3922))
- Fixed a bug where Craft wasn’t always preselecting the correct tab on Control Panel pages if the tab name contained non-ASCII characters. ([#3923](https://github.com/craftcms/cms/issues/3923))
- Fixed a bug where the `--uid` option on `resave/*` console commands wasn’t working. ([#3941](https://github.com/craftcms/cms/issues/3941))
- Fixed a SQL error that could occur when running `resave/*` console commands.
- Fixed a PHP error that occurred when calling the deprecated `getError()` method on a model that had no errors. ([#3934](https://github.com/craftcms/cms/issues/3934))
- Fixed a bug where Craft wasn’t sanitizing new asset subfolder names. ([#3689](https://github.com/craftcms/cms/issues/3689))
- Fixed a bug where Table fields weren’t remembering the sort order for their Default Values settings. ([#3947](https://github.com/craftcms/cms/issues/3947))

## 3.1.15 - 2019-02-26

### Added
- Added the `resave/assets`, `resave/categories`, `resave/entries`, `resave/tags`, and `resave/users` console commands.

### Changed
- Craft now sends system messages authored for the same root language as the requested language, if an exact language match can’t be found. ([#3888](https://github.com/craftcms/cms/issues/3888))
- Element source definitions can now include a `badgeCount` key.
- Login requests no longer enforce CSRF validation if someone is already logged in.
- Craft now throws an `InvalidConfigException` when updating the project config if any unexpected data types are encountered.
- The `testToEmailAddress` config setting can now be set to `false`. ([#3910](https://github.com/craftcms/cms/pull/3910))

### Fixed
- Fixed a bug where the System Messages utility wouldn’t update message previews after editing a message for the primary site’s language, if the user had a different preferred language selected.
- Fixed a bug where structures weren’t getting deleted and unassigned from their sections properly after converting a Structure section to a Channel or Single. ([#3895](https://github.com/craftcms/cms/issues/3895))
- Really fixed a bug where Craft could update the `dateModified` value in the project config even when nothing had changed. ([#3792](https://github.com/craftcms/cms/issues/3792))
- Fixed a bug where the Settings → Routes page wasn’t listing routes in the user-defined order. ([#3892](https://github.com/craftcms/cms/issues/3892))
- Fixed an error that occurred when viewing trashed entries, if the “Entry Type” column was shown and one of the trashed entries’ entry types had been deleted. ([#3899](https://github.com/craftcms/cms/issues/3899))

## 3.1.14 - 2019-02-21

### Added
- Added `craft\helpers\ProjectConfig::cleanupConfig()`.
- Added `craft\web\Request::$maxPageNum`, which determines the maximum page number Craft should accept (100,000 by default). ([#3880](https://github.com/craftcms/cms/issues/3880))

### Deprecated
- Deprecated `craft\mutex\FileMutex`.

### Fixed
- Fixed a bug where Craft could update the `dateModified` value in the project config even when nothing had changed. ([#3792](https://github.com/craftcms/cms/issues/3792))
- Fixed a SQL error that occurred when running the “Localizing relations” task if using PostgreSQL. ([#3877](https://github.com/craftcms/cms/issues/3877))
- Fixed a bug where file locking wasn’t working on Windows. ([#3879](https://github.com/craftcms/cms/issues/3879))

### Security
- Fixed a bug where sensitive environment variable values weren’t getting redacted correctly.

## 3.1.13 - 2019-02-20

### Added
- Added `craft\helpers\StringHelper::replaceMb4()`.
- Added `craft\services\ProjectConfig::defer()`.

### Changed
- The `users/login` and `users/logout` actions now include a `csrfTokenValue` key in JSON responses. ([#3858](https://github.com/craftcms/cms/issues/3858))
- Craft no longer deletes search indexes when soft-deleting an element, until the element gets hard-deleted. ([#3863](https://github.com/craftcms/cms/issues/3863))
- Updated Yii to 2.0.16.

### Fixed
- Fixed a bug where Craft could auto-place the `{{ beginBody() }}` and `{{ endBody() }}` tags in the wrong places.
- Fixed a bug where Craft wasn’t storing custom volume sort orders. ([#3764](https://github.com/craftcms/cms/issues/3764))
- Fixed a SQL error that would occur when uploading a file with emojis in its name, if using MySQL. ([#3852](https://github.com/craftcms/cms/issues/3852))
- Fixed a bug where Assets fields weren’t respecting their View Mode setting when files were drag-uploaded to them. ([#3578](https://github.com/craftcms/cms/issues/3578))
- Fixed a bug where asset queries’ `kind` param wasn’t working for custom file kinds defined by the `extraFileKinds` config setting, for file extensions that were already associated with another file kind. ([#3869](https://github.com/craftcms/cms/issues/3869))
- Fixed a bug where `craft\helpers\FileHelper::sanitizeFilename()` could return inconsistent results.
- Fixed an error that could occur when syncing `project.yaml` if it introduced a new Super Table field with a nested Matrix field.

## 3.1.12 - 2019-02-15

### Fixed
- Fixed a bug where the `relatedTo` element query param could include results for elements that were related via soft-deleted Matrix blocks. ([#3846](https://github.com/craftcms/cms/issues/3846))
- Fixed a bug where some search queries were not returning results when they should, if using MySQL.
- Fixed an error that could occur when syncing `project.yaml` changes if the `allowAdminChanges` config setting was disabled. ([#3823](https://github.com/craftcms/cms/issues/3823))
- Fixed an `InvalidConfigException` that was thrown if a user’s photo was soft-deleted. ([#3849](https://github.com/craftcms/cms/issues/3849))

## 3.1.11 - 2019-02-14

### Added
- Added `craft\helpers\UrlHelper::rootRelativeUrl()`.

### Fixed
- Fixed a bug where the Plugin Store wouldn’t load if the `baseCpUrl` config setting was set to a URL with a different scheme than Craft believed the request had.
- Fixed a validation error that would occur on non-required Checkboxes and Multi-select fields if no options were selected. ([#3844](https://github.com/craftcms/cms/issues/3844))
- Fixed a validation error that would occur on Dropdown and Radio Buttons fields if the selected option’s value was `0`. ([#3842](https://github.com/craftcms/cms/issues/3842))
- Fixed a bug where the Value column for Checkboxes, Dropdown, Multi-select, and Radio Buttons fields’ Options settings weren’t auto-populating if the Option Label column was set to a number.
- Fixed an error on the Settings → Users page if `users.photoVolumeUid` was not defined in the project config. ([#3303](https://github.com/craftcms/cms/issues/3303))

## 3.1.10 - 2019-02-13

### Changed
- `craft\helpers\FileHelper::writeToFile()` now invalidates the OPcache for the file. ([#3838](https://github.com/craftcms/cms/pull/3838))
- The `serve` command now uses `@webroot` as the default `docroot` option value. ([#3770](https://github.com/craftcms/cms/pull/3770))

### Fixed
- Fixed a bug where the `users/save-user` action wasn’t deleting user photos properly.
- Fixed a bug where changes to Matrix block type fields’ settings weren’t always saving. ([#3832](https://github.com/craftcms/cms/issues/3832))
- Fixed a bug where non-searchable fields were still getting search keywords stored when using the Search Indexes utility. ([#3837](https://github.com/craftcms/cms/issues/3837))

## 3.1.9.1 - 2019-02-12

### Fixed
- Fixed a bug where `Craft::alias()` wasn’t beginning the response string with an `@` character if no `@` was passed into `Craft::setAlias()` to begin with.
- Fixed an error that could occur if there were any HTML entities in the project config.

## 3.1.9 - 2019-02-12

### Added
- Added the `disabledPlugins` config setting. ([craftcms/webhooks#4](https://github.com/craftcms/webhooks/issues/4))
- Added the `$language` argument to `craft\helpers\StringHelper::toAscii()`.
- Added `craft\validators\SlugValidator::$language`.
- Added `craft\web\twig\variables\Cp::getAsciiCharMap()`.

### Changed
- The operating system name & version are now shown in the System Report utility. ([#3784](https://github.com/craftcms/cms/issues/3784))
- Craft’s installer no longer applies the current `project.yaml` file if the installed schema version doesn’t match the one in the file. ([#3783](https://github.com/craftcms/cms/issues/3783))
- Control Panel settings no longer warn about using the `@web` alias, if it was defined by the `aliases` config setting. ([#3798](https://github.com/craftcms/cms/pull/3798))
- The `clear-caches` console command now clears CP resource files if the `@webroot` alias was defined by the `aliases` config setting. ([#3787](https://github.com/craftcms/cms/issues/3787))
- `craft\models\VolumeFolder::getVolume()` now throws an `InvalidConfigException` if its `$volumeId` property is set to an invalid volume ID, rather than returning `null`.
- Craft now checks if all files in project config mapping are valid and regenerates the map if they are not.
- Craft now auto-generates slugs using an ASCII char map based on the language of the current entry/category, rather than the logged-in user. ([#3820](https://github.com/craftcms/cms/issues/3820))

### Fixed
- Fixed a SQL error that could occur when deleting an asset. ([#3786](https://github.com/craftcms/cms/issues/3786))
- Fixed an error that occurred when customizing element indexes if the `allowAdminChanges` config setting was disabled. ([#3788](https://github.com/craftcms/cms/issues/3788))
- Fixed a bug where Checkboxes, Dropdown, Multi-select, and Radio Buttons fields wouldn’t pass validation if the selected option value was `true` or `false`.
- Fixed an error that occurred on the Settings → Plugins page, if there were any plugins in the database that weren’t Composer-installed.
- Fixed an error that could occur if an Assets field was configured to upload to a deleted volume. ([#3799](https://github.com/craftcms/cms/issues/3799))
- Fixed a bug where sections’ Default Status settings weren’t always being respected. ([#3791](https://github.com/craftcms/cms/issues/3791))
- Fixed a bug where only users with the “Edit users” user permission were allowed to upload a new user photo. ([#3735](https://github.com/craftcms/cms/issues/3735))
- Fixed a bug where renaming a Matrix block type’s handle would result in new content columns being created in the database, and existing Matrix blocks losing their content. ([#3809](https://github.com/craftcms/cms/issues/3809))
- Fixed a SQL error that could occur when updating to Craft 3.1 if any system messages contained emoji characters.
- Fixed an error that could occur when working with elements, if a site had been created earlier in the same request. ([#3824](https://github.com/craftcms/cms/issues/3824))

## 3.1.8 - 2019-02-05

### Changed
- Craft now automatically logs users in after resetting their password, if the `autoLoginAfterAccountActivation` config setting is enabled.

### Fixed
- Fixed a bug where pressing the <kbd>Return</kbd> key on editable tables with a static number of rows would add a new row. ([#3765](https://github.com/craftcms/cms/issues/3765))
- Fixed a bug where pressing the <kbd>Return</kbd> key on editable tables would select the next row’s cell even if the cell was disabled.
- Fixed a bug where pressing the <kbd>Return</kbd> key on an editable table wouldn’t move the focus to the next row’s sell if it had an `<input>` instead of a `<textarea>`.
- Fixed an error that could occur in the Control Panel if any environment variable values began with an `@` character. ([#3769](https://github.com/craftcms/cms/issues/3769))
- Fixed a bug where `craft\helpers\DateTimeHelper::toDateTime()` was mistaking year-only values for Unix timestamps. ([#3772](https://github.com/craftcms/cms/issues/3772))
- Fixed an error that occurred when a non-admin user attempted to edit a system message, or when the `allowAdminChanges` config setting was disabled. ([#3775](https://github.com/craftcms/cms/issues/3775))
- Fixed a bug where it was hard to see error notifications on pages with a licensing alert. ([#3776](https://github.com/craftcms/cms/issues/3776))
- Fixed a JavaScript error that occurred when adding a new row to a custom editable table that contained a `time` column, if no rows existed on page load. ([#3780](https://github.com/craftcms/cms/issues/3780))

## 3.1.7 - 2019-01-31

### Added
- Added all the things that came in [Craft 3.0.40](https://github.com/craftcms/cms/blob/master/CHANGELOG-v3.md#3040---2019-01-31).
- Added `craft\helpers\FileHelper::canTrustMimeType()`.
- Added `craft\web\UploadedFile::getMimeType()`.

### Changed
- The “Port” SMTP mail transport setting can now be set to an environment variable. ([#3740](https://github.com/craftcms/cms/issues/3740))
- `craft\web\Controller::requireAdmin()` now has a `$requireAdminChanges` argument, which dictates whether the `allowAdminChanges` config setting must also be enabled (`true` by default).
- The `project-config/sync` console command now creates a `project.yaml` file, if it's missing. ([#3736](https://github.com/craftcms/cms/issues/3736))
- Querying for active users no longer excludes locked users.
- `craft\helpers\FileHelper::getMimeType()` now returns `application/x-yaml` for `.yaml` and `.yml` files.
- Updated Craft UI to 0.2.0.

### Fixed
- Fixed an error that occurred when updating to Craft 3.1 if a plugin or module was calling `craft\records\User::find()`.
- Fixed a bug where cross-domain Live Preview requests could fail due to CORS restrictions.
- Fixed a 403 error that would occur when an admin attempted to log in as another user on an environment where the `allowAdminChanges` config setting was disabled. ([#3749](https://github.com/craftcms/cms/issues/3749))
- Fixed a bug where asset index toolbar items would be misaligned when searching in a volume or folder with subfolders.
- Fixed a bug where asset indexes could show multiple view mode toggles if a different volume or subfolder was selected while at least one asset was checked. ([#3702](https://github.com/craftcms/cms/issues/3702))
- Fixed a bug where Plugin Store screenshots were not showing properly. ([#3709](https://github.com/craftcms/cms/issues/3709))
- Fixed a bug where zoomed Plugin Store screenshots would not close when hitting the browser’s Back button. ([#3754](https://github.com/craftcms/cms/issues/3754))
- Fixed a bug where the Plugin Store was not working properly when Dev Mode was enabled.

### Security
- User accounts are now locked after multiple failed password attempts in current-password modals, per the `maxInvalidLogins` config setting.
- Users are no longer signed out of active sessions when their account becomes locked.
- Database backup/restore exception messages now redact the database password when using PostgreSQL.

## 3.1.6.1 - 2019-01-29

### Fixed
- Fixed an error that occurred when creating a Table field with a Date column. ([#3748](https://github.com/craftcms/cms/issues/3748))

## 3.1.6 - 2019-01-29

### Added
- It’s now possible to update disabled plugins.

### Changed
- `craft\web\Controller::requireAdmin()` now sends a 403 (Forbidden) response if the `allowAdminChanges` config setting has been set to `false`. ([#3728](https://github.com/craftcms/cms/issues/3728))
- `craft\helpers\DateTimeHelper::toDateTime()` now supports passing an array with a `date` key set to the `YYYY-MM-DD` format, in addition to the current locale’s short date format.
- `craft\helpers\DateTimeHelper::toDateTime()` now supports passing an array with a `time` key set to the `HH:MM` format, in addition to the current locale’s short time format.
- `craft\helpers\DateTimeHelper::toDateTime()` now supports passing an array with a `datetime` key, which will be handled the same way strings passed to the method are handled (except that the `datetime` key can be paired with a `timezone` key).

### Fixed
- Fixed an error that occurred when using the `json_decode` filter. ([#3722](https://github.com/craftcms/cms/pull/3722))
- Fixed a bug a bug where plugin screenshots in the Plugin Store were not rendering correctly. ([#3709](https://github.com/craftcms/cms/issues/3709))
- Fixed an error where the `index-assets/one` and `index-assets/all` console commands were creating `.` folders in each volume.
- Fixed a bug where the Settings → Plugins page was showing extra “Missing” rows for any unlicensed plugins that were Composer-installed but not Craft-installed. ([#3726](https://github.com/craftcms/cms/issues/3726))
- Fixed an error that could occur when viewing trashed elements.
- Fixed a bug where many system message translations were missing line breaks. ([#3737](https://github.com/craftcms/cms/issues/3737))
- Fixed a bug where unparsed markdown code was present in the Control Panel error message displayed when the system was offline. ([#3746](https://github.com/craftcms/cms/issues/3746))

## 3.1.5 - 2019-01-25

### Changed
- Control Panel settings that can be set to environment variables now show a tip about that if the value is not already set to an environment variable or alias.
- Control Panel form fields can now be configured with a `tip` property, which will be displayed below the field.
- Control Panel templates can now pass `suggestEnvVars: true` and `suggestAliases: true` to autosuggest fields, rather that supplying the `suggestions` array.

### Fixed
- Fixed a bug where the “Duplicate” action wasn’t available on the Entries index page for non-admin users. ([#3705](https://github.com/craftcms/cms/issues/3705))
- Fixed a bug where it wasn’t possible to rename an asset’s filename from the Assets index page. ([#3707](https://github.com/craftcms/cms/issues/3707))
- Fixed an error that occurred when saving a user that had a first or last name set.
- Fixed a bug where it wasn’t possible to apply project config changes. ([#3713](https://github.com/craftcms/cms/issues/3713))
- Fixed a bug where the Password field on SMTP and Gmail mail transport settings could be set to an encoded and encrypted password. ([#3699](https://github.com/craftcms/cms/issues/3699))
- Fixed a bug where it was possible to remove the Primary Site status from the primary site, without offering a new primary site. ([#3720](https://github.com/craftcms/cms/issues/3720))
- Fixed an error that could occur if PHP’s `memory_limit` was set to a higher size (in bytes) than `PHP_INT_MAX`. ([#3717](https://github.com/craftcms/cms/issues/3717))

### Security
- Control Panel settings that can be set to an alias now show a warning if the current value begins with the `@web` alias.

## 3.1.4 - 2019-01-24

### Added
- Added all the things that came in [Craft 3.0.38](https://github.com/craftcms/cms/blob/master/CHANGELOG-v3.md#3038---2019-01-24).
- The System Name setting can now be set to an environment variable. ([#3529](https://github.com/craftcms/cms/issues/3529))
- Added the `index-assets/one` console command, which can now be used to index a single subfolder.
- Added `craft\base\ApplicationTrait::getSystemName()`.

### Changed
- Craft now ensures that installed schema versions match the schema versions in `project.yaml` before syncing project config changes.
- The `project-config/sync` console command now bails if there are pending Craft or plugin migrations.

### Fixed
- Fixed a bug where `site` translations were falling back to English if the translated message was identical to the source message. ([#3692](https://github.com/craftcms/cms/issues/3692))
- Fixed a bug where syncing Matrix field changes to the project config would result in new changes to the project config. ([#3695](https://github.com/craftcms/cms/issues/3695))
- Fixed an error that occurred when indexing assets in an empty volume.
- Fixed a bug where soft-deleted assets would show up as missing after indexing.
- Fixed a JavaScript error that could occur on the Settings → Plugins page.
- Fixed a bug where `Craft::parseEnv()` was throwing an `InvalidConfigException` if the given string began with `@` but was not an alias. ([#3700](https://github.com/craftcms/cms/issues/3700))

### Security
- URLs are no longer allowed in users’ first or last names.

## 3.1.3 - 2019-01-21

### Added
- Added the `|json_decode` Twig filter.  ([#3678](https://github.com/craftcms/cms/pull/3678))

### Fixed
- Fixed an error that occurred when updating to Craft 3.1 if a plugin or module was calling any soft-deletable records’ `find()` methods.
- Fixed an error that occurred when updating from Craft 2 to Craft 3.1 if there were any RichText fields. ([#3677](https://github.com/craftcms/cms/issues/3677))
- Fixed a bug where it was possible to create duplicate tags by searching for and selecting the same tag name twice in the same Tags field. ([#3676](https://github.com/craftcms/cms/issues/3676))
- Fixed a bug where system messages were getting sent with the message keys (e.g. “forgot_password_subject” and “forgot_password_body”) if Craft didn’t provide a default message translation for the site language, and the message hadn’t been translated for the user’s preferred language. ([#3673](https://github.com/craftcms/cms/issues/3673))
- Fixed a bug where `craft\web\Request::getIsLivePreview()` was returning `false` on Live Preview requests when called from an `yii\base\Controller::EVENT_BEFORE_ACTION` event handler. ([#3680](https://github.com/craftcms/cms/issues/3680))

## 3.1.2.2 - 2019-01-19

### Fixed
- Fixed an error that occurred when updating to Craft 3.1 if a plugin or module was calling any `craft\services\Sections` methods.

## 3.1.2.1 - 2019-01-19

### Fixed
- Fixed an error that occurred when updating to Craft 3.1 if there were any Matrix sub-fields that had their type set to a non-existing class. ([#3662](https://github.com/craftcms/cms/issues/3662))
- Fixed a bug where the project config could be in an unexpected state if a `project.yaml` file existed already when initially updating to Craft 3.1.

## 3.1.2 - 2019-01-18

### Added
- Added the `index-assets <volume>` and `index-assets/all` console commands. ([#3595](https://github.com/craftcms/cms/pull/3595))
- Added `craft\base\FieldTrait::$oldSettings`.
- Added `craft\helpers\Install`.
- Added `craft\services\Fields::prepFieldForSave()`.
- Added `craft\services\Path::getProjectConfigFilePath()`.
- Added `craft\services\ProjectConfig::$muteEvents`.

### Changed
- The installer now checks `project.yaml` when determining the default site name, handle, base URL, and language values. ([#3661](https://github.com/craftcms/cms/issues/3661))
- The Base URL field in the web-based installer now autouggests environment variable names and aliases.
- Craft now creates a `.gitignore` file in the `storage/config-backups/` folder, preventing any other files within it from getting tracked by Git.
- Craft no longer prevents changes in `project.yaml` from being synced if a plugins’ schema version in `project.yaml` doesn’t match up with its installed schema version, if one of them is blank.

### Deprecated
- Deprecated `craft\services\Fields::$ignoreProjectConfigChanges`.
- Deprecated `craft\services\Matrix::$ignoreProjectConfigChanges`.

### Fixed
- Fixed a PHP notice that occurred when updating to Craft 3.1 if there were any plugins installed without settings.
- Fixed a SQL error that occurred when updating to Craft 3.1 if a plugin or module was calling any `craft\services\Fields` methods. ([#3663](https://github.com/craftcms/cms/issues/3663))
- Fixed a bug where element indexes would forget their source settings after updating to Craft 3.1. ([#3659](https://github.com/craftcms/cms/issues/3659))
- Fixed a bug where commercial plugins weren’t installable from the Plugin Store.
- Fixed a bug where Matrix block type fields’ `beforeSave()` methods weren’t getting called.
- Fixed a bug where Matrix fields could forget their content table name if they were created with a non-global context.
- Fixed a bug where links to the Plugin Store from Settings → Plugins were 404ing. ([#3664](https://github.com/craftcms/cms/issues/3664))
- Fixed a bug where soft-deleted sections and entry types were still showing up in the Control Panel. ([#3648](https://github.com/craftcms/cms/issues/3648))
- Fixed a bug where an update to Craft 3.1 would fail with a database error in some scenarios.
- Fixed a bug where Plugin Store’s Try buttons would appear as disabled when they should be enabled. ([#3619](https://github.com/craftcms/cms/issues/3619))
- Fixed an error that occurred when updating to Craft 3.1 if there were any relational fields that were missing some expected settings. ([#3641](https://github.com/craftcms/cms/issues/3641))

### Security
- Fixed two XSS vulnerabilities.

## 3.1.1 - 2019-01-16

### Added
- Added support for the `CRAFT_LOG_PHP_ERRORS` PHP constant. ([#3619](https://github.com/craftcms/cms/issues/3619))
- Added `craft\web\User::generateToken()`.

### Changed
- System error message templates no longer parse exception messages as Markdown.

### Fixed
- Fixed a bug where `craft\services\Volumes::getVolumeByHandle()` wasn’t working. ([#3633](https://github.com/craftcms/cms/pull/3633))
- Fixed a bug where the `clear-caches/cp-resources` command could clear out the wrong directory if the `resourceBasePath` config setting began with `@webroot`. ([#3637](https://github.com/craftcms/cms/issues/3637))
- Fixed a bug where eager-loading Matrix blocks would come up empty. ([#3644](https://github.com/craftcms/cms/issues/3644))
- Fixed an error that occurred when updating to Craft 3.1 if there were any Matrix blocks without any sub-fields. ([#3635](https://github.com/craftcms/cms/pull/3635))
- Fixed an error that occurred when updating to Craft 3.1 if there were any Matrix block types left over from a Matrix field that had been converted to something else.
- Fixed an error that occurred when updating to Craft 3.1 if there were any Assets fields that were missing some expected field settings. ([#3641](https://github.com/craftcms/cms/issues/3641))
- Fixed an error that occurred when updating to Craft 3.1 if anything was calling `craft\services\Fields::getLayoutById()` or `getLayoutByType()` before the update was applied.
- Fixed an error that could occur when logging deprecation errors on PostgreSQL. ([#3638](https://github.com/craftcms/cms/issues/3638))
- Fixed a bug where users would get logged out while updating to Craft 3.1, causing a “User is not permitted to perform this action” error.
- Fixed a bug where “JavaScript must be enabled” and “Cookies must be enabled” messages weren’t getting positioned correctly. ([#3639](https://github.com/craftcms/cms/issues/3639))
- Fixed a “Variable "message" does not exist.” error that could occur in the Control Panel.
- Fixed a bug where free plugins weren’t installable from the Plugin Store. ([#3642](https://github.com/craftcms/cms/issues/3642))

### Security
- The Request panel in the Debug Toolbar now redacts any sensitive information. ([#3619](https://github.com/craftcms/cms/issues/3619))
- Fixed two XSS vulnerabilities.

## 3.1.0 - 2019-01-15

> {warning} This is a more complex update than usual, and failed update attempts are not uncommon. Please ensure you have a recent database backup, and we recommend you test the update on a local/staging environment before updating your production server.

### Added
- Added the Project Config, a portable and centralized configuration for system settings. ([#1429](https://github.com/craftcms/cms/issues/1429)) 
- Category groups, elements, entry types, field layouts, global sets, sections, sites, site groups, structures, tag groups, and volumes are now soft-deleted. ([#867](https://github.com/craftcms/cms/issues/867))
- Entries, categories, and users can now be restored within the Control Panel by selecting “Trashed” from the status menu on element index pages, and clicking the “Restore” button.
- Added the System Messages utility for editing system messages, replacing the Settings → Email → System Messages page. ([#3421](https://github.com/craftcms/cms/issues/3421))
- Some Site settings (Base URL), volume settings (Base URL and File System Path), and email settings (System Email Address, Sender Name, HTML Email Template, Username, Password, and Host Name) can now be set to environment variables using a `$VARIABLE_NAME` syntax. ([#3219](https://github.com/craftcms/cms/issues/3219))
- The installer now checks whether a `project.yaml` file exists and applies any changes in it. ([#3291](https://github.com/craftcms/cms/issues/3291))
- Control Panel settings that support environment variables now autosuggest environment variable names (and aliases when applicable) while typing.
- Control Panel settings that define a template path now autosuggest existing template files.
- Added cross-domain support for Live Preview. ([#1521](https://github.com/craftcms/cms/issues/1521))
- Plugins can now have multiple editions.
- Custom fields can now opt out of being included in elements’ search keywords. ([#2600](https://github.com/craftcms/cms/issues/2600))
- Added the `allowAdminChanges` config setting.
- Added the `softDeleteDuration` config setting.
- Added the `storeUserIps` config setting. ([#3311](https://github.com/craftcms/cms/issues/3311))
- Added the `useProjectConfigFile` config setting.
- Added the `gc` console command, which can be used to run garbage collection tasks.
- Added the `project-config/sync` console command. ([#3510](https://github.com/craftcms/cms/issues/3510))
- Added the `trashed` element query param, which can be used to query for elements that have been soft-deleted.
- Added the `expression()` Twig function, for creating new `yii\db\Expression` objects in templates. ([#3289](https://github.com/craftcms/cms/pull/3289))
- Added the `parseEnv()` Twig function.
- Added the `plugin()` Twig function.
- Added the `_includes/forms/autosuggest.html` include template for the Control Panel. 
- Added `Craft::parseEnv()`.
- Added `craft\base\ApplicationTrait::getIsLive()`.
- Added `craft\base\Element::EVENT_AFTER_RESTORE`.
- Added `craft\base\Element::EVENT_BEFORE_RESTORE`.
- Added `craft\base\Element::EVENT_DEFINE_EAGER_LOADING_MAP`.
- Added `craft\base\ElementInterface::afterRestore()`.
- Added `craft\base\ElementInterface::beforeRestore()`.
- Added `craft\base\Field::EVENT_AFTER_ELEMENT_RESTORE`.
- Added `craft\base\Field::EVENT_BEFORE_ELEMENT_RESTORE`.
- Added `craft\base\FieldInterface::afterElementRestore()`.
- Added `craft\base\FieldInterface::beforeElementRestore()`.
- Added `craft\base\Model::EVENT_DEFINE_RULES`.
- Added `craft\base\Plugin::editions()`.
- Added `craft\base\Plugin::is()`.
- Added `craft\base\SavableComponentInterface::beforeApplyDelete()`.
- Added `craft\behaviors\EnvAttributeParserBehavior`.
- Added `craft\controllers\LivePreviewController`.
- Added `craft\db\ActiveRecord::prepareForDb()`.
- Added `craft\db\Command::restore()`.
- Added `craft\db\Command::softDelete()`.
- Added `craft\db\Migration::restore()`.
- Added `craft\db\Migration::softDelete()`.
- Added `craft\db\SoftDeleteTrait`, which can be used by Active Record classes that wish to support soft deletes.
- Added `craft\db\Table`.
- Added `craft\elements\actions\Restore`, which can be included in elements’ `defineActions()` methods to opt into element restoration.
- Added `craft\events\ConfigEvent`.
- Added `craft\events\DeleteElementEvent`, which provides a `$hardDelete` property that can be set to `true` to force an element to be immediately hard-deleted. ([#3403](https://github.com/craftcms/cms/pull/3403))
- Added `craft\helpers\App::editionHandle()`.
- Added `craft\helpers\App::editionIdByHandle()`.
- Added `craft\helpers\App::mailSettings()`.
- Added `craft\helpers\ArrayHelper::firstWhere()`.
- Added `craft\helpers\Db::idByUid()`.
- Added `craft\helpers\Db::idsByUids()`.
- Added `craft\helpers\Db::uidById()`.
- Added `craft\helpers\Db::uidsByIds()`.
- Added `craft\helpers\ProjectConfig`.
- Added `craft\helpers\StringHelper::toWords()`.
- Added `craft\models\FieldLayout::createFromConfig()`.
- Added `craft\models\FieldLayout::getConfig()`.
- Added `craft\models\Section::setEntryTypes()`.
- Added `craft\models\Site::getBaseUrl()`.
- Added `craft\services\AssetTransforms::getTransformByUid()`.
- Added `craft\services\AssetTransforms::EVENT_BEFORE_APPLY_TRANSFORM_DELETE`.
- Added `craft\services\Categories::getGroupByUid()`.
- Added `craft\services\Categories::EVENT_BEFORE_APPLY_GROUP_DELETE`.
- Added `craft\services\Elements::restoreElement()`.
- Added `craft\services\Elements::EVENT_AFTER_RESTORE_ELEMENT`.
- Added `craft\services\Elements::EVENT_BEFORE_RESTORE_ELEMENT`.
- Added `craft\services\Fields::applyFieldDelete()`.
- Added `craft\services\Fields::applyFieldSave()`.
- Added `craft\services\Fields::createFieldConfig()`.
- Added `craft\services\Fields::deleteFieldInternal()`.
- Added `craft\services\Fields::restoreLayoutById()`.
- Added `craft\services\Fields::saveFieldInternal()`.
- Added `craft\services\Fields::EVENT_BEFORE_APPLY_FIELD_DELETE`.
- Added `craft\services\Fields::EVENT_BEFORE_APPLY_GROUP_DELETE`.
- Added `craft\services\Gc` for handling garbage collection tasks.
- Added `craft\services\Path::getConfigBackupPath()`.
- Added `craft\services\ProjectConfig`.
- Added `craft\services\Routes::deleteRouteByUid()`
- Added `craft\services\Sections::getSectionByUid()`.
- Added `craft\services\Sections::EVENT_BEFORE_APPLY_ENTRY_TYPE_DELETE`.
- Added `craft\services\Sections::EVENT_BEFORE_APPLY_SECTION_DELETE`.
- Added `craft\services\Sites::restoreSiteById()`.
- Added `craft\services\Sites::EVENT_BEFORE_APPLY_GROUP_DELETE`.
- Added `craft\services\Sites::EVENT_BEFORE_APPLY_SITE_DELETE`.
- Added `craft\services\Tags::EVENT_BEFORE_APPLY_GROUP_DELETE`.
- Added `craft\services\UserGroups::EVENT_BEFORE_APPLY_GROUP_DELETE`.
- Added `craft\services\Volumes::EVENT_BEFORE_APPLY_VOLUME_DELETE`.
- Added `craft\validators\TemplateValidator`.
- Added `craft\web\Controller::requireCpRequest()`.
- Added `craft\web\Controller::requireSiteRequest()`.
- Added `craft\web\twig\variables\Cp::EVENT_REGISTER_CP_SETTINGS`. ([#3314](https://github.com/craftcms/cms/issues/3314))
- Added `craft\web\twig\variables\Cp::getEnvSuggestions()`.
- Added `craft\web\twig\variables\Cp::getTemplateSuggestions()`.
- Added the ActiveRecord Soft Delete Extension for Yii2.
- Added the Symfony Yaml Component.
- The bundled Vue asset bundle now includes Vue-autosuggest.

### Changed
- The `defaultWeekStartDay` config setting is now set to `1` (Monday) by default, to conform with the ISO 8601 standard.
- Renamed the `isSystemOn` config setting to `isSystemLive`.
- The `app/migrate` web action now applies pending `project.yaml` changes, if the `useProjectConfigFile` config setting is enabled.
- The `svg()` function now strips `<title>`, `<desc>`, and comments from the SVG document as part of its sanitization process.
- The `svg()` function now supports a `class` argument, which will add a class name to the root `<svg>` node. ([#3174](https://github.com/craftcms/cms/issues/3174))
- The `{% redirect %}` tag now supports `with notice` and `with error` params for setting flash messages. ([#3625](https://github.com/craftcms/cms/pull/3625))
- `info` buttons can now also have a `warning` class.
- User permission definitions can now include `info` and/or `warning` keys.
- The old “Administrate users” permission has been renamed to “Moderate users”.
- The old “Change users’ emails” permission has been renamed to “Administrate users”, and now comes with the ability to activate user accounts and reset their passwords. ([#942](https://github.com/craftcms/cms/issues/942))  
- All users now have the ability to delete their own user accounts. ([#3013](https://github.com/craftcms/cms/issues/3013))
- System user permissions now reference things by their UIDs rather than IDs (e.g. `editEntries:<UID>` rather than `editEntries:<ID>`).
- Animated GIF thumbnails are no longer animated. ([#3110](https://github.com/craftcms/cms/issues/3110))
- Craft Tokens can now be sent either as a query string param (named after the `tokenParam` config setting) or an `X-Craft-Token` header.
- Element types that support Live Preview must now hash the `previewAction` value for `Craft.LivePreview`.
- Live Preview now loads each new preview into its own `<iframe>` element. ([#3366](https://github.com/craftcms/cms/issues/3366))
- Assets’ default titles now only capitalize the first word extracted from the filename, rather than all the words. ([#2339](https://github.com/craftcms/cms/issues/2339))
- All console commands besides `setup/*` and `install/craft` now output a warning if Craft isn’t installed yet. ([#3620](https://github.com/craftcms/cms/issues/3620))
- All classes that extend `craft\base\Model` now have `EVENT_INIT` and `EVENT_DEFINE_BEHAVIORS` events; not just classes that extend `craft\base\Component`.
- `craft\db\mysql\Schema::findIndexes()` and `craft\db\pgsql\Schema::findIndexes()` now return arrays with `columns` and `unique` keys.
- `craft\helpers\ArrayHelper::filterByValue()` now defaults its `$value` argument to `true`.
- `craft\helpers\MigrationHelper::doesIndexExist()` no longer has a `$foreignKey` argument, and now has an optional `$db` argument.
- `craft\mail\Mailer::send()` now swallows any exceptions that are thrown when attempting to render the email HTML body, and sends the email as plain text only. ([#3443](https://github.com/craftcms/cms/issues/3443))
- `craft\mail\Mailer::send()` now fires an `afterSend` event with `yii\mail\MailEvent::$isSuccessful` set to `false` if any exceptions were thrown when sending the email, and returns `false`. ([#3443](https://github.com/craftcms/cms/issues/3443))
- `craft\services\Routes::saveRoute()` now expects site and route UIDs instead of IDs.
- `craft\services\Routes::updateRouteOrder()` now expects route UIDs instead of IDs.
- The `craft\helpers\Assets::EVENT_SET_FILENAME` event is now fired after sanitizing the filename.

### Removed
- Removed `craft\elements\User::authData()`.
- Removed `craft\fields\Matrix::getOldContentTable()`.
- Removed `craft\services\Routes::deleteRouteById()`

### Deprecated
- Deprecated `craft\base\ApplicationTrait::getIsSystemOn()`. `getIsLive()` should be used instead.
- Deprecated `craft\helpers\MigrationHelper::dropAllIndexesOnTable()`.
- Deprecated `craft\helpers\MigrationHelper::dropAllUniqueIndexesOnTable()`.
- Deprecated `craft\helpers\MigrationHelper::dropIndex()`.
- Deprecated `craft\helpers\MigrationHelper::restoreForeignKey()`.
- Deprecated `craft\helpers\MigrationHelper::restoreIndex()`.
- Deprecated `craft\models\Info::getEdition()`. `Craft::$app->getEdition()` should be used instead.
- Deprecated `craft\models\Info::getName()`. `Craft::$app->projectConfig->get('system.name')` should be used instead.
- Deprecated `craft\models\Info::getOn()`. `Craft::$app->getIsLive()` should be used instead.
- Deprecated `craft\models\Info::getTimezone()`. `Craft::$app->getTimeZone()` should be used instead.
- Deprecated `craft\services\Routes::getDbRoutes()`. `craft\services\Routes::getProjectConfigRoutes()` should be used instead.
- Deprecated `craft\services\SystemSettings`. `craft\services\ProjectConfig` should be used instead.
- Deprecated `craft\validators\UrlValidator::$allowAlias`. `craft\behaviors\EnvAttributeParserBehavior` should be used instead.

### Fixed
- Fixed a bug where the Dashboard could rapidly switch between two column sizes at certain browser sizes. ([#2438](https://github.com/craftcms/cms/issues/2438))
- Fixed a bug where ordered and unordered lists in field instructions didn’t have numbers or bullets.
- Fixed a bug where switching an entry’s type could initially show the wrong field layout tab. ([#3600](https://github.com/craftcms/cms/issues/3600))
- Fixed an error that occurred when updating to Craft 3 if there were any Rich Text fields without any stored settings.
- Fixed a bug where Craft wasn’t saving Dashboard widget sizes properly on PostgreSQL. ([#3609](https://github.com/craftcms/cms/issues/3609))
- Fixed a PHP error that could occur if the primary site didn’t have a base URL. ([#3624](https://github.com/craftcms/cms/issues/3624))
- Fixed a bug where `craft\helpers\MigrationHelper::dropIndexIfExists()` wasn’t working if the index had an unexpected name.
- Fixed an error that could occur if a plugin attempted to register the same Twig extension twice in the same request.

### Security
- The web and CLI installers no longer suggest `@web` for the site URL, and now attempt to save the entered site URL as a `DEFAULT_SITE_URL` environment variable in `.env`. ([#3559](https://github.com/craftcms/cms/issues/3559))
- Craft now destroys all other sessions associated with a user account when a user changes their password.
- It’s no longer possible to spoof Live Preview requests.

## 3.0.41.1 - 2019-03-12

### Changed
- Craft now requires Twig 2.6.

## 3.0.41 - 2019-02-22

### Changed
- System error message templates no longer parse exception messages as Markdown.

### Security
- Database backup/restore exception messages now redact the database password when using PostgreSQL.
- URLs are no longer allowed in users’ first or last names.
- The Request panel in the Debug Toolbar now redacts any sensitive information. ([#3619](https://github.com/craftcms/cms/issues/3619))
- Fixed XSS vulnerabilities.

## 3.0.40.1 - 2019-02-21

### Fixed
- Fixed a bug where Craft wasn’t always aware of plugin licensing issues. ([#3876](https://github.com/craftcms/cms/issues/3876))

## 3.0.40 - 2019-01-31

### Added
- Added `craft\helpers\App::testIniSet()`.

### Changed
- Craft now warns if `ini_set()` is disabled and [memory_limit](http://php.net/manual/en/ini.core.php#ini.memory-limit) is less than `256M` or [max_execution_time](http://php.net/manual/en/info.configuration.php#ini.max-execution-time) is less than `120` before performing Composer operations.
- `craft\helpers\App::maxPowerCaptain()` now attempts to set the `memory_limit` to `1536M` rather than `-1`.

## 3.0.39 - 2019-01-29

### Changed
- It’s now possible to update disabled plugins.

### Fixed
- Fixed an error that could occur if PHP’s `memory_limit` was set to a higher size (in bytes) than `PHP_INT_MAX`. ([#3717](https://github.com/craftcms/cms/issues/3717))

## 3.0.38 - 2019-01-24

### Added
- Added the `update` command, which can be used to [update Craft from the terminal](https://docs.craftcms.com/v3/updating.html#updating-from-the-terminal).
- Craft now warns if PHP is running in Safe Mode with a [max_execution_time](http://php.net/manual/en/info.configuration.php#ini.max-execution-time) of less than 120 seconds, before performing Composer operations.
- Craft now stores backups of `composer.json` and `composer.lock` files in `storage/composer-backups/` before running Composer operations.
- Added `craft\db\Connection::getBackupFilePath()`.
- Added `craft\helpers\App::phpConfigValueInBytes()`.
- Added `craft\helpers\Console::isColorEnabled()`.
- Added `craft\helpers\Console::outputCommand()`.
- Added `craft\helpers\Console::outputWarning()`.
- Added `craft\helpers\FileHelper::cycle()`.
- Added `craft\services\Composer::$maxBackups`.
- Added `craft\services\Path::getComposerBackupsPath()`.

### Changed
- The `migrate/all` console command now supports a `--no-content` argument that can be passed to ignore pending content migrations.
- Craft now attempts to disable PHP’s memory and time limits before running Composer operations.
- Craft no longer respects the `phpMaxMemoryLimit` config setting if PHP’s `memory_limit` setting is already set to `-1` (no limit).
- Craft now respects Composer’s [classmap-authoritative](https://getcomposer.org/doc/06-config.md#classmap-authoritative) config setting.
- Craft now links to the [Troubleshooting Failed Updates](https://craftcms.com/guides/failed-updates) guide when an update fails.
- `craft\services\Composer::install()` can now behave like the `composer install` command, if `$requirements` is `null`.
- `craft\services\Composer::install()` now has a `$whitelist` argument, which can be set to an array of packages to whitelist, or `false` to disable the whitelist.

## 3.0.37 - 2019-01-08

### Added
- Routes defined in the Control Panel can now have a `uid` token, and URL rules defined in `config/routes.php` can now have a `{uid}` token. ([#3583](https://github.com/craftcms/cms/pull/3583))
- Added the `extraFileKinds` config setting. ([#1584](https://github.com/craftcms/cms/issues/1584))
- Added the `clear-caches` console command. ([#3588](https://github.com/craftcms/cms/pull/3588))
- Added `craft\feeds\Feeds::getFeed()`.
- Added `craft\helpers\StringHelper::UUID_PATTERN`.

### Changed
- Pressing the <kbd>Return</kbd> key (or <kbd>Ctrl</kbd>/<kbd>Command</kbd> + <kbd>Return</kbd>) when a textual cell is focused in an editable table will now change the focus to the same cell in the next row (after creating a new row if necessary.) ([#3576](https://github.com/craftcms/cms/issues/3576))
- The Password input in the web-based Craft setup wizard now has a “Show” button like other password inputs.
- The Feed widget now sets the items’ text direction based on the feed’s language.
- Matrix blocks that contain validation errors now have red titles and alert icons, to help them stand out when collapsed. ([#3599](https://github.com/craftcms/cms/issues/3599))

### Fixed
- Fixed a bug where the “Edit” button on asset editor HUDs didn’t launch the Image Editor if the asset was being edited on another element type’s index page. ([#3575](https://github.com/craftcms/cms/issues/3575))
- Fixed an exception that would be thrown when saving a user from a front-end form with a non-empty `email` or `newPassword` param, if the `password` param was missing or empty. ([#3585](https://github.com/craftcms/cms/issues/3585))
- Fixed a bug where global set, Matrix block, tag, and user queries weren’t respecting `fixedOrder` params.
- Fixed a bug where `craft\helpers\MigrationHelper::renameColumn()` was only restoring the last foreign key for each table that had multiple foreign keys referencing the table with the renamed column.
- Fixed a bug where Date/Time fields could output the wrong date in Live Preview requests. ([#3594](https://github.com/craftcms/cms/issues/3594))
- Fixed a few RTL language styling issues.
- Fixed a bug where drap-and-drop uploading would not work for custom asset selector inputs. ([#3590](https://github.com/craftcms/cms/pull/3590))
- Fixed a bug where Number fields weren’t enforcing thein Min Value and Max Value settings if set to 0. ([#3598](https://github.com/craftcms/cms/issues/3598))
- Fixed a SQL error that occurred when uploading assets with filenames that contained emoji characters, if using MySQL. ([#3601](https://github.com/craftcms/cms/issues/3601))

### Security
- Fixed a directory traversal vulnerability.
- Fixed a remote code execution vulnerability.

## 3.0.36 - 2018-12-18

### Added
- Added the `{{ actionInput() }}` global Twig function. ([#3566](https://github.com/craftcms/cms/issues/3566))

### Changed
- Suspended users are no longer shown when viewing pending or locked users. ([#3556](https://github.com/craftcms/cms/issues/3556))
- The Control Panel’s Composer installer now prevents scripts defined in `composer.json` from running. ([#3574](https://github.com/craftcms/cms/issues/3574))

### Fixed
- Fixed a bug where elements that belonged to more than one structure would be returned twice in element queries.

### Security
- Fixed a self-XSS vulnerability in the Recent Entries widget.
- Fixed a self-XSS vulnerability in the Feed widget.

## 3.0.35 - 2018-12-11

### Added
- Added `craft\models\Section::getHasMultiSiteEntries()`.

### Changed
- Field types that extend `craft\fields\BaseRelationField` now pass their `$sortable` property value to the `BaseElementSelectInput` JavaScript class by default. ([#3542](https://github.com/craftcms/cms/pull/3542))

### Fixed
- Fixed a bug where the “Disabled for Site” entry status option was visible for sections where site propagation was disabled. ([#3519](https://github.com/craftcms/cms/issues/3519))
- Fixed a bug where saving an entry that was disabled for a site would retain its site status even if site propagation had been disabled for the section.
- Fixed a SQL error that occurred when saving a field layout with 4-byte characters (like emojis) in a tab name. ([#3532](https://github.com/craftcms/cms/issues/3532))
- Fixed a bug where autogenerated Post Date values could be a few hours off when saving new entries with validation errors. ([#3528](https://github.com/craftcms/cms/issues/3528))
- Fixed a bug where plugins’ minimum version requirements could be enforced even if a development version of a plugin had been installed previously.

## 3.0.34 - 2018-12-04

### Fixed
- Fixed a bug where new Matrix blocks wouldn’t remember that they were supposed to be collapsed if “Save and continue editing” was clicked. ([#3499](https://github.com/craftcms/cms/issues/3499))
- Fixed an error that occurred on the System Report utility if any non-bootstrapped modules were configured with an array or callable rather than a string. ([#3507](https://github.com/craftcms/cms/issues/3507))
- Fixed an error that occurred on pages with date or time inputs, if the user’s preferred language was set to Arabic. ([#3509](https://github.com/craftcms/cms/issues/3509))
- Fixed a bug where new entries within sections where site propagation was disabled would show both “Enabled Globally” and “Enabled for [Site Name]” settings. ([#3519](https://github.com/craftcms/cms/issues/3519))
- Fixed a bug where Craft wasn’t reducing the size of elements’ slugs if the resulting URI was over 255 characters. ([#3514](https://github.com/craftcms/cms/issues/3514))

## 3.0.33 - 2018-11-27

### Changed
- Table fields with a fixed number of rows no longer show Delete buttons or the “Add a row” button. ([#3488](https://github.com/craftcms/cms/issues/3488))
- Table fields that are fixed to a single row no longer show the Reorder button. ([#3488](https://github.com/craftcms/cms/issues/3488))
- Setting `components.security.sensitiveKeywords` in `config/app.php` will now append keywords to the default array `craft\services\Security::$sensitiveKeywords` array, rather than completely overriding it.
- When performing an action that requires an elevated session while impersonating another user, admin must now enter their own password instead of the impersonated user’s. ([#3487](https://github.com/craftcms/cms/issues/3487))
- The System Report utility now lists any custom modules that are installed. ([#3490](https://github.com/craftcms/cms/issues/3490))
- Control Panel charts now give preference to `ar-SA` for Arabic locales, `de-DE` for German locales, `en-US` for English locales, `es-ES` for Spanish locales, or `fr-FR` for French locales, if data for the exact application locale doesn’t exist. ([#3492](https://github.com/craftcms/cms/pull/3492))
- “Create a new child entry” and “Create a new child category” element actions now open an edit page for the same site that was selected on the index page. ([#3496](https://github.com/craftcms/cms/issues/3496))
- The default `allowedFileExtensions` config setting value now includes `webp`.
- The Craft Support widget now sends `composer.json` and `composer.lock` files when contacting Craft Support.
- It’s now possible to create element select inputs that include a site selection menu by passing `showSiteMenu: true` when including the `_includes/forms/elementSelect.html` Control Panel include template. ([#3494](https://github.com/craftcms/cms/pull/3494))

### Fixed
- Fixed a bug where a Matrix fields’ block types and content table could be deleted even if something set `$isValid` to `false` on the `beforeDelete` event.
- Fixed a bug where a global sets’ field layout could be deleted even if something set `$isValid` to `false` on the `beforeDelete` event.
- Fixed a bug where after impersonating another user, the Login page would show the impersonated user’s username rather than the admin’s.
- Fixed a bug where `craft\services\Sections::getAllSections()` could return stale results if a new section had been added recently. ([#3484](https://github.com/craftcms/cms/issues/3484))
- Fixed a bug where “View entry” and “View category” element actions weren’t available when viewing a specific section or category group.
- Fixed a bug where Craft would attempt to index image transforms.
- Fixed a bug where the Asset Indexes utility could report that asset files were missing even though they weren’t. ([#3450](https://github.com/craftcms/cms/issues/3450))

### Security
- Updated jQuery File Upload to 9.28.0.

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
- Updated svg-sanitize to 0.9.

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
