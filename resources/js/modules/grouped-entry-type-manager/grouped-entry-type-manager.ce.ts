import {
  attachChipMoveActions,
  GroupedEntryTypeManager,
} from '@/modules/grouped-entry-type-manager/grouped-entry-type-manager';
import {ControllerElement} from '@/common/web-components';

/**
 * `<craft-entry-type-manager>` — boots a {@link GroupedEntryTypeManager}
 * around the server-rendered markup it wraps (the Matrix field settings'
 * grouped entry type UI), so Twig can emit the element instead of a manual
 * `new Craft.GroupedEntryTypeManager(...)` boot.
 *
 * Attributes:
 * - `namespace` — the field settings' input namespace.
 * - `default-columns-id` — id of the container the Default Table Columns
 *   select is rebuilt into (it lives outside this element, so it's resolved
 *   lazily at rebuild time).
 * - `allow-overrides` — whether entry-type chips get the per-field override
 *   editor (the gear "Settings" chip action).
 * - `disabled` — read-only settings; the element stays inert (no boot),
 *   matching the legacy `{% if not readOnly %}` guard.
 *
 * The boot gates on the `<template data-entry-type-select>` child rather than
 * the groups list: the template is the last-rendered child in the Twig, so its
 * presence also guarantees the groups (and their selects) are fully parsed.
 */
export default class CraftEntryTypeManager extends ControllerElement<GroupedEntryTypeManager> {
  protected readonly rootSelector = ':scope > template[data-entry-type-select]';

  override connectedCallback(): void {
    if (this.hasAttribute('disabled')) {
      return;
    }

    // Attached ahead of the deferred boot so the (stateless) listener is in
    // place before any child `craft-component-select` boots and wires its
    // chips — otherwise the initial chips would miss their "Move to
    // previous/next group" actions.
    attachChipMoveActions(this);

    super.connectedCallback();
  }

  protected create(root: HTMLElement): GroupedEntryTypeManager {
    if (!(root instanceof HTMLTemplateElement)) {
      throw new Error('Entry type manager root must be a template.');
    }
    return new GroupedEntryTypeManager(this, {
      namespace: this.getAttribute('namespace'),
      allowOverrides: this.hasAttribute('allow-overrides'),
      entryTypeSelectHtml: root.innerHTML,
      defaultColumnsContainer: () => {
        const id = this.getAttribute('default-columns-id');
        return id ? document.getElementById(id) : null;
      },
    });
  }
}

declare global {
  interface HTMLElementTagNameMap {
    'craft-entry-type-manager': CraftEntryTypeManager;
  }
}
