import {shallowRef, type ShallowRef} from 'vue';
import {
  expandFormData,
  type PostValue,
  type PostValues,
} from '@/common/utils/forms';

export type ConditionRuleConfig = {class: string} & PostValues;

export type ConditionConfig = {
  class: string;
  conditionRules?: ConditionRuleConfig[];
} & PostValues;

function isConditionConfig(value: PostValue): value is ConditionConfig {
  return (
    value instanceof Object &&
    !Array.isArray(value) &&
    !(value instanceof File) &&
    Object(value.class).constructor === String
  );
}

/**
 * Owns the element index's active filter condition: the portable condition
 * config that the filter HUD edits and the index submits alongside its other
 * filters as the `condition` param.
 */
export function useConditionBuilder({
  initialState = null,
}: {initialState?: ConditionConfig | null} = {}) {
  const conditions: ShallowRef<ConditionConfig | null> =
    shallowRef(initialState);

  return {conditions};
}

/**
 * Extracts the condition config from a server-rendered condition builder
 * form (inputs namespaced under `condition`). Returns null when the builder
 * has no rules, so an empty builder clears the filter instead of submitting
 * an empty condition.
 */
export function conditionsFromForm(
  form: HTMLFormElement
): ConditionConfig | null {
  const expanded = expandFormData(new FormData(form));
  const condition = expanded.condition;

  if (!isConditionConfig(condition)) {
    return null;
  }

  if (Array.isArray(condition.conditionRules)) {
    // Rule inputs are 1-based (`conditionRules[1]`), which leaves a hole at
    // index 0 when expanded; compact so the config JSON-serializes cleanly.
    condition.conditionRules = condition.conditionRules.filter((rule) =>
      isConditionConfig(rule)
    );
  }

  if (!condition.conditionRules?.length) {
    return null;
  }

  return condition;
}
