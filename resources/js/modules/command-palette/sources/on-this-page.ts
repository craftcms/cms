import {t} from '@craftcms/ui';
import {firstFocusableWithin} from '@/common/utils/dom';
import {labelScore} from '../rank';
import type {CommandPaletteItem, CommandPaletteProvider} from '../types';

/** Anything inside these isn't part of the page being searched. */
const EXCLUDED = 'craft-dialog, dialog, .cp-skip-links, [hidden], [inert]';

interface Target {
  label: string;
  /** Supporting text below the label. */
  hint?: string | null;
  description: string;
  icon: string;
  /** A palette color for the icon, such as a nested entry's type's. */
  iconColor?: string | null;
  element: HTMLElement;
  activate: (element: HTMLElement) => void;
  /** Whether it can be found in a tab or sidebar panel that isn't open, which choosing it opens. */
  revealable?: boolean;
  /** Where it goes, for a link — chosen as a link, not by `activate`. */
  href?: string;
}

/** Tab and sidebar panels, which choosing a result inside one can open. */
const REVEALABLE =
  '[role="tabpanel"], .details-panels section[aria-labelledby], .details-panels__content';

/**
 * The closed tab and sidebar panels an element is in, outermost first, or
 * null if something else hides it — a collapsed block, say — or it isn't
 * part of the page.
 */
function closedPanels(element: HTMLElement): HTMLElement[] | null {
  if (element.closest('craft-dialog, dialog, .cp-skip-links, [inert]')) {
    return null;
  }

  const panels: HTMLElement[] = [];

  for (
    let node: HTMLElement | null = element;
    node && node !== document.body;
    node = node.parentElement
  ) {
    if (node.hidden || getComputedStyle(node).display === 'none') {
      if (!node.matches(REVEALABLE)) {
        return null;
      }

      panels.unshift(node);
    }
  }

  return panels;
}

/** Opens the closed tab and sidebar panels an element is in, and waits for them to show. */
async function reveal(element: HTMLElement): Promise<void> {
  for (const panel of closedPanels(element) ?? []) {
    if (panel.matches('[role="tabpanel"]')) {
      document
        .getElementById(panel.getAttribute('aria-labelledby') ?? '')
        ?.click();
    } else if (panel.matches('section')) {
      document
        .querySelector<HTMLElement>(
          `.details-panels__trigger[aria-controls="${CSS.escape(panel.id)}"]`
        )
        ?.click();
    }
  }

  await new Promise((resolve) =>
    requestAnimationFrame(() => requestAnimationFrame(resolve))
  );
}

function isOnPage(element: HTMLElement): boolean {
  if (element.closest(EXCLUDED)) {
    return false;
  }

  return typeof element.checkVisibility === 'function'
    ? element.checkVisibility()
    : element.offsetParent !== null;
}

function text(element: Element | null | undefined): string {
  return (element?.textContent ?? '').replace(/\s+/g, ' ').trim();
}

function prefersReducedMotion(): boolean {
  return window.matchMedia('(prefers-reduced-motion: reduce)').matches;
}

/**
 * Moves focus to an element that isn't normally focusable — a heading or a
 * landmark — so a screen reader announces it and the next Tab continues from
 * there. The temporary `tabindex` comes off again once focus moves on.
 */
function focusElement(
  element: HTMLElement,
  block: ScrollLogicalPosition = 'center'
): void {
  if (!element.hasAttribute('tabindex')) {
    element.setAttribute('tabindex', '-1');
    element.addEventListener(
      'blur',
      () => element.removeAttribute('tabindex'),
      {once: true}
    );
  }

  element.scrollIntoView({
    block,
    behavior: prefersReducedMotion() ? 'auto' : 'smooth',
  });
  element.focus({preventScroll: true});
}

/**
 * Focuses a field's control — in a `craft-field`, its input slot, ahead of
 * the field's own actions menu — or the field itself when it has none.
 */
