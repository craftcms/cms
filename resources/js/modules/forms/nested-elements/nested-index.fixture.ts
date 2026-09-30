import {nextTick} from 'vue';
import {expect, vi, type Mock} from 'vite-plus/test';
import type {BulkActionItem} from '@/modules/elements/types/actions';
export {
  COPY_ACTION,
  DELETE_ACTION,
  DUPLICATE_ACTION,
} from '@/modules/elements/index/element-action-identity';
import {
  COPY_ACTION,
  DELETE_ACTION,
  DUPLICATE_ACTION,
} from '@/modules/elements/index/element-action-identity';
import type {NestedContentIndexData, NestedEntry} from './nested-entries';

export function standardElementAction(
  key: string,
  label: string,
  selectionAttribute: NonNullable<BulkActionItem['selectionAttribute']>,
  type: 'event' | 'http'
): BulkActionItem {
  return {
    key,
    label,
    selectionAttribute,
    ...(key === DELETE_ACTION ? {destructive: true, variant: 'danger'} : {}),
    action:
      type === 'event'
        ? {type, name: 'craft:copy-elements'}
        : {
            type,
            method: 'POST',
            url: '/actions/element-indexes/perform-action',
            body: {elementAction: key},
            ...(key === DELETE_ACTION
              ? {
                  confirm:
                    'Are you sure you want to delete the selected entries?',
                }
              : {}),
          },
  };
}

export const standardNestedActions: BulkActionItem[] = [
  standardElementAction(COPY_ACTION, 'Copy', 'copyable', 'event'),
  standardElementAction(DUPLICATE_ACTION, 'Duplicate', 'duplicatable', 'http'),
  standardElementAction(DELETE_ACTION, 'Delete', 'deletable', 'http'),
];

export function nestedElementAction(
  elementId: number,
  item: BulkActionItem,
  label = item.label
) {
  return {
    label,
    action: {
      type: 'event' as const,
      name: 'craft:nested-element-action',
      detail: {action: 'element-action', elementId, item},
    },
  };
}

export function nestedEntry(
  id: number,
  overrides: Partial<NestedEntry> = {}
): NestedEntry {
  const editUrl = `/edit/${id}?elementId=${id}`;

  return {
    id,
    label: `Entry ${id}`,
    title: `<a href="${editUrl}">Entry ${id}</a>`,
    siteId: 1,
    entryTypeId: 9,
    ownerId: 73,
    capabilities: {
      copyable: true,
      duplicatable: true,
      deletable: true,
    },
    ownerIsCanonical: true,
    isUnpublishedDraft: false,
    ownerIsUnpublishedDraft: false,
    primaryOwnerId: 73,
    isCanonical: true,
    inlineEditable: true,
    editUrl,
    cpEditUrl: `/entries/${id}`,
    actionMenuItems: [],
    cardAttributes: {
      data: {
        label: `Entry ${id}`,
        editable: true,
      },
    },
    cardHeaderHtml: `Entry ${id}`,
    cardActionsHtml: '',
    cardContentHtml: '',
    cardFooterHtml: '',
    cardThumbHtml: '',
    thumbAlignment: 'end',
    ...overrides,
  };
}

export function inlineTitleInput(id: number, label = `Title ${id}`) {
  return {
    title: `<input aria-label="${label}" name="inline[element-${id}][title]" value="Entry ${id}">`,
  };
}

