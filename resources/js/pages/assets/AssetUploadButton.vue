<script setup lang="ts">
  import {t} from '@craftcms/ui';
  import {actionClient} from '@craftcms/ui/utilities/api/actionClient';
  import {router} from '@inertiajs/vue3';
  import {computed, onBeforeUnmount, onMounted, ref, watch} from 'vue';
  import ResolveUploadConflictController from '@/actions/CraftCms/Cms/Http/Controllers/Assets/ResolveUploadConflictController';
  import {deleteAsset} from '@/actions/CraftCms/Cms/Http/Controllers/Assets/ActionController';
  import {store} from '@/routes/craft/actions/craft/cp/uploads';
  import type {UploaderCallbacks} from '@/modules/uploader/base-uploader';
  import {PromptHandler} from '@/modules/prompt-handler/prompt-handler';

  import {Uploader as FileUploader} from '@/modules/uploader/uploader';

  interface Uploader {
    destroy(): void;
    isLastUpload(): boolean;
    setParams(params: Record<string, unknown>): void;
  }

  type UploadResult = Omit<CraftCms.Cms.Asset.Data.UploadResult, 'status'>;

  interface ConflictPrompt extends UploadResult {
    assetId: number | string;
    filename: string;
    conflict: string;
    choice?: string;
    prompt: {
      message: string;
      choices: {value: string; title: string}[];
      modalSettings: {hideOnEsc: boolean; hideOnShadeClick: boolean};
    };
  }

  // `withDefaults`, because Vue casts an absent optional Boolean prop to
  // `false` rather than leaving it undefined — so `reloadOnComplete` has to
  // say out loud that it defaults to true, or no caller ever reloads.
  const props = withDefaults(
    defineProps<{
      canUpload: boolean;
      folderId?: number;
      fsType?: string;
      allowedKinds?: string[];
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
  const promptHandler = new PromptHandler(
    () =>
      fileInput.value?.closest<HTMLElement>('craft-element-selector-modal') ??
      document.body
  );
  let uploader: Uploader | null = null;

  function reportUploaded(result: UploadResult): void {
    if (!result.assetId) {
      return;
    }

    emit('uploaded', {
      id: Number(result.assetId),
      label: String(result.filename ?? result.assetId),
    });
  }

  function queueConflict(result: Omit<ConflictPrompt, 'prompt'>): void {
    promptHandler.addPrompt({
      ...result,
      prompt: {
        message: result.conflict,
        choices: [
          {value: 'keepBoth', title: t('Keep both')},
          {value: 'replace', title: t('Replace it')},
        ],
        modalSettings: {hideOnEsc: false, hideOnShadeClick: false},
      },
    });
  }

  function finishUploads(): void {
    promptHandler.resetPrompts();

    if (props.reloadOnComplete) {
      router.reload({only: ['data', 'pagination']});
    }
  }

  async function resolveConflicts(conflicts: ConflictPrompt[]): Promise<void> {
    try {
      for (const conflict of conflicts) {
        if (conflict.choice === 'keepBoth') {
          reportUploaded({
            ...conflict,
            filename: conflict.suggestedFilename ?? conflict.filename,
          });
        } else if (conflict.choice === 'replace') {
          const target = conflict.conflictingAssetId
            ? {assetId: conflict.conflictingAssetId}
            : {targetFilename: conflict.filename};
          const {data} = await actionClient.post<UploadResult>(
            ResolveUploadConflictController.url(),
            {sourceAssetId: Number(conflict.assetId), ...target}
          );

          reportUploaded(data);
        } else {
          await actionClient.post(deleteAsset.url(), {
            assetId: Number(conflict.assetId),
          });
        }
      }
    } catch (error) {
      Craft.cp?.displayError?.(
        error instanceof Error ? error.message : t('Upload failed.')
      );
    } finally {
      finishUploads();
    }
  }

  function createUploader(): void {
    uploader?.destroy();
    uploader = null;

    if (!enabled.value || !fileInput.value) {
      return;
    }

    const input = fileInput.value;

    uploader = new FileUploader(input, {
      fileInput: input,
      allowedKinds: props.allowedKinds,
      ...(props.dropZone ? {dropZone: props.dropZone} : {}),
      url: store.url(),
      on: {
        start: () => promptHandler.resetPrompts(),
        done: ({result}: {result: UploadResult}) => {
          Craft.cp?.runQueue?.();

          if (result.assetId && result.filename && result.conflict) {
            queueConflict(result as Omit<ConflictPrompt, 'prompt'>);
          } else {
            reportUploaded(result);
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
          if (!uploader?.isLastUpload()) {
            return;
          }

          if (promptHandler.getPromptCount()) {
            promptHandler.showBatchPrompts((conflicts) => {
              void resolveConflicts(conflicts as ConflictPrompt[]);
            });
          } else {
            finishUploads();
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
      () => props.allowedKinds,
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