function focusControl(field: HTMLElement): void {
  const input = field.querySelector<HTMLElement>(':scope > [slot="input"]');
  const control =
    (input && firstFocusableWithin(input)) ?? firstFocusableWithin(field);

  if (control) {
    control.scrollIntoView({
      block: 'center',
      behavior: prefersReducedMotion() ? 'auto' : 'smooth',
    });
    control.focus({preventScroll: true});
  } else {
    focusElement(field);
  }
}

/**
 * Moves focus to a landmark the way a skip link does: the landmark itself
 * takes focus, scrolled to its start, so the next Tab continues inside it.
 */
function skipTo(element: HTMLElement): void {
  focusElement(element, 'start');
}

function landmarkLabel(landmark: Element): string {
  return (
    landmark.getAttribute('aria-label') ??
    text(
      document.getElementById(landmark.getAttribute('aria-labelledby') ?? '')
    )
  );
}

function landmarks(): Target[] {
  const targets: Target[] = [];

  for (const breadcrumbs of document.querySelectorAll<HTMLElement>(
    'craft-breadcrumbs'
  )) {
    // The landmark is the `nav` inside its shadow root.
    const nav = breadcrumbs.shadowRoot?.querySelector<HTMLElement>('nav');
    const label = nav ? landmarkLabel(nav) : '';

    if (nav && label) {
      targets.push({
        label,
        description: t('Landmark'),
        icon: 'compass',
        element: breadcrumbs,
        activate: () => skipTo(nav),
      });
    }
  }

  for (const nav of document.querySelectorAll<HTMLElement>(
    'nav[aria-label], nav[aria-labelledby], [role="navigation"][aria-label]'
  )) {
    const label = landmarkLabel(nav);

    if (label) {
      targets.push({
        label,
        description: t('Landmark'),
        icon: 'compass',
        element: nav,
        activate: skipTo,
      });
    }
  }

  const main = document.querySelector<HTMLElement>('main');

  if (main) {
    targets.push({
      label: t('Main content'),
      description: t('Landmark'),
      icon: 'window-maximize',
      element: main,
      activate: skipTo,
    });
  }

  for (const tab of document.querySelectorAll<HTMLElement>(
    'craft-tab:not([disabled])'
  )) {
    const label = text(tab);

    // Only the page's own tabs: a nested entry's tabs inside a field would
    // list its “Content” once per entry.
    if (label && !tab.closest('craft-field, .field')) {
      targets.push({
        label,
        description: t('Tab'),
        icon: tab.querySelector('craft-icon')?.name ?? 'folder',
        element: tab,
        activate: (element) => {
          element.click();
          element.focus();
        },
      });
    }
  }

  for (const trigger of document.querySelectorAll<HTMLElement>(
    '.details-panels__trigger:not([data-unlisted-in-palette])'
  )) {
    const icon = trigger.querySelector('craft-icon');
    const label = text(trigger.querySelector('.sr-only')) || icon?.label || '';

    if (label) {
      targets.push({
        label,
        description: t('Sidebar panel'),
        icon: icon?.name ?? 'sidebar-flip',
        element: trigger,
        activate: openPanel,
      });
    }
  }

  return targets;
}

/**
 * Opens a details panel from its trigger, which moves focus to the panel's
 * heading — or, when it's open already, just moves focus there.
 */
function openPanel(trigger: HTMLElement): void {
  if (trigger.getAttribute('aria-expanded') !== 'true') {
    trigger.click();

    return;
  }

  const panel = document.getElementById(
    trigger.getAttribute('aria-controls') ?? ''
  );
  const heading = document.getElementById(
    panel?.getAttribute('aria-labelledby') ?? ''
  );

  if (heading) {
    focusElement(heading);
  }
}

/** A secondary nav item's own label, without the items nested in it. */
function navItemLabel(item: HTMLElement): string {
  const label = item.cloneNode(true) as HTMLElement;
  label.querySelectorAll('craft-nav-item, craft-nav-list').forEach((nested) => {
    nested.remove();
  });

  return text(label);
}

