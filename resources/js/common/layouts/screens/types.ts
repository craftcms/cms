import type {InertiaForm} from '@inertiajs/vue3';
import type {
  ActionItem,
  ActionItemButton,
  FormSaveOptions,
} from '@/common/types';

export type DefaultFormAction = 'saveAndContinueEditing';

/**
 * The contract every screen shell implements.
 *
 * `AppLayout` dispatches to whichever shell matches the current context
 * (`PageScreen` for a full page, `SlideoutScreen` inside a slideout), so a page
 * component renders unchanged in either one. Adding to this contract means
 * implementing it in both shells.
 */
export interface ScreenProps {
  title?: string;
  debug?: any;
  form?: InertiaForm<any> | null;
  defaultFormActions?: Array<DefaultFormAction>;
  formActions?: Array<ActionItem>;
  formAdditionalActions?: Array<ActionItem>;
  formAdditionalButtons?: Array<ActionItemButton>;
  /**
   * Controls below the secondary nav, as descriptors rather than markup.
   *
   * The nav renders these twice over — as buttons where it has the room, and
   * as items appended to its action menu once it collapses — so describing
   * them beats filling the `subnav-actions` slot with one of the two.
   */
  subnavActions?: Array<ActionItem>;
  /** Overrides the submit button's text. Craft 5: `submitButtonLabel`. */
  submitButtonLabel?: string;
  additionalSkipLinks?: Array<{label: string; url: string}>;
  /**
   * Caps the content column's width and centres it in the space it has, with
   * the footer's rule kept to the content: `true` for the default
   * (`--cp-content-max-width`, 960px), or any CSS length. Full pages only; a
   * slideout is narrow already.
   */
  contentMaxWidth?: boolean | string;
  /**
   * Fits the shell to the viewport instead of letting the document scroll,
   * so `page-main` gets a definite height to fill (e.g. a full-bleed editor).
   * Full pages only; a slideout is bounded already.
   */
  fillViewport?: boolean;
}

export interface ScreenEmits {
  (e: 'save', options?: FormSaveOptions): void;
}

/**
 * The shells' extension points, mirroring Craft 5's `_layouts/cp.twig` blocks
 * and variables (noted per slot).
 *
 * Names carry the level they belong to: `page-` for regions the current page
 * owns end to end, `content-` for regions inside the primary content area.
 * Unprefixed names sit inside `content-header` or apply to every level.
 *
 * A slideout has no room for some of these (`breadcrumbs`, `content-sidebar`,
 * `subnav-actions`, `page-footer`). `SlideoutScreen` still renders their
 * outlets, hidden, so a page written for full-page use doesn't drop teleported
 * content on the floor when it opens in a slideout.
 */
export interface ScreenSlots {
  /** Page content inside the content column. Craft 5: `block content`. */
  default?: () => any;
  /** Replaces the entire main column: breadcrumb bar, page header, and content. Craft 5: `block main`. */
  'page-main'?: () => any;
  /**
   * Notices about the page's state — a draft, a stale copy, a read-only view —
   * at the top of the content column, above the page header. Craft 5:
   * `contentNotice`.
   */
  'content-notices'?: () => any;
  /** Replaces the breadcrumb bar. Default renders the `crumbs` page prop and the `context-menu` slot. */
  breadcrumbs?: () => any;
  /** Extra controls next to the breadcrumbs, e.g. a site picker. Craft 5: `contextMenu`. */
  'context-menu'?: () => any;
  /** Replaces the page header (the title). Pass empty content to hide it. Craft 5: `block header` / `showHeader`. */
  'content-header'?: () => any;
  /** Replaces the default `<h1>` page title. Craft 5: `block pageTitle`. */
  title?: () => any;
  /**
   * Replaces the toolbar above the page header, which holds
   * `content-toolbar-meta` and `content-toolbar-actions`.
   */
  'content-toolbar'?: () => any;
  /**
   * About what the page shows, at the start of the toolbar: status badges,
   * who else is editing, View buttons, a server screen's toolbar HTML.
   * Craft 5: `#revision-indicators`, `toolbar`.
   */
  'content-toolbar-meta'?: () => any;
  /**
   * Actions on what the page shows, at the end of the toolbar, usually as an
   * action menu: editing an entry's entry type, Validate entry, and the like.
   */
  'content-toolbar-actions'?: () => any;
  /**
   * Page-level actions beside the title, e.g. New … or Upload. Craft 5:
   * `actionButton`.
   */
  'content-actions'?: () => any;
  /** Extra buttons before the form save UI, in the content footer. Craft 5: `additionalButtons`. */
  'additional-buttons'?: () => any;
  /** Replaces the save button while keeping the form action menu. Craft 5: `block submitButton`. */
  'submit-button'?: () => any;
  /** Replaces the default form error summary. Craft 5: `errorSummary`. */
  'error-summary'?: () => any;
  /** Tabs above the content. Craft 5: `tabs`. */
  'content-tabs'?: () => any;
  /** Left column beside the content. Defaults to a secondary nav built from the `subnav` page prop. Craft 5: `sidebar`. */
  'content-sidebar'?: () => any;
  /**
   * Extra controls below the default secondary nav, for anything the
   * `subnavActions` prop can't describe. Markup placed here only appears in
   * the expanded nav — the collapsed action menu is built from descriptors.
   */
  'subnav-actions'?: () => any;
  /** Bottom of the content column (pagination, meta info, …), beside the form save UI. Craft 5: `footer` (content pane). */
  'content-footer'?: () => any;
  /** Right details column beside the content. Craft 5: `details`. */
  'content-details'?: () => any;
  /** Global page footer. */
  'page-footer'?: () => any;
}
