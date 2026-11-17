import {
  createElementActionDispatcher,
  type ElementActionBehavior,
} from './composables/useElementActionMenu';

const dispatch = createElementActionDispatcher();

/**
 * Runs the behavior a server-rendered element action menu item names, the
 * same way the Vue editor's menu runs its own.
 */
window.addEventListener('craft:element-action', (event) => {
  const {behavior, destructive, sourceEvent} =
    (
      event as CustomEvent<{
        behavior?: ElementActionBehavior;
        destructive?: boolean;
        sourceEvent?: Event;
      }>
    ).detail ?? {};

  if (behavior) {
    dispatch(behavior, destructive, sourceEvent);
  }
});
