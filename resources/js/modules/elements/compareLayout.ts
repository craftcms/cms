import {canonical, valueAt} from '@/modules/ui/runtime';
import type {
  UiNodePayload,
  UiPayload,
  UiProperties,
  UiValue,
  UiValues,
} from '@/modules/ui/types';
import {readControlValue, type UiValueSnapshot} from '@/modules/ui/valueAccess';

export type CompareSide = 'from' | 'to';

/** A field on the Compare screen, as each side has it. */
export interface CompareRow {
  fromUid: string | null;
  toUid: string | null;
  /** `null` when it can’t be told, such as for Matrix fields shown as cards. */
  changed: boolean | null;
}

/** A tab on the Compare screen, with the rows of fields it shows. */
export interface CompareTab {
  key: string;
  /** `null` for fields that aren't in a tab. */
  label: string | null;
  icon?: string;
  /** Indexes of the comparison's rows, in order. */
  rows: number[];
  /** Whether each version has the tab. */
  sides: Record<CompareSide, boolean>;
  /** Whether it's a sidebar tab, which the screen shows as an ordinary one. */
  sidebar: boolean;
}

interface FormTab {
  uid: string | null;
  label: string | null;
  icon?: string;
  sidebar: boolean;
  fields: Set<string>;
}

/** The layout element a field's node is for, which identifies it on either side. */
export const layoutUid = (node: UiNodePayload): string =>
  typeof node.props.layoutUid === 'string'
    ? node.props.layoutUid
    : String(node.uid);

/** The field nodes in a form, in order, out of the tabs and groups they're in. */
export function fieldNodes(nodes: UiNodePayload[]): UiNodePayload[] {
  return nodes.flatMap((node) =>
    node.control ? [node] : fieldNodes(node.children ?? [])
  );
}

const fieldUids = (nodes: UiNodePayload[]): string[] =>
  fieldNodes(nodes).map(layoutUid);

/** A form's tabs, with the fields outside any tab as one more, unlabeled. */
function formTabs(ui: UiPayload): FormTab[] {
  const untabbed: FormTab = {
    uid: null,
    label: null,
    sidebar: false,
    fields: new Set(),
  };
  const tabs: FormTab[] = [];

  for (const node of ui.nodes) {
    if (node.component === 'craft:tab') {
      tabs.push({
        uid: node.uid ?? null,
        label: typeof node.props.label === 'string' ? node.props.label : null,
        icon: typeof node.props.icon === 'string' ? node.props.icon : undefined,
        sidebar: node.props.sidebar === true,
        fields: new Set(fieldUids(node.children ?? [])),
      });
    } else {
      fieldUids([node]).forEach((uid) => untabbed.fields.add(uid));
    }
  }

  return untabbed.fields.size ? [untabbed, ...tabs] : tabs;
}

/**
 * Groups the rows of a comparison into the newer version's tabs, with any of
 * the older version's that it doesn't have after the tab before them in the
 * older version, noting which versions have each. A tab is the same on both
 * sides when it's the same layout tab, or failing that, has the same label.
 * Tabs without any of the comparison's fields are left out.
 */
export function compareTabs(
  from: UiPayload,
  to: UiPayload,
  rows: CompareRow[]
): CompareTab[] {
  const tabs = formTabs(to);
  const toTabs = new Set(tabs);
  // Where the last of the older version's tabs went, so one only it has goes
  // after it, in the older version's order.
  let previous: FormTab | null = null;
  const fromTabs = new Map(
    formTabs(from).map((fromTab) => {
      const match =
        tabs.find((tab) => fromTab.uid !== null && tab.uid === fromTab.uid) ??
        tabs.find((tab) => tab.label === fromTab.label);

      if (!match) {
        tabs.splice(previous ? tabs.indexOf(previous) + 1 : 0, 0, fromTab);
      }

      previous = match ?? fromTab;

      return [fromTab, previous] as const;
    })
  );
  const rowsByTab = new Map<FormTab, number[]>();

  rows.forEach((row, index) => {
    const tab =
      tabs.find((tab) => row.toUid !== null && tab.fields.has(row.toUid)) ??
      [...fromTabs].find(
        ([fromTab]) => row.fromUid !== null && fromTab.fields.has(row.fromUid)
      )?.[1];

    if (tab) {
      rowsByTab.set(tab, [...(rowsByTab.get(tab) ?? []), index]);
    }
  });

  return tabs.flatMap((tab, index) => {
    const tabRows = rowsByTab.get(tab);

    return tabRows
      ? [
          {
            key: tab.uid ?? `tab-${index}`,
            label: tab.label,
            icon: tab.icon,
            rows: tabRows,
            sides: {
              from: [...fromTabs.values()].includes(tab),
              to: toTabs.has(tab),
            },
            sidebar: tab.sidebar,
          },
        ]
      : [];
  });
}

