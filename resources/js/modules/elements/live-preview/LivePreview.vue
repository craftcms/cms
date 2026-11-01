<script setup lang="ts">
  /**
   * Live Preview: the element's front end beside its editor, which reloads as
   * edits are autosaved.
   *
   * The editor isn't rendered here. The page's own editor content, tabs and
   * all, is moved into the exposed `editorTarget` while this is open, so
   * there's one form, one autosave queue, and nothing to reconcile when it
   * closes. Its details panels move into the exposed `panels`, which the
   * editor shows beside it, as a slideout's does.
   */
  import {t} from '@craftcms/ui';
  import {ref, useId, useTemplateRef} from 'vue';
  import ScreenDetailsLayout from '@/common/components/ScreenDetailsLayout.vue';
  import {createScreenPanels} from '@/common/composables/screenPanels';
  import PreviewCloseButton from './PreviewCloseButton.vue';
  import PreviewLayout from './PreviewLayout.vue';
  import type {LivePreviewController} from './useLivePreview';

  defineProps<{
    controller: LivePreviewController;
    title: string;
    /** Set when the screen is only there for the preview, which then can't close. */
    standalone?: {editUrl: string | null} | null;
  }>();

  const emit = defineEmits<{
    (e: 'save'): void;
    (e: 'closed'): void;
  }>();

  const editorTarget = useTemplateRef<HTMLElement>('editorTarget');
  const panels = createScreenPanels();
  const panelIdPrefix = `lp-details-${useId()}`;

  /**
   * Whether the editor content belongs in the pane: from when it has slid in
   * until it has slid out, so the content is never seen leaving either place.
   */
  const holdsEditor = ref(false);

  // An element rather than an id, so the content follows it if it's replaced.
  defineExpose({editorTarget, holdsEditor, panels});
</script>

<template>
  <PreviewLayout
    :controller="controller"
    :closable="!standalone"
    @after-enter="holdsEditor = true"
    @after-leave="holdsEditor = false"
    @closed="emit('closed')"
  >
    <template #editor>
      <div class="lp-editor-pane">
        <header class="lp-pane-header">
          <div class="lp-editor-pane__heading">
            <h2 class="lp-pane-title">{{ title }}</h2>
            <slot name="meta"></slot>
          </div>
          <div class="lp-editor__status">
            <slot name="status"></slot>
          </div>
          <slot name="actions"></slot>
          <craft-button
            v-if="standalone?.editUrl"
            size="small"
            icon="arrow-up-right-from-square"
            icon-position="suffix"
            variant="plain"
            flush="inline-end"
            :href="standalone.editUrl"
            target="_blank"
          >
            {{ t('Edit') }}
            <span class="sr-only">{{ t('Opens in a new window') }}</span>
          </craft-button>
          <PreviewCloseButton v-else @click="controller.close()" />
        </header>

        <ScreenDetailsLayout
          :panels="panels"
          :id-prefix="panelIdPrefix"
          :show-details="panels.panels.value.length > 0"
        >
          <form
            class="lp-editor__body"
            novalidate
            @submit.prevent="emit('save')"
          >
            <div v-if="!holdsEditor" class="lp-editor__loading">
              <craft-spinner :label="t('Loading')"></craft-spinner>
            </div>
            <div ref="editorTarget"></div>
          </form>
        </ScreenDetailsLayout>

        <footer v-if="$slots.footer" class="lp-pane-footer">
          <slot name="footer"></slot>
        </footer>
      </div>
    </template>
  </PreviewLayout>
</template>

<style scoped>
  .lp-editor-pane {
    display: flex;
    flex-direction: column;
    min-width: 0;
    min-height: 0;
  }

  .lp-editor-pane__heading {
    flex: 1;
    display: flex;
    align-items: center;
    gap: var(--c-spacing-md);
    min-width: 0;

    & .lp-pane-title {
      flex: 0 1 auto;
    }
  }

  .lp-editor__body {
    & :deep(craft-field.width-100:not(craft-field *)) {
      --cp-scroll-bleed: var(--cp-container-padding);
    }

    & :deep(craft-field craft-field) {
      --cp-scroll-bleed: 0px;
    }
  }

  /* Centred over the whole pane body: the content column only grows to fit
     what's in it, and there's no details rail until the editor arrives. */
  .lp-editor__loading {
    position: absolute;
    inset: 0;
    display: flex;
    align-items: center;
    justify-content: center;
  }
</style>
