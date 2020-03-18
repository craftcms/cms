# Running Release Notes for Craft CMS 3.5

### Added
- Added the “Use shapes to represent statuses” user preference. ([#3293](https://github.com/craftcms/cms/issues/3293))
- Added the ability to disable sites on the front end. ([#3005](https://github.com/craftcms/cms/issues/3005))
- Soft-deleted elements now have a “Delete permanently” element action. ([#4420](https://github.com/craftcms/cms/issues/4420))
- It’s now possible to set a custom route that handles Set Password requests. ([#5722](https://github.com/craftcms/cms/issues/5722))
- Added the `siteToken` config setting.
- Added support for the `CRAFT_CP` PHP constant. ([#5122](https://github.com/craftcms/cms/issues/5122))
- Added the `drafts`, `draftOf`, `draftId`, `draftCreator`, `revisions`, `revisionOf`, `revisionId` and `revisionCreator` arguments to element queries using GraphQL API. ([#5580](https://github.com/craftcms/cms/issues/5580)) 
- Added the `isDraft`, `isRevision`, `sourceId`, `sourceUid`, and `isUnsavedDraft` fields to elements when using GraphPQL API. ([#5580](https://github.com/craftcms/cms/issues/5580))
- Added the `assetCount`, `categoryCount`, `entryCount`, `tagCount`, and `userCount` queries for fetching the element counts to the GraphPQL API. ([#4847](https://github.com/craftcms/cms/issues/4847))
- Added the `locale` argument to the `formatDateTime` GraphQL directive. ([#5593](https://github.com/craftcms/cms/issues/5593))
- Added `craft\base\ElementInterface::getIconUrl()`.
- Added `craft\base\ElementInterface::gqlMutationNameByContext()`.
- Added `craft\config\GeneralConfig::getTestToEmailAddress()`.
- Added `craft\console\controllers\MailerController::$to`.
- Added `craft\elements\actions\Delete::$hard`.
- Added `craft\elements\Asset::getSrcset()`. ([#5774](https://github.com/craftcms/cms/issues/5774))
- Added `craft\events\RegisterGqlMutationsEvent`.
- Added `craft\gql\arguments\elements\DraftMutation`.
- Added `craft\gql\arguments\elements\EntryMutation`.
- Added `craft\gql\base\ElementMutationArguments`.
- Added `craft\gql\base\GeneratorInterface::generateType()`.
- Added `craft\gql\base\MutationArguments`.
- Added `craft\gql\ElementQueryConditionBuilder`.
- Added `craft\gql\Mutation`.
- Added `craft\gql\mutations\Entry`.
- Added `craft\gql\mutations\Ping`.
- Added `craft\gql\resolvers\mutations\CreateDraft`.
- Added `craft\gql\resolvers\mutations\PublishDraft`.
- Added `craft\gql\resolvers\mutations\SaveDraft`.
- Added `craft\gql\resolvers\mutations\SaveEntry`.
- Added `craft\gql\types\Mutation`.
- Added `craft\helpers\Assets::parseSrcsetSize()`.
- Added `craft\helpers\Assets::scaledDimensions()`.
- Added `craft\helpers\Gql::canMutateEntries()`.
- Added `craft\helpers\MailerHelper::normalizeEmails()`.
- Added `craft\helpers\MailerHelper::settingsReport()`.
- Added `craft\models\Site::$enabled`.
- Added `craft\web\AssetBundle\ContentWindowAsset`.
- Added `craft\web\AssetBundle\IframeResizerAsset`.
- Added `craft\web\Request::getFullUri()`.
- Added the [iFrame Resizer](http://davidjbradshaw.github.io/iframe-resizer/) library.
- Added transform support to `width` and `height` for Assets when using GraphQL API.

### Changed
- Large asset thumbnails now use the same aspect ratio as the source image. ([#5515](https://github.com/craftcms/cms/issues/5515))
- Preview frames now maintain their scroll position across refreshes, even for cross-origin preview targets.
- Preview targets that aren’t directly rendered by Craft must now include `lib/iframe-resizer-cw/iframeResizer.contentWindow.js` in order to maintain scroll position across refreshes.
- The preview frame header no longer hides the top 54px of the preview frame when it’s scrolled all the way to the top. ([#5547](https://github.com/craftcms/cms/issues/5547))
- Modal backdrops no longer blur the page content. ([#5651](https://github.com/craftcms/cms/issues/5651))
- The `cpTrigger` config setting can now be set to `null` or an empty string. ([#5122](https://github.com/craftcms/cms/issues/5122))
- The `mailer/test` command now only supports testing the current email settings.
- The `withTransforms` asset query param can now include `srcset`-style sizes (e.g. `100w` or `2x`), following a normal transform definition.
- The `QueryArgument` GraphQL type now also allows boolean values.
- `craft\elements\Asset::getImg()` now has a `$sizes` argument. ([#5774](https://github.com/craftcms/cms/issues/5774))
- `craft\services\Gql` now fires a `registerGqlMutations` event that allows for plugins to register their own GraphQL mutations.
- `craft\services\Sites::getAllSiteIds()`, `getSiteByUid()`, `getAllSites()`, `getSitesByGroupId()`, `getSiteById()`, and `getSiteByHandle()` now have `$withDisabled` arguments.
- Improved transform eager-loading support when using GraphQL API.

### Fixed
- Fixed a bug where the `mailer/test` command wasn’t factoring in custom `mailer` configurations in its settings report. ([#5763](https://github.com/craftcms/cms/issues/5763))

### Deprecated
- Deprecated `craft\gql\base\Resolver::extractEagerLoadCondition()` in favor of the new `ElementQueryConditionBuilder` class.
