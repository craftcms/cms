# Craft CMS Import feature — current state on `feature/import`

## 1. What the feature does

Bulk-imports data from a JSON, CSV or XML source (a local file or a URL) into Craft elements
(entries, assets, users, addresses-as-nested-content) or into opted-in Eloquent models. It can
create new records or find and update existing ones, and it understands nested content (Matrix,
Addresses, Content Blocks) to arbitrary depth.

Three ways to drive it: the control panel, import plans registered in code, or the CLI.

---

## 2. Import plans

An **import plan** is a named, handled, ordered list of **steps**
(`src/Import/Data/ImportPlan.php`, service `src/Import/ImportPlan.php`). Each step is one
importer instance with its own `uid`, `type` (the importer FQCN), `source`, `transformer`,
`batchSize` and `settings` (importer-specific settings plus `map`, `matchCriteria`,
`clearableItems` and, for elements, `site`, `fieldLayout`, `keepMissingNestedElements` and
`fieldSettings`). Two flavours:

- **Editable** — created in the CP, stored in `import_plans`.
- **Non-editable** — registered in code from a service provider's `boot()` via
  `ImportPlan::register()`. They're never stored, and since they live outside the config,
  `artisan config:cache` isn't affected by their closures.

`ImportPlan::isEditable()` reads the `$editable` flag, which is set for plans loaded from the
database. `ImportPlan::getAllImportPlans()` (`src/Import/ImportPlan.php:128`) lists DB plans
first, in their saved `sortOrder` (then name, then handle), followed by registered plans sorted
by name. The result is keyed by handle and memoized. Registered plans are created lazily, the
first time the list is built, and re-created whenever it's rebuilt after a save, delete or
reorder. An invalid registered plan is **skipped and logged** as a warning rather than blowing up
the list.

New and duplicated editable plans are appended to the end of the order (`nextSortOrder()`);
`reorderImportPlans(array $uids)` saves a new order.

### Registering an import plan

Register a handle with a factory that returns a configured import plan. Steps can be given as
importer instances or as step arrays:

```php
public function boot(ImportPlan $importPlans): void
{
    $importPlans->register('entriesFromFile', fn () => new ImportPlanData()
        ->name('Entries from file')
        ->steps([
            EntryImporter::create()
                ->source('resources/import/entry-with-plain-text.json')
                ->site('default')
                ->section('news')
                ->matchCriteria(['title' => 'title']),
        ]));
}
```

`register()` only stores the factory; it's called through the container when the list is built,
so it can type-hint dependencies. The registered handle is the plan's handle: a plan that sets
none gets it, and one that sets a different handle is skipped and logged. Registering a handle
twice throws an `InvalidArgumentException`; `remove(...$handles)` unregisters plans (e.g. to
replace one). Registered steps typically set no `->map()` and no field layout; the transformer
(or the per-row `type`/`sectionId` data) does that work, which is why one step can span multiple
sections and entry types.

### What's persisted for an editable plan

`steps` is stored as JSON, one entry per step, built by `Data\ImportPlan::serializeSteps()` from
each importer's `toImportStep()` (which uses `BaseImporter::toArrayData()`). On load,
`ImportPlan::createImporter()` instantiates `$step['type']`, and `BaseImporter::__construct()`
applies `uid`, `source`, `transformer` and `batchSize`, then replays each `settings` key through the
importer's setter of the same name. Only settings declared in the importer's `getSettingsRules()`
(`settings.<name>`) and backed by an existing setter are applied; any other key is ignored, since
step config can come from a request. An element importer left without a field layout then gets
one from `resolveDefaultFieldLayout()`. Steps whose importer can't be created are left out. The target class is fixed by the importer
subclass itself, so it isn't stored.

### Sources

A step's `source` is a `@root`-relative path, an aliased path (`@root/...`, `@storage/...`) or
an http(s) URL, resolved by `BaseImporter::resolvedSourcePath()`. Absolute filesystem paths are
rejected.

`BaseImporter::sourceError()` (used by the `source` validation rule) checks that:

