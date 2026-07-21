<script setup lang="ts">
  import {t} from '@craftcms/ui';
  import {computed} from 'vue';
  import {useAppLayout} from '@/common/composables/useAppLayout';
  import ElementIndexPage from '@/modules/elements/components/ElementIndexPage.vue';
  import type {ElementIndexRoute} from '@/modules/elements/composables/useElementIndexVisits';
  import {usePage} from '@inertiajs/vue3';
  import {index} from '@routes/cp/assets';
  import Breadcrumbs from '@/common/components/Breadcrumbs.vue';
  import {useFolderNavigation} from '@/modules/elements/composables/useFolderNavigation';

  const page = usePage<CraftCms.Cms.Http.ViewModels.AssetIndexViewModel>();

  // Breadcrumb clicks navigate the same way folder rows do, so the current view
  // (mode, columns, sort) carries across when moving up the folder tree.
  const {navigateToFolder} = useFolderNavigation();

  // Keep the current volume/folder segment in the URL so index reloads (sort,
  // filter, pagination) stay in the same folder instead of bouncing to the root.
  const route: ElementIndexRoute = {
    url: (query = {}) =>
      index.url(
        {defaultSource: page.props.defaultSource ?? undefined},
        {query: query as Record<string, string>}
      ),
  };

  /** One step of the volume-root → current-folder chain. */
  interface SourcePathStep {
    uri: string;
    label: string;
    icon?: string | null;
  }

  // The folder trail for the breadcrumb bar: the full chain when in a subfolder,
  // otherwise just the volume root (from the source). Every ancestor links to
  // its own folder index; the current folder (last step) is plain text.
  const breadcrumbs = computed(() => {
    const steps: SourcePathStep[] =
      (page.props.defaultSourcePath as SourcePathStep[] | null) ??
      (page.props.source as {defaultSourcePath?: SourcePathStep[]} | null)
        ?.defaultSourcePath ??
      [];

    return steps.map((step, i) => ({
      label: step.label,
      icon: step.icon ?? undefined,
      url:
        i === steps.length - 1
          ? null
          : // `uri` is e.g. `assets/local/general`; the route's defaultSource is
            // the path after `assets/`.
            index.url({
              defaultSource: step.uri.replace(/^assets\/?/, '') || undefined,
            }),
    }));
  });

  useAppLayout({fullWidth: true});
</script>

<template>
  <ElementIndexPage :route="route">
    <template #navbar>
      <Breadcrumbs :items="breadcrumbs" @navigate="navigateToFolder" />
    </template>
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
