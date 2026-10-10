import {computed, type ComputedRef} from 'vue';
import {router} from '@inertiajs/vue3';
import {t} from '@craftcms/ui/utilities/translate';
import useCraftData from '@/common/composables/useCraftData';
import type {ActionItemButton} from '@/common/types';
import {
  appendIndexQuery,
  type ElementIndexRoute,
} from '@/modules/elements/index/composables/useElementIndexVisits';

export interface CustomizeSourcesTarget {
  /** The element type whose sources are being edited. */
  elementType?: string | null;
  /** The index's page, when the element type splits its sources across more than one. */
  page?: string | null;
  /** The source the index is on, so the editor opens on it. */
  sourceKey?: string | null;
  /** The index's route, so saving can land on the source last edited. */
  route?: ElementIndexRoute | null;
  /** Canonical URL used when switching sources, when it differs from route. */
  sourceHref?: string | null;
}

/** Where an index lands once its sources are saved, as the modal reports it. */
export interface SourcesLanding {
  /** The source last edited in the modal, or null to stay put. */
  sourceKey: string | null;
  /** The nav's link to that source, when the nav has one. */
  url: string | null;
}

/**
 * The URL an index lands on once its sources are saved: the source the modal
 * picked, by the nav's own link so the nav highlights it, or named in the
 * query when the nav has none.
 */
export function sourcesLandingUrl(
  landing: SourcesLanding,
  route: ElementIndexRoute,
  sourceHref?: string | null
): string {
  if (landing.url) {
    return landing.url;
  }

  if (landing.sourceKey === null) {
    return window.location.href;
  }

  const site = new URLSearchParams(window.location.search).get('site');
  const query = {source: landing.sourceKey, site: site ?? undefined};

  return sourceHref ? appendIndexQuery(sourceHref, query) : route.url(query);
}

/**
 * The Customize Sources entry for an index's secondary nav, as a descriptor
 * the nav renders as a button or a menu item.
 *
 * An index reached through a nav item offers the editor from that item's own
 * gear. One without — a plugin's index, or a bridged Craft 5 screen — offers
 * it here instead, where Craft 5 put it.
 *
 * Saving lands on the source last edited, as the nav item's gear does, when
 * the target names the index's route. Without one, the page reloads.
 *
 * Empty for anyone the server would refuse, so the entry is only offered to
 * those it would accept.
 *
 * @since 6.0.0
 */
export function useCustomizeSources(
  target: () => CustomizeSourcesTarget
): ComputedRef<Array<ActionItemButton>> {
  const {currentUser, allowAdminChanges} = useCraftData();

  return computed(() => {
    const {elementType, page = null, sourceKey = null} = target();

    if (!elementType || !currentUser.value?.admin || !allowAdminChanges.value) {
      return [];
    }

    return [
      {
        label: t('Customize sources'),
        icon: 'gear',
        onClick: () => {
          const {route, sourceHref} = target();

          void window.Craft?.openCustomizeSourcesModal?.({
            elementType,
            page,
            sourceKey,
            onSaved: route
              ? (sourceKey: string | null, url: string | null) =>
                  new Promise<void>((resolve) =>
                    router.visit(
                      sourcesLandingUrl({sourceKey, url}, route, sourceHref),
                      {onFinish: () => resolve()}
                    )
                  )
              : undefined,
          });
        },
      },
    ];
  });
}
