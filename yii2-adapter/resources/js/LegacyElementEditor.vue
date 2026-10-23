<script setup lang="ts">
  import {computed, shallowRef} from 'vue';
  import ElementEditor from '@/modules/elements/components/ElementEditor.vue';
  import HtmlFragmentRenderer from '@/common/components/HtmlFragmentRenderer.vue';
  import LayoutSlot from '@/common/components/LayoutSlot.vue';
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
  // The assets may bind to the buttons, so they wait for them too.
  const showButtons = computed(() =>
    Boolean(payload.value.editorAdditionalButtonsHtml)
  );
  const buttonsReady = shallowRef(false);
  const assetsReady = computed(
    () =>
      context.contentReady.value &&
      (context.sidebarReady.value ||
        (!payload.value.sidebarUi && !payload.value.editorSidebarHtml)) &&
      (buttonsReady.value || !showButtons.value)
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
    <LayoutSlot v-if="showButtons" name="additional-buttons">
      <HtmlFragmentRenderer
        :fragment="{
          html: payload.editorAdditionalButtonsHtml ?? '',
          headHtml: '',
          bodyHtml: '',
        }"
        class="flex items-center gap-md"
        @ready="buttonsReady = true"
      />
    </LayoutSlot>
    <HtmlFragmentRenderer
      v-if="assetsReady && payload.editorAssets"
      :fragment="payload.editorAssets"
      hidden
    />
  </div>
</template>
