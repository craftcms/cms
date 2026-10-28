import {t} from '@craftcms/ui';
import type {ActionItem} from '@/common/types';
import type {ElementActionMenuItem} from '@/modules/elements/composables/useElementActionMenu';
import type {ElementPresentation} from './types';

/**
 * The menus an element-select field offers: one per chip or card, and one for
 * the selection. Built from the elements and what the field allows; what each
 * item does comes from the caller.
 */

/** What `Craft.cp.copyElements()` wants for one element. */
type CopyDescriptor = {
  type: string;
  id: string | number;
  siteId?: number | null;
  draftId?: number | null;
  revisionId?: number | null;
};

function copyDescriptor(
  element: ElementPresentation,
  elementType: string
): CopyDescriptor {
  return {
    type: elementType,
    id: element.id,
    siteId: element.siteId === null ? null : Number(element.siteId),
    draftId: element.draftId ?? null,
    revisionId: element.revisionId ?? null,
  };
}

/**
 * Joins menu sections with a separator between each.
 *
 * Empty sections drop out, so a menu never opens or closes on a rule, and two
 * adjacent empty sections can't produce a double one.
 */
function withSeparators(sections: ActionItem[][]): ActionItem[] {
  return sections
    .filter((section) => section.length > 0)
    .flatMap((section, index) =>
      index === 0 ? section : [{type: 'hr'} as ActionItem, ...section]
    );
}

/**
 * The element's own actions.
 *
 * The server describes these per element, so an element type's extras arrive
 * without the field knowing what any of them are. Elements picked since the
 * last render have no descriptors yet — the selector reports only the common
 * few — so those fall back to what can be derived client-side until the next
 * server render fills them in.
 */
function elementActions(
  element: ElementPresentation,
  field: ChipMenuField
): ActionItem[] {
  if (element.actions?.length) {
    return field.toActionItems(element.actions);
  }

  const actions: ActionItem[] = [];

  if (element.url) {
    actions.push({
      icon: 'share',
      label: t('View in a new tab'),
      onClick: () => window.open(element.url!, '_blank', 'noopener'),
    });
  }

  if (element.canCopy) {
    actions.push({
      icon: 'custom-icons/clone-dashed',
      label: t('Copy {type}', {type: field.elementDisplayName}),
      // `Craft.cp` owns the clipboard, including its confirmation toast.
      onClick: () =>
        Craft.cp?.copyElements?.([copyDescriptor(element, field.elementType)]),
    });
  }

  return actions;
}

type ChipMenuField = {
  elementType: string;
  elementDisplayName: string;
  editable: boolean;
  toActionItems: (items: ElementActionMenuItem[]) => ActionItem[];
};

/**
 * A chip or card's menu, in three sections: what the element itself offers,
 * what the field offers for it, and detaching it.
 *
 * This is the place to regroup the menu — move an item between the arrays, or
 * merge two sections into one, and the separators follow.
 *
 * `edit` is left out in the card modes, where the pencil button beside the
 * menu already opens the editor.
 */
export function chipMenu(
  element: ElementPresentation,
  field: ChipMenuField,
  handlers: {
    edit?: {label: string; open: () => void};
    replace: () => void;
    remove: () => void;
  }
): ActionItem[] {
  // What the element itself offers — server-described, so an element type's
  // own actions (an asset's Preview file, Download, …) arrive here.
  const elementSection = elementActions(element, field);

  // What the field offers for that element. Opening the editor is the field's
  // affordance rather than the element's: the server's descriptors never
  // include an edit item, because they're written for the editor itself.
  const fieldSection: ActionItem[] = [];

  if (handlers.edit && element.canEdit === true) {
    fieldSection.push({
      icon: 'edit',
      label: handlers.edit.label,
      onClick: handlers.edit.open,
    });
  }

  // Detaching it from the field. Separated because it's the one that changes
  // the field's value.
  const removeSection: ActionItem[] = [];

  // A read-only field still offers the element's own actions and its editor —
  // you can view, edit and copy what you can't detach.
  if (field.editable) {
    if (field.elementType) {
      fieldSection.push({
        icon: 'arrows-rotate',
        label: t('Replace'),
        onClick: handlers.replace,
      });
    }

    removeSection.push({
      icon: 'remove',
      label: t('Remove'),
      variant: 'danger',
      onClick: handlers.remove,
    });
  }

  return withSeparators([elementSection, fieldSection, removeSection]);
}

/**
 * The selection's own actions, for the toolbar menu.
 *
 * Copy covers only the elements the server said may be copied — a revision or
 * one the user can't read is skipped rather than silently failing — so the item
 * is offered whenever any of the selection is copyable. Remove needs an editable
 * field: a read-only one can show and copy what it can't detach.
 */
export function selectionMenu(
  selected: ElementPresentation[],
  field: Pick<ChipMenuField, 'elementType' | 'editable'>,
  remove: () => void
): ActionItem[] {
  if (!selected.length) {
    return [];
  }

  const actions: ActionItem[] = [];
  const copyable = selected.filter((element) => element.canCopy);

  if (copyable.length) {
    actions.push({
      icon: 'custom-icons/clone-dashed',
      label: t('Copy selected'),
      onClick: () =>
        Craft.cp?.copyElements?.(
          copyable.map((element) => copyDescriptor(element, field.elementType))
        ),
    });
  }

  if (field.editable) {
    actions.push({
      icon: 'remove',
      label: t('Remove selected'),
      variant: 'danger',
      onClick: remove,
    });
  }

  // @TODO Provide a way for plugins to register bulk actions for asset fields

  return actions;
}