export function nestedIndexPayload(
  entries: NestedEntry[],
  options: {
    page?: number;
    lastPage?: number;
    total?: number;
    perPage?: number;
    mode?: 'table' | 'cards';
    reorderable?: boolean;
    actions?: BulkActionItem[] | null;
  } = {}
): NestedContentIndexData {
  const page = options.page ?? 1;
  const perPage = options.perPage ?? 2;
  const lastPage = options.lastPage ?? 1;
  const total = options.total ?? entries.length;
  const from = (page - 1) * perPage + 1;

  return {
    elementType: 'Entry',
    elementDisplayName: 'Entry',
    elementPluralDisplayName: 'Entries',
    canHaveDrafts: true,
    title: 'Entries',
    page: null,
    selectedSubnavItem: null,
    showSiteMenu: false,
    showStatusMenu: true,
    siteId: 1,
    sites: [],
    crumbs: [],
    structure: null,
    drafts: false,
    trashed: false,
    exporters: [{type: 'Expanded', name: 'Expanded', formattable: true}],
    context: 'embeddedIndex',
    source: {type: 'native', key: '__IMP__', label: 'Entries'},
    sources: [],
    status: '',
    statusOptions: [],
    search: '',
    currentCondition: null,
    viewState: {mode: options.mode ?? 'table', showHeaderColumn: true},
    viewModes: [
      {mode: 'table', title: 'Table', icon: 'table'},
      {mode: 'cards', title: 'Cards', icon: 'grid'},
    ],
    tableColumns: [{label: 'Title', value: 'title'}],
    defaultTableColumns: [],
    sort: [{field: 'sortOrder', direction: 'asc'}],
    sortOptions: [{label: 'Order', value: 'sortOrder', defaultDir: 'asc'}],
    data: entries,
    actions:
      options.actions === undefined ? standardNestedActions : options.actions,
    pagination: {
      total,
      unfilteredTotal: total,
      per_page: perPage,
      current_page: page,
      last_page: lastPage,
      next_page_url: page < lastPage ? `/page/${page + 1}` : null,
      prev_page_url: page > 1 ? `/page/${page - 1}` : null,
      from,
      to: Math.min(from + entries.length - 1, total),
    },
    headHtml: '',
    bodyHtml: '',
    reorderable: options.reorderable ?? true,
  } as unknown as NestedContentIndexData;
}

export function stubElementInternals(): () => void {
  if ('attachInternals' in HTMLElement.prototype) {
    return () => {};
  }

  Object.defineProperty(HTMLElement.prototype, 'attachInternals', {
    configurable: true,
    value: () => ({setFormValue: () => {}}),
  });

  return () => {
    delete (HTMLElement.prototype as {attachInternals?: unknown})
      .attachInternals;
  };
}

export function postsTo<Body = Record<string, unknown>>(
  post: Mock,
  path: string
): Body[] {
  return post.mock.calls
    .filter(([url]) => String(url).includes(path))
    .map(([, body]) => body as Body);
}

export function button(root: HTMLElement, label: string) {
  return [...root.querySelectorAll<HTMLElement>('button, craft-button')].find(
    (candidate) => candidate.textContent?.trim() === label
  ) as HTMLElement & {disabled: boolean};
}

export function pagerButton(
  root: HTMLElement,
  label: 'Next page' | 'Previous page'
) {
  return [...root.querySelectorAll<HTMLElement & {label: string}>('craft-icon')]
    .find((icon) => icon.label === label)!
    .closest<HTMLElement & {disabled: boolean}>('craft-button')!;
}

export function pageInput(root: HTMLElement) {
  return [...root.querySelectorAll<HTMLElement>('craft-input')].find((input) =>
    input.textContent?.includes('Current page')
  ) as HTMLElement & {modelValue: string; disabled: boolean};
}

export async function waitForPage(
  root: HTMLElement,
  page: number
): Promise<void> {
  await vi.waitFor(() => {
    expect(pageInput(root)?.querySelector('input')?.value).toBe(String(page));
  });
}

export async function goToPage(
  root: HTMLElement,
  direction: 'Next page' | 'Previous page',
  page: number
): Promise<void> {
  const pager = pagerButton(root, direction);
  await vi.waitFor(() => expect(pager.disabled).toBe(false));
  pager.click();
  await waitForPage(root, page);
}

export function selectRow(root: HTMLElement, id: number, key = ' '): void {
  root
    .querySelector<HTMLElement>(`[data-nested-id="${id}"]`)!
    .dispatchEvent(new KeyboardEvent('keydown', {key, bubbles: true}));
}

export async function selectRows(
  root: HTMLElement,
  ids: number[]
): Promise<void> {
  ids.forEach((id) => selectRow(root, id));
  await nextTick();
}
