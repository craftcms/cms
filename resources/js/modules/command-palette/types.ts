import type {BaseAction} from '@craftcms/ui';
import type {Component} from 'vue';
import type {ElementActionMenuItem} from '@/modules/elements/composables/useElementActionMenu';

type ServerItem = CraftCms.Cms.Cp.CommandPalette.CommandPaletteItem;

/**
 * A view an item swaps the palette's search view for: a `type` the palette
 * has a component registered for, plus whatever that view takes.
 */
export interface CommandPaletteView {
  type: string;
  [key: string]: unknown;
}

/**
 * One thing the palette lists: a page, an action, or a search result.
 *
 * The same shape as `Cp\CommandPalette\CommandPaletteItem` on the server,
 * plus `run`, for items defined on the client. Choosing an item shows its
 * `view`, or calls `run`, or runs its `action`, or goes to its `href` â the
 * first of those it has.
 */
export interface CommandPaletteItem {
  id: string;
  label: string;
  /** The label as HTML, for inline formatting such as code. Output as is. */
  labelHtml?: string | null;
  description?: string | null;
  /** Supporting text below the label, searched along with it. */
  hint?: string | null;
  /** The hint as HTML, for inline formatting such as code. Output as is. */
  hintHtml?: string | null;
  /** The language the hint is in, when it differs from the control panel's. */
  hintLang?: string | null;
  icon?: string | null;
  /** A palette color for the icon, such as an entry type's. */
  iconColor?: string | null;
  iconSvg?: string | null;
  thumbHtml?: string | null;
  href?: string | null;
  external?: boolean;
  action?: BaseAction | null;
  view?: CommandPaletteView | null;
  /** Called once the palette has closed. */
  run?: () => void | Promise<void>;
  /** Asks this first, inside the palette, before calling `run`. */
  confirm?: string | null;
  reload?: boolean;
  /** A page the action does away with, such as the edit page of what it deletes. */
  removedPage?: string | null;
  /** Where to go once the action succeeds, when it removed the page being viewed. */
  removedPageRedirect?: string | null;
  actions?: CraftCms.Cms.Cp.Data.ActionItem[];
  /** An element's own actions, run the way element chips run them. */
  elementActions?: ElementActionMenuItem[];
  keywords?: string | null;
  variant?: string | null;
}

export interface CommandPaletteProvider {
  handle: string;
  label: string;
  /**
   * Whether its results are listed before any other provider's, however
   * closely those match, such as results about the page being viewed â its
   * fields, say, or its actions. It should then only return close matches
   * itself: `labelScore(label, query, {loose: false})`.
   */
  displayFirst?: boolean;
  /** Results for the query. Server providers are searched by the palette itself. */
  search?: (
    query: string
  ) => CommandPaletteItem[] | Promise<CommandPaletteItem[]>;
}

/**
 * The props a registered view component receives. It emits `done` once its
 * work succeeded, which closes the palette, and `back` to return to the
 * search view.
 */
export interface CommandPaletteViewProps {
  item: CommandPaletteItem;
  view: CommandPaletteView;
  /**
   * A selector for the view header’s actions, beside its heading. Buttons of
   * the view’s own, such as “Mark all as read”, go there with
   * `<Teleport defer :to="actionsTarget">`.
   */
  actionsTarget?: string;
}

export interface CommandPaletteViewResult {
  message?: string;
  /** Called once the palette has closed, which leaves focus to it. */
  run?: () => void | Promise<void>;
  redirect?: string;
  /** Replaces the current history entry rather than adding one, for a page that's gone. */
  replace?: boolean;
  reload?: boolean;
}

export type CommandPaletteViewComponent = Component<CommandPaletteViewProps>;

let generatedIds = 0;

/** A server item in the client's shape. */
export function fromServerItem(item: ServerItem): CommandPaletteItem {
  return {
    ...item,
    id: item.id ?? `command-palette-item-${++generatedIds}`,
    action: item.action as BaseAction | null,
    view: item.view as CommandPaletteView | null,
    elementActions: item.elementActions as unknown as ElementActionMenuItem[],
  };
}
