/**
 * A Matrix block's action menu, resolved against the state the server couldn't
 * know when it built it. Pure: the control hands in that state as it renders.
 */

import {t} from '@craftcms/ui';
import type {ActionItems} from '@/common/types';
import type {BlockStatus, EntryType} from './nested-element-blocks';

/** What every block menu item dispatches on `window`. */
export const BLOCK_ACTION_EVENT = 'craft:matrix-block-action';

/** The instance-local half of a block's menu. See `Matrix::blockActions()`. */
export type BlockActionDetail = {
  action:
    | 'collapse'
    | 'expand'
    | 'disable'
    | 'enable'
    | 'disableForSite'
    | 'enableForSite'
    | 'disableGlobally'
    | 'enableGlobally'
    | 'delete'
    | 'add'
    | 'duplicate'
    | 'copy'
    | 'paste';
  uid: string;
  entryType?: string;
  trigger?: unknown;
};

/** The block a menu belongs to, and the field around it, right now. */
export type BlockMenuState = {
  uid: string;
  /** The blocks the menu's actions apply to; several for a selection. */
  targets: readonly string[];
  collapsed: boolean;
  status: BlockStatus;
  siteName: string | null | undefined;
  creationTypes: readonly EntryType[];
  canAdd: boolean;
  busy: boolean;
  /** How many copied blocks could be pasted here. */
  pasteable: number;
};

/**
 * The label an action takes when it applies to a whole selection rather than
 * one block. Craft 5 swapped these in as the menu opened.
 */
const BULK_LABEL: Record<string, (siteName: string) => string> = {
  collapse: () => t('Collapse selected blocks'),
  expand: () => t('Expand selected blocks'),
  disable: () => t('Disable selected {type}', {type: t('blocks')}),
  enable: () => t('Enable selected {type}', {type: t('blocks')}),
  disableForSite: (site) =>
    t('Disable selected {type} for {site}', {type: t('blocks'), site}),
  enableForSite: (site) =>
    t('Enable selected {type} for {site}', {type: t('blocks'), site}),
  disableGlobally: () =>
    t('Disable selected {type} globally', {type: t('blocks')}),
  enableGlobally: () =>
    t('Enable selected {type} globally', {type: t('blocks')}),
  duplicate: () => t('Duplicate selected {type}', {type: t('blocks')}),
  copy: () => t('Copy selected {type}', {type: t('blocks')}),
  delete: () => t('Delete selected {type}', {type: t('blocks')}),
};

function bulkLabel(name: string, state: BlockMenuState): string {
  return BULK_LABEL[name]!(state.siteName ?? '');
}

function blockEvent(
  uid: string,
  action: string,
  detail: Record<string, unknown> = {}
) {
  return {
    type: 'event' as const,
    name: BLOCK_ACTION_EVENT,
    detail: {action, uid, ...detail},
  };
}

/**
 * The block's menu, resolved against the state the server couldn't know: what
 * the block is doing right now, how much of the field is selected, whether
 * there's room for another block, and what's on the clipboard.
 *
 * Resolved in place rather than rebuilt so the server keeps ownership of which
 * items exist and in what order — its list already reflects the permissions.
 */
export function blockActions(
  server: ActionItems | undefined,
  state: BlockMenuState
): ActionItems {
  if (!server?.length) {
    return localActions(state);
  }

  const bulk = state.targets.length > 1;

  return server.map((item) => {
    const action = 'action' in item ? item.action : undefined;
    const name =
      action?.type === 'event' && action.name === BLOCK_ACTION_EVENT
        ? (action.detail?.action as string | undefined)
        : undefined;

    if (name === undefined) {
      return item;
    }

    return {
      ...item,
      ...(bulk && BULK_LABEL[name] ? {label: bulkLabel(name, state)} : {}),
      // Craft 5 relabeled paste with what was actually on the clipboard.
      ...(name === 'paste' && state.pasteable
        ? {
            label: t('Paste {type} above', {
              type: state.pasteable === 1 ? t('block') : t('blocks'),
            }),
          }
        : {}),
      ...actionState(name, state),
    };
  });
}

