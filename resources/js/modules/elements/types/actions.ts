/**
 * A bulk element action, serialized by `ElementActions::serializeActionItems()`
 * on the server as a primitive "action item" descriptor. These are rendered
 * through the shared `craft-action-item` component (via `ActionMenu`), so the
 * request, confirm dialog, spinner, and feedback are all handled by the
 * built-in `runAction()` primitives rather than bespoke code.
 *
 * The `action.body` carries the action class + its server-baked settings; the
 * client merges in the live selection (`elementIds`) and index context
 * (`elementType`, `source`, `context`) before performing.
 */
export interface BulkActionItem {
  /** The action's class string — a stable id for singling out specific actions. */
  key: string;
  /** The action's trigger/button label. */
  label: string;
  /** Whether the action is destructive (delete-like); drives danger styling. */
  destructive?: boolean;
  /** Color variant (e.g. `'danger'`). */
  variant?: string;
  /** Disabled actions (e.g. interactive ones not yet ported to Vue). */
  disabled?: boolean;
  /** `false` if only performable on a single selected element (mirrors the legacy `bulk: false` trigger setting). Omitted when bulk-capable. */
  bulk?: boolean;
  /** Limits the action to real elements or synthetic asset-folder rows. */
  appliesTo?: 'elements' | 'folders';
  /** Row capability that every selected element must expose as truthy. */
  selectionAttribute?: keyof ElementCapabilities;
  /** The primitive action descriptor. Absent for disabled/placeholder items. */
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

export type BulkActionParams = Record<string, unknown>;

export interface ElementCapabilities {
  copyable: boolean;
  duplicatable: boolean;
  deletable: boolean;
}

export function selectionAllows(
  item: BulkActionItem,
  elements: ReadonlyArray<ElementActionSelection>
): boolean {
  const attribute = item.selectionAttribute;

  return (
    !attribute ||
    elements.every((element) => element.capabilities?.[attribute] === true)
  );
}

export interface ElementActionSelection {
  id: string | number;
  capabilities?: Partial<ElementCapabilities>;
  type?: string;
  siteId?: number | null;
  entryTypeId?: number | null;
  ownerId?: number | null;
  fieldId?: number | null;
  draftId?: number | null;
  revisionId?: number | null;
  data?: {entryTypeId?: number | null};
  cardAttributes?: {
    data?: Record<string, unknown>;
  };
}

export type RunBulkAction = (overrides?: BulkActionParams) => Promise<boolean>;

export type PerformBulkAction = (
  item: BulkActionItem,
  run: RunBulkAction
) => Promise<void>;

export interface BulkActionEventDetail {
  elementIds: ReadonlyArray<string | number>;
  elementType: string;
  trigger: HTMLElement;
}
import type {FormValues} from '@/modules/forms/types';
