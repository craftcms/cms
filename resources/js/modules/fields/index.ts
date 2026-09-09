import {createCopyTextPrompt} from '@craftcms/ui/factory';
import {openSlideout} from '@/common/slideouts';
import type {SlideoutSaveResult} from '@/common/slideouts/types';
import {MatrixEntry} from '@/modules/matrix/matrix-entry';

/**
 * Window listeners for the declarative actions carried by a field's "⋮" action
 * menu (see `Field::actionMenuItems()` and `BaseField::copyAttributeAction()`).
 *
 * These used to be registered per-item as inline jQuery keyed on the item's
 * DOM id. That never reached an Inertia-rendered page — the registered JS only
 * ships on a full page load — so the behavior travels with the item as a
 * `{type: 'event'}` action descriptor instead, and `runAction()` dispatches it
 * here. `runAction` merges the invoking element in as `detail.trigger`.
 */

/** Whether the Vue slideout stack is available (i.e. we're on an Inertia page). */
function canUseVueSlideout(): boolean {
  return window.Craft?.openSlideout instanceof Function;
}

// `craft:edit-field` — the "Field settings" item. Opens the field's settings
// screen in a slideout and, on save, re-announces it as the bubbling
// `field-saved` event the field layout designer listens for to refresh its
// selector (see `field-layout-designer/element.ts`).
// SAFETY: craft:edit-field is a registered CustomEvent with a {fieldId} payload.
window.addEventListener('craft:edit-field', ((ev: CustomEvent) => {
  const {fieldId, trigger} = ev.detail ?? {};

  if (!fieldId) {
    return;
  }

  const announceSaved = (detail: unknown) => {
    // Dispatched from the trigger, not the window: the designer scopes its
    // listener to the settings slideout the menu was opened from.
    (trigger instanceof HTMLElement ? trigger : window).dispatchEvent(
      new CustomEvent('field-saved', {bubbles: true, detail})
    );
  };

  // Focus the trigger so closing the slideout returns focus to it.
  if (trigger instanceof HTMLElement) {
    trigger.focus();
  }

  const url = Craft.getCpUrl('settings/fields/edit', {fieldId});

  if (canUseVueSlideout()) {
    void openSlideout(url, {
      opener: trigger instanceof HTMLElement ? trigger : null,
      onSaved: ({data, draft}: SlideoutSaveResult) => {
        // An autosaved draft isn't a finished save; don't refresh on it.
        if (!draft) {
          announceSaved(data);
        }
      },
    });

    return;
  }

  const slideout = new Craft.CpScreenSlideout('fields/edit-field', {
    params: {fieldId},
  });

  slideout.on('submit', ({response}: any) => announceSaved(response?.data));
}) as EventListener);

// `craft:copy-text-prompt` — the "Copy field handle" / "Copy attribute name"
// items. Shows the value in a read-only field with a copy button, matching what
// the legacy `Craft.ui.createCopyTextPrompt` handler did.
// SAFETY: craft:copy-text-prompt is a registered CustomEvent with a {label, value} payload.
window.addEventListener('craft:copy-text-prompt', ((ev: CustomEvent) => {
  const {label, value} = ev.detail ?? {};

  if (typeof value !== 'string') {
    return;
  }

  createCopyTextPrompt({label, value});
}) as EventListener);

/**
 * The `craft-field` the invoking menu item belongs to. `craft-action-menu`
 * keeps its content in place (Lion's dropdown config is local placement), so
 * the item is still a descendant of the field it was rendered into.
 */
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
    (el) => el.closest('craft-field') === field
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

  for (const block of ownElements(field, '.matrixblock')) {
    const entry = MatrixEntry.forContainer(block);

    if (collapse) {
      entry?.collapse();
    } else {
      entry?.expand();
    }
  }
}) as EventListener);

// `craft:copy-nested-elements` — the "Copy all …" item on Matrix and Addresses
// fields. Hands the cards to the CP clipboard, which stores them in
// localStorage for paste targets like the nested element manager.
// SAFETY: craft:copy-nested-elements is a registered CustomEvent with a {selector, elementType, fieldId} payload.
window.addEventListener('craft:copy-nested-elements', ((ev: CustomEvent) => {
  const {selector, elementType, fieldId, trigger} = ev.detail ?? {};
  const field = fieldFor(trigger);

  if (!field || typeof selector !== 'string') {
    return;
  }

  // `dataset` reads are strings; the legacy `$.data()` calls these replace
  // returned numbers. `id` stays as-is — an unsaved Matrix block is keyed by
  // its uid, which the legacy code also passed through untouched.
  const numeric = (value: string | undefined): number | null =>
    value === undefined || value === '' || Number.isNaN(Number(value))
      ? null
      : Number(value);

  const elements = ownElements(field, selector).map((el) => ({
    type: String(elementType),
    fieldId: numeric(fieldId === undefined ? undefined : String(fieldId)),
    id: el.dataset.id!,
    draftId: numeric(el.dataset.draftId),
    revisionId: numeric(el.dataset.revisionId),
    ownerId: numeric(el.dataset.ownerId),
    siteId: numeric(el.dataset.siteId),
  }));

  if (!elements.length) {
    return;
  }

  Craft.cp?.copyElements?.(elements);
}) as EventListener);
