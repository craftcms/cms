export interface SelectOption {
  label?: string;
  value?: string;
  optgroup?: string;
  data?: Record<string, any> | null;
}

export interface Suggestion {
  name: string;
  hint: string;
}

export interface SuggestionGroup {
  label: string;
  data: Array<Suggestion>;
}

export interface SiteGroup {
  id: number;
  uid: string;
  rawName: string;
  name: string;
}

export interface Site {
  name: string;
  handle: string;
  language: string;
  id: number;
  enabled: boolean;
  groupId: number;
  group: SiteGroup | null;
  primary: boolean;
  hasUrls: boolean;
  baseUrl: string;
  sortOrder: number;
  uid: string;
  dateCreated: string;
  dateUpdated: string;
}
