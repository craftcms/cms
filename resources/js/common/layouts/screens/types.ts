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
  /** Overrides the submit button's text. Craft 5: `submitButtonLabel`. */
  submitButtonLabel?: string;
  additionalSkipLinks?: Array<{label: string; url: string}>;
}

export interface ScreenEmits {
  (e: 'save', options?: FormSaveOptions): void;
}

/**
 * The shells' extension points, mirroring Craft 5's `_layouts/cp.twig` blocks
 * and variables (noted per slot).
 *
 * A slideout has no room for some of these (`breadcrumbs`, `sidebar`,
 * `subnav-actions`, `footer`). `SlideoutScreen` still renders their outlets,
 * hidden, so a page written for full-page use doesn't drop teleported content
 * on the floor when it opens in a slideout.
 */
export interface ScreenSlots {
  /** Page content inside the content column. Craft 5: `block content`. */
  default?: () => any;
  /** Replaces the entire main column: breadcrumb bar, page header, and content. Craft 5: `block main`. */
  main?: () => any;
  /** Replaces the breadcrumb bar. Default renders the `crumbs` page prop and the `context-menu` slot. */
  breadcrumbs?: () => any;
  /** Extra controls next to the breadcrumbs, e.g. a site picker. Craft 5: `contextMenu`. */
  'context-menu'?: () => any;
  /** Replaces the page header (title through action buttons). Pass empty content to hide it. Craft 5: `block header` / `showHeader`. */
  header?: () => any;
  /** Replaces the default `<h1>` page title. Craft 5: `block pageTitle`. */
  title?: () => any;
  /** Status badges next to the title. Craft 5: `#revision-indicators`. */
  'title-badge'?: () => any;
  /** Controls between the title and the action buttons. Craft 5: `toolbar`. */
  toolbar?: () => any;
  /** Replaces the whole action-buttons area, including the form save UI. Craft 5: `actionButton`. */
  actions?: () => any;
  /** Extra buttons before the form save UI. Craft 5: `additionalButtons`. */
  'additional-buttons'?: () => any;
  /** Replaces the save button while keeping the form action menu. Craft 5: `block submitButton`. */
  'submit-button'?: () => any;
  /** Replaces the default form error summary. Craft 5: `errorSummary`. */
  'error-summary'?: () => any;
  /** Status notice at the top of the content column. Craft 5: `contentNotice`. */
  'content-notice'?: () => any;
  /** Tabs above the content. Craft 5: `tabs`. */
  tabs?: () => any;
  /** Left column beside the content. Defaults to a secondary nav built from the `subnav` page prop. Craft 5: `sidebar`. */
  sidebar?: () => any;
  /** Extra controls below the default secondary nav. */
  'subnav-actions'?: () => any;
  /** Bottom of the content column (pagination, meta info, …). Craft 5: `footer` (content pane). */
  'content-footer'?: () => any;
  /** Right details column beside the content. Craft 5: `details`. */
  details?: () => any;
  /** Global page footer. */
  footer?: () => any;
}
