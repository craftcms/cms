# Entry Templates

## Added

**New class or service:**
```
- Added `craft\services\Icons`. ([#20277](https://github.com/craftcms/cms/pull/20277))
```

**New event class:**
```
- Added `craft\events\DefineElementDeletionBlockersEvent`. ([#18728](https://github.com/craftcms/cms/pull/18728))
```

**New event constant:**
```
- Added `craft\base\Element::EVENT_DEFINE_DELETION_BLOCKERS`. ([#18728](https://github.com/craftcms/cms/pull/18728))
```

**New method, property, or constant:**
```
- Added `craft\services\Users::destroyOtherSessions()`.
- Added `craft\base\ElementTrait::$applyingDraft`. ([#18057](https://github.com/craftcms/cms/pull/18057))
- Added `craft\services\Elements::REF_TAG_PATTERN`.
```

**New console command:**
```
- Added the `mcp/serve` command. ([#20145](https://github.com/craftcms/cms/pull/20145))
```

**New config setting:**
```
- Added the `enableTwigSandbox` config setting. ([#18208](https://github.com/craftcms/cms/pull/18208))
```

**New Twig function or variable:**
```
- Added the `svg()` Twig function.
```

**Multiple related additions (with sub-list):**
```
- Added the following GraphQL scopes:
  - `craft\services\Gql::SCOPE_READ`
  - `craft\services\Gql::SCOPE_WRITE`
```

## Fixed

**Bug fix:**
```
- Fixed a bug where something wasn't working properly. ([#12345](https://github.com/craftcms/cms/pull/12345))
- Fixed a bug where Matrix fields in Blocks view could lose their existing values when they became editable.
```

**Error fix:**
```
- Fixed an error that could occur when editing an element with a Table field. ([#18408](https://github.com/craftcms/cms/pull/18408))
- Fixed an error that occurred when creating a new element on multi-site installs. ([#18393](https://github.com/craftcms/cms/pull/18393))
```

**JavaScript fix:**
```
- Fixed a JavaScript error that occurred if a Matrix field's label was hidden. ([#18366](https://github.com/craftcms/cms/pull/18366))
- Fixed potential JavaScript errors that could occur if a disclosure menu's trigger was missing. ([#18358](https://github.com/craftcms/cms/pull/18358))
```

**Security fix:**
```
- Fixed a [high-severity](https://github.com/craftcms/cms/security/policy#severity--remediation) RCE vulnerability. (GHSA-fp5j-j7j4-mcxc)
- Fixed a [moderate-severity](https://github.com/craftcms/cms/security/policy#severity--remediation) SSTI vulnerability. (GHSA-qc86-q28f-ggww)
- Fixed [low-severity](https://github.com/craftcms/cms/security/policy#severity--remediation) XSS vulnerabilities. (GHSA-4mgv-366x-qxvx)
```

**Styling fix:**
```
- Fixed a styling issue with slideouts within Live Preview. ([#18383](https://github.com/craftcms/cms/pull/18383))
```

## Deprecated

**Class or method replaced:**
```
- Deprecated `craft\helpers\ElementHelper::renderElements()`. `craft\helpers\Template::render()` should be used instead. ([#19988](https://github.com/craftcms/cms/pull/19988))
- Deprecated `craft\services\Path::getRuntimePath()`. `Craft::$app->getRuntimePath()` should be called instead.
```

**Config setting deprecated:**
```
- Deprecated the `disableGraphqlTransformDirective` config setting.
```

## Removed

```
- Removed `craft\controllers\AppController::actionResourceJs()`. ([#18559](https://github.com/craftcms/cms/pull/18559))
```

## Improved

```
- Improved the performance of `craft\helpers\Typecast`. ([#18426](https://github.com/craftcms/cms/pull/18426))
- Improved the accessibility of user permission lists. ([#18290](https://github.com/craftcms/cms/pull/18290))
- Improved drag-n-drop performance. ([#18019](https://github.com/craftcms/cms/pull/18019))
```

## Updated

```
- Updated Twig to 3.30.
- Updated Fabric.js to v7. ([#19628](https://github.com/craftcms/cms/pull/19628))
- Updated the built-in composer.phar to 2.11.2. ([#20742](https://github.com/craftcms/cms/pull/20742))
```

## Behavioral Changes (no action verb prefix)

```
- `craft\elements\db\NestedElementQueryTrait::owner()` now accepts an array of owner elements.
- `craft\services\Elements::stopCollectingCacheInfo()` no longer sets the returned duration to the `cacheDuration` config setting if a duration wasn't explicitly declared. ([#16796](https://github.com/craftcms/cms/pull/16796))
- Element indexes now show "Paste" buttons alongside bulk element action buttons. ([#18427](https://github.com/craftcms/cms/pull/18427))
- MCP upload URLs now respond with JSON errors. ([#20578](https://github.com/craftcms/cms/pull/20578))
```

## Feature Descriptions (no code reference)

```
- Nested entries' edit screens now have a "Field settings" action menu item.
- Bulk element actions are now available on element indexes for mobile devices.
- Revisions now keep track of which element attributes/fields were modified for the revision.
```
