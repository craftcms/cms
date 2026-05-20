<script setup lang="ts">
  import {t} from '@craftcms/cp';
  import {computed} from 'vue';
  import {usePage} from '@inertiajs/vue3';
  import type {Filesystem} from '@/pages/SettingsFilesystemsEditPage.vue';
  import VarDump from '@/components/VarDump.vue';
  import CraftSwitch from '@craftcms/cp/vue/CraftSwitch.vue';
  import CraftCombobox from '@/components/form/CraftCombobox.vue';
  import DynamicHtmlRenderer from '@/components/DynamicHtmlRenderer.vue';

  const hasUrls = defineModel<boolean>('hasUrls');
  const url = defineModel<string>('url');
  withDefaults(
    defineProps<{
      filesystem?: Filesystem;
    }>(),
    {
      filesystem: {
        errors: () => [],
        name: null,
        handle: null,
        oldHandle: null,
        hasUrls: false,
        url: null,
        uid: null,
        rootUrl: null,
        id: null,
        dateCreated: null,
        dateUpdated: null,
        settingsHtml: null,
        rootPath: null,
        path: null,
      },
    }
  );

  const page = usePage<{
    readOnly: boolean;
    baseUrlSuggestions?: Array<any>;
  }>();
</script>

<template>
  <div v-if="filesystem" :id="filesystem.type">
    <template v-if="filesystem.showHasUrlSetting">
      <CraftSwitch
        :label="t('Files in this filesystem have public URLs')"
        name="hasUrls"
        id="has-urls"
        v-model="hasUrls"
        :disabled="page.props.readOnly"
      />

      <template v-if="hasUrls && filesystem.showUrlSetting">
        <CraftCombobox
          :label="t('Base URL')"
          :help-text="t('The base URL to the files in this filesystem.')"
          v-model="url"
          :options="page.props.baseUrlSuggestions"
          name="url"
          :required="true"
          placeholder="//example.com/path/to/folder"
          data-error-key="url"
          :disabled="page.props.readOnly"
        ></CraftCombobox>
      </template>
    </template>
    <DynamicHtmlRenderer :html="filesystem.settingsHtml"/>
    <!--<div v-html="filesystem.settingsHtml" />-->
  </div>
</template>

<style scoped lang="scss"></style>
