import type {ContentIndexData} from '../composables/useContentIndexData';

export function indexData(
  id?: number,
  overrides: Partial<ContentIndexData> = {}
): ContentIndexData {
  return {
    elementType: 'Entry',
    elementDisplayName: 'Entry',
    elementPluralDisplayName: 'Entries',
    context: 'modal',
    source: {type: 'native', key: '*', label: 'All entries'},
    sources: [],
    search: '',
    status: '',
    currentCondition: null,
    viewState: {mode: 'table'},
    viewModes: [{mode: 'table', title: 'Table', icon: 'table'}],
    tableColumns: [],
    defaultTableColumns: [],
    sort: [{field: 'title', direction: 'asc'}],
    sortOptions: [{label: 'Title', value: 'title', defaultDir: 'asc'}],
    data: id ? [{id, label: `Entry ${id}`}] : [],
    pagination: {
      total: id ? 1 : 0,
      per_page: 50,
      current_page: 1,
      last_page: 1,
      next_page_url: null,
      prev_page_url: null,
      from: id ? 1 : 0,
      to: id ? 1 : 0,
    },
    ...overrides,
  } as unknown as ContentIndexData;
}
