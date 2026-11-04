import {t} from '@craftcms/ui';
import {toRaw} from 'vue';
import {setBlockCollapsed} from '@/modules/matrix/collapsed-blocks';
import {NESTED_ELEMENT_UID_PREFIX, type NestedElementValue} from '../types';
import type {UpdateBlocksValue} from './nested-element-blocks';

type BlockStatusOptions = {
  value: () => NestedElementValue;
  siteId: () => number | null | undefined;
  /** Set when entries propagate to several sites, so status is per site too. */
  siteName: () => string | null | undefined;
  update: UpdateBlocksValue;
};

/**
 * Which blocks are enabled — globally and for this site — and switching them.
 * The predicates make up the `BlockStatus` the menus read.
 */
export function useBlockStatus(options: BlockStatusOptions) {
  function isDisabled(uid: string): boolean {
    return isGloballyDisabled(uid) || isDisabledForSite(uid);
  }

  function isGloballyDisabled(uid: string): boolean {
    return options.value().entries[uid]?.enabled === false;
  }

  function isDisabledForSite(uid: string): boolean {
    return options.value().entries[uid]?.enabledForSite === false;
  }

  function disabledLabel(uid: string): string {
    const siteName = options.siteName();

    if (isGloballyDisabled(uid) && siteName) {
      return t('Disabled globally');
    }

    if (isDisabledForSite(uid) && siteName) {
      return t('Disabled for {site}', {site: siteName});
    }

    return t('Disabled');
  }

  /**
   * Disabling folds a block away; enabling brings it back, which is what Craft
   * 5's enable did too.
   */
  function setStatus(
    uids: readonly string[],
    scope: 'global' | 'site',
    enabled: boolean
  ): void {
    const next = structuredClone(toRaw(options.value()));

    for (const uid of uids) {
      if (next.entries[uid]) {
        if (scope === 'site' && next.entries[uid].enabled === false) {
          continue;
        }

        next.entries[uid][scope === 'global' ? 'enabled' : 'enabledForSite'] =
          enabled;

        const effective =
          next.entries[uid].enabled !== false &&
          next.entries[uid].enabledForSite !== false;

        // Written straight onto `next` rather than through the collapse
        // composable, whose own emit this one would clobber.
        if (uid.startsWith(NESTED_ELEMENT_UID_PREFIX)) {
          next.entries[uid].collapsed = !effective;
        }

        setBlockCollapsed(uid, !effective, options.siteId());
      }
    }

    options.update(next);
  }

  return {
    isDisabled,
    isGloballyDisabled,
    isDisabledForSite,
    disabledLabel,
    setStatus,
  };
}
