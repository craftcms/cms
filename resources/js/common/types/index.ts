import type {VariantKey} from '@craftcms/cp';

export interface BaseOption {
  label: string;
  value: string;
}
export interface CheckboxOption extends BaseOption {
  info?: string;
}
export interface SelectOption<
  T = Record<string, any> | null | undefined,
> extends BaseOption {
  type?: 'option';
  data?: T;
}

export interface SelectOptGroup {
  type: 'optgroup';
  label: string;
  options: Array<SelectOption>;
}

export type SelectItem = SelectOption | SelectOptGroup;

export interface SuggestionGroup extends SelectOptGroup {
  type: 'optgroup';
}

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

export interface ChipIndicator {
  label?: string;
  iconColor?: string;
  icon?: string;
}

export interface ActionItemHr {
  type: 'hr';
}

export type ShortcutProps =
  | string
  | {
      alt?: boolean;
      shift?: boolean;
      key: string;
    }
  | null;

export interface ActionItemData {
  type?: 'link' | 'button';
  id?: string;
  label: string;
  variant?: VariantKey | string;
  icon?: string;
  onClick?: (event: PointerEvent) => void;
  shortcut?: ShortcutProps;
  action?: string;
  disabled?: boolean;
  params?: Record<string, any>;
  confirm?: string;
  success?: string;
  error?: string;
}

export interface EntryType {
  id: number;
  name: string;
  handle: string;
  description: string | null;
  icon?: null | {
    name: string;
    family: string;
    variant: string;
  };
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
  actions?: Array<ActionItemData>;
  indicators?: Array<ChipIndicator>;
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
  Spacious: 'spacious',
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

/**
 * These are mostly here so tailwind sees them and makes sure the classes
 * are around, but you can use it if you want.
 */
export const Width = {
  sm: 'w-sm',
  md: 'w-md',
  lg: 'w-lg',
  xl: 'w-xl',
  '2xl': 'w-2xl',
  '3xl': 'w-3xl',
  '4xl': 'w-4xl',
  '5xl': 'w-5xl',
  '6xl': 'w-6xl',
  '7xl': 'w-7xl',
} as const;

export interface SortItem {
  field: string;
  direction: 'desc' | 'asc';
}

export interface PaginationData {
  total: number;
  per_page: number;
  current_page: number;
  last_page: number;
  next_page_url: string | null;
  prev_page_url: string | null;
  from: number;
  to: number;
}

export interface UserGroup {
  id: number;
  name: string;
  handle: string;
  description?: string | null;
  uid: string;
  permissions?: Array<string>;
}
