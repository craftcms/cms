<script setup lang="ts">
  import {useForm} from '@inertiajs/vue3';
  import type {Site} from '@/common/types';
  import {t} from '@craftcms/cp';
  import {store} from '@actions/Settings/SitesController';
  import SiteFields from '@/modules/sites/components/SiteFields.vue';
  import DeleteSiteModal from '@/modules/sites/components/DeleteSiteModal.vue';
  import {ref} from 'vue';
  import Badge from '@/common/components/Badge.vue';
  import {useSettingsSave} from '@/modules/settings/composables/useSettingsSave';
  import Pane from '@/common/components/Pane.vue';
  import {useAppLayout} from '@/common/composables/useAppLayout';
  import LayoutSlot from '@/common/components/LayoutSlot.vue';

  const props = defineProps<{
    title: string;
    crumbs: Array<any>; // @TODO
    site: Site;
    groupId?: number | string;
    flash?: Record<any, any>;
    errors: Record<any, any> | null;
    isMultiSite: boolean;
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

  const {save} = useSettingsSave(form, store);

  const modalActive = ref(false);

  useAppLayout(() => ({title: props.title, form, onSave: save}));
</script>

<template>
  <LayoutSlot name="title-badge">
    <Badge :variant="site.enabled ? 'success' : 'default'">
      {{ site.enabled ? t('Enabled') : t('Disabled') }}
    </Badge>
    <craft-callout v-if="site.primary" size="small" inline>
      <span>{{ t('Primary') }}</span>
    </craft-callout>
  </LayoutSlot>

  <Pane appearance="raised">
    <div class="grid gap-3">
      <SiteFields :inertia-form="form" />
    </div>
  </Pane>

  <DeleteSiteModal
    @close="modalActive = false"
    :open="modalActive"
    :site="props.site"
    v-if="!site.primary"
  />
</template>
