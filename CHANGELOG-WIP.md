# Release Notes for Craft CMS 4.17 (WIP)

### Administration
- Added the “View user” GraphQL schema option for Craft Solo. ([#17863](https://github.com/craftcms/cms/pull/17863))
- The `clear-cache` command now accepts a space-delimited list of cache IDs that should be cleared.
- Compiled templates are now deleted by the `up` command rather than from `migrate` commands.
- Added the `enableTwigSandbox` config setting. ([#18208](https://github.com/craftcms/cms/pull/18208))

### Development
- Added support for referencing environment variables anywhere within settings that support them (e.g. `foo/$ENV_NAME/bar` or `foo-${ENV_NAME}-bar`). ([#17949](https://github.com/craftcms/cms/pull/17949))
- It’s no longer possible to instantiate objects that don’t extend `yii\base\BaseObject` via the `create()` Twig function. (GHSA-94rc-cqvm-m4pw)
- Added the `uuid()` Twig function.
- The `@parseRefs` GraphQL directive is now optional for each GraphQL schema. (GHSA-7x43-mpfg-r9wj)

### Extensibility
- Added `craft\services\Search::deleteOrphanedIndexJobs()`.
- Added `craft\web\GqlResponseFormatter`.
- Added `craft\web\Response::FORMAT_GQL`.
- Added `craft\web\twig\nodes\BaseNode`.
- `craft\helpers\FileHelper::writeToFile()` now throws an exception if the file path isn’t writable, or there isn’t sufficient free space on the disk. ([#17762](https://github.com/craftcms/cms/pull/17762))
- `craft\helpers\UrlHelper` now encodes square brackets in generated URLs. ([#17840](https://github.com/craftcms/cms/pull/17840))
- `craft\web\Request::accepts()` now accepts wildcard characters (`*`) in the `$contentType` argument, to check for a range of MIME types (e.g. `application/*+json`).
- `craft\web\Request::getAcceptsJson()` now returns `true` for requests with `Content-Type` headers that match `application/*+json`, in addition to `application/json`.

### System
- GraphQL API responses now set their `Content-Type` header to `application/graphql-response+json`.
- GraphQL API responses now set cache headers based on whether a mutation was performed, regardless of the request type.
- Global set queries no longer register cache tags.
- Updated Twig to 3.19. ([#17603](https://github.com/craftcms/cms/discussions/17603))
- Fixed a bug where Table fields with the “Static Rows” setting enabled would lose track of which values belonged to which row headings, if the “Default Values” table was reordered. ([#17090](https://github.com/craftcms/cms/issues/17090))
- Fixed a bug where deadlocks could occur when updating elements’ search indexes. ([#18139](https://github.com/craftcms/cms/pull/18139))
