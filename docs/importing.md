# Craft CMS Import feature — current state on `feature/import`

## 1. What the feature does

Bulk-imports data from a JSON, CSV or XML file into Craft elements (entries, assets, users,
addresses-as-nested-content) or into opted-in Eloquent models. It can create new records or find
and update existing ones, and it understands nested content (Matrix, Addresses, Content Blocks)
to arbitrary depth.

Three ways to drive it: the control panel, a PHP config file, or the CLI.

---

## 2. Import configs

An **import config** is one importer instance: source file, target class, mapping,
match criteria, clearable items, keep-missing flags. Two flavours:

- **Editable** — created in the CP, stored in `import_configs`. Has a `uid`.
- **Non-editable** — defined in `config/craft/import.php`, read via
  `Config::get('craft.import')`. No `uid`.

`BaseImporter::isEditable()` is literally `isset($this->uid)`, which is what splits
the two lists in the CP. `ImportConfig::getAllConfigs()` (`src/Import/ImportConfig.php:60`)
merges DB rows with file closures, keys by handle, sorts by name, and memoises.
An invalid file-based config is **skipped and logged** as a warning rather than
blowing up the list.

### File-based config shape

Returns an array of closures, each returning a configured importer:

```php
return [
    'myElementImport' => fn() => EntryImporter::create()
        ->name('file - entry with plain text')
        ->handle('fileEntryWithPlainText')
        ->site('default')
        ->file('resources/import/entry-with-plain-text.json')
        ->matchCriteria(['title' => 'title']),
];
```

The array key is only a fallback — configs are re-keyed by `handle`. File-based
configs typically set no `->map()` and no `->fieldLayout()`; the transformer (or the
per-row `type`/`sectionId` data) does that work, which is why one file-based config
can span multiple sections and entry types in a single run.

### What's persisted for an editable config

`settings` JSON holds: `file`, `className`, `transformer`, `map`, `matchCriteria`,
`clearableItems`, `keepMissingNestedElements`, plus `site` (uid) and `fieldLayout`.
`createImporter()` replays these through the fluent setters on load — except `className`, whose
target class is fixed at construction time by the importer subclass and has no setter at all; it's
stored for display/introspection only and skipped on replay.

---

## 3. Import runs

A **run** (`import_runs`) chains ordered steps. Each step is
`['config' => <uid|handle>, 'file' => <optional>, 'batchSize' => <int|null>]`.

`Import::dispatchImport()` resolves each step's config, builds an `Import` job, fires
`ImportRunDispatching` (cancellable), then dispatches a single `ImportPipeline` job so
the whole run shows as one named queue item. `ImportPipeline` wraps each step in its
own `Bus::batch(...)->allowFailures()` and chains them, so steps run sequentially and
one bad step doesn't kill the run.

The `Import` job re-reads the file each time, slices off already-processed rows,
processes `batchSize` items (default 5; **0 disables batching** and does everything in
one job), and re-adds itself to the batch for the remainder. Per-item failures are
logged and skipped, not fatal.

---

## 4. The two importers

- **`ElementImporter`** is abstract. Each importable element type is represented by its own
  concrete subclass, which implements `elementClass()` to name its target type: `EntryImporter`,
  `AssetImporter`, `UserImporter` are the built-ins (there's no `UserTransformer`, so
  `UserImporter` uses the default `ElementTransformer`). Adds `site` (required) and `fieldLayout`.
  An element type is importable if and only if an `ElementImporter` subclass is registered for
  it — `Address` has none, so it's never a standalone import target, only reachable as nested
  content (Addresses field / User addresses container). `Import::getElementImporterTypeFor(string
  $elementClass): ?string` looks up the registered subclass for a given element FQCN, returning
  `null` if none is registered.
- **`ModelImporter`** is abstract too, and works exactly the same way: each importable Eloquent
  model is represented by its own concrete subclass, which implements `modelClass()` to name its
  target. `SystemMessageImporter` is the only built-in. A model is importable if and only if a
  `ModelImporter` subclass is registered for it — there's no marker interface. `ModelImporter`
  still defaults `matchCriteria` to `['id' => 'id']`, though a subclass can override that in its
  constructor (`SystemMessageImporter` matches on `key` + `language`, since incoming data won't
  carry Craft's IDs). `Import::getModelImporterTypeFor(string $modelClass): ?string` looks up the
  registered subclass for a given model FQCN, returning `null` if none is registered.

Extra importer types — including importers for plugin-defined element types and models — register
via the `RegisterImporterTypes` event (`$event->importers`).

---

## 5. Per-item pipeline

`Import::importItem()` (`src/Import/Import.php:138`):

1. `DataImporting` event (cancellable, can rewrite `$data`).
2. Apply the map via `ImportHelper::remapData()` — only if a map is set.
3. Collect `additionalMatchCriteria()` from the transformer.
4. Resolve match criteria.
5. Apply clearable items.
6. Hand to the importer's `importItem()`.
7. `DataImported` event.

### 

Resolution recurses through containers: rows carrying a `type` (Matrix blocks) look
their criteria up under that type; untyped rows (addresses) use the container's criteria
directly. It deliberately walks keys present in *either* the criteria or the data, so
inline criteria on a container the config never configured still resolve.

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
   example — typecasts criteria, and **forces the config's siteId** so it always wins over
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

- `elementClass()` — abstract; names the target element type.
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
)
```

Both `name` and `label` are **required** positionally. Examples: `id`/`uid` are
`canBeCleared: false`; `Entry::$_typeId` is `#[Importable('typeId', 'Type ID', true)]`
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

