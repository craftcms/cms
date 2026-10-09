import {computed, type ComputedRef} from 'vue';
import {t} from '@craftcms/ui/utilities/translate';
import useCraftData from '@/common/composables/useCraftData';
import type {ActionItemButton} from '@/common/types';

export interface CustomizeSourcesTarget {
  /** The element type whose sources are being edited. */
  elementType?: string | null;
  /** The index's page, when the element type splits its sources across more than one. */
  page?: string | null;
  /** The source the index is on, so the editor opens on it. */
  sourceKey?: string | null;
}

/**
 * The Customize Sources entry for an index's secondary nav, as a descriptor
 * the nav renders as a button or a menu item.
 *
 * An index reached through a nav item offers the editor from that item's own
 * gear. One without — a plugin's index, or a bridged Craft 5 screen — offers
 * it here instead, where Craft 5 put it.
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
          void window.Craft?.openCustomizeSourcesModal?.({
            elementType,
            page,
            sourceKey,
          });
        },
      },
    ];
  });
}
