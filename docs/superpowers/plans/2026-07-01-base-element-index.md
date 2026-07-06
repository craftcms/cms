# BaseElementIndex Refactor Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Abstract the shared shell/footer/selection from `AdminTable` and `ElementCards` into a page-composed `BaseElementIndex`, extract a bare `DataTable` core, rename the shell CSS to `element-index__*`, and add an ARIA live region plus shift-click and keyboard selection.

**Architecture:** `content/Index.vue` renders `<BaseElementIndex>` (owns the shell chrome, footer, and aria-live region) with either a bare `DataTable` or the bare `ElementCards` grid in its `#body` slot. Shared selection logic (including shift-click range) lives in a `useElementIndexSelection` composable consumed independently by the base and both bodies. A thin `AdminTable` wrapper (= `BaseElementIndex` + `DataTable`) preserves the public API for ~19 standalone callers.

**Tech Stack:** Vue 3 `<script setup>` + TypeScript, TanStack Vue Table, Inertia, Vitest 4, `@craftcms/cp` web components (`craft-checkbox`, etc.), scoped SCSS.

## Global Constraints

- `declare`-style strictness: all TS is strict; no `any` leaks beyond the existing `Record<any, any>` row type already used for element rows.
- Rename **shell classes only**: `cp-table-wrapper/header/body/footer` → `element-index__*`. Do **not** touch `.cp-table` / `.cp-table-cell` / `.cp-table--*` (generic `<table>` primitive used by legacy Twig + Storybook).
- New component/composable files live under `resources/js/modules/elements/` (matches existing dependency direction — `AdminTable` already imports `BulkActionsBar` from there).
- Existing `useElementIndex*` composables (`Pagination` = TanStack model config, `Sort`, `Filters`, `Columns`, `ViewMode`, `ViewState`, `Loading`) are **not** modified.
- The ~19 standalone `AdminTable` callers must keep working with no source changes.
- Verify commands (used throughout):
  - Composable logic: `npx vitest run <path/to/test>`
  - Types: `npm run typecheck`
  - JS/Vue lint: `npx eslint <files>`
  - Styles: `npm run lint:styles`
- Commit after each task. Branch: `feature/inertia-element-indexes` (already checked out; do not create a new branch).
- Do **not** `git add -A` — the working tree holds unrelated WIP (`base.css`, `_cp.scss`, `AdminTable.vue`, `ElementCards.vue`). Stage only the exact files each step names.

---

## File Structure

**New**
- `resources/js/modules/elements/composables/useElementIndexSelection.ts` — selection state + handlers (single responsibility: derive/mutate row selection from the shared table).
- `resources/js/modules/elements/composables/useElementIndexSelection.test.ts` — vitest unit tests.
- `resources/js/modules/elements/components/BaseElementIndex.vue` — shell chrome + footer + aria-live region.
- `resources/js/modules/elements/components/DataTable.vue` — bare `<table>` core (columns, rows, reorder, skeleton, caption).

**Modified**
- `resources/js/modules/admin-table/components/AdminTable.vue` → thin wrapper.
- `resources/js/modules/elements/components/ElementCards.vue` → bare card grid.
- `resources/js/pages/content/Index.vue` → composes `BaseElementIndex`.
- `packages/craftcms-cp/src/styles/shared/base.css` → remove dead shell rules.

---

## Task 1: `useElementIndexSelection` composable

**Files:**
- Create: `resources/js/modules/elements/composables/useElementIndexSelection.ts`
- Test: `resources/js/modules/elements/composables/useElementIndexSelection.test.ts`

**Interfaces:**
- Consumes: `@tanstack/vue-table` (`Table`, `Row` types), `@/modules/elements/types/actions` (`BulkActionItem`).
- Produces:
  ```ts
  export interface ElementIndexSelectionOptions {
    selectable: MaybeRefOrGetter<boolean>;
    readOnly: MaybeRefOrGetter<boolean>;
    actions: MaybeRefOrGetter<Array<BulkActionItem> | null | undefined>;
  }
  export function useElementIndexSelection(
    table: MaybeRefOrGetter<Table<any>>,
    options: ElementIndexSelectionOptions,
  ): {
    selectedIds: ComputedRef<Array<string | number>>;
    hasSelection: ComputedRef<boolean>;
    hasBulkActions: ComputedRef<boolean>;
    showBulkActions: ComputedRef<boolean>;
    bulkActionsActive: ComputedRef<boolean>;
    readOnly: ComputedRef<boolean>;
    anchorIndex: Ref<number | null>;
    clearSelection: () => void;
    onToggleAllSelected: (checked: boolean) => void;
    selectRow: (row: Row<any>, opts: {checked: boolean; shiftKey?: boolean}) => void;
    toggleRow: (row: Row<any>) => void;
    extendSelectionTo: (row: Row<any>) => void;
  };
  ```
  Notes for consumers: `onToggleAllSelected` takes the already-extracted `checked` boolean (not the DOM event). `selectRow` is the per-row checkbox handler and owns the `craft-checkbox` programmatic-change guard and shift-click range. `toggleRow`/`extendSelectionTo` are keyboard primitives (Tasks 8–9). Callers resolve `readOnly` via `props.readOnly ?? usePage().props.readOnly` and pass it in.

- [ ] **Step 1: Write the failing test**

Create `resources/js/modules/elements/composables/useElementIndexSelection.test.ts`:

```ts
import {describe, expect, it} from 'vitest';
import {ref} from 'vue';
import {useElementIndexSelection} from './useElementIndexSelection';

// Minimal TanStack-table stand-in: rows own their selected state.
function makeRow(id: number, selected = false) {
  let sel = selected;
  return {
    id: String(id),
    original: {id},
    getIsSelected: () => sel,
    toggleSelected: (v?: boolean) => {
      sel = v ?? !sel;
    },
  };
}

function makeTable(rows: ReturnType<typeof makeRow>[]) {
  let allToggled: boolean | null = null;
  return {
    getRowModel: () => ({rows}),
    getSelectedRowModel: () => ({rows: rows.filter((r) => r.getIsSelected())}),
    getIsAllRowsSelected: () => rows.every((r) => r.getIsSelected()),
    toggleAllRowsSelected: (v: boolean) => {
      allToggled = v;
      rows.forEach((r) => r.toggleSelected(v));
    },
    _allToggled: () => allToggled,
    resetRowSelection: () => rows.forEach((r) => r.toggleSelected(false)),
  } as any;
}

const opts = (over = {}) => ({selectable: true, readOnly: false, actions: [], ...over});

describe('useElementIndexSelection', () => {
  it('toggles a single row and sets the anchor', () => {
    const rows = [makeRow(1), makeRow(2), makeRow(3)];
    const table = makeTable(rows);
    const s = useElementIndexSelection(table, opts());

    s.selectRow(rows[1] as any, {checked: true});

    expect(rows[1].getIsSelected()).toBe(true);
    expect(s.anchorIndex.value).toBe(1);
    expect(s.selectedIds.value).toEqual([2]);
    expect(s.hasSelection.value).toBe(true);
  });

  it('shift-clicking selects the inclusive range from the anchor', () => {
    const rows = [makeRow(1), makeRow(2), makeRow(3), makeRow(4)];
    const table = makeTable(rows);
    const s = useElementIndexSelection(table, opts());

    s.selectRow(rows[0] as any, {checked: true}); // anchor = 0
    s.selectRow(rows[2] as any, {checked: true, shiftKey: true});

    expect(rows.map((r) => r.getIsSelected())).toEqual([true, true, true, false]);
  });

  it('ignores a programmatic change where checked already matches (no shift)', () => {
    const rows = [makeRow(1, true)];
    const table = makeTable(rows);
    const s = useElementIndexSelection(table, opts());

    s.selectRow(rows[0] as any, {checked: true}); // already selected → no-op, no anchor
    expect(s.anchorIndex.value).toBe(null);
  });

  it('does nothing when read-only', () => {
    const rows = [makeRow(1)];
    const table = makeTable(rows);
    const s = useElementIndexSelection(table, opts({readOnly: true}));

    s.selectRow(rows[0] as any, {checked: true});
    s.onToggleAllSelected(true);

    expect(rows[0].getIsSelected()).toBe(false);
  });

  it('computes bulk-action visibility from selectable + actions + selection', () => {
    const rows = [makeRow(1)];
    const table = makeTable(rows);
    const actions = ref<any[]>([{label: 'Delete'}]);
    const s = useElementIndexSelection(table, opts({actions}));

    expect(s.hasBulkActions.value).toBe(true);
    expect(s.showBulkActions.value).toBe(true);
    expect(s.bulkActionsActive.value).toBe(false);

    s.selectRow(rows[0] as any, {checked: true});
    expect(s.bulkActionsActive.value).toBe(true);
  });
});
```

- [ ] **Step 2: Run the test to verify it fails**

Run: `npx vitest run resources/js/modules/elements/composables/useElementIndexSelection.test.ts`
Expected: FAIL — `Failed to resolve import "./useElementIndexSelection"`.

- [ ] **Step 3: Write the implementation**

Create `resources/js/modules/elements/composables/useElementIndexSelection.ts`:

```ts
import {computed, ref, toValue, type MaybeRefOrGetter, type Ref} from 'vue';
import type {Row, Table} from '@tanstack/vue-table';
import type {BulkActionItem} from '@/modules/elements/types/actions';

export interface ElementIndexSelectionOptions {
  selectable: MaybeRefOrGetter<boolean>;
  readOnly: MaybeRefOrGetter<boolean>;
  actions: MaybeRefOrGetter<Array<BulkActionItem> | null | undefined>;
}

export function useElementIndexSelection(
  table: MaybeRefOrGetter<Table<any>>,
  options: ElementIndexSelectionOptions,
) {
  // The anchor is the last individually-toggled row; shift-click selects the
  // inclusive range between it and the clicked row in current row-model order.
  const anchorIndex: Ref<number | null> = ref(null);

  const readOnly = computed(() => toValue(options.readOnly));

  const selectedIds = computed<Array<string | number>>(() =>
    toValue(table)
      .getSelectedRowModel()
      .rows.map((row) => row.original.id),
  );
  const hasSelection = computed(() => selectedIds.value.length > 0);
  const hasBulkActions = computed(
    () => (toValue(options.actions)?.length ?? 0) > 0,
  );
  const showBulkActions = computed(
    () => toValue(options.selectable) && hasBulkActions.value,
  );
  const bulkActionsActive = computed(
    () => showBulkActions.value && hasSelection.value,
  );

  function clearSelection() {
    toValue(table).resetRowSelection();
  }

  // craft-checkbox (Lion) fires `model-value-changed` on programmatic `.checked`
  // updates too, so only act when the incoming value actually differs.
  function onToggleAllSelected(checked: boolean) {
    if (readOnly.value) return;
    const t = toValue(table);
    if (checked !== t.getIsAllRowsSelected()) {
      t.toggleAllRowsSelected(checked);
    }
  }

  function rowIndex(row: Row<any>): number {
    return toValue(table)
      .getRowModel()
      .rows.findIndex((r) => r.id === row.id);
  }

  function selectRow(
    row: Row<any>,
    {checked, shiftKey = false}: {checked: boolean; shiftKey?: boolean},
  ) {
    if (readOnly.value) return;
    const rows = toValue(table).getRowModel().rows;
    const index = rowIndex(row);

    if (shiftKey && anchorIndex.value !== null) {
      const [start, end] =
        anchorIndex.value <= index
          ? [anchorIndex.value, index]
          : [index, anchorIndex.value];
      for (let i = start; i <= end; i++) {
        rows[i].toggleSelected(checked);
      }
      return; // anchor is preserved across a range select
    }

    // Guard the programmatic re-fire: nothing to do if state already matches.
    if (checked === row.getIsSelected()) return;
    row.toggleSelected(checked);
    anchorIndex.value = index;
  }

  function toggleRow(row: Row<any>) {
    if (readOnly.value) return;
    row.toggleSelected();
    anchorIndex.value = rowIndex(row);
  }

  function extendSelectionTo(row: Row<any>) {
    if (readOnly.value) return;
    const rows = toValue(table).getRowModel().rows;
    const index = rowIndex(row);
    const from = anchorIndex.value ?? index;
    const [start, end] = from <= index ? [from, index] : [index, from];
    for (let i = start; i <= end; i++) {
      rows[i].toggleSelected(true);
    }
  }

  return {
    selectedIds,
    hasSelection,
    hasBulkActions,
    showBulkActions,
    bulkActionsActive,
    readOnly,
    anchorIndex,
    clearSelection,
    onToggleAllSelected,
    selectRow,
    toggleRow,
    extendSelectionTo,
  };
}
```