/**
 * The secondary nav's other pages, such as a user's Permissions and
 * Addresses, described by the items they're nested under. Listed whether
 * or not the nav is on screen, since choosing one just goes there.
 */
function secondaryNav(): Target[] {
  type NavItem = HTMLElement & {
    href?: string;
    icon?: string | null;
    current?: boolean;
    group?: boolean;
  };

  return [
    ...document.querySelectorAll<NavItem>('#nav-container craft-nav-item'),
  ].flatMap((item): Target[] => {
    const label = navItemLabel(item);

    if (!label || !item.href || item.current || item.group) {
      return [];
    }

    const parents: string[] = [];
    let icon = item.icon || null;

    for (
      let parent = item.parentElement?.closest<NavItem>('craft-nav-item');
      parent;
      parent = parent.parentElement?.closest<NavItem>('craft-nav-item')
    ) {
      parents.unshift(navItemLabel(parent));
      icon ??= parent.icon || null;
    }

    return [
      {
        label,
        description: [t('Secondary navigation'), ...parents].join(' →\u00A0'),
        icon: icon ?? 'compass',
        element: item,
        activate: () => {},
        href: item.href,
      },
    ];
  });
}

/**
 * The page's headings below its title, which names the page rather than a
 * part of it. The details sidebar's headings are left out: each just repeats
 * its panel's name, which is listed already.
 */
function headings(): Target[] {
  return [
    ...document.querySelectorAll<HTMLElement>('main :is(h2, h3, h4, h5, h6)'),
  ]
    .filter((heading) => !heading.closest('.details-panels'))
    .map((heading) => ({
      label: text(heading),
      description: trail(heading) || t('Heading'),
      icon: 'heading',
      element: heading,
      activate: focusElement,
      revealable: true,
    }))
    .filter(({label}) => label !== '');
}

/**
 * A `craft-field`'s label: its label slot's text, without the indicators
 * decorating it — “Required”, a translation note, and so on.
 */
export function fieldLabel(field: HTMLElement): string {
  const slot = field.querySelector(':scope > [slot="label"]');

  if (!slot) {
    return field.getAttribute('label') ?? '';
  }

  const label = slot.cloneNode(true) as HTMLElement;
  label
    .querySelectorAll('[data-craft-field-decoration]')
    .forEach((decoration) => decoration.remove());

  return text(label);
}

function fields(): Target[] {
  const targets: Target[] = [];

  for (const field of document.querySelectorAll<
    HTMLElement & {labelSrOnly?: boolean}
  >('main craft-field')) {
    // An editable table's cells are left to the table, which is listed as a
    // whole, and so is any field whose label isn't on screen to search by.
    if (
      field.closest('table.editable') ||
      field.labelSrOnly ||
      field.hasAttribute('label-sr-only')
    ) {
      continue;
    }

    // Shown when the edit form shows handles too, from the field's own chip.
    const handle = [
      ...field.querySelectorAll<HTMLElement & {value?: string}>(
        'craft-copy-attribute'
      ),
    ].find((chip) => chip.closest('craft-field') === field)?.value;

    targets.push({
      label: fieldLabel(field),
      hint: handle ?? null,
      description: trail(field) || t('Field'),
      // The field type's icon, when the field sends one.
      icon: field.dataset.icon ?? 'i-cursor',
      element: field,
      activate: focusControl,
      revealable: true,
    });
  }

  for (const label of document.querySelectorAll<HTMLLabelElement>(
    'main .field > .heading label'
  )) {
    const field = label.closest<HTMLElement>('.field');

    if (field) {
      targets.push({
        label: text(label),
        description: trail(field) || t('Field'),
        icon: 'i-cursor',
        element: field,
        activate: focusControl,
        revealable: true,
      });
    }
  }

  return targets.filter(({label}) => label !== '');
}

/** A nested entry's UI label, as its block shows it. */
function blockLabel(block: HTMLElement): string {
  return block.dataset.uiLabel || t('Untitled {type}', {type: t('entry')});
}

