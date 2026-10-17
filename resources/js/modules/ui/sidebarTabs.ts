import type {UiNodePayload} from './types';

/** A field layout tab bound for the edit form's sidebar. */
export type SidebarTabNode = UiNodePayload<{
  label: string;
  icon?: string;
  sidebar: true;
}> & {uid: string};

/**
 * Where each sidebar tab renders, keyed by tab UID. Tabs without an outlet
 * (yet) stay hidden where they are, so they move rather than remount once
 * their outlet appears.
 */
export type SidebarTabOutlets = Map<string, HTMLElement>;

export function isSidebarTab(node: UiNodePayload): node is SidebarTabNode {
  return (
    node.component === 'craft:tab' &&
    node.uid != null &&
    node.props?.sidebar === true
  );
}
