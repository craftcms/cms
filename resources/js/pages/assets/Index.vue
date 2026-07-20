<script setup lang="ts">
  import {t} from '@craftcms/ui';
  import {useAppLayout} from '@/common/composables/useAppLayout';
  import ElementIndexPage from '@/modules/elements/components/ElementIndexPage.vue';
  import type {ElementIndexRoute} from '@/modules/elements/composables/useElementIndexVisits';
  import {usePage} from '@inertiajs/vue3';
  import {index} from '@routes/cp/assets';

  const page = usePage<CraftCms.Cms.Http.ViewModels.AssetIndexViewModel>();

  // Keep the current volume/folder segment in the URL so index reloads (sort,
  // filter, pagination) stay in the same folder instead of bouncing to the root.
  const route: ElementIndexRoute = {
    url: (query = {}) =>
      index.url(
        {defaultSource: page.props.defaultSource ?? undefined},
        {query: query as Record<string, string>}
      ),
  };

  useAppLayout({fullWidth: true});
</script>

<template>
  <ElementIndexPage :route="route">
    <template #actions>
      <form action="">
        <craft-button icon="upload">
          {{ t('Upload files') }}
        </craft-button>
      </form>
    </template>
  </ElementIndexPage>
</template>

<style scoped lang="scss"></style>
