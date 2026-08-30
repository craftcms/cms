# Release Notes for Craft CMS 6

## Unreleased

- Added configurable asset transformers, which can be managed from Settings → Assets → Asset Transformers and assigned to asset volumes by handle.
- Added `CraftCms\Cms\Asset\AssetTransformers` and `CraftCms\Cms\Asset\AssetTransformDrivers`.
- Added `CraftCms\Cms\Config\GeneralConfig::$defaultAssetTransformer`.
- Moved the `generateTransformsBeforePageLoad` setting to Craft Asset Transformer profiles.
- Removed per-call immediate generation arguments from the core Asset Transform APIs and GraphQL transform arguments.
- Removed the core image transformer registry, contracts, fallback transformer, and execution methods from `CraftCms\Cms\Image\ImageTransforms`. Legacy equivalents remain available through `craftcms/yii2-adapter`.
- Improved environment variable and alias settings fields to show suggestions after typing `$` or `@`, automatically bracing embedded environment variables.
- Element edit screens now autosave at the pace of the change — a keystroke waits, a discrete change saves almost immediately.
- Submitting an element edit screen now cancels any in-flight autosave, and a failed autosave reports its HTTP status.
- Element edit screens now indicate which fields a draft has unapplied changes to.
- Fixed a bug where the Control Panel loaded two copies of Lit, which could break rendering within legacy HTML controls.
- Fixed a bug where field layout changes weren’t saved on entry type settings screens.
- Fixed a bug where Typecast would throw when trying to set properties that didn’t exist. ([#19492](https://github.com/craftcms/cms/pull/19492))
- Fixed a bug where POST requests to legacy action URLs weren’t getting routed properly. ([#19478](https://github.com/craftcms/cms/issues/19478))
- Fixed a JavaScript error that occurred when creating a new Dashboard widget. ([#19479](https://github.com/craftcms/cms/issues/19479))

## 6.0.0-alpha.17 - 2026-08-18

- Improved template resource cache collection by replaying structured HTML stack entries without parsing rendered tags.
- Changed `CraftCms\Cms\Auth\Passkeys\Passkeys::verifyPasskey()` to return the updated credential record on success.
- Changed GraphQL AST value decoding to use `webonyx/graphql-php` while preserving Craft-specific query condition validation.
- Improved element queries to retain only explicitly supplied custom-field criteria.
- Improved `CraftCms\Cms\Form\FormResolver` performance by indexing control paths and node UIDs for membership checks.
- Added `CraftCms\Cms\Http\ResponseHeaders` and `CraftCms\Cms\Support\Facades\ResponseHeaders` for accumulating response headers within the current request scope.
- Improved job progress persistence by using an atomic upsert.
- Changed `CraftCms\Cms\Support\Json::decode()` to use exception-based JSON decoding.
- Changed `craft:db:drop-all-tables` to use Laravel’s schema API.
- Replaced the asset index lifecycle flags with `CraftCms\Cms\Asset\Enums\AssetIndexStatus` and explicit status transitions.
- Added `CraftCms\Cms\Plugin\Plugin::settingsForm()` for defining standard plugin settings pages with the Control Panel Form system. ([#19439](https://github.com/craftcms/cms/pull/19439))
- Removed `CraftCms\Cms\Plugin\Plugin::settingsHtml()`. `settingsForm()` should be used instead; Yii-era plugin settings HTML remains supported by `craftcms/yii2-adapter`. ([#19439](https://github.com/craftcms/cms/pull/19439))
- Safe HTML elements are now allowed within Markdown field layout elements. ([#19426](https://github.com/craftcms/cms/pull/19426))
- Added a slideout system for the Inertia/Vue Control Panel, which renders any `CpScreenResponse`-based screen as an in-page panel from a normal Inertia response, alongside the existing legacy `Craft.CpScreenSlideout`. ([#19354](https://github.com/craftcms/cms/pull/19354))
- Added `CraftCms\Cms\Http\Responses\CpScreenResponse::screenData()`. ([#19354](https://github.com/craftcms/cms/pull/19354))
- Removed the `Pane.vue` Vue component in favor of the `craft-pane` web component. ([#19398](https://github.com/craftcms/cms/pull/19398))
- Element edit screens now autosave when the form’s values actually differ from the server’s, rather than whenever a control reports a change.
- Improved structure mutation reliability by representing each pending change as a single immutable operation.
- Improved Project Config change event handler registration by keeping callbacks and ordering metadata together.
- Fixed a bug where Table field column handles became arrays after failed validation.
- Fixed a bug where nested or concurrent searches could overwrite another search’s parser state.
- Fixed a bug where cached user permission trees could become stale after permission changes or be modified by assignability filtering.
- Fixed inconsistent handling of forced-disabled plugin configuration values.
- Fixed a bug where `CraftCms\Cms\Edition` capability checks could report capabilities from the configured edition rather than the receiver.
- Fixed a bug where throwing validation could run the validation lifecycle twice.
- Fixed a bug where `CraftCms\Cms\Element\ElementCollection::with()` could pass incompatible element classes into eager loading.
- Fixed a bug where cache options and tags registered via `CraftCms\Cms\Utility\Utilities\ClearCaches::add()` and `addTag()` were unavailable as Artisan commands.
- Fixed a JavaScript error that occurred on non-Inertial pages that rendered field layout designers. ([#19380](https://github.com/craftcms/cms/discussions/19380))
- Fixed a bug where Yii asset bundles registered with `craft\web\View::registerAssetBundle()` during plugin initialization were not included in rendered pages. ([#19393](https://github.com/craftcms/cms/pull/19393))
- Fixed a bug where legacy asset bundle dependencies could be rendered after their dependent resources when using `craftcms/yii2-adapter`. ([#19394](https://github.com/craftcms/cms/pull/19394))
- Fixed an error that occurred when `config/craft/app.web.php` or `config/craft/app.console.php` was present.
- Fixed a bug where jobs run on the sync queue could remain marked as reserved after completing. ([#19431](https://github.com/craftcms/cms/pull/19431))
- Fixed a bug where Addresses fields weren’t reading the value posted by the Control Panel form, so removing every address didn’t stick and blank addresses could be created. ([#19432](https://github.com/craftcms/cms/pull/19432))
- Fixed a bug where newly-added Matrix entries and addresses showed a spinner indefinitely in the Inertia/Vue element editor, rather than their fields.
- Fixed a bug where opening an element edit page with a Money field immediately created a provisional draft, before anything had been edited.
- Fixed a bug where the `jobprogress` table was missing `dateCompleted` and `dateFailed` columns for installs that were upgraded from Craft 5.
- Fixed a bug where failed queue jobs were losing their descriptions. ([#19444](https://github.com/craftcms/cms/issues/19444))
- Fixed a bug where queue job details in the Queue Manager utility included “Error” and timestamp values even if they were null.

## 6.0.0-alpha.16 - 2026-08-05

- Fixed a bug where Yii-style migrations could be required twice. ([#19376](https://github.com/craftcms/cms/pull/19376))
- Fixed a bug where the legacy `craft\base\Widget` class wasn’t fully implementing `CraftCms\Cms\Dashboard\Contracts\WidgetInterface`. ([#19375](https://github.com/craftcms/cms/pull/19375))

## 6.0.0-alpha.15 - 2026-08-04

- Added support for Markdown-based custom Dashboard widgets in the application’s `resources/widgets/` directory. ([#19319](https://github.com/craftcms/cms/pull/19319))
- Replaced `pixelandtonic/imagine` with `intervention/image` for image manipulation.
- Added support for the libvips image driver via the optional `intervention/image-driver-vips` package.
- Added BMP, HEIC, ICO, JPEG 2000, JPEG XL, and TIFF image transform formats when supported by the active image driver.
- Added support for configuring field layout field instruction positions.
- Added fluent APIs for creating and modifying `CraftCms\Cms\FieldLayout\FieldLayout`, `CraftCms\Cms\FieldLayout\FieldLayoutTab`, and field layout elements.
- Added a renderer-neutral Control Panel Form system with shared PHP and Vue rendering, extensible Nodes and Controls, nested and refreshable scopes, changed-only submission, and integration with configurable component settings, fields, and field layouts. ([#19384](https://github.com/craftcms/cms/pull/19384))
- Changed `craft:resave:all` to discover registered `craft:resave:*` Artisan commands directly, rather than relying on a resolving event. ([#19270](https://github.com/craftcms/cms/pull/19270))
- Changed the My Account → Addresses page to a full Inertia/Vue page, rendering nested-element cards from data instead of server-rendered HTML. ([#19324](https://github.com/craftcms/cms/pull/19324))
- Changed `CraftCms\Cms\Cp\FormFields::textFromConfig()` to accept an optional `CraftCms\Cms\Cp\Components\Input` instance as a second argument, so callers can build on an existing component instead of always creating a plain `Input`. ([#19323](https://github.com/craftcms/cms/pull/19323))
- Changed `CraftCms\Cms\Search\Events\SearchPerformed` to be a readonly, immutable event; its `$results` and `$scores` properties can no longer be overridden by listeners. `CraftCms\Cms\Search\Events\SearchScoresResolving` should be used to override scores instead. ([#19308](https://github.com/craftcms/cms/pull/19308))
- Added `CraftCms\Cms\Asset\AssetFileKinds`. ([#19270](https://github.com/craftcms/cms/pull/19270))
- Added `CraftCms\Cms\Cp\Components\Button::action()`, for declarative click actions. ([#19324](https://github.com/craftcms/cms/pull/19324))
- Added `CraftCms\Cms\Cp\Components\Button::inherit()`. ([#19306](https://github.com/craftcms/cms/pull/19306))
- Added `CraftCms\Cms\Cp\Components\InputColor`. ([#19323](https://github.com/craftcms/cms/pull/19323))
- Added `CraftCms\Cms\Cp\Components\InputPassword`. ([#19323](https://github.com/craftcms/cms/pull/19323))
- Added `CraftCms\Cms\Cp\Data\NavItem::group()`. ([#19350](https://github.com/craftcms/cms/pull/19350))
- Added `CraftCms\Cms\Cp\Enums\ButtonVariant`. ([#19306](https://github.com/craftcms/cms/pull/19306))
- Added `CraftCms\Cms\Cp\FormFields::colorFromConfig()`. ([#19323](https://github.com/craftcms/cms/pull/19323))
- Added `CraftCms\Cms\Cp\FormFields::passwordFromConfig()`, `passwordHtml()`, and `passwordFieldHtml()`. ([#19323](https://github.com/craftcms/cms/pull/19323))
- Added `CraftCms\Cms\Cp\Html\ElementHtml::elementCardLabelHtml()`, `elementCardActionsHtml()`, `elementCardThumbHtml()`, and `elementCardThumbAlignment()`. ([#19324](https://github.com/craftcms/cms/pull/19324))
- Added `CraftCms\Cms\Cp\Settings::registerSetting()` and `registerReadOnlySetting()`. ([#19270](https://github.com/craftcms/cms/pull/19270))
- Added `CraftCms\Cms\Dashboard\WidgetTypes`. ([#19270](https://github.com/craftcms/cms/pull/19270))
- Added `CraftCms\Cms\Database\Commands\MigrateCommand::registerMigrator()`. ([#19270](https://github.com/craftcms/cms/pull/19270))
- Added `CraftCms\Cms\Element\ElementTypes`. ([#19270](https://github.com/craftcms/cms/pull/19270))
- Added `CraftCms\Cms\Element\NestedElementManager::getCardsData()` and `getIndexData()`. ([#19324](https://github.com/craftcms/cms/pull/19324))
- Added `CraftCms\Cms\Field\FieldTypes`. ([#19270](https://github.com/craftcms/cms/pull/19270))
- Added `CraftCms\Cms\Field\LinkTypes`. ([#19270](https://github.com/craftcms/cms/pull/19270))
- Added `CraftCms\Cms\Field\NestedEntryFieldTypes`. ([#19270](https://github.com/craftcms/cms/pull/19270))
- Added `CraftCms\Cms\FieldLayout\NativeFields`. ([#19270](https://github.com/craftcms/cms/pull/19270))
- Added `CraftCms\Cms\Filesystem\FilesystemTypes`. ([#19270](https://github.com/craftcms/cms/pull/19270))
- Added `CraftCms\Cms\Gql\GqlArguments`. ([#19270](https://github.com/craftcms/cms/pull/19270))
- Added `CraftCms\Cms\Gql\GqlDirectives`. ([#19270](https://github.com/craftcms/cms/pull/19270))
- Added `CraftCms\Cms\Gql\GqlMutations`. ([#19270](https://github.com/craftcms/cms/pull/19270))
- Added `CraftCms\Cms\Gql\GqlQueries`. ([#19270](https://github.com/craftcms/cms/pull/19270))
- Added `CraftCms\Cms\Gql\GqlTypes`. ([#19270](https://github.com/craftcms/cms/pull/19270))
- Added `CraftCms\Cms\Image\ImageTransformers`. ([#19270](https://github.com/craftcms/cms/pull/19270))
- Added `CraftCms\Cms\Image\Raster::getInterventionImage()`.
- Added `CraftCms\Cms\Plugin\Plugin::$filesystemTypes`. ([#19307](https://github.com/craftcms/cms/pull/19307))
- Added `CraftCms\Cms\Plugin\Plugin::$gqlDirectives`. ([#19307](https://github.com/craftcms/cms/pull/19307))
- Added `CraftCms\Cms\Plugin\Plugin::$gqlMutations`. ([#19307](https://github.com/craftcms/cms/pull/19307))
- Added `CraftCms\Cms\Plugin\Plugin::$gqlQueries`. ([#19307](https://github.com/craftcms/cms/pull/19307))
- Added `CraftCms\Cms\Plugin\Plugin::$gqlTypes`. ([#19307](https://github.com/craftcms/cms/pull/19307))
- Added `CraftCms\Cms\Plugin\Plugin::$linkTypes`. ([#19307](https://github.com/craftcms/cms/pull/19307))
- Added `CraftCms\Cms\Plugin\Plugin::$siteTemplateRoots`. ([#19307](https://github.com/craftcms/cms/pull/19307))
- Added `CraftCms\Cms\Plugin\Plugin::getCacheOptions()`. ([#19307](https://github.com/craftcms/cms/pull/19307))
- Added `CraftCms\Cms\Plugin\Plugin::getCacheTags()`. ([#19307](https://github.com/craftcms/cms/pull/19307))
- Added `CraftCms\Cms\Plugin\Plugin::getNativeFields()`. ([#19307](https://github.com/craftcms/cms/pull/19307))
- Added `CraftCms\Cms\Plugin\Plugin::getSystemMessages()`. ([#19307](https://github.com/craftcms/cms/pull/19307))
- Added `CraftCms\Cms\Search\Events\SearchResultsResolving`. ([#19308](https://github.com/craftcms/cms/pull/19308))
- Added `CraftCms\Cms\Search\Events\SearchScoresResolving`. ([#19308](https://github.com/craftcms/cms/pull/19308))
- Added `CraftCms\Cms\Support\Facades\AuthMethods`. ([#19270](https://github.com/craftcms/cms/pull/19270))
- Added `CraftCms\Cms\SystemMessage\SystemMessages::register()`. ([#19270](https://github.com/craftcms/cms/pull/19270))
- Added `CraftCms\Cms\Twig\Variables\Cp::color()` and `password()`. ([#19323](https://github.com/craftcms/cms/pull/19323))
- Added `CraftCms\Cms\User\UserPermissions::registerPermissionGroup()`. ([#19270](https://github.com/craftcms/cms/pull/19270))
- Added `CraftCms\Cms\Utility\Utilities\ClearCaches::add()` and `addTag()`. ([#19270](https://github.com/craftcms/cms/pull/19270))
- Added `CraftCms\Cms\Utility\UtilityTypes`. ([#19270](https://github.com/craftcms/cms/pull/19270))
- Added `CraftCms\Cms\View\TemplateCacheCollectors`. ([#19270](https://github.com/craftcms/cms/pull/19270))
- Added `CraftCms\Cms\View\TemplateRoots`. ([#19270](https://github.com/craftcms/cms/pull/19270))
- Removed `CraftCms\Cms\Support\Concerns\EvaluatesClosures` and support for closure values in fluent CP component and field layout builder APIs.
- Changed `CraftCms\Cms\FieldLayout\LayoutElements\BaseField::label()` to accept an optional label and return the field layout element when one is passed. Overrides must accept the new optional argument.
- Changed `CraftCms\Cms\Image\Raster::getTextBox()` to return a `width` and `height` array.
- Renamed the protected `CraftCms\Cms\FieldLayout\LayoutElements\BaseField::instructions()`, `tip()`, and `warning()` methods to `instructionsText()`, `tipText()`, and `warningText()`.
- Removed `CraftCms\Cms\Asset\Events\AssetFileKindsResolving`. `CraftCms\Cms\Asset\AssetFileKinds::register()` should be used instead. ([#19270](https://github.com/craftcms/cms/pull/19270))
- Removed `CraftCms\Cms\Auth\Events\AuthMethodsResolving`. `CraftCms\Cms\Auth\AuthMethods::register()` should be used instead. ([#19270](https://github.com/craftcms/cms/pull/19270))
- Removed `CraftCms\Cms\Cp\Events\RegisterCpSettings` and `CraftCms\Cms\Cp\Events\RegisterReadonlyCpSettings`. `CraftCms\Cms\Cp\Settings::registerSetting()` and `registerReadOnlySetting()` should be used instead. ([#19270](https://github.com/craftcms/cms/pull/19270))
- Removed `CraftCms\Cms\Dashboard\Events\WidgetTypesResolving`. `CraftCms\Cms\Dashboard\WidgetTypes::register()` should be used instead. ([#19270](https://github.com/craftcms/cms/pull/19270))
- Removed `CraftCms\Cms\Database\Events\MigratorsResolving`. `CraftCms\Cms\Database\Commands\MigrateCommand::registerMigrator()` should be used instead. ([#19270](https://github.com/craftcms/cms/pull/19270))
- Removed `CraftCms\Cms\Element\Events\ElementResaveCommandsResolving`. A normal Artisan command in the `craft:resave` namespace should be registered instead. ([#19270](https://github.com/craftcms/cms/pull/19270))
- Removed `CraftCms\Cms\Element\Events\ElementTypesResolving`. `CraftCms\Cms\Element\ElementTypes::register()` should be used instead. ([#19270](https://github.com/craftcms/cms/pull/19270))
- Removed `CraftCms\Cms\Field\Events\FieldTypesResolving`. `CraftCms\Cms\Field\FieldTypes::register()` should be used instead. ([#19270](https://github.com/craftcms/cms/pull/19270))
- Removed `CraftCms\Cms\Field\Events\LinkTypesResolving`. `CraftCms\Cms\Field\LinkTypes::register()` should be used instead. ([#19270](https://github.com/craftcms/cms/pull/19270))
- Removed `CraftCms\Cms\Field\Events\NestedEntryFieldTypesResolving`. `CraftCms\Cms\Field\NestedEntryFieldTypes::register()` should be used instead. ([#19270](https://github.com/craftcms/cms/pull/19270))
- Removed `CraftCms\Cms\FieldLayout\Events\NativeFieldsResolving`. `CraftCms\Cms\FieldLayout\NativeFields::register()` should be used instead. ([#19270](https://github.com/craftcms/cms/pull/19270))
- Removed `CraftCms\Cms\Filesystem\Events\FilesystemTypesResolving`. `CraftCms\Cms\Filesystem\FilesystemTypes::register()` should be used instead. ([#19270](https://github.com/craftcms/cms/pull/19270))
- Removed `CraftCms\Cms\Gql\Events\GqlArgumentHandlersResolving`. `CraftCms\Cms\Gql\GqlArguments::register()` should be used instead. ([#19270](https://github.com/craftcms/cms/pull/19270))
- Removed `CraftCms\Cms\Gql\Events\GqlDirectivesResolving`. `CraftCms\Cms\Gql\GqlDirectives::register()` should be used instead. ([#19270](https://github.com/craftcms/cms/pull/19270))
- Removed `CraftCms\Cms\Gql\Events\GqlMutationsResolving`. `CraftCms\Cms\Gql\GqlMutations::register()` should be used instead. ([#19270](https://github.com/craftcms/cms/pull/19270))
- Removed `CraftCms\Cms\Gql\Events\GqlQueriesResolving`. `CraftCms\Cms\Gql\GqlQueries::register()` should be used instead. ([#19270](https://github.com/craftcms/cms/pull/19270))
- Removed `CraftCms\Cms\Gql\Events\GqlTypesResolving`. `CraftCms\Cms\Gql\GqlTypes::register()` should be used instead. ([#19270](https://github.com/craftcms/cms/pull/19270))
- Removed `CraftCms\Cms\Image\Events\ImageTransformersResolving`. `CraftCms\Cms\Image\ImageTransformers::register()` should be used instead. ([#19270](https://github.com/craftcms/cms/pull/19270))
- Removed `CraftCms\Cms\Image\Images::MINIMUM_IMAGICK_VERSION` and `craft\services\Images::MINIMUM_IMAGICK_VERSION`.
- Removed `CraftCms\Cms\Image\Raster::getImagineImage()`.
- Removed `CraftCms\Cms\Search\Events\ScoringResults` in favor of the following new events: ([#19308](https://github.com/craftcms/cms/pull/19308))
  - `CraftCms\Cms\Search\Events\SearchResultsResolving`
  - `CraftCms\Cms\Search\Events\SearchScoresResolving`
- Removed `CraftCms\Cms\SystemMessage\Events\SystemMessagesResolving`. `CraftCms\Cms\SystemMessage\SystemMessages::register()` should be used instead. ([#19270](https://github.com/craftcms/cms/pull/19270))
- Removed `CraftCms\Cms\User\Events\UserPermissionsResolving`. `CraftCms\Cms\User\UserPermissions::registerPermissionGroup()` should be used instead. ([#19270](https://github.com/craftcms/cms/pull/19270))
- Removed `CraftCms\Cms\Utility\Events\ClearCachesOptionsResolving` and `CraftCms\Cms\Utility\Events\ClearCachesTagOptionsResolving`. `CraftCms\Cms\Utility\Utilities\ClearCaches::add()` and `addTag()` should be used instead. ([#19270](https://github.com/craftcms/cms/pull/19270))
- Removed `CraftCms\Cms\Utility\Events\UtilitiesResolving`. `CraftCms\Cms\Utility\UtilityTypes::register()` should be used instead. ([#19270](https://github.com/craftcms/cms/pull/19270))
- Removed `CraftCms\Cms\View\Events\CpTemplateRootsResolving` and `CraftCms\Cms\View\Events\SiteTemplateRootsResolving`. `CraftCms\Cms\View\TemplateRoots::register()` should be used instead. ([#19270](https://github.com/craftcms/cms/pull/19270))
- Removed `CraftCms\Cms\View\Events\TemplateCacheCollectorsResolving`. `CraftCms\Cms\View\TemplateCacheCollectors::register()` should be used instead. ([#19270](https://github.com/craftcms/cms/pull/19270))
- Added the `@craftcms/ui/factory` module, a jQuery-free layer of typed element factories that mirror the `src/Cp/Components` PHP builders. ([#19323](https://github.com/craftcms/cms/pull/19323))
- Added `createTextInput()` and `createCopyTextPrompt()` to the `@craftcms/ui/factory` module. ([#19333](https://github.com/craftcms/cms/pull/19333))
- Added `turnOn()`, `turnOff()`, and `turnIndeterminate()` methods to the `<craft-switch>` web component. ([#19323](https://github.com/craftcms/cms/pull/19323))
- Added a `group` property to the `<craft-nav-item>` web component, for rendering a subnav as a non-collapsible semantic grouping. ([#19350](https://github.com/craftcms/cms/pull/19350))
- Added `Garnish.CustomSelect` and `Garnish.MenuBtn` to `@craftcms/garnish`, jQuery-free TypeScript ports of the legacy floating listbox menu and menu-button classes. ([#19352](https://github.com/craftcms/cms/pull/19352))
- Moved the `Craft.ComponentSelectInput` control panel JavaScript class out of the core bundle into a `yii2-adapter` compatibility asset, since `<craft-component-select>` is now used everywhere in core; the `componentSelect.twig` `jsClass` escape hatch still works for plugin subclasses. ([#19333](https://github.com/craftcms/cms/pull/19333))
- Moved the `Craft.AssetMover`, `Craft.AssetSelectorModal`, `Craft.BaseElementSelectInput`, `Craft.BaseElementSelectorModal`, `Craft.BaseUploader`, `Craft.Chart`, `Craft.CpModal`, `Craft.CustomizeSourcesModal`, `Craft.DataTableSorter`, `Craft.ElementActionTrigger`, `Craft.ElementDeletionManager`, `Craft.ElementTableSorter`, `Craft.EntrySelectInput`, `Craft.Grid`, `Craft.PreviewFileModal`, `Craft.Tabs`, `Craft.TagSelectInput`, `Craft.Uploader`, and `Craft.VolumeFolderSelectorModal` control panel JavaScript classes from the legacy jQuery bundle to TypeScript modules. ([#19352](https://github.com/craftcms/cms/pull/19352))
- Changed `<craft-nav-item>` to render as a `<span>` instead of an `<a>` when it has no `href`, dropping `aria-current` in that case. ([#19350](https://github.com/craftcms/cms/pull/19350))
- Changed element index table rows and cards so clicking anywhere on them (other than an interactive control) selects them, extending the selection range on shift-click just like clicking a row’s checkbox. ([#19351](https://github.com/craftcms/cms/pull/19351))
- Deprecated the `Craft.LightSwitch`, `Craft.InfoIcon`, `Craft.ColorInput`, `Craft.PasswordInput`, `Craft.IconPicker`, `Craft.SlidePicker`, `Craft.SlideRuleInput`, and `Craft.Tooltip` control panel JavaScript classes, along with the `.infoicon` jQuery plugin. The corresponding `@craftcms/ui` web components should be used instead. ([#19323](https://github.com/craftcms/cms/pull/19323))
- Removed the `Craft.Accordion` and `Craft.EnvVarGenerator` control panel JavaScript classes. ([#19323](https://github.com/craftcms/cms/pull/19323))
- Removed the `Craft.DeleteUserModal` control panel JavaScript class. It was deprecated in 5.10.0 and unused. ([#19352](https://github.com/craftcms/cms/pull/19352))
- Fixed a bug where Blade templates rendered through Craft used path-based view names, preventing named Laravel view composers from running. ([#19177](https://github.com/craftcms/cms/issues/19177))
- Fixed a bug where the `accent` semantic color used by colorable elements (e.g. `craft-callout`, `[data-color]`) rendered red instead of blue, due to a drifted color mapping in `@craftcms/ui`. ([#19306](https://github.com/craftcms/cms/pull/19306))
- Fixed a styling issue. ([#19296](https://github.com/craftcms/cms/pull/19296))
- Fixed a bug where Yii adapter plugins could cause legacy Control Panel assets to be omitted. ([#19302](https://github.com/craftcms/cms/pull/19302))
- Fixed a bug where assets’ Alternative Text values could not be cleared. ([#19310](https://github.com/craftcms/cms/issues/19310))
- Fixed a bug where element deletion confirmation dialogs could display unresolved pluralization syntax. ([#19311](https://github.com/craftcms/cms/issues/19311))
- Fixed a bug where replacing an asset would fail silently. ([#19312](https://github.com/craftcms/cms/issues/19312))
- Fixed JavaScript errors that could occur throughout the control panel. ([#19313](https://github.com/craftcms/cms/issues/19313))
- Fixed a bug where `forms.checkboxField()` and `CraftCms\Cms\Cp\FormFields::checkboxFieldHtml()` rendered an empty field. ([#19338](https://github.com/craftcms/cms/pull/19338))
- Fixed a bug where Utility pages weren’t rendering, and were logging `$ is not defined` and `window.Cp.config is not a function` errors to the console. ([#19340](https://github.com/craftcms/cms/pull/19340))
- Fixed a bug where `actionClient` requests for bare action paths could corrupt the `?site=` query string on multi-site installs. ([#19342](https://github.com/craftcms/cms/pull/19342))
- `Craft.cp.announce()` now accepts live regions that are plain elements as well as jQuery collections. ([#19340](https://github.com/craftcms/cms/pull/19340))

## 6.0.0-alpha.14 - 2026-07-22

> [!IMPORTANT]
> This update contains breaking changes for plugins. See [#19263](https://github.com/craftcms/cms/pull/19263) for details.

- Plugins should no longer define `extra.laravel.providers` in `composer.json`. ([#19263](https://github.com/craftcms/cms/pull/19263))
- Removed automatic plugin trait lifecycle hooks. ([#19263](https://github.com/craftcms/cms/pull/19263))
- Added `CraftCms\Cms\Cp\Components\Button`. ([#19248](https://github.com/craftcms/cms/pull/19248))
- Added `CraftCms\Cms\Cp\Components\ButtonGroup`. ([#19248](https://github.com/craftcms/cms/pull/19248))
- Added `CraftCms\Cms\Cp\Components\Callout::hideIcon()`. ([#19297](https://github.com/craftcms/cms/pull/19297))
- Added `CraftCms\Cms\Cp\Components\Callout`. ([#19248](https://github.com/craftcms/cms/pull/19248))
- Added `CraftCms\Cms\Cp\Components\Checkbox`. ([#19248](https://github.com/craftcms/cms/pull/19248))
- Added `CraftCms\Cms\Cp\Components\CheckboxGroup`. ([#19248](https://github.com/craftcms/cms/pull/19248))
- Added `CraftCms\Cms\Cp\Components\CheckboxSelect`. ([#19248](https://github.com/craftcms/cms/pull/19248))
- Added `CraftCms\Cms\Cp\Components\ChoiceGroup`. ([#19248](https://github.com/craftcms/cms/pull/19248))
- Added `CraftCms\Cms\Cp\Components\ComponentRegistry`. ([#19248](https://github.com/craftcms/cms/pull/19248))
- Added `CraftCms\Cms\Cp\Components\Field`. ([#19248](https://github.com/craftcms/cms/pull/19248))
- Added `CraftCms\Cms\Cp\Components\FieldGroup`. ([#19248](https://github.com/craftcms/cms/pull/19248))
- Added `CraftCms\Cms\Cp\Components\Icon`. ([#19297](https://github.com/craftcms/cms/pull/19297))
- Added `CraftCms\Cms\Cp\Components\Input`. ([#19297](https://github.com/craftcms/cms/pull/19297))
- Added `CraftCms\Cms\Cp\Components\Lightswitch`. ([#19248](https://github.com/craftcms/cms/pull/19248))
- Added `CraftCms\Cms\Cp\Components\Radio`. ([#19248](https://github.com/craftcms/cms/pull/19248))
- Added `CraftCms\Cms\Cp\Components\RadioGroup`. ([#19248](https://github.com/craftcms/cms/pull/19248))
- Added `CraftCms\Cms\Cp\Components\Textarea`. ([#19297](https://github.com/craftcms/cms/pull/19297))
- Added `CraftCms\Cms\Cp\Components\ViewComponent`. ([#19248](https://github.com/craftcms/cms/pull/19248))
- Added `CraftCms\Cms\Cp\Concerns\EvaluatesClosures`. ([#19248](https://github.com/craftcms/cms/pull/19248))
- Added `CraftCms\Cms\Cp\Concerns\HasAppearance`. ([#19248](https://github.com/craftcms/cms/pull/19248))
- Added `CraftCms\Cms\Cp\Concerns\HasDisabled`. ([#19248](https://github.com/craftcms/cms/pull/19248))
- Added `CraftCms\Cms\Cp\Concerns\HasId`. ([#19248](https://github.com/craftcms/cms/pull/19248))
- Added `CraftCms\Cms\Cp\Concerns\HasSize`. ([#19248](https://github.com/craftcms/cms/pull/19248))
- Added `CraftCms\Cms\Cp\Concerns\HasVariant`. ([#19248](https://github.com/craftcms/cms/pull/19248))
- Added `CraftCms\Cms\Cp\Enums\Appearance`. ([#19248](https://github.com/craftcms/cms/pull/19248))
- Added `CraftCms\Cms\Cp\Enums\Size`. ([#19248](https://github.com/craftcms/cms/pull/19248))
- Added `CraftCms\Cms\Cp\Enums\Variant`. ([#19248](https://github.com/craftcms/cms/pull/19248))
- Added `CraftCms\Cms\Cp\FormFields::buttonFromConfig()`. ([#19248](https://github.com/craftcms/cms/pull/19248))
- Added `CraftCms\Cms\Cp\FormFields::buttonGroupFromConfig()`. ([#19248](https://github.com/craftcms/cms/pull/19248))
- Added `CraftCms\Cms\Cp\FormFields::checkboxFromConfig()`. ([#19248](https://github.com/craftcms/cms/pull/19248))
- Added `CraftCms\Cms\Cp\FormFields::checkboxGroupFromConfig()`. ([#19248](https://github.com/craftcms/cms/pull/19248))
- Added `CraftCms\Cms\Cp\FormFields::checkboxSelectFromConfig()`. ([#19248](https://github.com/craftcms/cms/pull/19248))
- Added `CraftCms\Cms\Cp\FormFields::lightswitchFromConfig()`. ([#19248](https://github.com/craftcms/cms/pull/19248))
- Added `CraftCms\Cms\Cp\FormFields::radioFromConfig()`. ([#19248](https://github.com/craftcms/cms/pull/19248))
- Added `CraftCms\Cms\Cp\FormFields::radioGroupFieldHtml()`. ([#19248](https://github.com/craftcms/cms/pull/19248))
- Added `CraftCms\Cms\Cp\FormFields::radioGroupFromConfig()`. ([#19248](https://github.com/craftcms/cms/pull/19248))
- Added `CraftCms\Cms\Cp\FormFields::textareaFromConfig()`. ([#19297](https://github.com/craftcms/cms/pull/19297))
- Added `CraftCms\Cms\Cp\FormFields::textFromConfig()`. ([#19297](https://github.com/craftcms/cms/pull/19297))
- Added `CraftCms\Cms\Support\Facades\Template`. ([#19290](https://github.com/craftcms/cms/pull/19290))
- Added `CraftCms\Cms\Twig\Contracts\TwigRendererInterface`. ([#19290](https://github.com/craftcms/cms/pull/19290))
- Added `CraftCms\Cms\Twig\Variables\Cp::button()`. ([#19248](https://github.com/craftcms/cms/pull/19248))
- Added `CraftCms\Cms\Twig\Variables\Cp::buttonGroup()`. ([#19248](https://github.com/craftcms/cms/pull/19248))
- Added `CraftCms\Cms\Twig\Variables\Cp::checkbox()`. ([#19248](https://github.com/craftcms/cms/pull/19248))
- Added `CraftCms\Cms\Twig\Variables\Cp::checkboxGroup()`. ([#19248](https://github.com/craftcms/cms/pull/19248))
- Added `CraftCms\Cms\Twig\Variables\Cp::checkboxSelect()`. ([#19248](https://github.com/craftcms/cms/pull/19248))
- Added `CraftCms\Cms\Twig\Variables\Cp::lightswitch()`. ([#19248](https://github.com/craftcms/cms/pull/19248))
- Added `CraftCms\Cms\Twig\Variables\Cp::radio()`. ([#19248](https://github.com/craftcms/cms/pull/19248))
- Added `CraftCms\Cms\Twig\Variables\Cp::radioGroup()`. ([#19248](https://github.com/craftcms/cms/pull/19248))
- Added `CraftCms\Cms\Twig\Variables\Cp::text()`. ([#19297](https://github.com/craftcms/cms/pull/19297))
- Added `CraftCms\Cms\Twig\Variables\Cp::textarea()`. ([#19297](https://github.com/craftcms/cms/pull/19297))
- Added `CraftCms\Cms\ui()`. ([#19248](https://github.com/craftcms/cms/pull/19248))
- Added `CraftCms\Cms\View\TemplateManager`. ([#19290](https://github.com/craftcms/cms/pull/19290))
- `template()` and `pageTemplate()` now accept an optional template renderer name. ([#19290](https://github.com/craftcms/cms/pull/19290))
- `TemplateRendered` and `PageTemplateRendered` events now expose the final renderer name via `$rendererName`; the corresponding before events no longer expose renderer identity. ([#19290](https://github.com/craftcms/cms/pull/19290))
- Replaced `CraftCms\Cms\Support\HtmlSanitizer\HtmlSanitizers` with `CraftCms\Cms\Support\HtmlSanitizer\HtmlSanitizerManager`. HTML sanitizers should now be registered via `CraftCms\Cms\Support\Facades\HtmlSanitizers::extend()` rather than `CraftCms\Cms\Support\HtmlSanitizer\HtmlSanitizers::register()`. ([#19292](https://github.com/craftcms/cms/pull/19292))
- Removed `CraftCms\Cms\Plugin\Events\PluginUnregistered`. ([#19263](https://github.com/craftcms/cms/pull/19263))
- Removed `CraftCms\Cms\Plugin\Plugin::bootPlugin()`. `boot()` should be used instead. ([#19263](https://github.com/craftcms/cms/pull/19263))
- Removed `CraftCms\Cms\Plugin\Plugin::registerPlugin()`. `register()` should be used instead. ([#19263](https://github.com/craftcms/cms/pull/19263))
- Fixed a bug where the legacy `yii\web\JqueryAsset` wasn’t resolving properly. ([#19264](https://github.com/craftcms/cms/pull/19264))
- Fixed a bug where bulk entry moves could assign entries to sections that didn’t support their entry types. ([#19267](https://github.com/craftcms/cms/pull/19267))
- Fixed a bug where queued resaves ignored offset and limit criteria or accepted non-positive batch sizes. ([#19271](https://github.com/craftcms/cms/pull/19271))
- Fixed a bug where failed password and passkey login attempts incremented invalid-login counters twice. ([#19283](https://github.com/craftcms/cms/pull/19283))
- Fixed bugs that could prevent authored content from being deleted or reassigned safely when deleting users. ([#19273](https://github.com/craftcms/cms/pull/19273))
- Fixed an issue where disabled and archived user accounts could still authenticate. ([#19265](https://github.com/craftcms/cms/pull/19265))
- Fixed a bug where one-time two-factor authentication credentials could be accepted by concurrent login attempts. ([#19282](https://github.com/craftcms/cms/pull/19282))
- Fixed issues with `craft:users:set-password` password validation, exit statuses, and session invalidation. ([#19272](https://github.com/craftcms/cms/pull/19272))
- Fixed a bug where selected GraphQL mutations could be mistaken for cacheable queries. ([#19284](https://github.com/craftcms/cms/pull/19284))
- Fixed a [high-severity](https://github.com/craftcms/cms/security/policy#severity--remediation) authorization bypass vulnerability.
- Fixed a bug where two-factor authentication could lose login state or verify the wrong user during impersonation. ([#19274](https://github.com/craftcms/cms/pull/19274))
- Fixed a bug where new Matrix blocks weren’t getting created. ([#19161](https://github.com/craftcms/cms/issues/19161))

## 6.0.0-alpha.13 - 2026-07-16

- Updated logout routes to require CSRF-protected POST requests. ([#19252](https://github.com/craftcms/cms/pull/19252/changes/ff6f20717a48e7ede5fbbe47487e7bb8ef71da78))
- Updated Control Panel configuration serialization to use `Illuminate\Support\Js::from()`. ([#19252](https://github.com/craftcms/cms/pull/19252/changes/f844ae1cc3b02659ff68eb320ff7d2190a2a01da))
- Updated the project testing guidelines to emphasize behavior-focused tests. ([#19252](https://github.com/craftcms/cms/pull/19252/changes/63b452d4b82d066566ad653ea39f6c1ad949eaa3))
- Updated queue connection retry windows to exceed Craft’s maximum job timeout. ([#19252](https://github.com/craftcms/cms/pull/19252/changes/21bff956e964679a3f9951545bdf059473e0e5e8))
- Updated core queue jobs to resolve their dependencies through Laravel’s service container. ([#19252](https://github.com/craftcms/cms/pull/19252/changes/dced32fc03120f27bc82ca61a9d960b95786ee03))
- Updated automatic garbage collection to run as a unique queue job. ([#19252](https://github.com/craftcms/cms/pull/19252/changes/a35f815f3d9096311fc891b33c31427af865d902))
- Updated two-factor authentication recovery codes to be encrypted at rest. ([#19252](https://github.com/craftcms/cms/pull/19252/changes/fb560d68a1d4f3a4e02b360c5bcacde27780fa05))
- Improved field reference cleanup performance. ([#19252](https://github.com/craftcms/cms/pull/19252/changes/303054c48d45f33d43c6781664729fd01a6130f3))
- Improved numeric element reference resolution performance. ([#19252](https://github.com/craftcms/cms/pull/19252/changes/08e882820aa0681e55c346f57102dfb1792e142c))
- Improved element merge performance by avoiding per-relation and per-structure queries. ([#19252](https://github.com/craftcms/cms/pull/19252/changes/202c2f6782ac59d15cf8cbda32ab4304807b0407))
- Improved search indexing memory use when indexing many elements. ([#19252](https://github.com/craftcms/cms/pull/19252/changes/117c2c5f222eec8acb2371937928a364a52c0fb0))
- Improved asset indexing performance when finding missing and empty folders. ([#19252](https://github.com/craftcms/cms/pull/19252/changes/fe4974ab449f2e0402b333cf77282d04f4ee5558))
- Prevented duplicate image transform jobs from being queued for the same transform. ([#19252](https://github.com/craftcms/cms/pull/19252/changes/059ccbed6ccd41c12dea1a5eb806f281e5878290))
- Removed duplicate Control Panel icon alias registrations. ([#19252](https://github.com/craftcms/cms/pull/19252/changes/e8acf264f2bad6fd4c7c0f3de742ad85f89cdbbb))
- Fixed a bug where GraphQL asset mutations weren’t executing HTTP requests for remote asset URLs. ([#19252](https://github.com/craftcms/cms/pull/19252/changes/35e54924d7404ce21a591ece04a970afc5a3ae80))
- Fixed a bug where failed relation writes could leave partial relation changes persisted. ([#19252](https://github.com/craftcms/cms/pull/19252/changes/4f466d0730e266ea347a069807be6bc7a61e3afc))
- Fixed a bug where element merge replacement jobs could be dispatched before their database transaction was committed. ([#19252](https://github.com/craftcms/cms/pull/19252/changes/a50f8cb6fe6f037d7fc95a3b7305fb09a57759ff))
- Fixed a bug where section project-config jobs could be dispatched before their database transaction was committed. ([#19252](https://github.com/craftcms/cms/pull/19252/changes/3b54bc28253cade054a10a879252a8d102571a33))
- Fixed a bug where passkey login attempts weren’t rate limited. ([#19252](https://github.com/craftcms/cms/pull/19252/changes/e6f7deca8eaba4c056b27358bb9b0b89b96e0cc5))
- Fixed a bug where relation localization could not be retried safely after a failed run. ([#19252](https://github.com/craftcms/cms/pull/19252/changes/915b8196d920cc1d27d352e1fe773a921a258e46))
- Fixed a bug where project config mutex cleanup could release a lock owned by another process. ([#19252](https://github.com/craftcms/cms/pull/19252/changes/845e2795c5158dfe1249d7543331e2a163e9dc3f))
- Fixed a bug where nested structure operations could release their mutex lock before the outer operation completed. ([#19252](https://github.com/craftcms/cms/pull/19252/changes/9f5157ae9745a4677117cdfeb0051a82b8af916b))
- Fixed a bug where two-factor authentication verification attempts weren’t rate limited. ([#19252](https://github.com/craftcms/cms/pull/19252/changes/f4f11c8ef465fa981ee32d2c1ed84ea8b8e8d13e))
- Fixed a bug where user photo uploads could exceed the configured maximum upload size. ([#19252](https://github.com/craftcms/cms/pull/19252/changes/fc3fa25f5e7c9108b9e465b1d93ea5d116cf3e08))
- Fixed a bug where unsafe filenames could be used for Craft support attachments. ([#19252](https://github.com/craftcms/cms/pull/19252/changes/2872b891eca9d243901e186b6bba76150fc0d16e))
- Fixed a bug where a failed legacy field type migration could leave project config events muted. ([#19252](https://github.com/craftcms/cms/pull/19252/changes/0b22858254f9d6ef54e1f4aaca4de055a1c7f388))
- Fixed a bug where TemplateGlobals were being resolved every time a Blade component rendered. ([#19257](https://github.com/craftcms/cms/pull/19257))
- Fixed a bug where Craft updates could fail with a 503 response when the action URL contained query parameters.

## 6.0.0-alpha.12 - 2026-07-15

- Added `Illuminate\Contracts\Translation\HasLocalePreference` support to user elements, allowing Laravel notifications to use users’ Language preferences. ([#19228](https://github.com/craftcms/cms/pull/19228))
- Login attempts are now rate limited.
- Updated core asset I/O to resolve Craft filesystem definitions and configured storage targets through Laravel filesystem disks.
- Updated elevated session prompts to use the modern control panel frontend while preserving the legacy JavaScript APIs.
- Fixed a bug where site routes weren’t being registered for each localized site value.
- Fixed a bug where POST requests to the `loginPath` weren’t being handled properly. ([#19220](https://github.com/craftcms/cms/pull/19220))
- Fixed a bug where users were redirected to the previous page on logout. ([#19220](https://github.com/craftcms/cms/pull/19220))
- Fixed a bug where requests to the `loginPath`, `setPasswordPath`, and `verifyEmailPath` were getting redirected to the control panel. ([#19229](https://github.com/craftcms/cms/pull/19229))
- Fixed a bug where Laravel translation fallbacks weren’t applied when `translationDebugOutput` was enabled. ([#19228](https://github.com/craftcms/cms/pull/19228))
- Fixed a bug where custom plugin settings `FormRequest` classes could persist unvalidated settings. ([#19228](https://github.com/craftcms/cms/pull/19228))
- Fixed a bug where failed Craft API responses weren’t processing response headers. ([#19228](https://github.com/craftcms/cms/pull/19228))
- Fixed a bug where Craft plugin service providers could be skipped when the plugin’s Composer metadata also defined Laravel package discovery settings. ([#19228](https://github.com/craftcms/cms/pull/19228))
- Fixed a bug where event-registered user permissions weren’t getting saved. ([#19232](https://github.com/craftcms/cms/issues/19232))
- Fixed lifecycle leaks where asset, GraphQL, route token, user, and user permission state could persist in long-running application processes. ([#19242](https://github.com/craftcms/cms/pull/19242))
- Fixed [moderate-severity](https://github.com/craftcms/cms/security/policy#severity--remediation) authorization bypass vulnerabilities.
- Fixed a [low-severity](https://github.com/craftcms/cms/security/policy#severity--remediation) authorization bypass vulnerability.
- Fixed a [low-severity](https://github.com/craftcms/cms/security/policy#severity--remediation) XSS vulnerability.

## 6.0.0-alpha.11 - 2026-07-07

- Users can now connect their accounts to one or more Socialite providers. ([#19202](https://github.com/craftcms/cms/pull/19202))
- The login page now lists Socialite providers. ([#19202](https://github.com/craftcms/cms/pull/19202))
- Fixed a bug where some control panel resources and pages weren’t loading properly. ([#19214](https://github.com/craftcms/cms/issues/19214))

## 6.0.0-alpha.10 - 2026-07-03

- It’s now possible to load Blade views within Twig templates. ([#19148](https://github.com/craftcms/cms/pull/19148))
- Craft now registers several Blade directives, bringing near feature parity with Twig templating. ([#19148](https://github.com/craftcms/cms/pull/19148))
- Added SQLite database support. ([#19149](https://github.com/craftcms/cms/pull/19149))
- Control panel resources are now provided by a `craftcms/cms-assets` package. ([#19162](https://github.com/craftcms/cms/pull/19162))
- Renamed `CraftCms\Cms\Twig\PageLifecycle` to `CraftCms\Cms\View\PageLifecycle`. ([#19148](https://github.com/craftcms/cms/pull/19148))
- Renamed `CraftCms\Cms\Twig\TemplateResolver` to `CraftCms\Cms\View\TemplateResolver`. ([#19148](https://github.com/craftcms/cms/pull/19148))
- Fixed a bug where anonymous homepage and fallback site-template requests could bypass offline-site access enforcement. ([#19151](https://github.com/craftcms/cms/pull/19151))
- Fixed a bug where the login page would show a CSRF token mismatch on http requests
- Fixed an error that could occur during Craft 6 upgrades when the `migrations` table was missing its `track` column. ([#19168](https://github.com/craftcms/cms/pull/19168))
- Fixed an error that occurred when saving Users source settings after selecting the Groups table column. ([#19184](https://github.com/craftcms/cms/pull/19184))
- Fixed a bug where database backups weren’t using the `--single-transaction` or `--column-statistics=0` flags on MySQL.
- Fixed a bug where Content Block fields’ nested fields weren’t available as table columns on element indexes.

## 6.0.0-alpha.9 - 2026-06-23

- Added `CraftCms\Yii2Adapter\Database\DeprecatedTable`.
- Added `CraftCms\Cms\Translation\Formatter::asRelativeTime()`. ([#19146](https://github.com/craftcms/cms/pull/19146))
- Updated `webonyx/graphql-php` to 15.33.1. ([#18757](https://github.com/craftcms/cms/pull/18757))
- `craft\elements\Category::find()` now returns a `CraftCms\Yii2Adapter\Element\Queries\CategoryQuery` object. ([#19120](https://github.com/craftcms/cms/pull/19120))
- `craft\elements\GlobalSet::find()` now returns a `CraftCms\Yii2Adapter\Element\Queries\GlobalSetQuery` object. ([#19120](https://github.com/craftcms/cms/pull/19120))
- `craft\elements\Tag::find()` now returns a `CraftCms\Yii2Adapter\Element\Queries\TagQuery` object. ([#19120](https://github.com/craftcms/cms/pull/19120))
- Fixed a “This password does not use the Bcrypt algorithm” error that could occur when logging in with a user whose password was set in an earlier version of Craft.
- Fixed a “File name is not a string” error that could occur when an error was encountered when rendering a string template. ([#19122](https://github.com/craftcms/cms/pull/19122))
- Fixed a bug where parsed site names would get saved to the project config. ([#19123](https://github.com/craftcms/cms/issues/19123))
- Fixed a bug where plugin `$styles`, `$scripts`, and `$publishables` weren’t published automatically when the plugin was installed or enabled. ([#19137](https://github.com/craftcms/cms/pull/19137))
- Fixed several issues that occurred when Craft was configured with a custom (or no) `cpTrigger`. ([#19127](https://github.com/craftcms/cms/pull/19127))
- Fixed a bug where Craft wasn’t applying the Settings → General timezone to PHP’s default timezone. ([#19138](https://github.com/craftcms/cms/pull/19138))
- Fixed a bug where entry queries weren’t fetching structure data by default.
- Fixed a bug where top-level structure elements were always repositioned to the end of the structure on save.
- Fixed a bug where the Settings index page didn’t include “Globals”, “Categories”, or “Tags” links, when the concepts were supported. ([#19120](https://github.com/craftcms/cms/pull/19120))
- Fixed errors that occurred when editing global sets, category groups, and tag groups. ([#19120](https://github.com/craftcms/cms/pull/19120))
- Fixed a bug where it wasn’t possible to create new categories. ([#19120](https://github.com/craftcms/cms/pull/19120))
- Fixed an error that occurred when editing a category. ([#19120](https://github.com/craftcms/cms/pull/19120))
- Fixed a bug where users’ Language preference field could be set to Arabic by default when the browser’s preferred language included a territory ID (e.g. `en-US`).
- Fixed a bug where preview tokens weren’t taking the `previewTokenDuration` config setting into account.
- Fixed a bug where success/failure notifications weren’t being shown after deleting elements. ([#19028](https://github.com/craftcms/cms/pull/19028))

## 6.0.0-alpha.8 - 2026-06-17

- Added `CraftCms\Cms\Twig\AllowableInSandbox`.
- Fixed a bug where Blade templates weren’t loading for text/Markdown mail. ([#19106](https://github.com/craftcms/cms/pull/19106))
- Fixed a bug where it wasn’t possible to change the primary site, or edit site statuses. ([#19109](https://github.com/craftcms/cms/issues/19109))

## 6.0.0-alpha.7 - 2026-06-16

- Added a new core Markdown field ([#18960](https://github.com/craftcms/cms/pull/18960))
- Added a way for fields to track references and register a deletion blocker for them ([#19014](https://github.com/craftcms/cms/pull/19014))
- Added `CraftCms\Cms\Validation\Events\ValidationRulesResolving::$ruleset`.
- Relaxed the allowed types in the `ValidationRulesResolving` event to include any implementation `ValidatesWithRuleset` or a `Illuminate\Http\Request` object.
- Renamed `CraftCms\Cms\Validation\Events\ValidationRulesResolving::$component` to `$subject`.
- Relocated `CraftCms\Cms\Element\Validation\Events\ValidationRulesResolving` to `CraftCms\Cms\Validation\Events\` to reflect its broader applicability to components and rulesets.
- Fixed errors that could occur when Craft user elements were expected but the authenticated user was resolved as a Laravel user model. ([#19051](https://github.com/craftcms/cms/pull/19051))
- Fixed a bug where the `craft:install` command would hang if run within a production environment.
- Fixed an error that could occur when `CraftCms\Cms\Support\DateTimeHelper::toDateTime()` returned a `DateTimeInterface` implementation other than `DateTime`. ([#19079](https://github.com/craftcms/cms/pull/19079))
- Fixed a bug where “Replace relation” action buttons weren’t working.
- Fixed a “Invalid URL” JavaScript error in the control panel. ([#19041](https://github.com/craftcms/cms/pull/19041))
- Fixed an error that could occur during Craft 6 upgrades when legacy relational or Matrix field settings included `showCardsInGrid`. ([#19047](https://github.com/craftcms/cms/pull/19047))
- Fixed a bug where queue job progress labels weren’t getting translated.
- Fixed a bug where the control panel sidebar and Queue Manager were showing completed jobs.
- Fixed a bug where `CraftCms\Yii2Adapter\Mixins\ValidateMixin::addErrors()` had incorrect arguments. ([#19065](https://github.com/craftcms/cms/pull/19065))
- Fixed a bug where plugin templates were not being loaded correctly
- Fixed a bug where query string params were getting registered as variables in Twig templates. ([#19090](https://github.com/craftcms/cms/discussions/19090))
- Fixed a bug where parsed site URLs would get saved to the project config. ([#19092](https://github.com/craftcms/cms/issues/19092))

## 6.0.0-alpha.6 - 2026-06-03

- Improved the accessibility of the Login page. ([#19025](https://github.com/craftcms/cms/pull/19025))
- Added `CraftCms\Cms\User\Contracts\CraftUser` and `CraftUserTrait`. ([#19009](https://github.com/craftcms/cms/pull/19009))
- Removed `CraftCms\Cms\Auth\UserProvider`; the Craft guard now defaults to Laravel’s Eloquent provider using `CraftCms\Cms\User\Models\User`. ([#19009](https://github.com/craftcms/cms/pull/19009))
- Added `\CraftCms\Cms\craftUser()`/`\CraftCms\Cms\craftUser()` and `request()->craftUser()` as Craft-safe ways to access the authenticated user. ([#19009](https://github.com/craftcms/cms/pull/19009))
- `Element::getIterator()` no longer includes custom field values. ([#19004](https://github.com/craftcms/cms/issues/19004))
- Fixed a bug where checking the elevated session timeout could overwrite newer session data, which could prevent passkeys from being created.
- Fixed a bug where legacy plugin-defined `actions.php` routes could collide between plugins. ([#18994](https://github.com/craftcms/cms/pull/18994))
- Fixed a bug where JavaScript and CSS registered by utility pages weren’t executed when navigating between utility pages, and weren’t cleaned up when navigating away. ([#18978](https://github.com/craftcms/cms/issues/18978))
- Fixed a bug where custom element authorization methods weren’t respected by Laravel element policies. ([#18983](https://github.com/craftcms/cms/pull/18983))
- Fixed a bug where removing all permissions from a user wouldn’t save. ([#18995](https://github.com/craftcms/cms/pull/18995))
- Fixed a bug where Single sections had Max Authors settings. ([#19001](https://github.com/craftcms/cms/pull/19001))
- Fixed a bug where Channel and Structure sections didn’t have Max Authors settings. ([#19001](https://github.com/craftcms/cms/pull/19001))
- Fixed a bug where sections’ Min Authors settings were defaulting to `1` when blank. ([#19001](https://github.com/craftcms/cms/pull/19001))
- Fixes a bug where the “View entry” permission was listed twice for Single sections, causing a SQL error when both were selected. ([#19002](https://github.com/craftcms/cms/pull/19002))
- Fixes a bug where user group handles weren’t getting auto-generated. ([#19002](https://github.com/craftcms/cms/pull/19002))
- Fixed a JavaScript error that could occur in the Control Panel when a custom element was registered more than once.
- Fixed a bug where Control Panel action menu items could trigger their action twice when clicked.
- Fixed a bug where legacy Control Panel JavaScript wasn’t loaded and initialized on all Control Panel pages.
- Fixed a styling issue with user avatars.

## 6.0.0-alpha.5 - 2026-05-27

- Improved emoji shortcode handling performance for strings without shortcode delimiters.
- Improved element query performance by caching element source table column listings in memory.
- Improved nested entry type resolution by avoiding unnecessary owner element queries.
- Added Laravel event dispatching to Craft’s `Yiisoft\Translator\Translator` instance, enabling `Yiisoft\Translator\Event\MissingTranslationEvent` listeners. ([#18952](https://github.com/craftcms/cms/pull/18952))
- The `loginPath` config setting is now `false` by default.
- Renamed the `PluginsLoaded` event to `PluginsRegistered`. ([#18973](https://github.com/craftcms/cms/pull/18973))
- Updated Twig to 3.27. ([#18980](https://github.com/craftcms/cms/pull/18980))
- Fixed some errors that could occur when running Craft through Laravel Octane ([#18921](https://github.com/craftcms/cms/pull/18921))
- Fixed an error that occurred when rendering the database update screen outside Control Panel template mode.
- Fixed an error that occurred when Redis was configured as the session driver.
- Fixed a bug where legacy Control Panel URL rules couldn’t route directly to templates. ([#18972](https://github.com/craftcms/cms/pull/18972))
- Fixed an error that could occur when request context was dehydrated after a matched element route was resolved.
- Fixed a bug where `CraftCms\Cms\Support\Typecast` could skip setters that used a same-name private backing property.
- Fixed a bug where `CraftCms\Cms\Support\Typecast` could attempt to assign read-only, private-set, protected-set, or setterless virtual properties.
- Fixed a bug where publishable Craft assets were registered during web requests.
- Fixed a bug where eager-loading didn’t treat address, content block, and entry queries as nested element queries.
- Fixed a bug where lazy eager-loading nested element fields could reuse owner criteria and return the wrong elements.
- Fixed an error that occurred when Updates were cached and deserialized.
- Fixed an error that prevented link fields from saving.
- Fixed a bug where Money fields could throw an error during element validation when the field value was falsy.
- Fixed a bug where `CraftCms\Cms\Validation\Contracts\Validatable::prepareForValidation()` wasn’t called consistently, and plain `Validatable` classes without a configured ruleset couldn’t be validated. ([#18944](https://github.com/craftcms/cms/pull/18944))
- Fixed a bug where invalid element query filters could return all results. ([#18937](https://github.com/craftcms/cms/pull/18937))
- Fixed an error that occurred when uploading assets to fields with dynamic default upload locations. ([#18949](https://github.com/craftcms/cms/pull/18949))
- Fixed a bug where Craft could look for the license key in `config/license.key` instead of `config/craft/license.key`.
- Fixed a styling issue that occurred when editable table cells had a `code` class. ([#18900](https://github.com/craftcms/cms/issues/18900))

## 6.0.0-alpha.4 - 2026-05-19

- Added support for plugins to register Laravel scheduled tasks that run via `php artisan schedule:run`.
- Updated `yiisoft/html` to 4.1.0. ([#18920](https://github.com/craftcms/cms/pull/18920))
- Updated `elvanto/litemoji` to 5.2.0. ([#18917](https://github.com/craftcms/cms/pull/18917))
- Updated `pragmarx/google2fa` to 9.0.0. ([#18919](https://github.com/craftcms/cms/pull/18919))
- Fixed an error that occurred when opening element selector modals with string `sources` values. ([#18915](https://github.com/craftcms/cms/pull/18915))
- Added “Elements” and “Deprecations” panels to Laravel Debugbar. ([#18897](https://github.com/craftcms/cms/pull/18897))
- Fixed a bug where legacy redirect responses were not being returned as a redirect ([#18893](https://github.com/craftcms/cms/pull/18893))
- Fixed an error that could occur when saving filesystems with null transient settings. ([#18909](https://github.com/craftcms/cms/pull/18909))
- Fixed a bug where plugin routes were not being registered with the `web` middleware.
- Fixed an error that could occur when storing image transform indexes. ([#18899](https://github.com/craftcms/cms/pull/18899))
- Fixed an error when `loginPath` or `logoutPath` was set to `false` in `GeneralConfig`. ([#18894](https://github.com/craftcms/cms/issue/18894))
- Fixed a bug where plugin-registered Twig variables weren’t available via the `craft` template variable. ([#18903](https://github.com/craftcms/cms/pull/18903))
- Fixed an error that occurred when using the legacy cache service with a new dependency object ([#18904](https://github.com/craftcms/cms/pull/18904))
- Fixed a bug where clearing submitted values could retain the previous value. ([#18905](https://github.com/craftcms/cms/issues/18905))
- Fixed a bug where a legacy Yii action controller would result in a 404 when returning `null` as the response ([#18907](https://github.com/craftcms/cms/pull/18907))
- Fixed an error that could occur because `ol` and `ul` were not normalizing the attributes ([#18907](https://github.com/craftcms/cms/pull/18907))
- Fixed an error that occurred when trying to upload an asset through a legacy filesystem plugin ([#18908](https://github.com/craftcms/cms/pull/18908))

## 6.0.0-alpha.3 - 2026-05-15

- Added the `compiledTemplatesPath` config setting. ([#18861](https://github.com/craftcms/cms/pull/18861))
- Added a missing migration that adds `minAuthors` to the section table ([#18875](https://github.com/craftcms/cms/pull/18875))
- Fixed a bug where the `cpTrigger` would be appended twice to the URL after running migrations from the control panel. ([#18858](https://github.com/craftcms/cms/pull/18858))
- Fixed an error that occurred when rendering element indexes with blank source headings. ([#18891](https://github.com/craftcms/cms/pull/18891))
- Fixed an error that occurred when uninstalling plugins. ([#18862](https://github.com/craftcms/cms/pull/18862))
- Fixed an error that could occur when Control Panel HTML values were passed as `Stringable` objects. ([#18883](https://github.com/craftcms/cms/pull/18883))
- Fixed a bug where plugin package config files could affect plugin settings before being published. ([#18885](https://github.com/craftcms/cms/pull/18885))
- Fixed a bug where the control panel would continuously poll for queue job info, even if there were no active jobs. ([#18853](https://github.com/craftcms/cms/issues/18853))
- Fixed a bug where legacy redirect responses were not being handled. ([#18860](https://github.com/craftcms/cms/pull/18860))
- Fixed a bug where email addresses couldn’t be saved when applying unpublished user drafts. ([#18882](https://github.com/craftcms/cms/pull/18882))
- Fixed a bug where the Updates utility wasn’t showing available updates. ([#18884](https://github.com/craftcms/cms/pull/18884))

## 6.0.0-alpha.2 - 2026-05-13

- Added support for SQLite backups and restores. ([#18803](https://github.com/craftcms/cms/pull/18803))
- Added support for Symfony-style array config files in `config/craft/sanitizers/`. ([#18808](https://github.com/craftcms/cms/pull/18808))
- Added support for configuring the system time zone during installation. ([#18794](https://github.com/craftcms/cms/pull/18794))
- Added the legacy `paginate` Twig variable back.
- Added CP access permission checks to Control Panel action routes.
- The `craftAsset()` Twig function now resolves to Vite versioned assets. ([#18801](https://github.com/craftcms/cms/pull/18801))
- Renamed the `|money` Twig filter’s `formatLocale` argument to `locale`.
- Deprecated the `csrfTokenName`, `enableCsrfCookie`, and `enableCsrfProtection` general config settings. ([#18806](https://github.com/craftcms/cms/pull/18806))
- Removed support for the Debug Toolbar. [Laravel Debugbar](https://laraveldebugbar.com) can be used instead. ([#18812](https://github.com/craftcms/cms/pull/18812))
- Improved Control Panel icon loading performance.
- Fixed a PHP error that occurred when saving a Structure section with a Max Levels value. ([#18809](https://github.com/craftcms/cms/issues/18809))
- Fixed a bug where plugin settings pages were missing registered scripts and styles. ([#18815](https://github.com/craftcms/cms/pull/18815))
- Fixed a PHP error that occurred when saving an entry type. ([#18816](https://github.com/craftcms/cms/pull/18816))
- Fixed an issue with Typecast where typed setters wouldn’t have precedence over private properties.
- Fixed a bug where Control Panel templates failed to load on Windows due to mismatched directory separators or drive-letter casing in `CraftCms\Cms\View\TwigEngine`. ([#18804](https://github.com/craftcms/cms/issues/18804))
- Fixed a bug where Craft’s Vite hot file configuration could override the host application’s Vite hot file. ([#18810](https://github.com/craftcms/cms/issues/18810))
- Fixed a bug where `CraftCms\Cms\Support\Typecast` could give private properties precedence over typed setters.
- Fixed a bug where `runQueueAutomatically` wasn’t being respected. ([#18817](https://github.com/craftcms/cms/pull/18817))
- Fixed a bug where `CraftCms\Cms\Validation\Rules\EnvValueRule` could parse boolean values incorrectly.
- Fixed a bug where Blade templates weren’t resolving correctly.
- Fixed a bug where console command aliases weren’t prefixed with `craft:`.
- Fixed a bug where element duplication included IDs.
- Fixed a bug where legacy action requests didn’t resolve correctly when using `DefaultController`.
- Fixed a bug where legacy controller CSRF validation exclusions weren’t always respected.
- Fixed a bug where legacy plugin settings weren’t saved correctly.
- Fixed a bug where legacy redirects and streamed responses weren’t forwarded to Laravel correctly.
- Fixed a bug where preview requests could write template caches.
- Fixed a bug where private templates couldn’t be used as section templates.
- Fixed a bug where the legacy `Application::EVENT_AFTER_REQUEST` event wasn’t triggered.
- Fixed a bug where the password reset email throttle applied to Control Panel requests.
- Fixed a bug where the Support widget could render unescaped HTML.
- Fixed a bug where Twig macros were allowed in sandboxed templates.
- Fixed a bug where user passkeys weren’t persisting.
- Fixed an error that occurred when validation errors stored in the session were plain arrays.
- Fixed compatibility issues with legacy element queries.
- Fixed compatibility issues with legacy fallback routes.
- Fixed legacy model behavior when unsafe config keys were provided.
- Fixed permissions enforcement when duplicating Matrix blocks.
- Fixed permissions enforcement when saving user fields through generic element save endpoints.
- Fixed a bug where a `yii\base\InvalidConfigException` would be thrown when a Yii2-based plugin registered an asset bundle. ([#18818](https://github.com/craftcms/cms/issues/18818))
- Fixed a bug where using `{{ successMessageInput() }}` would not decrypt the resulting message for the flash message.
- Fixed a bug where a missing widget from an uninstalled plugin would throw instead of mapping to a MissingWidget.
- Fixed a bug where an address’ ownership ids could be overridden unintentionally.
- Fixed a bug where `getHasSsoIdentity()` would return `false` when Socialite was not installed but the user had an SSO identity.
- Fixed a bug where the site’s offline status was not being enforced on matched element routes.
- Fixed a user photo validation issue with file extensions.
- Fixed a bug where legacy controllers could return `null` but were not considered handled.
- Improved performance of the dashboard by reducing the amount of queries for widgets
- Fixed a bug where criteria added to clones of executed element queries could be ignored. ([#18826](https://github.com/craftcms/cms/pull/18826))
- Fixed a bug where Yii2 behaviors registered from plugins weren’t getting attached at the right time. ([#18824](https://github.com/craftcms/cms/issues/18824))
- Fixed an error that occurred when running `craft:install` in environments where Laravel Prompts can only render tasks statically. ([#18830](https://github.com/craftcms/cms/pull/18830))
- Fix legacy model array access for null properties ([#18843](https://github.com/craftcms/cms/pull/18843))
- Fix Twig access to Laravel error bags ([#18841](https://github.com/craftcms/cms/pull/18841))
- Fixed a bug where singles were throwing an exception ([#18845](https://github.com/craftcms/cms/issue/18845))
- Fixed a bug where plugin settings were not saving consistently ([#18849](https://github.com/craftcms/cms/pull/18849))
- Fixed a bug where deprecated string-based `orderBy()` arguments could cause element queries to throw an unknown column exception. ([#18852](https://github.com/craftcms/cms/pull/18852))
- Fixed a bug where Laravel HTTP exceptions weren’t passed to legacy error handler event listeners. ([#18854](https://github.com/craftcms/cms/pull/18854))
- Fixed a bug where FieldLayouts were being flashed to the session as arrays. ([#18847](https://github.com/craftcms/cms/pull/18847))

## 6.0.0-alpha.1 - 2026-05-06

### Development
- Reference tags now support fallback values when no attribute is specified. ([#17688](https://github.com/craftcms/cms/pull/17688))
- Deprecated support for categories, global sets, and tags. ([#18009](https://github.com/craftcms/cms/pull/18009))

### Extensibility
- Added `CraftCms\Cms\Support\Arr`.
- Added `CraftCms\Cms\Support\DateTimeHelper`.
- Added `CraftCms\Cms\Support\File`.
- Added `CraftCms\Cms\Support\Facades\Path`.
- Added `CraftCms\Cms\Support\Facades\Markdown`.
- Added `CraftCms\Cms\Support\Path`.
- Added `CraftCms\Cms\Support\Str`.
- Added `CraftCms\Cms\Support\Url`.
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
- Deprecated `config/craft/htmlpurifier/*.json` sanitizer config files. Sanitizers should be registered on `CraftCms\Cms\Support\HtmlSanitizer\HtmlSanitizers` instead.
- Deprecated `craft\services\Path`. `CraftCms\Cms\Support\Path` should be used instead.
- Deprecated `craft\helpers\SessionHelper`. `Illuminate\Support\Facades\Session` should be used instead.
- Deprecated `craft\helpers\Sequence`. `CraftCms\Cms\Support\Sequence` should be used instead.
- Deprecated `craft\helpers\StringHelper`. `CraftCms\Cms\Support\Str` should be used instead.
- Deprecated `Craft::$app->getConfig()->getGeneral()`. `CraftCms\Cms\Config\GeneralConfig` should be used instead. This can be used through dependency injection or through `app(CraftCms\Cms\Config\GeneralConfig::class)`.
- Deprecated `craft.app.config.general` in Twig. `app.config.craft.general` should be used instead.
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
- Deprecated `craft\helpers\UrlHelper`. `CraftCms\Cms\Support\Url` should be used instead.

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

Craft’s Mutex classes have been deprecated. [Laravel’s atomic locking](https://laravel.com/docs/12.x/cache#atomic-locks) should be used instead.

- Deprecated `craft\mutex\Mutex`
- Deprecated `craft\mutex\MutexTrait`
- Deprecated `Craft::$app->getMutex()`

#### Components
- Deprecated `craft\base\ComponentInterface`. `CraftCms\Cms\Component\Contracts\ComponentInterface` should be used instead.
- Deprecated `craft\base\ConfigurableComponentInterface`. `CraftCms\Cms\Component\Contracts\ConfigurableComponentInterface` should be used instead.
- Deprecated `craft\base\SavableComponentInterface`. `CraftCms\Cms\Component\Contracts\SavableComponentInterface` should be used instead.

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

- Deprecated `craft\services\Dashboard::EVENT_REGISTER_WIDGET_TYPES`. `CraftCms\Cms\Dashboard\Events\WidgetTypesResolving` should be used instead.
- Deprecated `craft\events\WidgetEvent` in favor of the following new events:
  - `craft\services\Dashboard::EVENT_BEFORE_SAVE_WIDGET` => `CraftCms\Cms\Dashboard\Events\WidgetSaving`
  - `craft\services\Dashboard::EVENT_AFTER_SAVE_WIDGET` => `CraftCms\Cms\Dashboard\Events\WidgetSaved`
  - `craft\services\Dashboard::EVENT_BEFORE_DELETE_WIDGET` => `CraftCms\Cms\Dashboard\Events\WidgetDeleting`
  - `craft\services\Dashboard::EVENT_AFTER_DELETE_WIDGET` => `CraftCms\Cms\Dashboard\Events\WidgetDeleted`

### Address

- Added `CraftCms\Cms\Support\Facades\Addresses`.

### Assets

- Added `CraftCms\Cms\Asset\AssetsHelper`.
- Added `CraftCms\Cms\Support\Facades\Assets`.
- Added `CraftCms\Cms\Support\Facades\AssetIndexer` facade.
- Added `CraftCms\Cms\Support\Facades\Folders`.
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

#### Events

- Added `CraftCms\Cms\Asset\Events\AssetFileKindsResolving`.
- Added `CraftCms\Cms\Asset\Events\SetAssetFilename`.
- Deprecated `craft\events\SetAssetFilenameEvent`. `CraftCms\Cms\Asset\Events\SetAssetFilename` should be used instead.
- Deprecated `craft\events\RegisterAssetFileKindsEvent`. `CraftCms\Cms\Asset\Events\AssetFileKindsResolving` should be used instead.
- Deprecated `craft\events\ReplaceAssetEvent` in favor of the following new events:
  - `craft\services\Assets::EVENT_BEFORE_REPLACE_ASSET` => `CraftCms\Cms\Asset\Events\AssetReplacing`
  - `craft\services\Assets::EVENT_AFTER_REPLACE_ASSET` => `CraftCms\Cms\Asset\Events\AssetReplaced`
- Deprecated `craft\events\DefineAssetThumbUrlEvent`. `CraftCms\Cms\Asset\Events\ThumbUrlResolving` should be used instead.
- Deprecated `craft\events\AssetPreviewEvent`. `CraftCms\Cms\Asset\Events\PreviewHandlerResolving` should be used instead.

### Auth

- Refactored the authentication system to use Laravel’s authentication system.
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
- Deprecated the `elevatedSessionDuration` general config setting. The `auth.password_timeout` config value should be used instead. To disable password confirmation (elevated sessions), you now set this value to `-1` instead of `0`.
  - Elevated sessions now work through [Laravel’s password confirmation](https://laravel.com/docs/12.x/authentication#password-confirmation) system.
- Removed `craft\controllers\AuthController`. The following controllers now implement this functionality:
  - `CraftCms\Cms\Http\Controllers\Users\AuthMethodController`
  - `CraftCms\Cms\Http\Controllers\Users\PasskeysController`
  - `CraftCms\Cms\Http\Controllers\Users\RecoveryCodesController`
- Removed `verificationCode` and `verificationCodeIssuedDate` columns on the `users` table in favor of the `password_reset_tokens` table.

#### Authorization

Craft 6 now uses [Laravel’s authorization system](https://laravel.com/docs/12.x/authorization) for element authorization checks.

##### Added

- Added `CraftCms\Cms\Auth\Events\ElementAuthorizing` event for customizing element authorization.
- Added `CraftCms\Cms\Element\Policies\ElementPolicy` base policy for element authorization.
- Added element-specific authorization policies:
  - `CraftCms\Cms\Address\Policies\AddressPolicy`
  - `CraftCms\Cms\Asset\Policies\AssetPolicy`
  - `CraftCms\Cms\Entry\Policies\EntryPolicy`
  - `CraftCms\Cms\User\Policies\UserPolicy`
  - `CraftCms\Cms\Field\Policies\ContentBlockPolicy`

#### Passkeys

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

### Conditions

#### Added

- Added `CraftCms\Cms\Support\Facades\Conditions`.

#### Controllers

- Removed `craft\controllers\ConditionsController`. `CraftCms\Cms\Http\Controllers\ConditionsController` should be used instead.

#### Deprecations

##### Service

- Deprecated `craft\services\Conditions`. `CraftCms\Cms\Condition\Conditions` should be used instead.

##### Base Conditions

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

##### Elements

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

##### Entries

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

##### Users

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

##### Assets

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

##### Addresses

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

##### Fields

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

##### Events

- Deprecated `craft\events\RegisterConditionRulesEvent`. `CraftCms\Cms\Condition\Events\ConditionRulesResolving` should be used instead.

### Drafts

- Deprecated `craft\services\Drafts`. `CraftCms\Cms\Element\Drafts` should be used instead.
- Deprecated `craft\events\DraftEvent`. One of the events extending `CraftCms\Cms\Element\Events\DraftEvent` should be used instead.
- Deprecated `craft\behaviors\DraftBehavior`. `CraftCms\Cms\Element\Concerns\Draftable` should be used instead.

### Elements

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

#### Validation

Craft 6 introduces a new validation system that uses Laravel’s Validator instead of Yii2’s model validation.

##### Added

- Added `CraftCms\Cms\Validation\Contracts\Validatable` interface for classes that support Laravel-style validation.
- Added `CraftCms\Cms\Validation\Contracts\ValidatableWithRuleset` interface for classes that use a `Ruleset` class to define validation rules.
- Added `CraftCms\Cms\Validation\Ruleset` abstract class for defining validation rules, messages, and preparation logic.
- Added `CraftCms\Cms\Validation\Attributes\Ruleset` PHP attribute for specifying a component’s ruleset class.
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

##### Changed

- `FieldInterface::getElementValidationRules()` has been replaced by `FieldInterface::getElementRules()` which returns rules in Laravel’s validation format.
- Added `FieldInterface::prepareForElementValidation()` for preparing field values before validation.
- Validation rules are now defined as Laravel-style arrays (e.g., `['required', 'string', 'max:255']`).

##### Deprecations

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

### ElementSources

- Deprecated `craft\services\ElementSources`. `CraftCms\Cms\Element\ElementSources` should be used instead.
- Deprecated `craft\events\DefineSourceSortOptionsEvent`. `CraftCms\Cms\Element\Events\ElementSourceSortOptionsResolving` should be used instead.
- Deprecated `craft\events\DefineSourceTableAttributesEvent`. `CraftCms\Cms\Element\Events\ElementSourceTableAttributesResolving` should be used instead.

### Element Queries

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

### Entries & Entry Types

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

### Component

- Added `CraftCms\Cms\Component\Component` base class, replacing Yii2’s `BaseObject`/`Component` with config hydration, magic getters/setters, and `Arrayable` support.
- Added `CraftCms\Cms\Component\Exceptions\InvalidCallException`, replacing `yii\base\InvalidCallException`.
- Added `CraftCms\Cms\Component\Exceptions\UnknownPropertyException`, replacing `yii\base\UnknownPropertyException`.

### Field Layouts

#### Added

- Added `CraftCms\Cms\FieldLayout\FieldLayoutForm`.
- Added `CraftCms\Cms\FieldLayout\FieldLayoutFormTab`.
- Added `CraftCms\Cms\FieldLayout\FieldLayoutFormElement`.
- Added `CraftCms\Cms\FieldLayout\FieldLayoutServiceProvider`.
- Added `CraftCms\Cms\FieldLayout\Concerns\HasFieldLayout` trait.

#### Deprecations
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
- Deprecated `craft\events\DefineFieldLayoutFieldsEvent`. `CraftCms\Cms\FieldLayout\Events\NativeFieldsResolving` should be used instead.
- Deprecated `craft\events\DefineShowFieldLayoutComponentInFormEvent`. `CraftCms\Cms\FieldLayout\Events\FieldLayoutComponentShowInFormResolving` should be used instead.
- Deprecated `craft\events\DefineFieldActionsEvent`. `CraftCms\Cms\FieldLayout\Events\FieldLayoutComponentActionMenuItemsResolving` should be used instead.

### Fields

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

### Filesystems

- Deprecated `craft\errors\InvalidSubpathException`. `CraftCms\Cms\Filesystem\Exceptions\InvalidSubpathException` should be used instead.

### GQL

- Deprecated `\craft\records\GqlSchema`. `\CraftCms\Cms\Gql\Models\GqlSchema` should be used instead.
- Deprecated `\craft\records\GqlToken`. `\CraftCms\Cms\Gql\Models\GqlToken` should be used instead.

### HTTP

- Deprecated the `errorTemplatePrefix` general config setting. Configure [Laravel’s custom error pages](https://laravel.com/docs/13.x/errors#custom-http-error-pages) instead.
- Deprecated `craft\filters\BasicHttpAuthLogin`. Use the `auth.basic` middleware instead. (see https://laravel.com/docs/12.x/authentication#http-basic-authentication)
- Deprecated `craft\filters\BasicHttpAuthStatic`. Use the `auth.basic` middleware instead. (see https://laravel.com/docs/12.x/authentication#http-basic-authentication)
- Deprecated `craft\filters\BasicHttpAuthTrait`. Use the `auth.basic` middleware instead. (see https://laravel.com/docs/12.x/authentication#http-basic-authentication)
- Deprecated `craft\filters\Cors`. Use Laravel’s CORS settings instead. (see https://laravel.com/docs/12.x/routing#cors)
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
- Removed `craft\controllers\NotFoundController`. Laravel’s exception handling should be used instead.
- Removed the header-setting logic in `yii2-adapter\legacy\web\Application`. The new `\CraftCms\Cms\Http\Middleware\SetHeaders` middleware handles this functionality.
- Removed the licensing issues screen logic in `yii2-adapter\legacy\web\Application`. The new `\CraftCms\Cms\Http\Middleware\EnforceLicenses` middleware handles this functionality.
- Removed `craft\controllers\AppController::actionTryEdition()` and `actionSwitchToLicensedEdition()` in favor of `CraftCms\Cms\Http\Controllers\EditionController`.

### Mail

- Added `CraftCms\Cms\Email\Commands\SendTestMailCommand`.
- Added `CraftCms\Cms\Email\Mailables\CraftMailable`, a base mailable class that automatically applies project config email settings (from, replyTo, mailer) with site-specific overrides.
- Added `CraftCms\Cms\SystemMessage\Mailables\SystemMessageMailable`.
- Deprecated `Craft::$app->getMailer()`. Laravel mailers/drivers and `CraftCms\Cms\SystemMessage\SystemMessages::mailable()` should be used instead.
- Deprecated `craft\mail\Mailer`. Laravel mailers/drivers and `CraftCms\Cms\SystemMessage\SystemMessages::mailable()` should be used instead.
- Deprecated `craft\helpers\MailerHelper`. Laravel mail configuration and drivers should be used instead.
- Deprecated the `testToEmailAddress` general config setting. `Illuminate\Support\Facades\Mail::alwaysTo()` should be used instead.
- Deprecated `craft\mail\Mailer::$template`, `craft\mail\Mailer::$siteOverrides`, `craft\models\MailSettings::$template`, and `craft\models\MailSettings::$siteOverrides`. Laravel mailable views and environment-specific Laravel mailers should be used instead.
- Removed legacy `projectConfig.email` mail settings and mail transport adapter configuration in favor of Laravel’s `mail` config and drivers.

### Migrations

Craft and Yii’s migrations have been removed in favor of [Laravel migrations](https://laravel.com/docs/12.x/migrations).

The `php craft fields:merge` and `php craft entry-types:merge` commands will now generate Laravel migrations.

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

### Plugins

#### Added
- The base `CraftCms\Cms\Plugin\Plugin` class is now a [Laravel ServiceProvider](https://laravel.com/docs/12.x/providers) which provides a new way to register components for your plugins.

#### Deprecations

- Deprecated `craft\services\Plugins`. `CraftCms\Cms\Plugin\Plugins` should be used instead.
- Deprecated `craft\base\Plugin`. `CraftCms\Cms\Plugin\Plugin` should be used instead.
- Deprecated `craft\base\PluginTrait`.
- Deprecated `craft\base\PluginInterface`. `CraftCms\Cms\Plugin\Contracts\PluginInterface` should be used instead.
- Deprecated `craft\errors\InvalidPluginException`. `CraftCms\Cms\Plugin\Exceptions\InvalidPluginException` should be used instead.
- Deprecated `craft\errors\InvalidLicenseKeyException`. `CraftCms\Cms\Plugin\Exceptions\InvalidLicenseKeyException` should be used instead.

#### Controllers
- Removed `craft\controllers\PluginsController`. Use `CraftCms\Cms\Http\Controllers\PluginsController` instead.

#### Commands
- Removed `craft\console\controllers\PluginController` in favor of:
  - `CraftCms\Cms\Plugin\Commands\DisableCommand` -> `php craft plugin:disable`
  - `CraftCms\Cms\Plugin\Commands\EnableCommand` -> `php craft plugin:enable`
  - `CraftCms\Cms\Plugin\Commands\InstallCommand` -> `php craft plugin:install`
  - `CraftCms\Cms\Plugin\Commands\UninstallCommand` -> `php craft plugin:uninstall`
  - `CraftCms\Cms\Plugin\Commands\ListCommand` -> `php craft plugin:list`

#### Events
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
  - `craft\services\Plugins::EVENT_AFTER_LOAD_PLUGINS` => `CraftCms\Cms\Plugin\Events\PluginsLoaded`;
  - `craft\services\Plugins::EVENT_AFTER_SAVE_PLUGIN_SETTINGS` => `CraftCms\Cms\Plugin\Events\PluginSettingsSaved`;
  - `craft\services\Plugins::EVENT_AFTER_UNINSTALL_PLUGIN` => `CraftCms\Cms\Plugin\Events\PluginUninstalled`;

### Request

- Added `Request::isPreview()` macro for detecting preview requests via `x-craft-preview` or `x-craft-live-preview` parameters.
- Added `Request::isCpRequest()`, `Request::isSiteRequest()`, `Request::isActionRequest()`, `Request::actionSegments()`, `Request::actionSegmentsToRoute()`, `Request::duplicateWithUri()`, `Request::getToken()`, and `Request::getSigned()` macros.
- Updated paginated requests to resolve the current page from the configured `pageTrigger` query parameter rather than path-style pagination segments.

### Security

- Added `CraftCms\Cms\Support\Security`.
- Added `CraftCms\Cms\Support\Facades\Security`.
- Added `CraftCms\Cms\Http\Middleware\AddLogContext`.
- Deprecated `Craft::$app->getSecurity()` in favor of Laravel’s Hash and Crypt facades, or `CraftCms\Cms\Support\Facades\Security`.
- Deprecated the `blowfishHashCost` general config setting in favor of Laravel’s `hashing.bcrypt.rounds` config or the `BCRYPT_ROUNDS` environment variable.

### Updates

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

##### Deprecations & removals
- Deprecated `craft\helpers\Install`. `CraftCms\Cms\Site\Concerns\SiteDefaults` should be used instead.
- Deprecated `craft\helpers\Update`. The only method was `checkPhpConstraint` which is now available on `CraftCms\Cms\Support\PHP::checkConstraint()`
- Removed `craft\events\UpdateReleaseEvent` in favor of `CraftCms\Cms\Update\Events\CriticalUpdateReleasedEvent`
- Removed `craft\models\Update`. `CraftCms\Cms\Update\Data\Update` should be used instead.
- Removed `craft\models\UpdateRelease`. `CraftCms\Cms\Update\Data\UpdateRelease` should be used instead.
- Removed `craft\models\Updates`. `CraftCms\Cms\Update\Data\Updates` should be used instead.

#### Users

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

### Project Config

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

### Revisions

- Deprecated `craft\services\Revisions`. `CraftCms\Cms\Element\Revisions` should be used instead.
- Deprecated `craft\events\RevisionEvent`. One of the events extending `CraftCms\Cms\Element\Events\RevisionEvent` should be used instead.
- Deprecated `craft\behaviors\RevisionBehavior`. `CraftCms\Cms\Element\Concerns\Revisionable` should be used instead.

### Routes

- Deprecated `craft\services\Routes`. `CraftCms\Cms\Route\Routes` should be used instead.
- Using routes in `config/routes.php` is no longer supported. Register routes using [Laravel’s routing](https://laravel.com/docs/12.x/routing) instead.

### Search

- Added `CraftCms\Cms\Support\Facades\Search`.
- Deprecated `craft\services\Search`. `CraftCms\Cms\Search\Search` should be used instead.
- Deprecated `Craft::$app->getSearch()`. `CraftCms\Cms\Support\Facades\Search` or `app(CraftCms\Cms\Search\Search::class)` should be used instead.
- Deprecated `craft\search\SearchQuery`. `CraftCms\Cms\Search\SearchQuery` should be used instead.
- Deprecated `craft\search\SearchQueryTerm`. `CraftCms\Cms\Search\SearchQueryTerm` should be used instead.
- Deprecated `craft\search\SearchQueryTermGroup`. `CraftCms\Cms\Search\SearchQueryTermGroup` should be used instead.
- Deprecated `craft\events\SearchEvent` in favor of the following new events:
  - `craft\services\Search::EVENT_BEFORE_SEARCH` => `CraftCms\Cms\Search\Events\SearchStarting`
  - `craft\services\Search::EVENT_AFTER_SEARCH` => `CraftCms\Cms\Search\Events\SearchPerformed`
  - `craft\services\Search::EVENT_BEFORE_SCORE_RESULTS` => `CraftCms\Cms\Search\Events\ScoringResults`
- Deprecated `craft\events\IndexKeywordsEvent`. `CraftCms\Cms\Search\Events\KeywordsIndexing` should be used instead.

### Sections

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

### Sites

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

### Structures

- Deprecated `craft\services\Structures`. `CraftCms\Cms\Structure\Structures` should be used instead.
- Deprecated `craft\models\Structure`. `CraftCms\Cms\Structure\Data\Structure` should be used instead.
- Deprecated `craft\records\Structure`. `CraftCms\Cms\Structure\Models\Structure` should be used instead.
- Deprecated `craft\records\StructureElement`. `CraftCms\Cms\Structure\Models\StructureElement` should be used instead.
- Replaced `craft\controllers\StructuresController`. `CraftCms\Cms\Http\Controllers\StructuresController`.
- Replaced structure related commands in `craft\console\controllers\RepairController` with:
  - `\CraftCms\Cms\Structure\Commands\RepairCategoryGroupStructureCommand`
  - `\CraftCms\Cms\Structure\Commands\RepairSectionStructureCommand`

### System Messages

- Deprecated `craft\services\SystemMessages`. `CraftCms\Cms\SystemMessage\SystemMessages` should be used instead.
- Deprecated `craft\models\SystemMessage` and `craft\records\SystemMessage`. `CraftCms\Cms\SystemMessage\Models\SystemMessage` should be used instead.
- Replaced `craft\controllers\SystemMessagesController` with `CraftCms\Cms\Http\Controllers\Utilities\SystemMessagesController`

### Tokens

- Deprecated `craft\services\Tokens`. `CraftCms\Cms\RouteToken\RouteTokens` should be used instead.
- Deprecated `craft\records\Token`. `CraftCms\Cms\RouteToken\Models\RouteToken` should be used instead.

### Twig

- Updated Twig `{% paginate %}` queries to use Laravel paginators and generate query-string pagination URLs based on the `pageTrigger` general config setting.
- Added `CraftCms\Cms\Twig\Twig` service for managing Twig environments, replacing the Twig management logic previously in `craft\web\View`.
- Added `CraftCms\Cms\View\TemplateManager` for rendering templates, replacing the rendering logic previously in `craft\web\View`.
- Added `CraftCms\Cms\Twig\PageLifecycle` for managing the page rendering lifecycle (head/body placeholder replacement), replacing the page lifecycle logic previously in `craft\web\View`.
- Added `CraftCms\Cms\Support\Facades\Twig` facade, resolving to `CraftCms\Cms\Twig\Twig`.
- Added `CraftCms\Cms\Twig\Environment`, moved from `craft\web\twig\Environment`.
- Added `CraftCms\Cms\Twig\TemplateResolver`.
- Added `CraftCms\Cms\Twig\TemplateLoader`.
- Added `CraftCms\Cms\Twig\Exceptions\TemplateLoaderException`.
- Added helper functions in the `CraftCms\Cms` namespace: `template()`, `sandboxedTemplate()`, `pageTemplate()`, `renderString()`, `renderSandboxedString()`, `renderObjectTemplate()`, `renderSandboxedObjectTemplate()`.
- Added `|sanitize` Twig filter for sanitizing HTML with `CraftCms\Cms\Support\HtmlSanitizer\HtmlSanitizers`.
- Deprecated `craft\web\View::getTwig()`. `CraftCms\Cms\Twig\Twig::get()` should be used instead.
- Deprecated `craft\web\View::setTwig()`. `CraftCms\Cms\Twig\Twig::set()` should be used instead.
- Deprecated `craft\web\View::createTwig()`. `CraftCms\Cms\Twig\Twig::create()` should be used instead.
- Deprecated `craft\web\View::registerCpTwigExtension()`. `CraftCms\Cms\Twig\Twig::registerExtension()` should be used instead.
- Deprecated `craft\web\View::registerSiteTwigExtension()`. `CraftCms\Cms\Twig\Twig::registerExtension()` should be used instead.
- Deprecated `craft\web\View::registerTwigExtension()`. `CraftCms\Cms\Twig\Twig::registerExtension()` should be used instead.
- Deprecated `craft\web\View::renderTemplate()`. `CraftCms\Cms\View\TemplateManager::renderTemplate()` or the `template()` helper should be used instead.
- Deprecated `craft\web\View::renderSandboxedTemplate()`. `CraftCms\Cms\View\TemplateManager::renderSandboxedTemplate()` or the `sandboxedTemplate()` helper should be used instead.
- Deprecated `craft\web\View::renderPageTemplate()`. `CraftCms\Cms\View\TemplateManager::renderPageTemplate()` or the `pageTemplate()` helper should be used instead.
- Deprecated `craft\web\View::renderString()`. `CraftCms\Cms\View\TemplateManager::renderTwigString()` or the `renderString()` helper should be used instead.
- Deprecated `craft\web\View::renderSandboxedString()`. `CraftCms\Cms\View\TemplateManager::renderSandboxedString()` or the `renderSandboxedString()` helper should be used instead.
- Deprecated `craft\web\View::renderObjectTemplate()`. `CraftCms\Cms\View\TemplateManager::renderObjectTemplate()` or the `renderObjectTemplate()` helper should be used instead.
- Deprecated `craft\web\View::renderSandboxedObjectTemplate()`. `CraftCms\Cms\View\TemplateManager::renderSandboxedObjectTemplate()` or the `renderSandboxedObjectTemplate()` helper should be used instead.
- Deprecated `craft\web\View::normalizeObjectTemplate()`. `CraftCms\Cms\View\TemplateManager::normalizeObjectTemplate()` should be used instead.
- Deprecated `craft\web\View::getIsRenderingTemplate()`. `CraftCms\Cms\View\TemplateManager::isRenderingTemplate()` should be used instead.
- Deprecated `craft\web\View::getIsRenderingPageTemplate()`. `CraftCms\Cms\View\TemplateManager::isRenderingPageTemplate()` should be used instead.
- Deprecated `craft\web\twig\Environment`. `CraftCms\Cms\Twig\Environment` should be used instead.
- Deprecated `craft\web\View::EVENT_AFTER_CREATE_TWIG`. `CraftCms\Cms\Twig\Events\TwigCreated` should be used instead.
- Deprecated `craft\web\View::doesTemplateExist()`. `CraftCms\Cms\Twig\TemplateResolver::doesTemplateExist()` should be used instead.
- Deprecated `craft\web\View::resolveTemplate()`. `CraftCms\Cms\Twig\TemplateResolver::resolveTemplate()` should be used instead.
- Deprecated `craft\web\twig\TemplateLoader`. `CraftCms\Cms\Twig\TemplateLoader` should be used instead.
- Deprecated `craft\web\twig\TemplateLoaderException`. `CraftCms\Cms\Twig\Exceptions\TemplateLoaderException` should be used instead.

#### Events

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

### Translations

- Deprecated `craft\i18n\FormatConverter`. `CraftCms\Cms\Translation\FormatConverter` should be used instead.
- Deprecated `craft\i18n\Formatter`. `CraftCms\Cms\Translation\Formatter` should be used instead.
- Deprecated `craft\i18n\I18N`. `CraftCms\Cms\Translation\I18N` should be used instead.
- Deprecated `craft\i18n\Locale`. `CraftCms\Cms\Translation\Locale` should be used instead.
- Deprecated `craft\i18n\MessageFormatter`.
- Deprecated `craft\i18n\PhpMessageSource`.
- Deprecated `craft\i18n\Translation`. `CraftCms\Cms\Support\Facades\I18N` should be used instead.
- Deprecated `Craft::t`. `CraftCms\Cms\t` should be used instead.

### Users

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
- Deprecated `craft.app.userPermissions`. `craft.userPermissions` should be used instead.
- Deprecated `craft\events\DefineEditUserScreensEvent`. `CraftCms\Cms\User\Events\EditUserScreensResolving` should be used instead.

### View

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
- Added `CraftCms\Cms\View\Events\CpTemplateRootsResolving`.
- Added `CraftCms\Cms\View\Events\SiteTemplateRootsResolving`.
- Added `CraftCms\Cms\View\TemplateCaches`.
- Added `CraftCms\Cms\View\CacheCollectors\DependencyCollector`.
- Added `CraftCms\Cms\View\CacheCollectors\ResourceCollector`.
- Added `CraftCms\Cms\View\Contracts\CacheCollectorInterface`.
- Added `CraftCms\Cms\View\Data\TemplateCacheContext`.
- Added `CraftCms\Cms\View\Events\TemplateCacheCollectorsResolving`.
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
- Deprecated `craft\web\View::EVENT_REGISTER_CP_TEMPLATE_ROOTS`. `CraftCms\Cms\View\Events\CpTemplateRootsResolving` should be used instead.
- Deprecated `craft\web\View::EVENT_REGISTER_SITE_TEMPLATE_ROOTS`. `CraftCms\Cms\View\Events\SiteTemplateRootsResolving` should be used instead.
- Deprecated `craft\web\View::registerDeltaName()`. `CraftCms\Cms\View\DeltaRegistry::registerName()` should be used instead.
- Deprecated `craft\web\View::getDeltaNames()`. `CraftCms\Cms\View\DeltaRegistry::getNames()` should be used instead.
- Deprecated `craft\web\View::getModifiedDeltaNames()`. `CraftCms\Cms\View\DeltaRegistry::getModifiedNames()` should be used instead.
- Deprecated `craft\web\View::setInitialDeltaValue()`. `CraftCms\Cms\View\DeltaRegistry::setInitialValue()` should be used instead.
- Deprecated `craft\web\View::getInitialDeltaValues()`. `CraftCms\Cms\View\DeltaRegistry::getInitialValues()` should be used instead.
- Deprecated `craft\web\View::getIsDeltaRegistrationActive()`. `CraftCms\Cms\View\DeltaRegistry::isActive()` should be used instead.
- Deprecated `craft\web\View::setIsDeltaRegistrationActive()`. `CraftCms\Cms\View\DeltaRegistry::setActive()` should be used instead.
- Deprecated `craft\web\View::hook()`. `CraftCms\Cms\View\TemplateHooks::register()` should be used instead.
- Deprecated `craft\web\View::invokeHook()`. `CraftCms\Cms\View\TemplateHooks::invoke()` should be used instead.
