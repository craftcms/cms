import {h, type MaybeRef} from 'vue';
import {
  createColumnHelper,
  createPaginatedRowModel,
  createSortedRowModel,
  sortFn_alphanumeric,
  sortFn_text,
  type Table,
  tableFeatures,
  useTable,
} from '@tanstack/vue-table';
import {
  craftTableFeatures,
  type CraftTableFeatures,
} from '@/common/table/craftTable';
import ElementStatus from '@/modules/elements/ElementStatus.vue';
import type {BulkActionItem} from '@/modules/elements/types/actions';

export interface SampleEntry {
  id: number;
  title: string;
  status: string;
  section: string;
  postDate: string;
  label?: string;
}

export const sampleEntries: Array<SampleEntry> = [
  {
    id: 1,
    title: 'Welcome to Craft 6',
    status: 'live',
    section: 'Blog',
    postDate: '2026-07-06',
  },
  {
    id: 2,
    title: 'Porting the element index to Vue',
    status: 'live',
    section: 'Blog',
    postDate: '2026-07-05',
  },
  {
    id: 3,
    title: 'Inertia in the control panel',
    status: 'live',
    section: 'Blog',
    postDate: '2026-07-04',
  },
  {
    id: 4,
    title: 'Structures, sections & sources',
    status: 'live',
    section: 'News',
    postDate: '2026-07-03',
  },
  {
    id: 5,
    title: 'Pagination without page reloads',
    status: 'live',
    section: 'News',
    postDate: '2026-07-02',
  },
  {
    id: 6,
    title: 'Searching every entry',
    status: 'live',
    section: 'Blog',
    postDate: '2026-07-01',
  },
  {
    id: 7,
    title: 'Sorting by any column',
    status: 'live',
    section: 'News',
    postDate: '2026-06-30',
  },
  {
    id: 8,
    title: 'Scheduled for next week',
    status: 'pending',
    section: 'Blog',
    postDate: '2026-07-13',
  },
  {
    id: 9,
    title: 'Expired promotion',
    status: 'expired',
    section: 'News',
    postDate: '2026-05-01',
  },
  {
    id: 10,
    title: 'Draft ideas',
    status: 'disabled',
    section: 'Blog',
    postDate: '2026-06-06',
  },
  {
    id: 11,
    title: 'Company retreat recap',
    status: 'live',
    section: 'News',
    postDate: '2026-06-20',
  },
  {
    id: 12,
    title: 'Mid-year roadmap',
    status: 'pending',
    section: 'Blog',
    postDate: '2026-07-20',
  },
].map((entry) => ({...entry, label: entry.title}));

// The CP's own tables sort and paginate on the server; the sample table has no
// server, so it does both on the client.
const sampleFeatures = tableFeatures({
  ...craftTableFeatures,
  sortedRowModel: createSortedRowModel(),
  paginatedRowModel: createPaginatedRowModel(),
  sortFns: {alphanumeric: sortFn_alphanumeric, text: sortFn_text},
});

const columnHelper = createColumnHelper<typeof sampleFeatures, SampleEntry>();

export const sampleColumns = columnHelper.columns([
  columnHelper.accessor('title', {header: 'Title'}),
  columnHelper.accessor('status', {
    header: 'Status',
    cell: ({getValue}) => h(ElementStatus, {value: getValue()}),
  }),
  columnHelper.accessor('section', {header: 'Section'}),
  columnHelper.accessor('postDate', {header: 'Post Date'}),
]);

/**
 * Builds a client-side TanStack table over the sample entries, with the row-id
 * and selection settings the element index components expect. Stories call
 * this inside `setup()`.
 */
export function createSampleTable(
  options: {data?: MaybeRef<Array<SampleEntry>>; pageSize?: number} = {}
): Table<CraftTableFeatures, SampleEntry> {
  const table = useTable<typeof sampleFeatures, SampleEntry>({
    features: sampleFeatures,
    data: options.data ?? sampleEntries,
    columns: sampleColumns,
    getRowId: (row) => String(row.id),
    enableRowSelection: true,
    initialState: {
      pagination: {pageIndex: 0, pageSize: options.pageSize ?? 50},
    },
  });

  // SAFETY: Row-model and sort-fn slots only change how rows are processed, not
  // the table's API, so this is the same surface as a plain CP table's.
  return table as unknown as Table<CraftTableFeatures, SampleEntry>;
}

/**
 * Card-shaped data matching what the server sends for the cards view mode:
 * per-card attribute bags plus server-rendered header/content/footer HTML.
 */
