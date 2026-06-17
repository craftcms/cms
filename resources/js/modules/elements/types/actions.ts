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
  /** The primitive action descriptor. Absent for disabled/placeholder items. */
  action?: {
    type: 'http' | 'download' | 'clipboard' | 'event';
    method?: 'GET' | 'POST' | 'PATCH' | 'DELETE';
    url?: string;
    body?: Record<string, unknown>;
    confirm?: string;
  };
}
