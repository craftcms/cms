<script setup lang="ts">
  import type {UrlMethodPair} from '@inertiajs/core';
  import {toUriFormat} from '@craftcms/ui';
  import {ref} from 'vue';
  import type {SectionSiteSettingsData} from '@/common/types';
  import type {
    FormChange,
    FormChangeKind,
    FormPayload,
    FormValue,
  } from '@/modules/forms/types';
  import {isRecord, pathsMatch} from '@/modules/forms/runtime';
  import PreviewTargetsTable from '@/modules/sections/components/PreviewTargetsTable.vue';
  import SiteSettingsTable from '@/modules/sections/components/SiteSettingsTable.vue';
  import FormPage from '@/pages/Form.vue';

  type SiteSettings = Record<string, Omit<SectionSiteSettingsData, 'handle'>>;
  type PreviewTarget = {
    label: string;
    urlFormat: string;
    refresh: boolean;
  };

  const props = defineProps<{
    form: FormPayload;
    submit: UrlMethodPair;
    refreshUrl: string | null;
    brandNew: boolean;
    homepageUri: string;
    templateOptions: Array<unknown>;
    isMultiSite: boolean;
    headlessMode: boolean;
  }>();

  const formPage = ref<{
    setValue(path: string[], value: FormValue, kind?: FormChangeKind): void;
  }>();

  function siteSettings(value: FormValue): SiteSettings {
    if (!isRecord(value)) {
      return {};
    }
    // SAFETY: the section form's `sites` control emits SectionSiteSettingsData rows.
    return value as SiteSettings;
  }

  function previewTargets(value: FormValue): PreviewTarget[] {
    if (!Array.isArray(value)) {
      return [];
    }
    // SAFETY: the preview-targets control owns this array and emits PreviewTarget rows.
    return value as PreviewTarget[];
  }

  function onChange(change: FormChange, values: FormPayload['values']): void {
    if (!props.brandNew || !pathsMatch(change.path, ['name'])) {
      return;
    }

    const sites = siteSettings(values.sites);
    const uri = toUriFormat(String(values.name ?? ''));
    const generated = Object.fromEntries(
      Object.entries(sites).map(([handle, site]) => [
        handle,
        {
          ...site,
          singleUri: uri && !site.singleHomepage ? uri : (site.singleUri ?? ''),
          uriFormat: uri ? `${uri}/{slug}` : '',
          template: uri ? `${uri}/_entry.twig` : '',
        },
      ])
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
  >
    <template #sites="{value, values, setValue, editable}">
      <SiteSettingsTable
        :is-multisite="isMultiSite"
        :is-headless="headlessMode"
        :selected-type="String(values.type ?? '')"
        :model-value="siteSettings(value)"
        :disabled="!editable"
        @update:model-value="setValue($event, 'typing')"
      />
    </template>

    <template #previewTargets="{value, setValue, editable}">
      <PreviewTargetsTable
        :model-value="previewTargets(value)"
        :disabled="!editable"
        @update:model-value="setValue($event, 'typing')"
      />
    </template>
  </FormPage>
</template>
