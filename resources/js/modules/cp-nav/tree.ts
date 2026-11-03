/**
 * The nav builder's tree, and the edits it makes to it.
 *
 * The server hands the builder the nav with a customization worked in
 * (`NavCustomizer::editorTree()`) and takes the edited tree back, working out
 * the customization from it. So everything here is plain tree surgery; none
 * of it decides what gets saved.
 *
 * Where things can go:
 * - Sections sit at the top level, after any items there.
 * - Items sit at the top level, before any section, or in a section.
 * - Anything beneath an item changes places with its siblings, or moves out
 *   to where items go, taking its own children along. It can go back beneath
 *   the item it came from (its `home`), but never beneath another one.
 */

import {
  resolveStructureMove,
  type StructureMove,
} from '@/modules/elements/index/composables/useElementIndexStructure';

/** Generated from `CraftCms\Cms\Cp\Data\NavNode`. */
export type NavNode = CraftCms.Cms.Cp.Data.NavNode;

/** One line of the builder: any node, at any depth. */
export interface NavRow {
  id: string;
  node: NavNode;
  label: string;
  /** 1 at the top level. */
  level: number;
  hasDescendants: boolean;
  /** The section or item it sits in, or null at the top level. */
  parentId: string | null;
}

/** Where a node goes: among a parent's children, at an index. */
export interface NavPlacement {
  parentId: string | null;
  /** Counted as though the node had already been taken out. */
  index: number;
}

/** A structure move, or one to the top level or a section from a menu. */
export type NavMove = StructureMove | {type: 'to'; parentId: string | null};

/** The top level, items before sections. */
function root(tree: NavNode[]): NavNode[] {
  return [
    ...tree.filter((node) => node.kind !== 'section'),
    ...tree.filter((node) => node.kind === 'section'),
  ];
}

/** Every node as a row, depth first. */
export function flatten(tree: NavNode[]): NavRow[] {
  const rows: NavRow[] = [];
  const walk = (nodes: NavNode[], level: number, parentId: string | null) => {
    for (const node of nodes) {
      rows.push({
        id: node.id,
        node,
        label: node.label,
        level,
        hasDescendants: node.children.length > 0,
        parentId,
      });
      walk(node.children, level + 1, node.id);
    }
  };

  walk(root(tree), 1, null);

  return rows;
}

/** Whether a node's user condition has rules, so not everyone sees it. */
export function isConditional(node: NavNode): boolean {
  const rules = node.userCondition?.conditionRules;

  return (Array.isArray(rules) ? rules : (rules?.rules ?? [])).length > 0;
}

/** Finds a node anywhere in the tree. */
export function find(tree: NavNode[], id: string): NavNode | null {
  for (const node of tree) {
    if (node.id === id) return node;

    const found = find(node.children, id);

    if (found) return found;
  }

  return null;
}

function childrenOf(tree: NavNode[], parentId: string | null): NavNode[] {
  return parentId === null
    ? root(tree)
    : (find(tree, parentId)?.children ?? []);
}

/** The tree with one node changed. */
export function update(
  tree: NavNode[],
  id: string,
  changes: Partial<NavNode>
): NavNode[] {
  return tree.map((node) =>
    node.id === id
      ? {...node, ...changes}
      : {...node, children: update(node.children, id, changes)}
  );
}

/** The tree without a node. */
export function remove(tree: NavNode[], id: string): NavNode[] {
  return tree
    .filter((node) => node.id !== id)
    .map((node) => ({...node, children: remove(node.children, id)}));
}

function insert(
  tree: NavNode[],
  node: NavNode,
  {parentId, index}: NavPlacement
): NavNode[] {
  if (parentId === null) {
    const nodes = root(tree);
    nodes.splice(index, 0, node);

    return nodes;
  }

  return tree.map((n) => {
    if (n.id !== parentId) {
      return {...n, children: insert(n.children, node, {parentId, index})};
    }

    const children = [...n.children];
    children.splice(index, 0, node);

    return {...n, children};
  });
}

/** How many items sit at the top level, before the first section. */
function topLevelItemCount(tree: NavNode[], without: string): number {
  return tree.filter((node) => node.kind !== 'section' && node.id !== without)
    .length;
}