- the alias is defined;
- the value isn't some other scheme (`data:`, `phar://`, …), a dotfile, or inside a restricted
  directory;
- a local file exists and is readable, its extension is a registered data type, and its contents
  match that type (CSV also accepts content detected as `txt`). Content type detection only
  looks at the first few MB, so a large JSON file detected as `text/plain` still passes if it
  starts with `[` or `{` (after an optional BOM; `startsLikeJson()`).

URLs are checked with `BaseImporter::urlValidator()`, which rejects URLs resolving to private IP
addresses unless the host is listed in the `allowedPrivateHosts` general config setting
(exact hostnames or `*.` wildcards). Loopback, link-local and cloud metadata addresses are always
rejected. The download
(`downloadFile()`) pins the connection to the validated IPs to guard against SSRF and DNS
rebinding, and doesn't follow redirects. A remote file's data type comes from the response's
`Content-Type`, falling back to the URL path's extension, and its contents are then checked like
a local file's.

Registered plans are validated every time they're loaded, so their URLs are checked without
resolving the hostname (`resolveHost: false`); the import job validates the step fully before
downloading. `withLocalFile(Closure $callback)` hands a local path to one-off callers (the CLI,
mapping), downloading a remote source to a temp file and deleting it afterwards.

---

## 3. Running an import plan

`Import::dispatchImport()` (`src/Import/Import.php:123`) generates a `runId`, builds one
`Import` job per step, fires `ImportDispatching` (cancelable), then dispatches a single
`ImportPipeline` job so the whole plan shows as one named queue item. `ImportPipeline` wraps
each step in its own `Bus::batch(...)->allowFailures()` and chains them, followed by a
`FinishImport` job, so steps run sequentially and one bad step doesn't kill the plan. The
batches and the chain go onto `Cms::config()->queueName`. `ImportDispatched` fires afterwards.

Each `Import` job carries the import plan as it was at dispatch, rather than looking it up again.
Changes an `ImportDispatching` listener makes to `$importPlan` reach the jobs; replacing it with a
different object doesn't.

An `Import` job processes up to the step's `batchSize` items (default 100; **0 disables
batching** and does everything in one job), and re-adds itself to the batch for the remainder.
It also stops early, leaving the rest to the next chunk, when the next item could take it past
the memory limit or the timeout. Jobs aren't retried (`$tries = 1`, `$failOnTimeout = true`),
since a retried chunk would import its items again. A canceled batch skips its remaining jobs
(`SkipIfBatchCancelled`), and between items the job stops if its queue progress entry has been
removed (`Job::shouldStillRun()`), i.e. the job was canceled from the queue UI. Progress is reported
as "n of total" items. Per-item failures (any `Throwable`) are logged with the exception and
skipped, not fatal.

The first chunk of a step runs the step's full `validate()` and parses the source; a URL source
is downloaded to Craft's temp folder, parsed and deleted straight away. If the source has more
than `batchSize` items, the first chunk stores all of them (its own included) on the import disk
(the `importDisk` general config setting, `craft-tmp` by default) in pieces of `batchSize` items,
at `imports/{runId}/{stepUid}/{n}.json` starting from `0.json` (`Import::spoolPath()`). Later
chunks only run `validateSettings()` and read just their own pieces, so they don't need to run on
the same server as long as the disk is shared. Otherwise nothing is stored: with batching
disabled, or when a chunk of a source that fits in one batch stops early on the memory or time
limit, the follow-up job parses the source again (downloading a URL source again).

Each step batch's `finally` deletes that step's stored items (even if the batch was canceled),
and `FinishImport` deletes the run's directory (`FinishImport::spoolPath()`). The
`RemoveStaleImportData` garbage collection action removes run directories whose files haven't
changed in 7 days, and empty run directories regardless of age.

### Run lifecycle events

| Event | Fired | Extra payload |
| --- | --- | --- |
| `ImportStarted` | when `ImportPipeline` starts | `steps` |
| `ImportStepStarted` | by a step's first chunk | `step` |
| `ImportChunkStarted` | before each chunk | `step`, `offset`, `limit` |
| `ImportChunkFinished` | after each chunk | `step`, `offset`, `count`, `hasFailures` |
| `ImportStepFinished` | in the step batch's `finally` | `step`, `hasFailures` |
| `ImportFinished` | by `FinishImport` | `steps`, `hasFailures` |

