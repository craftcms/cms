import {router} from '@inertiajs/vue3';
import {computed, type ComputedRef} from 'vue';
import type {ActionItem} from '@/common/types';
import {ElementDeletionManager} from '@/modules/element-deletion-manager';

/** Identifies an element for the CP clipboard. */
interface ElementCopyRef {
  type: string;
  id: string | number;
  siteId?: number | null;
  draftId?: number | null;
  revisionId?: number | null;
}

/**
 * What an action menu item does, as described by the server. The element
 * supplies behavior rather than markup plus an inline script, so the client
 * dispatches these itself.
 */
export type ElementActionBehavior =
  | {type: 'link'; href: string; newTab?: boolean}
  | {
      type: 'submit';
      actionUrl: string;
      params?: Record<string, unknown>;
      /** Pre-encrypted by the server. */
      redirect?: string;
      confirm?: string;
    }
  | {type: 'copy'; elements: Array<ElementCopyRef>}
  | {
      type: 'delete';
      elementType: string;
      elementId: number;
      siteId: number | null;
      confirm: string;
      redirect: string;
    }
  | {
      type: 'slideout';
      url?: string;
      action?: string;
      params?: Record<string, unknown>;
      entryTypeFromField?: boolean;
    };

export interface ElementActionMenuItem {
  label: string;
  icon?: string;
  color?: string;
  destructive?: boolean;
  behavior: ElementActionBehavior;
}

interface Options {
  /**
   * Resolves a live value the behavior depends on — currently the entry type,
   * which the sidebar can change without saving.
   */
  currentEntryTypeId?: () => unknown;
}

/**
 * Turns the element's action descriptors into menu items the CP action menu can
 * render, dispatching each behavior directly rather than through registered
 * jQuery handlers.
 */
export function useElementActionMenu(
  items: () => Array<ElementActionMenuItem>,
  {currentEntryTypeId}: Options = {}
): ComputedRef<Array<ActionItem>> {
  function dispatch(behavior: ElementActionBehavior): void {
    switch (behavior.type) {
      case 'link':
        window.open(
          behavior.href,
          behavior.newTab ? '_blank' : '_self',
          behavior.newTab ? 'noopener' : undefined
        );

        return;

      case 'submit': {
        if (behavior.confirm && !window.confirm(behavior.confirm)) {
          return;
        }

        router.post(behavior.actionUrl, {
          ...behavior.params,
          ...(behavior.redirect ? {redirect: behavior.redirect} : {}),
        });

        return;
      }

      case 'copy':
        // `Craft.cp` owns the clipboard, including its confirmation toast.
        Craft.cp?.copyElements?.(behavior.elements);

        return;

      case 'delete':
        // Routed through the deletion manager so blocking relations and
        // references can be reassigned before the element goes.
        new ElementDeletionManager(behavior.elementType, [behavior.elementId], {
          siteId: behavior.siteId,
          confirmationMessage: behavior.confirm,
          onSuccess: () => router.visit(behavior.redirect),
        });

        return;

      case 'slideout': {
        const entryTypeId = currentEntryTypeId?.();
        const url =
          behavior.entryTypeFromField && entryTypeId
            ? Craft.getCpUrl(`settings/entry-types/${entryTypeId}`)
            : behavior.url;

        if (url) {
          new Craft.CpScreenSlideout(url);

          return;
        }

        if (behavior.action) {
          new Craft.CpScreenSlideout(behavior.action, {
            params: behavior.params,
          });
        }
      }
    }
  }

  return computed(() =>
    items().map(
      (item): ActionItem => ({
        label: item.label,
        icon: item.icon,
        iconColor: item.color,
        destructive: item.destructive,
        onClick: () => dispatch(item.behavior),
      })
    )
  );
}
