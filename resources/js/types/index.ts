export * from './layout';

export interface CheckboxOption {
  label: string;
  value: string;
  info?: string;
}
export interface SelectOption<
  T = Record<string, any> | null | undefined,
> extends CheckboxOption {
  type?: 'option';
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

export interface SectionSiteSettingsData {
  siteId: number;
  handle: string;
  name: string;
  enabled: boolean;
  enabledByDefault: boolean;
  singleHomepage: boolean;
  singleUri: string | null;
  uriFormat: string | null;
  template: string | null;
}

export interface EntryType {
  id: number;
  name: string;
  handle: string;
  description: null;
  icon: string;
  color: string | {name: string; value: string} | null;
  uiLabelFormat: string;
  hasTitleField: boolean;
  titleTranslationMethod: TranslationMethod;
  titleTranslationKeyFormat: null;
  titleFormat: null;
  allowLineBreaksInTitles: boolean;
  showSlugField: boolean;
  slugTranslationMethod: TranslationMethod;
  slugTranslationKeyFormat: null;
  showStatusField: boolean;
  uid: string;
  validateHandleUniqueness: boolean;
  group: null;
  original: null;
  idAttribute: null;
}

export interface TranslationMethod {
  name: string;
  value: string;
}

export interface SectionResource {
  id: number | null;
  name: string | null;
  handle: string | null;
  type: string;
  enableVersioning: boolean;
  maxAuthors: number | null;
  maxLevels: number | null;
  propagationMethod: string;
  defaultPlacement: string;
  previewTargets: Array<{label: string; urlFormat: string; refresh: boolean}>;
  entryTypes: Array<EntryType>;
}

export type EditableTableCellType =
  | 'checkbox'
  | 'lightswitch'
  | 'select'
  | 'color'
  | 'date'
  | 'time'
  | 'email'
  | 'url'
  | 'autosuggest'
  | 'template'
  | 'number'
  | 'singleline'
  | 'multiline'
  | 'heading'
  | 'html'
  | 'icon';

/**
 * @TODO this could probably be a more generic `spacing` constant
 */
export const TableSpacing = {
  Relaxed: 'relaxed',
  Compact: 'compact',
} as const;

export type TableSpacingValue =
  (typeof TableSpacing)[keyof typeof TableSpacing];

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
