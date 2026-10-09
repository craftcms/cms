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
  import TypeConfigurator from '@/modules/ui/TypeConfigurator.vue';
  import {valueAt} from '@/modules/ui/runtime';
  import type {UiChange, UiPayload, UiValues} from '@/modules/ui/types';
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

  const uiKey = ref(0);
  const typeConfigurator = useTemplateRef('typeConfigurator');
  const latestUiPayload = shallowRef(payload.value.ui);

  watch(
    () => payload.value.ui,
    (payload) => (latestUiPayload.value = payload)
  );

  editor.registerRule(props.rule.id, {
    snapshot: () => ({
      ...payload.value,
      ui: {
        ...latestUiPayload.value,
        values:
          typeConfigurator.value?.currentValues() ??
          latestUiPayload.value.values,
      },
    }),
    canSubmit: () => typeConfigurator.value?.canSubmit() ?? true,
  });

  onBeforeUnmount(() => {
    editor.registerRule(props.rule.id);
  });

  function change(_change: UiChange, values: UiValues): void {
    const inputs = valueAt(values, payload.value.ui.scope);

    editor.rules[props.rule.id] = {
      ...payload.value,
      config: {
        ...payload.value.config,
        ...(inputs as UiValues),
      },
    };

    editor.changed();
  }

  async function refresh(values: UiValues): Promise<UiPayload> {
    changingType.value = false;

    const refreshed = await execute({...payload.value.config, ...values});

    if (!refreshed) throw new Error('Condition rule refresh did not complete.');

    latestUiPayload.value = refreshed.ui;

    return refreshed.ui;
  }

  async function switchType(type: string): Promise<void> {
    changingType.value = true;

    const refreshed = await execute({...payload.value.config, type});

    if (!refreshed) return;

    editor.rules[props.rule.id] = refreshed;
    latestUiPayload.value = refreshed.ui;
    uiKey.value++;

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
          :key="uiKey"
          ref="typeConfigurator"
          class="min-w-0 flex-1"
          :types="editor.payload().ruleTypes"
          :selected-type-label="payload.label"
          :ui="payload.ui"
          :errors="editor.errors()"
          :disabled="!editor.editable()"
          :ui-disabled="switching"
          :refresh="refresh"
          @select="switchType"
          @change="change"
        />

        <craft-button
          v-if="editor.editable()"
          type="button"
          class="ms-auto self-center"
          icon="xmark-large"
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