- [ ] **Step 4: Run the test to verify it passes**

Run: `npx vitest run resources/js/modules/elements/composables/useElementIndexSelection.test.ts`
Expected: PASS — 5 tests pass.

- [ ] **Step 5: Typecheck + lint**

Run: `npm run typecheck && npx eslint resources/js/modules/elements/composables/useElementIndexSelection.ts resources/js/modules/elements/composables/useElementIndexSelection.test.ts`
Expected: no errors.

- [ ] **Step 6: Commit**

```bash
git add resources/js/modules/elements/composables/useElementIndexSelection.ts \
        resources/js/modules/elements/composables/useElementIndexSelection.test.ts
git commit -m "Add useElementIndexSelection composable with shift-click range"
```

---

## Task 2: `BaseElementIndex.vue` (shell + footer + aria-live)

**Files:**
- Create: `resources/js/modules/elements/components/BaseElementIndex.vue`

**Interfaces:**
- Consumes: `useElementIndexSelection` (Task 1); `BulkActionsBar`, `Text`, `Select`, `LoadingSkeleton`(not here), `usePage`.
- Produces: a component with props `{table, selectable?, readOnly?, loading?, from?, to?, total?, enableAdjustPageSize?, pageSizeOptions?, actions?, elementType?, source?, context?}`, slots `header` and `body`, and emit `action-performed`.

This task **moves** the footer markup + the shell wrapper/header/body/footer chrome out of `AdminTable.vue`/`ElementCards.vue` into one component, renaming the shell classes. Source the footer template + pagination proxies from the current `AdminTable.vue` lines 482–562 and 120–163; source `onActionPerformed`/`clearSelection`/selection state from the selection composable.

- [ ] **Step 1: Create the component**

Create `resources/js/modules/elements/components/BaseElementIndex.vue`:

