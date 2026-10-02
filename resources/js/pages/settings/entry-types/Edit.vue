<script setup lang="ts">
  import MetadataDetails from '@/common/components/MetadataDetails.vue';
  import type {FormAction, FormSaveOptions} from '@/common/types';
  import type {FormPayload} from '@/modules/forms/types';
  import FormPage from '@/pages/Form.vue';
  import {t} from '@craftcms/ui';
  import type {UrlMethodPair} from '@inertiajs/core';
  import {computed, ref} from 'vue';

  const props = defineProps<{
    form: FormPayload;
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

  <FormPage
    ref="formPage"
    :form="form"
    :submit="submit"
    :form-actions="formActions"
    :refresh-url="refreshUrl ?? undefined"
  />
</template>
