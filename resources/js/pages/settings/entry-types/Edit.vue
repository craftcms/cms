<script setup lang="ts">
  import DynamicHtmlRenderer from '@/common/components/DynamicHtmlRenderer.vue';
  import LayoutSlot from '@/common/components/LayoutSlot.vue';
  import type {ActionItem, FormSaveOptions} from '@/common/types';
  import {useAppLayout} from '@/common/composables/useAppLayout';
  import type {FormPayload} from '@/modules/forms/types';
  import FormPage from '@/pages/Form.vue';
  import {t} from '@craftcms/ui';
  import type {UrlMethodPair} from '@inertiajs/core';
  import {ref} from 'vue';

  const props = defineProps<{
    form: FormPayload;
    submit: UrlMethodPair;
    refreshUrl: string | null;
    brandNew: boolean;
    lowerTypeName: string;
    metadataHtml: string | null;
    formActions?: ActionItem[];
  }>();

  const formPage = ref<{
    save(options?: FormSaveOptions): void;
  }>();
  const formActions: ActionItem[] = [
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
  ];

  useAppLayout({formActions});
</script>

<template>
  <LayoutSlot v-if="metadataHtml" name="details">
    <DynamicHtmlRenderer :html="metadataHtml" />
  </LayoutSlot>

  <FormPage
    ref="formPage"
    :form="form"
    :submit="submit"
    :refresh-url="refreshUrl ?? undefined"
  />
</template>
