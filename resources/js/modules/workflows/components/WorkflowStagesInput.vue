<script setup lang="ts">
  import {computed} from 'vue';
  import {t} from '@craftcms/ui';
  import CraftInput from '@craftcms/ui/vue/CraftInput.vue';
  import ActionMenu from '@/common/components/ActionMenu.vue';
  import SelectableCardList from '@/common/components/SelectableCardList.vue';
  import {useSelectable} from '@/common/composables/useSelectable';
  import type {ActionItems} from '@/common/types';
  import FormRenderer from '@/modules/forms/FormRenderer.vue';
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
    errors?: FormPayload['errors'];
  }>();
  const emit = defineEmits<{
    'update:modelValue': [value: WorkflowStage[]];
  }>();
  const addStageActions = computed<ActionItems>(() =>
    props.stageTypes.map((stageType) => ({
      label: stageType.label,
      icon: 'plus',
      onClick: () => addStage(stageType.type),
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

  const selection = useSelectable<string>({
    ids: () => props.modelValue.map((stage) => stage.uid),
    enabled: false,
    readOnly: true,
  });

  function stageTypeLabel(stage: WorkflowStage): string {
    return (
      props.stageTypes.find((type) => type.type === stage.type)?.label ??
      t('Missing stage type')
    );
  }

  function changeName(index: number, value: string | number | undefined): void {
    updateStage(index, {name: String(value ?? '')});
  }

  function stageNameError(index: number): string | undefined {
    return props.errors
      ?.find((error) => error.path.join('.') === `stages.${index}.name`)
      ?.messages.join(' ');
  }

  function stageActions(index: number): ActionItems {
    return [
      {
        label: t('Remove stage'),
        icon: 'trash',
        variant: 'danger',
        disabled: props.modelValue.length === 1,
        onClick: () => removeStage(index),
      },
    ];
  }

  function stageForm(stage: WorkflowStage): FormPayload | null {
    return stage.settingsForm
      ? {
          ...(stage.settingsForm as FormPayload),
          values: stage.settings,
        }
      : null;
  }
</script>

<template>
  <div>
    <SelectableCardList
      :ids="modelValue.map((stage) => stage.uid)"
      :selection="selection"
      :sortable="editable && modelValue.length > 1"
      :read-only="!editable"
      single-column
      tag="div"
      item-tag="div"
      list-class="grid gap-1"
      :item-attrs="
        (uid, index) => ({
          role: 'listitem',
          'aria-label': t('Stage {num}: {name}', {
            num: index + 1,
            name: modelValue[index]?.name ?? uid,
          }),
        })
      "
      role="list"
      @reorder="moveStage"
    >
      <template #label="{index}">
        {{ stageTypeLabel(modelValue[index]!) }}
      </template>

      <template #actions="{index}">
        <ActionMenu
          v-if="editable"
          :actions="stageActions(index)"
          :label="t('Stage {num} actions', {num: index + 1})"
        />
      </template>

      <template #default="{index}">
        <div class="grid gap-4">
          <CraftInput
            :label="t('Name')"
            required
            :disabled="!editable"
            :model-value="modelValue[index]?.name ?? ''"
            :error="stageNameError(index)"
            @update:model-value="changeName(index, $event)"
          >
            <input slot="input" />
          </CraftInput>

          <FormRenderer
            v-if="stageForm(modelValue[index]!)"
            :key="modelValue[index]!.type"
            :payload="stageForm(modelValue[index]!)!"
            :disabled="!editable"
            @change="(change, values) => changeSettings(index, change, values)"
          />
        </div>
      </template>
    </SelectableCardList>

    <div v-if="editable" class="mt-3">
      <ActionMenu
        :actions="addStageActions"
        :searchable="stageTypes.length > 5"
        :label="t('Add stage')"
      >
        <template #invoker="{attributes}">
          <craft-button
            v-bind="attributes"
            type="button"
            variant="dashed"
            icon="plus"
            :disabled="!stageTypes.length"
          >
            {{ t('Add stage') }}
          </craft-button>
        </template>
      </ActionMenu>
    </div>
  </div>
</template>
