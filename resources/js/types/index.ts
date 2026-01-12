export interface SelectOption<T = Record<string, any> | null | undefined> {
  label?: string;
  value?: string;
  optgroup?: string;
  data?: T
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
  uiLabel?: string;
  handle: string;
  language: string;
  languageRaw: string;
  id: number;
  enabled: boolean;
  enabledRaw: boolean | string;
  groupId: number;
  group: SiteGroup | null;
  primary: boolean;
  hasUrls: boolean;
  baseUrl: string;
  baseUrlRaw: string;
  sortOrder: number;
  uid: string;
  dateCreated: string;
  dateUpdated: string;
}
