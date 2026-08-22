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

  const props = defineProps<{
    canUpload: boolean;
    folderId?: number;
    fsType?: string;
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
      url: upload.url(),
      events: {
        fileuploaddone: () => Craft.cp?.runQueue?.(),
        fileuploadfail: (event: CustomEvent, data: any = null) => {
          const response =
            event instanceof CustomEvent
              ? event.detail
              : data?.jqXHR?.responseJSON;

          Craft.cp?.displayError?.(response?.message ?? t('Upload failed.'));
        },
        fileuploadalways: () => {
          if (uploader?.isLastUpload()) {
            router.reload({only: ['data', 'pagination']});
          }
        },
      },
    }) as Uploader;

    uploader.setParams({folderId: props.folderId});
  }

  onMounted(createUploader);
  watch(() => [props.canUpload, props.folderId, props.fsType], createUploader);
  onBeforeUnmount(() => uploader?.destroy());
</script>

<template>
  <input ref="fileInput" type="file" name="assets-upload" multiple hidden />
  <craft-button icon="upload" :disabled="!enabled" @click="fileInput?.click()">
    {{ t('Upload files') }}
  </craft-button>
</template>
