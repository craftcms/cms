<script setup lang="ts">
  import type {Site} from '@/common/types';
  import type {UrlMethodPair} from '@inertiajs/core';
  import {t, toEnvVar} from '@craftcms/ui';
  import {ref} from 'vue';
  import LayoutSlot from '@/common/components/LayoutSlot.vue';
  import UiPage from '@/pages/Ui.vue';
  import type {
    UiChange,
    UiChangeKind,
    UiPayload,
    UiValue,
  } from '@/modules/ui/types';
  import {pathsMatch} from '@/modules/ui/runtime';

  const props = defineProps<{
    site: Site;
    ui: UiPayload;
    submit: UrlMethodPair;
    refreshUrl: string;
  }>();

  const uiPage = ref<{
    setValue(path: string[], value: UiValue, kind?: UiChangeKind): void;
  }>();
  const baseUrlDirty = ref(
    Boolean(props.ui.values.siteId) || Boolean(props.ui.values.baseUrl)
  );

  function onChange(change: UiChange, values: UiPayload['values']): void {
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

    uiPage.value?.setValue(
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
    <craft-badge :fill="site.enabled ? 'success' : 'gray'">
      {{ site.enabled ? t('Enabled') : t('Disabled') }}
    </craft-badge>
    <craft-badge v-if="site.primary" no-prefix fill="accent" inline>
      <span>{{ t('Primary') }}</span>
    </craft-badge>
  </LayoutSlot>

  <UiPage
    ref="uiPage"
    :ui="ui"
    :submit="submit"
    :refresh-url="refreshUrl"
    @change="onChange"
  />
</template>
