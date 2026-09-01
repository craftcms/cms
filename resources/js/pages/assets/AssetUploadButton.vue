<script setup lang="ts">
  import {t} from '@craftcms/ui';
  import {router} from '@inertiajs/vue3';
  import {computed, onBeforeUnmount, onMounted, ref, watch} from 'vue';
  import {upload} from '@actions/Assets/UploadController';

  declare const $: any;

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
      /**
       * An element that also accepts dropped files. The asset index doesn't
       * set one — its drop target is the whole page, handled elsewhere — but
       * a relation field passes its own container so files can be dropped
       * onto it.
       */
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
    /** One completed upload, as `assets/upload` reported it. */
    (event: 'uploaded', asset: {id: number; label: string}): void;
  }>();

  const fileInput = ref<HTMLInputElement>();
  const enabled = computed(
    () => props.canUpload && !!props.folderId && !!props.fsType
  );
  let uploader: Uploader | null = null;

  function createUploader(): void {
    uploader?.destroy();
    uploader = null;

    if (!enabled.value || !fileInput.value) {
      return;
    }

    const input = $(fileInput.value);

    uploader = Craft.createUploader(props.fsType!, input, {
      fileInput: input,
      // Files dropped on the caller's container upload as if picked, which is
      // what makes a relation field a drop target.
      ...(props.dropZone ? {dropZone: $(props.dropZone)} : {}),
      url: upload.url(),
      events: {
        // jQuery File Upload calls this as `(event, data)` with the parsed
        // response on `data.result`; the CustomEvent branch covers an uploader
        // class that re-dispatches natively instead.
        fileuploaddone: (event: Event, data: any = null) => {
          Craft.cp?.runQueue?.();

          // `assets/upload` answers with the new asset's id and filename. A
          // filename conflict answers with `conflict` instead and is resolved
          // separately, so there is nothing to attach yet.
          const result =
            event instanceof CustomEvent && event.detail
              ? event.detail
              : (data?.result ?? data?.jqXHR?.responseJSON);

          if (result?.assetId && !result?.conflict) {
            emit('uploaded', {
              id: Number(result.assetId),
              label: String(result.filename ?? result.assetId),
            });
          }
        },
        fileuploadfail: (event: Event, data: any = null) => {
          const response =
            event instanceof CustomEvent && event.detail
              ? event.detail
              : data?.jqXHR?.responseJSON;

          Craft.cp?.displayError?.(response?.message ?? t('Upload failed.'));
        },
        fileuploadalways: () => {
          if (uploader?.isLastUpload() && props.reloadOnComplete) {
            router.reload({only: ['data', 'pagination']});
          }
        },
      },
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
      () => props.fsType,
      () => props.dropZone,
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
