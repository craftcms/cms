# Craft CMS Import feature — current state on `feature/import`

## 1. What the feature does

Bulk-imports data from a JSON, CSV or XML file into Craft elements (entries, assets, users,
addresses-as-nested-content) or into opted-in Eloquent models. It can create new records or find
and update existing ones, and it understands nested content (Matrix, Addresses, Content Blocks)
to arbitrary depth.

Three ways to drive it: the control panel, a PHP config file, or the CLI.

---

## 2. Import plans

An **import plan** is a named, handled, ordered list of **steps**
(`src/Import/Data/ImportPlan.php`, service `src/Import/ImportPlan.php`). Each step is one
importer instance with its own `uid`, `type` (the importer FQCN), `file`, `transformer`,
`batchSize` and `settings` (importer-specific settings plus `map`, `matchCriteria`,
`clearableItems` and, for elements, `keepMissingNestedElements`). Two flavours:

- **Editable** — created in the CP, stored in `import_plans`.
- **Non-editable** — defined in `config/craft/import.php`, read via
  `Config::get('craft.import')`.

`ImportPlan::isEditable()` reads the `$editable` flag, which is set for plans loaded from the
database. `ImportPlan::getAllImportPlans()` (`src/Import/ImportPlan.php:51`) merges DB rows with
file closures, keys by handle, sorts by name, and memoises. An invalid file-based plan is
**skipped and logged** as a warning rather than blowing up the list.

### File-based plan shape

Returns an array of closures, each returning a configured import plan. Steps can be given as
importer instances or as step arrays:

```php
return [
    'entriesFromFile' => fn () => new ImportPlan()
        ->name('Entries from file')
        ->handle('entriesFromFile')
        ->steps([
            EntryImporter::create()
                ->file('resources/import/entry-with-plain-text.json')
                ->site('default')
                ->matchCriteria(['title' => 'title']),
        ]),
];
```

The array key is only a fallback — plans are re-keyed by `handle`. File-based steps typically
set no `->map()` and no field layout; the transformer (or the per-row `type`/`sectionId` data)
does that work, which is why one step can span multiple sections and entry types.

### What's persisted for an editable plan

`steps` is stored as JSON, one entry per step, built by `BaseImporter::toArrayData()`. On load,
`ImportPlan::createImporter()` instantiates `$step['type']`, and `BaseImporter::__construct()`
applies `file`, `transformer` and `batchSize`, then replays each `settings` key through the
importer's public setter of the same name (keys without a matching setter are ignored). The
target class is fixed by the importer subclass itself, so it isn't stored.

---

## 3. Running an import plan

`Import::dispatchImport()` (`src/Import/Import.php:133`) builds one `Import` job per step,
fires `ImportDispatching` (cancellable), then dispatches a single `ImportPipeline` job so
the whole plan shows as one named queue item. `ImportPipeline` wraps each step in its
own `Bus::batch(...)->allowFailures()` and chains them, so steps run sequentially and
one bad step doesn't kill the plan. `ImportDispatched` fires afterwards.

The `Import` job re-reads the file each time, slices off already-processed rows,
processes the step's `batchSize` items (default 5; **0 disables batching** and does everything
in one job), and re-adds itself to the batch for the remainder. Per-item failures are
logged and skipped, not fatal.

---

## 4. Importers

Every importer extends `BaseImporter`, whose abstract `targetClass()` names what it imports
into. Each importer also owns its settings: `settingsForm()`, `refreshSettingsForm()`,
`storeSettings()`, `getSettings()` and `getSettingsRules()` drive the step slideout in the CP
and its validation.

- **`ElementImporter`** is abstract. Each importable element type is represented by its own
  concrete subclass: `EntryImporter`, `AssetImporter`, `UserImporter` are the built-ins (there's
  no `UserTransformer`, so `UserImporter` uses the default `ElementTransformer`). Adds `site`
  (required) and `fieldLayout`; `EntryImporter` adds `section` and `entryType`, `AssetImporter`
  adds `volume`, and `UserImporter` resolves its fixed layout via `resolveDefaultFieldLayout()`.
  An element type is importable if and only if an `ElementImporter` subclass is registered for
  it — `Address` has none, so it's never a standalone import target, only reachable as nested
  content (Addresses field / User addresses container). `Import::getElementImporterTypeFor(string
  $elementClass): ?string` looks up the registered subclass for a given element FQCN, returning
  `null` if none is registered.