All of them carry `importPlan` and `runId`. Failures are tracked across jobs through cache flags
(`FinishImport::hasFailuresCacheKey()` / `stepHasFailuresCacheKey()`). The CLI fires
`ImportStarted`, `ImportStepStarted`, `ImportStepFinished` and `ImportFinished` with a `null`
`importPlan`, but no chunk events.

---

## 4. Importers

Every importer extends `BaseImporter`, whose abstract `targetClass()` names what it imports
into. Each importer also owns its settings: `settingsUi()`, `refreshSettingsUi()`,
`storeSettings()`, `getSettings()` and `getSettingsRules()` drive the step slideout in the CP
and its validation. `settingsUi()` returns a list of [UI](ui.md) nodes, which the step's UI
wraps in a `settings` `Scope`.

- **`ElementImporter`** is abstract. Each importable element type is represented by its own
  concrete subclass: `EntryImporter`, `AssetImporter`, `UserImporter` are the built-ins (there's
  no `UserTransformer`, so `UserImporter` uses the default `ElementTransformer`). Adds `site`
  (required), `fieldLayout`, `keepMissingNestedElements` and `fieldSettings`; `EntryImporter`
  adds `section` and `entryType`, `AssetImporter`
  adds `volume`, and `UserImporter` resolves its fixed layout via `resolveDefaultFieldLayout()`.
  An element type is importable if and only if an `ElementImporter` subclass is registered for
  it — `Address` has none, so it's never a standalone import target, only reachable as nested
  content (Addresses field / User addresses container). `UserImporter::setAttributesForImport()`
  drops `User::SENSITIVE_ATTRIBUTES` (e.g. `admin`, `password`) apart from
  `IMPORTABLE_SENSITIVE_ATTRIBUTES` — `affiliatedSiteId`, `email`, `passwordResetRequired` and
  `photoId` — none of which grant any privileges. A non-numeric `photoId` is treated as a URL or
  local path (same rules as an Assets field's files, see §7) and saved with
  `Users::saveUserPhoto()` in an `afterItemImported()` callback; group assignment (`groups`, Pro
  only) runs in that callback too.
- **`ModelImporter`** is abstract too, and works exactly the same way: each importable Eloquent
  model is represented by its own concrete subclass. `SystemMessageImporter` is the only
  built-in. A model is importable if and only if a `ModelImporter` subclass is registered for
  it — there's no marker interface. A subclass can set default match criteria in its
  constructor (`SystemMessageImporter` matches on `key` + `language`, since incoming data won't
  carry Craft's IDs).

No importer sets default match criteria in the base classes — with none set, everything is
imported as new.

`importItem(array $data): ElementInterface|Model|null` returns the element or model the data was
imported into.

Extra importer types — including importers for plugin-defined element types and models — register
with the `Import\ImporterTypes` registry from a service provider's `boot()`
(`$importerTypes->register(MyImporter::class)`).

---

## 5. Per-item pipeline

`Import::importItem()` (`src/Import/Import.php:174`):

1. `ItemImporting` event (cancelable, can rewrite `$data`).
2. Apply the map via `ImportHelper::remapData()` — only if a map is set.
3. Collect `additionalMatchCriteria()` from the transformer.
4. Resolve match criteria.
5. Apply clearable items.
6. Hand to the importer's `importItem()`.
7. Run the importer's queued `afterItemImported()` callbacks with the imported item (they're
   discarded if step 6 throws).
8. `ItemImported` event, carrying the returned `importedItem`.

Both item events also carry the `runId` (`null` outside a run).

### Match criteria

Resolution recurses through containers: rows carrying a `type` (Matrix blocks) look
their criteria up under that type; untyped rows (addresses) use the container's criteria
directly. It deliberately walks keys present in *either* the criteria or the data, so
inline criteria on a container the step never configured still resolve.

`matchCriteria` set to `null` means don't match at all — import everything as new.

### Clearable items

A tree of truthy leaves mirroring the match-criteria shape (a flat dot-notation list
is accepted and expanded). Behavior:

- Marked clearable + value missing/empty → forced to `null` so it's explicitly applied.
- **Not** marked clearable + value empty → the key is `unset()` entirely, so the
  existing value is left untouched.

"Empty" means null, whitespace-only string, or `[]`.

---

## 6. Saving an element

`ElementImporter::importItem()` (`src/Element/Import/ElementImporter.php`):

1. `getRootElement()` calls `$this->prepareNewRootElementForImport($data)` on the importer to get
   a new element instance (per-type subclasses like `EntryImporter`/`AssetImporter` resolve the
   entry type/volume here; `EntryImporter` also sets a Structure section's `structureId`, so
   `parentId` can be applied). If there are match criteria, it then queries with
   `->drafts(null)->status(null)`, lets the importer adjust the query
   (`$this->prepareRootElementImportQuery($element, $query)`) — scoping it by type/volume, for
   example — typecasts criteria, and **forces the step's siteId** so it always wins over
   anything in the criteria. The match is returned as is, falling back to the new element
   (`$query->one() ?? $element`).
2. `markAsImporting()` — for existing and new elements alike.
3. Transformer runs *after* the element is resolved, so it can see the existing element
   via Fractal meta.
4. Split the result into native attributes, custom fields, and container properties. The
   reserved keys (`matchCriteria`, `clearableItems`, `keepMissingNestedElements`) are never
   treated as fields. Attributes are applied via
   `$this->setAttributesForImport($element, $attributes, $data)` on the importer (base implementation
   strips `id`/`uid` and applies the rest via `setAttributesFromRequest()`; `AssetImporter`
   overrides it to resolve filename/folder/temp-file handling and download).
5. **Skip-unchanged optimization**: snapshots attribute values and serialized field
   values (only for handles in the field layout); if nothing changed, the element is never saved. Skipped for new elements or
   when container data is present. A special case catches content blocks, whose
   `serializeValue()` returns null while the block has no id.
6. Validation scenario: `SCENARIO_LIVE` when `enabled && getEnabledForSite()`,
   otherwise `SCENARIO_ESSENTIALS`.
7. Enable keep-flags, save, restore flags in a `finally`.
8. Return the element.

Container *properties* (as opposed to fields) go through `ImportableContainerPropertiesInterface`:
`getDestinationColsForProperty()` supplies the mapping panel's columns, and `importIntoContainerAttribute()`
imports the data — only `User` implements it, for `addresses`.

---

## 7. Making things importable

### Elements

`Element` itself is deliberately thin here — it provides `markAsImporting()`, which sets
`public private(set) bool $importing`. Its only reader is `Entry::setAttributesFromRequest()`,
where it skips the `canChangeAuthor()` permission check so an import can set entry authors.

The hooks that customize how a type participates in import instead live on the `ElementImporter`
subclass for that type (see §4):

- `targetClass()` — abstract; names the target element type.
- `getDefaultTransformer()` — `ElementTransformer` by default; `EntryImporter` →
  `EntryTransformer`, `AssetImporter` → `AssetTransformer`. There is no `UserTransformer`, so
  `UserImporter` uses the default.
- `prepareNewRootElementForImport(array &$data, ?ElementInterface $element = null):
  ElementInterface`, `prepareRootElementImportQuery()`, `setAttributesForImport()` — per-type
  hooks. `EntryImporter` resolves the section and entry type (from its `section`/`entryType`
  settings, or from the row's `sectionId`/`typeId`) and scopes the match query by type; in
  `setAttributesForImport()` a configured `fieldLayout` keeps `typeId` from being overwritten, and
  a Structure `parentId` is resolved by ID or by title/slug within the section and site
  (`normalizeParent()`). `AssetImporter` resolves the volume from `fieldLayout` and does the heavy
  filename/folder/temp-file work in `setAttributesForImport()`.
- `isPropertyAvailable(array $prop): bool` — filters the importable properties
  (`getAvailableProperties()`) down to the ones the mapping screen should offer for the current
  settings. The base implementation allows properties not declared in a trait, plus `HasStatuses`
  ones (`enabled`); `EntryImporter` also allows `Structurable` properties (`parentId`) when the
  section is a Structure; `UserImporter` only offers `groups` on Pro, `affiliatedSiteId` on
  multisite and `photoId` when a user photo volume is set.

### The `Importable` attribute

`CraftCms\Cms\Support\Attributes\Importable`:

```php
__construct(
    public string $name,
    public string $label,
    public bool $excludeFromUiMapping = false,
    public bool $isContainer = false,
    public bool $canBeMatchCriteria = true,
    public bool $canBeCleared = true,
    public bool $canBeSet = true,
)
```

Both `name` and `label` are **required** positionally. Examples: `id`/`uid` are
`canBeCleared: false, canBeSet: false` (so they can only be used for matching); `Entry::$_typeId` is `#[Importable('typeId', 'Type ID', true)]`
(excluded from UI mapping since the field-layout-provider step covers it);
`User::$_addresses` is the only `isContainer: true` property; an element type with one must implement
`ImportableContainerPropertiesInterface`.

An attributed property declared in a trait (e.g. `Structurable::$_parentId`, which every element
inherits) is only offered on the mapping screen when the importer's `isPropertyAvailable()`
allows it, since a trait used by `Element` doesn't apply to every element type or section.
Properties declared directly on a class are offered unless the importer's `isPropertyAvailable()`
says otherwise (and subject to `excludeFromUiMapping`).

### Field layout elements

`ImportableFieldLayoutElementInterface` — `getFieldsForMapping()`,
`canBeMatchCriteria()`, `canBeCleared()`, `getImportMappingExtraSettings()` (default `[]`). `getFieldsForMapping()` returns an
`Import\Data\MappingColumn`, a `CompoundMappingColumn` (several columns under one heading,
e.g. lat/long), or `null` to offer no column; `MappingColumn::make()` derives a column's path
and input names from its prefixed handle. The default trait returns `false` for both,
so this is opt-in per layout element — the opposite default to the attribute.
Implementors: `TitleField`, `CustomField`, `FullNameField`, `UsernameField`,
`EmailField`, `AffiliatedSiteField`, `AltField`, and the Address layout elements.
`User::$email` is deliberately not attributed — it comes in via `EmailField`.

### Container fields

`ImportableElementContainerFieldInterface` adds `normalizeNestedEntryForImport()`,
`getMappingUiPrefix()`, `validateMapping()`, `canKeepMissingNestedElements()`,
`setKeepMissingNestedElements()`. Used by `Matrix`, `Addresses`, `ContentBlock`.
Matrix and Addresses can keep missing nested elements; ContentBlock can't (single block).

`FieldInterface::normalizeValueForImport($value, $importer, $rootOwner, $importSettings)` — base returns
the value unchanged. Overridden by `BaseRelationField` (returns `[]` rather than null so
relations actually clear), `Matrix`, `Addresses`, `ContentBlock`. Core always calls it through
`ImportHelper::normalizeFieldValueForImport()`, which then runs the field's registered import
handler, if it has one (see below). Inside nested
elements (via `normalizeNestedEntryForImport()`), container fields are only normalized for array
values and other fields only for non-null ones, so nothing nested gets cleared by a missing value.

`$importSettings` is the field's branch of the importer's `fieldSettings` tree, which mirrors
`map`'s shape (`['myMatrix' => ['someEntryType' => ['fields' => ['photos' => [...]]]]]`). A field
type offers settings in the mapping UI through its import handler's `mappingSettings()` (see
below), which returns a list of `Import\Data\FieldMappingSetting`; `CustomField::getImportMappingExtraSettings()`
returns them, and `CustomField::getFieldsForMapping()` passes them on as the column's `importSettings`.

### Field import handlers

Import-specific work for a field type can live outside the field, in a class implementing
`Import\FieldHandlers\FieldImportHandlerInterface`. The field needs no import code at all.
- `normalizeValue()` runs after the field's own `normalizeValueForImport()`.
- `mappingSettings()` returns extra per-field settings for the mapping UI, as a list of
  `Import\Data\FieldMappingSetting`; a setting's `instructions` show in an info tooltip beside its label.

A handler does its work immediately, as the value is normalized: `normalizeValue()` gets the
importer, the owner (the existing element or nested entry, or `null`/a new element) and the field's
`fieldSettings` branch, and returns what the field should be given. Side effects that should only
happen if the row goes through (replacing a file, calling an external service) can be queued with
`$importer->afterItemImported(fn ($item) => ...)`. `Import::importItem()` runs the queued callbacks
with the imported element or model once the item has been imported (saved, or skipped as unchanged),
before `ItemImported` is dispatched, and discards them if importing the item throws.

Core registers `AssetsFieldImportHandler` for `Assets`. Plugins add their own like data types and importers,
from a service provider's `boot()`:
```php
public function boot(FieldImportHandlers $handlers): void
{
    $handlers->register(MyFieldImportHandler::class);
}
```

A handler names the field class it handles with its static `fieldClass()` method, and handlers are
keyed by it, so a class has at most one. A field uses the handler for its own class or, failing that,
its nearest parent class with one (a subclass of `Assets` gets `AssetsFieldImportHandler`).
Registering a second handler for the same class throws; `remove()` the existing one first to replace it.

### Creating assets from incoming files

Handled by `Asset\Import\AssetsFieldImportHandler`. An Assets field value can mix asset IDs with
file references — absolute URLs or local paths. A local path must be within Craft's temp folder,
or within the project root but outside the storage folder, and can't be a dotfile, inside a
dot-prefixed folder or in a restricted directory (`AssetsHelper::resolveImportFilePath()`). Local
paths are refused altogether when the step's source is remote, since the remote source's author
could otherwise point them anywhere; `AssetImporter` applies the same rules. Remote files are
downloaded through `RemoteFileDownloader`, capped at the max asset upload size. Files are turned into assets as the value is normalized, in the same upload
location as a file dropped onto the field (the default upload location, or the restricted one), and
the field gets plain asset IDs, so change detection works as usual. Incoming order is kept.

When an incoming file's name matches an existing asset in that folder, the `fileConflict`
setting (`ImportFileConflict`) decides what happens: `useExisting` (default; the file isn't
downloaded), `replace` (the existing asset is related straight away, but its file is only replaced
via an `afterItemImported()` callback, so not at all if the row fails, and still when the row is
otherwise unchanged), or `keepBoth` (a new asset with a suffixed filename). A URL without a file
extension is downloaded first and gets its extension from the response's content type; URL
filenames are URL-decoded. If the upload location can't be resolved yet (e.g. a `{id}` subpath on a
new entry), files go to the temp folder as new assets and the field moves them into place after the
save, as with drag and drop. The upload location is resolved from the owner's state before the import,
so a dynamic subpath that depends on a value the import changes (e.g. `{slug}`) uses the old value.
A file that can't be fetched or isn't allowed is logged to the import log and skipped; the rest of
the row still imports. New assets for a row that then fails to save are left in place.

