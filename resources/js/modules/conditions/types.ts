import type {FormPayload, FormValues} from '@/modules/forms/types';
import type {InjectionKey} from 'vue';

export type RuleConfig = {class: string; uid?: string} & FormValues;

export type GroupConfig = {
  operator: CraftCms.Cms.Condition.Enums.GroupOperator;
  rules: Array<RuleConfig | GroupConfig>;
} & FormValues;

export type ConditionConfig = {
  class: string;
  conditionRules?: GroupConfig | RuleConfig[];
} & FormValues;

export type RulePayload = Omit<
  CraftCms.Cms.Condition.ConditionRulePayload,
  'config' | 'form'
> & {
  config: RuleConfig & {uid: string};
  form: FormPayload;
};

export type BuilderPayload = Omit<
  CraftCms.Cms.Condition.ConditionBuilderPayload,
  'config' | 'value' | 'rules'
> & {
  config: FormValues;
  value: ConditionConfig;
  rules: Record<string, RulePayload>;
};

export type RuleDraft = {kind: 'rule'; id: string};

export type GroupDraft = {
  kind: 'group';
  id: string;
  operator: CraftCms.Cms.Condition.Enums.GroupOperator;
  rules: Array<RuleDraft | GroupDraft>;
};

export const ConditionEditor: InjectionKey<{
  payload: () => BuilderPayload;
  rules: Record<string, RulePayload>;
  errors: () => FormPayload['errors'];
  editable: () => boolean;
  value: () => ConditionConfig;
  changed: () => void;
  status: (id: string, valid: boolean) => void;
  registerRule: (
    id: string,
    rule?: {snapshot: () => RulePayload; canSubmit: () => boolean}
  ) => void;
}> = Symbol('ConditionEditor');