export const sampleCards = sampleEntries.slice(0, 6).map((entry) => ({
  id: entry.id,
  cardAttributes: {
    class: ['card'],
    data: {id: entry.id},
  },
  cardHeaderHtml: `<span class="text-sm">${entry.section}</span>`,
  cardContentHtml: `<div><a href="#">${entry.title}</a></div>`,
  cardFooterHtml: '',
}));

/**
 * A representative bulk-action set. The URLs are inert in Storybook — they
 * demonstrate the shape the server serializes, not live endpoints.
 */
export const sampleActions: Array<BulkActionItem> = [
  {
    key: 'demo\\Edit',
    label: 'Edit',
    action: {
      type: 'http',
      method: 'POST',
      url: '/demo/perform-action',
      body: {elementAction: 'demo\\Edit'},
    },
  },
  {
    key: 'demo\\Duplicate',
    label: 'Duplicate',
    action: {
      type: 'http',
      method: 'POST',
      url: '/demo/perform-action',
      body: {elementAction: 'demo\\Duplicate'},
    },
  },
  {
    key: 'demo\\Copy',
    label: 'Copy',
    action: {
      type: 'event',
      name: 'craft:copy-elements',
    },
  },
  {
    key: 'demo\\Delete',
    label: 'Delete',
    destructive: true,
    variant: 'danger',
    action: {
      type: 'http',
      method: 'POST',
      url: '/demo/perform-action',
      body: {elementAction: 'demo\\Delete'},
      confirm: 'Are you sure you want to delete the selected elements?',
    },
  },
];

/**
 * Element data in the shape `ElementSelect::presentation()` sends: what chips,
 * thumbnail tiles and element lists draw from.
 */
export interface SampleElement {
  id: number;
  label: string;
  siteId: number;
  status: {fill: string; label: string; draft: boolean} | null;
  color: string | null;
  cpEditUrl: string | null;
  url: string | null;
  thumbHtml?: string;
  level?: number | null;
}

const statusPayloads: Record<string, SampleElement['status']> = {
  live: {fill: 'teal', label: 'Live', draft: false},
  pending: {fill: 'sky', label: 'Pending', draft: false},
  expired: {fill: 'red', label: 'Expired', draft: false},
  disabled: {fill: 'gray', label: 'Disabled', draft: false},
};

/** A square placeholder image, standing in for a server-rendered thumbnail. */
function sampleThumbHtml(hue: number): string {
  const svg =
    '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 4 3">' +
    `<rect width="4" height="3" fill="hsl(${hue} 60% 75%)"/>` +
    `<circle cx="2.9" cy="0.9" r="0.45" fill="hsl(${hue} 70% 92%)"/>` +
    `<path d="M0 3 1.4 1.4 2.4 2.3 3 1.8 4 3z" fill="hsl(${hue} 45% 45%)"/>` +
    '</svg>';

  return `<img src="data:image/svg+xml,${encodeURIComponent(svg)}" alt="" style="width:100%;height:100%;object-fit:cover">`;
}

/** Entries as chips: a status dot, a section color, and no thumbnails. */
export const sampleElements: Array<SampleElement> = sampleEntries
  .slice(0, 6)
  .map((entry) => ({
    id: entry.id,
    label: entry.title,
    siteId: 1,
    status: statusPayloads[entry.status] ?? null,
    color: entry.section === 'Blog' ? 'blue' : 'amber',
    cpEditUrl: `/admin/content/entries/${entry.id}`,
    url: `/blog/${entry.id}`,
  }));

/** Assets as thumbnail tiles: each has a thumbnail and no status. */
export const sampleAssets: Array<SampleElement> = [
  'harbor-at-dawn.jpg',
  'team-offsite.jpg',
  'product-hero.png',
  'mountain-trail.jpg',
  'office-plants.jpg',
  'launch-banner.png',
  'night-market.jpg',
  'studio-portrait.jpg',
].map((filename, index) => ({
  id: 100 + index,
  label: filename,
  siteId: 1,
  status: null,
  color: null,
  cpEditUrl: `/admin/assets/edit/${100 + index}`,
  url: null,
  thumbHtml: sampleThumbHtml(index * 45),
}));

/** Categories in a structure, with the levels a hierarchical relation field nests by. */
export const sampleCategories: Array<SampleElement> = [
  {id: 201, label: 'Products', level: 1},
  {id: 202, label: 'Hardware', level: 2},
  {id: 203, label: 'Keyboards', level: 3},
  {id: 204, label: 'Mice', level: 3},
  {id: 205, label: 'Software', level: 2},
  {id: 206, label: 'Services', level: 1},
  {id: 207, label: 'Consulting', level: 2},
].map((category) => ({
  ...category,
  siteId: 1,
  status: null,
  color: null,
  cpEditUrl: `/admin/categories/${category.id}`,
  url: null,
}));
