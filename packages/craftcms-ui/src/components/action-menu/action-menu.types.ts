import type {VariantKey} from '@src/constants/variants';

/**
 * Keyboard shortcut descriptor accepted by `craft-action-item`.
 * Either a plain string (e.g. `"k"` or `"ctrl+k"`) or a structured object.
 */
export type ActionShortcut =
  | string
  | {alt?: boolean; shift?: boolean; key: string}
  | null;

/**
 * A horizontal divider between groups of items.
 */
export interface ActionMenuItemHr {
  type: 'hr';
}

/**
 * Arbitrary DOM content rendered inside the menu.
 *
 * Per the component-parity standard, the web component's "display" contract
 * takes a DOM `Node` (or a function returning one) — NOT a framework component.
 * Framework adapters (e.g. the Vue `ActionMenu.vue`) are responsible for
 * converting their component instances into a DOM node.
 */
export interface ActionMenuItemDisplay {
  type: 'display';
  node: Node | (() => Node);
}

/**
 * Common props shared by the interactive (button/link) item types. Extra
 * properties are forwarded onto the underlying `craft-action-item` element so
 * consumers can pass through things like `action`, `feedback`, `confirm`, etc.
 */
interface ActionMenuItemInteractiveBase {
  label?: string;
  icon?: string;
  /**
   * Craft color name used to tint the item's icon (e.g. `rose`, `gray`).
   * Maps to a `--c-color-*` token on `craft-action-item`.
   */
  iconColor?: string;
  variant?: VariantKey | string;
  disabled?: boolean;
  shortcut?: ActionShortcut;
  /**
   * Extra search terms (e.g. a handle) matched by the menu's `searchable`
   * filter in addition to the item's visible text. Rendered onto the generated
   * `craft-action-item` as a `data-keywords` attribute.
   */
  keywords?: string;
  onClick?: (event: Event) => void;
  [key: string]: unknown;
}

/**
 * A button item. Triggers `onClick` (and/or a forwarded `action`) when clicked.
 */
export interface ActionMenuItemButton extends ActionMenuItemInteractiveBase {
  type?: 'button';
}

/**
 * A link item. Renders an anchor pointing at `href`.
 */
export interface ActionMenuItemLink extends ActionMenuItemInteractiveBase {
  type: 'link';
  href: string;
}

/**
 * A labelled group of items, rendered as a heading followed by its members.
 *
 * Groups are one level deep: `items` may not contain further groups. The
 * members render as siblings of ungrouped items so keyboard navigation and the
 * `searchable` filter treat every item alike — the heading is presentational.
 */
export interface ActionMenuItemGroup {
  type: 'group';
  heading?: string;
  items: Array<
    | ActionMenuItemHr
    | ActionMenuItemDisplay
    | ActionMenuItemButton
    | ActionMenuItemLink
  >;
}

/**
 * The union of every item descriptor accepted by the `actions` property of
 * `craft-action-menu`.
 */
export type ActionMenuItem =
  | ActionMenuItemHr
  | ActionMenuItemDisplay
  | ActionMenuItemButton
  | ActionMenuItemLink
  | ActionMenuItemGroup;

/**
 * The value accepted by `craft-action-menu`'s `actions` property. Either a
 * static array of item descriptors, or a provider function that returns the
 * items. When a function is supplied, it is re-evaluated each time the menu
 * opens, so it may return state-dependent items.
 */
export type ActionMenuItemsProvider = () => ActionMenuItem[];

export type ActionMenuActions = ActionMenuItem[] | ActionMenuItemsProvider;