---

## 8. Nested data formats

Matrix itself accepts the flat and the `sortOrder`/`entries` shapes. The grouped-by-type shape is
converted into the flat one by `ImportHelper::remapData()` (`src/Support/ImportHelper.php`), so it
only works when the step has a map with per-type submaps; without one (e.g. from the CLI) its rows
carry no `type` and are dropped. Grouped rows come out in the map's type order.

**Flat, own-type-per-row** — order preserved:
```json
"myMatrix": [
  {"type": "blockEt", "title": "block 1", "fields": {"plainText": "one"}},
  {"type": "blockEt", "title": "block 2", "fields": {"plainText": "two"}}
]
```

**Grouped by type** (needs a map) — keyed by entry type handle, rows carry no `type`:
```php
'myMatrix' => [
    'secondEt' => [['title' => 'block 1', 'plainText' => 'foo']],
    'firstEt'  => [['title' => 'block 2', 'plainText' => 'bar']],
]
```

**Explicit `sortOrder`/`entries`**:
```php
'myMatrix' => [
    'sortOrder' => ['new:1', 'new:2'],
    'entries' => ['new:1' => [...], 'new:2' => [...]],
]
```

Notes:
- A block with no `type`, a type not allowed in the field, or nothing but `type`/`matchCriteria`
  is **silently skipped**.
