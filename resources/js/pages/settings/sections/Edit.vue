<script setup lang="ts">
  import type {UrlMethodPair} from '@inertiajs/core';
  import {toUriFormat} from '@craftcms/ui';
  import {ref} from 'vue';
  import type {
    UiChange,
    UiChangeKind,
    UiPayload,
    UiValue,
  } from '@/modules/ui/types';
  import {isRecord, pathsMatch} from '@/modules/ui/runtime';
  import FormPage from '@/pages/Ui.vue';

  const props = defineProps<{
    form: UiPayload;
    submit: UrlMethodPair;
    refreshUrl: string | null;
    brandNew: boolean;
  }>();

  const formPage = ref<{
    setValue(path: string[], value: UiValue, kind?: UiChangeKind): void;
  }>();

  // A refresh never overwrites values the user can edit, so deriving the site
  // URIs from the name has to happen here.
  function onChange(change: UiChange, values: UiPayload['values']): void {
    if (
      !props.brandNew ||
      !pathsMatch(change.path, ['name']) ||
      !isRecord(values.sites)
    ) {
      return;
    }

    const uri = toUriFormat(String(values.name ?? ''));
    const generated = Object.fromEntries(
      Object.entries(values.sites).map(([handle, site]) => {
        const row = isRecord(site) ? site : {};

        return [
          handle,
          {
            ...row,
            singleUri: uri,
            uriFormat: uri ? `${uri}/{slug}` : '',
            routeType: 'template',
            route: uri ? `${uri}/_entry.twig` : '',
          },
        ];
      })
    );

    formPage.value?.setValue(['sites'], generated, change.kind);
  }
</script>

<template>
  <FormPage
    ref="formPage"
    :form="form"
    :submit="submit"
    :refresh-url="refreshUrl ?? undefined"
    @change="onChange"
  />
</template>
