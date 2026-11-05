import {t} from '@craftcms/ui/utilities/translate';
import {setElementAttributes} from './attributes';
import {initLegacyUi} from './legacy-ui';

/** An action in the shape the legacy `disclosureMenu()` plugin accepts. */
export interface ChipAction {
  label?: string;
  icon?: string | Element | (() => Element | Promise<Element>);
  destructive?: boolean;
  attributes?: Record<string, unknown>;
  onActivate?: (item: HTMLElement) => void;
  callback?: (item: HTMLElement) => void;
  [key: string]: unknown;
}

type ElementLike = Element | ArrayLike<Element>;

function toElement(element: ElementLike): Element | undefined {
  return element instanceof Element ? element : element[0];
}

/**
 * Adds actions to a chip or card's action menu, building the menu if the chip
 * has an action container but no menu yet.
 */
export function addActionsToChip(
  chip: ElementLike,
  actions: ChipAction[] | null | undefined,
  prepend = false
): void {
  const el = toElement(chip);

  if (!el || !actions?.length) {
    return;
  }

  const container = el.querySelector(
    ':scope > [slot="suffix"], :scope > .chip-content > .chip-actions, :scope > .card-titlebar > .card-actions-container > .card-actions'
  );

  let actionMenu = container?.querySelector(':scope > craft-action-menu');
  if (actionMenu) {
    void addActionsToActionMenu(actionMenu, actions, prepend);
    return;
  }

  // Old markup, or a plugin that still renders the disclosure menu shape
  const trigger = container?.querySelector(
    '.action-btn, [data-disclosure-trigger]'
  );
  trigger?.classList.remove('hidden');

  if (!trigger && container) {
    actionMenu = buildActionMenu();
    container.append(actionMenu);
    void addActionsToActionMenu(actionMenu, actions, prepend);
    return;
  }

  addActionsToDisclosureMenu(el, trigger ?? null, actions, prepend);
}

function buildActionMenu(): HTMLElement {
  const invoker = document.createElement('craft-button');
  invoker.setAttribute('type', 'button');
  invoker.setAttribute('slot', 'invoker');
  invoker.setAttribute('appearance', 'plain');
  invoker.setAttribute('variant', 'inherit');
  invoker.setAttribute('size', 'small');
  invoker.setAttribute('icon', '');
  invoker.setAttribute('aria-label', t('Actions'));

  const icon = document.createElement('craft-icon');
  icon.setAttribute('name', 'ellipsis');
  invoker.append(icon);

  const content = document.createElement('div');
  content.setAttribute('slot', 'content');

  const menu = document.createElement('craft-action-menu');
  menu.append(invoker, content);

  return menu;
}

async function buildActionItem(action: ChipAction): Promise<HTMLElement> {
  const item = document.createElement('craft-action-item') as HTMLElement & {
    disabled?: boolean;
  };

  if (action.destructive) {
    item.setAttribute('variant', 'danger');
  }

  if (action.attributes) {
    setElementAttributes(item, action.attributes);
  }

  if (typeof action.icon === 'string') {
    item.setAttribute('icon', action.icon);
  } else if (action.icon) {
    const icon =
      typeof action.icon === 'function' ? await action.icon() : action.icon;
    if (icon instanceof Element) {
      icon.setAttribute('slot', 'icon');
      item.append(icon);
    }
  }

  if (action.label) {
    item.append(document.createTextNode(action.label));
  }

  const handler = action.onActivate || action.callback;
  item.addEventListener('click', () => {
    if (!item.disabled) {
      handler?.(item);
    }
    // The menu only closes itself for items in its default slot
    item.dispatchEvent(new Event('close-overlay', {bubbles: true}));
  });

  return item;
}

async function addActionsToActionMenu(
  actionMenu: Element,
  actions: ChipAction[],
  prepend: boolean
): Promise<void> {
  let content = actionMenu.querySelector(':scope > [slot="content"]');
  if (!content) {
    content = document.createElement('div');
    content.setAttribute('slot', 'content');
    actionMenu.append(content);
  }

  const [safeItems, destructiveItems] = await Promise.all([
    Promise.all(actions.filter((a) => !a.destructive).map(buildActionItem)),
    Promise.all(actions.filter((a) => a.destructive).map(buildActionItem)),
  ]);

  const nodes: Node[] = [...safeItems];

  if (destructiveItems.length) {
    if (safeItems.length || content.children.length) {
      const hr = document.createElement('hr');
      hr.className = 'action-menu__separator';
      Object.assign(hr.style, {
        margin: '0',
        border: '0',
        borderBlockStart: '1px solid var(--c-color-neutral-border-quiet)',
      });
      nodes.push(hr);
    }
    nodes.push(...destructiveItems);
  }

  if (prepend) {
    content.prepend(...nodes);
  } else {
    content.append(...nodes);
  }
}

/** Chips with no action container at all, from very old or custom markup. */
function addActionsToDisclosureMenu(
  chip: Element,
  trigger: Element | null,
  actions: ChipAction[],
  prepend: boolean
): void {
  // The legacy menu plugin comes with the Yii2 adapter
  // oxlint-disable-next-line typescript/no-explicit-any
  const $ = (window as {jQuery?: any}).jQuery;
  if (typeof $?.fn.disclosureMenu !== 'function') {
    return;
  }

  let triggerElement = trigger;

  if (!triggerElement) {
    console.warn(
      'A chip was given actions without an action container, so a deprecated ' +
        'disclosure menu was built for it. Render the chip with a ' +
        '`<div slot="suffix">` (as `craft-chip` and ' +
        '`ElementHtml::chipHtml()` do) to get a `craft-action-menu` instead.',
      chip
    );

    const menuId = `actions-${Math.floor(Math.random() * 1000000)}`;
    const labelId = `${menuId}-label`;
    const label = document.createElement('label');
    label.id = labelId;
    label.className = 'visually-hidden';
    label.textContent = t('Actions');

    const button = document.createElement('button');
    button.className = 'btn action-btn';
    button.type = 'button';
    button.title = t('Actions');
    button.setAttribute('aria-controls', menuId);
    button.setAttribute('aria-describedby', labelId);
    button.dataset.disclosureTrigger = 'true';

    const menu = document.createElement('div');
    menu.id = menuId;
    menu.className = 'menu menu--disclosure';

    const container = document.createElement('div');
    container.className = 'chip-actions';
    container.append(label, button, menu);
    chip.append(container);
    triggerElement = button;
  }

  const $trigger = $(triggerElement);
  const disclosureMenu = $trigger.disclosureMenu().data('disclosureMenu');
  const before = prepend
    ? disclosureMenu.$container.children().first().get(0)
    : null;
  const safeActions = actions.filter((a) => !a.destructive);
  const destructiveActions = actions.filter((a) => a.destructive);

  if (safeActions.length) {
    disclosureMenu.addItems(
      safeActions,
      disclosureMenu.addGroup(null, true, before)
    );
  }

  if (destructiveActions.length) {
    disclosureMenu.addItems(
      destructiveActions,
      disclosureMenu.addGroup(null, true, before)
    );
  }

  initLegacyUi(disclosureMenu.$container);
}
