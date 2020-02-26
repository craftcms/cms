# Running Release Notes for Craft CMS 3.5

### Added
- It’s now possible to set a custom route that handles Set Password requests. ([#5722](https://github.com/craftcms/cms/issues/5722))
- Added the `drafts`, `draftOf`, `draftId`, `draftCreator`, `revisions`, `revisionOf`, `revisionId` and `revisionCreator` arguments to element queries using GraphQL API. ([#5580](https://github.com/craftcms/cms/issues/5580)) 
- Added the `isDraft`, `isRevision`, `sourceId`, `sourceUid`, and `isUnsavedDraft` fields to elements when using GraphPQL API. ([#5580](https://github.com/craftcms/cms/issues/5580))
- Added the `assetCount`, `categoryCount`, `entryCount`, `tagCount`, and `userCount` queries for fetching the element counts to the GraphPQL API. ([#4847](https://github.com/craftcms/cms/issues/4847))
- Added the `locale` argument to the `formatDateTime` GraphQL directive. ([#5593](https://github.com/craftcms/cms/issues/5593))

### Changed
- The `QueryArgument` GraphQL type now also allows boolean values.
