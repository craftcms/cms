<script setup lang="ts">
  import {t, appendBodyHtml, appendHeadHtml, ButtonVariant} from '@craftcms/ui';
  import {useHttp, usePage} from '@inertiajs/vue3';
  import {inject, onMounted, ref, shallowRef} from 'vue';
  import ElementIndexController from '@actions/Elements/ElementIndex/ElementIndexController';
  import type {SourceItem} from '@/modules/elements/types/sources';
  import {useAnnouncer} from '@/common/composables/useAnnouncer';
  import ConditionBuilder from '@/modules/conditions/ConditionBuilder.vue';
  import type {
    BuilderPayload,
    ConditionConfig,
  } from '@/modules/conditions/types';
  import {elementIndexContextKey} from '@/modules/elements/index/index-context';

  type FilterHudResponse = {
    builder: BuilderPayload;
    headHtml: string;
    bodyHtml: string;
  };

  type FilterHudRequest = {
    elementType: string;
    context: string;
    source: {
      type: 'native' | 'custom';
      key: string;
      label: string;
    };
    id: string;
  };

  const emit = defineEmits<{
    (e: 'close'): void;
    (e: 'apply'): void;
  }>();

  const conditions = defineModel<ConditionConfig | null>();
  const page = usePage<{
    elementType: string;
    context?: string;
    source: SourceItem;
    id: string;
  }>();
  const providedContext = inject(elementIndexContextKey);

  /** Read when the request is sent, so it reflects the index's current context. */
  function requestData(): FilterHudRequest {
    const context = providedContext?.value;

    return {
      ...context?.extraParams,
      elementType: context?.elementType ?? page.props.elementType,
      context: context?.context ?? page.props.context ?? 'index',
      source: context?.source ?? page.props.source,
      id: `filters`,
    } as FilterHudRequest;
  }

  const http = useHttp<FilterHudRequest, FilterHudResponse>(requestData());

  http.transform(() => ({
    ...requestData(),
    fieldLayouts: providedContext?.value?.fieldLayouts,
    conditionConfig: conditions.value ?? undefined,
  }));

  const {announce} = useAnnouncer();

  const builder = shallowRef<BuilderPayload>();
  const editor = ref<InstanceType<typeof ConditionBuilder>>();
  const draft = shallowRef<ConditionConfig>();
  const valid = ref(true);

  onMounted(() => {
    http.post(ElementIndexController.filterHud().url, {
      onSuccess: async (data) => {
        announce(t('Loading complete'));
        builder.value = data.builder;
        draft.value = data.builder.value;

        if (data.headHtml) {
          await appendHeadHtml(data.headHtml);
        }

        if (data.bodyHtml) {
          await appendBodyHtml(data.bodyHtml);
        }
      },
    });
  });

  function clearOrClose(): void {
    if (conditions.value) {
      conditions.value = null;
      emit('apply');
    }

    emit('close');
  }

  async function handleSubmit() {
    if (!draft.value || !valid.value) return;

    if (!(await editor.value?.validate())) return;

    const rules = draft.value.conditionRules;
    conditions.value = (
      Array.isArray(rules) ? rules.length : rules?.rules.length
    )
      ? draft.value
      : null;

    emit('apply');
    emit('close');
  }
</script>

<template>
  <form class="filter-hud" @submit.prevent.stop="handleSubmit">
    <craft-pane appearance="plain" padding="lg" class="w-full min-h-20">
      <craft-spinner v-if="http.processing"></craft-spinner>
      <template v-else-if="builder">
        <ConditionBuilder
          ref="editor"
          slot="body"
          class="filter-condition-builder"
          :payload="builder"
          @change="draft = $event"
          @valid="valid = $event"
        />

        <craft-button
          slot="secondary-action"
          type="button"
          :variant="ButtonVariant.Fill"
          @click="clearOrClose"
          >{{ conditions ? t('Clear') : t('Cancel') }}</craft-button
        >

        <craft-button
          slot="primary-action"
          type="submit"
          .disabled="!valid"
          :variant="ButtonVariant.Primary"
          >{{ t('Apply') }}</craft-button
        >
      </template>
    </craft-pane>
  </form>
</template>

<style scoped lang="scss">
  .filter-hud > craft-pane {
    --c-pane-border-width: 0;
  }

  .filter-condition-builder > :deep(div > .condition-group::part(base)) {
    border: 0;
    border-radius: 0;
  }
</style>