/** The nested entries in Matrix fields, by their UI labels. */
function blocks(): Target[] {
  return [
    ...document.querySelectorAll<HTMLElement>('main [data-matrix-block]'),
  ].map((block) => ({
    label: blockLabel(block),
    hint: block.dataset.typeName ?? null,
    description: trail(block) || t('Entry'),
    iconColor: block.dataset.color || null,
    icon:
      block.querySelector<HTMLElementTagNameMap['craft-icon']>(
        ':scope > craft-card > [slot="header"] craft-icon'
      )?.name ?? 'file',
    element: block,
    activate: focusElement,
    revealable: true,
  }));
}

/**
 * Whether a tab panel's name says something about where a result is: not
 * when its tab is a layout's only one, nor when it's a nested entry's first
 * tab, which is where that entry's fields are taken to be.
 */
function isTellingTab(panel: Element): boolean {
  const tab = document.getElementById(
    panel.getAttribute('aria-labelledby') ?? ''
  );
  const tabs = [
    ...(tab?.parentElement?.querySelectorAll(':scope > craft-tab') ?? []),
  ];

  if (tabs.length < 2) {
    return false;
  }

  return !(panel.closest('[data-matrix-block]') && tabs[0] === tab);
}

/**
 * Where an element sits on the page, outermost first: the tab or sidebar
 * panel it's in, and any fields, nested entries and their own tabs leading
 * to it, as “Content → Content Blocks → Exhibition Location”.
 */
export function trail(element: HTMLElement): string {
  const parts: string[] = [];

  for (
    let node = element.parentElement;
    node && node.tagName !== 'MAIN';
    node = node.parentElement
  ) {
    if (node.matches('[role="tabpanel"]')) {
      if (isTellingTab(node)) {
        parts.unshift(
          node.getAttribute('aria-label') ??
            text(
              document.getElementById(
                node.getAttribute('aria-labelledby') ?? ''
              )
            )
        );
      }
    } else if (node.matches('[data-matrix-block]')) {
      parts.unshift(blockLabel(node));
    } else if (node.matches('craft-field')) {
      parts.unshift(fieldLabel(node));
    } else if (node.matches('.details-panels section[aria-labelledby]')) {
      parts.unshift(
        text(document.getElementById(node.getAttribute('aria-labelledby')!))
      );
    }
  }

  // A no-break space after each arrow keeps it with the segment it leads to.
  return parts.filter((part) => part !== '').join(' →\u00A0');
}

let targetIds = 0;

/**
 * The page's own navigation and focus targets: its landmarks and tabs, its
 * secondary nav, its headings, its nested entries, and its fields' labels. Read from the page at search time, so it
 * finds whatever the page is currently showing.
 */
export const onThisPageProvider: CommandPaletteProvider = {
  handle: 'on-this-page',
  displayFirst: true,
  label: t('On This Page'),
  search(query: string): CommandPaletteItem[] {
    const seen = new Set<HTMLElement>();

    return [
      ...landmarks(),
      ...secondaryNav(),
      ...headings(),
      ...blocks(),
      ...fields(),
    ]
      .filter(({element, label, hint, revealable, href}) => {
        // The label and hint are checked first: walking up for closed panels
        // is the costly part.
        const matches = [label, hint].some(
          (text) => text && labelScore(text, query, {loose: false}) > 0
        );

        if (seen.has(element) || !matches) {
          return false;
        }

        seen.add(element);

        if (href) {
          return true;
        }

        return revealable ? closedPanels(element) !== null : isOnPage(element);
      })
      .map(
        ({
          label,
          hint,
          description,
          icon,
          iconColor,
          element,
          activate,
          revealable,
          href,
        }): CommandPaletteItem => ({
          id: `on-this-page-${++targetIds}`,
          label,
          hint,
          description,
          icon,
          iconColor,
          ...(href
            ? {href}
            : {
                run: revealable
                  ? async () => {
                      await reveal(element);
                      activate(element);
                    }
                  : () => activate(element),
              }),
        })
      );
  },
};
