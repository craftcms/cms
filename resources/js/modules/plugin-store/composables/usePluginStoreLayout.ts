import {computed, type MaybeRefOrGetter, toValue} from 'vue';
import {usePage} from '@inertiajs/vue3';
import {t} from '@craftcms/ui';
import {useAppLayout} from '@/common/composables/useAppLayout';
import {
  category as categoryRoute,
  index,
  legacy,
} from '@actions/PluginStore/PluginStoreController';
import {useCoreData} from './usePluginStoreData';
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
 * Puts the store's own navigation beside the page: Discover, the Craft upgrade
 * and every plugin category.
 */
export function usePluginStoreLayout(
  options: {title?: MaybeRefOrGetter<string | null | undefined>} = {}
): void {
  const page = usePage<{context: PluginStoreContext; title: string}>();
  const {categories} = useCoreData();

  const subnav = computed<NavItem[]>(() => {
    const path = new URL(page.url, window.location.origin).pathname;
    const item = (label: string, href: string, icon: string | null = null) => {
      return navItem({
        label,
        href,
        icon,
        selected: new URL(href, window.location.origin).pathname === path,
      });
    };

    return [
      item(t('Discover'), index().url, 'star'),
      ...(canUpgradeCraft(page.props.context)
        ? [
            item(
              t('Upgrade Craft CMS'),
              legacy({path: 'upgrade-craft'}).url,
              'arrow-up'
            ),
          ]
        : []),
      navItem({
        label: t('Categories'),
        href: '#',
        group: true,
        subnav: categories.value.map((category) =>
          item(category.title, categoryRoute({id: category.id}).url)
        ),
      }),
    ];
  });

  useAppLayout(() => ({
    title: toValue(options.title) ?? page.props.title,
    subnav: subnav.value,
  }));
}