```vue
<script setup lang="ts">
  import {computed, ref, watch} from 'vue';
  import {usePage} from '@inertiajs/vue3';
  import {t, Appearance} from '@craftcms/cp';
  import Text from '@/common/components/Text.vue';
  import Select from '@/common/form/Select.vue';
  import BulkActionsBar from '@/modules/elements/components/BulkActionsBar.vue';
  import {useElementIndexSelection} from '@/modules/elements/composables/useElementIndexSelection';
  import type {BulkActionItem} from '@/modules/elements/types/actions';

  const props = withDefaults(
    defineProps<{
      table: any;
      selectable?: boolean;
      readOnly?: boolean;
      loading?: boolean;
      from?: number;
      to?: number;
      total?: number;
      enableAdjustPageSize?: boolean;
      pageSizeOptions?: Array<number>;
      actions?: Array<BulkActionItem> | null;
      elementType?: string;
      source?: string | null;
      context?: string;
    }>(),
    {
      selectable: false,
      loading: false,
      enableAdjustPageSize: false,
      pageSizeOptions: () => [50, 100, 250],
      actions: () => [],
      source: null,
      context: 'index',
    },
  );

  const emit = defineEmits<{'action-performed': []}>();

  const page = usePage<{readOnly: boolean}>();
  const readOnly = computed(() => props.readOnly ?? page.props.readOnly);

  const {
    selectedIds,
    hasSelection,
    showBulkActions,
    bulkActionsActive,
    clearSelection,
  } = useElementIndexSelection(
    () => props.table,
    {
      selectable: () => props.selectable,
      readOnly,
      actions: () => props.actions,
    },
  );

  function onActionPerformed() {
    clearSelection();
    emit('action-performed');
  }

  // --- Pagination footer proxies (moved verbatim from AdminTable) ---
  const pageIndexProxy = computed({
    get: () => props.table.getState().pagination.pageIndex + 1,
    set: (v) => {
      if (v) props.table.setPageIndex(parseInt(String(v)) - 1);
    },
  });
  const pageSizeProxy = computed({
    get: () => props.table.getState().pagination.pageSize,
    set: (v) => {
      if (v) props.table.setPageSize(parseInt(String(v)));
    },
  });
  const showPagination = computed(() => props.table.getPageCount() > 1);
  const showPageSize = computed(() => props.enableAdjustPageSize);
  const showDisplayedRows = computed(
    () => props.from && props.to && props.total,
  );
  const showFooter = computed(
    () =>
      showPagination.value ||
      showPageSize.value ||
      showDisplayedRows.value ||
      (showBulkActions.value && hasSelection.value),
  );

  // --- ARIA live region ---
  const liveMessage = ref('');
  watch(
    () => props.loading,
    (isLoading, was) => {
      if (isLoading) liveMessage.value = t('Loading…');
      else if (was && props.total != null)
        liveMessage.value = t('{total, plural, =1{# item} other{# items}}', {
          total: props.total ?? 0,
        });
    },
  );
  watch(selectedIds, (ids) => {
    liveMessage.value = ids.length
      ? t('{num, plural, =1{# item selected} other{# items selected}}', {
          num: ids.length,
        })
      : t('Selection cleared');
  });
</script>

<template>
  <div class="element-index">
    <div class="element-index__header" v-if="$slots.header">
      <slot name="header"></slot>
    </div>

    <div class="element-index__body" :aria-busy="loading ? 'true' : undefined">
      <slot name="body"></slot>
    </div>

    <div
      class="element-index__footer"
      :class="{'element-index__footer--has-selection': showBulkActions && hasSelection}"
      v-if="showFooter"
    >
      <div class="element-index__footer-lead">
        <BulkActionsBar
          v-if="showBulkActions && hasSelection"
          :selected-ids="selectedIds"
          :actions="actions"
          :element-type="elementType ?? ''"
          :source="source"
          :context="context"
          @performed="onActionPerformed"
          @clear="clearSelection"
        />
        <Text
          v-else-if="showDisplayedRows"
          template="{from} – {to} of {total, plural, =1{# item} other{# items}}"
          :params="{from: from ?? 0, to: to ?? 0, total: total ?? 0}"
        />
      </div>
      <div class="flex gap-1">
        <template v-if="showPagination && !bulkActionsActive">
          <craft-button
            type="button"
            @click="table.previousPage()"
            :disabled="!table.getCanPreviousPage()"
            :appearance="Appearance.Plain"
            icon
            size="small"
          >
            <craft-icon name="chevron-left" :label="t('Previous page')"></craft-icon>
          </craft-button>
          <div class="flex items-center gap-1 mx-2">
            {{ t('Page') }}
            <craft-input
              type="text"
              v-model="pageIndexProxy"
              maxlength="3"
              :label="t('Current page')"
              label-sr-only
              center
              size="small"
              style="width: 4ch"
            />
            {{ t('of') }}
            {{ table.getPageCount() }}
          </div>
          <craft-button
            type="button"
            @click="table.nextPage()"
            :disabled="!table.getCanNextPage()"
            size="small"
            :appearance="Appearance.Plain"
            icon
          >
            <craft-icon name="chevron-right" :label="t('Next page')"></craft-icon>
          </craft-button>
        </template>
      </div>
      <div class="flex gap-2 items-center">
        <template v-if="showPageSize && !bulkActionsActive">
          {{ t('Items per page:') }}
          <Select small :options="pageSizeOptions!" v-model="pageSizeProxy" class="w-auto" />
        </template>
      </div>
    </div>

    <span class="sr-only" role="status" aria-live="polite">{{ liveMessage }}</span>
  </div>
</template>

<style scoped lang="scss">
  .element-index {
    overflow-y: clip;
  }

  .element-index__body {
    overflow-x: auto;
  }

  .element-index__footer {
    position: sticky;
    bottom: 0;
    z-index: 1;
    background-color: var(--c-surface-default);
  }

  .element-index__footer--has-selection .element-index__footer-lead {
    flex: 1 1 auto;
  }
</style>
```

- [ ] **Step 2: Typecheck + lint**

Run: `npm run typecheck && npx eslint resources/js/modules/elements/components/BaseElementIndex.vue`
Expected: no errors. (If `t()`'s ICU plural signature complains, match the exact `t()` call style already used in `AdminTable.vue`'s `Text` usage — category as third arg — and re-run.)

- [ ] **Step 3: Commit**

```bash
git add resources/js/modules/elements/components/BaseElementIndex.vue
git commit -m "Add BaseElementIndex shell with footer and aria-live region"
```

---

## Task 3: `DataTable.vue` (bare table core)

**Files:**
- Create: `resources/js/modules/elements/components/DataTable.vue`

