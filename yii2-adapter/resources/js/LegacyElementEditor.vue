<script setup lang="ts">
  import {computed} from 'vue';
  import ElementEditor from '@/modules/elements/components/ElementEditor.vue';
  import HtmlFragmentRenderer from '@/common/components/HtmlFragmentRenderer.vue';
  import {useScreenPageProps} from '@/common/composables/screen';
  import type {UiValues} from '@/modules/ui/types';
  import ElementEditorHtml from './ElementEditorHtml.vue';
  import {
    provideLegacyEditorContext,
    legacyEditorRequestValues,
    type LegacyEditorPayload,
  } from './element-editor-context';

  defineProps<{saveData?: () => UiValues}>();
  const context = provideLegacyEditorContext();
  const pageProps = useScreenPageProps();
  const payload = computed(
    () => (context.editor.value?.props ?? pageProps()) as LegacyEditorPayload
  );
  const assetsReady = computed(
    () =>
      context.contentReady.value &&
      (context.sidebarReady.value ||
        (!payload.value.sidebarUi && !payload.value.editorSidebarHtml))
  );
</script>
<template>
  <div>
    <ElementEditor
      :save-data="saveData"
      :transform="legacyEditorRequestValues"
      :form-wrapper="ElementEditorHtml"
      :show-details="Boolean(payload.editorSidebarHtml)"
    />
    <HtmlFragmentRenderer
      v-if="assetsReady && payload.editorAssets"
      :fragment="payload.editorAssets"
      hidden
    />
  </div>
</template>
