import type {ActionFeedback, BaseAction, VariantKey} from '@craftcms/ui';
import type {ComboboxOptionData} from '@craftcms/ui/components/combobox/combobox';
import type {Component} from 'vue';
import type {FormValues} from '@/modules/forms/types';

export type OptionData = ComboboxOptionData;

export interface BaseOption {
  label: string;
  value: string;
}
export interface CheckboxOption extends BaseOption {
  info?: string;
  checked?: boolean;
  disabled?: boolean;
}
export interface SelectOption<
  T = OptionData | null | undefined,
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

export interface BreadcrumbItem {
  href?: string | null;
  /** What the navigation and the legacy templates used to call `href`. */
  url?: string | null;
  label?: string | null;
  /** Server-rendered crumb content, e.g. an element chip. */
  html?: string | null;
  icon?: string;
  /** Extra attributes for the crumb (e.g. drag-and-drop drop-target hooks). */
  attrs?: Record<string, string>;
  /**
   * A menu on the crumb, listing what else sits at this level — the sources
   * beside this one, the sibling nav entries, and so on.
   *
   * Named for `Cp\Data\ActionItem::$items`, which is what fills it.
   */
  items?: ActionItems;
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

export interface ActionItemDisplay {
  type: 'display';
  is: Component;
}

export interface ActionItemButton {
  type?: 'button';
  label: string;
  /** A dot beside the label, for a nav entry with a badge count. */
  indicator?: boolean;
  /**
   * Extra attributes for the rendered element — drag-and-drop drop-target
   * hooks, say. Undefined values are dropped rather than rendered empty.
   */
  attrs?: Record<string, string | undefined>;
  onMousedown?: (event: Event) => void;
  /**
   * Marks this as the one currently in effect, for a list that's a choice
   * rather than a set of commands — a source switcher, say. When any item in a
   * list says so, the whole list renders with a checkmark gutter, so the
   * labels stay aligned whichever one is current.
   */
  selected?: boolean;
  variant?: VariantKey | string;
  icon?: string;
  /**
   * A rendered SVG to use in place of a named icon, for the things that bring
   * their own — a plugin's `icon.svg`. Takes precedence over `icon`.
   */
  iconSvg?: string;
  disabled?: boolean;
  onClick?: (event: Event) => void;
  shortcut?: ShortcutProps;
  action?: BaseAction;
  feedback?: ActionFeedback;
  keywords?: string;
  iconColor?: string;
  /**
   * Items that hang off this one — the nav's own children.
   *
   * A menu draws a flat list and ignores this; a nav draws it beside or below
   * the item. It lives on the descriptor either way so the two are describing
   * the same thing rather than each having a shape the other can't read.
   */
  subnav?: ActionItems;
}

export interface ActionItemLink {
  type: 'link';
  href: string;
  label: string;
  icon?: string;
  /**
   * A rendered SVG to use in place of a named icon, for the things that bring
   * their own — a plugin's `icon.svg`. Takes precedence over `icon`.
   */
  iconSvg?: string;
  /** A dot beside the label, for a nav entry with a badge count. */
  indicator?: boolean;
  /**
   * Extra attributes for the rendered element — drag-and-drop drop-target
   * hooks, say. Undefined values are dropped rather than rendered empty.
   */
  attrs?: Record<string, string | undefined>;
  onMousedown?: (event: Event) => void;
  /**
   * Leaves the page rather than making an Inertia visit. For links out of the
   * CP, and for the handful of places still handing off to the legacy stack.
   */
  external?: boolean;
  /**
   * Marks this as the one currently in effect, for a list that's a choice
   * rather than a set of commands — a source switcher, say. When any item in a
   * list says so, the whole list renders with a checkmark gutter, so the
   * labels stay aligned whichever one is current.
   */
  selected?: boolean;
  variant?: VariantKey | string;
  onClick?: (event: Event) => void;
  shortcut?: ShortcutProps;
  action?: BaseAction;
  feedback?: ActionFeedback;
  keywords?: string;
  iconColor?: string;
  /**
   * Items that hang off this one — the nav's own children.
   *
   * A menu draws a flat list and ignores this; a nav draws it beside or below
   * the item. It lives on the descriptor either way so the two are describing
   * the same thing rather than each having a shape the other can't read.
   */
  subnav?: ActionItems;
}

/**
 * A heading over a run of items — the shape a source list's headings and the
 * navigation's groups both take.
 *
 * One level deep, as a heading: its members may have children of their own,
 * but a heading never sits inside another heading. The heading labels its
 * items visually but is never itself a choice, and the items stay siblings of
 * any ungrouped ones so a menu's roving focus and search filter keep treating
 * them alike.
 */
export interface ActionItemGroup {
  type: 'group';
  heading?: string;
  items: Array<ActionItemButton | ActionItemLink>;
}

export type ActionItem =
  | ActionItemDisplay
  | ActionItemHr
  | ActionItemGroup
  | ActionItemButton
  | ActionItemLink;

export type ActionItems = Array<ActionItem>;

export interface FormSaveOptions {
  redirect?: boolean;
  data?: FormValues;
  preserveState?: boolean;
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
  actions?: Array<ActionItem>;
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
  minAuthors: number;
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
