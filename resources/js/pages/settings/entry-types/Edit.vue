<script setup lang="ts">
  import MetadataDetails from '@/common/components/MetadataDetails.vue';
  import type {FormAction, FormSaveOptions} from '@/common/types';
  import type {UiPayload} from '@/modules/ui/types';
  import UiPage from '@/pages/Ui.vue';
  import {t} from '@craftcms/ui';
  import type {UrlMethodPair} from '@inertiajs/core';
  import {computed, ref} from 'vue';

  const props = defineProps<{
    ui: UiPayload;
    submit: UrlMethodPair;
    refreshUrl: string | null;
    brandNew: boolean;
    lowerTypeName: string;
    metadataHtml: string | null;
    formActions?: FormAction[];
  }>();

  const formPage = ref<{
    save(options?: FormSaveOptions): void;
  }>();
  const formActions = computed<FormAction[]>(() => [
    ...(!props.brandNew
      ? [
          {
            label: t('Save as a new {type}', {type: props.lowerTypeName}),
            onClick: () =>
              formPage.value?.save({
                data: {saveAsNew: true},
                preserveState: false,
              }),
          },
        ]
      : []),
    ...(props.formActions ?? []),
  ]);
</script>

<template>
  <MetadataDetails :html="metadataHtml" />

  <UiPage
    ref="formPage"
    :ui="ui"
    :submit="submit"
    :form-actions="formActions"
    :refresh-url="refreshUrl ?? undefined"
  />
</template>
