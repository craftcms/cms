# Running Release Notes for Craft 3.5

### Added
- Added the `drafts`, `draftOf`, `draftId`, `draftCreator`, `revisions`, `revisionOf`, `revisionId` and `revisionCreator` arguments to element queries using GraphQL API. ([#5580](https://github.com/craftcms/cms/issues/5580)) 
- Added the `isDraft`, `isRevision`, `sourceId`, `sourceUid`, and `isUnsavedDraft` fields to elements when using GraphPQL API. ([#5580](https://github.com/craftcms/cms/issues/5580))
- Added the `assetCount`, `categoryCount`, `entryCount`, `tagCount`, and `userCount` queries for fetching the element counts to the GraphPQL API. ([#4847](https://github.com/craftcms/cms/issues/4847))
- Added the `locale` argument to the `formatDateTime` GraphQL directive. ([#5593](https://github.com/craftcms/cms/issues/5593))
- Added `craft/events/RegisterGqlMutationsEvent`.
- Added `craft/gql/Mutation`.
- Added `craft/mutations/Ping`.
- Added `craft/types/Mutation`.

### Changed
- The `QueryArgument` GraphQL type now also allows boolean values.
- `craft\services\Gql` now fires a `registerGqlMutations` event that allows for plugins to register their own GraphQL mutations.
