import {MatrixEntry} from './matrix-entry';
import {
  MATRIX_SELECTION_ACTION,
  matrixField,
  syncSelectionMenu,
} from '@/modules/matrix/selection-menu';

function fieldFor(trigger: unknown): HTMLElement | null {
  return trigger instanceof HTMLElement
    ? trigger.closest<HTMLElement>('craft-field')
    : null;
}

/**
 * Elements matching `selector` that belong to `field` itself rather than to a
 * field nested inside it — the job the legacy selectors did with explicit
 * direct-descendant chains, which the two render paths spell differently.
 */
function ownElements(field: HTMLElement, selector: string): HTMLElement[] {
  return [...field.querySelectorAll<HTMLElement>(selector)].filter(
    (el) => matrixField(el) === field
  );
}

// `craft:matrix-toggle-all` — the Matrix field's "Expand/Collapse all blocks"
// items. Expanding when nothing is collapsed (or vice versa) is a no-op.
// SAFETY: craft:matrix-toggle-all is a registered CustomEvent with a {collapse} payload.
window.addEventListener('craft:matrix-toggle-all', ((ev: CustomEvent) => {
  const {collapse, trigger} = ev.detail ?? {};
  const field = fieldFor(trigger);

  if (!field) {
    return;
  }

  for (const block of ownElements(field, '[data-matrix-block]')) {
    const entry = MatrixEntry.forContainer(block);

    if (collapse) {
      entry?.collapse();
    } else {
      entry?.expand();
    }
  }
}) as EventListener);

// `craft:matrix-selection-action` — the Matrix field's "Collapse/Expand selected
// blocks" and "Disable/Enable selected blocks" items, for server-rendered
// blocks. The Vue control applies them to its own blocks, which have no
// MatrixEntry controller.
// SAFETY: craft:matrix-selection-action is a registered CustomEvent with an {action} payload.
window.addEventListener(MATRIX_SELECTION_ACTION, ((ev: CustomEvent) => {
  const {action, trigger} = ev.detail ?? {};
  const field = fieldFor(trigger);

  if (!field) {
    return;
  }

  // Selecting goes through the input's own Select, whose change callback keeps
  // the menu in step.
  if (action === 'select' || action === 'deselect') {
    const [block] = ownElements(field, '[data-matrix-block]');
    const select = block
      ? MatrixEntry.forContainer(block)?.matrix.entrySelect
      : null;

    if (action === 'select') {
      select?.selectAll();
    } else {
      select?.deselectAll();
    }

    return;
  }

  let applied = false;

  for (const block of ownElements(
    field,
    '[data-matrix-block][data-selected]'
  )) {
    const entry = MatrixEntry.forContainer(block);

    if (!entry) {
      continue;
    }

    applied = true;

    switch (action) {
      case 'collapse':
        entry.collapse();
        break;
      case 'expand':
        entry.expand();
        break;
      case 'disable':
        entry.disable();
        break;
      case 'enable':
        entry.enable();
        break;
      case 'disableForSite':
        entry.disableForSite();
        break;
      case 'enableForSite':
        entry.enableForSite();
        break;
      case 'disableGlobally':
        entry.disableGlobally();
        break;
      case 'enableGlobally':
        entry.enableGlobally();
        break;
    }
  }

  // The items now read for what was just done — "Expand selected blocks", say.
  if (applied) {
    syncSelectionMenu(field);
  }
}) as EventListener);
