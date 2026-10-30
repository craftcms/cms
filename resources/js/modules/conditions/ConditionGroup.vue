<script setup lang="ts">
  import {inject} from 'vue';
  import {t} from '@craftcms/ui';
  import {useDelayedLoading} from '@/common/composables/useDelayedLoading';
  import TypePicker from '@/common/components/TypePicker.vue';
  import {ConditionEditor, type GroupDraft} from './types';
  import ConditionRule from './ConditionRule.vue';
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

    editor.addRule(props.group, payload);
  }

  function remove(index: number): void {
    editor.removeChild(props.group, index);
  }

  function removeGroup(): void {
    if (
      !props.group.rules.length ||
      window.confirm(t('Remove this group and all its rules?'))
    ) {
      emit('remove');
    }
  }

  function onOperatorChange(event: Event): void {
    if (!(event.target instanceof HTMLSelectElement)) {
      throw new TypeError('Expected a select event target.');
    }

    editor.setOperator(
      props.group,
      event.target.value as GroupDraft['operator']
    );
  }
</script>

<template>
  <div class="relative" :aria-busy="isLoading">
    <craft-pane
      :inert="isLoading"
      class="condition-group min-w-0"
      appearance="outline"
      padding="lg"
      role="group"
      :aria-label="t('Condition group')"
    >
      <div
        slot="title"
        class="condition-group__operator flex items-center gap-2 font-normal"
      >
        <span>{{ t('Where') }}</span>
        <craft-select :label="t('Group Operator')" label-sr-only small>
          <select
            slot="input"
            :disabled="!editor.editable()"
            :value="group.operator"
            @change="onOperatorChange"
          >
            <option value="and">{{ t('All') }}</option>
            <option value="or">{{ t('Any') }}</option>
          </select>
        </craft-select>
      </div>

      <craft-button
        slot="header-actions"
        v-if="!root && editor.editable()"
        type="button"
        icon="xmark-large"
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
          <TypePicker
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
            @click="editor.addGroup(group)"
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

<style scoped>
  .condition-group__operator :deep(craft-select) {
    width: auto;
  }
</style>
