# Craft CMS Import feature — current state on `feature/import`

## 1. What the feature does

Bulk-imports data from a JSON, CSV or XML file into Craft elements (entries, assets,
users, addresses-as-nested-content) or into opted-in Eloquent models. It can create new
records or find and update existing ones, and it understands nested content (Matrix,
Addresses, Content Blocks) to arbitrary depth.

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
    'myElementImport' => fn() => ElementImporter::create()
        ->className(\CraftCms\Cms\Entry\Elements\Entry::class)
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
`createImporter()` replays these through the fluent setters on load.

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

- **`ElementImporter`** — target must be a registered element type whose
  `isImportable()` returns true. Adds `site` (required) and `fieldLayout`.
- **`ModelImporter`** — target must implement the marker interface
  `ImportableModelInterface` and must *not* be an element type. This is a change in
  emphasis: elements are importable by default and opt out; models are opt-in only.
  Still defaults `matchCriteria` to `['id' => 'id']`; `ElementImporter` no longer does.

Extra types register via the `RegisterImporterTypes` event (`$event->importers`).

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

`ElementImporter::importItem()` (`src/Import/Importers/ElementImporter.php:513`):

1. `getRootElement()` — new instance, `prepareNewElementForImport()`, then if there are
   match criteria, query with `->drafts(null)->status(null)`, let the element adjust the
   query (`prepareRootElementImportQuery()`), typecast criteria, and **force the config's
   siteId** so it always wins over anything in the criteria.
2. `markAsImporting()` — for existing and new elements alike.
3. Transformer runs *after* the element is resolved, so it can see the existing element
   via Fractal meta.
4. Split the result into native attributes, custom fields, and container properties.
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

`Element` uses the `Importable` concern (`src/Component/Concerns/Importable.php`)
implementing `ImportableInterface`:

- `isImportable()` — true by default; `Address` returns false (never standalone).
- `getDefaultTransformer()` — `ElementTransformer`; Entry → `EntryTransformer`,
  Asset → `AssetTransformer`. There is no `UserTransformer`.
- `prepareNewElementForImport()`, `prepareRootElementImportQuery()`,
  `setAttributesForImport()` — per-type hooks. Entry resolves type/section, Asset does
  the heavy filename/folder/temp-file work.
- `markAsImporting()` sets `public private(set) bool $importing`, read by
  `Entry::canChangeAuthor()`-adjacent logic to bypass the logged-in-user requirement.

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
- `import/configs/new|{handle}` — name, handle, description, importer type (reactive
  select), then the importer's own settings form: data file, site, element type,
  transformer.
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

```
craft:import:model {className} {file} [--transformer=] [--matchCriteria=]
```
Alias `import/model`.

Neither takes a `--map`, so CLI mapping is the transformer's job.

---

## 11. Extension points

- `RegisterDataTypes` — `$event->dataTypes['yml'] = MyType::class;` (extension-keyed).
  Built-ins: json, csv, xml. A data type implements two **static** methods,
  `format()` and `getHeadings()`.
- `RegisterImporterTypes` — `$event->importers[] = MyImporter::class;`
- `DataImporting` / `DataImported`, `ImportConfigSaving` / `Saved`,
  `ImportRunSaving` / `Saved`, `ImportRunDispatching` / `Dispatched`.
  The `*ing` variants are cancellable.
- Transformers: extend `ElementTransformer` (or the per-type one) and override
  `transform()`; or implement `additionalMatchCriteria()`. Transformers can also be
  given as a class string or an `fn($element) => ...` closure string.
  `ElementTransformer` also supports convention-based `normalize{PropName}()` methods.
