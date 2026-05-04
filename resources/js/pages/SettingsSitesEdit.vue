<script setup lang="ts">
  import AppLayout from '@/layout/AppLayout.vue';
  import {useForm} from '@inertiajs/vue3';
  import type {Site} from '@/types';
  import {t} from '@craftcms/cp';
  import {store} from '@actions/Settings/SitesController';
  import SiteFields from '@/components/sites/SiteFields.vue';
  import DeleteSiteModal from '@/components/sites/DeleteSiteModal.vue';
  import {ref} from 'vue';
  import Badge from '@/components/Badge.vue';
  import {useSettingsSave} from '@/composables/useSettingsSave';
  import Pane from '@/components/Pane.vue';
  import useCraftData from '@/composables/useCraftData';
  import {useEventListener} from '@vueuse/core';

  const props = defineProps<{
    title: string;
    crumbs: Array<any>; // @TODO
    site: Site;
    groupId?: number | string;
    flash?: Record<any, any>;
    errors: Record<any, any> | null;
    isMultisite: boolean;
  }>();

  const form = useForm({
    siteId: props.site.id ?? null,
    group: props.groupId,
    name: props.site.nameRaw,
    handle: props.site.handle,
    language: props.site.languageRaw,
    enabled: props.site.enabledRaw,
    hasUrls: props.site.hasUrls,
    primary: props.site.primary,
    baseUrl: props.site.baseUrlRaw ?? '',
  });

  const {readOnly} = useCraftData();

  // Handle cmd + s events
  useEventListener('keydown', (event) => {
    if ((event.metaKey || event.ctrlKey) && event.key === 's') {
      event.preventDefault();
      save();
    }
  });

  const {save} = useSettingsSave(form, store);

  const modalActive = ref(false);
</script>

<template>
  <AppLayout :title="title" :form="form" @save="save">
    <template #title-badge>
      <Badge :variant="site.enabled ? 'success' : 'default'">
        {{ site.enabled ? t('Enabled') : t('Disabled') }}
      </Badge>
      <craft-callout v-if="site.primary" size="small" inline>
        <span>{{ t('Primary') }}</span>
      </craft-callout>
    </template>

    <Pane appearance="raised">
      <div class="grid gap-3">
        <SiteFields :inertia-form="form" />
      </div>
    </Pane>
  </AppLayout>

  <DeleteSiteModal
    @close="modalActive = false"
    :open="modalActive"
    :site="props.site"
    v-if="!site.primary"
  />
</template>

<style scoped lang="scss"></style>