/** The keys of the tabs that hold a field the newer version has errors in. */
export function tabsWithErrors(
  tabs: CompareTab[],
  rows: CompareRow[],
  erroredUids: string[]
): Set<string> {
  const errored = new Set(erroredUids);

  return new Set(
    tabs
      .filter((tab) =>
        tab.rows.some((index) => {
          const uid = rows[index]?.toUid;

          return uid != null && errored.has(uid);
        })
      )
      .map((tab) => tab.key)
  );
}

/** The keys of the tabs that hold a changed field, on either side. */
export function tabsWithChanges(
  tabs: CompareTab[],
  rows: CompareRow[]
): Set<string> {
  return new Set(
    tabs
      .filter((tab) => tab.rows.some((index) => rows[index]?.changed))
      .map((tab) => tab.key)
  );
}

/**
 * The editable side's live form, with the fields the server sent for any tab
 * the editor hasn't loaded yet, which are taken to still be there.
 */
export function withServerTabFields(
  live: UiPayload,
  server: UiPayload
): UiPayload {
  const serverTabs = new Map(
    server.nodes
      .filter((node) => node.component === 'craft:tab')
      .map((node) => [node.uid, node])
  );

  return {
    ...live,
    nodes: live.nodes.map((node) =>
      node.component === 'craft:tab' &&
      node.props.deferred &&
      !node.children?.length
        ? {...node, children: serverTabs.get(node.uid)?.children ?? []}
        : node
    ),
  };
}

/**
 * The comparison's rows for the newer version's form as it stands. A field
 * the form no longer shows, as a condition hides it, leaves its row with only
 * the older version's field, if any, and one it newly shows takes the older
 * version's row for the same field, or a new row after the field before it.
 * Whether a newly paired field changed isn't known until the page reloads.
 */
export function liveRows(rows: CompareRow[], to: UiPayload): CompareRow[] {
  const shown = fieldUids(to.nodes);
  const shownSet = new Set(shown);
  const result = rows.flatMap((row): CompareRow[] => {
    if (row.toUid === null || shownSet.has(row.toUid)) {
      return [row];
    }

    return row.fromUid === null ? [] : [{...row, toUid: null, changed: true}];
  });
  let previous = -1;

  for (const uid of shown) {
    let index = result.findIndex((row) => row.toUid === uid);

    if (index < 0) {
      index = result.findIndex(
        (row) => row.toUid === null && row.fromUid === uid
      );

      if (index >= 0) {
        result[index] = {...result[index]!, toUid: uid, changed: null};
      } else {
        index = previous + 1;
        result.splice(index, 0, {fromUid: null, toUid: uid, changed: true});
      }
    }

    previous = index;
  }

  return result;
}

/** The icon that tells each side apart once they're stacked: A above, B below. */
export const sideIcons: Record<CompareSide, string> = {
  from: 'circle-a',
  to: 'circle-b',
};

/** The markup for a side's icon before a field's label. */
export function sideIconHtml(side: CompareSide): string {
  return `<craft-icon name="${sideIcons[side]}" class="compare-side-icon" aria-hidden="true"></craft-icon>`;
}

/**
 * A side's form with its icon before each field's label, for the screen to
 * show once the sides are stacked. It's decorative: the field's cell is
 * already named by the side's heading. Its fields lose their “Compare
 * changes” action, which the screen already is.
 */
