<script setup lang="ts">
  import {computed} from 'vue';
  import {t} from '@craftcms/ui';
  import CraftInput from '@craftcms/ui/components/input/input';
  import TypePicker, {
    type TypePickerOption,
  } from '@/common/components/TypePicker.vue';
  import {useReorderableItems} from '@/common/composables/useReorderableItems';
  import TypeConfigurator from '@/modules/forms/TypeConfigurator.vue';
  import type {
    FormChange,
    FormPayload,
    FormValues,
  } from '@/modules/forms/types';

  type WorkflowStageData = CraftCms.Cms.Workflow.Data.WorkflowStageData;
  type WorkflowStage = {
    -readonly [Key in keyof WorkflowStageData]: WorkflowStageData[Key];
  };
  type StageType =
    CraftCms.Cms.Http.ViewModels.WorkflowEditViewModel['stageTypes'][number];

  const props = defineProps<{
    modelValue: WorkflowStage[];
    stageTypes: StageType[];
    editable: boolean;
  }>();
  const emit = defineEmits<{
    'update:modelValue': [value: WorkflowStage[]];
  }>();
  const typePickerOptions = computed<TypePickerOption[]>(() =>
    props.stageTypes.map((type) => ({
      value: type.type,
      label: type.label,
    }))
  );

  function updateStage(index: number, values: Partial<WorkflowStage>): void {
    emit(
      'update:modelValue',
      props.modelValue.map((stage, stageIndex) =>
        stageIndex === index ? {...stage, ...values} : stage
      )
    );
  }

  function changeSettings(
    index: number,
    _change: FormChange,
    values: FormValues
  ): void {
    updateStage(index, {settings: values});
  }

  function changeType(index: number, type: string): void {
    const current = props.modelValue[index];
    const replacement = props.stageTypes.find(
      (candidate) => candidate.type === type
    );
    if (!current || !replacement || current.type === type) return;

    if (
      Object.keys(current.settings).length > 0 &&
      !window.confirm(t('Changing the stage type will reset its settings.'))
    ) {
      return;
    }

    updateStage(index, {
      type: replacement.type,
      settings: {...replacement.settings},
      settingsForm: replacement.settingsForm,
    });
  }

  function addStage(type: string): void {
    const stageType = props.stageTypes.find(
      (candidate) => candidate.type === type
    );
    if (!stageType) return;

    emit('update:modelValue', [
      ...props.modelValue,
      {
        uid: crypto.randomUUID(),
        name: t('Review'),
        type: stageType.type,
        settings: {...stageType.settings},
        settingsForm: stageType.settingsForm,
      },
    ]);
  }

  function removeStage(index: number): void {
    if (props.modelValue.length === 1) return;
    emit(
      'update:modelValue',
      props.modelValue.filter((_, stageIndex) => stageIndex !== index)
    );
  }

  function moveStage(index: number, destination: number): void {
    if (destination < 0 || destination >= props.modelValue.length) return;

    const stages = [...props.modelValue];
    const [stage] = stages.splice(index, 1);
    if (!stage) return;
    stages.splice(destination, 0, stage);
    emit('update:modelValue', stages);
  }

  const {setItemRef, setHandleRef, getDragState, getDropState, getRowPosition} =
    useReorderableItems({
      getItemIds: () => props.modelValue.map((stage) => stage.uid),
      enabled: () => props.editable && props.modelValue.length > 1,
      onReorder: moveStage,
    });

  function stageTypeLabel(stage: WorkflowStage): string {
    return (
      props.stageTypes.find((type) => type.type === stage.type)?.label ??
      t('Missing stage type')
    );
  }

  function changeName(index: number, event: Event): void {
    if (!(event.target instanceof CraftInput)) {
      throw new TypeError('Expected a craft input event target.');
    }

    updateStage(index, {name: String(event.target.modelValue ?? '')});
  }
</script>

<template>
  <div class="flex flex-col gap-2">
    <craft-pane
      v-for="(stage, index) in modelValue"
      :key="stage.uid"
      :ref="(el: Parameters<typeof setItemRef>[0]) => setItemRef(el, stage.uid)"
      padding="lg"
      appearance="outline"
      class="workflow-stage"
      :class="{
        'opacity-50': getDragState(stage.uid).type === 'is-dragging',
        'bg-gray-100': getDropState(stage.uid).type === 'is-over',
      }"
      role="group"
      :aria-label="t('Stage {num}: {name}', {num: index + 1, name: stage.name})"
    >
      <div
        slot="title"
        class="workflow-stage__title flex items-center gap-2 font-normal"
      >
        <span class="whitespace-nowrap">
          {{ t('Stage {num}', {num: index + 1}) }}
        </span>
        <craft-input
          :label="t('Stage {num} name', {num: index + 1})"
          label-sr-only
          small
          required
          :disabled="!editable"
          .modelValue="stage.name"
          @model-value-changed="changeName(index, $event)"
        >
          <input slot="input" />
        </craft-input>
      </div>

      <div
        v-if="editable"
        slot="header-actions"
        class="flex items-center gap-1"
      >
        <craft-reorder-button
          :ref="
            (el: Parameters<typeof setHandleRef>[0]) =>
              setHandleRef(el, stage.uid)
          "
          :position="getRowPosition(index)"
          :disabled="modelValue.length === 1"
          :label="t('Reorder stage {num}', {num: index + 1})"
          @reorder="
            (event: CustomEvent<{direction: 'up' | 'down'}>) =>
              moveStage(
                index,
                index + (event.detail.direction === 'up' ? -1 : 1)
              )
          "
        />
        <craft-button
          type="button"
          size="small"
          variant="danger-plain"
          icon="xmark"
          :disabled="modelValue.length === 1"
          :aria-label="t('Remove stage')"
          @click="removeStage(index)"
        />
      </div>

      <TypeConfigurator
        :key="stage.type"
        :types="typePickerOptions"
        :selected-type-label="stageTypeLabel(stage)"
        :type-label="t('Type')"
        :form="
          stage.settingsForm
            ? {
                ...(stage.settingsForm as FormPayload),
                values: stage.settings,
              }
            : null
        "
        :disabled="!editable"
        @select="changeType(index, $event)"
        @change="(change, values) => changeSettings(index, change, values)"
      />
    </craft-pane>

    <TypePicker
      v-if="editable"
      :types="typePickerOptions"
      :label="t('Add stage')"
      :disabled="!stageTypes.length"
      adding
      @select="addStage"
    />
  </div>
</template>

<style scoped>
  .workflow-stage {
    container-type: inline-size;
  }

  .workflow-stage__title :deep(craft-input) {
    min-inline-size: 8rem;
  }
</style>
