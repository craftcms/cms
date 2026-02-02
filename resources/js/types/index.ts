export interface SelectOption<T = Record<string, any> | null | undefined> {
  type?: 'option';
  label: string;
  value: string;
  data?: T;
}

export interface SelectOptGroup {
  type: 'optgroup';
  label: string;
  options: Array<SelectOption>;
}

export type SelectItem = SelectOption | SelectOptGroup;

export interface SiteGroup {
  id: number;
  uid: string;
  rawName: string;
  name: string;
}

export interface Site {
  name: string;
  nameRaw: string;
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
