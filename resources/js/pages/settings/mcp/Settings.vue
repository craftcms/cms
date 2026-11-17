<script setup lang="ts">
  import type {UrlMethodPair} from '@inertiajs/core';
  import {ref} from 'vue';
  import UiPage from '@/pages/Ui.vue';
  import type {
    UiChange,
    UiChangeKind,
    UiPayload,
    UiValue,
  } from '@/modules/ui/types';
  import {pathsMatch} from '@/modules/ui/runtime';

  const props = defineProps<{
    ui: UiPayload;
    submit: UrlMethodPair;
    refreshUrl: string | null;
  }>();

  const uiPage = ref<{
    setValue(path: string[], value: UiValue, kind?: UiChangeKind): void;
  }>();

  // While the authorization endpoint starts with the endpoint, keep the rest of it after the endpoint.
  let suffix = suffixFor(props.ui.values);

  function suffixFor(values: UiPayload['values']): string | null {
    const route = String(values.route ?? '');
    const authorizationRoute = String(values.authorizationRoute ?? '');

    return route !== '' && authorizationRoute.startsWith(route)
      ? authorizationRoute.slice(route.length)
      : null;
  }

  function onChange(change: UiChange, values: UiPayload['values']): void {
    if (pathsMatch(change.path, ['authorizationRoute'])) {
      // Ignore the change made by syncing, which would end syncing whenever the endpoint is empty.
      if (
        suffix === null ||
        values.authorizationRoute !== `${String(values.route ?? '')}${suffix}`
      ) {
        suffix = suffixFor(values);
      }

      return;
    }

    if (pathsMatch(change.path, ['route']) && suffix !== null) {
      uiPage.value?.setValue(
        ['authorizationRoute'],
        `${String(values.route ?? '')}${suffix}`,
        change.kind
      );
    }
  }
</script>

<template>
  <UiPage
    ref="uiPage"
    :ui="ui"
    :submit="submit"
    :refresh-url="refreshUrl ?? undefined"
    @change="onChange"
  />
</template>