- The `fields` wrapper is optional — `normalizeNestedEntryForImport()` (from the
  `ImportableElementContainerField` trait) moves custom field handles into `fields` when it's absent.
- Blocks are matched only when the owner already has an id, using the block's own
  `matchCriteria`, scoped by field id, owner id and site.
- Blocks absent from the incoming data are pruned by the normal nested save, unless kept.

### Keeping missing nested elements

`keepMissingNestedElements` is a tree mirroring the match-criteria shape, where each
container's own decision lives under a reserved **`__keep__`** leaf alongside its
children:

```php
['outerMatrix' => [
    '__keep__' => true,
    'someEntryType' => ['fields' => ['innerMatrix' => ['__keep__' => false]]],
]]
```

A flat list of (dot-notation) handles is accepted too and expanded into `__keep__: true` leaves.
Opt-in per field, per level — an outer field can keep while an inner one prunes. Only Matrix and
Addresses support it. Pruning is
Craft's normal save behavior, so it isn't tracked or logged.

---

## 9. Control panel

Nav: **Import**, in the Administration group. Permissions group `import`: `viewImportPlans`
(→ `saveImportPlans`, `deleteImportPlans`, `triggerImportPlans`). `triggerImportPlans` shows a
warning that import plans can create and update data regardless of the user's other permissions.

