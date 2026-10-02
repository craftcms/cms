import type {BulkActionItem} from '../types/actions';

export const COPY_ACTION = 'CraftCms\\Cms\\Element\\Actions\\Copy';
export const DUPLICATE_ACTION = 'CraftCms\\Cms\\Element\\Actions\\Duplicate';
export const DELETE_ACTION = 'CraftCms\\Cms\\Element\\Actions\\Delete';

export function isCopyAction(item: BulkActionItem | undefined): boolean {
  return (
    item?.action?.type === 'event' && item.action.name === 'craft:copy-elements'
  );
}
