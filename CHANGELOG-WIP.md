# Release Notes for Craft CMS 4.17 (WIP)

### Extensibility
- Added `craft\web\GqlResponseFormatter`.
- Added `craft\web\Response::FORMAT_GQL`.
- Added `craft\web\twig\nodes\BaseNode`.
- `craft\web\Request::accepts()` now accepts wildcard characters (`*`) in the `$contentType` argument, to check for a range of MIME types (e.g. `application/*+json`).
- `craft\web\Request::getAcceptsJson()` now returns `true` for requests with `Content-Type` headers that match `application/*+json`, in addition to `application/json`.

### System
- GraphQL API responses now set their `Content-Type` header to `application/graphql-response+json`.
- Updated Twig to 3.19. ([#17603](https://github.com/craftcms/cms/discussions/17603))