Screens are Vue/Inertia under `resources/js/pages/import/`, backed by `ImportPlansController`:

- `import` — two tables, editable and registered (non-editable). Editable rows get Edit, plus Run (with
  `triggerImportPlans`), Duplicate (with `saveImportPlans`) and Delete (with `deleteImportPlans`);
  registered rows get Run only (with `triggerImportPlans`). Run queues the plan via `dispatchImport()`. With
  `saveImportPlans`, the editable table can be reordered by drag and drop (`import/reorder`,
  rolled back client-side if the request fails).
- `import/new|{handle}` — name, handle, description, then the **Steps** list
  (`modules/import/steps/StepList.vue`). Each step opens a slideout (`StepSlideout.vue`), with a
  loading state on the button while it opens: importer type (reactive select, with a spinner
  while the UI for a new type loads), the importer's own settings, **Data Source**
  (a path, alias or URL), transformer and batch size, plus a **Mapping** section with an
  "Edit mapping" button. Choosing the importer type for an element import (Entries / Assets /
  Users) *is* choosing the element type — there's no separate "Element Type" field. A step is
  validated (`import/validate-step`) before its slideout can close, and the unsaved-changes
  prompt only appears if something actually changed (`dirtyState()` in
  `modules/import/mapping/paths.ts`).
