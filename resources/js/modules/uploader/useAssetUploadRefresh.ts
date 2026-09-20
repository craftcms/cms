import {router} from '@inertiajs/vue3';
import {useEventListener} from '@vueuse/core';
import {onBeforeUnmount, onMounted, watch} from 'vue';
import {assetUploadQueue} from './asset-upload-queue';

/** Refresh only a mounted Assets index, including a stale history visit back to it. */
export function useAssetUploadRefresh(
  folderId: () => number | undefined
): void {
  let mounted = false;
  let navigating = false;
  let refreshing = false;

  function refresh(): void {
    const folder = folderId();
    const revision = folder ? assetUploadQueue.revision(folder) : 0;
    if (!mounted || navigating || refreshing || !folder || !revision) {
      return;
    }

    refreshing = true;
    router.reload({
      only: ['data', 'pagination'],
      async: true,
      onSuccess: () => {
        if (mounted && folderId() === folder) {
          assetUploadQueue.acknowledge(folder, revision);
        }
      },
      onFinish: () => {
        refreshing = false;
        if (
          assetUploadQueue.revision(folder) !== revision ||
          folderId() !== folder
        ) {
          refresh();
        }
      },
    });
  }

  useEventListener(assetUploadQueue, 'change', refresh);
  watch(folderId, refresh);

  const stopStart = router.on('start', (event) => {
    if (!event.detail.visit.async) {
      navigating = true;
    }
  });
  const stopFinish = router.on('finish', (event) => {
    if (!event.detail.visit.async) {
      navigating = false;
      refresh();
    }
  });

  onMounted(() => {
    mounted = true;
    refresh();
  });
  onBeforeUnmount(() => {
    mounted = false;
    stopStart();
    stopFinish();
  });
}