export function withSideIcon(ui: UiPayload, side: CompareSide): UiPayload {
  const headingPrefix = sideIconHtml(side);
  const prefix = (nodes: UiNodePayload[]): UiNodePayload[] =>
    nodes.map((node) =>
      node.control
        ? withoutCompareAction({...node, props: {...node.props, headingPrefix}})
        : {...node, children: node.children && prefix(node.children)}
    );

  return {...ui, nodes: prefix(ui.nodes)};
}

/** The field action that opens “Compare changes”, which the screen already is. */
const compareActionEvent = 'craft:compare-field-changes';

interface MenuItem {
  type?: string;
  items?: MenuItem[];
  action?: {type?: string; name?: string} | null;
}

function withoutCompareItem(items: MenuItem[]): MenuItem[] {
  const kept = items.flatMap((item): MenuItem[] => {
    if (item.type === 'group') {
      const groupItems = withoutCompareItem(item.items ?? []);

      return groupItems.length ? [{...item, items: groupItems}] : [];
    }

    return item.action?.type === 'event' &&
      item.action.name === compareActionEvent
      ? []
      : [item];
  });

  // No rule left at either end, or next to another.
  return kept.filter(
    (item, index) =>
      item.type !== 'hr' ||
      (index > 0 && index < kept.length - 1 && kept[index + 1]?.type !== 'hr')
  );
}

/**
 * A field without its “Compare changes” action, and without its action menu
 * if that was all it had.
 */
export function withoutCompareAction(node: UiNodePayload): UiNodePayload {
  if (!node.children) {
    return node;
  }

  return {
    ...node,
    children: node.children.flatMap((child) => {
      if (child.component !== 'craft:action-menu') {
        return [child];
      }

      const items = withoutCompareItem(
        (child.props.items as MenuItem[] | undefined) ?? []
      );

      return items.length
        ? [
            {
              ...child,
              props: {
                ...child.props,
                items: items as unknown as UiProperties[],
              },
            },
          ]
        : [];
    }),
  };
}

/**
 * Remembers each field's value the first time a form shows it, by layout UID,
 * leaving any already remembered as they were — so a tab that loads later adds
 * its fields as they loaded, rather than as missing.
 */
export function rememberValues(
  nodes: UiNodePayload[],
  values: UiValues,
  remembered: Map<string, string>
): void {
  for (const node of fieldNodes(nodes)) {
    const uid = layoutUid(node);

    if (node.control && !remembered.has(uid)) {
      remembered.set(uid, canonical(valueAt(values, node.control.path)));
    }
  }
}

/** The layout UIDs of the fields whose values differ from those remembered. */
export function editedFields(
  nodes: UiNodePayload[],
  values: UiValues,
  remembered: Map<string, string>
): string[] {
  return fieldNodes(nodes)
    .filter((node) => {
      const loaded = remembered.get(layoutUid(node));

      return (
        node.control !== undefined &&
        loaded !== undefined &&
        loaded !== canonical(valueAt(values, node.control.path))
      );
    })
    .map(layoutUid);
}

/**
 * The older version's value of a field, read to copy into the newer one, when
 * the newer one can be edited, both are the same kind of control, the older
 * one's value can be read and the newer one's written, and the value isn't
 * the newer one's already.
 *
 * @param current The newer field's value as it stands, as a canonical string,
 *   when it's been edited since `to` was rendered.
 */
export function copyableValue(
  row: CompareRow,
  from: UiPayload,
  to: UiPayload,
  current?: string
): UiValueSnapshot | undefined {
  if (row.fromUid === null || row.toUid === null) {
    return undefined;
  }

  const fromControl = fieldNodes(from.nodes).find(
    (node) => layoutUid(node) === row.fromUid
  )?.control;
  const toControl = fieldNodes(to.nodes).find(
    (node) => layoutUid(node) === row.toUid
  )?.control;

  if (
    !fromControl ||
    !toControl ||
    toControl.mode !== 'editable' ||
    fromControl.component !== toControl.component ||
    !toControl.writable
  ) {
    return undefined;
  }

  const snapshot = readControlValue(fromControl, from.values);

  if (
    !snapshot ||
    canonical(snapshot.value) ===
      (current ?? canonical(valueAt(to.values, toControl.path)))
  ) {
    return undefined;
  }

  return snapshot;
}
