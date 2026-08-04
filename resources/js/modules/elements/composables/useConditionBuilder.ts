import {ref, type Ref} from 'vue';
import {expandFormData} from '@/common/utils/forms';

// The config's values are arbitrary server-defined rule settings, so they
// stay `any` (also keeps the type compatible with Inertia's FormDataType).
export type ConditionRuleConfig = {class: string} & Record<string, any>;

export type ConditionConfig = {
  class: string;
  conditionRules?: ConditionRuleConfig[];
} & Record<string, any>;

/**
 * Owns the element index's active filter condition: the portable condition
 * config that the filter HUD edits and the index submits alongside its other
 * filters as the `condition` param.
 */
export function useConditionBuilder({
  initialState = null,
}: {initialState?: ConditionConfig | null} = {}) {
  const conditions: Ref<ConditionConfig | null> = ref(initialState);

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
  const condition = expanded.condition as ConditionConfig | undefined;

  if (!condition) {
    return null;
  }

  if (Array.isArray(condition.conditionRules)) {
    // Rule inputs are 1-based (`conditionRules[1]`), which leaves a hole at
    // index 0 when expanded; compact so the config JSON-serializes cleanly.
    condition.conditionRules = condition.conditionRules.filter(
      Boolean
    ) as ConditionRuleConfig[];
  }

  if (!condition.conditionRules?.length) {
    return null;
  }

  return condition;
}
