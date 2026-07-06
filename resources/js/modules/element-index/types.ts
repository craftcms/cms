export interface ElementIndexSource {
  type: 'native' | 'custom' | 'heading';
  key?: string;
  label?: string;
  heading?: string;
  badgeCount?: number | null;
  nested?: Array<ElementIndexSource>;
}

export interface ElementIndexColumn {
  key: string;
  label: string;
}

export interface ElementIndexSortOption {
  label: string;
  attribute: string;
  defaultDir: 'asc' | 'desc';
}

export interface ElementIndexElement {
  id: number;
  title: string;
  url: string | null;
  status: string | null;
  attributeHtml: Record<string, string>;
}

export interface ElementIndexSite {
  id: number;
  name: string;
  handle: string;
}

export interface ElementIndexStatus {
  value: string;
  label: string;
}
