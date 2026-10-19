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

/**
 * A sidebar tab as it renders in the sidebar: its fields and other layout
 * elements span the sidebar's full width, whatever widths their layout
 * config gives them, and date and time inputs fill it, as entries' Post Date
 * does.
 */
export function forSidebar(tab: SidebarTabNode): SidebarTabNode {
  const children = tab.children as UiNodePayload[] | undefined;
  const sidebarChildren = children?.map(forSidebarElement);

  if (
    !children ||
    !sidebarChildren ||
    sidebarChildren.every((child, index) => child === children[index])
  ) {
    return tab;
  }

  return {
    ...tab,
    children: sidebarChildren as SidebarTabNode['children'],
  };
}

function forSidebarElement(node: UiNodePayload): UiNodePayload {
  let result = node;

  if (node.props?.width != null) {
    const {width: _width, ...props} = node.props;
    result = {...result, props};
  }

  if (
    node.control?.component === 'craft:date-time' &&
    !node.control.props?.fullWidth
  ) {
    result = {
      ...result,
      control: {
        ...node.control,
        props: {...node.control.props, fullWidth: true},
      },
    };
  }

  return result;
}
