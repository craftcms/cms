<script setup lang="ts">
  import {
    computed,
    inject,
    onBeforeUnmount,
    ref,
    shallowRef,
    useTemplateRef,
    watch,
  } from 'vue';
  import {t} from '@craftcms/ui';
  import {useDelayedLoading} from '@/common/composables/useDelayedLoading';
  import TypeConfigurator from '@/modules/forms/TypeConfigurator.vue';
  import {valueAt} from '@/modules/forms/runtime';
  import type {
    FormChange,
    FormPayload,
    FormValues,
  } from '@/modules/forms/types';
  import {ConditionEditor, type RuleDraft} from './types';
  import {useConditionRuleRequest} from './useConditionRuleRequest';

  const props = defineProps<{rule: RuleDraft}>();
  const emit = defineEmits<{remove: []}>();
  const editor = inject(ConditionEditor)!;

  const {execute, isLoading, error} = useConditionRuleRequest(
    props.rule.id,
    t('Couldn’t update the condition rule.')
  );
  const showLoading = useDelayedLoading(isLoading);

  const payload = computed(() => editor.rules[props.rule.id]!);

  const changingType = ref(false);
  const switching = computed(() => isLoading.value && changingType.value);

  const formKey = ref(0);
  const typeConfigurator = useTemplateRef('typeConfigurator');
  const latestFormPayload = shallowRef(payload.value.form);

  watch(
    () => payload.value.form,
    (payload) => (latestFormPayload.value = payload)
  );

  editor.registerRule(props.rule.id, {
    snapshot: () => ({
      ...payload.value,
      form: {
        ...latestFormPayload.value,
        values:
          typeConfigurator.value?.currentValues() ??
          latestFormPayload.value.values,
      },
    }),
    canSubmit: () => typeConfigurator.value?.canSubmit() ?? true,
  });

  onBeforeUnmount(() => {
    editor.registerRule(props.rule.id);
  });

  function change(_change: FormChange, values: FormValues): void {
    const inputs = valueAt(values, payload.value.form.scope);

    editor.rules[props.rule.id] = {
      ...payload.value,
      config: {
        ...payload.value.config,
        ...(inputs as FormValues),
      },
    };

    editor.changed();
  }

  async function refresh(values: FormValues): Promise<FormPayload> {
    changingType.value = false;

    const refreshed = await execute({...payload.value.config, ...values});

    if (!refreshed) throw new Error('Condition rule refresh did not complete.');

    latestFormPayload.value = refreshed.form;

    return refreshed.form;
  }

  async function switchType(type: string): Promise<void> {
    changingType.value = true;

    const refreshed = await execute({...payload.value.config, type});

    if (!refreshed) return;

    editor.rules[props.rule.id] = refreshed;
    latestFormPayload.value = refreshed.form;
    formKey.value++;

    editor.changed();
  }
</script>

<template>
  <div class="relative" :aria-busy="isLoading">
    <div
      :inert="isLoading"
      class="condition-rule min-w-0"
      role="group"
      :aria-label="payload.label"
    >
      <div class="condition-rule__content flex items-start gap-2">
        <TypeConfigurator
          :key="formKey"
          ref="typeConfigurator"
          class="min-w-0 flex-1"
          :types="editor.payload().ruleTypes"
          :selected-type-label="payload.label"
          :form="payload.form"
          :errors="editor.errors()"
          :disabled="!editor.editable()"
          :form-disabled="switching"
          :refresh="refresh"
          @select="switchType"
          @change="change"
        />

        <craft-button
          v-if="editor.editable()"
          type="button"
          class="ms-auto self-center"
          icon="xmark"
          variant="danger-plain"
          size="small"
          :aria-label="t('Remove')"
          @click="emit('remove')"
        />
      </div>

      <craft-callout v-if="error" role="alert" variant="danger" class="mt-2">
        {{ error }}
      </craft-callout>
    </div>
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
  .condition-rule__content :deep(craft-combobox > [slot='label']) {
    position: absolute;
    width: 1px;
    height: 1px;
    overflow: hidden;
    clip-path: inset(50%);
  }
</style>