- The "Edit mapping" button is shown as soon as the step has a type, and disabled until the step
  can be mapped (`canMap`: it knows what it imports into and its source passes `sourceError()`).
  The source is re-checked when its field loses focus, and any problem is shown under the field.
  The file is only downloaded and parsed once the mapping is opened; a read error at that point
  is shown under the source field too, until the source changes.
- The mapping table: **Destination / Incoming data / Match / Clear**. The incoming-data select
  is a combobox that shows each column's first-row value as a hint, and the most likely
  column is pre-selected (`ImportHelper::suggestMapValues()`). Attributes with
  `canBeSet: false` are match-only. Container rows show a "Map field" button opening a nested
  slideout instead of a select; the slideout has the "Keep existing nested elements missing
  from the imported data" checkbox and one section per field-layout provider, and nests
  arbitrarily deep. All four trees are held client-side and saved with the step.
- The mapping endpoints (`ImportPlansController::mappingImporter()`) only build the posted draft
  step for users with `saveImportPlans`. Anyone else gets the saved step with the posted step's
  UID, so they can't make the server fetch a source of their choosing.

Read-only: users without `saveImportPlans` see the forms in read-only mode, without the
Save/Delete/Run actions; the mapping button reads "View mapping" and steps aren't validated on
close (`import/validate-step` requires `saveImportPlans`). It isn't tied to `allowAdminChanges`.
Running plans is gated only by `triggerImportPlans`.

