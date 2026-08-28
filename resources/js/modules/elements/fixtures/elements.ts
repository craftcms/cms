import {h} from 'vue';
import {
  createColumnHelper,
  getCoreRowModel,
  getPaginationRowModel,
  getSortedRowModel,
  useVueTable,
} from '@tanstack/vue-table';
import ElementStatus from '@/modules/elements/ElementStatus.vue';
import type {BulkActionItem} from '@/modules/elements/types/actions';

export interface SampleEntry {
  id: number;
  title: string;
  status: string;
  section: string;
  postDate: string;
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
];

const columnHelper = createColumnHelper<SampleEntry>();

export const sampleColumns = [
  columnHelper.accessor('title', {header: 'Title'}),
  columnHelper.accessor('status', {
    header: 'Status',
    cell: ({getValue}) => h(ElementStatus, {value: getValue()}),
  }),
  columnHelper.accessor('section', {header: 'Section'}),
  columnHelper.accessor('postDate', {header: 'Post Date'}),
];

/**
 * Builds a client-side TanStack table over the sample entries, with the row-id
 * and selection settings the element index components expect. Stories call
 * this inside `setup()`.
 */
export function createSampleTable(
  options: {data?: Array<SampleEntry>; pageSize?: number} = {}
) {
  return useVueTable<SampleEntry>({
    data: options.data ?? sampleEntries,
    columns: sampleColumns,
    getRowId: (row) => String(row.id),
    enableRowSelection: true,
    getCoreRowModel: getCoreRowModel(),
    getSortedRowModel: getSortedRowModel(),
    getPaginationRowModel: getPaginationRowModel(),
    initialState: {
      pagination: {pageIndex: 0, pageSize: options.pageSize ?? 50},
    },
  });
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
  cardHeaderHtml: `<span class="text-sm c-text-on-neutral-quiet">${entry.section}</span>`,
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
