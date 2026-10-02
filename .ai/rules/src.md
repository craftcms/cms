---
paths:
  - 'src/**'
---

# Src

## Use Craft content APIs
Query content through elements and element queries when an element type exists. Use `CraftCms\Cms\Database\Table` constants and preserve the base `elements` and `elements_sites` tables.

## Keep Craft behavior in Craft
Put Craft-specific settings on `GeneralConfig`. Use Laravel events in core and place Yii event constants and bridges in `yii2-adapter`.

## Follow Craft service and translation conventions
Use `t()` with replacement parameters second and the category third or named. Mark singleton or request-scoped services with Laravel's `#[Singleton]` or `#[Scoped]` attributes.

## The CP's site is RequestedSite, never getCurrentSite()
`Sites::getCurrentSite()` always returns the **primary** site on a control panel request — `ResolveSite` middleware skips CP requests entirely. The site the CP is actually working with comes from `RequestedSite::get()` (the `?site=` handle, held to `getEditableSiteIds()`).

Element queries default their `siteId` to `getCurrentSite()`, so a CP view model that doesn't set one explicitly lists primary-site content regardless of `?site=`. `ContentIndexViewModel::site()` does this correctly — copy it.

The element selector modal posts its index params in the body, so honour an explicit `site` input before falling back to `RequestedSite`, which only reads the query string.

## Nested index settings and action limits

`NestedElementManager` initializes nested index display settings. The frontend retains and posts them on subsequent requests. Do not require fields or owner elements to rebuild that configuration. The frontend checks whether a whole selection fits `maxElements`. Resolve owner scope and permissions on the server. Enforce authoritative limits through existing element or field policies and field validation, as in 5.x. Do not store display limits in the session or introduce a second batch-limit contract.
