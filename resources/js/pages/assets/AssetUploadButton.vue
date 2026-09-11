<script setup lang="ts">
  import {t} from '@craftcms/ui';
  import {router} from '@inertiajs/vue3';
  import {computed, onBeforeUnmount, onMounted, ref, watch} from 'vue';
  import {store} from '@/routes/craft/actions/craft/cp/uploads';
  import {assetUploadQueue} from '@/modules/uploader/asset-upload-queue';
  import type {UploaderCallbacks} from '@/modules/uploader/base-uploader';
  import type {AssetUploadDestination} from '@/modules/uploader/upload-notification';

  import {Uploader as FileUploader} from '@/modules/uploader/uploader';

  interface Uploader {
    destroy(): void;
    isLastUpload(): boolean;
    setParams(params: Record<string, unknown>): void;
  }

  // `withDefaults`, because Vue casts an absent optional Boolean prop to
  // `false` rather than leaving it undefined — so `reloadOnComplete` has to
  // say out loud that it defaults to true, or no caller ever reloads.
  const props = withDefaults(
    defineProps<{
      canUpload: boolean;
      folderId?: number;
      fsType?: string;
      /** Assets index uploads belong to the application queue when a destination is supplied. */
      destination?: AssetUploadDestination;
      /** The Assets page or relation-field container that accepts dropped files. */
      dropZone?: HTMLElement | null;
      /**
       * Whether finishing an upload should reload the page's index props.
       *
       * True for the asset index, whose listing is what the upload changed.
       * A relation field sets this false and handles `uploaded` instead:
       * there is no index behind it to refresh, and reloading an element edit
       * screen would throw away unsaved changes.
       */
      reloadOnComplete?: boolean;
    }>(),
    {reloadOnComplete: true}
  );

  const emit = defineEmits<{
    /** One completed upload, as the upload session reported it. */
    (event: 'uploaded', asset: {id: number; label: string}): void;
  }>();

  const fileInput = ref<HTMLInputElement>();
  const enabled = computed(() => props.canUpload && !!props.folderId);
  let uploader: Uploader | null = null;

  function createUploader(): void {
    uploader?.destroy();
    uploader = null;

    if (!enabled.value || !fileInput.value) {
      return;
    }

    const input = fileInput.value;
    const destination = props.destination ? {...props.destination} : null;

    uploader = new FileUploader(input, {
      fileInput: input,
      ...(props.dropZone ? {dropZone: props.dropZone} : {}),
      url: store.url(),
      ...(destination
        ? {
            enqueueUpload: (file: File, selection: File[]) =>
              assetUploadQueue.enqueue(file, destination, selection),
          }
        : {}),
      on: {
        done: ({result}) => {
          Craft.cp?.runQueue?.();

          if (result?.assetId && !result?.conflict) {
            emit('uploaded', {
              id: Number(result.assetId),
              label: String(result.filename ?? result.assetId),
            });
          }
        },
        fail: ({error, canceled}) => {
          if (!canceled) {
            Craft.cp?.displayError?.(
              error instanceof Error ? error.message : t('Upload failed.')
            );
          }
        },
        settled: () => {
          if (uploader?.isLastUpload() && props.reloadOnComplete) {
            router.reload({only: ['data', 'pagination']});
          }
        },
      } satisfies UploaderCallbacks,
    }) as Uploader;

    uploader.setParams({folderId: props.folderId});
  }

  onMounted(createUploader);
  // An array of getters, not a getter returning an array: the latter yields a
  // fresh array every evaluation, so it never compares equal and re-runs on
  // unrelated invalidations — each of which tears the uploader down and, if
  // the input isn't resolvable at that moment, leaves it null.
  watch(
    [
      () => props.canUpload,
      () => props.folderId,
      () => props.dropZone,
      () => props.destination?.folderId,
      () => props.destination?.url,
      () => props.destination?.label,
    ],
    createUploader
  );
  onBeforeUnmount(() => uploader?.destroy());
</script>

<template>
  <input ref="fileInput" type="file" name="assets-upload" multiple hidden />
  <craft-button
    icon="upload"
    :disabled="!enabled"
    @click="fileInput?.click()"
    v-bind="$attrs"
  >
    {{ t('Upload files') }}
  </craft-button>
</template>
