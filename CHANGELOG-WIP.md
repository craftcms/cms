# Release Notes for Craft CMS 6.0 (WIP)

### Extensibility
- Added `CraftCms\Cms\Support\Arr`.
- Added `CraftCms\Cms\Support\Str`.
- `craft\services\Elements::stopCollectingCacheInfo()` no longer sets the returned duration to the `cacheDuration` config setting if a duration wasn’t explicitly declared. ([#16796](https://github.com/craftcms/cms/pull/16796))
- Deprecated `craft\helpers\ArrayHelper`. `CraftCms\Cms\Support\Arr` should be used instead.
- Deprecated `craft\helpers\StringHelper`. `CraftCms\Cms\Support\Str` should be used instead.
- Deprecated `craft\services\Composer`. `CraftCms\Cms\Support\Composer` should be used instead.

### Mutex

Craft's Mutex classes have been deprecated. [Laravel's atomic locking](https://laravel.com/docs/12.x/cache#atomic-locks) should be used instead.

- Deprecated `craft\mutex\Mutex`
- Deprecated `craft\mutex\MutexTrait`
- Deprecated `Craft::$app->getMutex()`

### Components
- Deprecated `craft\base\ComponentInterface`. `CraftCms\Cms\Component\Contracts\ComponentInterface` should be used instead.
- Deprecated `craft\base\ConfigurableComponentInterface`. `CraftCms\Cms\Component\Contracts\ConfigurableComponentInterface` should be used instead.
- Deprecated `craft\base\SavableComponentInterface`. `CraftCms\Cms\Component\Contracts\SavableComponentInterface` should be used instead.

### Dashboard & Widgets

#### Controllers
- Removed `craft\controllers\DashboardController`. The following controllers now implement this functionality:
  - `CraftCms\Cms\Http\Controllers\Dashboard\DashboardController`
  - `CraftCms\Cms\Http\Controllers\Dashboard\WidgetsController`
  - `CraftCms\Cms\Http\Controllers\Dashboard\Widgets\CraftSupportController`
  - `CraftCms\Cms\Http\Controllers\Dashboard\Widgets\FeedController`

#### Deprecations
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

#### Events

- Deprecated `craft\services\Dashboard::EVENT_REGISTER_WIDGET_TYPES`. `CraftCms\Cms\Dashboard\Events\RegisterWidgetTypes` should be used instead.
- Deprecated `craft\events\WidgetEvent` in favor of the following new events:
  - `craft\services\Dashboard::EVENT_BEFORE_SAVE_WIDGET` => `CraftCms\Cms\Dashboard\Events\WidgetSaving`
  - `craft\services\Dashboard::EVENT_AFTER_SAVE_WIDGET` => `CraftCms\Cms\Dashboard\Events\WidgetSaved`
  - `craft\services\Dashboard::EVENT_BEFORE_DELETE_WIDGET` => `CraftCms\Cms\Dashboard\Events\WidgetDeleting`
  - `craft\services\Dashboard::EVENT_AFTER_DELETE_WIDGET` => `CraftCms\Cms\Dashboard\Events\WidgetDeleted`
