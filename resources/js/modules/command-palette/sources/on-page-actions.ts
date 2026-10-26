import {t, type CraftActionItem} from '@craftcms/ui';
import type {BreadcrumbItem} from '@/common/types';
import type {ActionItem} from '@/common/types';
import {useGlobalSidebar} from '@/common/composables/useGlobalSidebar';
import {labelScore, normalize} from '../rank';
import {getNotifications} from '../../notifications/store';
import {fieldLabel, trail} from './on-this-page';
import type {CommandPaletteItem, CommandPaletteProvider} from '../types';

/**
 * Marks a menu whose items the palette offers as the current page's actions.
 * Put it on an element containing a `craft-action-menu`.
 */
export const PAGE_ACTIONS_ATTRIBUTE = 'data-command-palette-actions';

function globalNavigationAction(): CommandPaletteItem {
  const {sidebar, toggle} = useGlobalSidebar();
  const expanded = sidebar.visibility === 'visible';

  return {
    id: 'toggle-global-navigation',
    label: expanded
      ? t('Collapse the global navigation')
      : t('Expand the global navigation'),
    description: t('This page'),
    icon: expanded ? 'arrow-left-to-line' : 'arrow-right-from-line',
    run: toggle,
  };
}

/**
 * The current user's notifications, as the global header's notification
 * center shows them. They come with the page, so the item is there from the
 * start, even when there are none.
 */
function notificationsAction(): CommandPaletteItem {
  const unread = getNotifications().filter(
    (notification) => notification.unread
  );

  return {
    id: 'notifications',
    label: t('Notifications'),
    description: t('My Account'),
    hint: unread.length ? t('{count} unread', {count: unread.length}) : null,
    icon: 'bell',
    keywords: 'alerts inbox mentions reviews',
    view: {type: 'notifications'},
  };
}

function text(element: Element | null | undefined): string {
  return (element?.textContent ?? '').replace(/\s+/g, ' ').trim();
}

/** The details sidebar beside the content, when the page has one. */
function sidebarActions(): CommandPaletteItem[] {
  const rail = document.querySelector<HTMLElement>('.details-panels__rail');
  const triggers = rail
    ? [...rail.querySelectorAll<HTMLElement>('.details-panels__trigger')]
    : [];

  if (!rail || !triggers.length) {
    return [];
  }

  const open = triggers.find(
    (trigger) => trigger.getAttribute('aria-expanded') === 'true'
  );

  return [
    {
      id: 'toggle-sidebar',
      label: open ? t('Collapse the sidebar') : t('Expand the sidebar'),
      description: t('This page'),
      icon: 'sidebar-flip',
      run: () => {
        const trigger = open ?? triggers[0];
        trigger?.click();
        trigger?.focus();
      },
    },
  ];
}

function siteActions(crumbs: BreadcrumbItem[]): CommandPaletteItem[] {
  const siteCrumb = crumbs.find((crumb) => crumb.id === 'site-crumb');

  return (siteCrumb?.items ?? []).flatMap((site: ActionItem) => {
    if (
      site.type === 'hr' ||
      site.type === 'display' ||
      site.type === 'group' ||
      site.selected
    ) {
      return [];
    }

    const href = 'href' in site ? site.href : undefined;

    return href
      ? [
          {
            id: `switch-site-${href}`,
            label: t('Switch to {site}', {site: site.label}),
            description: t('This page'),
            icon: siteCrumb?.icon ?? null,
            href,
          },
        ]
      : [];
  });
}

/**
 * The other drafts and revisions of the element being edited, as its
 * revision crumb lists them — “Current”, each draft, recent revisions, and
 * “View all revisions” — leaving out the one being viewed.
 */
