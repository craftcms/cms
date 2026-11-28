<script setup lang="ts">
  /**
   * Compares an upload with the images it looks like a copy of, with the
   * same choices as its upload notification. With several matches, a menu
   * switches between them, and Replace replaces the one being shown.
   */
  import {t} from '@craftcms/ui';
  import {useObjectUrl} from '@vueuse/core';
  import {computed, shallowRef, useTemplateRef, watch} from 'vue';
  import ImageComparison from '@/common/components/ImageComparison.vue';
  import type {
    ComparedDetail,
    ComparedImage,
    ImageComparisonView,
  } from '@/common/components/imageComparison';
  import ImageComparisonViews from '@/common/components/ImageComparisonViews.vue';
  import Modal from '@/common/components/Modal.vue';
  import type {ConflictResolution} from '@/modules/assets/assetMover';
  import '@/modules/assets/asset-conflict-resolution.ce';
  import {formatBytes} from './file-upload-notification.ce';
  import type {UploadDuplicate} from './upload-duplicates-notification.ce';

  const props = defineProps<{
    isActive: boolean;
    file: File;
    /** Where the file is being uploaded to. */
    location: string;
    duplicates: UploadDuplicate[];
  }>();
  const emit = defineEmits<{
    (event: 'close'): void;
    (event: 'closed'): void;
    (event: 'choose', choice: ConflictResolution, assetId: number): void;
  }>();

  const previewUrl = useObjectUrl(() => props.file);
  const dimensions = shallowRef<{width: number; height: number}>();
  const selected = shallowRef(0);
  const view = shallowRef<ImageComparisonView>('sideBySide');
  const comparison = useTemplateRef('comparison');
  const opened = shallowRef(false);

  // Read from the preview, which is all the browser knows of the file's size in pixels.
  watch(
    previewUrl,
    (url) => {
      dimensions.value = undefined;
      if (!url) {
        return;
      }
      const image = new Image();
      image.onload = () => {
        if (previewUrl.value === url) {
          dimensions.value = {
            width: image.naturalWidth,
            height: image.naturalHeight,
          };
        }
      };
      image.src = url;
    },
    {immediate: true}
  );

  const title = computed(() =>
    t('Compare “{filename}”', {filename: props.file.name})
  );

  function extension(filename: string): string {
    return filename.includes('.')
      ? filename.split('.').pop()!.toUpperCase()
      : '';
  }

  function formatDimensions(
    width: number | null | undefined,
    height: number | null | undefined
  ): string | null {
    return width && height ? `${width} × ${height}` : null;
  }

  const details: ComparedDetail[] = [
    {key: 'filename', label: t('Filename')},
    {key: 'dimensions', label: t('Dimensions')},
    {key: 'size', label: t('File size')},
    {key: 'type', label: t('File type')},
    {key: 'location', label: t('Location')},
    {key: 'uploaded', label: t('Uploaded'), compared: false},
    {key: 'uploader', label: t('Uploaded by'), compared: false},
  ];

  const upload = computed<ComparedImage>(() => ({
    key: 'upload',
    title: props.file.name,
    src: previewUrl.value ?? null,
    details: {
      filename: props.file.name,
      dimensions: formatDimensions(
        dimensions.value?.width,
        dimensions.value?.height
      ),
      size: formatBytes(props.file.size),
      type: extension(props.file.name),
      location: props.location,
    },
  }));

  const matches = computed<ComparedImage[]>(() =>
    props.duplicates.map((duplicate) => ({
      key: duplicate.id,
      title: duplicate.title,
      src: duplicate.thumbUrl,
      url: duplicate.url,
      details: {
        filename: duplicate.filename,
        dimensions: formatDimensions(duplicate.width, duplicate.height),
        size: duplicate.size === null ? null : formatBytes(duplicate.size),
        type: duplicate.extension.toUpperCase(),
        location: duplicate.location,
        uploaded: duplicate.dateCreated,
        uploader: duplicate.uploader,
      },
    }))
  );

  function choose(event: Event): void {
    emit(
      'choose',
      (event as CustomEvent<ConflictResolution>).detail,
      props.duplicates[selected.value]!.id
    );
  }
</script>

<template>
  <Modal
    :is-active="isActive"
    :label="title"
    width="4xl"
    :hold-height="false"
    resizable
    storage-key="UploadDuplicatesModal.size"
    @close="emit('close')"
    @opened="opened = true"
    @closed="emit('closed')"
  >
    <craft-pane :label="title" class="upload-duplicates">
      <ImageComparisonViews
        v-if="comparison?.slidable"
        v-model="view"
        slot="header-actions"
      />
      <craft-button
        slot="header-actions"
        type="button"
        icon="xmark-large"
        :aria-label="t('Close')"
        variant="plain"
        size="small"
        flush="inline-end"
        @click="emit('close')"
      ></craft-button>

      <ImageComparison
        ref="comparison"
        v-model:selected="selected"
        v-model:view="view"
        :from="upload"
        :to="matches"
        :from-label="t('New upload')"
        :to-label="t('Existing image')"
        :details="details"
        :announce="opened"
        data-upload-duplicates-modal
      />

      <craft-asset-conflict-resolution
        slot="actions"
        class="upload-duplicates__choices"
        duplicate
        discards
        @craft-conflict-resolve="choose"
      ></craft-asset-conflict-resolution>
    </craft-pane>
  </Modal>
</template>

<style scoped>
  /* Fills a modal that's been resized taller, keeping the footer at its
     bottom, as ModalForm's pane does. It grows but never shrinks, so the
     modal scrolls what doesn't fit. */
  .upload-duplicates {
    --image-comparison-bleed: var(--c-spacing-lg);
    --compare-surface: var(--c-modal-fill);

    display: flex;
    flex: 1 0 auto;
    flex-direction: column;
  }

  .upload-duplicates::part(base) {
    display: flex;
    flex: 1 0 auto;
    flex-direction: column;
  }

  .upload-duplicates::part(body) {
    flex: 1 0 auto;
  }

  .upload-duplicates__choices {
    display: flex;
    flex-wrap: wrap;
    gap: var(--c-spacing-sm);
    justify-content: flex-end;
  }
</style>