/** Collapse/Expand and status actions, resolved against the block right now. */
function stateActions(state: BlockMenuState): ActionItems {
  const {uid, targets, status, siteName} = state;
  const bulk = targets.length > 1;
  const globallyDisabled = targets.every(status.isGloballyDisabled);
  const anyGloballyDisabled = targets.some(status.isGloballyDisabled);
  const disabledForSite = targets.every(status.isDisabledForSite);
  const actions: ActionItems = [
    state.collapsed
      ? {
          label: t('Expand'),
          icon: 'up-right-and-down-left-from-center',
          action: blockEvent(uid, 'expand'),
        }
      : {
          label: t('Collapse'),
          icon: 'down-left-and-up-right-to-center',
          action: blockEvent(uid, 'collapse'),
        },
  ];

  if (!siteName) {
    const action = targets.every(status.isDisabled) ? 'enable' : 'disable';
    actions.push({
      label: bulk
        ? bulkLabel(action, state)
        : action === 'enable'
          ? t('Enable')
          : t('Disable'),
      icon: action === 'enable' ? 'circle' : 'circle-dashed',
      action: blockEvent(uid, action),
    });

    return actions;
  }

  if (anyGloballyDisabled) {
    const action = globallyDisabled ? 'enableGlobally' : 'disableGlobally';
    actions.push({
      label: bulk
        ? bulkLabel(action, state)
        : action === 'enableGlobally'
          ? t('Enable globally')
          : t('Disable globally'),
      icon: action === 'enableGlobally' ? 'circle' : 'circle-dashed',
      action: blockEvent(uid, action),
    });

    return actions;
  }

  const siteAction = disabledForSite ? 'enableForSite' : 'disableForSite';
  actions.push(
    {
      label: bulk
        ? bulkLabel(siteAction, state)
        : siteAction === 'enableForSite'
          ? t('Enable for {site}', {site: siteName})
          : t('Disable for {site}', {site: siteName}),
      icon: siteAction === 'enableForSite' ? 'circle' : 'circle-dashed',
      action: blockEvent(uid, siteAction),
    },
    {
      label: bulk ? bulkLabel('disableGlobally', state) : t('Disable globally'),
      icon: 'circle-dashed',
      action: blockEvent(uid, 'disableGlobally'),
    }
  );

  return actions;
}

/**
 * A block the browser minted has no server-built menu until the next save
 * materializes it, so compose the half that needs no server data. Duplicate,
 * Copy and Paste are all absent — each of them needs an element to point at.
 */
function localActions(state: BlockMenuState): ActionItems {
  return [
    ...stateActions(state),
    {type: 'hr'},
    {
      label: t('Delete'),
      icon: 'trash',
      variant: 'danger',
      action: blockEvent(state.uid, 'delete'),
    },
    {type: 'hr'},
    ...state.creationTypes.map((type) => ({
      label: t('Add {type} above', {type: type.label}),
      icon: 'plus',
      hidden: !state.canAdd,
      disabled: state.busy,
      action: blockEvent(state.uid, 'add', {entryType: type.value}),
    })),
  ];
}

/** Whether one of the server's items is shown, and whether it can be used. */
function actionState(
  name: string,
  state: BlockMenuState
): {hidden?: boolean; disabled?: boolean} {
  const {targets, status} = state;
  const globallyDisabled = targets.every(status.isGloballyDisabled);
  const anyGloballyDisabled = targets.some(status.isGloballyDisabled);
  const disabledForSite = targets.every(status.isDisabledForSite);
  const disabled = targets.every(status.isDisabled);

  switch (name) {
    case 'collapse':
      return {hidden: state.collapsed};
    case 'expand':
      return {hidden: !state.collapsed};
    case 'disable':
      return {hidden: disabled};
    case 'enable':
      return {hidden: !disabled};
    case 'disableForSite':
      return {hidden: anyGloballyDisabled || disabledForSite};
    case 'enableForSite':
      return {hidden: anyGloballyDisabled || !disabledForSite};
    case 'disableGlobally':
      return {hidden: globallyDisabled};
    case 'enableGlobally':
      return {hidden: !globallyDisabled};
    case 'add':
    case 'duplicate':
      return {hidden: !state.canAdd, disabled: state.busy};
    case 'paste':
      return {hidden: state.pasteable === 0, disabled: state.busy};
    default:
      return {};
  }
}
