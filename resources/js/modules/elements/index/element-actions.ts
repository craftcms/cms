import {t} from '@craftcms/ui';
import {
  runAction,
  type BaseAction,
  type RunActionOptions,
} from '@craftcms/ui/actions.mjs';
import type {BulkActionItem, BulkActionParams} from '../types/actions';

export function elementActionRequest(
  item: BulkActionItem,
  params: BulkActionParams
): BaseAction {
  const action = item.action;

  if (!action || (action.type !== 'http' && action.type !== 'download')) {
    throw new Error('Element action does not contain an executable request.');
  }

  return {...action, body: {...action.body, ...params}};
}

export async function runElementAction(
  item: BulkActionItem,
  params: BulkActionParams,
  options: RunActionOptions = {}
): Promise<void> {
  try {
    await runAction(elementActionRequest(item, params), options);
    window.Craft?.cp?.displayNotice?.(t('Done'));
  } catch (cause) {
    window.Craft?.cp?.displayError?.(
      cause instanceof Error ? cause.message : t('A server error occurred.')
    );

    throw cause;
  }
}