- **`ModelImporter`** is abstract too, and works exactly the same way: each importable Eloquent
  model is represented by its own concrete subclass. `SystemMessageImporter` is the only
  built-in. A model is importable if and only if a `ModelImporter` subclass is registered for
  it — there's no marker interface. A subclass can set default match criteria in its
  constructor (`SystemMessageImporter` matches on `key` + `language`, since incoming data won't
  carry Craft's IDs). `Import::getModelImporterTypeFor(string $modelClass): ?string` looks up the
  registered subclass for a given model FQCN, returning `null` if none is registered.

No importer sets default match criteria in the base classes — with none set, everything is
imported as new.

Extra importer types — including importers for plugin-defined element types and models — register
via the `RegisterImporterTypes` event (`$event->importers`).

---

## 5. Per-item pipeline

`Import::importItem()` (`src/Import/Import.php:190`):

1. `ItemImporting` event (cancellable, can rewrite `$data`).
2. Apply the map via `ImportHelper::remapData()` — only if a map is set.
3. Collect `additionalMatchCriteria()` from the transformer.
4. Resolve match criteria.
5. Apply clearable items.
6. Hand to the importer's `importItem()`.
7. `ItemImported` event.

### Match criteria

Resolution recurses through containers: rows carrying a `type` (Matrix blocks) look
their criteria up under that type; untyped rows (addresses) use the container's criteria
directly. It deliberately walks keys present in *either* the criteria or the data, so
inline criteria on a container the step never configured still resolve.

`matchCriteria` set to `null` means don't match at all — import everything as new.

### Clearable items

A tree of truthy leaves mirroring the match-criteria shape (a flat dot-notation list
is accepted and expanded). Behaviour:

- Marked clearable + value missing/empty → forced to `null` so it's explicitly applied.
- **Not** marked clearable + value empty → the key is `unset()` entirely, so the
  existing value is left untouched.

"Empty" means null, whitespace-only string, or `[]`.

---

## 6. Saving an element

`ElementImporter::importItem()` (`src/Element/Import/ElementImporter.php`):

1. `getRootElement()` calls `$this->prepareNewRootElementForImport($data)` on the importer to get
   a new element instance (per-type subclasses like `EntryImporter`/`AssetImporter` resolve the
   entry type/volume here). If there are match criteria, it then queries with
   `->drafts(null)->status(null)`, lets the importer adjust the query
   (`$this->prepareRootElementImportQuery($element, $query)`) — scoping it by type/volume, for
   example — typecasts criteria, and **forces the step's siteId** so it always wins over
   anything in the criteria. If a match is found, the resolved element is passed back through
   `prepareNewRootElementForImport()` so per-type subclasses can finish preparing it without
   re-deriving the type.
2. `markAsImporting()` — for existing and new elements alike.
3. Transformer runs *after* the element is resolved, so it can see the existing element
   via Fractal meta.
4. Split the result into native attributes, custom fields, and container properties. Attributes
   are applied via `$this->setAttributesForImport($element, $attributes)` on the importer (base
   implementation strips `id`/`uid` and applies the rest via `setAttributesFromRequest()`;
   `AssetImporter` overrides it to resolve filename/folder/temp-file handling and download).
5. **Skip-unchanged optimisation**: snapshots attribute values and serialized field
   values; if nothing changed, the element is never saved. Skipped for new elements or
   when container data is present. A special case catches content blocks, whose
   `serializeValue()` returns null while the block has no id.
6. Validation scenario: `SCENARIO_LIVE` when `enabled && getEnabledForSite()`,
   otherwise `SCENARIO_ESSENTIALS`.
7. Enable keep-flags, save, restore flags in a `finally`.
8. Log any nested elements pruned during the save.

Container *properties* (as opposed to fields) go through
`$element->importIntoContainerAttribute()` — only `User` defines it, for `addresses`.

---

## 7. Making things importable

### Elements

`Element` itself is deliberately thin here — it provides `markAsImporting()`, which sets
`public private(set) bool $importing`, read by `Entry::canChangeAuthor()`-adjacent logic to
bypass the logged-in-user requirement.

The hooks that customize how a type participates in import instead live on the `ElementImporter`
subclass for that type (see §4):

- `targetClass()` — abstract; names the target element type.
- `getDefaultTransformer()` — `ElementTransformer` by default; `EntryImporter` →
  `EntryTransformer`, `AssetImporter` → `AssetTransformer`. There is no `UserTransformer`, so
  `UserImporter` uses the default.
- `prepareNewRootElementForImport(array &$data, ?ElementInterface $element = null):
  ElementInterface`, `prepareRootElementImportQuery()`, `setAttributesForImport()` — per-type
  hooks. `EntryImporter` resolves the entry type (from the configured `fieldLayout`, or from the
  row's `typeId`) and scopes the match query by type; `AssetImporter` resolves the volume from
  `fieldLayout` and does the heavy filename/folder/temp-file work in `setAttributesForImport()`.

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
`User::$_addresses` is the only `isContainer: true` property.

### Field layout elements

`ImportableFieldLayoutElementInterface` — `getFieldsForMapping()`,
`canBeMatchCriteria()`, `canBeCleared()`. The default trait returns `false` for both,
so this is opt-in per layout element — the opposite default to the attribute.
Implementors: `TitleField`, `CustomField`, `FullNameField`, `UsernameField`,
`EmailField`, `AffiliatedSiteField`, `AltField`, and the Address layout elements.
`User::$email` is deliberately not attributed — it comes in via `EmailField`.

### Container fields

`ImportableElementContainerFieldInterface` adds `normalizeNestedEntryForImport()`,
`getMappingUiPrefix()`, `validateMapping()`, `canKeepMissingNestedElements()`,
`setKeepMissingNestedElements()`. Used by `Matrix`, `Addresses`, `ContentBlock`.
Matrix and Addresses can keep missing nested elements; ContentBlock can't (single block).

`FieldInterface::normalizeValueForImport($value, $importer, $rootOwner)` — base returns
the value unchanged. Overridden by `BaseRelationField` (returns `[]` rather than null so
relations actually clear), `Matrix`, `Addresses`, `ContentBlock`.

---

## 8. Nested data formats

Matrix accepts three shapes:

**Flat, own-type-per-row** — order preserved:
```json
"myMatrix": [
  {"type": "blockEt", "title": "block 1", "fields": {"plainText": "one"}},
  {"type": "blockEt", "title": "block 2", "fields": {"plainText": "two"}}
]
```

**Grouped by type** — keyed by entry type handle, rows carry no `type`:
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
- A block with no `type`, or a type not allowed in the field, is **silently skipped**.
- The `fields` wrapper is optional — `normalizeNestedEntryForImport()` moves custom
  field handles into `fields` when it's absent.
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

Opt-in per field, per level — an outer field can keep while an inner one prunes.
Pruned ids are captured via an `ElementDeleted` listener and logged.

---

## 9. Control panel

Nav: **Import**. Permissions group `import`: `viewImportPlans`
(→ `saveImportPlans`, `deleteImportPlans`, `triggerImportPlans`).

Screens are Vue/Inertia under `resources/js/pages/import/`, backed by `ImportPlansController`:

- `import` — two tables, editable and file-based. Editable rows get Edit, Duplicate, Delete
  and Run; file-based rows get Run only. Run queues the plan via `dispatchImport()`.
- `import/new|{handle}` — name, handle, description, then the **Steps** list
  (`modules/import/steps/StepList.vue`). Each step opens a slideout (`StepSlideout.vue`):
  importer type (reactive select), the importer's own settings form, data file, transformer
  and batch size, plus a **Mapping** summary with an "Edit mapping" button. Choosing the
  importer type for an element import (Entries / Assets / Users) *is* choosing the element
  type — there's no separate "Element Type" field. A step is validated (`import/validate-step`)
  before its slideout can close.
- The mapping table: **Destination / Incoming data / Match / Clear**. The incoming-data select
  is a combobox that shows each column's first-row value as a hint, and the most likely
  column is pre-selected (`ImportHelper::suggestMapValues()`). Attributes with
  `canBeSet: false` are match-only. Container rows show a "Map field" button opening a nested
  slideout instead of a select; the slideout has the "Keep existing nested elements missing
  from the imported data" checkbox and one section per field-layout provider, and nests
  arbitrarily deep. All four trees are held client-side and saved with the step.

Read-only: derived from `allowAdminChanges`. Save/delete actions are dropped and the
forms render in read-only mode. Running plans is deliberately *not* gated on it.

There is **no logs/history screen** — logging goes to a daily `import` channel at
`storage/logs/import.log` (debug, 14 days), registered only if the app hasn't already
defined one.

---

## 10. CLI

One command per importer, each extending the abstract `CraftCms\Cms\Import\Commands\Import`:

```
craft:import:entry {file} [--site=] [--section=] [--entryType=] [--transformer=] [--matchCriteria=]
craft:import:asset {file} [--site=] [--volume=] [--transformer=] [--matchCriteria=]
craft:import:user {file} [--site=] [--transformer=] [--matchCriteria=]
craft:import:system-message {file} [--transformer=] [--matchCriteria=]
```

Aliases: `import/entry`, `import/asset`, `import/user`, `import/system-message`. `{file}` is
prompted if missing, as are any missing options; `--site` is only added for element
importers and only prompted on multisite. `--matchCriteria` is JSON.

A command builds a one-off importer via `ImportPlan::createImporter()` and imports every item
synchronously — it doesn't go through a plan or the queue. To add a command for your own
importer, extend `Import`, implement `importerClass()`, add any extra prompts via
`getAdditionalOptions()`, and register it with `$this->commands()`.

No command takes a `--map`, so CLI mapping is the transformer's job.

---

## 11. Extension points

- `RegisterDataTypes` — `$event->dataTypes['yml'] = MyType::class;` (extension-keyed).
  Built-ins: json, csv, xml. A data type implements two **static** methods,
  `format()` and `getHeadings()`.
- `RegisterImporterTypes` — `$event->importers[] = MyImporter::class;`. Registering a new
  importable element type or model means contributing a concrete `ElementImporter` or
  `ModelImporter` subclass that implements `targetClass()`.
- `ItemImporting` / `ItemImported`, `ImportPlanSaving` / `ImportPlanSaved`,
  `ImportDispatching` / `ImportDispatched`.
  The `*ing` variants are cancellable.
- Transformers: extend `ElementTransformer` (or the per-type one) and override
  `transform()`; or implement `additionalMatchCriteria()`. Transformers can also be
  given as a class string or an `fn($element) => ...` closure string.
  `ElementTransformer` also supports convention-based `normalize{PropName}()` methods, and
  auto-matches incoming keys that don't match exactly (`ImportHelper::prepKeyForAutoMatching()`,
  e.g. `Plain Text` / `plain_text` → `plainText`).
