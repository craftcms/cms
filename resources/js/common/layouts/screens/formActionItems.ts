import {t} from '@craftcms/ui/utilities/translate';
import type {ActionItem, FormSaveOptions} from '@/common/types';
import type {DefaultFormAction} from './types';

/** What a screen's Save menu offers when the page doesn't say otherwise. */
export const DEFAULT_FORM_ACTIONS: Array<DefaultFormAction> = [
  'saveAndContinueEditing',
];

/**
 * The items in the Save button's menu: the built-in actions first, then the
 * page's own. Both shells use this, so a page's save controls are the same on
 * a full page and in a slideout.
 */
export function formActionItems(
  defaults: Array<DefaultFormAction>,
  formActions: Array<ActionItem> | undefined,
  save: (options?: FormSaveOptions) => void
): Array<ActionItem> {
  return [
    ...defaults.map((action) => defaultFormActionItem(action, save)),
    ...(formActions ?? []),
  ];
}

function defaultFormActionItem(
  action: DefaultFormAction,
  save: (options?: FormSaveOptions) => void
): ActionItem {
  if (action === 'saveAndContinueEditing') {
    return {
      label: t('Save and continue editing'),
      onClick: () => save({redirect: false}),
      shortcut: 'S',
    };
  }

  throw new Error(`Unknown default form action: ${action}`);
}
