# AdminTable Component

The `AdminTable` component renders data tables in the Craft CMS Control Panel. It wraps [TanStack Table (Vue)](https://tanstack.com/table/latest) with Craft's styling, accessibility, pagination, sorting, reordering, and empty-state handling.

## Basic Usage

```vue
<script setup lang="ts">
import {getCoreRowModel, useVueTable} from '@tanstack/vue-table';
import AdminTable from '@/components/AdminTable/AdminTable.vue';
import {createCraftColumnHelper} from '@/components/AdminTable/createCraftColumnHelper';
import {t} from '@craftcms/cp';

interface RowData {
  id: number;
  name: string;
  handle: string;
}

const props = defineProps<{
  data: Array<RowData>;
}>();

const columnHelper = createCraftColumnHelper<RowData>();

const columns = [
  columnHelper.link('name', {
    header: t('Name'),
    props: ({row}) => ({href: `/admin/things/${row.original.id}`, inertia: false}),
  }),
  columnHelper.handle('handle'),
];

const table = useVueTable({
  get data() {
    return props.data;
  },
  columns,
  getCoreRowModel: getCoreRowModel<RowData>(),
});
</script>

<template>
  <AdminTable :table="table" />
</template>
```

## Props

| Prop | Type | Default | Description |
|------|------|---------|-------------|
| `table` | TanStack `Table` instance | *required* | The table instance created by `useVueTable()`. |
| `title` | `string` | — | Table caption for screen readers (prefixed to sort instructions). |
| `reorderable` | `boolean` | `false` | Enables drag-and-drop row reordering with a drag handle column. |
| `selectable` | `boolean` | `true` | Reserved for future row selection support. |
| `readOnly` | `boolean` | — | When `true`, hides reorder handles (used with `reorderable`). |
| `layout` | `'auto' \| 'fixed'` | `'auto'` | CSS table layout mode. |
| `spacing` | `TableSpacingValue` | — | Row density: `'compact'`, `'relaxed'`, or `'spacious'`. |
| `from` | `number` | — | Start index of displayed rows (for "X–Y of Z" display). |
| `to` | `number` | — | End index of displayed rows. |
| `total` | `number` | — | Total number of items (all pages). |
| `enableAdjustPageSize` | `boolean` | `false` | Shows a "per page" dropdown in the footer. |
| `pageSizeOptions` | `number[]` | `[50, 100, 250]` | Options for the page-size dropdown. |

## Events

| Event | Payload | Description |
|-------|---------|-------------|
| `reorder` | `(startIndex: number, finishIndex: number)` | Emitted when a row is reordered via drag-and-drop or the keyboard buttons. |

## Slots

### `search-form`

Renders above the table header. Use with the `SearchForm` component for server-side search:

```vue
<AdminTable :table="table">
  <template #search-form>
    <SearchForm :action="indexRoute()" v-model="searchTerm" />
  </template>
</AdminTable>
```

### `empty-row`

Custom content shown when the table has no rows. Falls back to a generic "No results" message.

```vue
<AdminTable :table="table">
  <template #empty-row>
    <Empty :label="t('No volumes exist yet.')" icon="light/files">
      <CpLink appearance="button" :href="create().url">
        {{ t('New volume') }}
      </CpLink>
    </Empty>
  </template>
</AdminTable>
```

## Column Meta Options

TanStack Table's `meta` object on column definitions is used by `AdminTable` to control rendering behavior:

| Meta Key | Type | Description |
|----------|------|-------------|
| `trackSize` | `string` | CSS grid track size for the column (e.g., `'1.5fr'`, `'34px'`, `'60px'`). Defaults to `1fr`. |
| `headerSrOnly` | `boolean` | Visually hides the header text (still available to screen readers). |
| `headerTip` | `string` | Displays an info icon tooltip next to the header. |
| `columnClass` | `string \| object` | CSS classes applied to both header and body cells. |
| `headerClass` | `string \| object` | CSS classes applied only to header cells. |
| `cellClass` | `string \| object` | CSS classes applied only to body cells. |
| `cellTag` | `string` | Override the cell HTML element (defaults to `'td'`). |
| `wrap` | `boolean` | Enables text wrapping in cells (cells are `nowrap` by default). |

Example:

```ts
columnHelper.accessor('searchable', {
  header: t('Searchable'),
  meta: {
    trackSize: '34px',
    headerSrOnly: true,
  },
  enableSorting: false,
  cell: ({row}) => {
    if (row.original.searchable) {
      return h('craft-icon', {
        appearance: 'badge',
        name: 'magnifying-glass',
        label: t('Searchable'),
      });
    }
  },
});
```

---

## `createCraftColumnHelper`

The `createCraftColumnHelper<T>()` factory extends TanStack's `createColumnHelper` with Craft-specific column presets for common cell types. It returns a `CraftColumnHelper<T>` that includes all the standard TanStack methods (`accessor`, `display`, `group`) plus four additional helpers:

### `columnHelper.link(accessor, config?)`

Renders the cell value as a bold `CpLink`. Use for the primary name/title column.

```ts
columnHelper.link('name', {
  header: t('Name'),
  props: ({row}) => ({
    href: `/admin/things/${row.original.id}/edit`,
    inertia: false, // use a plain <a> tag (set true for Inertia navigation)
  }),
});
```

The `props` function receives the cell context and should return props for the `CpLink` component (e.g., `href`, `inertia`, `variant`).

### `columnHelper.handle(accessor, config?)`

Renders the cell value inside a `<craft-copy-attribute>` web component, showing the handle with a click-to-copy button. Automatically sets the header to "Handle".

```ts
columnHelper.handle('handle');

// With a custom header:
columnHelper.handle('handle', {header: t('API Handle')});
```

### `columnHelper.date(accessor, config?)`

Renders date values using the `Date` component, which formats them according to the user's locale. Handles both raw date strings and objects with a `.date` property. Displays "Never" when the value is empty.

```ts
columnHelper.date('lastUsed', {
  header: t('Last Used'),
});

columnHelper.date('expiryDate', {
  header: t('Expires'),
});
```

### `columnHelper.actions(actionsFn, config?)`

Creates a display column (id: `'actions'`) for row action buttons. The header is set to "Actions" and visually hidden (screen-reader only). Actions are rendered in a right-aligned flex container.

```ts
columnHelper.actions(({row}) => [
  h(DeleteButton, {onClick: () => deleteItem(row.original)}),
]);
```

The first argument is a function receiving the cell context and returning an array of VNodes (typically buttons). You can render any combination of components:

```ts
columnHelper.actions(({row}) => [
  h(CpLink, {href: editUrl(row.original), appearance: 'button', size: 'small'}, () => t('Edit')),
  h(DeleteButton, {onClick: () => handleDelete(row.original)}),
]);
```

### Using `accessor` and `display` directly

The standard TanStack helpers are still available for columns that don't fit the presets:

```ts
// Simple text column — just renders the value
columnHelper.accessor('type', {
  header: t('Type'),
});

// Custom cell rendering with accessor
columnHelper.accessor('type', {
  header: t('Type'),
  cell: ({row, getValue}) => {
    if (row.original.missing) {
      return h('span', {class: 'c-color-error'}, getValue());
    }
    return getValue();
  },
});

// Display column (no data accessor)
columnHelper.display({
  id: 'type',
  header: t('Type'),
  cell: ({row}) => h('div', {class: 'flex items-center gap-2'}, [
    h('craft-icon', row.original.type.icon),
    h('span', row.original.type.label),
  ]),
});
```

---

## Column Visibility

Control which columns are shown using TanStack's `columnVisibility` state. This is useful for hiding the actions column when the user is in read-only mode:

```ts
const table = useVueTable({
  data: props.data,
  columns,
  state: {
    get columnVisibility() {
      return {
        name: true,
        handle: true,
        actions: !props.readOnly,
      };
    },
  },
  getCoreRowModel: getCoreRowModel(),
});
```

## Reorderable Rows

Enable drag-and-drop reordering by setting `:reorderable="true"` and handling the `@reorder` event. The component adds a drag handle column and keyboard-accessible up/down buttons.

```vue
<script setup lang="ts">
import {ref, watch, nextTick} from 'vue';
import {router} from '@inertiajs/vue3';

const itemIds = ref(props.items.map((item) => item.id));
const items = computed(() =>
  itemIds.value
    .map((id) => props.items.find((item) => item.id === id))
    .filter(Boolean)
);

function handleReorder(startIndex: number, finishIndex: number) {
  const newIds = [...itemIds.value];
  const [id] = newIds.splice(startIndex, 1);
  newIds.splice(finishIndex, 0, id);
  itemIds.value = newIds;
}

// Persist the new order to the server
watch(itemIds, (newValue, oldValue) => {
  nextTick(() => {
    router.post(reorderRoute(), {ids: [...newValue]}, {
      preserveScroll: true,
      preserveState: true,
      onError: () => { itemIds.value = oldValue; },
    });
  });
});
</script>

<template>
  <AdminTable
    :table="table"
    :reorderable="true"
    :read-only="readOnly"
    @reorder="handleReorder"
  />
</template>
```

## Server-Side Pagination & Sorting

For paginated data, use the `useServerPagination` and `useServerSort` composables and pass the pagination display props:

```vue
<script setup lang="ts">
import {useServerPagination} from '@/composables/useServerPagination';
import {useServerSort} from '@/composables/useServerSort';

const {paginationState, paginationConfig} = useServerPagination({
  initialState: props.pagination,
  onChange: ({query}) => {
    router.visit(indexRoute({query}), {
      only: ['data', 'pagination'],
      preserveScroll: true,
    });
  },
});

const {sortingState, sortingConfig} = useServerSort({
  initialState: props.sort,
  onChange: ({query}) => {
    router.visit(indexRoute({query}), {
      only: ['data', 'sort'],
      preserveScroll: true,
    });
  },
});

const table = useVueTable({
  get data() { return props.data; },
  columns,
  getCoreRowModel: getCoreRowModel(),
  state: {
    get pagination() { return paginationState.value; },
    get sorting() { return sortingState.value; },
  },
  ...paginationConfig,
  ...sortingConfig,
});
</script>

<template>
  <AdminTable
    :table="table"
    :from="pagination.from"
    :to="pagination.to"
    :total="pagination.total"
    :enable-adjust-page-size="true"
  />
</template>
```

## Supporting Components

| Component | Location | Description |
|-----------|----------|-------------|
| `SearchForm` | `@/components/AdminTable/SearchForm.vue` | Debounced search input with Inertia form submission. |
| `DeleteButton` | `@/components/AdminTable/DeleteButton.vue` | Small danger button with an "×" icon for row deletion. |
| `CpLink` | `@/components/CpLink.vue` | Link component supporting both Inertia and plain `<a>` navigation. |
| `Empty` | `@/components/Empty.vue` | Empty state display with icon and optional action slot. |