function revisionActions(crumbs: BreadcrumbItem[]): CommandPaletteItem[] {
  const revisionCrumb = crumbs.find((crumb) => crumb.id === 'revision-crumb');

  /**
   * Drafts and revisions are grouped under their headings; above them comes
   * “Current”, and after them “View all revisions”, which reads fine as is.
   */
  const label = (
    item: ActionItem & {label?: string},
    heading: string | null,
    index: number
  ): string => {
    if (heading !== null) {
      return t('Go to {name}', {name: item.label});
    }

    return index === 0 ? t('Go to the current revision') : (item.label ?? '');
  };

  const links = (
    items: ActionItem[],
    heading: string | null
  ): CommandPaletteItem[] =>
    items.flatMap((item, index): CommandPaletteItem[] => {
      if (item.type === 'group') {
        return links(item.items ?? [], item.heading ?? null);
      }

      if (item.type === 'hr' || item.type === 'display' || item.selected) {
        return [];
      }

      const href = 'href' in item ? item.href : undefined;

      return href && item.label
        ? [
            {
              id: `switch-revision-${href}`,
              label: label(item, heading, index),
              // When it was saved, and by whom.
              hint:
                'description' in item && typeof item.description === 'string'
                  ? item.description
                  : null,
              description: heading ?? t('This page'),
              icon: 'code-branch',
              href,
            },
          ]
        : [];
    });

  return links(revisionCrumb?.items ?? [], null);
}

/**
 * Expanding or collapsing every block in each Matrix field, as the field's
 * own menu does.
 */
function matrixActions(): CommandPaletteItem[] {
  return [
    ...document.querySelectorAll<HTMLElement>('main [data-matrix-field]'),
  ].flatMap((host, index): CommandPaletteItem[] => {
    const field = host.closest<HTMLElement>('craft-field');
    const label = field ? fieldLabel(field) : '';

    if (!field || !label) {
      return [];
    }

    const description = trail(field) || t('This page');
    const toggle = (collapse: boolean) => () => {
      window.dispatchEvent(
        new CustomEvent('craft:matrix-toggle-all', {
          detail: {collapse, trigger: host},
        })
      );
    };

    return [
      {
        id: `matrix-expand-all-${index}`,
        label: t('Expand all blocks in {field}', {field: label}),
        description,
        icon: 'expand',
        run: toggle(false),
      },
      {
        id: `matrix-collapse-all-${index}`,
        label: t('Collapse all blocks in {field}', {field: label}),
        description,
        icon: 'collapse',
        run: toggle(true),
      },
    ];
  });
}

/**
 * The items of the page's own action menus — View, Validate, Copy URL, and
 * so on. Choosing one clicks it, so it behaves exactly as it does in the
 * menu, confirmation prompts included.
 */
function pageMenuActions(): CommandPaletteItem[] {
  const items = document.querySelectorAll<CraftActionItem>(
    `[${PAGE_ACTIONS_ATTRIBUTE}] craft-action-item`
  );

  return [...items].flatMap((item, index) => {
    const label = text(item);

    if (!label || item.hidden || item.disabled) {
      return [];
    }

    return [
      {
        id: `page-action-${index}`,
        label,
        description: t('This page'),
        icon: item.icon ?? null,
        variant: item.variant ?? null,
        run: () => (item.closest<HTMLElement>('a') ?? item).click(),
      },
    ];
  });
}

/**
 * Actions for the page being viewed: the user's notifications, the chrome's
 * own toggles, its Matrix fields' blocks, the element's other drafts and
 * revisions, the site switcher's sites, and the page's action menus.
 */
export function onPageActions(crumbs: BreadcrumbItem[]): CommandPaletteItem[] {
  return [
    ...pageMenuActions(),
    notificationsAction(),
    globalNavigationAction(),
    {
      id: 'keyboard-shortcuts',
      label: t('Keyboard shortcuts'),
      description: t('Help'),
      icon: 'keyboard',
      view: {type: 'shortcuts'},
    },
    {
      id: 'search-docs',
      label: t('Search Craft CMS documentation'),
      description: t('Help'),
      icon: 'book',
      keywords: 'docs help knowledge base glossary',
      view: {type: 'docs'},
    },
    ...sidebarActions(),
    ...matrixActions(),
    ...revisionActions(crumbs),
    ...siteActions(crumbs),
  ];
}

/**
 * Searches the current page's actions, which the server's “Actions” provider
 * can't see, so they turn up in results as well as in the initial list.
 */
export function createPageActionsProvider(
  items: () => CommandPaletteItem[]
): CommandPaletteProvider {
  return {
    handle: 'page-actions',
    displayFirst: true,
    label: t('Actions'),
    search(term: string): CommandPaletteItem[] {
      const normalizedTerm = normalize(term);

      return items().filter(
        (item) =>
          labelScore(item.label, term, {loose: false}) > 0 ||
          [item.description, item.hint, item.keywords].some(
            (text) => text && normalize(text).includes(normalizedTerm)
          )
      );
    },
  };
}