Nav: **Import** → Configs / Runs. Permissions group `import`: `viewImportConfigs`
(→ `saveImportConfigs`, `deleteImportConfigs`) and `viewImportRuns`
(→ `saveImportRuns`, `deleteImportRuns`, `triggerImportRuns`).

Screens are Vue/Inertia under `resources/js/pages/import/`:

- `import` — hub with Configs/Runs tiles.
- `import/configs` — two tables, editable and file-based. File-based rows get a Run
  button (synchronous) and nothing else.
- `import/configs/new|{handle}` — name, handle, description, importer type (reactive select),
  then the importer's own settings form: data file, site, transformer. Choosing the importer type
  for an element import (Entries / Assets / Users) *is* choosing the element type — there's no
  separate "Element Type" field.
- `import/configs/{handle}/field-layout-provider` — element imports only; redirects back
  to edit for non-element imports.
- `import/configs/{handle}/map` — the mapping table: **Destination / Incoming data /
  Match / Clear**. Container rows show a "Map field" button opening a nested slideout
  instead of a select; the slideout has the "Keep existing nested elements missing from
  the imported data" checkbox and one section per field-layout provider, and nests
  arbitrarily deep. All four trees are held client-side and posted together.
- `import/runs` and `import/runs/new|{handle}` — steps table with config + batch size.

Read-only: derived from `allowAdminChanges`. Save/delete actions are dropped and the
forms render in read-only mode; only the map screen shows an explicit notice.
Triggering runs is deliberately *not* gated on it.

There is **no logs/history screen** — logging goes to a daily `import` channel at
`storage/logs/import.log` (debug, 14 days), registered only if the app hasn't already
defined one.

---

## 10. CLI

```
craft:import:element {elementType} {file}
    [--site=] [--fieldLayoutProvider=] [--transformer=] [--matchCriteria=]
```
Alias `import/element`. Both arguments are positional and prompted if missing;
`--site` is only prompted on multisite. `--matchCriteria` is JSON.

`{elementType}` is resolved to a concrete importer via `Import::getElementImporterTypeFor()`; the
command fails with a clear error if no importer is registered for that type. The interactive
prompt lists options built from `Import::getAllImporterTypes()` filtered to `ElementImporter`
subclasses (label = the importer's `displayName()`, value = its `elementClass()`).

```
craft:import:model {className} {file} [--transformer=] [--matchCriteria=]
```
Alias `import/model`. `{className}` is resolved to a concrete importer via
`Import::getModelImporterTypeFor()`; the command fails with a clear error if no importer is
registered for that model. The interactive prompt lists options built from
`Import::getAllImporterTypes()` filtered to `ModelImporter` subclasses (label = the importer's
`displayName()`, value = its `modelClass()`).

Neither takes a `--map`, so CLI mapping is the transformer's job.

---

## 11. Extension points

- `RegisterDataTypes` — `$event->dataTypes['yml'] = MyType::class;` (extension-keyed).
  Built-ins: json, csv, xml. A data type implements two **static** methods,
  `format()` and `getHeadings()`.
- `RegisterImporterTypes` — `$event->importers[] = MyImporter::class;`. Registering a new
  importable element type means contributing a concrete `ElementImporter` subclass that
  implements `elementClass()`; registering a new importable model means contributing a concrete
  `ModelImporter` subclass that implements `modelClass()`.
- `DataImporting` / `DataImported`, `ImportConfigSaving` / `Saved`,
  `ImportRunSaving` / `Saved`, `ImportRunDispatching` / `Dispatched`.
  The `*ing` variants are cancellable.
- Transformers: extend `ElementTransformer` (or the per-type one) and override
  `transform()`; or implement `additionalMatchCriteria()`. Transformers can also be
  given as a class string or an `fn($element) => ...` closure string.
  `ElementTransformer` also supports convention-based `normalize{PropName}()` methods.
