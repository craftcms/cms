<script setup lang="ts">
  import type {Site} from '@/common/types';
  import type {UrlMethodPair} from '@inertiajs/core';
  import {t, toEnvVar} from '@craftcms/ui';
  import {ref} from 'vue';
  import Badge from '@/common/components/Badge.vue';
  import LayoutSlot from '@/common/components/LayoutSlot.vue';
  import FormPage from '@/pages/Form.vue';
  import type {
    FormChange,
    FormChangeKind,
    FormPayload,
    FormValue,
  } from '@/modules/forms/types';
  import {pathsMatch} from '@/modules/forms/runtime';

  const props = defineProps<{
    site: Site;
    form: FormPayload;
    submit: UrlMethodPair;
    refreshUrl: string;
  }>();

  const formPage = ref<{
    setValue(path: string[], value: FormValue, kind?: FormChangeKind): void;
  }>();
  const baseUrlDirty = ref(
    Boolean(props.form.values.siteId) || Boolean(props.form.values.baseUrl)
  );

  function onChange(change: FormChange, values: FormPayload['values']): void {
    if (pathsMatch(change.path, ['baseUrl'])) {
      baseUrlDirty.value = true;

      return;
    }

    if (baseUrlDirty.value || !values.hasUrls) {
      return;
    }

    if (
      !pathsMatch(change.path, ['name']) &&
      !pathsMatch(change.path, ['hasUrls'])
    ) {
      return;
    }

    formPage.value?.setValue(
      ['baseUrl'],
      toEnvVar(String(values.name ?? ''), {
        prefix: '$',
        suffix: '_URL',
      }),
      change.kind
    );
  }
</script>

<template>
  <LayoutSlot name="content-toolbar-meta">
    <craft-badge :fill="site.enabled ? 'success' : 'default'">
      {{ site.enabled ? t('Enabled') : t('Disabled') }}
    </craft-badge>
    <craft-badge v-if="site.primary" no-prefix fill="accent" inline>
      <span>{{ t('Primary') }}</span>
    </craft-badge>
  </LayoutSlot>

  <FormPage
    ref="formPage"
    :form="form"
    :submit="submit"
    :refresh-url="refreshUrl"
    @change="onChange"
  />
</template>
