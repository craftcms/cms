import type {ConditionConfig, GroupConfig, RuleConfig} from './types';

export type ConditionItem = RuleConfig | GroupConfig;
export type GroupOperator = GroupConfig['operator'];

export function isGroup(item: ConditionItem): item is GroupConfig {
  return typeof item.class !== 'string' && Array.isArray(item.rules);
}

/** A condition's top level: its rules and how they combine. */
export function rootGroup(condition: ConditionConfig | null): {
  operator: GroupOperator;
  rules: ConditionItem[];
} {
  const rules = condition?.conditionRules;

  if (!rules) return {operator: 'and' as GroupOperator, rules: []};

  return Array.isArray(rules)
    ? {operator: 'and' as GroupOperator, rules}
    : {operator: rules.operator, rules: rules.rules};
}

/** Rewrites every item, at any depth, dropping groups left empty. */
function mapItems(
  items: ConditionItem[],
  map: (item: RuleConfig) => RuleConfig | null
): ConditionItem[] {
  return items.flatMap((item): ConditionItem[] => {
    if (isGroup(item)) {
      const rules = mapItems(item.rules, map);
      return rules.length ? [{...item, rules}] : [];
    }

    const mapped = map(item);
    return mapped ? [mapped] : [];
  });
}

function withRoot(
  condition: ConditionConfig,
  operator: GroupOperator,
  rules: ConditionItem[]
): ConditionConfig | null {
  return rules.length
    ? {...condition, conditionRules: {operator, rules}}
    : null;
}

/** Swaps the rule `uid` for `replacement`, or removes it when that's null. */
export function replaceRule(
  condition: ConditionConfig,
  uid: string,
  replacement: RuleConfig | null
): ConditionConfig | null {
  const root = rootGroup(condition);

  return withRoot(
    condition,
    root.operator,
    mapItems(root.rules, (rule) => (rule.uid === uid ? replacement : rule))
  );
}

export function findRule(
  condition: ConditionConfig | null,
  uid: string
): RuleConfig | undefined {
  const search = (items: ConditionItem[]): RuleConfig | undefined => {
    for (const item of items) {
      const found = isGroup(item)
        ? search(item.rules)
        : item.uid === uid
          ? item
          : undefined;
      if (found) return found;
    }
    return undefined;
  };

  return search(rootGroup(condition).rules);
}

type GroupLike = {operator: GroupOperator; rules: ConditionItem[]};

/**
 * Rewrites the group at `path` (indexes into each level's rules; empty is the
 * top level), dropping it when `update` returns null.
 */
function updateGroup(
  condition: ConditionConfig,
  path: number[],
  update: (group: GroupLike) => GroupLike | null
): ConditionConfig | null {
  const root = rootGroup(condition);

  if (!path.length) {
    const updated = update(root);
    return updated
      ? withRoot(condition, updated.operator, updated.rules)
      : null;
  }

  const walk = (items: ConditionItem[], depth: number): ConditionItem[] =>
    items.flatMap((item, index): ConditionItem[] => {
      if (index !== path[depth] || !isGroup(item)) return [item];
      if (depth < path.length - 1) {
        return [{...item, rules: walk(item.rules, depth + 1)}];
      }
      const updated = update(item);
      return updated?.rules.length ? [{...item, ...updated}] : [];
    });

  return withRoot(condition, root.operator, walk(root.rules, 0));
}

/** Flips a group between All and Any. */
export function toggleOperator(
  condition: ConditionConfig,
  path: number[]
): ConditionConfig | null {
  return updateGroup(condition, path, (group) => ({
    ...group,
    operator: (group.operator === 'or' ? 'and' : 'or') as GroupOperator,
  }));
}

/** Drops a group and every rule in it. */
export function removeGroup(
  condition: ConditionConfig,
  path: number[]
): ConditionConfig | null {
  return replaceGroup(condition, path, null);
}

/** Moves a group's rules up into its parent, in its place. */
export function ungroup(
  condition: ConditionConfig,
  path: number[]
): ConditionConfig | null {
  const at = path[path.length - 1];

  return updateGroup(condition, path.slice(0, -1), (parent) => ({
    ...parent,
    rules: parent.rules.flatMap((item, index) =>
      index === at && isGroup(item) ? item.rules : [item]
    ),
  }));
}

/** The group at `path`, if there is one. */
export function findGroup(
  condition: ConditionConfig | null,
  path: number[]
): GroupConfig | undefined {
  let items = rootGroup(condition).rules;
  let group: GroupConfig | undefined;

  for (const index of path) {
    const item = items[index];
    if (!item || !isGroup(item)) return undefined;
    group = item;
    items = item.rules;
  }

  return group;
}

/** Swaps the group at `path` for `replacement`, or drops it when that's null. */
export function replaceGroup(
  condition: ConditionConfig,
  path: number[],
  replacement: GroupLike | null
): ConditionConfig | null {
  return updateGroup(condition, path, () => replacement);
}
