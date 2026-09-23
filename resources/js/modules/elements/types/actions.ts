/**
 * A `BulkActionsBar` menu/status item, as a primitive descriptor rather than a
 * fully-built `ActionItem` — the bar itself fills in the selection (under
 * `idsField`) and any shared `actionContext` before performing, so the
 * request, confirm dialog, spinner, and feedback all come from the built-in
 * `runAction()` primitives rather than bespoke code. Not element-specific:
 * `action.body` is whatever the target endpoint expects, element index or not.
 */
export interface BulkActionItem {
  type?: 'button';
  /** A stable id for singling out specific actions (e.g. matching one across renders). */
  key: string;
  /** The action's trigger/button label. */
  label: string;
  /** Whether the action is destructive (delete-like); drives danger styling. */
  destructive?: boolean;
  /** Color variant (e.g. `'danger'`). */
  variant?: string;
  /** Disabled actions (e.g. interactive ones not yet ported to Vue). */
  disabled?: boolean;
  /** `false` if only performable on a single selected row (mirrors the legacy `bulk: false` trigger setting). Omitted when bulk-capable. */
  bulk?: boolean;
  /** Limits the action to real elements or synthetic asset-folder rows. Meaningless outside an element index. */
  appliesTo?: 'elements' | 'folders';
  /** A colored status dot before the label — `craft-indicator`'s own `fill` values. Only meaningful in a `statuses` list. */
  fill?: string;
  /**
   * An imperative alternative to `action`, for a caller that needs to do more
   * than post-and-refresh (optimistic local removal, say). Takes priority
   * over `action` when both are given.
   */
  onClick?: (event: Event) => void;
  /** The primitive action descriptor. Ignored when `onClick` is set; absent for disabled/placeholder items. */
  action?:
    | {
        type: 'event';
        name: string;
        detail?: FormValues;
      }
    | {
        type: 'http' | 'download';
        method?: 'GET' | 'POST' | 'PATCH' | 'DELETE';
        url: string;
        body?: FormValues;
        confirm?: string;
      }
    | {
        type: 'clipboard';
        value: string;
      };
}

/** A heading over a run of `BulkActionItem`s, for `actions`/`statuses`. */
export interface BulkActionGroup {
  type: 'group';
  heading?: string;
  items: Array<BulkActionItem>;
}

export type BulkAction = BulkActionItem | BulkActionGroup | ActionItemDisplay;
import type {ActionItemDisplay} from '@/common/types';
import type {FormValues} from '@/modules/forms/types';
