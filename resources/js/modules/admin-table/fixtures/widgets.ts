import type {PaginationData, SortItem} from '@/common/types';

export interface Widget {
  id: number;
  name: string;
  handle: string;
  type: string;
  dateCreated: string;
}

const widgetTypes = ['Gadget', 'Gizmo', 'Doohickey'];

export const widgets: Array<Widget> = Array.from({length: 23}, (_, i) => ({
  id: i + 1,
  name: `Widget ${i + 1}`,
  handle: `widget${i + 1}`,
  type: widgetTypes[i % widgetTypes.length]!,
  dateCreated: new Date(Date.UTC(2026, 0, 1 + i * 3, 12)).toISOString(),
}));

export interface WidgetQuery {
  page: number;
  perPage: number;
  sort: Array<SortItem>;
}

/**
 * Stands in for a plugin's controller: sorts and slices the widgets for a
 * query the way a paginated Laravel query would.
 */
export function queryWidgets(query: WidgetQuery): {
  data: Array<Widget>;
  pagination: PaginationData;
} {
  const sorted = [...widgets].sort((a, b) => {
    for (const {field, direction} of query.sort) {
      const order = String(a[field as keyof Widget]).localeCompare(
        String(b[field as keyof Widget]),
        undefined,
        {numeric: true}
      );
      if (order !== 0) {
        return direction === 'desc' ? -order : order;
      }
    }
    return 0;
  });

  const total = sorted.length;
  const lastPage = Math.max(1, Math.ceil(total / query.perPage));
  const page = Math.min(Math.max(1, query.page), lastPage);
  const offset = (page - 1) * query.perPage;
  const data = sorted.slice(offset, offset + query.perPage);

  return {
    data,
    pagination: {
      total,
      per_page: query.perPage,
      current_page: page,
      last_page: lastPage,
      next_page_url: null,
      prev_page_url: null,
      from: data.length ? offset + 1 : 0,
      to: offset + data.length,
    },
  };
}

/** Serializes a query the way Inertia puts it in the URL. */
export function toQueryString(query: Record<string, unknown>): string {
  const params: string[] = [];

  function add(key: string, value: unknown) {
    if (value !== null && typeof value === 'object') {
      for (const [k, v] of Object.entries(value)) {
        add(`${key}[${k}]`, v);
      }
    } else {
      params.push(`${key}=${String(value)}`);
    }
  }

  for (const [key, value] of Object.entries(query)) {
    add(key, value);
  }

  return params.join('&');
}
