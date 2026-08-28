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
