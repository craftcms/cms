# Element Index → Inertia + Vue (Core Slice, Entries First)

**Date:** 2026-07-06
**Status:** Approved

## Goal

Port the entries element index from the legacy Twig/jQuery stack
(`resources/templates/entries/index.twig` → `_layouts/elementindex.twig` →
`Craft.createElementIndex` / `BaseElementIndex`) to Inertia + Vue, following the
patterns established by already-ported CP pages (GraphQL tokens, entry types,
user permissions).

## Scope

### In scope (this port)

- Table view of elements
- Sources sidebar (hierarchical, permission-filtered)
- Search (debounced)
- Sorting (per-source sort options)
- Pagination
- Status filter and site selection
- Per-source table columns (the source's defined/default columns)
- Entries index routes only (`/entries`, `/entries/{sectionHandle}`,
  `/content/{page}`, `/content/{page}/{sectionHandle}`)

### Out of scope (follow-up iterations; architecture must not preclude them)

- Cards, thumbs, and structure view modes (incl. drag reorder)
- Inline editing
- Condition-builder filters
- Bulk element actions
- Export
- Per-user column customization / view-state persistence
- Other element types (assets, categories, users) and modal/field contexts

The legacy Twig/jQuery element index and all `element-indexes/*` endpoints stay
untouched and continue to serve every other element type and all embedded/modal
contexts.

## Key decisions

1. **Core slice first.** Ship table view + sources + search + sort + pagination
   + status/site; iterate on the rest.
2. **Hybrid cell rendering.** Native attributes are serialized as structured
   JSON and rendered by real Vue components; any other visible column falls
   back to a server-rendered `attributeHtml` map built from
   `$element->getAttributeHtml()`, so custom fields and plugin columns keep
   working unchanged.
3. **Generic module, entries first.** The Vue module and the backend service /
   Resource layer are keyed by element type; the entries page is the first and
   only consumer for now.
4. **Inertia-native data flow with a reusable core.** The page is pure Inertia:
   state lives in URL query params; updates use `router.visit()` partial
   reloads. Query-building and serialization live in a controller-agnostic
   service so a thin JSON controller can expose the identical payload later for
   modal/field contexts.

## Backend design

### Routes & controllers

- Routes in `routes/cp.php` are unchanged.
- `src/Http/Controllers/Entries/EntriesIndexController.php` keeps its
  redirect-to-first-source behavior; the section-page routes now return
  `Inertia::render('entries/Index', [...])` instead of rendering
  `entries/index.twig`.
- Invalid/unknown source → redirect to the first available source page (parity
  with the current template logic).

### ElementIndexService

New controller-agnostic service in `src/Element/` (singleton via attribute per
repo convention). Responsibilities, given an element type + request params
(source, search, sort, page, site, status):

- Resolve the source tree via the existing `ElementSources` service, filtered
  to what the current user may view.
- Resolve the selected source (or default) and its column/sort metadata
  (`getTableAttributes()`, `getSourceSortOptions()`).
- Build the `ElementQuery`: source criteria + search + sort + status + site +
  pagination.
- Return a plain, serializable result (sources, columns, sort options,
  elements, pagination) consumed by the Inertia controller now and by a JSON
  controller later.

### Resources

- `ElementSourceResource` — key, label, nesting/children, badge/count metadata,
  default sort, table columns.
- `ElementIndexElementResource` — structured fields for native concerns: `id`,
  `title`, `cpEditUrl`, `status`, `slug`, `section`/`type`, `author`,
  `postDate`, `dateUpdated`; plus `attributeHtml: {attr: html}` for each
  visible column not covered by a structured field, built from
  `getAttributeHtml()`.

### Props contract (entries/Index)

`title`, `crumbs`, `elementType` metadata (singular/plural display names),
`sources`, `selectedSource`, `columns`, `sortOptions`, `sort`, `searchTerm`,
`sites`, `selectedSiteId`, `statuses`, `selectedStatus`, `elements`,
`pagination`, `readOnly`, `canCreate` (per selected section, drives the
"New entry" button).

## Frontend design

### Module: `resources/js/modules/element-index/`

Mirrors the `admin-table` module layout (components + composables + helpers).

- `components/ElementIndex.vue` — orchestrator: wires toolbar, source list, and
  table; owns nothing but composition.
- `components/SourceList.vue` — recursive source tree rendered into the
  `IndexLayout` sidebar slot; selected source highlighted; disclosure for
  nested sources.
- `components/ElementIndexToolbar.vue` — debounced search input, status menu,
  site menu, built from `@craftcms/cp` components.
- Cell renderers:
  - element label cell (status indicator + link to `cpEditUrl`, the Vue
    equivalent of the legacy element chip),
  - date cell (reuses admin-table date formatting),
  - `HtmlCell.vue` — renders `attributeHtml` fallback via `v-html`
    (server-rendered CP HTML, same trust model as the legacy index).
- Columns are assembled with the existing `createCraftColumnHelper`; the table
  is the existing `AdminTable` (TanStack) with server pagination.
- `composables/useElementIndexState.ts` — owns URL-query state and issues
  `router.visit()` calls (see Data flow).

### Page: `resources/js/pages/entries/Index.vue`

Thin page: `IndexLayout` + `<ElementIndex>` fed from props; "New entry" button
in the actions slot, shown per `canCreate` and pointing at the selected
section's create URL.

## Data flow & state

- URL query params are the single source of truth: `source`, `search`, `sort`
  (`attr-dir`), `page`, `site`, `status`. URLs are shareable and the back
  button works.
- Search/sort/page changes → `router.visit()` with
  `only: ['elements', 'pagination']`, `preserveState: true`,
  `preserveScroll: true`; search is debounced in the composable.
- Source changes additionally reload `columns`, `sortOptions`, `sort`, and
  `canCreate` (they are per-source) and reset `page`.
- No localStorage/user-pref view-state persistence in this slice; the URL is
  the state.

## Compatibility & error handling

- Legacy index untouched for all other element types and modal/field contexts.
- Plugin/custom-field columns render through the `attributeHtml` fallback.
- Sources are permission-filtered server-side; requests for sources the user
  cannot view fall back to the first visible source.
- Empty result sets render the shared `Empty` state with a create CTA when
  permitted.
- Out-of-range `page` values clamp to the last valid page; non-numeric values
  fall back to page 1.

## Testing

- Pest feature tests on the entries index routes: props shape, source-criteria
  filtering, search, sort, pagination, site/status params, permission-based
  source visibility, redirect behavior for invalid sources. Use real code
  paths per the repo's `testing-guidelines` skill.
- Unit tests for `ElementIndexElementResource`: structured field serialization
  and correct attributeHtml fallback selection for non-native columns.

## Follow-up roadmap (not this port)

1. Bulk selection + element actions
2. Cards/thumbs view modes; structure view with drag reorder
3. Condition-builder filters
4. Inline editing; export
5. JSON controller exposing the ElementIndexService payload for modal/field
   contexts; migrate assets/categories/users indexes

## Addendum (2026-07-06, post-merge)

`feature/inertia-element-indexes` was merged into this branch, folding its
full feature set (selection + bulk actions, cards view, view-mode switching,
per-source column/sort customization, sources sidebar, publishable-sections
New Entry button, CP package components) into the service-based architecture
above. Notes:

- The Vue module now lives at `resources/js/modules/elements/` (the merged
  branch's richer module); the core-slice `modules/element-index/` and
  `pages/entries/Index.vue` were superseded and removed.
- `ContentIndexController` serves `content/{page}/{sectionHandle?}` and
  delegates data assembly to the `ElementIndexes` service.
- `EntriesIndexController` handles all entries/content redirects.
- Cell rendering goes through `$element->getAttributeHtml()` (not
  `ElementAttributeRenderer` directly) so element-type attribute overrides
  like Entry's `authors` keep working.