/**
 * Where a move would put a node, before checking it's allowed there. Moves
 * between rows are worked out the way structures work them out.
 */
export function placement(
  tree: NavNode[],
  id: string,
  move: NavMove
): NavPlacement | null {
  const othersIn = (parentId: string | null): NavNode[] =>
    childrenOf(tree, parentId).filter((node) => node.id !== id);

  if (move.type === 'to') {
    return {
      parentId: move.parentId,
      index:
        move.parentId === null && find(tree, id)?.kind !== 'section'
          ? topLevelItemCount(tree, id)
          : othersIn(move.parentId).length,
    };
  }

  const resolved = resolveStructureMove(flatten(tree), id, move, {
    startsAtTop: true,
  });

  if (!resolved) return null;

  const parentId =
    resolved.newParentId === undefined ? null : String(resolved.newParentId);

  return {
    parentId,
    index:
      resolved.prevId === undefined
        ? 0
        : othersIn(parentId).findIndex(
            (node) => node.id === String(resolved.prevId)
          ) + 1,
  };
}

/** Whether a node can go somewhere. */
export function canPlace(
  tree: NavNode[],
  id: string,
  to: NavPlacement | null
): boolean {
  const source = find(tree, id);

  if (!source || !to) return false;

  const parent = to.parentId === null ? null : find(tree, to.parentId);

  if (to.parentId !== null && !parent) return false;

  const itemCount = topLevelItemCount(tree, id);

  if (source.kind === 'section') {
    return to.parentId === null && to.index >= itemCount;
  }

  const fitsAsItem =
    (to.parentId === null && to.index <= itemCount) ||
    parent?.kind === 'section';

  // Back beneath the item it came from, or out to where items go.
  return (
    (source.home !== null && to.parentId === source.home) ||
    (fitsAsItem && !source.heading)
  );
}

/** The tree once a node is moved, or null when it can't go there. */
export function moveNode(
  tree: NavNode[],
  id: string,
  move: NavMove
): NavNode[] | null {
  const to = placement(tree, id, move);

  if (!to || !canPlace(tree, id, to)) return null;

  const source = find(tree, id)!;
  const kind =
    source.kind === 'section'
      ? 'section'
      : source.home !== null && to.parentId === source.home
        ? 'child'
        : 'item';

  return insert(remove(tree, id), {...source, kind}, to);
}

/** The tree without a section, its items kept at the top level. */
export function removeSection(tree: NavNode[], id: string): NavNode[] {
  const section = tree.find((node) => node.id === id);

  if (!section) return tree;

  return section.children.reduce(
    (next, child) =>
      insert(next, child, {
        parentId: null,
        index: topLevelItemCount(next, ''),
      }),
    remove(tree, id)
  );
}

function randomId(): string {
  return `custom-${crypto.randomUUID().replaceAll('-', '').slice(0, 12)}`;
}

function blank(overrides: Partial<NavNode>): NavNode {
  return {
    id: '',
    kind: 'item',
    source: 'custom',
    type: null,
    label: '',
    defaultLabel: null,
    icon: null,
    defaultIcon: null,
    iconSvg: null,
    href: null,
    url: null,
    newWindow: false,
    userCondition: null,
    home: null,
    hidden: false,
    inherited: false,
    heading: false,
    children: [],
    ...overrides,
  };
}

/** A new, empty section, added at the end. */
export function addSection(
  tree: NavNode[],
  label: string
): [NavNode[], string] {
  const section = blank({
    id: randomId(),
    kind: 'section',
    type: 'section',
    label,
  });

  return [[...root(tree), section], section.id];
}

/** A new link, added at the end of the top level's items. */
export function addLink(
  tree: NavNode[],
  link: {label: string; url: string; icon: string | null; newWindow: boolean}
): [NavNode[], string] {
  const node = blank({
    id: randomId(),
    type: 'link',
    ...link,
    href: link.url,
  });

  return [
    insert(tree, node, {parentId: null, index: topLevelItemCount(tree, '')}),
    node.id,
  ];
}