**Interfaces:**
- Consumes: `useElementIndexSelection` (Task 1); `@tanstack/vue-table` `FlexRender`; `ColumnHeaderTitle`, `DropIndicator`, `LoadingSkeleton`, `useReorderableRows` (all already imported by today's `AdminTable`).
- Produces: props `{table, selectable?, readOnly?, loading?, reorderable?, layout?, spacing?, title?}`, emit `reorder: [startIndex, finishIndex]`.

This task **moves** the entire `<table>` render (thead/tbody, columns, reorder handles, skeleton, caption) out of today's `AdminTable.vue` (lines 279–479 template + supporting script) into `DataTable.vue`, replacing inline selection handlers with the composable.

- [ ] **Step 1: Create the component by extracting AdminTable's table body**

Create `resources/js/modules/elements/components/DataTable.vue`. Copy the following from the current `AdminTable.vue` **unchanged** except where noted:
- Script: imports for `Column`, `FlexRender`, `useId`, `useReorderableRows`, `TableSpacing`/`TableSpacingValue`, `ColumnHeaderTitle`, `DropIndicator`, `LoadingSkeleton`; the `resolveMetaClasses`, `getAriaSortAttribute`, `visibleColumnCount`, `tableStyles`, `skeletonCount`, `skeletonColumns`, `getClosestEdge`, `getRowPosition`, `titleString`, and reorder wiring (`setRowRef`, `setHandleRef`, `getDragState`, `getDropState`).
- Template: the `<table>…</table>` block (current lines 292–479) plus the `LoadingSkeleton` branch (285–291).

Then make these **changes**:
1. Props become `{table, selectable?, readOnly?, loading?, reorderable?, layout?, spacing?, title?}` (drop the footer/pagination/actions/source/context/elementType props — those now live on `BaseElementIndex`).
2. Resolve `readOnly` the same way (`props.readOnly ?? usePage().props.readOnly`).
3. Replace the inline selection functions with the composable:

```ts
  import {useElementIndexSelection} from '@/modules/elements/composables/useElementIndexSelection';

  const {onToggleAllSelected, selectRow} = useElementIndexSelection(
    () => props.table,
    {
      selectable: () => props.selectable ?? false,
      readOnly,
      actions: () => [], // actions/bulk bar live on BaseElementIndex
    },
  );

  // Captures modifier state from the native click, because craft-checkbox's
  // `model-value-changed` event does not carry `shiftKey`.
  const pendingShiftKey = ref(false);
  function rememberShift(event: MouseEvent) {
    pendingShiftKey.value = event.shiftKey;
  }
```

4. In the `<thead>` select-all checkbox, change the handler to:

```html
  <craft-checkbox
    label-sr-only
    .checked="table.getIsAllRowsSelected()"
    .indeterminate="table.getIsSomeRowsSelected()"
    .disabled="readOnly"
    @model-value-changed="onToggleAllSelected(($event.target as HTMLInputElement).checked)"
  >
```

5. In each row's select checkbox, wire the click-then-change pair:

```html
  <td v-if="selectable" class="cp-table-cell cp-table-cell--select">
    <craft-checkbox
      label-sr-only
      .checked="row.getIsSelected()"
      .disabled="readOnly || !row.getCanSelect()"
      @click="rememberShift($event)"
      @model-value-changed="
        selectRow(row, {
          checked: ($event.target as HTMLInputElement).checked,
          shiftKey: pendingShiftKey,
        })
      "
    >
      <label slot="label">{{ t('Select row') }}</label>
    </craft-checkbox>
  </td>
```

6. Keep the `<table>`'s existing `cp-table*` cell classes (`cp-table`, `cp-table-cell`, `cp-table-cell--select`, `cp-table--auto`, etc.) — those are the primitive and are **not** renamed.
7. Keep the scoped `<style>` block from today's `AdminTable.vue` **except** remove the three shell rules (`.cp-table-wrapper`, `.cp-table-footer`, `.cp-table-footer--has-selection …`) which now live on `BaseElementIndex`. Keep the `:deep(.cell*)`, `:deep(.cp-table-cell--select)`, `:deep(.cell--drag-handle)`, `:deep(.row--dragging)` rules.

- [ ] **Step 2: Typecheck + lint**

Run: `npm run typecheck && npx eslint resources/js/modules/elements/components/DataTable.vue`
Expected: no errors.

- [ ] **Step 3: Commit**

```bash
git add resources/js/modules/elements/components/DataTable.vue
git commit -m "Add DataTable bare table core using selection composable"
```

---

## Task 4: `AdminTable.vue` → thin wrapper

**Files:**
- Modify: `resources/js/modules/admin-table/components/AdminTable.vue` (full rewrite of the file)

**Interfaces:**
- Consumes: `BaseElementIndex` (Task 2), `DataTable` (Task 3).
- Produces: unchanged public API — same props and the `reorder` + `action-performed` emits the ~19 callers already use.

- [ ] **Step 1: Replace the file body with the wrapper**

Rewrite `resources/js/modules/admin-table/components/AdminTable.vue`:

```vue
<script setup lang="ts">
  import {computed} from 'vue';
  import BaseElementIndex from '@/modules/elements/components/BaseElementIndex.vue';
  import DataTable from '@/modules/elements/components/DataTable.vue';
  import {type TableSpacingValue} from '@/common/types';
  import type {BulkActionItem} from '@/modules/elements/types/actions';

  const props = withDefaults(
    defineProps<{
      table: any;
      title?: string;
      reorderable?: boolean;
      selectable?: boolean;
      readOnly?: boolean;
      loading?: boolean;
      layout?: 'auto' | 'fixed';
      spacing?: TableSpacingValue;
      from?: number;
      to?: number;
      total?: number;
      enableAdjustPageSize?: boolean;
      pageSizeOptions?: Array<number>;
      actions?: Array<BulkActionItem> | null;
      elementType?: string;
      source?: string | null;
      context?: string;
    }>(),
    {
      reorderable: false,
      selectable: false,
      loading: false,
      layout: 'auto',
      enableAdjustPageSize: false,
      pageSizeOptions: () => [50, 100, 250],
      actions: () => [],
      source: null,
      context: 'index',
    },
  );

  const emit = defineEmits<{
    reorder: [startIndex: number, finishIndex: number];
    'action-performed': [];
  }>();

  const baseProps = computed(() => ({
    table: props.table,
    selectable: props.selectable,
    readOnly: props.readOnly,
    loading: props.loading,
    from: props.from,
    to: props.to,
    total: props.total,
    enableAdjustPageSize: props.enableAdjustPageSize,
    pageSizeOptions: props.pageSizeOptions,
    actions: props.actions,
    elementType: props.elementType,
    source: props.source,
    context: props.context,
  }));

  const viewProps = computed(() => ({
    table: props.table,
    selectable: props.selectable,
    readOnly: props.readOnly,
    loading: props.loading,
    reorderable: props.reorderable,
    layout: props.layout,
    spacing: props.spacing,
    title: props.title,
  }));
</script>

<template>
  <BaseElementIndex v-bind="baseProps" @action-performed="emit('action-performed')">
    <template #header v-if="$slots['table-header']">
      <slot name="table-header"></slot>
    </template>
    <template #body>
      <DataTable
        v-bind="viewProps"
        @reorder="(s: number, f: number) => emit('reorder', s, f)"
      />
    </template>
  </BaseElementIndex>
</template>
```

- [ ] **Step 2: Typecheck + lint**

Run: `npm run typecheck && npx eslint resources/js/modules/admin-table/components/AdminTable.vue`
Expected: no errors.

- [ ] **Step 3: Verify no standalone caller referenced a removed API**

Run: `grep -rn "cp-table-wrapper\|cp-table-footer\|cp-table-header\|cp-table-body" resources/js --include=*.vue`
Expected: no matches in `resources/js` (all shell-class usage now lives inside `BaseElementIndex`'s scoped styles). If any page referenced these classes directly, update it to not depend on AdminTable internals.

- [ ] **Step 4: Commit**

```bash
git add resources/js/modules/admin-table/components/AdminTable.vue
git commit -m "Rewrite AdminTable as thin BaseElementIndex + DataTable wrapper"
```

---

## Task 5: `ElementCards.vue` → bare card grid

**Files:**
- Modify: `resources/js/modules/elements/components/ElementCards.vue` (full rewrite)

**Interfaces:**
- Consumes: `useElementIndexSelection` (Task 1).
- Produces: props `{table, data?, selectable?, readOnly?, loading?}`, no footer/pagination props (those move to `BaseElementIndex`). No emits (bulk-action refresh is owned by `BaseElementIndex`).

- [ ] **Step 1: Rewrite the component as body-only**

Rewrite `resources/js/modules/elements/components/ElementCards.vue`, keeping the card grid markup (current lines 156–209) and dropping the wrapper/footer (lines 146–151 wrapper open, 212–291 footer). New file:

```vue
<script setup lang="ts">
  import {attrs, t} from '@craftcms/cp';
  import {computed, ref} from 'vue';
  import {usePage} from '@inertiajs/vue3';
  import Empty from '@/common/components/Empty.vue';
  import DynamicHtmlRenderer from '@/common/components/DynamicHtmlRenderer.vue';
  import {useElementIndexSelection} from '@/modules/elements/composables/useElementIndexSelection';

  type CardElement = Record<any, any>;

  const props = withDefaults(
    defineProps<{
      table: any;
      data?: Array<CardElement>;
      selectable?: boolean;
      readOnly?: boolean;
      loading?: boolean;
    }>(),
    {data: () => [], selectable: false, loading: false},
  );

  const page = usePage<{readOnly: boolean}>();
  const readOnly = computed(() => props.readOnly ?? page.props.readOnly);

  const {onToggleAllSelected, selectRow} = useElementIndexSelection(
    () => props.table,
    {selectable: () => props.selectable, readOnly, actions: () => []},
  );

  function rowFor(id: number | string) {
    return props.table.getRow(String(id));
  }

  const pendingShiftKey = ref(false);
  function rememberShift(event: MouseEvent) {
    pendingShiftKey.value = event.shiftKey;
  }
</script>

<template>
  <div class="grid place-items-center min-h-50" v-if="loading">
    <craft-spinner></craft-spinner>
  </div>
  <template v-else-if="data!.length > 0">
    <div class="card-grid-header" v-if="selectable">
      <craft-checkbox
        label-sr-only
        .checked="table.getIsAllRowsSelected()"
        .indeterminate="table.getIsSomeRowsSelected()"
        .disabled="readOnly"
        @model-value-changed="onToggleAllSelected(($event.target as HTMLInputElement).checked)"
      >
        <label slot="label">{{ t('Select all') }}</label>
      </craft-checkbox>
    </div>

    <ul class="card-grid">
      <li
        v-for="element in data"
        :key="element.id"
        :data-id="element.id"
        :class="{element: true, sel: rowFor(element.id)?.getIsSelected()}"
      >
        <craft-card
          v-bind="attrs(element.cardAttributes, {exclude: ['class']})"
          :active="rowFor(element.id)?.getIsSelected()"
        >
          <div slot="header">
            <div class="flex gap-2 items-center">
              <craft-checkbox
                v-if="selectable"
                label-sr-only
                .checked="rowFor(element.id)?.getIsSelected()"
                .disabled="readOnly || !rowFor(element.id)?.getCanSelect()"
                @click="rememberShift($event)"
                @model-value-changed="
                  selectRow(rowFor(element.id), {
                    checked: ($event.target as HTMLInputElement).checked,
                    shiftKey: pendingShiftKey,
                  })
                "
              >
                <label slot="label">{{ t('Select') }}</label>
              </craft-checkbox>
              <DynamicHtmlRenderer :html="element.cardHeaderHtml" />
            </div>
          </div>
          <DynamicHtmlRenderer :html="element.cardContentHtml" />
          <DynamicHtmlRenderer :html="element.cardFooterHtml" slot="footer" />
        </craft-card>
      </li>
    </ul>
  </template>
  <template v-else>
    <slot name="empty">
      <Empty :label="t('No results')" icon="empty-set" />
    </slot>
  </template>
</template>

<style scoped lang="scss">
  .card-grid-header {
    padding: var(--c-spacing-md);
    background-color: var(--c-color-neutral-fill-quiet);
    border-block-end: 1px solid var(--c-color-neutral-border-quiet);
  }

  .card-grid {
    padding: var(--c-spacing-md);
  }

  .card-grid > li {
    position: relative;
  }
</style>
```

- [ ] **Step 2: Typecheck + lint**

Run: `npm run typecheck && npx eslint resources/js/modules/elements/components/ElementCards.vue`
Expected: no errors.

- [ ] **Step 3: Commit**

```bash
git add resources/js/modules/elements/components/ElementCards.vue
git commit -m "Slim ElementCards to a bare card grid body"
```

---

## Task 6: `content/Index.vue` composes `BaseElementIndex`

**Files:**
- Modify: `resources/js/pages/content/Index.vue` (script lines ~179–207 and template lines ~240–261)

**Interfaces:**
- Consumes: `BaseElementIndex`, `DataTable`, `ElementCards`.

- [ ] **Step 1: Update imports**

In `resources/js/pages/content/Index.vue`, replace:
```ts
  import AdminTable from '@/modules/admin-table/components/AdminTable.vue';
  import ElementCards from '@/modules/elements/components/ElementCards.vue';
```
with:
```ts
  import BaseElementIndex from '@/modules/elements/components/BaseElementIndex.vue';
  import DataTable from '@/modules/elements/components/DataTable.vue';
  import ElementCards from '@/modules/elements/components/ElementCards.vue';
```

- [ ] **Step 2: Remove the `indexComponent`/`sharedProps`/`modeSpecificProps` juggling**

Delete the `indexComponent`, `sharedProps`, and `modeSpecificProps` computeds (current lines ~179–207) and the now-unused `Component` import. Keep `TableSpacing` (used below).

- [ ] **Step 3: Rewrite the template body**

Replace the `<component :is="indexComponent" …>` block (current lines ~240–261) with:

```html
    <BaseElementIndex
      :table="elementTable"
      :selectable="true"
      :loading="loading"
      :from="props.pagination.from"
      :to="props.pagination.to"
      :total="props.pagination.total"
      :enable-adjust-page-size="true"
      :actions="props.actions"
      :element-type="props.elementType"
      :source="props.source?.key"
      :context="props.context"
      @action-performed="onActionPerformed"
    >
      <template #header>
        <ElementIndexToolbar
          v-model:search="filters.form.search"
          v-model:status="filters.form.status"
          :processing="filters.form.processing"
          :status-options="statusOptions"
          :view-modes="visibleViewModes"
          :column-options="columnOptions"
          v-model:mode="mode"
          v-model:sort-field="sortField"
          v-model:sort-direction="sortDirection"
          v-model:table-columns="tableColumns"
          @submit="filters.submit"
          @reorder="reorder"
        />
      </template>
      <template #body>
        <ElementCards
          v-if="mode === 'cards'"
          :table="elementTable"
          :data="props.data"
          :selectable="true"
          :loading="loading"
        />
        <DataTable
          v-else
          :table="elementTable"
          :selectable="true"
          :loading="loading"
          :spacing="TableSpacing.Spacious"
        />
      </template>
    </BaseElementIndex>
```

- [ ] **Step 4: Typecheck + lint**

Run: `npm run typecheck && npx eslint resources/js/pages/content/Index.vue`
Expected: no errors.

- [ ] **Step 5: Manual preview verification**

Run the dev server (`npm run dev`) and open an element index (e.g. Entries). Confirm: table view renders with pagination footer; switching to cards view keeps the same footer/selection; selecting rows shows the bulk-actions bar; "X–Y of Z" is correct. This is the parity check the component tests would otherwise cover (no component-mount infra exists in `resources/js`).

- [ ] **Step 6: Commit**

```bash
git add resources/js/pages/content/Index.vue
git commit -m "Compose BaseElementIndex directly on the content index page"
```

---

## Task 7: Remove dead shell CSS from `base.css`

**Files:**
- Modify: `packages/craftcms-cp/src/styles/shared/base.css`

- [ ] **Step 1: Delete the shell rules**

In `packages/craftcms-cp/src/styles/shared/base.css`, remove the now-unused shell rules: `.cp-table-header`, `.cp-table-footer`, and `.cp-table-body__header` (around lines 340–355). Leave every `.cp-table` / `.cp-table-cell` / `.cp-table--*` rule intact.

- [ ] **Step 2: Confirm nothing else references the removed classes**

Run:
```bash
grep -rn "cp-table-wrapper\|cp-table-header\|cp-table-footer\|cp-table-body\b\|cp-table-body__header" resources packages --include=*.vue --include=*.scss --include=*.css | grep -vE "dist/|build/"
```
Expected: no matches.

- [ ] **Step 3: Lint styles**

Run: `npm run lint:styles`
Expected: no errors.

- [ ] **Step 4: Commit**

```bash
git add packages/craftcms-cp/src/styles/shared/base.css
git commit -m "Remove dead cp-table shell rules superseded by element-index"
```

---

## Task 8: Keyboard selection navigation in `DataTable`

**Files:**
- Modify: `resources/js/modules/elements/components/DataTable.vue`

**Interfaces:**
- Consumes: `toggleRow`, `extendSelectionTo`, `selectRow` from the selection composable (already destructured — add `toggleRow`, `extendSelectionTo`).

- [ ] **Step 1: Add roving-focus + key handling to selectable rows**

In `DataTable.vue`, add `toggleRow` and `extendSelectionTo` to the composable destructure. On the `<tr>` for data rows, when `selectable`, add `tabindex="0"` and a keydown handler:

```html
  <tr
    v-for="(row, rowIdx) in table.getRowModel().rows"
    :key="row.id"
    :tabindex="selectable ? 0 : undefined"
    @keydown="onRowKeydown(row, rowIdx, $event)"
    ... existing bindings ...
  >
```

Add the handler in script:

```ts
  function focusRowByIndex(index: number, el: HTMLElement) {
    const table = el.closest('table');
    const rows = table?.querySelectorAll<HTMLElement>('tbody > tr[tabindex]');
    rows?.[index]?.focus();
  }

  function onRowKeydown(row: any, index: number, event: KeyboardEvent) {
    if (!props.selectable) return;
    const rows = props.table.getRowModel().rows;
    const target = event.currentTarget as HTMLElement;
    switch (event.key) {
      case ' ':
      case 'Enter':
        event.preventDefault();
        toggleRow(row);
        break;
      case 'ArrowDown':
        event.preventDefault();
        if (event.shiftKey) extendSelectionTo(rows[Math.min(index + 1, rows.length - 1)]);
        focusRowByIndex(Math.min(index + 1, rows.length - 1), target);
        break;
      case 'ArrowUp':
        event.preventDefault();
        if (event.shiftKey) extendSelectionTo(rows[Math.max(index - 1, 0)]);
        focusRowByIndex(Math.max(index - 1, 0), target);
        break;
    }
  }
```

- [ ] **Step 2: Typecheck + lint**

Run: `npm run typecheck && npx eslint resources/js/modules/elements/components/DataTable.vue`
Expected: no errors.

- [ ] **Step 3: Manual preview verification**

On an element index (table view): focus a row, press Space to toggle selection, Arrow up/down to move focus, Shift+Arrow to extend selection. Confirm the aria-live region announces "N items selected".

- [ ] **Step 4: Commit**

```bash
git add resources/js/modules/elements/components/DataTable.vue
git commit -m "Add keyboard selection navigation to DataTable rows"
```

---

## Task 9: Keyboard selection navigation in `ElementCards`

**Files:**
- Modify: `resources/js/modules/elements/components/ElementCards.vue`

- [ ] **Step 1: Add key handling to selectable cards**

In `ElementCards.vue`, add `toggleRow` and `extendSelectionTo` to the composable destructure. On the `<li>` add `tabindex` + keydown when selectable, resolving the row via `rowFor(element.id)`:

```html
  <li
    v-for="(element, cardIdx) in data"
    :key="element.id"
    :data-id="element.id"
    :tabindex="selectable ? 0 : undefined"
    @keydown="onCardKeydown(element.id, cardIdx, $event)"
    :class="{element: true, sel: rowFor(element.id)?.getIsSelected()}"
  >
```

```ts
  function focusCardByIndex(index: number, el: HTMLElement) {
    const list = el.closest('ul.card-grid');
    const items = list?.querySelectorAll<HTMLElement>(':scope > li[tabindex]');
    items?.[index]?.focus();
  }

  function onCardKeydown(id: number | string, index: number, event: KeyboardEvent) {
    if (!props.selectable) return;
    const target = event.currentTarget as HTMLElement;
    const last = props.data!.length - 1;
    switch (event.key) {
      case ' ':
      case 'Enter':
        event.preventDefault();
        toggleRow(rowFor(id));
        break;
      case 'ArrowRight':
      case 'ArrowDown':
        event.preventDefault();
        if (event.shiftKey) extendSelectionTo(rowFor(props.data![Math.min(index + 1, last)].id));
        focusCardByIndex(Math.min(index + 1, last), target);
        break;
      case 'ArrowLeft':
      case 'ArrowUp':
        event.preventDefault();
        if (event.shiftKey) extendSelectionTo(rowFor(props.data![Math.max(index - 1, 0)].id));
        focusCardByIndex(Math.max(index - 1, 0), target);
        break;
    }
  }
```

- [ ] **Step 2: Typecheck + lint**

Run: `npm run typecheck && npx eslint resources/js/modules/elements/components/ElementCards.vue`
Expected: no errors.

- [ ] **Step 3: Manual preview verification**

On an element index (cards view): focus a card, Space toggles selection, Arrow keys move focus, Shift+Arrow extends. Confirm parity with the table view.

- [ ] **Step 4: Commit**

```bash
git add resources/js/modules/elements/components/ElementCards.vue
git commit -m "Add keyboard selection navigation to ElementCards"
```

---

## Final verification

- [ ] `npx vitest run resources/js/modules/elements/composables/useElementIndexSelection.test.ts` — green.
- [ ] `npm run typecheck` — clean.
- [ ] `npm run lint` — clean (js + styles + typecheck).
- [ ] `grep -rn "cp-table-wrapper\|cp-table-header\|cp-table-footer\|cp-table-body__header" resources packages --include=*.vue --include=*.scss --include=*.css | grep -vE "dist/|build/"` — no matches.
- [ ] Manual preview: element index table + cards views (footer, selection, bulk actions, keyboard nav) and one standalone `AdminTable` page (e.g. Settings → Fields) render correctly.

## Notes for the implementer

- **Why the click-then-change pair for checkboxes:** `craft-checkbox` is a Lion web component; its `model-value-changed` event does not include `shiftKey`. The `@click` handler stashes the modifier into `pendingShiftKey` just before `model-value-changed` fires, so `selectRow` receives the correct shift state. Keep both handlers on the same element.
- **Do not** rename `.cp-table` / `.cp-table-cell` / `.cp-table--*` — legacy Twig (`editableTable.twig`, `PhpInfo.twig`) and Storybook depend on them.
- **No component-mount tests:** `resources/js` has no `@vue/test-utils`/jsdom setup. Component behavior is verified via typecheck + lint + manual preview. If the team later adds mount infra, `BaseElementIndex`/`DataTable`/`ElementCards` smoke tests are the natural follow-up.
