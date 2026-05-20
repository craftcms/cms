<script setup lang="ts">
  import {t} from '@craftcms/cp';
  import {usePage} from '@inertiajs/vue3';
  import CraftSwitch from '@craftcms/cp/vue/CraftSwitch.vue';
  import CraftCombobox from '@/components/form/CraftCombobox.vue';
  import DynamicHtmlRenderer from '@/components/DynamicHtmlRenderer.vue';
  import type {Filesystem} from '@/types/filesystems';
  import VarDump from '@/components/VarDump.vue';

  const hasUrls = defineModel<boolean>('hasUrls');
  const url = defineModel<string>('url', {default: ''});
  defineProps<{
    filesystem: Filesystem;
  }>();

  const page = usePage<{
    readOnly: boolean;
    baseUrlSuggestions?: Array<any>;
  }>();
</script>

<template>
  <div v-if="filesystem" :id="filesystem.type">
    <div class="grid gap-3">
      <template v-if="filesystem.showHasUrlSetting">
        <CraftSwitch
          :label="t('Files in this filesystem have public URLs')"
          name="hasUrls"
          id="has-urls"
          v-model="hasUrls"
          :disabled="page.props.readOnly"
        />
      </template>

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
      <DynamicHtmlRenderer :html="filesystem.settingsHtml" />
    </div>
  </div>
</template>

<style scoped lang="scss"></style>
