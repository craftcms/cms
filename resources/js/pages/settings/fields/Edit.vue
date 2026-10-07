<script setup lang="ts">
  import {t} from '@craftcms/ui';
  import type {UrlMethodPair} from '@inertiajs/core';
  import {computed, ref} from 'vue';
  import MetadataDetails from '@/common/components/MetadataDetails.vue';
  import type {FormAction, FormSaveOptions} from '@/common/types';
  import {pathsMatch} from '@/modules/ui/runtime';
  import type {UiChange, UiPayload, UiValue} from '@/modules/ui/types';
  import UiPage from '@/pages/Ui.vue';

  const props = defineProps<{
    ui: UiPayload;
    submit: UrlMethodPair;
    refreshUrl: string | null;
    supportedTranslationMethods: Record<string, string[]>;
    formActions?: FormAction[];
    /** The field's ID and usages, from `CpScreenResponse::metaSidebarHtml()`. */
    details?: string | null;
  }>();

  const formPage = ref<{
    save(options?: FormSaveOptions): void;
    setValue(path: string[], value: UiValue, kind?: UiChange['kind']): void;
  }>();
  const formActions = computed<FormAction[]>(() => [
    {
      label: t('Save and add another'),
      onClick: () =>
        formPage.value?.save({data: {addAnother: 1}, preserveState: false}),
    },
    ...(props.formActions ?? []),
  ]);

  function onChange(change: UiChange, values: UiPayload['values']): void {
    if (!pathsMatch(change.path, ['type'])) {
      return;
    }

    const supported =
      props.supportedTranslationMethods[String(values.type)] ?? [];

    if (!supported.includes(String(values.translationMethod))) {
      formPage.value?.setValue(
        ['translationMethod'],
        supported[0] ?? 'none',
        change.kind
      );
    }
  }
</script>

<template>
  <MetadataDetails :html="details" />

  <UiPage
    ref="formPage"
    :ui="ui"
    :submit="submit"
    :form-actions="formActions"
    :refresh-url="refreshUrl ?? undefined"
    @change="onChange"
  />
</template>
