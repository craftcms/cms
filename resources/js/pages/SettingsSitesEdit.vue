<script setup lang="ts">
  import AppLayout from '@/layout/AppLayout.vue';
  import {useForm} from '@inertiajs/vue3';
  import type {Site} from '@/types';
  import CalloutReadOnly from '@/components/CalloutReadOnly.vue';
  import {t} from '@craftcms/cp';
  import TransitionFade from '@/components/TransitionFade.vue';
  import {store} from '@actions/Settings/SitesController';
  import {useEventListener} from '@vueuse/core';
  import SiteFields from '@/components/sites/SiteFields.vue';
  import DeleteSiteModal from '@/components/sites/DeleteSiteModal.vue';
  import {ref} from 'vue';
  import Badge from '@/components/Badge.vue';

  const props = defineProps<{
    title: string;
    crumbs: Array<any>; // @TODO
    readOnly?: boolean;
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

  // Handle cmd + s events
  useEventListener('keydown', (event) => {
    if ((event.metaKey || event.ctrlKey) && event.key === 's') {
      event.preventDefault();
      save();
    }
  });

  function save() {
    form.clearErrors().submit(store());
  }

  const modalActive = ref(false);
</script>

<template>
  <form @submit.prevent="save">
    <AppLayout :title="title" :debug="$props">
      <template #title-badge>
        <Badge :variant="site.enabled ? 'success' : 'default'">
          {{ site.enabled ? t('Enabled') : t('Disabled') }}
        </Badge>
        <craft-callout v-if="site.primary" size="small" inline>
          <span>{{ t('Primary') }}</span>
        </craft-callout>
      </template>

      <template #actions>
        <TransitionFade>
          <template v-if="form.recentlySuccessful && flash?.success">
            <div class="flex gap-1 items-center text-sm">
              <craft-icon
                name="circle-check"
                style="color: var(--c-color-success-bg-emphasis)"
              ></craft-icon>
              {{ flash.success }}
            </div>
          </template>
          <template v-if="form.hasErrors">
            <div class="tw:flex tw:gap-1 tw:items-center tw:text-sm">
              <craft-icon
                name="triangle-exclamation"
                style="color: var(--c-color-danger-bg-emphasis)"
              ></craft-icon>
              {{ t('Could not save settings') }}
            </div>
          </template>
        </TransitionFade>

        <craft-button-group v-if="!readOnly">
          <craft-button
            type="submit"
            variant="primary"
            :loading="form.processing"
          >
            {{ t('Save') }}
          </craft-button>
          <craft-action-menu>
            <craft-button slot="invoker" variant="primary" type="button" icon>
              <craft-icon name="chevron-down"></craft-icon>
            </craft-button>

            <div slot="content">
              <craft-action-item @click="save">
                {{ t('Save and continue editing') }}
                <craft-shortcut slot="suffix" class="ml-2">S</craft-shortcut>
              </craft-action-item>

              <template v-if="site.id && !site.primary">
                <hr />
                <craft-action-item @click="modalActive = true" variant="danger">
                  {{ t('Delete site') }}
                </craft-action-item>
              </template>
            </div>
          </craft-action-menu>
        </craft-button-group>
      </template>
      <div
        class="bg-white border border-neutral-border-quiet rounded-sm shadow-sm"
      >
        <template v-if="readOnly">
          <CalloutReadOnly />
        </template>

        <div class="grid gap-3 p-5">
          <SiteFields :inertia-form="form" :read-only="readOnly" />
        </div>
      </div>
    </AppLayout>
  </form>

  <DeleteSiteModal
    @close="modalActive = false"
    :open="modalActive"
    :site="props.site"
    v-if="!site.primary"
  />
</template>

<style scoped lang="scss"></style>
