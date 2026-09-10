<script setup lang="ts">
  import {inject, shallowReactive} from 'vue';
  import {t} from '@craftcms/ui';
  import {useDelayedLoading} from '@/common/composables/useDelayedLoading';
  import {ConditionEditor, type GroupDraft} from './types';
  import ConditionRule from './ConditionRule.vue';
  import ConditionRulePicker from './ConditionRulePicker.vue';
  import {useConditionRuleRequest} from './useConditionRuleRequest';

  const props = defineProps<{group: GroupDraft; root?: boolean}>();
  const emit = defineEmits<{remove: []}>();

  const editor = inject(ConditionEditor)!;
  const {execute, isLoading, error} = useConditionRuleRequest(
    props.group.id,
    t('Couldn’t add the condition rule.')
  );
  const showLoading = useDelayedLoading(isLoading);

  async function addRule(type: string): Promise<void> {
    const payload = await execute({type});

    if (!payload) return;

    editor.rules[payload.config.uid] = payload;
    props.group.rules.push({kind: 'rule', id: payload.config.uid});

    editor.changed();
  }

  function addGroup(): void {
    props.group.rules.push(
      shallowReactive({
        kind: 'group',
        id: crypto.randomUUID(),
        operator: 'and',
        rules: shallowReactive([]),
      })
    );

    editor.changed();
  }

  function remove(index: number): void {
    props.group.rules.splice(index, 1);

    editor.changed();
  }

  function removeGroup(): void {
    if (
      !props.group.rules.length ||
      window.confirm(t('Remove this group and all its rules?'))
    ) {
      emit('remove');
    }
  }

  function operator(value: GroupDraft['operator']): void {
    props.group.operator = value;

    editor.changed();
  }
</script>

<template>
  <div class="relative" :aria-busy="isLoading">
    <craft-pane
      :inert="isLoading"
      class="condition-group min-w-0"
      appearance="outline"
      padding="md"
      role="group"
      :aria-label="t('Condition group')"
    >
      <div slot="title" class="flex items-center gap-2 font-normal">
        <span>{{ t('Where') }}</span>
        <craft-button-group role="group" :aria-label="t('Group operator')">
          <craft-button
            type="button"
            variant="fill"
            :active="group.operator === 'and'"
            :aria-pressed="group.operator === 'and'"
            :disabled="!editor.editable()"
            @click="operator('and')"
            size="small"
            >{{ t('All') }}</craft-button
          >
          <craft-button
            type="button"
            variant="fill"
            :active="group.operator === 'or'"
            :aria-pressed="group.operator === 'or'"
            :disabled="!editor.editable()"
            @click="operator('or')"
            size="small"
            >{{ t('Any') }}</craft-button
          >
        </craft-button-group>
      </div>

      <craft-button
        slot="header-actions"
        v-if="!root && editor.editable()"
        type="button"
        icon="xmark"
        variant="danger-plain"
        size="small"
        :aria-label="t('Remove group')"
        @click="removeGroup"
      />

      <div class="min-w-0 flex flex-col gap-2">
        <template v-for="(child, index) in group.rules" :key="child.id">
          <ConditionGroup
            v-if="child.kind === 'group'"
            :group="child"
            @remove="remove(index)"
          />
          <ConditionRule v-else :rule="child" @remove="remove(index)" />
        </template>

        <div v-if="editor.editable()" class="flex flex-wrap items-center gap-2">
          <ConditionRulePicker
            :types="editor.payload().ruleTypes"
            :label="editor.payload().addRuleLabel"
            :disabled="isLoading || !editor.payload().ruleTypes.length"
            adding
            @select="addRule"
          />
          <craft-button
            type="button"
            icon="plus"
            variant="dashed"
            @click="addGroup"
            >{{ t('Add a group') }}</craft-button
          >
        </div>

        <craft-callout v-if="error" role="alert" variant="danger">
          {{ error }}
        </craft-callout>
      </div>
    </craft-pane>
    <div
      v-if="isLoading"
      class="absolute inset-0 z-20 flex items-center justify-center rounded-md cursor-wait"
      :class="{'bg-(--c-surface-raised)/70': showLoading}"
    >
      <craft-spinner v-if="showLoading" role="status">
        {{ t('Loading') }}
      </craft-spinner>
    </div>
  </div>
</template>
