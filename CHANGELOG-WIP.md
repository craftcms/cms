# Release Notes for Craft CMS 6.0 (WIP)

### Control Panel
- Added a command palette, opened from the search button in the global header or with <kbd>Command</kbd>/<kbd>Ctrl</kbd> + <kbd>K</kbd>. It finds fields, tabs, and Matrix blocks on the current page, control panel pages and settings, entries, assets, users, and releases, and runs actions such as creating entries and settings, publishing releases, clearing caches, and changing preferences, with the same permission checks as the screens they stand in for. It also searches the Craft CMS documentation, and lists recently visited pages when nothing has been typed. ([#20081](https://github.com/craftcms/cms/pull/20081))
- Plain links to control panel pages, such as those in elements’ metadata and other server-rendered HTML, now open with Inertia rather than reloading the page. ([#20118](https://github.com/craftcms/cms/pull/20118))
- Added a “Theme” user preference, with “Light”, “Dark”, and “System” options. “System” is the default. Pages shown to logged-out users, such as the login page, always follow the system’s light or dark setting. ([#19966](https://github.com/craftcms/cms/pull/19966))
- Added a control panel notification center.
- Added flyout menus to the global control panel nav, for accessing sub-pages of non-selected sections. ([#19648](https://github.com/craftcms/cms/pull/19648))
- Control panel success, notice, and error messages now stack, newest first, and can be swiped away. Errors and messages with actions stay until dismissed.
- Admin table links and create actions can now open in a slideout with a custom width, via the `slideoutWidth` link option and new `slideout` and `slideoutWidth` arguments on `CraftCms\Cms\Ui\Nodes\Table::createAction()`.
- Double-clicking an admin table row now opens its link's slideout, if it has one.
- Sortable checkbox selects, such as “Card Attributes”, “Table Columns”, and “Default Table Columns”, now list only the selected options, with an “Add item” menu for the rest. ([#20001](https://github.com/craftcms/cms/pull/20001), [#20036](https://github.com/craftcms/cms/pull/20036))
- Slideouts can now be resized horizontally, and remember their width. ([#19978](https://github.com/craftcms/cms/pull/19978))
- Slideouts’ details panes that were folded away for lack of room now reopen once the slideout is wide enough again. ([#19978](https://github.com/craftcms/cms/pull/19978))
- Content within black cards, chips, and badges, such as buttons, badges, and links, is now styled as in the dark theme.
- Card view designers now preview users with a sample user’s name, default avatar, and active status.
- The control panel now warns users a couple of minutes before their session expires, with options to stay signed in or sign out, and lets them sign back in without leaving the page once it has. ([#20086](https://github.com/craftcms/cms/pull/20086))
- The global nav now groups Entries and Assets under a “Content” heading, and Users, GraphQL, Utilities, Settings, and Plugin Store under an “Administration” heading. ([#19903](https://github.com/craftcms/cms/pull/19903))
- The Plugin Store is now part of the control panel rather than a standalone app, with the store’s categories in the secondary nav, plugin pages whose Overview, Reviews, Editions, and Changelog tabs each have their own URL, and a cart that opens in a dialog from the store’s toolbar.
- Dates are now shown in the calendar of the user’s formatting locale, such as the Persian calendar for Persian or the Buddhist calendar for Thai. Named and ICU date formats in Twig, such as `|date('short')`, follow the site locale’s calendar too. ([#8741](https://github.com/craftcms/cms/discussions/8741), [#20085](https://github.com/craftcms/cms/pull/20085))
- Element editor slideouts now have the full Save menu, including “Save and continue editing”, as well as an action menu and an “Open in a new tab” button. ([#19748](https://github.com/craftcms/cms/pull/19748), [#19854](https://github.com/craftcms/cms/pull/19854))
- Modals, including the Customize Sources modal and element selection modals, can now be resized. ([#20008](https://github.com/craftcms/cms/pull/20008))
- Pressing <kbd>Ctrl</kbd>/<kbd>Command</kbd> + <kbd>Return</kbd> in a text input or textarea now submits its form. ([#20025](https://github.com/craftcms/cms/pull/20025))
- Refreshed the control panel’s styling, including modals, buttons, form inputs, editable tables, chips, Matrix blocks, status badges, and file and folder icons. ([#19942](https://github.com/craftcms/cms/pull/19942), [#20008](https://github.com/craftcms/cms/pull/20008), [#20085](https://github.com/craftcms/cms/pull/20085))
- Link and Email field values in element indexes and cards now stay on one line, cut off with an ellipsis, after a button that shows the full address with options to open or copy it. Hovering over the link shows the same details. ([#20123](https://github.com/craftcms/cms/pull/20123))

### Workflows
- Added publishing workflows, which control when drafts may be applied to entries. Workflows are configured from Settings → Workflows and assigned to sections. (Craft Pro and Enterprise only.) ([#19667](https://github.com/craftcms/cms/pull/19667))
- Changes to enabled entries in sections with a workflow are now saved as drafts, which must be submitted for review and approved by each workflow stage before they can be applied. ([#19667](https://github.com/craftcms/cms/pull/19667))
- Added a “Workflow” element details tab, for submitting drafts for review, reviewing them, and viewing workflow activity history. ([#19667](https://github.com/craftcms/cms/pull/19667))
- Added the “User Review” workflow stage type, which supports per-group approval requirements, carried-over approvals, change requests, and email notifications for reviewers. ([#19667](https://github.com/craftcms/cms/pull/19667))
- Added releases, which group saved drafts of entries and other top-level elements so they can be scheduled and published together, atomically. Drafts in a release can be queried with the `releaseId` element query param and GraphQL argument, and the “Query for releases” GraphQL schema permission adds `releases` and `release` queries.

### Elements
- Added Markdown comments to element activity timelines, with support for editing, removing, structured user mentions, and email notifications.
- Element index searches now update results as users type. ([#19754](https://github.com/craftcms/cms/pull/19754))
- Clicking anywhere on an element index table row or card (other than an interactive control) now selects it. ([#19351](https://github.com/craftcms/cms/pull/19351))
- Element index View menus now have a “Thumb Size” setting in table, structure, and thumbnail views, with “Small”, “Medium”, and “Large” options, plus “XL” and “2XL” in thumbnail view. Each view remembers its own size, and chips take a size to suit their thumbnails. ([#20083](https://github.com/craftcms/cms/discussions/20083), [#20124](https://github.com/craftcms/cms/pull/20124))
- Thumbnail views in element indexes and relational fields now show each element as a chip, with its thumbnail above its label. ([#20083](https://github.com/craftcms/cms/discussions/20083), [#20124](https://github.com/craftcms/cms/pull/20124))
- Element index sources can now show a count of their elements in the control panel nav, via a new “Show count” setting in the “Customize sources” modal. Entries, assets, and users support it.
- Apps can now manage their own Eloquent models as elements in the control panel, with an element index and nav item, an element editor, permissions or model policies, search, activity history, relation fields, and Twig element queries. Elements are kept in sync with their models in both directions, and the models and their tables are left unchanged. ([#19971](https://github.com/craftcms/cms/pull/19971))
- Element edit pages now have a “Sites” pane in their details sidebars on multi-site installs, in place of the “Status” pane. It lists the sites the element is saved to, with status toggles and links to the element in each site, and lets authors add the element to other sites, or remove it from them. Element editor slideouts can be switched to other sites from it. ([#20061](https://github.com/craftcms/cms/pull/20061))
- Deleting an element for the only site it exists in now soft-deletes it, unless it’s a draft or nested element. ([#20060](https://github.com/craftcms/cms/pull/20060))

#### Entries
- Added support for disabling Matrix entries for individual sites. ([#19686](https://github.com/craftcms/cms/pull/19686))
- Added the “Show the Post Date field” and “Show the Expiry Date field” entry type settings. ([#17675](https://github.com/craftcms/cms/pull/17675))
- Added support for controller actions and named Laravel routes in section and Matrix site settings, via a single “Route To” setting in place of Craft 5’s template setting, which suggests the app’s named routes and controller actions as well as templates. ([#19762](https://github.com/craftcms/cms/pull/19762), [#19942](https://github.com/craftcms/cms/pull/19942))
- Collapsed Matrix blocks are now remembered per site, like their enabled status. ([#20119](https://github.com/craftcms/cms/pull/20119))

### Assets
- Added background uploads in the Assets index, which continue during control panel navigation, with persistent progress, retry, cancellation, and filename-conflict controls.
- Images uploaded from the Assets index that look like copies of images that are already uploaded, even resized or recompressed, now wait within their upload notification, which shows the existing images’ chips and offers to cancel the upload, keep both, or replace the existing image’s file, like filename conflicts. ([#20113](https://github.com/craftcms/cms/pull/20113))
- Added Undo/Redo support and a “Revert to original” button to the image editor. ([#19600](https://github.com/craftcms/cms/pull/19600))
- Image thumbnails and previews in the control panel now show a blurred placeholder based on the image’s colors while they load. ([#19750](https://github.com/craftcms/cms/pull/19750))
- Volumes now have a “Propagation Method” setting, which can be set to “Let each asset choose which sites it should be saved to”. New volumes default to it, as do existing volumes on single-site installs, while existing volumes on multi-site installs continue to save assets to all sites. Assets are now initially saved to the site they were uploaded from, and can be deleted for individual sites. ([#20060](https://github.com/craftcms/cms/pull/20060))
- Added the `trashedAssetDisk` config setting. When it’s set, soft-deleting an asset moves its file to that disk, and restoring the asset moves it back. Otherwise, soft-deleting an asset deletes its file, and the asset can’t be restored, unless it was deleted along with its volume. ([#8745](https://github.com/craftcms/cms/discussions/8745), [#20093](https://github.com/craftcms/cms/pull/20093))
- Images now keep the metadata embedded in their files when they’re uploaded, replaced, or indexed, such as their title, description, alt text, creator, copyright notice, keywords, date taken, camera, and location. Uploaded images are titled and given alt text from it, embedded keywords and descriptions are searchable, and asset edit pages’ Info panels show it along with the image’s type, its main colors, and a map of where it was taken. ([#20103](https://github.com/craftcms/cms/pull/20103))
- Asset indexes can now show “Image Type”, “Colors”, “Creator”, “Credit”, “Copyright”, “Date Taken”, “Camera”, and “Embedded Keywords” columns and card attributes. Asset indexes’ “Location” column and card attribute, which show an asset’s folder, are now “Folder”. ([#20103](https://github.com/craftcms/cms/pull/20103))
- Images uploaded straight to a remote filesystem, such as an S3 bucket, are no longer downloaded to be sampled, and uploads staged on an S3 disk are copied into their volume within S3 when the disks share credentials and an endpoint. ([#20103](https://github.com/craftcms/cms/pull/20103))
- Asset conditions, such as for index filters and Assets fields’ sources, now have an “Orientation” rule, which filters images by whether they’re landscape, portrait, or square. Images whose width and height differ by up to 2% count as square. ([#20114](https://github.com/craftcms/cms/pull/20114))
- Removed Craft-managed filesystems and their control panel settings. Volumes and Craft asset transformers now reference Laravel filesystem disks configured in `config/filesystems.php`, and each defines whether its assets have public URLs. Existing filesystem references are migrated to matching disks automatically, with an actionable error if a disk isn’t configured. ([#19650](https://github.com/craftcms/cms/pull/19650))
- Added configurable asset transformers, which can be managed from Settings → Assets → Asset Transformers and assigned to asset volumes by handle.
- Added support for the libvips image driver via the optional `intervention/image-driver-vips` package.
- Added BMP, HEIC, ICO, JPEG 2000, JPEG XL, and TIFF image transform formats, when supported by the active image driver.
- Added the `defaultAssetTransformer` config setting.
- Added the `uploadSessionDisk` config setting, which determines the disk used to stage upload sessions. ([#19650](https://github.com/craftcms/cms/pull/19650))
- Renamed the `tempAssetUploadFs` config setting to `tempAssetUploadDisk`, which now references a Laravel filesystem disk. ([#19650](https://github.com/craftcms/cms/pull/19650))
- Moved the `generateTransformsBeforePageLoad` config setting to Craft asset transformer profiles.
- Image assets now have [BlurHash](https://blurha.sh) strings, available via `asset.blurhash` in templates. ([#19755](https://github.com/craftcms/cms/pull/19755))
- `<img>` tags returned by `asset.getImg()` now have a blurred placeholder as their background, unless the image has transparent regions. ([#19750](https://github.com/craftcms/cms/pull/19750))
- Added the `blurhash`, `analysis`, and `placeholderDataUrl` fields to assets in GraphQL queries. `analysis` holds what was found in an image, such as its colors, embedded metadata, and type. ([#19740](https://github.com/craftcms/cms/pull/19740), [#19750](https://github.com/craftcms/cms/pull/19750), [#19755](https://github.com/craftcms/cms/pull/19755), [#20103](https://github.com/craftcms/cms/pull/20103))
- Images can now be queried by orientation via the `orientation` asset query param and GraphQL argument, and image assets’ orientations are available via `asset.orientation` in templates and the `orientation` GraphQL field. ([#20114](https://github.com/craftcms/cms/pull/20114))

#### Users & Auth
- Users can now connect their accounts to one or more Laravel Socialite providers, which are listed on the login page. ([#19202](https://github.com/craftcms/cms/pull/19202))
- Added the `authGuard` and `authPasswordBroker` config settings, allowing Craft authentication to use a dedicated Laravel guard, provider, and password broker. ([#19598](https://github.com/craftcms/cms/issues/19598))
- The `loginPath` config setting is now `false` by default.

### Conditions
- Added support for nested condition groups. ([#19587](https://github.com/craftcms/cms/pull/19587))

### Fields & Field Layouts
- Added sidebar field layout tabs, whose fields are shown in element edit forms’ details sidebar. ([#20005](https://github.com/craftcms/cms/pull/20005))
- Field layout tabs can now have icons. ([#20005](https://github.com/craftcms/cms/pull/20005))
- Added a new core Markdown field type. ([#18960](https://github.com/craftcms/cms/pull/18960))
- Added a “Selectable {Type} Condition” setting to Link and Markdown fields’ Entry and Asset link types. ([#19622](https://github.com/craftcms/cms/pull/19622))
- Added a “Default Row Values” setting to Table fields. ([#3621](https://github.com/craftcms/cms/issues/3621))
- Table field columns are now configured with cell settings forms rather than JSON option inputs.
- Table fields now have a “Link” column type, with the same settings as Link fields. Link cells return `CraftCms\Cms\Field\Data\LinkData` objects, and Table fields now track the elements they link to. ([#16009](https://github.com/craftcms/cms/discussions/16009), [#19998](https://github.com/craftcms/cms/pull/19998))
- Table field column settings now open in a slideout. ([#19998](https://github.com/craftcms/cms/pull/19998))
- Table cell validation errors now name the column. ([#19998](https://github.com/craftcms/cms/pull/19998))
- Table columns with invalid settings are now flagged in the Table field’s settings, and link setting validation errors in Link, Markdown, and Table fields now name the setting. ([#19998](https://github.com/craftcms/cms/pull/19998))
- Added support for configuring field layout field instruction positions.
- Field layouts with a card view designer now show it and the content tab designer in “Content” and “Appearance” tabs, with Generated Fields in the Content tab. ([#20001](https://github.com/craftcms/cms/pull/20001)), ([#20003](https://github.com/craftcms/cms/pull/20003))
- Entry types’ Icon and Color settings are now in their field layout designer’s Appearance tab, above Card Attributes. ([#20001](https://github.com/craftcms/cms/pull/20001))
- Card view designers’ previews now reflect the owner’s color, icon, and name, such as an entry type’s, as they’re edited. ([#20001](https://github.com/craftcms/cms/pull/20001))
- Entry type and user group descriptions, fields’ default instructions, tips, and warnings, and field layout elements’ instructions, tips, and warnings now have Markdown inputs. ([#20001](https://github.com/craftcms/cms/pull/20001))
- Removed the “Show unpermitted entries” setting from Entries, Link, and Markdown fields, in favor of the “Viewable” condition rule in the “Selectable Entries Condition” setting. ([#19611](https://github.com/craftcms/cms/pull/19611), [#19622](https://github.com/craftcms/cms/pull/19622))
- Removed the “Show unpermitted files” and “Allowed File Types” settings from Assets, Link, and Markdown fields, in favor of “Viewable” and “File Type” condition rules in the “Selectable Assets Condition” setting. ([#19611](https://github.com/craftcms/cms/pull/19611), [#19622](https://github.com/craftcms/cms/pull/19622))
- Custom fields now have “Default Tip” and “Default Warning” settings. ([#19976](https://github.com/craftcms/cms/pull/19976))
- Relational fields now have a “Paste” button when the clipboard holds elements they could relate.

### Mail
- Removed legacy `projectConfig.email` mail settings and mail transport adapter configuration in favor of Laravel's `mail` config and drivers.

### Accessibility
- Improved the accessibility of the control panel, including element indexes, element edit screens, and the login page. ([#19025](https://github.com/craftcms/cms/pull/19025), [#19520](https://github.com/craftcms/cms/pull/19520), [#19699](https://github.com/craftcms/cms/pull/19699), [#19749](https://github.com/craftcms/cms/pull/19749), [#19785](https://github.com/craftcms/cms/pull/19785))
- Added a “Skip to messages” link, and errors are now announced to screen readers immediately.
- Error summaries now receive focus when a form fails validation, and each error links to its field, switching tabs if needed. ([#20044](https://github.com/craftcms/cms/issues/20044))

### Administration

#### AI
- Added Settings → Extend → Connections, where administrators choose the providers and models that plugins’ AI features use. Providers come from the `ai.providers` config, with keys set through environment variables. AI features stay off until a provider is chosen. ([#20006](https://github.com/craftcms/cms/pull/20006))
- Added image analysis AI tasks: “Generate alt text”, “Generate titles”, “Detect focal points”, “Tag images”, and “Classify images”. Images are analyzed by the tasks that are set up when they’re uploaded or replaced, or when asset indexing finds a new or changed file, filling in only what they’re missing. ([#20103](https://github.com/craftcms/cms/pull/20103))
- Added the “Analyze images” asset action, and an “Analyze images” option to the Asset Indexes utility and `index-assets` commands, which queue images to be analyzed. ([#20103](https://github.com/craftcms/cms/pull/20103))
- Alternative Text fields now have a “Generate alt text” action, which fills in suggested alt text for editors to review before saving. ([#20103](https://github.com/craftcms/cms/pull/20103))
- Assets can now be filtered by what kind of image they are, as found by the “Classify images” AI task, with the “Image Type” asset condition rule. ([#20103](https://github.com/craftcms/cms/pull/20103))

#### MCP
- Added authenticated and configurable public Model Context Protocol (MCP) servers for inspecting and managing Craft sites, content, assets, users, plugins, and project config. ([#19846](https://github.com/craftcms/cms/pull/19846))
- Added the `mcp:read`, `mcp:write`, and `mcp:admin` OAuth scopes for the authenticated MCP server. Users choose an access level when they authorize an MCP client, and it defaults to read only. ([#20080](https://github.com/craftcms/cms/pull/20080))
- The OAuth authorization page now shows where the user will be redirected, and can no longer be displayed in a frame. ([#20105](https://github.com/craftcms/cms/pull/20105))
- Added the `mcp:serve` command, which serves the authenticated MCP server over stdio as a named user. ([#19846](https://github.com/craftcms/cms/pull/19846))

#### General
- Replaced Craft’s system status with Laravel maintenance mode, including an admin-only Maintenance Mode control in General Settings.
- Added support for Markdown-based custom Dashboard widgets in the application's `resources/widgets/` directory. ([#19319](https://github.com/craftcms/cms/pull/19319))
- Added support for SQLite databases, including backups and restores. ([#18803](https://github.com/craftcms/cms/pull/18803), [#19149](https://github.com/craftcms/cms/pull/19149))
- Added support for configuring the system time zone during installation. ([#18794](https://github.com/craftcms/cms/pull/18794))
- Added the `autoEagerLoadElements` config setting (`true` by default), which determines whether element queries should be automatically lazy eager-loaded. ([#19637](https://github.com/craftcms/cms/pull/19637))
- Added the `compiledTemplatesPath` config setting. ([#18861](https://github.com/craftcms/cms/pull/18861))
- Added the `defaultRateLimit` and `defaultRateLimitDuration` config settings, which configure the rate limits for login, password reset, and two-step verification requests. ([#20054](https://github.com/craftcms/cms/pull/20054))
- Environment variable and alias settings fields now show suggestions after typing `$` or `@`.
- The “Assets” settings nav item is now listed under “Content”, and “Globals”, “Categories”, and “Tags” are now listed under a new “Deprecated” heading, if those concepts are supported for the project. ([#19670](https://github.com/craftcms/cms/pull/19670))
- The System Messages utility now shows each message in a card, and edits messages in a slideout with a Markdown editor. Typing `{{` in a message’s subject or body suggests the Twig variables it can use. ([#19946](https://github.com/craftcms/cms/pull/19946))
- Removed the `isSystemLive` config setting and the `craft:on` and `craft:off` commands. Laravel maintenance mode should be used instead.

### Development

#### Routing & URLs
- Logging out now requires a CSRF-protected POST request. ([#19252](https://github.com/craftcms/cms/pull/19252))
- Base site URLs, including `url('')`, `siteUrl('')`, and homepage URLs, now respect the `addTrailingSlashesToUrls` config setting, so they no longer end with a trailing slash by default. The `siteUrl` Twig variable still always ends with a trailing slash. ([#19723](https://github.com/craftcms/cms/pull/19723))
- Updated paginated requests to resolve the current page from the configured `pageTrigger` query parameter rather than path-style pagination segments.
- Using routes in `config/routes.php` is no longer supported. Register routes using [Laravel's routing](https://laravel.com/docs/12.x/routing) instead.
- Routes outside the `craft` middleware group can now use `Route::allowDuringMaintenance()` to stay reachable during maintenance mode. ([#20095](https://github.com/craftcms/cms/pull/20095))

#### Templates
- It’s now possible to load Blade views within Twig templates. ([#19148](https://github.com/craftcms/cms/pull/19148))
- Craft now registers several Blade directives, bringing near feature parity with Twig templating. ([#19148](https://github.com/craftcms/cms/pull/19148))
- Reference tags now support fallback values when no attribute is specified. ([#17688](https://github.com/craftcms/cms/pull/17688))
- Elements no longer include custom field values when iterated over. ([#19004](https://github.com/craftcms/cms/issues/19004))
- Element save requests no longer enable the element when no status is posted. Craft 5 resolved the status as `enabled ?? (setEnabled ? true : null)`, so a POST carrying none of `enabled`, `setEnabled`, or `enabledForSite` force-enabled the element, and posting `setEnabled=0` was how a request opted out of that. A posted status now decides on its own, and a save that doesn’t carry one leaves the element’s status untouched — which is what lets partial/delta saves keep a disabled element disabled. The `setEnabled` param has nothing left to suppress and is ignored.
- Updated Twig `{% paginate %}` queries to use Laravel paginators and generate query-string pagination URLs based on the `pageTrigger` general config setting.
- Added `|sanitize` Twig filter for sanitizing HTML with `CraftCms\Cms\Support\HtmlSanitizer\HtmlSanitizers`.
- Renamed the `|money` Twig filter’s `formatLocale` argument to `locale`.
- Added the `{% render %}` Twig tag. `{% render %}` tags and `render()` methods are allowed within the Twig sandbox. ([#19988](https://github.com/craftcms/cms/pull/19988))
- Templates can now choose white or black text for legibility over an image or one of its edges, with `asset.analysis.colors.textColor()`, which also gives the text’s contrast ratio. ([#20103](https://github.com/craftcms/cms/pull/20103))

#### GraphQL
- GraphQL schemas can now include read-only components for sites, sections, entry types, volumes, and user groups, which add `site` fields to elements, `volume` fields to assets, and `groups` fields to users. ([#20055](https://github.com/craftcms/cms/pull/20055))
- Added the `hasUrls` field to volumes in GraphQL queries.
- GraphQL mutations now return an error when new Matrix entries aren’t listed in `sortOrder`, rather than ignoring them, and each Matrix entry input can only specify one entry type. ([#19965](https://github.com/craftcms/cms/pull/19965))

#### Debugging
- Removed support for the Debug Toolbar. [Laravel Debugbar](https://laraveldebugbar.com) can be used instead. ([#18812](https://github.com/craftcms/cms/pull/18812))
- Added “Elements” and “Deprecations” panels to Laravel Debugbar. ([#18897](https://github.com/craftcms/cms/pull/18897))

#### Migrations
- Craft and Yii's migrations have been removed in favor of [Laravel migrations](https://laravel.com/docs/12.x/migrations).
- The `php craft fields:merge` and `php craft entry-types:merge` commands will now generate Laravel migrations.

#### Deprecations
- Deprecated support for categories, global sets, and tags. ([#18009](https://github.com/craftcms/cms/pull/18009))
- Deprecated the `elevatedSessionDuration` general config setting. The `auth.password_timeout` config value should be used instead. To disable password confirmation (elevated sessions), you now set this value to `-1` instead of `0`.
  - Elevated sessions now work through [Laravel's password confirmation](https://laravel.com/docs/12.x/authentication#password-confirmation) system.
- Deprecated the `errorTemplatePrefix` general config setting. Configure [Laravel's custom error pages](https://laravel.com/docs/13.x/errors#custom-http-error-pages) instead.
- Deprecated the `testToEmailAddress` general config setting. `Illuminate\Support\Facades\Mail::alwaysTo()` should be used instead.
- Deprecated the `blowfishHashCost` general config setting in favor of Laravel's `hashing.bcrypt.rounds` config or the `BCRYPT_ROUNDS` environment variable.
- Deprecated the `csrfTokenName`, `enableCsrfCookie`, and `enableCsrfProtection` general config settings. ([#18806](https://github.com/craftcms/cms/pull/18806))
- Deprecated `craft.app.config.general` in Twig. `app.config.craft.general` should be used instead.
- Deprecated `craft.app.userPermissions`. `craft.userPermissions` should be used instead.

### System
- Replaced `pixelandtonic/imagine` with `intervention/image` for image manipulation.
- Image assets now store color data sampled from their file when it’s uploaded, replaced, or indexed, including the image’s dominant color, a grid of the average colors of its regions, its main colors and how much of the image each covers, and how light its regions are. Control panel uploads sample it in the browser. ([#19740](https://github.com/craftcms/cms/pull/19740), [#19750](https://github.com/craftcms/cms/pull/19750), [#20103](https://github.com/craftcms/cms/pull/20103))
- Control panel resources are now provided by a `craftcms/cms-assets` package. ([#19162](https://github.com/craftcms/cms/pull/19162))
- Login attempts are now rate limited.
- Two-factor authentication recovery codes are now encrypted at rest. ([#19252](https://github.com/craftcms/cms/pull/19252))
- MySQL database backups and restores now carry over SSL/TLS settings from the system’s MySQL option files, if the database connection doesn’t have any `PDO::MYSQL_ATTR_SSL_*` attributes set, and respect the `PDO::MYSQL_ATTR_SSL_CAPATH` and `PDO::MYSQL_ATTR_SSL_CIPHER` attributes. ([#19366](https://github.com/craftcms/cms/pull/19366))
- Fixed a bug where multi-instance fields’ search keywords were indexed per field rather than per instance, so searching by one instance’s handle could match other instances’ values. ([#13991](https://github.com/craftcms/cms/pull/13991))

### Extensibility

#### Plugins
- Links to control panel pages in plugins’ server-rendered HTML now open with Inertia. Pages that aren’t Inertia pages still load in full, after an Inertia request. Links to files or other responses that aren’t HTML need `download` or `data-inertia="false"`, on the link or an element around it, as Inertia can’t open them. ([#20118](https://github.com/craftcms/cms/pull/20118))
- Plugins should no longer define `extra.laravel.providers` in `composer.json`. ([#19263](https://github.com/craftcms/cms/pull/19263))
- Added support for defining standard plugin settings pages with the control panel UI system, via `CraftCms\Cms\Plugin\Plugin::settingsUi()`. ([#19439](https://github.com/craftcms/cms/pull/19439))
- Added support for plugins to register Laravel scheduled tasks that run via `php artisan schedule:run`.
- Added support for custom publishing workflow stage types. ([#19667](https://github.com/craftcms/cms/pull/19667))
- Plugins can register their element types, field types, link types, widgets, utilities, GraphQL types, queries, mutations, and directives, and site template roots, via `CraftCms\Cms\Plugin\Plugin` properties such as `$elementTypes` and `$fieldTypes`, or with the `register()` methods of registries such as `CraftCms\Cms\Field\FieldTypes`. ([#19270](https://github.com/craftcms/cms/pull/19270), [#19307](https://github.com/craftcms/cms/pull/19307))
- Plugin settings are now persisted based on the settings model’s public properties, via `CraftCms\Cms\Plugin\PluginSettings::configData()`. Settings stored in private properties with getter and setter methods must be redeclared as public properties, or included by overriding `configData()`.
- Plugins’ control panel nav items can now be shown as groups, by calling `group(true)` on the item returned by `getCpNavItem()` and passing its items to `subnav()`. ([#19903](https://github.com/craftcms/cms/pull/19903))
- Plugin bundles can now import the control panel’s components and composables from the `@craftcms/cp` import-map module, including `ElementIndexPage`, `ElementEditor`, `AdminTable`, and `useCraftTable`. ([#19912](https://github.com/craftcms/cms/issues/19912))
- Added `CraftCms\Cms\Element\Contracts\ElementInterface::supportsSourceBadgeCounts()`, `sourceSupportsBadgeCount()`, and `sourceBadgeCount()`, and the `defaultBadgeCount` source key, for element types whose sources can show a count of their elements.
- Added `CraftCms\Cms\Cp\Data\ActionItem::$badgeLabel`. Element sources’ `badgeLabel` keys are now passed to screen readers in the control panel nav.
- `<craft-badge-indicator>` now has a `max` property, which sets the largest count shown before it’s shortened to “99+”. Set it to `Infinity` to show the whole count. Counts are now formatted for the page’s language.
- Added the `thumbSize` config option to `CraftCms\Cms\Cp\Html\ElementHtml::chipHtml()` and `elementChipHtml()`, which sizes a chip’s thumbnail independently of the chip (`sm`, `md`, `lg`, `xl`, or `2xl`), and a `medium` chip size (`ElementHtml::CHIP_SIZE_MEDIUM`). Chips without a `size` take the one that suits their thumbnail. ([#20083](https://github.com/craftcms/cms/discussions/20083), [#20124](https://github.com/craftcms/cms/pull/20124))
- Plugins can now make their element types available through the `elements.*` MCP tools.
- Action menu items added via `craft\base\Element::EVENT_DEFINE_ACTION_MENU_ITEMS`, or by plugin element types’ `safeActionMenuItems()` and `destructiveActionMenuItems()` overrides, are only shown in the Inertia element editor and element chips if they define a `url` or `action`. ([#19783](https://github.com/craftcms/cms/pull/19783))
- Plugins can no longer register custom Craft filesystem types. Filesystem types registered through `craft\base\Fs` remain supported for legacy plugins via the Yii adapter. ([#19650](https://github.com/craftcms/cms/pull/19650))

##### Added
- The base `CraftCms\Cms\Plugin\Plugin` class is now a [Laravel ServiceProvider](https://laravel.com/docs/12.x/providers) which provides a new way to register components for your plugins.

##### Deprecations
- Deprecated `craft\base\PluginInterface`. `CraftCms\Cms\Plugin\Contracts\PluginInterface` should be used instead.
- Deprecated `craft\base\PluginTrait`.
- Deprecated `craft\base\Plugin`. `CraftCms\Cms\Plugin\Plugin` should be used instead.
- Deprecated `craft\base\Plugin::getVersion()` and `setVersion()`. The `$version` property should be used instead, since Craft 6 plugins don’t have these methods. ([#19850](https://github.com/craftcms/cms/pull/19850))
- Deprecated `craft\errors\InvalidLicenseKeyException`. `CraftCms\Cms\Plugin\Exceptions\InvalidLicenseKeyException` should be used instead.
- Deprecated `craft\errors\InvalidPluginException`. `CraftCms\Cms\Plugin\Exceptions\InvalidPluginException` should be used instead.
- Deprecated `craft\errors\InvalidSubpathException`. `CraftCms\Cms\Filesystem\Exceptions\InvalidSubpathException` should be used instead.
- Deprecated `craft\services\Plugins`. `CraftCms\Cms\Plugin\Plugins` should be used instead.

##### Controllers
- Removed `craft\controllers\PluginsController`. Use `CraftCms\Cms\Http\Controllers\PluginsController` instead.

##### Commands
- Removed `craft\console\controllers\PluginController` in favor of:
  - `CraftCms\Cms\Plugin\Commands\DisableCommand` -> `php craft plugin:disable`
  - `CraftCms\Cms\Plugin\Commands\EnableCommand` -> `php craft plugin:enable`
  - `CraftCms\Cms\Plugin\Commands\InstallCommand` -> `php craft plugin:install`
  - `CraftCms\Cms\Plugin\Commands\UninstallCommand` -> `php craft plugin:uninstall`
  - `CraftCms\Cms\Plugin\Commands\ListCommand` -> `php craft plugin:list`

##### Events
- Deprecated `craft\events\PluginEvent` in favor of the following new events:
  - `craft\base\Plugin::EVENT_BEFORE_SAVE_SETTINGS` => `CraftCms\Cms\Component\Events\ComponentEvent`
  - `craft\base\Plugin::EVENT_AFTER_SAVE_SETTINGS` => `CraftCms\Cms\Component\Events\ComponentEvent`
  - `craft\services\Plugins::EVENT_BEFORE_DISABLE_PLUGIN` => `CraftCms\Cms\Plugin\Events\PluginDisabling`;
  - `craft\services\Plugins::EVENT_BEFORE_ENABLE_PLUGIN` => `CraftCms\Cms\Plugin\Events\PluginEnabling`;
  - `craft\services\Plugins::EVENT_BEFORE_INSTALL_PLUGIN` => `CraftCms\Cms\Plugin\Events\PluginInstalling`;
  - `craft\services\Plugins::EVENT_BEFORE_LOAD_PLUGINS` => `CraftCms\Cms\Plugin\Events\PluginsLoading`;
  - `craft\services\Plugins::EVENT_BEFORE_SAVE_PLUGIN_SETTINGS` => `CraftCms\Cms\Plugin\Events\SavingPluginSettings`;
  - `craft\services\Plugins::EVENT_BEFORE_UNINSTALL_PLUGIN` => `CraftCms\Cms\Plugin\Events\PluginUninstalling`;
  - `craft\services\Plugins::EVENT_AFTER_DISABLE_PLUGIN` => `CraftCms\Cms\Plugin\Events\PluginDisabled`;
  - `craft\services\Plugins::EVENT_AFTER_ENABLE_PLUGIN` => `CraftCms\Cms\Plugin\Events\PluginEnabled`;
  - `craft\services\Plugins::EVENT_AFTER_INSTALL_PLUGIN` => `CraftCms\Cms\Plugin\Events\PluginInstalled`;
  - `craft\services\Plugins::EVENT_AFTER_LOAD_PLUGINS` => `app()->booted(fn() => …)`;
  - `craft\services\Plugins::EVENT_AFTER_SAVE_PLUGIN_SETTINGS` => `CraftCms\Cms\Plugin\Events\PluginSettingsSaved`;
  - `craft\services\Plugins::EVENT_AFTER_UNINSTALL_PLUGIN` => `CraftCms\Cms\Plugin\Events\PluginUninstalled`;

#### Field Layouts & Forms
- Added `CraftCms\Cms\FieldLayout\FieldLayoutComponent::settingsUi()`.
- Added `CraftCms\Cms\FieldLayout\FieldLayoutComponent::settingsNodes()`.
- Added `CraftCms\Cms\FieldLayout\FieldLayoutComponent::conditionalSettingsNodes()`.
- Added `CraftCms\Cms\Ui\Nodes\Action`.
- Added `CraftCms\Cms\Ui\Nodes\Field::actions()`.
- Added `CraftCms\Cms\Ui\Controls\Checkbox`.
- Added `CraftCms\Cms\Ui\Controls\FieldSelect`.
- Added `CraftCms\Cms\Ui\Controls\ConditionBuilder::fieldLayouts()`.
- Added `CraftCms\Cms\Cp\Components\Field::actions()`.
- Added `CraftCms\Cms\Cp\FormFields::fieldSelectHtml()`.
- Added the `actions` slot to `<craft-field>`.
- Added the `<craft-checkbox-select>` web component, which replaces checkbox selects and sortable checkbox selects. It has an “All” option, independent `sortable` and `presentation="add-menu"` options, and an `options` property along with `addOption()`, `updateOption()`, and `removeOption()` methods for changing its options at runtime. ([#20036](https://github.com/craftcms/cms/pull/20036))
- Added the `presentation` option to the `forms.checkboxSelect` and `forms.checkboxSelectField` macros and `Craft.ui.createCheckboxSelect()`. Set it to `checkboxes` to make a sortable checkbox select list every option rather than an “Add item” menu. ([#20036](https://github.com/craftcms/cms/pull/20036))
- While a checkbox select's “All” option is checked, its other options are now left checked but unnamed, rather than disabled. ([#20036](https://github.com/craftcms/cms/pull/20036))
- The `storageKey` checkbox select option no longer has any effect. The order of a sortable checkbox select's options is posted with its value. ([#20036](https://github.com/craftcms/cms/pull/20036))
- Deprecated `CraftCms\Cms\Cp\Components\Field::labelExtra()`. `actions()` should be used instead.
- Deprecated the `labelExtra` field config option. `actions` should be used instead.
- Deprecated `<craft-field>`’s `label-extra` slot. The `actions` slot should be used instead.
- Removed `CraftCms\Cms\FieldLayout\FieldLayoutComponent::settingsHtml()`.
- Removed `CraftCms\Cms\FieldLayout\FieldLayoutComponent::renderSettingsHtml()`.

##### Added
- Added `CraftCms\Cms\FieldLayout\FieldLayoutForm`.
- Added `CraftCms\Cms\FieldLayout\FieldLayoutFormTab`.
- Added `CraftCms\Cms\FieldLayout\FieldLayoutFormElement`.
- Added `CraftCms\Cms\FieldLayout\FieldLayoutServiceProvider`.
- Added `CraftCms\Cms\FieldLayout\Concerns\HasFieldLayout` trait.

##### Deprecations
- Deprecated `craft\models\FieldLayout`. `CraftCms\Cms\FieldLayout\FieldLayout` should be used instead.
- Deprecated `craft\models\FieldLayoutTab`. `CraftCms\Cms\FieldLayout\FieldLayoutTab` should be used instead.
- Deprecated `craft\base\FieldLayoutComponent`. `CraftCms\Cms\FieldLayout\FieldLayoutComponent` should be used instead.
- Deprecated `craft\base\FieldLayoutElement`. `CraftCms\Cms\FieldLayout\FieldLayoutElement` should be used instead.
- Deprecated `craft\base\FieldLayoutProviderInterface`. `CraftCms\Cms\FieldLayout\Contracts\FieldLayoutProviderInterface` should be used instead.
- Deprecated `craft\records\FieldLayout`. `CraftCms\Cms\FieldLayout\Models\FieldLayout` should be used instead.
- Deprecated `craft\fieldlayoutelements\BaseField`. `CraftCms\Cms\FieldLayout\LayoutElements\BaseField` should be used instead.
- Deprecated `craft\fieldlayoutelements\BaseNativeField`. `CraftCms\Cms\FieldLayout\LayoutElements\BaseNativeField` should be used instead.
- Deprecated `craft\fieldlayoutelements\BaseUiElement`. `CraftCms\Cms\FieldLayout\LayoutElements\BaseUiElement` should be used instead.
- Deprecated `craft\fieldlayoutelements\CustomField`. `CraftCms\Cms\FieldLayout\LayoutElements\CustomField` should be used instead.
- Deprecated `craft\fieldlayoutelements\Heading`. `CraftCms\Cms\FieldLayout\LayoutElements\Heading` should be used instead.
- Deprecated `craft\fieldlayoutelements\HorizontalRule`. `CraftCms\Cms\FieldLayout\LayoutElements\HorizontalRule` should be used instead.
- Deprecated `craft\fieldlayoutelements\Html`. `CraftCms\Cms\FieldLayout\LayoutElements\Html` should be used instead.
- Deprecated `craft\fieldlayoutelements\LineBreak`. `CraftCms\Cms\FieldLayout\LayoutElements\LineBreak` should be used instead.
- Deprecated `craft\fieldlayoutelements\Markdown`. `CraftCms\Cms\FieldLayout\LayoutElements\Markdown` should be used instead.
- Deprecated `craft\fieldlayoutelements\Template`. `CraftCms\Cms\FieldLayout\LayoutElements\Template` should be used instead.
- Deprecated `craft\fieldlayoutelements\TextField`. `CraftCms\Cms\FieldLayout\LayoutElements\TextField` should be used instead.
- Deprecated `craft\fieldlayoutelements\TextareaField`. `CraftCms\Cms\FieldLayout\LayoutElements\TextareaField` should be used instead.
- Deprecated `craft\fieldlayoutelements\Tip`. `CraftCms\Cms\FieldLayout\LayoutElements\Tip` should be used instead.
- Deprecated `craft\fieldlayoutelements\TitleField`. `CraftCms\Cms\FieldLayout\LayoutElements\TitleField` should be used instead.
- Deprecated `craft\fieldlayoutelements\FullNameField`. `CraftCms\Cms\FieldLayout\LayoutElements\FullNameField` should be used instead.
- Deprecated `craft\fieldlayoutelements\addresses\AddressField`. `CraftCms\Cms\FieldLayout\LayoutElements\addresses\AddressField` should be used instead.
- Deprecated `craft\fieldlayoutelements\addresses\CountryCodeField`. `CraftCms\Cms\FieldLayout\LayoutElements\addresses\CountryCodeField` should be used instead.
- Deprecated `craft\fieldlayoutelements\addresses\LabelField`. `CraftCms\Cms\FieldLayout\LayoutElements\addresses\LabelField` should be used instead.
- Deprecated `craft\fieldlayoutelements\addresses\LatLongField`. `CraftCms\Cms\FieldLayout\LayoutElements\addresses\LatLongField` should be used instead.
- Deprecated `craft\fieldlayoutelements\addresses\OrganizationField`. `CraftCms\Cms\FieldLayout\LayoutElements\addresses\OrganizationField` should be used instead.
- Deprecated `craft\fieldlayoutelements\addresses\OrganizationTaxIdField`. `CraftCms\Cms\FieldLayout\LayoutElements\addresses\OrganizationTaxIdField` should be used instead.
- Deprecated `craft\fieldlayoutelements\assets\AssetTitleField`. `CraftCms\Cms\FieldLayout\LayoutElements\assets\AssetTitleField` should be used instead.
- Deprecated `craft\fieldlayoutelements\assets\AltField`. `CraftCms\Cms\FieldLayout\LayoutElements\assets\AltField` should be used instead.
- Deprecated `craft\fieldlayoutelements\entries\EntryTitleField`. `CraftCms\Cms\FieldLayout\LayoutElements\Entries\EntryTitleField` should be used instead.
- Deprecated `craft\fieldlayoutelements\users\UsernameField`. `CraftCms\Cms\FieldLayout\LayoutElements\Users\UsernameField` should be used instead.
- Deprecated `craft\fieldlayoutelements\users\FullNameField`. `CraftCms\Cms\FieldLayout\LayoutElements\Users\FullNameField` should be used instead.
- Deprecated `craft\fieldlayoutelements\users\EmailField`. `CraftCms\Cms\FieldLayout\LayoutElements\Users\EmailField` should be used instead.
- Deprecated `craft\fieldlayoutelements\users\AffiliatedSiteField`. `CraftCms\Cms\FieldLayout\LayoutElements\Users\AffiliatedSiteField` should be used instead.
- Deprecated `craft\fieldlayoutelements\users\PhotoField`. `CraftCms\Cms\FieldLayout\LayoutElements\Users\PhotoField` should be used instead.
- Deprecated `craft\events\CreateFieldLayoutFormEvent`. `CraftCms\Cms\FieldLayout\Events\FieldLayoutFormCreating` should be used instead.
- Deprecated `craft\events\DefineFieldLayoutCustomFieldsEvent`. `CraftCms\Cms\FieldLayout\Events\FieldLayoutCustomFieldsResolving` should be used instead.
- Deprecated `craft\events\DefineFieldLayoutElementsEvent`. `CraftCms\Cms\FieldLayout\Events\FieldLayoutUIElementsResolving` should be used instead.
- Deprecated `craft\events\DefineFieldLayoutFieldsEvent`. `CraftCms\Cms\FieldLayout\NativeFields` should be used instead.
- Deprecated `craft\events\DefineShowFieldLayoutComponentInFormEvent`. `CraftCms\Cms\FieldLayout\Events\FieldLayoutComponentShowInFormResolving` should be used instead.
- Deprecated `craft\events\DefineFieldActionsEvent`. `CraftCms\Cms\FieldLayout\Events\FieldLayoutComponentActionMenuItemsResolving` should be used instead.

#### Helpers
- Removed `CraftCms\Cms\FieldLayout\FieldLayoutComponent::conditionalSettingsHtml()`.
- Added `CraftCms\Cms\Support\Arr`.
- Added `CraftCms\Cms\Support\DateTimeHelper`.
- Added `CraftCms\Cms\Support\File`.
- Added `CraftCms\Cms\Support\Facades\Path`.
- Added `CraftCms\Cms\Support\Facades\Markdown`.
- Added `CraftCms\Cms\Support\Path`.
- Added `CraftCms\Cms\Support\Str`.
- Added `CraftCms\Cms\Support\URL`.
- Added `CraftCms\Cms\action_url()`, `CraftCms\Cms\cp_url()`, and `CraftCms\Cms\site_url()` helper functions.
- `craft\services\Elements::stopCollectingCacheInfo()` no longer sets the returned duration to the `cacheDuration` config setting if a duration wasn’t explicitly declared. ([#16796](https://github.com/craftcms/cms/pull/16796))
- Deprecated `craft\helpers\ArrayHelper`. `CraftCms\Cms\Support\Arr` should be used instead.
- Deprecated `craft\helpers\ConfigHelper`. `CraftCms\Cms\Support\Config` should be used instead.
- Deprecated `craft\helpers\DateTimeHelper`. `CraftCms\Cms\Support\DateTimeHelper` should be used instead.
- Deprecated `craft\helpers\Diff`. `CraftCms\Cms\Support\Diff` should be used instead.
- Deprecated `craft\helpers\ElementHelper`. `CraftCms\Cms\Element\ElementHelper` should be used for core element helper APIs, `CraftCms\Cms\Element\ElementSources` for source lookup, `CraftCms\Cms\Element\ElementAttributeRenderer` for attribute rendering, `CraftCms\Cms\Element\Drafts` for provisional draft helpers, `CraftCms\Cms\Field\Enums\TranslationMethod` for translation helpers, and `Illuminate\Support\Facades\Context` with `CraftCms\Cms\Element\Drafts::CONTEXT_PREVIEW_USER_ID` for preview-user context.
- Deprecated `craft\helpers\Html`. `CraftCms\Cms\Support\Html` should be used instead.
- Deprecated `craft\helpers\HtmlPurifier`. `CraftCms\Cms\Support\HtmlSanitizer\HtmlSanitizers` should be used for HTML sanitization, and `CraftCms\Cms\Support\Str` should be used for UTF-8 cleanup instead.
- Deprecated `craft\helpers\HtmlPurifier::process()`. `CraftCms\Cms\Support\HtmlSanitizer\HtmlSanitizers::sanitize()` should be used instead.
- Deprecated `craft\helpers\HtmlPurifier::cleanUtf8()`.
- Deprecated `craft\helpers\HtmlPurifier::convertToUtf8()`. `CraftCms\Cms\Support\Str::convertToUtf8()` should be used instead.
- Deprecated `craft\helpers\HtmlPurifier::configure()`. `CraftCms\Cms\Support\HtmlSanitizer\HtmlSanitizers::defaults()` or a custom sanitizer registration should be used instead.
- Deprecated `config/craft/htmlpurifier/*.json` sanitizer config files. Sanitizers should be registered as Symfony-style array config files in `config/craft/sanitizers/`, or on `CraftCms\Cms\Support\HtmlSanitizer\HtmlSanitizers` instead.
- Deprecated `craft\services\Path`. `CraftCms\Cms\Support\Path` should be used instead.
- Deprecated `craft\helpers\SessionHelper`. `Illuminate\Support\Facades\Session` should be used instead.
- Deprecated `craft\helpers\Sequence`. `CraftCms\Cms\Support\Sequence` should be used instead.
- Deprecated `craft\helpers\StringHelper`. `CraftCms\Cms\Support\Str` should be used instead.
- Deprecated `Craft::$app->getConfig()->getGeneral()`. `CraftCms\Cms\Config\GeneralConfig` should be used instead. This can be used through dependency injection or through `app(CraftCms\Cms\Config\GeneralConfig::class)`.
- Deprecated `craft\helpers\App::env()`, `CraftCms\Cms\Support\Env::get()` should be used instead.
- Deprecated `craft\markdown\Markdown`, `craft\markdown\GithubMarkdown`, `craft\markdown\MarkdownExtra`, and `craft\markdown\PreEncodedMarkdown`. `CraftCms\Cms\Support\Facades\Markdown` should be used instead.
- Deprecated `craft\helpers\DateRange`. `CraftCms\Cms\Shared\Enums\DateRangeType` and `CraftCms\Cms\Shared\Enums\DateRangePeriod` should be used instead.
- Deprecated `craft\helpers\Cp`. One of the following classes should be used instead:
  - `CraftCms\Cms\Cp\Alerts`
  - `CraftCms\Cms\Cp\FormFields`
  - `CraftCms\Cms\Cp\Html\ContentHtml`
  - `CraftCms\Cms\Cp\Html\ElementHtml`
  - `CraftCms\Cms\Cp\Html\ElementIndexHtml`
  - `CraftCms\Cms\Cp\Html\MenuHtml`
  - `CraftCms\Cms\Cp\Html\PreviewHtml`
  - `CraftCms\Cms\Cp\Html\StatusHtml`
  - `CraftCms\Cms\Cp\Icons`
  - `CraftCms\Cms\Cp\RequestedSite`
- Deprecated `craft\helpers\Json`. `CraftCms\Cms\Support\Json` should be used instead.
- Deprecated `craft\services\Composer`. `CraftCms\Cms\Support\Composer` should be used instead.
- Deprecated `craft\enums\Color`. `CraftCms\Cms\Support\Enums\Color` should be used instead.
- Deprecated `craft\enums\AttributeStatus`. `CraftCms\Cms\Element\Enums\AttributeStatus` should be used instead.
- Deprecated `craft\enums\CmsEdition`. `CraftCms\Cms\Edition` should be used instead.
- Deprecated `craft\enums\ElementIndexViewMode`. `CraftCms\Cms\Element\Enums\ElementIndexViewMode` should be used instead.
- Deprecated `craft\enums\LicenseKeyStatus`. `CraftCms\Cms\Support\Enums\LicenseKeyStatus` should be used instead.
- Deprecated `craft\enums\MenuItemType`. `CraftCms\Cms\Element\Enums\MenuItemType` should be used instead.
- Deprecated `craft\enums\PropagationMethod`. `CraftCms\Cms\Element\Enums\PropagationMethod` should be used instead.
- Deprecated `craft\enums\TimePeriod`. `CraftCms\Cms\Support\Enums\TimePeriod` should be used instead.
- Deprecated `craft\services\Gc`. `CraftCms\Cms\GarbageCollection\GarbageCollection` should be used instead.
- Deprecated `craft\services\Api`. `CraftCms\Cms\Support\Api` should be used instead.
- Deprecated `craft\helpers\Api`. `CraftCms\Cms\Support\Api` should be used instead.
- Deprecated `craft\nameparsing\CustomLanguage`. `CraftCms\Cms\Shared\Nameparser\CustomLanguage` should be used instead.
- Deprecated `craft\helpers\App`. The following classes/methods should be used instead:
  - #### General helpers
  - `App:devMode()` -> `app()->hasDebugModeEnabled()`
  - `App:parseBooleanEnv()` --> `\CraftCms\Cms\Support\Env::parseBoolean()`
  - `App:normalizeValue()` --> `\CraftCms\Cms\normalizeValue()`
  - `App:maxPowerCaptain()` --> `\CraftCms\Cms\maxPowerCaptain()`
  - `App:silence()` --> `\CraftCms\Cms\silence()`
  - `App:backtrace()` --> `\CraftCms\Cms\backtraceAsString()`
  - #### Env
  - `App:env()` --> `\CraftCms\Cms\Support\Env::get()`
  - `App:parseEnv()` --> `\CraftCms\Cms\Support\Env::parse()`
  - #### PHP
  - `App:phpVersion()` --> `\CraftCms\Cms\Support\PHP::version()`
  - `App:extensionVersion()` --> `\CraftCms\Cms\Support\PHP::extensionVersion()`
  - `App:phpConfigValueAsBool()` --> `\CraftCms\Cms\Support\PHP::configValueAsBool()`
  - `App:phpConfigValueInBytes()` --> `\CraftCms\Cms\Support\PHP::configValueInBytes()`
  - `App:phpSizeToBytes()` --> `\CraftCms\Cms\Support\PHP::sizeToBytes()`
  - `App:phpConfigValueAsPaths()` --> `\CraftCms\Cms\Support\PHP::configValueAsPaths()`
  - `App:normalizePhpPaths()` --> `\CraftCms\Cms\Support\PHP::normalizePaths()`
  - `App:isPathAllowed()` --> `\CraftCms\Cms\Support\PHP::isPathAllowed()`
  - `App:phpExecutable()` --> `\CraftCms\Cms\Support\PHP::executable()`
  - `App:testIniSet()` --> `\CraftCms\Cms\Support\PHP::testIniSet()`
  - `App:checkForValidIconv()` --> `\CraftCms\Cms\Support\PHP::checkForValidIconv()`
  - `App:supportsIdn()` --> `\CraftCms\Cms\Support\PHP::supportsIdn()`
  - #### License
  - `App:licenseKey()` --> `app(\CraftCms\Cms\License\License::class)->key()`
  - `App:licensingIssues()` --> `app(\CraftCms\Cms\License\License::class)->issues()`
  - `App:licenseShunCookieName()` --> `app(\CraftCms\Cms\License\License::class)->shunCookieName()`
  - `App:licensingIssuesHash()` --> `app(\CraftCms\Cms\License\License::class)->issuesHash()`
  -
- Deprecated `Craft::createGuzzleClient()`. `CraftCms\Cms\Support\Facades\Http::create()` should be used instead.
- Deprecated `craft\helpers\FileHelper`. `CraftCms\Cms\Support\File` should be used instead.
- Deprecated `craft\helpers\UrlHelper`. `CraftCms\Cms\Support\URL` should be used instead.

#### Deprecator
- Added `CraftCms\Cms\Support\Facades\Deprecator`.
- Added `CraftCms\Cms\Deprecator\Commands\ClearDeprecations`.
- Removed `craft\console\controllers\ClearDeprecationsController.php`.
- Deprecated `craft\services\Deprecator`. `CraftCms\Cms\Deprecator\Deprecator` should be used instead.
- Deprecated `craft\models\DeprecationError`. `CraftCms\Cms\Deprecator\Models\DeprecationError` should be used instead.
- Deprecated `craft\errors\DeprecationException`. `CraftCms\Cms\Deprecator\Exceptions\DeprecationException` should be used instead.

#### Console commands
- Added `php craft twig:cache` - Precompile Twig views
- Added `php craft twig:clear` - Clear precompiled Twig views
- Added `php craft project-config:check` - Check project config schema compatibility without applying changes
- `craft\console\controllers\EnvController` has been removed in favor of the classes below:
  - `CraftCms\Cms\Console\Commands\Env\EnvRemoveCommand` => `php craft env:remove`
  - `CraftCms\Cms\Console\Commands\Env\EnvSetCommand` => `php craft env:set`
  - `CraftCms\Cms\Console\Commands\Env\EnvShowCommand` => `php craft env:show`
- `craft\console\controllers\IndexAssetsController` has been removed in favor of the classes below:
  - `CraftCms\Cms\Asset\Commands\CleanupAssetIndexesCommand` => `php craft index-assets:cleanup`
  - `CraftCms\Cms\Asset\Commands\IndexAllAssetsCommand` => `php craft index-assets:all`
  - `CraftCms\Cms\Asset\Commands\IndexOneAssetCommand` => `php craft index-assets:one`
- `craft\console\controllers\BaseSystemStatusController`, `craft\console\controllers\OnController`, and `craft\console\controllers\OffController` have been removed in favor of the classes below:
  - `CraftCms\Cms\Console\Commands\System\OnCommand` => `php craft on`
  - `CraftCms\Cms\Console\Commands\System\OffCommand` => `php craft off`
- `craft\console\controllers\ElementsController` has been removed in favor of the classes below:
  - `CraftCms\Cms\Element\Commands\DeleteCommand` => `php craft elements:delete`
  - `CraftCms\Cms\Element\Commands\DeleteAllOfTypeCommand` => `php craft elements:delete-all-of-type`
  - `CraftCms\Cms\Element\Commands\RestoreCommand` => `php craft elements:restore`
- `craft\console\controllers\UpdateStatusesController` has been removed in favor of the class below:
  - `CraftCms\Cms\Entry\Commands\UpdateStatusesCommand` => `php craft update-statuses`
- `craft\console\controllers\utils\FixElementUidsController` has been removed in favor of the class below:
  - `CraftCms\Cms\Console\Commands\Utils\FixElementUidsCommand` => `php craft utils:fix-element-uids`
- `craft\console\controllers\utils\FixFieldLayoutUidsController` has been removed in favor of the class below:
  - `CraftCms\Cms\Console\Commands\Utils\FixFieldLayoutUidsCommand` => `php craft utils:fix-field-layout-uids`
- `craft\console\controllers\utils\PruneOrphanedEntriesController` has been removed in favor of the class below:
  - `CraftCms\Cms\Console\Commands\Utils\PruneOrphanedEntriesCommand` => `php craft utils:prune-orphaned-entries`
- `craft\console\controllers\utils\PruneProvisionalDraftsController` has been removed in favor of the class below:
  - `CraftCms\Cms\Console\Commands\Utils\PruneProvisionalDraftsCommand` => `php craft utils:prune-provisional-drafts`
- `craft\console\controllers\utils\PruneRevisionsController` has been removed in favor of the class below:
  - `CraftCms\Cms\Console\Commands\Utils\PruneRevisionsCommand` => `php craft utils:prune-revisions`
- `craft\console\controllers\utils\RepairController::actionProjectConfig()` has been removed in favor of the class below:
  - `CraftCms\Cms\ProjectConfig\Commands\RepairCommand` => `php craft project-config:repair`

#### Mutex
Craft's Mutex classes have been deprecated. [Laravel's atomic locking](https://laravel.com/docs/12.x/cache#atomic-locks) should be used instead.

- Deprecated `craft\mutex\Mutex`
- Deprecated `craft\mutex\MutexTrait`
- Deprecated `Craft::$app->getMutex()`

#### Components
- Deprecated `craft\base\ComponentInterface`. `CraftCms\Cms\Component\Contracts\ComponentInterface` should be used instead.
- Deprecated `craft\base\ConfigurableComponentInterface`. `CraftCms\Cms\Component\Contracts\ConfigurableComponentInterface` should be used instead.
- Deprecated `craft\base\SavableComponentInterface`. `CraftCms\Cms\Component\Contracts\SavableComponentInterface` should be used instead.
- Added `CraftCms\Cms\Component\Component` base class, replacing Yii2's `BaseObject`/`Component` with config hydration, magic getters/setters, and `Arrayable` support.
- Added `CraftCms\Cms\Component\Exceptions\InvalidCallException`, replacing `yii\base\InvalidCallException`.
- Added `CraftCms\Cms\Component\Exceptions\UnknownPropertyException`, replacing `yii\base\UnknownPropertyException`.

#### Dashboard & Widgets

##### Controllers
- Removed `craft\controllers\DashboardController`. The following controllers now implement this functionality:
  - `CraftCms\Cms\Http\Controllers\Dashboard\DashboardController`
  - `CraftCms\Cms\Http\Controllers\Dashboard\WidgetsController`
  - `CraftCms\Cms\Http\Controllers\Dashboard\Widgets\CraftSupportController`
  - `CraftCms\Cms\Http\Controllers\Dashboard\Widgets\FeedController`

##### Deprecations
- Deprecated `Craft::$app->getDashboard()`. `app(\CraftCms\Cms\Dashboard\Dashboard::class)` should be used instead.
- Deprecated `craft\services\Dashboard`. `CraftCms\Cms\Dashboard\Dashboard` should be used instead.
- Deprecated `craft\base\Widget`. `CraftCms\Cms\Dashboard\Widgets\Widget` should be used instead.
- Deprecated `craft\base\WidgetInterface`. `CraftCms\Cms\Dashboard\Contracts\WidgetInterface` should be used instead.
- Deprecated `craft\base\WidgetTrait`.
- Deprecated `craft\widgets\CraftSupport`. `CraftCms\Cms\Dashboard\Widgets\CraftSupport` should be used instead.
- Deprecated `craft\widgets\Feed`. `CraftCms\Cms\Dashboard\Widgets\Feed` should be used instead.
- Deprecated `craft\widgets\MissingWidget`. `CraftCms\Cms\Dashboard\Widgets\MissingWidget` should be used instead.
- Deprecated `craft\widgets\MyDrafts`. `CraftCms\Cms\Dashboard\Widgets\MyDrafts` should be used instead.
- Deprecated `craft\widgets\NewUsers`. `CraftCms\Cms\Dashboard\Widgets\NewUsers` should be used instead.
- Deprecated `craft\widgets\QuickPost`. `CraftCms\Cms\Dashboard\Widgets\QuickPost` should be used instead.
- Deprecated `craft\widgets\RecentEntries`. `CraftCms\Cms\Dashboard\Widgets\RecentEntries` should be used instead.
- Deprecated `craft\widgets\Updates`. `CraftCms\Cms\Dashboard\Widgets\Updates` should be used instead.
- Deprecated `craft\records\Widget`. `CraftCms\Cms\Dashboard\Models\Widget` should be used instead.

##### Events
- Deprecated `craft\services\Dashboard::EVENT_REGISTER_WIDGET_TYPES`. `CraftCms\Cms\Dashboard\WidgetTypes::register()` should be used instead.
- Deprecated `craft\events\WidgetEvent` in favor of the following new events:
  - `craft\services\Dashboard::EVENT_BEFORE_SAVE_WIDGET` => `CraftCms\Cms\Dashboard\Events\WidgetSaving`
  - `craft\services\Dashboard::EVENT_AFTER_SAVE_WIDGET` => `CraftCms\Cms\Dashboard\Events\WidgetSaved`
  - `craft\services\Dashboard::EVENT_BEFORE_DELETE_WIDGET` => `CraftCms\Cms\Dashboard\Events\WidgetDeleting`
  - `craft\services\Dashboard::EVENT_AFTER_DELETE_WIDGET` => `CraftCms\Cms\Dashboard\Events\WidgetDeleted`

#### Addresses
- Added `CraftCms\Cms\Support\Facades\Addresses`.

#### AI
- Added `CraftCms\Cms\Ai\Task` and `CraftCms\Cms\Ai\AiTasks`, for registering AI tasks. ([#20006](https://github.com/craftcms/cms/pull/20006))
- Added `CraftCms\Cms\Support\Facades\Ai`, which plugins use to run `laravel/ai` agents and requests with the provider and model administrators choose for each task. ([#20006](https://github.com/craftcms/cms/pull/20006))
- Added `CraftCms\Cms\Ai\Events\TaskRunning`, `TaskCompleted`, and `TaskFailed`, which plugins can use to track AI usage and failures, and cancel requests. ([#20006](https://github.com/craftcms/cms/pull/20006))

#### Assets
- Added `CraftCms\Cms\Asset\AssetsHelper`.
- Added `CraftCms\Cms\Support\Facades\Assets`.
- Added `CraftCms\Cms\Support\Facades\AssetIndexer` facade.
- Added `CraftCms\Cms\Support\Facades\Folders`.
- Added `CraftCms\Cms\Asset\Enums\ImageOrientation` and `CraftCms\Cms\Asset\Conditions\OrientationConditionRule`. ([#20114](https://github.com/craftcms/cms/pull/20114))
- Deprecated `craft\helpers\Assets`. `CraftCms\Cms\Asset\AssetsHelper` should be used instead.
- Deprecated `craft\services\Assets`. `CraftCms\Cms\Asset\Assets` and `CraftCms\Cms\Asset\Folders` should be used instead.
- Deprecated `\craft\records\Asset`. `\CraftCms\Cms\Asset\Models\Asset` should be used instead.
- Deprecated `\craft\records\AssetIndexData`. `\CraftCms\Cms\Asset\Models\AssetIndexData` should be used instead.
- Deprecated `\craft\records\AssetIndexingSession`. `\CraftCms\Cms\Asset\Models\AssetIndexingSession` should be used instead.
- Deprecated `\craft\records\Volume`. `\CraftCms\Cms\Asset\Models\Volume` should be used instead.
- Deprecated `\craft\records\VolumeFolder`. `\CraftCms\Cms\Asset\Models\VolumeFolder` should be used instead.
- Deprecated `\craft\controllers\AssetIndexesController`. `\CraftCms\Cms\Http\Controllers\Utilities\AssetIndexesController` should be used instead.
- Deprecated `craft\services\AssetIndexer`. `CraftCms\Cms\Asset\AssetIndexer` should be used instead.
- Deprecated `craft\models\AssetIndexData`. `CraftCms\Cms\Asset\Models\AssetIndexData` should be used instead.
- Deprecated `craft\models\AssetIndexingSession`. `CraftCms\Cms\Asset\Models\AssetIndexingSession` should be used instead.
- Deprecated `craft\errors\AssetException`. `CraftCms\Cms\Asset\Exceptions\AssetException` should be used instead.
- Deprecated `craft\errors\AssetDisallowedExtensionException`. `CraftCms\Cms\Asset\Exceptions\AssetDisallowedExtensionException` should be used instead.
- Deprecated `craft\errors\AssetNotIndexableException`. `CraftCms\Cms\Asset\Exceptions\AssetNotIndexableException` should be used instead.
- Deprecated `craft\errors\FileException`. `CraftCms\Cms\Asset\Exceptions\FileException` should be used instead.
- Deprecated `craft\errors\ImageException`. `CraftCms\Cms\Asset\Exceptions\ImageException` should be used instead.
- Deprecated `craft\errors\ImageTransformException`. `CraftCms\Cms\Asset\Exceptions\ImageTransformException` should be used instead.
- Deprecated `craft\errors\MissingAssetException`. `CraftCms\Cms\Asset\Exceptions\MissingAssetException` should be used instead.
- Deprecated `craft\errors\MissingVolumeFolderException`. `CraftCms\Cms\Asset\Exceptions\MissingVolumeFolderException` should be used instead.
- Deprecated `craft\errors\VolumeException`. `CraftCms\Cms\Asset\Exceptions\VolumeException` should be used instead.

##### Events
- Added `CraftCms\Cms\Asset\AssetFileKinds`. ([#19270](https://github.com/craftcms/cms/pull/19270))
- Added `CraftCms\Cms\Asset\Events\SetAssetFilename`.
- Deprecated `craft\events\SetAssetFilenameEvent`. `CraftCms\Cms\Asset\Events\SetAssetFilename` should be used instead.
- Deprecated `craft\events\RegisterAssetFileKindsEvent`. `CraftCms\Cms\Asset\AssetFileKinds::register()` should be used instead. ([#19270](https://github.com/craftcms/cms/pull/19270))
- Deprecated `craft\events\ReplaceAssetEvent` in favor of the following new events:
  - `craft\services\Assets::EVENT_BEFORE_REPLACE_ASSET` => `CraftCms\Cms\Asset\Events\AssetReplacing`
  - `craft\services\Assets::EVENT_AFTER_REPLACE_ASSET` => `CraftCms\Cms\Asset\Events\AssetReplaced`
- Deprecated `craft\events\DefineAssetThumbUrlEvent`. `CraftCms\Cms\Asset\Events\ThumbUrlResolving` should be used instead.
- Deprecated `craft\events\AssetPreviewEvent`. `CraftCms\Cms\Asset\Events\PreviewHandlerResolving` should be used instead.

#### Auth
- Refactored the authentication system to use Laravel's authentication system.
- Added `CraftCms\Cms\Auth\Events\SettingPassword`.
- Added `CraftCms\Cms\User\Notifications\ResetPasswordNotification`.
- Deprecated `craft\services\Auth`. `CraftCms\Cms\Auth\Auth` should be used instead.
- Deprecated `craft\web\User`. `auth()->user()` or `CraftCms\Cms\User\Elements\User` methods should be used instead.
- Deprecated `craft\events\AuthenticateUserEvent`. `CraftCms\Cms\Auth\Events\UserAuthenticating` should be used instead.
- Deprecated `\craft\records\Authenticator`. `\CraftCms\Cms\Auth\Models\Authenticator` should be used instead.
- Deprecated `\craft\records\RecoveryCodes`. `\CraftCms\Cms\Auth\Models\RecoveryCodes` should be used instead.
- Deprecated `\craft\records\SsoIdentity`. `\CraftCms\Cms\Auth\Models\SsoIdentity` should be used instead.
- Deprecated `\craft\records\WebAuthn`. `\CraftCms\Cms\Auth\Models\WebAuthn` should be used instead.
- Deprecated `craft\behaviors\SessionBehavior::authorize`. `CraftCms\Cms\Auth\SessionAuth::authorize` should be used instead.
- Deprecated `craft\behaviors\SessionBehavior::deauthorize`. `CraftCms\Cms\Auth\SessionAuth::deauthorize` should be used instead.
- Deprecated `craft\behaviors\SessionBehavior::checkAuthorization`. `CraftCms\Cms\Auth\SessionAuth::checkAuthorization` should be used instead.
- Deprecated `craft\services\Users::isVerificationCodeValidForUser()`. `Password::broker()->tokenExists($user, $code)` should be used instead.
- Removed `craft\controllers\AuthController`. The following controllers now implement this functionality:
  - `CraftCms\Cms\Http\Controllers\Users\AuthMethodController`
  - `CraftCms\Cms\Http\Controllers\Users\PasskeysController`
  - `CraftCms\Cms\Http\Controllers\Users\RecoveryCodesController`
- Removed `verificationCode` and `verificationCodeIssuedDate` columns on the `users` table in favor of the `password_reset_tokens` table.

##### Authorization
Craft 6 now uses [Laravel's authorization system](https://laravel.com/docs/12.x/authorization) for element authorization checks.

###### Added
- Added `CraftCms\Cms\Auth\Events\ElementAuthorizing` event for customizing element authorization.
- Added `CraftCms\Cms\Element\Policies\ElementPolicy` base policy for element authorization.
- Added element-specific authorization policies:
  - `CraftCms\Cms\Address\Policies\AddressPolicy`
  - `CraftCms\Cms\Asset\Policies\AssetPolicy`
  - `CraftCms\Cms\Entry\Policies\EntryPolicy`
  - `CraftCms\Cms\User\Policies\UserPolicy`
  - `CraftCms\Cms\Field\Policies\ContentBlockPolicy`

##### Passkeys
- Added `CraftCms\Cms\Auth\Passkeys\Passkeys`.
- Deprecated `craft\services\Auth` passkey methods. The following should be used instead:
  - `Auth::hasPasskeys()` -> `app(Passkeys::class)->hasPasskeys()`
  - `Auth::getPasskeys()` -> `app(Passkeys::class)->getPasskeys()`
  - `Auth::getPasskeyCreationOptions()` -> `app(Passkeys::class)->getPasskeyCreationOptions()`
  - `Auth::verifyPasskeyCreationResponse()` -> `app(Passkeys::class)->verifyPasskeyCreationResponse()`
  - `Auth::getPasskeyRequestOptions()` -> `app(Passkeys::class)->getPasskeyRequestOptions()`
  - `Auth::verifyPasskey()` -> `app(Passkeys::class)->verifyPasskey()`
  - `Auth::deletePasskey()` -> `app(Passkeys::class)->deletePasskey()`
- Deprecated `craft\auth\passkeys\CredentialRepository`. `CraftCms\Cms\Auth\Passkeys\CredentialRepository` should be used instead.
- Deprecated `craft\auth\passkeys\WebauthnServer`. `CraftCms\Cms\Auth\Passkeys\WebauthnServer` should be used instead.

#### Conditions

##### Added
- Added `CraftCms\Cms\Support\Facades\Conditions`.

##### Controllers
- Removed `craft\controllers\ConditionsController`. `CraftCms\Cms\Http\Controllers\ConditionsController` should be used instead.

###### Service
- Deprecated `craft\services\Conditions`. `CraftCms\Cms\Condition\Conditions` should be used instead.

###### Base Conditions
- Deprecated `craft\base\conditions\ConditionInterface`. `CraftCms\Cms\Condition\Contracts\ConditionInterface` should be used instead.
- Deprecated `craft\base\conditions\ConditionRuleInterface`. `CraftCms\Cms\Condition\Contracts\ConditionRuleInterface` should be used instead.
- Deprecated `craft\base\conditions\BaseCondition`. `CraftCms\Cms\Condition\BaseCondition` should be used instead.
- Deprecated `craft\base\conditions\BaseConditionRule`. `CraftCms\Cms\Condition\BaseConditionRule` should be used instead.
- Deprecated `craft\base\conditions\BaseTextConditionRule`. `CraftCms\Cms\Condition\BaseTextConditionRule` should be used instead.
- Deprecated `craft\base\conditions\BaseNumberConditionRule`. `CraftCms\Cms\Condition\BaseNumberConditionRule` should be used instead.
- Deprecated `craft\base\conditions\BaseSelectConditionRule`. `CraftCms\Cms\Condition\BaseSelectConditionRule` should be used instead.
- Deprecated `craft\base\conditions\BaseMultiSelectConditionRule`. `CraftCms\Cms\Condition\BaseMultiSelectConditionRule` should be used instead.
- Deprecated `craft\base\conditions\BaseLightswitchConditionRule`. `CraftCms\Cms\Condition\BaseLightswitchConditionRule` should be used instead.
- Deprecated `craft\base\conditions\BaseDateRangeConditionRule`. `CraftCms\Cms\Condition\BaseDateRangeConditionRule` should be used instead.
- Deprecated `craft\base\conditions\BaseElementSelectConditionRule`. `CraftCms\Cms\Condition\BaseElementSelectConditionRule` should be used instead.

###### Elements
- Added `CraftCms\Cms\Element\Eloquent\EloquentElement`, a base class for element types that represent an app’s Eloquent models, and `CraftCms\Cms\Element\Eloquent\EloquentElements::register()`. ([#19971](https://github.com/craftcms/cms/pull/19971))
- Added `CraftCms\Cms\Element\Eloquent\EloquentElementsField`, a base class for field types that relate elements to an Eloquent element type’s elements. ([#19971](https://github.com/craftcms/cms/pull/19971))
- Added the `craft:eloquent-elements:sync` command, which creates elements for existing rows of registered Eloquent element types’ models. ([#19971](https://github.com/craftcms/cms/pull/19971))
- Deprecated `craft\elements\conditions\ElementCondition`. `CraftCms\Cms\Element\Conditions\ElementCondition` should be used instead.
- Deprecated `craft\elements\conditions\ElementConditionInterface`. `CraftCms\Cms\Element\Conditions\Contracts\ElementConditionInterface` should be used instead.
- Deprecated `craft\elements\conditions\ElementConditionRuleInterface`. `CraftCms\Cms\Element\Conditions\Contracts\ElementConditionRuleInterface` should be used instead.
- Deprecated `craft\elements\conditions\HintableConditionRuleTrait`. `CraftCms\Cms\Element\Conditions\HintableConditionRuleTrait` should be used instead.
- Deprecated `craft\elements\conditions\TitleConditionRule`. `CraftCms\Cms\Element\Conditions\TitleConditionRule` should be used instead.
- Deprecated `craft\elements\conditions\SlugConditionRule`. `CraftCms\Cms\Element\Conditions\SlugConditionRule` should be used instead.
- Deprecated `craft\elements\conditions\UriConditionRule`. `CraftCms\Cms\Element\Conditions\UriConditionRule` should be used instead.
- Deprecated `craft\elements\conditions\IdConditionRule`. `CraftCms\Cms\Element\Conditions\IdConditionRule` should be used instead.
- Deprecated `craft\elements\conditions\StatusConditionRule`. `CraftCms\Cms\Element\Conditions\StatusConditionRule` should be used instead.
- Deprecated `craft\elements\conditions\HasUrlConditionRule`. `CraftCms\Cms\Element\Conditions\HasUrlConditionRule` should be used instead.
- Deprecated `craft\elements\conditions\HasDescendantsRule`. `CraftCms\Cms\Element\Conditions\HasDescendantsRule` should be used instead.
- Deprecated `craft\elements\conditions\LevelConditionRule`. `CraftCms\Cms\Element\Conditions\LevelConditionRule` should be used instead.
- Deprecated `craft\elements\conditions\DateCreatedConditionRule`. `CraftCms\Cms\Element\Conditions\DateCreatedConditionRule` should be used instead.
- Deprecated `craft\elements\conditions\DateUpdatedConditionRule`. `CraftCms\Cms\Element\Conditions\DateUpdatedConditionRule` should be used instead.
- Deprecated `craft\elements\conditions\SiteConditionRule`. `CraftCms\Cms\Element\Conditions\SiteConditionRule` should be used instead.
- Deprecated `craft\elements\conditions\SiteGroupConditionRule`. `CraftCms\Cms\Element\Conditions\SiteGroupConditionRule` should be used instead.
- Deprecated `craft\elements\conditions\LanguageConditionRule`. `CraftCms\Cms\Element\Conditions\LanguageConditionRule` should be used instead.
- Deprecated `craft\elements\conditions\RelatedToConditionRule`. `CraftCms\Cms\Element\Conditions\RelatedToConditionRule` should be used instead.
- Deprecated `craft\elements\conditions\NotRelatedToConditionRule`. `CraftCms\Cms\Element\Conditions\NotRelatedToConditionRule` should be used instead.

###### Entries
- Deprecated `craft\elements\conditions\entries\EntryCondition`. `CraftCms\Cms\Entry\Conditions\EntryCondition` should be used instead.
- Deprecated `craft\elements\conditions\entries\PostDateConditionRule`. `CraftCms\Cms\Entry\Conditions\PostDateConditionRule` should be used instead.
- Deprecated `craft\elements\conditions\entries\ExpiryDateConditionRule`. `CraftCms\Cms\Entry\Conditions\ExpiryDateConditionRule` should be used instead.
- Deprecated `craft\elements\conditions\entries\SectionConditionRule`. `CraftCms\Cms\Entry\Conditions\SectionConditionRule` should be used instead.
- Deprecated `craft\elements\conditions\entries\TypeConditionRule`. `CraftCms\Cms\Entry\Conditions\TypeConditionRule` should be used instead.
- Deprecated `craft\elements\conditions\entries\AuthorConditionRule`. `CraftCms\Cms\Entry\Conditions\AuthorConditionRule` should be used instead.
- Deprecated `craft\elements\conditions\entries\AuthorGroupConditionRule`. `CraftCms\Cms\Entry\Conditions\AuthorGroupConditionRule` should be used instead.
- Deprecated `craft\elements\conditions\entries\ViewableConditionRule`. `CraftCms\Cms\Entry\Conditions\ViewableConditionRule` should be used instead.
- Deprecated `craft\elements\conditions\entries\SavableConditionRule`. `CraftCms\Cms\Entry\Conditions\SavableConditionRule` should be used instead.
- Deprecated `craft\elements\conditions\entries\FieldConditionRule`. `CraftCms\Cms\Entry\Conditions\FieldConditionRule` should be used instead.

###### Users
- Deprecated `craft\elements\conditions\users\UserCondition`. `CraftCms\Cms\User\Conditions\UserCondition` should be used instead.
- Deprecated `craft\elements\conditions\users\UsernameConditionRule`. `CraftCms\Cms\User\Conditions\UsernameConditionRule` should be used instead.
- Deprecated `craft\elements\conditions\users\EmailConditionRule`. `CraftCms\Cms\User\Conditions\EmailConditionRule` should be used instead.
- Deprecated `craft\elements\conditions\users\FirstNameConditionRule`. `CraftCms\Cms\User\Conditions\FirstNameConditionRule` should be used instead.
- Deprecated `craft\elements\conditions\users\LastNameConditionRule`. `CraftCms\Cms\User\Conditions\LastNameConditionRule` should be used instead.
- Deprecated `craft\elements\conditions\users\GroupConditionRule`. `CraftCms\Cms\User\Conditions\GroupConditionRule` should be used instead.
- Deprecated `craft\elements\conditions\users\AdminConditionRule`. `CraftCms\Cms\User\Conditions\AdminConditionRule` should be used instead.
- Deprecated `craft\elements\conditions\users\CredentialedConditionRule`. `CraftCms\Cms\User\Conditions\CredentialedConditionRule` should be used instead.
- Deprecated `craft\elements\conditions\users\LastLoginDateConditionRule`. `CraftCms\Cms\User\Conditions\LastLoginDateConditionRule` should be used instead.
- Deprecated `craft\elements\conditions\users\AffiliatedSiteConditionRule`. `CraftCms\Cms\User\Conditions\AffiliatedSiteConditionRule` should be used instead.

###### Assets
- Deprecated `craft\elements\conditions\assets\AssetCondition`. `CraftCms\Cms\Asset\Conditions\AssetCondition` should be used instead.
- Deprecated `craft\elements\conditions\assets\VolumeConditionRule`. `CraftCms\Cms\Asset\Conditions\VolumeConditionRule` should be used instead.
- Deprecated `craft\elements\conditions\assets\FilenameConditionRule`. `CraftCms\Cms\Asset\Conditions\FilenameConditionRule` should be used instead.
- Deprecated `craft\elements\conditions\assets\FileTypeConditionRule`. `CraftCms\Cms\Asset\Conditions\FileTypeConditionRule` should be used instead.
- Deprecated `craft\elements\conditions\assets\FileSizeConditionRule`. `CraftCms\Cms\Asset\Conditions\FileSizeConditionRule` should be used instead.
- Deprecated `craft\elements\conditions\assets\HeightConditionRule`. `CraftCms\Cms\Asset\Conditions\HeightConditionRule` should be used instead.
- Deprecated `craft\elements\conditions\assets\WidthConditionRule`. `CraftCms\Cms\Asset\Conditions\WidthConditionRule` should be used instead.
- Deprecated `craft\elements\conditions\assets\DateModifiedConditionRule`. `CraftCms\Cms\Asset\Conditions\DateModifiedConditionRule` should be used instead.
- Deprecated `craft\elements\conditions\assets\HasAltConditionRule`. `CraftCms\Cms\Asset\Conditions\HasAltConditionRule` should be used instead.
- Deprecated `craft\elements\conditions\assets\UploaderConditionRule`. `CraftCms\Cms\Asset\Conditions\UploaderConditionRule` should be used instead.
- Deprecated `craft\elements\conditions\assets\SavableConditionRule`. `CraftCms\Cms\Asset\Conditions\SavableConditionRule` should be used instead.
- Deprecated `craft\elements\conditions\assets\ViewableConditionRule`. `CraftCms\Cms\Asset\Conditions\ViewableConditionRule` should be used instead.

###### Addresses
- Deprecated `craft\elements\conditions\addresses\AddressCondition`. `CraftCms\Cms\Address\Conditions\AddressCondition` should be used instead.
- Deprecated `craft\elements\conditions\addresses\FullNameConditionRule`. `CraftCms\Cms\Address\Conditions\FullNameConditionRule` should be used instead.
- Deprecated `craft\elements\conditions\addresses\OrganizationConditionRule`. `CraftCms\Cms\Address\Conditions\OrganizationConditionRule` should be used instead.
- Deprecated `craft\elements\conditions\addresses\OrganizationTaxIdConditionRule`. `CraftCms\Cms\Address\Conditions\OrganizationTaxIdConditionRule` should be used instead.
- Deprecated `craft\elements\conditions\addresses\LocalityConditionRule`. `CraftCms\Cms\Address\Conditions\LocalityConditionRule` should be used instead.
- Deprecated `craft\elements\conditions\addresses\DependentLocalityConditionRule`. `CraftCms\Cms\Address\Conditions\DependentLocalityConditionRule` should be used instead.
- Deprecated `craft\elements\conditions\addresses\PostalCodeConditionRule`. `CraftCms\Cms\Address\Conditions\PostalCodeConditionRule` should be used instead.
- Deprecated `craft\elements\conditions\addresses\SortingCodeConditionRule`. `CraftCms\Cms\Address\Conditions\SortingCodeConditionRule` should be used instead.
- Deprecated `craft\elements\conditions\addresses\CountryConditionRule`. `CraftCms\Cms\Address\Conditions\CountryConditionRule` should be used instead.
- Deprecated `craft\elements\conditions\addresses\AdministrativeAreaConditionRule`. `CraftCms\Cms\Address\Conditions\AdministrativeAreaConditionRule` should be used instead.
- Deprecated `craft\elements\conditions\addresses\AddressLine1ConditionRule`. `CraftCms\Cms\Address\Conditions\AddressLine1ConditionRule` should be used instead.
- Deprecated `craft\elements\conditions\addresses\AddressLine2ConditionRule`. `CraftCms\Cms\Address\Conditions\AddressLine2ConditionRule` should be used instead.
- Deprecated `craft\elements\conditions\addresses\AddressLine3ConditionRule`. `CraftCms\Cms\Address\Conditions\AddressLine3ConditionRule` should be used instead.
- Deprecated `craft\elements\conditions\addresses\FieldConditionRule`. `CraftCms\Cms\Address\Conditions\FieldConditionRule` should be used instead.

###### Fields
- Deprecated `craft\fields\conditions\FieldConditionRuleInterface`. `CraftCms\Cms\Field\Conditions\Contracts\FieldConditionRuleInterface` should be used instead.
- Deprecated `craft\fields\conditions\FieldConditionRuleTrait`. `CraftCms\Cms\Field\Conditions\FieldConditionRuleTrait` should be used instead.
- Deprecated `craft\fields\conditions\GeneratedFieldConditionRule`. `CraftCms\Cms\Field\Conditions\GeneratedFieldConditionRule` should be used instead.
- Deprecated `craft\fields\conditions\TextFieldConditionRule`. `CraftCms\Cms\Field\Conditions\TextFieldConditionRule` should be used instead.
- Deprecated `craft\fields\conditions\NumberFieldConditionRule`. `CraftCms\Cms\Field\Conditions\NumberFieldConditionRule` should be used instead.
- Deprecated `craft\fields\conditions\MoneyFieldConditionRule`. `CraftCms\Cms\Field\Conditions\MoneyFieldConditionRule` should be used instead.
- Deprecated `craft\fields\conditions\OptionsFieldConditionRule`. `CraftCms\Cms\Field\Conditions\OptionsFieldConditionRule` should be used instead.
- Deprecated `craft\fields\conditions\RelationalFieldConditionRule`. `CraftCms\Cms\Field\Conditions\RelationalFieldConditionRule` should be used instead.
- Deprecated `craft\fields\conditions\LightswitchFieldConditionRule`. `CraftCms\Cms\Field\Conditions\LightswitchFieldConditionRule` should be used instead.
- Deprecated `craft\fields\conditions\EmptyFieldConditionRule`. `CraftCms\Cms\Field\Conditions\EmptyFieldConditionRule` should be used instead.
- Deprecated `craft\fields\conditions\DateFieldConditionRule`. `CraftCms\Cms\Field\Conditions\DateFieldConditionRule` should be used instead.
- Deprecated `craft\fields\conditions\CountryFieldConditionRule`. `CraftCms\Cms\Field\Conditions\CountryFieldConditionRule` should be used instead.
- Deprecated `craft\fields\conditions\LinkFieldConditionRule`. `CraftCms\Cms\Field\Conditions\LinkFieldConditionRule` should be used instead.

###### Events
- Deprecated `craft\events\RegisterConditionRulesEvent`. `CraftCms\Cms\Condition\Events\ConditionRulesResolving` should be used instead.

#### Control Panel
- Added `CraftCms\Cms\Cp\CommandPalette\CommandPaletteItem`, `PageRegistry`, `ActionRegistry`, and `SearchProviderRegistry`, which plugins can use to add pages, actions, and search providers to the command palette, and `CraftCms\Cms\Cp\CommandPalette\Contracts\SearchProvider` and `CraftCms\Cms\Cp\CommandPalette\SearchProviders\ElementSearchProvider`, for searching custom content or element types. ([#20081](https://github.com/craftcms/cms/pull/20081))
- Added `Craft.commandPalette`, with `open()`, `close()`, `registerActions()`, `registerProvider()`, and `registerView()` methods, for opening the command palette and adding page actions, client-side search providers, and views to it. A page’s own action menus are listed as command palette actions when they’re within an element with a `data-command-palette-actions` attribute. ([#20081](https://github.com/craftcms/cms/pull/20081))
- Added a renderer-neutral control panel UI system with shared PHP and Vue rendering, extensible Nodes and Controls, nested and refreshable scopes, changed-only submission, and integration with configurable component settings, fields, and field layouts. ([#19384](https://github.com/craftcms/cms/pull/19384))
- Removed the `assets/upload` and `assets/replace-file` multipart endpoints and legacy uploader events and overrides, in favor of upload sessions. ([#19604](https://github.com/craftcms/cms/pull/19604))
- Removed HTMX from the control panel. Plugins that rely on it should bundle it themselves. ([#19836](https://github.com/craftcms/cms/pull/19836))
- Control panel screens from plugins that haven’t been updated for Craft 6 now render inside the control panel shell, with their tabs, assets, and JavaScript intact, and their element index screens use the new element index, with the element type’s sources in the secondary nav.
- Element edit page additional buttons from `getAdditionalButtons()`, `craft\base\Element::EVENT_DEFINE_ADDITIONAL_BUTTONS`, and `additionalButtonsHtml()` are now shown in the element editor’s footer when the Yii adapter is installed. ([#19910](https://github.com/craftcms/cms/issues/19910))
- The control panel no longer loads Fabric.js. `craft\web\assets\fabric\FabricAsset` is now deprecated and doesn’t register Fabric.js, so plugins that rely on it should bundle it themselves. ([#20085](https://github.com/craftcms/cms/pull/20085))
- `craft\web\assets\prismjs\PrismJsAsset` no longer registers Prism. Plugins that rely on Prism should bundle it themselves, or use `Craft.highlighter` instead. ([#20088](https://github.com/craftcms/cms/pull/20088))
- `craft\web\assets\pluginstore\PluginStoreAsset` is now deprecated and does nothing.
- Removed the `plugin-store/craft-data` and `plugin-store/save-plugin-license-keys` actions.
- Deprecated `Craft.AssetImageEditor`. `Craft.openAssetImageEditor()` should be used instead. Constructing it now opens the image editor dialog, and only its `onSave` setting is supported. ([#20085](https://github.com/craftcms/cms/pull/20085))
- `Craft.createElementEditor()` now opens the element editor in a Vue slideout on control panel pages that have them, rather than in the legacy `Craft.ElementEditorSlideout`. ([#20085](https://github.com/craftcms/cms/pull/20085))
- `Craft.PreviewFileModal` and `Craft.CpModal` are now built on `<craft-dialog>` rather than `Garnish.Modal`. ([#20085](https://github.com/craftcms/cms/pull/20085))
- Removed `Craft.AuthManager`, `Craft.cp.authManager`, and `Craft.UserPhotoInput`. `craft\web\assets\userphoto\UserPhotoAsset` is now deprecated and does nothing. ([#20085](https://github.com/craftcms/cms/pull/20085))
- Removed the `users/login-modal` action and the `_special/login-modal` template, and the `assets/image-editor` action now returns the image editor dialog’s settings, rather than the editor’s HTML. ([#20085](https://github.com/craftcms/cms/pull/20085))
- The `elements/recent-activity` and `queue/get-job-info` actions no longer extend the session, and the `dontExtendSession` parameter is no longer used. ([#20086](https://github.com/craftcms/cms/pull/20086))
- The control panel no longer loads the `XRegExp` library by default. Plugins that require it can register `craft\web\assets\xregexp\XregexpAsset`. ([#19621](https://github.com/craftcms/cms/pull/19621))
- The global `axios` is now only loaded in the control panel when `craftcms/yii2-adapter` is installed. `Craft.sendActionRequest()` or `actionClient` from `@craftcms/ui` should be used instead.
- `actionClient` and `apiClient` from `@craftcms/ui` are no longer axios instances. They're now built on a fetch-based HTTP client with the same request methods.
- `Craft.sendActionRequest()` and `Craft.sendApiRequest()` no longer use axios. Their errors still pass `axios.isAxiosError()` and `axios.isCancel()` checks.
- Added `createHttpClient()`, `http`, `isHttpError()`, and `isCancel()` to `@craftcms/ui`.
- Added `Craft.isCancel()`.
- Added the `<craft-link>` web component, which shows a link on one line with a details button, and the `--c-link-max-width` CSS custom property, which caps its width. ([#20123](https://github.com/craftcms/cms/pull/20123))
- Added the `subtle` attribute to `<craft-button-group>`, which sets its buttons on a quiet track with the pressed one raised out of it.
- Added the `.cp-prose` and `.cp-group-heading` CSS classes, for long-form text and group headings in control panel screens.
- Added the `page-header` layout slot, a row atop the screen’s panel that spans the secondary nav and the content.
- Deprecated the `cancelToken` request option for `Craft.sendActionRequest()` and `Craft.sendApiRequest()`. `signal` should be used instead.
- Removed `Cp.$axios`.
- Removed the `Craft.Accordion` and `Craft.EnvVarGenerator` control panel JavaScript classes. ([#19323](https://github.com/craftcms/cms/pull/19323))
- Deprecated the `Craft.LightSwitch`, `Craft.InfoIcon`, `Craft.ColorInput`, `Craft.PasswordInput`, `Craft.IconPicker`, `Craft.SlidePicker`, `Craft.SlideRuleInput`, and `Craft.Tooltip` control panel JavaScript classes, along with the `.infoicon` jQuery plugin. The corresponding `@craftcms/ui` web components should be used instead. 
- Deprecated the `Craft.SortableCheckboxSelect` control panel JavaScript class. `<craft-checkbox-select>` should be used instead. ([#20036](https://github.com/craftcms/cms/pull/20036))

#### Drafts
- Deprecated `craft\services\Drafts`. `CraftCms\Cms\Element\Drafts` should be used instead.
- Deprecated `craft\events\DraftEvent`. One of the events extending `CraftCms\Cms\Element\Events\DraftEvent` should be used instead.
- Deprecated `craft\behaviors\DraftBehavior`. `CraftCms\Cms\Element\Concerns\Draftable` should be used instead.

#### Elements
- Added `CraftCms\Cms\Element\ElementCaches` and `CraftCms\Cms\Support\Facades\ElementCaches`.
  - Deprecated `craft\services\Elements::getIsCollectingCacheInfo()`. `CraftCms\Cms\Element\ElementCaches::isCollectingCacheInfo()` should be used instead.
  - Deprecated `craft\services\Elements::startCollectingCacheInfo()`. `CraftCms\Cms\Element\ElementCaches::startCollectingCacheInfo()` should be used instead.
  - Deprecated `craft\services\Elements::collectCacheTags()`. `CraftCms\Cms\Element\ElementCaches::collectCacheTags()` should be used instead.
  - Deprecated `craft\services\Elements::setCacheExpiryDate()`. `CraftCms\Cms\Element\ElementCaches::setCacheExpiryDate()` should be used instead.
  - Deprecated `craft\services\Elements::collectCacheInfoForElement()`. `CraftCms\Cms\Element\ElementCaches::collectCacheInfoForElement()` should be used instead.
  - Deprecated `craft\services\Elements::stopCollectingCacheInfo()`. `CraftCms\Cms\Element\ElementCaches::stopCollectingCacheInfo()` should be used instead.
  - Deprecated `craft\services\Elements::invalidateAllCaches()`. `CraftCms\Cms\Element\ElementCaches::invalidateAll()` should be used instead.
  - Deprecated `craft\services\Elements::invalidateCachesForElementType()`. `CraftCms\Cms\Element\ElementCaches::invalidateForElementType()` should be used instead.
  - Deprecated `craft\services\Elements::invalidateCachesForElement()`. `CraftCms\Cms\Element\ElementCaches::invalidateForElement()` should be used instead.
- Added `CraftCms\Cms\Element\BulkOp\BulkOps`, `CraftCms\Cms\Element\BulkOp\BulkOpDeferrals`, and `CraftCms\Cms\Support\Facades\BulkOps`.
  - Deprecated `craft\services\Elements::getBulkOpKeys()`. `CraftCms\Cms\Element\BulkOp\BulkOps::activeKeys()` should be used instead.
  - Deprecated `craft\services\Elements::beginBulkOp()`. `CraftCms\Cms\Element\BulkOp\BulkOps::start()` should be used instead.
  - Deprecated `craft\services\Elements::resumeBulkOp()`. `CraftCms\Cms\Element\BulkOp\BulkOps::resume()` should be used instead.
  - Deprecated `craft\services\Elements::endBulkOp()`. `CraftCms\Cms\Element\BulkOp\BulkOps::end()` should be used instead.
  - Deprecated `craft\services\Elements::trackElementInBulkOps()`. `CraftCms\Cms\Element\BulkOp\BulkOps::trackElement()` should be used instead.
  - Deprecated `craft\services\Elements::ensureBulkOp()`. `CraftCms\Cms\Element\BulkOp\BulkOps::ensure()` should be used instead.
  - Deprecated `craft\events\BulkOpEvent::defer()`. `CraftCms\Cms\Element\BulkOp\BulkOps::defer()` should be used instead.
- Added `CraftCms\Cms\Element\ElementActivity`, `CraftCms\Cms\Element\Data\ElementActivity`, `CraftCms\Cms\Element\Enums\ElementActivityType`, and `CraftCms\Cms\Support\Facades\ElementActivity`.
  - Deprecated `craft\services\Elements::getRecentActivity()`. `CraftCms\Cms\Element\ElementActivity::getRecentActivity()` should be used instead.
  - Deprecated `craft\services\Elements::trackActivity()`. `CraftCms\Cms\Element\ElementActivity::trackActivity()` should be used instead.
- Added `CraftCms\Cms\Element\Actions\ElementAction`, `CraftCms\Cms\Element\ElementActions`, `CraftCms\Cms\Element\Contracts\DeleteActionInterface`, `CraftCms\Cms\Element\Contracts\ElementActionInterface`, `CraftCms\Cms\Element\Events\ElementActionPerformed`, `CraftCms\Cms\Element\Events\ElementActionPerforming`, `CraftCms\Cms\Http\Controllers\Elements\PerformElementActionController`, and `CraftCms\Cms\Support\Facades\ElementActions`.
- Added Laravel-native element action classes under `CraftCms\Cms\Element\Actions`, `CraftCms\Cms\Asset\Actions`, `CraftCms\Cms\Entry\Actions`, and `CraftCms\Cms\User\Actions`.
- Added `CraftCms\Cms\Element\ElementExporters`, `CraftCms\Cms\Element\Contracts\ElementExporterInterface`, `CraftCms\Cms\Element\Exporters\ElementExporter`, `CraftCms\Cms\Http\Controllers\Elements\ElementIndex\ExportElementIndexController`, and `CraftCms\Cms\Support\Facades\ElementExporters`.
- Added Laravel-native element exporter classes under `CraftCms\Cms\Element\Exporters`.
- Deprecated `craft\errors\InvalidTypeException`. `CraftCms\Cms\Element\Exceptions\InvalidTypeException` should be used instead.
- Deprecated `craft\errors\UnsupportedSiteException`. `CraftCms\Cms\Element\Exceptions\UnsupportedSiteException` should be used instead.
- Deprecated `craft\base\ElementAction`, `craft\base\ElementActionInterface`, `craft\elements\actions\DeleteActionInterface`, and the legacy `craft\elements\actions\*` classes. The corresponding `CraftCms\Cms\Element\Actions\*`, `CraftCms\Cms\Asset\Actions\*`, `CraftCms\Cms\Entry\Actions\*`, and `CraftCms\Cms\User\Actions\*` classes should be used instead.
- Deprecated `craft\base\ElementExporter`, `craft\base\ElementExporterInterface`, and the legacy `craft\elements\exporters\*` classes. The corresponding `CraftCms\Cms\Element\Exporters\*` classes should be used instead.

##### Validation
Craft 6 introduces a new validation system that uses Laravel's Validator instead of Yii2's model validation.

###### Added
- Added `CraftCms\Cms\Validation\Contracts\Validatable` interface for classes that support Laravel-style validation.
- Added `CraftCms\Cms\Validation\Contracts\ValidatableWithRuleset` interface for classes that use a `Ruleset` class to define validation rules.
- Added `CraftCms\Cms\Validation\Ruleset` abstract class for defining validation rules, messages, and preparation logic.
- Added `CraftCms\Cms\Validation\Attributes\Ruleset` PHP attribute for specifying a component's ruleset class.
- Added `CraftCms\Cms\Validation\Concerns\Validates` trait for simple validation support.
- Added `CraftCms\Cms\Validation\Concerns\ValidatesWithRuleset` trait for ruleset-based validation.
- Added `CraftCms\Cms\Validation\Concerns\HasScenarios` trait for scenario-based validation filtering.
- Added `CraftCms\Cms\Validation\Concerns\InteractsWithValidator` trait providing common validator interactions.
- Added `CraftCms\Cms\Element\Validation\ElementRules` abstract class for defining element-specific validation rules.
- Added `CraftCms\Cms\Element\Validation\Events\ValidationRulesResolving` event for plugins to modify element validation rules.
- Added `CraftCms\Cms\Element\Validation\Rules\ElementUriRule` for validating element URIs.
- Added element-specific ruleset classes:
  - `CraftCms\Cms\Address\Validation\AddressRules`
  - `CraftCms\Cms\Asset\Validation\AssetRules`
  - `CraftCms\Cms\Entry\Validation\EntryRules`
  - `CraftCms\Cms\User\Validation\UserRules`
  - `CraftCms\Cms\Field\Elements\ContentBlockRules`
- Added `CraftCms\Cms\Asset\Validation\Rules\AssetLocationRule` for validating asset locations.
- Added `CraftCms\Cms\User\Validation\Rules\UserPasswordRule` for validating user passwords.
- Added `CraftCms\Cms\User\Validation\Rules\UsernameRule` for validating usernames.
- Added `CraftCms\Cms\Validation\Rules\UniqueCaseInsensitiveRule` for case-insensitive unique validation.
- Added `CraftCms\Cms\Validation\Rules\DisallowMb4` for disallowing 4-byte UTF-8 characters.
- Added `CraftCms\Cms\Validation\Rules\MoneyRule` for validating money values.

###### Changed
- `FieldInterface::getElementValidationRules()` has been replaced by `FieldInterface::getElementRules()` which returns rules in Laravel's validation format.
- Added `FieldInterface::prepareForElementValidation()` for preparing field values before validation.
- Validation rules are now defined as Laravel-style arrays (e.g., `['required', 'string', 'max:255']`).

###### Deprecations
- Deprecated `craft\base\Model::hasErrors()`. Use `->errors()->has($attribute)` or `->errors()->isNotEmpty()` instead.
- Deprecated `craft\base\Model::getErrors()`. Use `->errors()->get($attribute)` or `->errors()->getMessages()` instead.
- Deprecated `craft\base\Model::addErrors()`. Use `->errors()->add($attribute, $message)` instead.
- Deprecated `craft\base\Model::clearErrors()`. Use `->errors()->forget()` instead.
- Deprecated `CraftCms\Cms\Component\Concerns\ValidatableComponent`. Use `CraftCms\Cms\Validation\Concerns\Validates` instead.
- Deprecated `CraftCms\Cms\Component\Contracts\ValidatableComponentInterface`. Use `CraftCms\Cms\Validation\Contracts\Validatable` instead.
- Deprecated `\craft\records\ContentBlock`. `\CraftCms\Cms\Element\Models\ContentBlock` should be used instead.
- Deprecated `\craft\records\Draft`. `\CraftCms\Cms\Element\Models\Draft` should be used instead.
- Deprecated `\craft\records\Element`. `\CraftCms\Cms\Element\Models\Element` should be used instead.
- Deprecated `\craft\records\Element_SiteSettings`. `\CraftCms\Cms\Element\Models\ElementSiteSettings` should be used instead.
- Deprecated `\craft\records\Revision`. `\CraftCms\Cms\Element\Models\Revision` should be used instead.

#### ElementSources
- Deprecated `craft\services\ElementSources`. `CraftCms\Cms\Element\ElementSources` should be used instead.
- Deprecated `craft\events\DefineSourceSortOptionsEvent`. `CraftCms\Cms\Element\Events\ElementSourceSortOptionsResolving` should be used instead.
- Deprecated `craft\events\DefineSourceTableAttributesEvent`. `CraftCms\Cms\Element\Events\ElementSourceTableAttributesResolving` should be used instead.

#### Element Queries
- Deprecated `craft\elements\db\ElementRelationParamParser`. `CraftCms\Cms\Database\ElementRelationParamFilter` should be used instead.
- Deprecated `craft\elements\db\NestedElementQueryInterface`. `CraftCms\Cms\Element\Queries\Contracts\NestedElementQueryInterface` should be used instead.
- Deprecated `craft\elements\db\NestedElementQueryTrait`. `CraftCms\Cms\Element\Queries\Concerns\QueriesNestedElements` should be used instead.
- Deprecated `craft\elements\db\OrderByPlaceholderExpression`. `CraftCms\Cms\Database\Expressions\OrderByPlaceholderExpression` should be used instead.
- Deprecated `\craft\elements\db\AddressQuery`. `\CraftCms\Cms\Element\Queries\AddressQuery` should be used instead.
- Deprecated `\craft\elements\db\AssetQuery` `\CraftCms\Cms\Element\Queries\AssetQuery` should be used instead.
- Deprecated `\craft\elements\db\ContentBlockQuery` `\CraftCms\Cms\Element\Queries\ContentBlockQuery` should be used instead.
- Deprecated `\craft\elements\db\ElementQuery` `\CraftCms\Cms\Element\Queries\ElementQuery` should be used instead.
- Deprecated `\craft\elements\db\ElementQueryInterface`
- Deprecated `\craft\elements\db\EntryQuery` `\CraftCms\Cms\Element\Queries\EntryQuery` should be used instead.
- Deprecated `\craft\elements\db\UserQuery` `\CraftCms\Cms\Element\Queries\UserQuery` should be used instead.
- Removed `craft\elements\db\ElementQuery::beforePrepare()`. `CraftCms\Cms\Element\Queries\ElementQuery::elementQueryBeforeQuery()` should be overridden instead. ([#19838](https://github.com/craftcms/cms/pull/19838))
- Removed `craft\elements\db\ElementQuery::joinElementTable()`. Element queries should now declare their element table via the `$table` property, which is joined automatically. ([#19838](https://github.com/craftcms/cms/pull/19838))
- Removed `craft\elements\db\ElementQuery::$subQuery`. Element queries are now a single query, so joins and conditions should be applied to `$query`. ([#19838](https://github.com/craftcms/cms/pull/19838))

#### Entries & Entry Types
- Updated entry type table pagination to return Laravel-style pagination metadata and use the `pageTrigger` query parameter.
- Deprecated `craft\services\Entries`. `CraftCms\Cms\Entry\Entries` and `CraftCms\Cms\Entry\EntryTypes` should be used instead.
- Deprecated `craft\models\EntryType`. `CraftCms\Cms\Entry\Data\EntryType` should be used instead.
- Deprecated `craft\records\EntryType`. `CraftCms\Cms\Entry\Models\EntryType` should be used instead.
- Deprecated `craft\records\Entry`. `CraftCms\Cms\Entry\Models\Entry` should be used instead.
- Deprecated `craft\errors\EntryTypeNotFoundException`. `CraftCms\Cms\Entry\Exceptions\EntryTypeNotFoundException` should be used instead.
- Deprecated `craft\events\EntryTypeEvent`. One of these should be used instead:
  - `craft\services\Entries::EVENT_BEFORE_DELETE_ENTRY_TYPE` => `CraftCms\Cms\Section\Events\DeletingEntryType`
  - `craft\services\Entries::EVENT_BEFORE_APPLY_ENTRY_TYPE_DELETE` => `CraftCms\Cms\Entry\Events\ApplyingEntryTypeDelete`
  - `craft\services\Entries::EVENT_AFTER_DELETE_ENTRY_TYPE` => `CraftCms\Cms\Entry\Events\EntryTypeDeleted`
  - `craft\services\Entries::EVENT_BEFORE_SAVE_ENTRY_TYPE` => `CraftCms\Cms\Entry\Events\EntryTypeSaving`
  - `craft\services\Entries::EVENT_AFTER_SAVE_ENTRY_TYPE` => `CraftCms\Cms\Entry\Events\EntryTypeSaved`
- Removed `craft\controllers\EntriesController`. The following controllers now implement this functionality:
  - `CraftCms\Cms\Http\Controllers\Entries\CreateEntryController`
  - `CraftCms\Cms\Http\Controllers\Entries\EntriesIndexController`
  - `CraftCms\Cms\Http\Controllers\Entries\MoveEntryToSectionController`
  - `CraftCms\Cms\Http\Controllers\Entries\StoreEntryController`
- Removed `craft\controllers\EntryTypesController` in favor of `CraftCms\Cms\Http\Controllers\EntryTypesController`
- Removed `craft\console\controllers\EntryTypesController` in favor of:
  - `CraftCms\Cms\Entry\Commands\MergeEntryTypesCommand`

#### Fields
- Updated field index pagination to return Laravel-style pagination metadata and use the configured `pageTrigger` query parameter.
- Removed `craft\controllers\FieldsController` in favor of `CraftCms\Cms\Http\Controllers\FieldsController`.
- Removed `craft\controllers\MatrixController`. `CraftCms\Cms\Http\Controllers\MatrixController` should be used instead.
- Removed `craft\controllers\RelationalFieldsController`. `CraftCms\Cms\Http\Controllers\RelationalFieldsController` should be used instead.
- Deprecated `craft\errors\InvalidFieldException`. `CraftCms\Cms\Field\Exceptions\InvalidFieldException` should be used instead.
- Deprecated `craft\fields\data\ColorData`. `CraftCms\Cms\Field\Data\ColorData` should be used instead.
- Deprecated `craft\fields\data\IconData`. `CraftCms\Cms\Field\Data\IconData` should be used instead.
- Deprecated `craft\fields\data\JsonData`. `CraftCms\Cms\Field\Data\JsonData` should be used instead.
- Deprecated `craft\fields\data\LinkData`. `CraftCms\Cms\Field\Data\LinkData` should be used instead.
- Deprecated `craft\fields\data\MultiOptionsFieldData`. `CraftCms\Cms\Field\Data\MultiOptionsFieldData` should be used instead.
- Deprecated `craft\fields\data\OptionData`. `CraftCms\Cms\Field\Data\OptionData` should be used instead.
- Deprecated `craft\fields\data\SingleOptionFieldData`. `CraftCms\Cms\Field\Data\SingleOptionFieldData` should be used instead.
- Deprecated `craft\fields\linktypes\Asset`. `CraftCms\Cms\Field\LinkTypes\Asset` should be used instead.
- Deprecated `craft\fields\linktypes\BaseElementLinkType`. `CraftCms\Cms\Field\LinkTypes\BaseElementLinkType` should be used instead.
- Deprecated `craft\fields\linktypes\BaseLinkType`. `CraftCms\Cms\Field\LinkTypes\BaseLinkType` should be used instead.
- Deprecated `craft\fields\linktypes\BaseTextLinkType`. `CraftCms\Cms\Field\LinkTypes\BaseTextLinkType` should be used instead.
- Deprecated `craft\fields\linktypes\Category`. `CraftCms\Cms\Field\LinkTypes\Category` should be used instead.
- Deprecated `craft\fields\linktypes\Email`. `CraftCms\Cms\Field\LinkTypes\Email` should be used instead.
- Deprecated `craft\fields\linktypes\Entry`. `CraftCms\Cms\Field\LinkTypes\Entry` should be used instead.
- Deprecated `craft\fields\linktypes\Phone`. `CraftCms\Cms\Field\LinkTypes\Phone` should be used instead.
- Deprecated `craft\fields\linktypes\Sms`. `CraftCms\Cms\Field\LinkTypes\Sms` should be used instead.
- Deprecated `craft\fields\linktypes\Url`. `CraftCms\Cms\Field\LinkTypes\Url` should be used instead.
- Deprecated `craft\fields\Addresses`. `CraftCms\Cms\Field\Addresses` should be used instead.
- Deprecated `craft\fields\Assets`. `CraftCms\Cms\Field\Assets` should be used instead.
- Deprecated `craft\fields\BaseOptionsField`. `CraftCms\Cms\Field\BaseOptionsField` should be used instead.
- Deprecated `craft\fields\BaseRelationField`. `CraftCms\Cms\Field\BaseRelationField` should be used instead.
- Deprecated `craft\fields\ButtonGroup`. `CraftCms\Cms\Field\ButtonGroup` should be used instead.
- Deprecated `craft\fields\Categories`. `CraftCms\Cms\Field\Categories` should be used instead.
- Deprecated `craft\fields\Checkboxes`. `CraftCms\Cms\Field\Checkboxes` should be used instead.
- Deprecated `craft\fields\Color`. `CraftCms\Cms\Field\Color` should be used instead.
- Deprecated `craft\fields\ContentBlock`. `CraftCms\Cms\Field\ContentBlock` should be used instead.
- Deprecated `craft\fields\Country`. `CraftCms\Cms\Field\Country` should be used instead.
- Deprecated `craft\fields\Date`. `CraftCms\Cms\Field\Date` should be used instead.
- Deprecated `craft\fields\Dropdown`. `CraftCms\Cms\Field\Dropdown` should be used instead.
- Deprecated `craft\fields\Email`. `CraftCms\Cms\Field\Email` should be used instead.
- Deprecated `craft\fields\Entries`. `CraftCms\Cms\Field\Entries` should be used instead.
- Deprecated `craft\fields\Icon`. `CraftCms\Cms\Field\Icon` should be used instead.
- Deprecated `craft\fields\Json`. `CraftCms\Cms\Field\Json` should be used instead.
- Deprecated `craft\fields\Lightswitch`. `CraftCms\Cms\Field\Lightswitch` should be used instead.
- Deprecated `craft\fields\Link`. `CraftCms\Cms\Field\Link` should be used instead.
- Deprecated `craft\fields\Matrix`. `CraftCms\Cms\Field\Matrix` should be used instead.
- Deprecated `craft\fields\MissingField`. `CraftCms\Cms\Field\MissingField` should be used instead.
- Deprecated `craft\fields\Money`. `CraftCms\Cms\Field\Money` should be used instead.
- Deprecated `craft\fields\MultiSelect`. `CraftCms\Cms\Field\MultiSelect` should be used instead.
- Deprecated `craft\fields\Number`. `CraftCms\Cms\Field\Number` should be used instead.
- Deprecated `craft\fields\PlainText`. `CraftCms\Cms\Field\PlainText` should be used instead.
- Deprecated `craft\fields\RadioButtons`. `CraftCms\Cms\Field\RadioButtons` should be used instead.
- Deprecated `craft\fields\Range`. `CraftCms\Cms\Field\Range` should be used instead.
- Deprecated `craft\fields\Table`. `CraftCms\Cms\Field\Table` should be used instead.
- Deprecated `craft\fields\Tags`. `CraftCms\Cms\Field\Tags` should be used instead.
- Deprecated `craft\fields\Time`. `CraftCms\Cms\Field\Time` should be used instead.
- Deprecated `craft\fields\Url`. `CraftCms\Cms\Field\Url` should be used instead.
- Deprecated `craft\fields\Users`. `CraftCms\Cms\Field\Users` should be used instead.
- Deprecated `craft\services\Fields`. `CraftCms\Cms\Field\Fields` should be used instead.

#### GQL
- Deprecated `\craft\records\GqlSchema`. `\CraftCms\Cms\Gql\Models\GqlSchema` should be used instead.
- Deprecated `\craft\records\GqlToken`. `\CraftCms\Cms\Gql\Models\GqlToken` should be used instead.

#### HTTP
- Deprecated `craft\filters\BasicHttpAuthLogin`. Use the `auth.basic` middleware instead. (see https://laravel.com/docs/12.x/authentication#http-basic-authentication)
- Deprecated `craft\filters\BasicHttpAuthStatic`. Use the `auth.basic` middleware instead. (see https://laravel.com/docs/12.x/authentication#http-basic-authentication)
- Deprecated `craft\filters\BasicHttpAuthTrait`. Use the `auth.basic` middleware instead. (see https://laravel.com/docs/12.x/authentication#http-basic-authentication)
- Deprecated `craft\filters\Cors`. Use Laravel's CORS settings instead. (see https://laravel.com/docs/12.x/routing#cors)
- Deprecated `craft\filters\Headers`. Use Laravel middleware instead. (see https://laravel.com/docs/middleware)
- Deprecated `craft\filters\ConditionalFilterTrait`.
- Deprecated `craft\filters\SiteFilterTrait`.
- Deprecated `craft\filters\UtilityAccess`.
- Deprecated `craft\controllers\AppController::actionLicensingIssues()`. `CraftCms\Cms\Http\Middleware\EnforceLicenses` should be used instead.
- Removed `craft\controllers\AppController::actionHealthCheck()`. `CraftCms\Cms\Http\Controllers\App\HealthCheckController` should be used instead.
- Removed `craft\controllers\AppController::actionGetCpAlerts()` and `actionShunCpAlert()`. `CraftCms\Cms\Http\Controllers\App\CpAlertsController` should be used instead.
- Removed `craft\controllers\AppController::actionIconPickerOptions()`. Use `CraftCms\Cms\Http\Controllers\IconController::pickerOptions()` instead.
- Removed `craft\controllers\AppController::actionSetLicenseShunCookie()`. `CraftCms\Cms\Http\Controllers\App\LicensesController::setShunCookie()` should be used instead.
- Removed `craft\controllers\AppController::actionGetPluginLicenseInfo()` and `actionUpdatePluginLicense()`. `CraftCms\Cms\Http\Controllers\App\PluginsController` should be used instead.
- Removed `craft\controllers\AppController::actionBrokenImage()`. `CraftCms\Cms\Http\Middleware\ShowBrokenImage` should be used instead.
- Removed `craft\controllers\AppController::actionRenderElements()` and `actionRenderComponents()`. `CraftCms\Cms\Http\Controllers\App\RenderController` should be used instead.
- Removed `craft\controllers\NotFoundController`. Laravel's exception handling should be used instead.
- Removed the header-setting logic in `yii2-adapter\legacy\web\Application`. The new `\CraftCms\Cms\Http\Middleware\SetHeaders` middleware handles this functionality.
- Removed the licensing issues screen logic in `yii2-adapter\legacy\web\Application`. The new `\CraftCms\Cms\Http\Middleware\EnforceLicenses` middleware handles this functionality.
- Removed `craft\controllers\AppController::actionTryEdition()` and `actionSwitchToLicensedEdition()` in favor of `CraftCms\Cms\Http\Controllers\EditionController`.

#### Mail
- Added `CraftCms\Cms\Email\Commands\SendTestMailCommand`.
- Added `CraftCms\Cms\Email\Mailables\CraftMailable`, a base mailable class that automatically applies project config email settings (from, replyTo, mailer) with site-specific overrides.
- Added `CraftCms\Cms\SystemMessage\Mailables\SystemMessageMailable`.
- Deprecated `Craft::$app->getMailer()`. Laravel mailers/drivers and `CraftCms\Cms\SystemMessage\SystemMessages::mailable()` should be used instead.
- Deprecated `craft\mail\Mailer`. Laravel mailers/drivers and `CraftCms\Cms\SystemMessage\SystemMessages::mailable()` should be used instead.
- Deprecated `craft\helpers\MailerHelper`. Laravel mail configuration and drivers should be used instead.
- Deprecated `craft\mail\Mailer::$template`, `craft\mail\Mailer::$siteOverrides`, `craft\models\MailSettings::$template`, and `craft\models\MailSettings::$siteOverrides`. Laravel mailable views and environment-specific Laravel mailers should be used instead.

#### Migrations
- Deprecated `craft\db\Migration`. `CraftCms\Cms\Database\Migration` should be used instead.
- Deprecated `craft\db\MigrationManager`
- Removed `craft\helpers\MigrationHelper` as it was deprecated since 4.0.0.
- Removed `craft\console\controllers\InstallController` in favor of:
  - `CraftCms\Cms\Console\Commands\InstallCommand`
  - `CraftCms\Cms\Console\Commands\InstallCheckCommand`
- Removed `craft\console\controllers\MigrateController` in favor of:
  - `CraftCms\Cms\Database\Commands\MigrateCommand`
- Removed `craft\console\controllers\UpController` in favor of:
  - `CraftCms\Cms\Console\Commands\UpCommand`

#### Request
- Added `Request::isPreview()` macro for detecting preview requests via `x-craft-preview` or `x-craft-live-preview` parameters.
- Added `Request::isCpRequest()`, `Request::isSiteRequest()`, `Request::isActionRequest()`, `Request::actionSegments()`, `Request::actionSegmentsToRoute()`, `Request::duplicateWithUri()`, `Request::getToken()`, and `Request::getSigned()` macros.

#### Security
- Added `CraftCms\Cms\Support\Security`.
- Added `CraftCms\Cms\Support\Facades\Security`.
- Added `CraftCms\Cms\Http\Middleware\AddLogContext`.
- Deprecated `Craft::$app->getSecurity()` in favor of Laravel's Hash and Crypt facades, or `CraftCms\Cms\Support\Facades\Security`.

#### Updates
The `craft\services\Updates` internal service has been removed. `CraftCms\Cms\Update\Updates` should be used instead.

Moved the following controllers:
- `craft\controllers\ConfigSyncController` => `CraftCms\Cms\Http\Controllers\ConfigSyncController`
- `craft\controllers\InstallController` => `CraftCms\Cms\Http\Controllers\InstallController`
- `craft\controllers\MigrateController` => `CraftCms\Cms\Http\Controllers\MigrateController`
- `craft\controllers\PluginStoreController` => `CraftCms\Cms\Http\Controllers\PluginStore\PluginStoreController`
- `craft\controllers\PluginStore\InstallController` => `CraftCms\Cms\Http\Controllers\PluginStore\InstallController`
- `craft\controllers\PluginStore\RemoveController` => `CraftCms\Cms\Http\Controllers\PluginStore\RemoveController`
- `craft\controllers\UpdaterController` => `CraftCms\Cms\Http\Controllers\Updates\UpdaterController`
- `craft\controllers\UpdatesController` => `CraftCms\Cms\Http\Controllers\Updates\UpdatesController`
- `craft\console\controllers\UpdateController` in favor of these commands:
  - `CraftCms\Cms\Update\Commands\UpdateCommand`
  - `CraftCms\Cms\Update\Commands\ComposerInstallCommand`
  - `CraftCms\Cms\Update\Commands\InfoCommand`

###### Deprecations & removals
- Deprecated `craft\helpers\Install`. `CraftCms\Cms\Site\Concerns\SiteDefaults` should be used instead.
- Deprecated `craft\helpers\Update`. The only method was `checkPhpConstraint` which is now available on `CraftCms\Cms\Support\PHP::checkConstraint()`
- Removed `craft\events\UpdateReleaseEvent` in favor of `CraftCms\Cms\Update\Events\CriticalUpdateReleasedEvent`
- Removed `craft\models\Update`. `CraftCms\Cms\Update\Data\Update` should be used instead.
- Removed `craft\models\UpdateRelease`. `CraftCms\Cms\Update\Data\UpdateRelease` should be used instead.
- Removed `craft\models\Updates`. `CraftCms\Cms\Update\Data\Updates` should be used instead.

##### Users
- Removed `craft\console\controllers\UsersController` in favor of the following commands (signatures are the same):
  - `CraftCms\Cms\User\Commands\ActivationUrlCommand`
  - `CraftCms\Cms\User\Commands\CreateCommand`
  - `CraftCms\Cms\User\Commands\DeleteCommand`
  - `CraftCms\Cms\User\Commands\ImpersonateCommand`
  - `CraftCms\Cms\User\Commands\ListAdminsCommand`
  - `CraftCms\Cms\User\Commands\LogoutAllCommand`
  - `CraftCms\Cms\User\Commands\PasswordResetUrlCommand`
  - `CraftCms\Cms\User\Commands\Remove2faCommand`
  - `CraftCms\Cms\User\Commands\SetPasswordCommand`
  - `CraftCms\Cms\User\Commands\UnlockCommand`

#### Project Config
- Deprecated `craft\services\ProjectConfig`. `CraftCms\Cms\ProjectConfig\ProjectConfig` should be used instead.
- Removed `craft\controllers\ProjectConfigController` in favor of `CraftCms\Cms\Http\Controllers\Utilities\ProjectConfigController`
- Removed `craft\console\controllers\PcController` & `craft\console\controllers\ProjectConfigController` in favor of the following commands:
  - `CraftCms\Cms\ProjectConfig\Commands\ApplyCommand`
  - `CraftCms\Cms\ProjectConfig\Commands\DiffCommand`
  - `CraftCms\Cms\ProjectConfig\Commands\ExportCommand`
  - `CraftCms\Cms\ProjectConfig\Commands\GetCommand`
  - `CraftCms\Cms\ProjectConfig\Commands\RebuildCommand`
  - `CraftCms\Cms\ProjectConfig\Commands\RemoveCommand`
  - `CraftCms\Cms\ProjectConfig\Commands\SetCommand`
  - `CraftCms\Cms\ProjectConfig\Commands\TouchCommand`
  - `CraftCms\Cms\ProjectConfig\Commands\WriteCommand`
  - All commands can be called using either `php craft project-config` or `php craft pc`
- Deprecated `craft\events\ConfigEvent` in favor of the following events:
  - `CraftCms\Cms\ProjectConfig\Events\ProjectConfigItemAdding`
  - `CraftCms\Cms\ProjectConfig\Events\ItemAdded`
  - `CraftCms\Cms\ProjectConfig\Events\ProjectConfigItemUpdated`
  - `CraftCms\Cms\ProjectConfig\Events\ItemUpdated`
  - `CraftCms\Cms\ProjectConfig\Events\ProjectConfigItemRemoved`
  - `CraftCms\Cms\ProjectConfig\Events\ItemRemoved`
- Deprecated `craft\services\ProjectConfig::EVENT_AFTER_APPLY_CHANGES`
  - Added `CraftCms\Cms\ProjectConfig\Events\ChangesApplied`
- Deprecated `craft\services\ProjectConfig::EVENT_AFTER_WRITE_YAML_FILES`
- Added `CraftCms\Cms\ProjectConfig\Events\YamlFilesWritten`
- Deprecated `craft\services\ProjectConfig::EVENT_REBUILD`
  - Added `CraftCms\Cms\ProjectConfig\Events\ProjectConfigRebuilt`
- Removed `craft\errors\BusyResourceException` in favor of `CraftCms\Cms\ProjectConfig\Exceptions\BusyResourceException`
- Removed `craft\errors\StaleResourceException` in favor of `CraftCms\Cms\ProjectConfig\Exceptions\StaleResourceException`
- Added `CraftCms\Cms\ProjectConfig\Exceptions\ReadonlyException`
- Removed `craft\models\ProjectConfigData` in favor of `CraftCms\Cms\ProjectConfig\Data\ProjectConfigData`
- Removed `craft\models\ReadOnlyProjectConfigData` in favor of `CraftCms\Cms\ProjectConfig\Data\ReadOnlyProjectConfigData`
- Deprecated `craft\helpers\ProjectConfig`. `CraftCms\Cms\ProjectConfig\ProjectConfigHelper` should be used instead.

#### Revisions
- Deprecated `craft\services\Revisions`. `CraftCms\Cms\Element\Revisions` should be used instead.
- Deprecated `craft\events\RevisionEvent`. One of the events extending `CraftCms\Cms\Element\Events\RevisionEvent` should be used instead.
- Deprecated `craft\behaviors\RevisionBehavior`. `CraftCms\Cms\Element\Concerns\Revisionable` should be used instead.

#### Routes
- Deprecated `craft\services\Routes`. `CraftCms\Cms\Route\Routes` should be used instead.

#### Search
- Added `CraftCms\Cms\Support\Facades\Search`.
- Deprecated `craft\services\Search`. `CraftCms\Cms\Search\Search` should be used instead.
- Deprecated `Craft::$app->getSearch()`. `CraftCms\Cms\Support\Facades\Search` or `app(CraftCms\Cms\Search\Search::class)` should be used instead.
- Deprecated `craft\search\SearchQuery`. `CraftCms\Cms\Search\SearchQuery` should be used instead.
- Deprecated `craft\search\SearchQueryTerm`. `CraftCms\Cms\Search\SearchQueryTerm` should be used instead.
- Deprecated `craft\search\SearchQueryTermGroup`. `CraftCms\Cms\Search\SearchQueryTermGroup` should be used instead.
- Deprecated `craft\events\SearchEvent` in favor of the following new events:
  - `craft\services\Search::EVENT_BEFORE_SEARCH` => `CraftCms\Cms\Search\Events\SearchStarting`
  - `craft\services\Search::EVENT_AFTER_SEARCH` => `CraftCms\Cms\Search\Events\SearchScoresResolving` and `CraftCms\Cms\Search\Events\SearchPerformed`
  - `craft\services\Search::EVENT_BEFORE_SCORE_RESULTS` => `CraftCms\Cms\Search\Events\SearchResultsResolving` and `CraftCms\Cms\Search\Events\SearchScoresResolving`
- Deprecated `craft\events\IndexKeywordsEvent`. `CraftCms\Cms\Search\Events\KeywordsIndexing` should be used instead.

#### Sections
- Updated section index pagination to return Laravel-style pagination metadata and use the configured `pageTrigger` query parameter.
- Deprecated the section related methods in `craft\services\Entries`. `CraftCms\Cms\Section\Sections` should be used instead.
- Deprecated `craft\models\Section`. `CraftCms\Cms\Section\Data\Section` should be used instead.
- Deprecated `craft\records\Section`. `CraftCms\Cms\Section\Models\Section` should be used instead.
- Deprecated `craft\models\Section_SiteSettings`. `CraftCms\Cms\Section\Data\SectionSiteSettings` should be used instead.
- Deprecated `craft\records\Section_SiteSettings`. `CraftCms\Cms\Section\Models\SectionSiteSettings` should be used instead.
- Deprecated `craft\events\SectionEvent`. One of these should be used instead:
  - `craft\services\Entries::EVENT_BEFORE_DELETE_SECTION` => `CraftCms\Cms\Section\Events\SectionDeleting`
  - `craft\services\Entries::EVENT_BEFORE_APPLY_SECTION_DELETE` => `CraftCms\Cms\Section\Events\SectionDeletionApplying`
  - `craft\services\Entries::EVENT_AFTER_DELETE_SECTION` => `CraftCms\Cms\Section\Events\SectionDeleted`
  - `craft\services\Entries::EVENT_BEFORE_SAVE_SECTION` => `CraftCms\Cms\Section\Events\SectionSaving`
  - `craft\services\Entries::EVENT_AFTER_SAVE_SECTION` => `CraftCms\Cms\Section\Events\SectionSaved`
- Removed `craft\controllers\SectionsController` in favor of `CraftCms\Cms\Http\Controllers\SectionsController`
- Removed `craft\console\controllers\SectionsController` in favor of:
  - `CraftCms\Cms\Section\Commands\CreateCommand`
  - `CraftCms\Cms\Section\Commands\DeleteCommand`
- Added `CraftCms\Cms\Section\Enums\DefaultPlacement`
- Added `CraftCms\Cms\Section\Enums\SectionType`
- Deprecated `craft\errors\SectionNotFoundException`. `CraftCms\Cms\Section\Exceptions\SectionNotFoundException` should be used instead.

#### Sites
- Deprecated `craft\services\Sites`. `CraftCms\Cms\Site\Sites` should be used instead.
- Deprecated `craft\models\Site`. `CraftCms\Cms\Site\Data\Site` should be used instead.
- Deprecated `craft\models\SiteGroup`. `CraftCms\Cms\Site\Data\SiteGroup` should be used instead.
- Deprecated `craft\records\Site`. `CraftCms\Cms\Site\Models\Site` should be used instead.
- Deprecated `craft\records\SiteGroup`. `CraftCms\Cms\Site\Models\SiteGroup` should be used instead.
- Deprecated `craft\events\SiteEvent`. One of `CraftCms\Cms\Site\Events\*` should be used instead.
- Deprecated `craft\events\DeleteSiteEvent`. One of `CraftCms\Cms\Site\Events\SiteDeleting` or `CraftCms\Cms\Site\Events\SiteDeleted` should be used instead.
- Deprecated `craft\events\ReorderSitesEvent`. One of `CraftCms\Cms\Site\Events\SitesReordering` or `CraftCms\Cms\Site\Events\SitesReordered` should be used instead.
- Deprecated `craft\events\SiteGroupEvent`. One of `CraftCms\Cms\Site\Events\*` should be used instead.
- Deprecated `craft\errors\SiteNotFoundException`. `CraftCms\Cms\Site\Exceptions\SiteNotFoundException` should be used instead.
- Deprecated `craft\errors\SiteGroupNotFoundException`.
- Removed `craft\controllers\SitesController` in favor of:
  - `CraftCms\Cms\Http\Controllers\Settings\SitesController`
  - `CraftCms\Cms\Http\Controllers\Settings\SiteGroupsController`

#### Structures
- Deprecated `craft\services\Structures`. `CraftCms\Cms\Structure\Structures` should be used instead.
- Deprecated `craft\models\Structure`. `CraftCms\Cms\Structure\Data\Structure` should be used instead.
- Deprecated `craft\records\Structure`. `CraftCms\Cms\Structure\Models\Structure` should be used instead.
- Deprecated `craft\records\StructureElement`. `CraftCms\Cms\Structure\Models\StructureElement` should be used instead.
- Replaced `craft\controllers\StructuresController`. `CraftCms\Cms\Http\Controllers\StructuresController`.
- Replaced structure related commands in `craft\console\controllers\RepairController` with:
  - `\CraftCms\Cms\Structure\Commands\RepairCategoryGroupStructureCommand`
  - `\CraftCms\Cms\Structure\Commands\RepairSectionStructureCommand`

#### System Messages
- Deprecated `craft\services\SystemMessages`. `CraftCms\Cms\SystemMessage\SystemMessages` should be used instead.
- Deprecated `craft\models\SystemMessage` and `craft\records\SystemMessage`. `CraftCms\Cms\SystemMessage\Models\SystemMessage` should be used instead.
- Replaced `craft\controllers\SystemMessagesController` with `CraftCms\Cms\Http\Controllers\Utilities\SystemMessagesController`
- System messages can now list the Twig variables they’re rendered with via a `variables` attribute, which the System Messages utility suggests while editing. Added `CraftCms\Cms\SystemMessage\SystemMessages::getTemplateVariables()`. ([#19946](https://github.com/craftcms/cms/pull/19946))

#### Tokens
- Deprecated `craft\services\Tokens`. `CraftCms\Cms\RouteToken\RouteTokens` should be used instead.
- Deprecated `craft\records\Token`. `CraftCms\Cms\RouteToken\Models\RouteToken` should be used instead.

#### Twig
- Added `CraftCms\Cms\Twig\Twig` service for managing Twig environments, replacing the Twig management logic previously in `craft\web\View`.
- Added `CraftCms\Cms\Twig\TemplateRenderer` for rendering templates, replacing the rendering logic previously in `craft\web\View`.
- Added `CraftCms\Cms\View\PageLifecycle` for managing the page rendering lifecycle (head/body placeholder replacement), replacing the page lifecycle logic previously in `craft\web\View`.
- Added `CraftCms\Cms\Support\Facades\Twig` facade, resolving to `CraftCms\Cms\Twig\TemplateRenderer`.
- Added `CraftCms\Cms\Twig\Environment`, moved from `craft\web\twig\Environment`.
- Added `CraftCms\Cms\View\TemplateResolver`.
- Added `CraftCms\Cms\Twig\TemplateLoader`.
- Added `CraftCms\Cms\Twig\Exceptions\TemplateLoaderException`.
- Added helper functions in the `CraftCms\Cms` namespace: `template()`, `sandboxedTemplate()`, `pageTemplate()`, `renderString()`, `renderSandboxedString()`, `renderObjectTemplate()`, `renderSandboxedObjectTemplate()`.
- Deprecated `craft\web\View::getTwig()`. `CraftCms\Cms\Twig\Twig::get()` should be used instead.
- Deprecated `craft\web\View::setTwig()`. `CraftCms\Cms\Twig\Twig::set()` should be used instead.
- Deprecated `craft\web\View::createTwig()`. `CraftCms\Cms\Twig\Twig::create()` should be used instead.
- Deprecated `craft\web\View::registerCpTwigExtension()`. `CraftCms\Cms\Twig\Twig::registerExtension()` should be used instead.
- Deprecated `craft\web\View::registerSiteTwigExtension()`. `CraftCms\Cms\Twig\Twig::registerExtension()` should be used instead.
- Deprecated `craft\web\View::registerTwigExtension()`. `CraftCms\Cms\Twig\Twig::registerExtension()` should be used instead.
- Deprecated `craft\web\View::renderTemplate()`. `CraftCms\Cms\Twig\TemplateRenderer::renderTemplate()` or the `template()` helper should be used instead.
- Deprecated `craft\web\View::renderSandboxedTemplate()`. `CraftCms\Cms\Twig\TemplateRenderer::renderSandboxedTemplate()` or the `sandboxedTemplate()` helper should be used instead.
- Deprecated `craft\web\View::renderPageTemplate()`. `CraftCms\Cms\Twig\TemplateRenderer::renderPageTemplate()` or the `pageTemplate()` helper should be used instead.
- Deprecated `craft\web\View::renderString()`. `CraftCms\Cms\Twig\TemplateRenderer::renderString()` or the `renderString()` helper should be used instead.
- Deprecated `craft\web\View::renderSandboxedString()`. `CraftCms\Cms\Twig\TemplateRenderer::renderSandboxedString()` or the `renderSandboxedString()` helper should be used instead.
- Deprecated `craft\web\View::renderObjectTemplate()`. `CraftCms\Cms\Twig\TemplateRenderer::renderObjectTemplate()` or the `renderObjectTemplate()` helper should be used instead.
- Deprecated `craft\web\View::renderSandboxedObjectTemplate()`. `CraftCms\Cms\Twig\TemplateRenderer::renderSandboxedObjectTemplate()` or the `renderSandboxedObjectTemplate()` helper should be used instead.
- Deprecated `craft\web\View::normalizeObjectTemplate()`. `CraftCms\Cms\Twig\TemplateRenderer::normalizeObjectTemplate()` should be used instead.
- Deprecated `craft\web\View::getIsRenderingTemplate()`. `CraftCms\Cms\Twig\TemplateRenderer::isRenderingTemplate` should be used instead.
- Deprecated `craft\web\View::getIsRenderingPageTemplate()`. `CraftCms\Cms\Twig\TemplateRenderer::isRenderingPageTemplate` should be used instead.
- Deprecated `craft\web\twig\Environment`. `CraftCms\Cms\Twig\Environment` should be used instead.
- Deprecated `craft\web\View::EVENT_AFTER_CREATE_TWIG`. `CraftCms\Cms\Twig\Events\TwigCreated` should be used instead.
- Deprecated `craft\web\View::doesTemplateExist()`. `CraftCms\Cms\View\TemplateResolver::doesTemplateExist()` should be used instead.
- Deprecated `craft\web\View::resolveTemplate()`. `CraftCms\Cms\View\TemplateResolver::resolveTemplate()` should be used instead.
- Deprecated `craft\web\twig\TemplateLoader`. `CraftCms\Cms\Twig\TemplateLoader` should be used instead.
- Deprecated `craft\web\twig\TemplateLoaderException`. `CraftCms\Cms\Twig\Exceptions\TemplateLoaderException` should be used instead.

##### Events
- Added `CraftCms\Cms\Twig\Events\TwigCreated`, dispatched when a Twig environment is created.
- Added `CraftCms\Cms\Twig\Events\TemplateRendering`, dispatched before a template is rendered. Supports cancellation via `ValidatableEvent`.
- Added `CraftCms\Cms\Twig\Events\TemplateRendered`, dispatched after a template is rendered. Has a mutable `output` property.
- Added `CraftCms\Cms\Twig\Events\PageTemplateRendering`, dispatched before a page template is rendered. Supports cancellation via `ValidatableEvent`.
- Added `CraftCms\Cms\Twig\Events\PageTemplateRendered`, dispatched after a page template is rendered. Has a mutable `output` property.
- Added `CraftCms\Cms\Twig\Events\PageStarting`, dispatched when page rendering begins.
- Added `CraftCms\Cms\Twig\Events\PageEnded`, dispatched when page rendering ends. Has nullable `headHtml`, `bodyBeginHtml`, and `bodyEndHtml` properties for overriding `HtmlStack` output.
- Deprecated `craft\web\View::EVENT_BEFORE_RENDER_TEMPLATE`. `CraftCms\Cms\Twig\Events\TemplateRendering` should be used instead.
- Deprecated `craft\web\View::EVENT_AFTER_RENDER_TEMPLATE`. `CraftCms\Cms\Twig\Events\TemplateRendered` should be used instead.
- Deprecated `craft\web\View::EVENT_BEFORE_RENDER_PAGE_TEMPLATE`. `CraftCms\Cms\Twig\Events\PageTemplateRendering` should be used instead.
- Deprecated `craft\web\View::EVENT_AFTER_RENDER_PAGE_TEMPLATE`. `CraftCms\Cms\Twig\Events\PageTemplateRendered` should be used instead.

#### Translations
- Deprecated `craft\i18n\FormatConverter`. `CraftCms\Cms\Translation\FormatConverter` should be used instead.
- Deprecated `craft\i18n\Formatter`. `CraftCms\Cms\Translation\Formatter` should be used instead.
- Deprecated `craft\i18n\I18N`. `CraftCms\Cms\Translation\I18N` should be used instead.
- Deprecated `craft\i18n\Locale`. `CraftCms\Cms\Translation\Locale` should be used instead.
- Deprecated `craft\i18n\MessageFormatter`.
- Deprecated `craft\i18n\PhpMessageSource`.
- Deprecated `craft\i18n\Translation`. `CraftCms\Cms\Support\Facades\I18N` should be used instead.
- Deprecated `Craft::t`. `CraftCms\Cms\t` should be used instead.

#### Users
- `CraftCms\Cms\User\Elements\User` now implements `Illuminate\Contracts\Auth\Authenticatable`, `Illuminate\Contracts\Auth\Access\Authorizable`, `Illuminate\Contracts\Auth\CanResetPassword`, and `Illuminate\Contracts\Auth\MustVerifyEmail`.
- Added `CraftCms\Cms\User\Notifications\VerifyEmailNotification`.
- `Users::purgeExpiredPendingUsers()` now joins the `password_reset_tokens` table to find expired pending users.
- Removed `verificationCode` and `verificationCodeIssuedDate` columns on the `users` table in favor of the `password_reset_tokens` table.
- Deprecated `craft\services\Users::isVerificationCodeValidForUser()`. `Password::broker()->tokenExists($user, $code)` should be used instead.
- Removed `craft\controllers\UsersController` in favor of:
  - `CraftCms\Cms\Http\Controllers\Users\ActivateController`.
  - `CraftCms\Cms\Http\Controllers\Users\PasswordController`.
  - `CraftCms\Cms\Http\Controllers\Users\SaveUserController`.
- Removed `\craft\controllers\UserSettingsController` in favor of:
  - `CraftCms\Cms\Http\Controllers\Settings\Users\UserGroupsController`
  - `CraftCms\Cms\Http\Controllers\Settings\Users\UserSettingsController`
- Deprecated `UserGroupEvent` in favor of:
  - `CraftCms\Cms\User\Events\UserGroupSaving`
  - `CraftCms\Cms\User\Events\UserGroupSaved`
  - `CraftCms\Cms\User\Events\UserGroupDeletionApplying`
  - `CraftCms\Cms\User\Events\UserGroupDeleting`
  - `CraftCms\Cms\User\Events\UserGroupDeleted`
- Deprecated `\craft\exceptions\UserGroupNotFoundException`.
- Deprecated `\craft\services\UserGroups`. `CraftCms\Cms\User\UserGroups` should be used instead.
- Deprecated `\craft\models\UserGroup`. `CraftCms\Cms\User\Data\UserGroup` should be used instead.
- Deprecated `\craft\records\User`. `\CraftCms\Cms\User\Models\User` should be used instead.
- Deprecated `\craft\records\UserGroup`. `\CraftCms\Cms\User\Models\UserGroup` should be used instead.
- Deprecated `\craft\records\UserPermission`. `\CraftCms\Cms\User\Models\UserPermission` should be used instead.
- Deprecated `craft\services\UserPermissions`. `CraftCms\Cms\User\UserPermissions` should be used instead.
- Deprecated `craft\events\DefineEditUserScreensEvent`. `CraftCms\Cms\User\Events\EditUserScreensResolving` should be used instead.

#### View
- Added `CraftCms\Cms\View\TwigEngine`.
- Added `CraftCms\Cms\View\HtmlStack`.
- Added `CraftCms\Cms\Support\Facades\HtmlStack`.
- Added `CraftCms\Cms\View\Enums\Position` enum.
- Added `CraftCms\Cms\View\InputNamespace`.
- Added `CraftCms\Cms\Support\Facades\InputNamespace`.
- Added `CraftCms\Cms\View\TemplateHooks`.
- Added `CraftCms\Cms\Support\Facades\TemplateHooks`.
- Added `CraftCms\Cms\View\DeltaRegistry`.
- Added `CraftCms\Cms\Support\Facades\DeltaRegistry`.
- Added `CraftCms\Cms\View\TemplateMode` enum.
- Added `CraftCms\Cms\View\TemplateRoots`.
- Added `CraftCms\Cms\View\TemplateCaches`.
- Added `CraftCms\Cms\View\CacheCollectors\DependencyCollector`.
- Added `CraftCms\Cms\View\CacheCollectors\ResourceCollector`.
- Added `CraftCms\Cms\View\Contracts\CacheCollectorInterface`.
- Added `CraftCms\Cms\View\Data\TemplateCacheContext`.
- Added `CraftCms\Cms\View\TemplateCacheCollectors`.
- Deprecated `craft\services\TemplateCaches`. `CraftCms\Cms\View\TemplateCaches` should be used instead.
- Deprecated `craft\web\View::registerJs()`. `CraftCms\Cms\View\HtmlStack::js()` should be used instead.
- Deprecated `craft\web\View::registerJsWithVars()`. `CraftCms\Cms\View\HtmlStack::jsWithVars()` should be used instead.
- Deprecated `craft\web\View::registerJsFile()`. `CraftCms\Cms\View\HtmlStack::jsFile()` should be used instead.
- Deprecated `craft\web\View::registerCss()`. `CraftCms\Cms\View\HtmlStack::css()` should be used instead.
- Deprecated `craft\web\View::registerCssFile()`. `CraftCms\Cms\View\HtmlStack::cssFile()` should be used instead.
- Deprecated `craft\web\View::registerScript()`. `CraftCms\Cms\View\HtmlStack::script()` should be used instead.
- Deprecated `craft\web\View::registerScriptWithVars()`. `CraftCms\Cms\View\HtmlStack::scriptWithVars()` should be used instead.
- Deprecated `craft\web\View::registerHtml()`. `CraftCms\Cms\View\HtmlStack::html()` should be used instead.
- Deprecated `craft\web\View::registerMetaTag()`. `CraftCms\Cms\View\HtmlStack::metaTag()` should be used instead.
- Deprecated `craft\web\View::registerLinkTag()`. `CraftCms\Cms\View\HtmlStack::linkTag()` should be used instead.
- Deprecated `craft\web\View::registerTranslations()`. `CraftCms\Cms\View\HtmlStack::translations()` should be used instead.
- Deprecated `craft\web\View::registerJsImport()`. `CraftCms\Cms\View\HtmlStack::jsImport()` should be used instead.
- Deprecated `craft\web\View::registerIcons()`. `CraftCms\Cms\View\HtmlStack::icons()` should be used instead.
- Deprecated `craft\web\View::startJsBuffer()`. `CraftCms\Cms\View\HtmlStack::startJsBuffer()` should be used instead.
- Deprecated `craft\web\View::clearJsBuffer()`. `CraftCms\Cms\View\HtmlStack::clearJsBuffer()` should be used instead.
- Deprecated `craft\web\View::startScriptBuffer()`. `CraftCms\Cms\View\HtmlStack::startScriptBuffer()` should be used instead.
- Deprecated `craft\web\View::clearScriptBuffer()`. `CraftCms\Cms\View\HtmlStack::clearScriptBuffer()` should be used instead.
- Deprecated `craft\web\View::startCssBuffer()`. `CraftCms\Cms\View\HtmlStack::startCssBuffer()` should be used instead.
- Deprecated `craft\web\View::clearCssBuffer()`. `CraftCms\Cms\View\HtmlStack::clearCssBuffer()` should be used instead.
- Deprecated `craft\web\View::startCssFileBuffer()`. `CraftCms\Cms\View\HtmlStack::startCssFileBuffer()` should be used instead.
- Deprecated `craft\web\View::clearCssFileBuffer()`. `CraftCms\Cms\View\HtmlStack::clearCssFileBuffer()` should be used instead.
- Deprecated `craft\web\View::startJsFileBuffer()`. `CraftCms\Cms\View\HtmlStack::startJsFileBuffer()` should be used instead.
- Deprecated `craft\web\View::clearJsFileBuffer()`. `CraftCms\Cms\View\HtmlStack::clearJsFileBuffer()` should be used instead.
- Deprecated `craft\web\View::startHtmlBuffer()`. `CraftCms\Cms\View\HtmlStack::startHtmlBuffer()` should be used instead.
- Deprecated `craft\web\View::clearHtmlBuffer()`. `CraftCms\Cms\View\HtmlStack::clearHtmlBuffer()` should be used instead.
- Deprecated `craft\web\View::startMetaTagBuffer()`. `CraftCms\Cms\View\HtmlStack::startMetaTagBuffer()` should be used instead.
- Deprecated `craft\web\View::clearMetaTagBuffer()`. `CraftCms\Cms\View\HtmlStack::clearMetaTagBuffer()` should be used instead.
- Deprecated `craft\web\View::startJsImportBuffer()`. `CraftCms\Cms\View\HtmlStack::startJsImportBuffer()` should be used instead.
- Deprecated `craft\web\View::clearJsImportBuffer()`. `CraftCms\Cms\View\HtmlStack::clearJsImportBuffer()` should be used instead.
- Deprecated `craft\web\View::getNamespace()`. `CraftCms\Cms\View\InputNamespace::get()` should be used instead.
- Deprecated `craft\web\View::setNamespace()`. `CraftCms\Cms\View\InputNamespace::set()` should be used instead.
- Deprecated `craft\web\View::namespaceInputs()`. `CraftCms\Cms\View\InputNamespace::namespaceInputs()` should be used instead.
- Deprecated `craft\web\View::namespaceInputName()`. `CraftCms\Cms\View\InputNamespace::namespaceInputName()` should be used instead.
- Deprecated `craft\web\View::namespaceInputId()`. `CraftCms\Cms\View\InputNamespace::namespaceInputId()` should be used instead.
- Deprecated `craft\web\View::TEMPLATE_MODE_CP`. `CraftCms\Cms\View\TemplateMode::Cp` should be used instead.
- Deprecated `craft\web\View::TEMPLATE_MODE_SITE`. `CraftCms\Cms\View\TemplateMode::Site` should be used instead.
- Deprecated `craft\web\View::getTemplateMode()`. `CraftCms\Cms\View\TemplateMode::get()` should be used instead.
- Deprecated `craft\web\View::setTemplateMode()`. `CraftCms\Cms\View\TemplateMode::set()` should be used instead.
- Deprecated `craft\web\View::getTemplatesPath()`. `CraftCms\Cms\View\TemplateMode::templatesPath()` should be used instead.
- Deprecated `craft\web\View::getCpTemplateRoots()`. `CraftCms\Cms\View\TemplateMode::templateRoots()` should be used instead.
- Deprecated `craft\web\View::getSiteTemplateRoots()`. `CraftCms\Cms\View\TemplateMode::templateRoots()` should be used instead.
- Deprecated `craft\web\View::EVENT_REGISTER_CP_TEMPLATE_ROOTS`. `CraftCms\Cms\View\TemplateRoots::register()` should be used instead.
- Deprecated `craft\web\View::EVENT_REGISTER_SITE_TEMPLATE_ROOTS`. `CraftCms\Cms\View\TemplateRoots::register()` should be used instead.
- Deprecated `craft\web\View::registerDeltaName()`. `CraftCms\Cms\View\DeltaRegistry::registerName()` should be used instead.
- Deprecated `craft\web\View::getDeltaNames()`. `CraftCms\Cms\View\DeltaRegistry::getNames()` should be used instead.
- Deprecated `craft\web\View::getModifiedDeltaNames()`. `CraftCms\Cms\View\DeltaRegistry::getModifiedNames()` should be used instead.
- Deprecated `craft\web\View::setInitialDeltaValue()`. `CraftCms\Cms\View\DeltaRegistry::setInitialValue()` should be used instead.
- Deprecated `craft\web\View::getInitialDeltaValues()`. `CraftCms\Cms\View\DeltaRegistry::getInitialValues()` should be used instead.
- Deprecated `craft\web\View::getIsDeltaRegistrationActive()`. `CraftCms\Cms\View\DeltaRegistry::isActive()` should be used instead.
- Deprecated `craft\web\View::setIsDeltaRegistrationActive()`. `CraftCms\Cms\View\DeltaRegistry::setActive()` should be used instead.
- Deprecated `craft\web\View::hook()`. `CraftCms\Cms\View\TemplateHooks::register()` should be used instead.
- Deprecated `craft\web\View::invokeHook()`. `CraftCms\Cms\View\TemplateHooks::invoke()` should be used instead.
