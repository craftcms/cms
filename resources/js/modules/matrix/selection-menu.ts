import {t} from '@craftcms/ui/utilities/translate';

/**
 * The Matrix field menu's selection-aware items.
 *
 * The server sends the field's "⋮" menu once, with "Collapse selected blocks"
 * and "Disable selected blocks" hidden (see `Matrix::blockViewActionMenuItems()`).
 * Whether they show, what they say, and whether "Copy all blocks" copies every
 * block or just the selected ones all depend on the selection — and whether
 * "Expand/Collapse all blocks" has anything to do depends on the blocks' own
 * state — which only the browser knows. Both render paths rewrite the server's items through here:
 * MatrixControl as menu props, the legacy input on the rendered
 * `craft-action-item` elements.
 */

export const MATRIX_SELECTION_ACTION = 'craft:matrix-selection-action';

const COPY_ACTION = 'craft:copy-nested-elements';

const TOGGLE_ALL_ACTION = 'craft:matrix-toggle-all';

export type MatrixSelectionState = {
  /** How many blocks the field holds. */
  total: number;
  /** How many blocks are selected. */
  count: number;
  /** Whether every selected block is collapsed, so the item expands instead. */
  collapsed: boolean;
  /** Whether every selected block is disabled, so the item enables instead. */
  disabled: boolean;
  /** Whether any block is collapsed, so there's something to expand. */
  anyCollapsed: boolean;
  /** Whether any block is expanded, so there's something to collapse. */
  anyExpanded: boolean;
};

type EventAction = {
  type: 'event';
  name: string;
  detail?: Record<string, unknown>;
};

/** The parts of a menu item the selection rewrites. */
type SelectionMenuItem = {
  label?: string;
  hidden?: boolean;
  action?: unknown;
};

function eventAction(action: unknown): EventAction | null {
  if (typeof action !== 'object' || action === null) {
    return null;
  }

  const candidate = action as Partial<EventAction>;

  return candidate.type === 'event' && typeof candidate.name === 'string'
    ? (candidate as EventAction)
    : null;
}

/** One of the server's field menu items, as it should read for `state`. */
export function selectionMenuItem<Item extends object>(
  item: Item,
  state: MatrixSelectionState
): Item {
  const action = eventAction((item as SelectionMenuItem).action);

  if (!action) {
    return item;
  }

  // What the field calls its blocks — "entries", unless the server says.
  const type =
    typeof action.detail?.type === 'string' ? action.detail.type : t('blocks');

  // "Expand all" with nothing collapsed (or vice versa) would do nothing.
  if (action.name === TOGGLE_ALL_ACTION) {
    return {
      ...item,
      hidden: action.detail?.collapse
        ? !state.anyExpanded
        : !state.anyCollapsed,
    };
  }

  if (action.name === COPY_ACTION) {
    const selector = action.detail?.selector;

    if (!state.count || typeof selector !== 'string') {
      return item;
    }

    return {
      ...item,
      label: t('Copy selected {type}', {type}),
      // Both renderers mark a selected block with `sel`.
      action: {
        ...action,
        detail: {...action.detail, selector: `${selector}.sel`},
      },
    };
  }

  if (action.name !== MATRIX_SELECTION_ACTION) {
    return item;
  }

  const hidden = state.count === 0;

  switch (action.detail?.action) {
    // Separate items, so both show while some blocks are selected and some
    // aren't.
    case 'select':
      return {...item, hidden: state.count >= state.total};

    case 'deselect':
      return {...item, hidden: state.count === 0};

    case 'collapse':
    case 'expand': {
      const expand = state.collapsed;

      return {
        ...item,
        hidden,
        label: expand
          ? t('Expand selected blocks')
          : t('Collapse selected blocks'),
        action: {
          ...action,
          detail: {...action.detail, action: expand ? 'expand' : 'collapse'},
        },
      };
    }

    case 'disable':
    case 'enable': {
      const enable = state.disabled;

      return {
        ...item,
        hidden,
        label: enable
          ? t('Enable selected {type}', {type})
          : t('Disable selected {type}', {type}),
        action: {
          ...action,
          detail: {...action.detail, action: enable ? 'enable' : 'disable'},
        },
      };
    }

    default:
      return item;
  }
}

/** The server's own item behind each rendered one, before any rewrite. */
const serverItems = new WeakMap<Element, SelectionMenuItem>();

function serverItem(element: Element): SelectionMenuItem | null {
  const known = serverItems.get(element);

  if (known) {
    return known;
  }

  let action: unknown;

  try {
    action = JSON.parse(element.getAttribute('action') ?? 'null');
  } catch {
    return null;
  }

  const name = eventAction(action)?.name;

  if (
    name !== COPY_ACTION &&
    name !== MATRIX_SELECTION_ACTION &&
    name !== TOGGLE_ALL_ACTION
  ) {
    return null;
  }

  const item = {
    label: element.textContent?.trim() ?? '',
    hidden: element.hasAttribute('hidden'),
    action,
  };
  serverItems.set(element, item);

  return item;
}

/**
 * Brings a server-rendered field menu in line with the field's blocks. Its
 * items are `craft-action-item` elements carrying their action as a JSON
 * attribute and their label as text.
 */
export function syncSelectionMenu(field: Element): void {
  const own = (element: Element) => element.closest('craft-field') === field;
  const blocks = [...field.querySelectorAll('.matrixblock')].filter(own);
  const selected = blocks.filter((block) => block.classList.contains('sel'));
  const every = (className: string) =>
    selected.length > 0 &&
    selected.every((block) => block.classList.contains(className));
  const state: MatrixSelectionState = {
    total: blocks.length,
    count: selected.length,
    collapsed: every('collapsed'),
    disabled: every('disabled-entry'),
    anyCollapsed: blocks.some((block) => block.classList.contains('collapsed')),
    anyExpanded: blocks.some((block) => !block.classList.contains('collapsed')),
  };

  for (const element of field.querySelectorAll<HTMLElement>(
    'craft-action-item'
  )) {
    const item = own(element) ? serverItem(element) : null;

    if (!item) {
      continue;
    }

    const next = selectionMenuItem(item, state);

    element.hidden = Boolean(next.hidden);
    element.textContent = next.label ?? '';
    element.setAttribute('action', JSON.stringify(next.action));
  }
}
