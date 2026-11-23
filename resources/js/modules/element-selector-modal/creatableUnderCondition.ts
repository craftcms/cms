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

/**
 * Returns which entry types an entry created from an entry selector can have,
 * in which sections, and still meet the selector's selection condition, or
 * `null` when the condition has a rule that depends on more than that, such as
 * a custom field's value, which can't be known until the entry is saved.
 */
export function creatableUnderCondition(
  condition: unknown
): ((creatable: CreatableType) => boolean) | null {
  const configs =
    (condition as {conditionRules?: RuleConfig[]} | null | undefined)
      ?.conditionRules ?? [];
  const tests: Array<(creatable: CreatableType) => boolean> = [];

  for (const config of configs) {
    const test = knownRules[config.class ?? '']?.(config) ?? null;

    if (!test) {
      return null;
    }

    tests.push(test);
  }

  return (creatable) => tests.every((test) => test(creatable));
}
