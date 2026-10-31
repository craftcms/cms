<script setup lang="ts">
  /**
   * Live Preview: the editor beside the element's front end, which reloads as
   * edits are autosaved.
   *
   * The editor isn't rendered here. The page's own editor content, tabs and
   * all, is moved into the exposed `editorTarget` while this is open, so
   * there's one form, one autosave queue, and nothing to reconcile when it
   * closes.
   */
  import {t} from '@craftcms/ui';
  import {ref, useTemplateRef, watch} from 'vue';
  import PreviewLayout from './PreviewLayout.vue';
  import type {LivePreviewController} from './useLivePreview';

  const props = defineProps<{
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
  const layout = useTemplateRef<InstanceType<typeof PreviewLayout>>('layout');

  // Where slideouts opened during the preview edit, beside it.
  watch(
    () => layout.value?.pane ?? null,
    (pane) => (props.controller.editor.value = pane),
    {immediate: true}
  );

  /**
   * Whether the editor content belongs in the pane: from when it has slid in
   * until it has slid out, so the content is never seen leaving either place.
   */
  const holdsEditor = ref(false);

  // An element rather than an id, so the content follows it if it's replaced.
  defineExpose({editorTarget, holdsEditor});
</script>

<template>
  <PreviewLayout
    ref="layout"
    :controller="controller"
    :title="title"
    :closable="!standalone"
    @after-enter="holdsEditor = true"
    @after-leave="holdsEditor = false"
    @closed="emit('closed')"
  >
    <template #meta>
      <slot name="meta"></slot>
    </template>

    <template #actions>
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
    </template>

    <form class="lp-editor__body" novalidate @submit.prevent="emit('save')">
      <div v-if="!holdsEditor" class="lp-editor__loading">
        <craft-spinner :label="t('Loading')"></craft-spinner>
      </div>
      <div ref="editorTarget"></div>
    </form>

    <template #footer>
      <slot name="footer"></slot>
    </template>
  </PreviewLayout>
</template>

<style scoped>
  .lp-editor__body {
    & :deep(craft-field.width-100:not(craft-field *)) {
      --cp-scroll-bleed: var(--cp-container-padding);
    }

    & :deep(craft-field craft-field) {
      --cp-scroll-bleed: 0px;
    }
  }

  .lp-editor__loading {
    display: flex;
    flex: 1;
    align-items: center;
    justify-content: center;
  }
</style>
