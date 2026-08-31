<script setup lang="ts">
  import {t, appendBodyHtml, appendHeadHtml, ButtonVariant} from '@craftcms/ui';
  import {useHttp, usePage} from '@inertiajs/vue3';
  import {onMounted, onUnmounted, ref, useTemplateRef} from 'vue';
  import ElementIndexController from '@actions/Elements/ElementIndex/ElementIndexController';
  import type {SourceItem} from '@/modules/elements/types/sources';
  import {useAnnouncer} from '@/common/composables/useAnnouncer';
  import {onKeyStroke} from '@vueuse/core';
  import {
    conditionsFromForm,
    type ConditionConfig,
  } from '@/modules/elements/composables/useConditionBuilder';

  type FilterHudResponse = {
    hudHtml: string;
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
    conditionConfig?: {
      class: string;
      conditionRules?: Array<{class: string}>;
    };
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

  const http = useHttp<FilterHudRequest, FilterHudResponse>({
    elementType: page.props.elementType,
    context: page.props.context ?? 'index',
    source: page.props.source,
    id: `filters`,
    // Re-render the builder with the currently-applied rules, so reopening
    // the HUD picks up where the last Apply left off.
    conditionConfig: conditions.value ?? undefined,
  });

  const hudForm = useTemplateRef<HTMLFormElement>('hudForm');
  const {announce} = useAnnouncer();

  const hudHtml = ref<string | null>();
  onKeyStroke('Escape', () => {
    emit('close');
  });

  onMounted(() => {
    http.post(ElementIndexController.filterHud().url, {
      onSuccess: async (data) => {
        announce(t('Loading complete'));
        hudHtml.value = data?.hudHtml;

        if (data.headHtml) {
          await appendHeadHtml(data.headHtml);
        }

        if (data.bodyHtml) {
          await appendBodyHtml(data.bodyHtml);
        }
      },
    });
  });

  onUnmounted(() => {
    hudHtml.value = null;
  });

  function handleSubmit() {
    if (!hudHtml.value || !hudForm.value) {
      return;
    }

    conditions.value = conditionsFromForm(hudForm.value);
    emit('apply');
    emit('close');
  }
</script>

<template>
  <div class="absolute w-full z-10" style="inset-block-start: calc(100% + 4px)">
    <div
      class="bg-white p-4 border-neutral-border-quiet shadow-lg rounded w-full min-h-20"
    >
      <!-- .stop keeps the submit from bubbling to the toolbar's own form,
        which would trigger a second index submit alongside @apply -->
      <form @submit.prevent.stop="handleSubmit" ref="hudForm">
        <craft-spinner v-if="http.processing"></craft-spinner>
        <template v-else-if="hudHtml">
          <div v-html="hudHtml" />
          <div class="mt-4 flex justify-end gap-2">
            <craft-button
              type="button"
              :variant="ButtonVariant.Fill"
              @click="() => emit('close')"
              >{{ t('Cancel') }}</craft-button
            >
            <craft-button type="submit" :variant="ButtonVariant.Primary">{{
              t('Apply')
            }}</craft-button>
          </div>
        </template>
      </form>
    </div>
  </div>
</template>

<style scoped lang="scss"></style>
