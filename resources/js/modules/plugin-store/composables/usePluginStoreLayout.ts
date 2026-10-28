import {computed, type MaybeRefOrGetter, toValue} from 'vue';
import {usePage} from '@inertiajs/vue3';
import {t} from '@craftcms/ui';
import {escapeHtml} from '@craftcms/ui/utilities/escapeHtml';
import {useAppLayout} from '@/common/composables/useAppLayout';
import {
  category as categoryRoute,
  index,
  upgradeCraft,
} from '@actions/PluginStore/PluginStoreController';
import {useCoreData} from './usePluginStoreData';
import craftIconUrl from '../images/craft.svg?url';
import type {PluginStoreContext} from '../types';

type NavItem = CraftCms.Cms.Cp.Data.NavItem;

function navItem(item: Partial<NavItem>): NavItem {
  return {
    label: null,
    html: null,
    ariaLabel: null,
    href: null,
    external: false,
    icon: null,
    iconSvg: null,
    fontIcon: null,
    iconColor: null,
    status: null,
    id: null,
    variant: null,
    badgeCount: 0,
    badgeLabel: null,
    selected: false,
    disabled: false,
    current: false,
    group: false,
    subnav: false,
    items: [],
    keywords: null,
    shortcut: null,
    action: null,
    feedback: null,
    linkAttributes: {},
    errors: undefined,
    ruleset: undefined,
    ...item,
  };
}

/**
 * Draws an icon image as nav icon markup. An SVG `<image>` loads it as an
 * image, so nothing in it runs. Artwork icons are padded, so they're drawn
 * larger than the glyph size.
 */
function imageIconSvg(url: string): string {
  return `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1 1" style="block-size: 1.25rem; inline-size: 1.25rem"><image href="${escapeHtml(url)}" width="1" height="1"/></svg>`;
}

/** Whether the install can still move up to Craft Pro. */
function canUpgradeCraft(context: PluginStoreContext): boolean {
  const pro = context.editions.indexOf('pro');
  const current = context.editions.indexOf(context.edition);
  const licensed = context.licensedEdition
    ? context.editions.indexOf(context.licensedEdition)
    : -1;

  return current < pro || licensed < pro;
}

/**
 * Puts the store's own navigation beside the page: Discover, the Craft upgrade,
 * every plugin category, and links out to craftcms.com and Craft Console.
 */
export function usePluginStoreLayout(
  options: {title?: MaybeRefOrGetter<string | null | undefined>} = {}
): void {
  const page = usePage<{context: PluginStoreContext; title: string}>();
  const {categories} = useCoreData();

  const subnav = computed<NavItem[]>(() => {
    const path = new URL(page.url, window.location.origin).pathname;
    const item = (
      label: string,
      href: string,
      icon: {name?: string; url?: string | null} = {}
    ) =>
      navItem({
        label,
        href,
        icon: icon.name ?? null,
        iconSvg: icon.url ? imageIconSvg(icon.url) : null,
        selected: new URL(href, window.location.origin).pathname === path,
      });

    return [
      item(t('Discover'), index().url, {name: 'star'}),
      ...(canUpgradeCraft(page.props.context)
        ? [
            item(t('Upgrade Craft CMS'), upgradeCraft().url, {
              url: craftIconUrl,
            }),
          ]
        : []),
      navItem({
        label: t('Categories'),
        href: '#',
        group: true,
        subnav: categories.value.map((category) =>
          item(category.title, categoryRoute({id: category.id}).url, {
            url: category.iconUrl,
          })
        ),
      }),
      navItem({
        label: t('Resources'),
        href: '#',
        group: true,
        subnav: [
          navItem({
            label: 'craftcms.com',
            href: 'https://craftcms.com/',
            external: true,
            icon: 'earth-americas',
          }),
          navItem({
            label: 'Craft Console',
            href: 'https://console.craftcms.com/',
            external: true,
            icon: 'earth-americas',
          }),
        ],
      }),
    ];
  });

  useAppLayout(() => ({
    title: toValue(options.title) ?? page.props.title,
    subnav: subnav.value,
  }));
}
