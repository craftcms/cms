<script setup lang="ts">
  /**
   * A server-built form, for items whose view is `{type: 'ui', modalUrl,
   * actionUrl}` — the same pair `openUiModal()` takes.
   */
  import {ButtonVariant, t} from '@craftcms/ui';
  import {computed, onMounted, useId, useTemplateRef} from 'vue';
  import UiRenderer from '@/modules/ui/UiRenderer.vue';
  import {useUiForm} from '@/modules/ui/useUiForm';
  import type {UiValues} from '@/modules/ui/types';
  import type {
    CommandPaletteViewProps,
    CommandPaletteViewResult,
  } from '../types';

  const props = defineProps<CommandPaletteViewProps>();

  const emit = defineEmits<{
    (event: 'done', result?: CommandPaletteViewResult): void;
    (event: 'back'): void;
  }>();

  const {form, errors, loading, load, submit} = useUiForm({
    modalUrl: () => String(props.view.modalUrl),
    actionUrl: () => String(props.view.actionUrl),
    params: () => props.view.params as UiValues | undefined,
  });
  const renderer = useTemplateRef<InstanceType<typeof UiRenderer>>('renderer');
  const submitId = useId();
  const disabledReason = computed(() =>
    form.value?.submitDisabled ? form.value.submitDisabledReason : null
  );
  const body = useTemplateRef<HTMLElement>('body');

  onMounted(async () => {
    if (!(await load())) {
      emit('back');
    }
  });

  async function save(): Promise<void> {
    const data = await submit(renderer.value, body.value);

    if (data !== null) {
      emit('done', {
        message: typeof data.message === 'string' ? data.message : undefined,
        redirect: typeof data.redirect === 'string' ? data.redirect : undefined,
        reload: props.item.reload,
      });
    }
  }
</script>

<template>
  <form class="flex flex-col gap-lg" novalidate @submit.prevent="save">
    <div v-if="!form" class="flex justify-center p-lg">
      <craft-spinner :label="t('Loading')"></craft-spinner>
    </div>
    <craft-field-group v-else ref="body">
      <UiRenderer ref="renderer" :payload="form.ui" :errors="errors" />
    </craft-field-group>
    <div class="flex flex-wrap justify-end gap-sm">
      <craft-button type="button" @click="emit('back')">
        {{ t('Cancel') }}
      </craft-button>
      <craft-button
        :id="submitId"
        type="submit"
        :variant="ButtonVariant.Primary"
        .loading="loading"
        .disabled="!form || form.submitDisabled"
        :focusable-when-disabled="disabledReason ? true : undefined"
      >
        {{ form?.submitLabel ?? t('Save') }}
      </craft-button>
      <craft-tooltip v-if="disabledReason" :for="submitId">
        {{ disabledReason }}
      </craft-tooltip>
    </div>
  </form>
</template>

<style scoped>
  /* Keeps tables, such as a release's drafts, in line with the view's heading above. */
  :deep(.cp-table tr > :is(th, td)) {
    &:first-child {
      padding-inline-start: 0;
    }

    &:last-child {
      padding-inline-end: 0;
    }
  }

  /* Fits the header row to its labels, so they sit as far below the view's heading as they do from its sides. */
  :deep(.cp-table thead th) {
    height: auto;
    padding-block-start: 0;
  }
</style>
