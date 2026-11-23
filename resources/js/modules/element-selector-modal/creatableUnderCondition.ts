import type {CreatableType} from '@/modules/elements/composables/useCreatableEntryTypes';

type RuleConfig = {
  class?: string;
  operator?: string;
  value?: unknown;
  values?: unknown;
};

const rules = 'CraftCms\\Cms\\Entry\\Conditions\\';

/**
 * Whether a multi-select rule's operator and values allow `value`, as
 * `BaseMultiSelectConditionRule::matchValue()` decides it.
 */
function multiSelectAllows(rule: RuleConfig, value: string): boolean {
  if (rule.operator === 'notempty') {
    return true;
  }

  const values = Array.isArray(rule.values) ? rule.values.map(String) : [];

  if (values.length === 0) {
    return true;
  }

  return rule.operator === 'ni'
    ? !values.includes(value)
    : values.includes(value);
}

/**
 * The rules whose outcome for a new entry is known from its section and entry
 * type alone, so the New entry button can offer only what they allow.
 */
const knownRules: Record<
  string,
  (rule: RuleConfig) => ((creatable: CreatableType) => boolean) | null
> = {
  // An entry the user creates is viewable to them
  [`${rules}ViewableConditionRule`]: (rule) =>
    rule.value === true ? () => true : null,
  // The button only offers sections whose entries the user can save
  [`${rules}SavableConditionRule`]: (rule) =>
    rule.value === true ? () => true : null,
  [`${rules}SectionConditionRule`]:
    (rule) =>
    ({section}) =>
      multiSelectAllows(rule, section.uid),
  [`${rules}SectionTypeConditionRule`]:
    (rule) =>
    ({section}) =>
      multiSelectAllows(rule, section.type),
  [`${rules}TypeConditionRule`]:
    (rule) =>
    ({entryType}) =>
      multiSelectAllows(rule, entryType.uid),
};

type Test = (creatable: CreatableType) => boolean;

/** A condition's rule group, as `BaseConditionGroup::getConfig()` gives it. */
type GroupConfig = {operator?: string; rules?: Array<RuleConfig | GroupConfig>};

/**
 * Returns the test for a rule group, or `null` when it has a rule anywhere
 * that `knownRules` doesn't cover. A group given as a plain list of rules,
 * as older configs are, is an `and` group.
 */
function groupTest(
  group: GroupConfig | Array<RuleConfig | GroupConfig>
): Test | null {
  const {operator = 'and', rules = []} = Array.isArray(group)
    ? {rules: group}
    : group;
  const tests: Test[] = [];

  for (const config of rules) {
    const test =
      'rules' in config && !('class' in config)
        ? groupTest(config as GroupConfig)
        : (knownRules[(config as RuleConfig).class ?? '']?.(
            config as RuleConfig
          ) ?? null);

    if (!test) {
      return null;
    }

    tests.push(test);
  }

  if (tests.length === 0) {
    return () => true;
  }

  return operator === 'or'
    ? (creatable) => tests.some((test) => test(creatable))
    : (creatable) => tests.every((test) => test(creatable));
}

/**
 * Returns which entry types an entry created from an entry selector can have,
 * in which sections, and still meet the selector's selection condition, or
 * `null` when the condition has a rule that depends on more than that, such as
 * a custom field's value, which can't be known until the entry is saved.
 */
export function creatableUnderCondition(condition: unknown): Test | null {
  const rules = (
    condition as
      | {conditionRules?: GroupConfig | Array<RuleConfig | GroupConfig>}
      | null
      | undefined
  )?.conditionRules;

  return groupTest(rules ?? []);
}