There is **no logs/history screen** — logging goes to a daily `import` channel at
`storage/logs/import.log` (debug, 14 days), registered only if the app hasn't already
defined one.

---

## 10. CLI

One command per importer, each extending the abstract `CraftCms\Cms\Import\Commands\ImportCommand`:

```
craft:import:entries {source} [--site=] [--transformer=] [--match-criteria=] [--section=] [--entry-type=]
craft:import:assets {source} [--site=] [--transformer=] [--match-criteria=] [--volume=]
craft:import:users {source} [--site=] [--transformer=] [--match-criteria=]
craft:import:system-messages {source} [--transformer=] [--match-criteria=]
```

Aliases: `import/entries`, `import/assets`, `import/users`, `import/system-messages`. `{source}` is an
aliased or `@root`-relative path, or a URL, and is prompted if missing, as are any missing
options; `--site` is only added for element importers and only prompted on multisite.
`--match-criteria` is JSON (a leading `=` is stripped; invalid JSON is silently treated as no
criteria).

A command builds a one-off importer via `ImportPlan::createImporter()`, validates it (printing
errors per attribute and failing if it's invalid), reads the data via `withLocalFile()` (so a
URL is downloaded to a temp file), and imports every item synchronously — it doesn't go through
a plan or the queue. It fires the run lifecycle events (see §3) without an import plan. To add a
command for your own importer, extend `ImportCommand`, implement `importerClass()`, add any extra
options in `configure()` and their prompts via `getAdditionalOptions()`, and register it with
`$this->commands()`. Options are kebab-case and applied through the importer setter of the same
camel-cased name (`--entry-type` → `entryType()`); `getAdditionalOptions()` is keyed by setter name.

No command takes a `--map`, so CLI mapping is the transformer's job.

---

## 11. Extension points

Registries (register from a service provider's `boot()`; see `Component\TypeRegistry`):

- `Import\DataTypes\DataTypes` — `$dataTypes->register(MyType::class);` (extension-keyed).
  Built-ins: json, csv, xml. A data type implements three **static** methods,
  `extension()`, `format()` and `getHeadings()`. To replace a built-in, `remove()` it first.
- `Import\ImporterTypes` — `$importerTypes->register(MyImporter::class);`. Registering a new
  importable element type or model means contributing a concrete `ElementImporter` or
  `ModelImporter` subclass that implements `targetClass()`.
- `Import\FieldHandlers\FieldImportHandlers` — `$handlers->register(MyFieldImportHandler::class);`
  (keyed by the handler's `fieldClass()`; see §7).
- `Import\ImportPlan` — `$importPlans->register('myHandle', fn () => new ImportPlanData()...);`
  registers a non-editable import plan (keyed by handle; see §2).
- `ItemImporting` / `ItemImported` (the latter with `$importedItem`),
  `ImportPlanSaving` / `ImportPlanSaved`, `ImportDispatching` / `ImportDispatched`.
  The `*ing` variants are cancelable: a listener sets `$event->isValid = false` (`ValidatableEvent`).
- Run lifecycle: `ImportStarted`, `ImportStepStarted`, `ImportChunkStarted`,
  `ImportChunkFinished`, `ImportStepFinished`, `ImportFinished` — all keyed by `runId`
  (see §3).
- Transformers: extend `ElementTransformer` (or the per-type one) and override
  `transform()`; or implement `additionalMatchCriteria()`. Transformers can also be
  given as a class string.
  `ElementTransformer` also supports convention-based `normalize{PropName}()` methods, and
  auto-matches incoming keys that don't match exactly (`ImportHelper::prepKeyForAutoMatching()`,
  e.g. `Plain Text` / `plain_text` → `plainText`).
- General config: `importDisk` (where multi-chunk steps store their parsed items; see §3) and
  `allowedPrivateHosts` (hosts a source URL may resolve to private IPs for; see §2).
