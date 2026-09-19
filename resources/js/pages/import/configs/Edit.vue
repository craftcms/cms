<script setup lang="ts">
  import type {UrlMethodPair} from '@inertiajs/core';
  import FormPage from '@/pages/Form.vue';
  import MappingSection from './MappingSection.vue';
  import type {FormPayload} from '@/modules/forms/types';
  import type {
    MappingColEntry,
    MappingValues,
    SourceDataCol,
  } from '@/modules/import/mapping/types';

  defineProps<{
    form: FormPayload;
    submit: UrlMethodPair;
    refreshUrl: string | null;
    mapping: {
      config: {
        uid: string;
        handle: string | null;
        name: string | null;
        file: string | null;
      };
      destinationCols: MappingColEntry[];
      sourceDataCols: SourceDataCol[] | null;
      values: MappingValues;
      submit: UrlMethodPair;
      nestedColsUrl: string;
      readOnly: boolean;
      canSave: boolean;
    } | null;
  }>();
</script>

<template>
  <div class="grid gap-6">
    <FormPage
      :form="form"
      :submit="submit"
      :refresh-url="refreshUrl ?? undefined"
    />

    <MappingSection v-if="mapping" v-bind="mapping" />
  </div>
</template>
