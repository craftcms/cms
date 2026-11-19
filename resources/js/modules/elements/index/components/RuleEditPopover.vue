<script setup lang="ts">
  import {t} from '@craftcms/ui';
  import {computed, useTemplateRef} from 'vue';
  import {
    findGroup,
    findRule,
    removeGroup,
    replaceGroup,
    replaceRule,
    rootGroup,
    ungroup,
  } from '@/modules/conditions/conditionTree';
  import type {ConditionConfig, RuleConfig} from '@/modules/conditions/types';
  import ConditionBuilderPopover from './ConditionBuilderPopover.vue';

  /** Edits one applied rule, or one nested group, wherever it sits. */
  const props = defineProps<{
    anchor: HTMLElement;
    /** The whole applied condition. */
    condition: ConditionConfig;
    /** The rule being edited, at any depth. */
    uid?: string;
    /** The nested group being edited instead, by its position. */
    groupPath?: number[];
    /** The rule's or group's name, for the popover's title. */
    label?: string;
  }>();

  const emit = defineEmits<{
    close: [];
    apply: [condition: ConditionConfig | null];
  }>();

  const popover =
    useTemplateRef<InstanceType<typeof ConditionBuilderPopover>>('popover');

  // The builder is given just this rule or group.
  const scoped = computed<ConditionConfig>(() => {
    const group = props.groupPath
      ? findGroup(props.condition, props.groupPath)
      : undefined;
    const rule = props.uid ? findRule(props.condition, props.uid) : undefined;

    return {
      ...props.condition,
      conditionRules: group
        ? {operator: group.operator, rules: group.rules}
        : rule
          ? [rule]
          : [],
    };
  });

  function apply(draft: ConditionConfig): void {
    const edited = rootGroup(draft);

    emit(
      'apply',
      props.groupPath
        ? replaceGroup(props.condition, props.groupPath, edited)
        : replaceRule(
            props.condition,
            props.uid!,
            (edited.rules[0] as RuleConfig | undefined) ?? null
          )
    );
  }

  function remove(): void {
    emit(
      'apply',
      props.groupPath
        ? removeGroup(props.condition, props.groupPath)
        : replaceRule(props.condition, props.uid!, null)
    );
  }

  function ungroupRules(): void {
    emit('apply', ungroup(props.condition, props.groupPath!));
    popover.value?.hide();
  }
</script>

<template>
  <ConditionBuilderPopover
    ref="popover"
    :anchor="anchor"
    :condition="scoped"
    :builder-id="groupPath ? 'filter-group' : 'filter-rule'"
    :label="label ?? t('Filter')"
    titled
    :scope="groupPath ? 'group' : 'rule'"
    :clear-label="t('Remove')"
    @apply="apply"
    @clear="remove"
    @close="emit('close')"
  >
    <template v-if="groupPath" #footer-start>
      <craft-button
        slot="footer-start"
        type="button"
        variant="plain"
        @click="ungroupRules"
        >{{ t('Ungroup') }}</craft-button
      >
    </template>
  </ConditionBuilderPopover>
</template>
