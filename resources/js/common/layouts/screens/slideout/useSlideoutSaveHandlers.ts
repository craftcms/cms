import {router} from '@inertiajs/vue3';
import type {ScreenPageProps} from '@/common/composables/screen';
import type {SlideoutController} from '@/common/slideouts/types';

/**
 * What a slideout does once its screen has saved: report it and close.
 */
export function useSlideoutSaveHandlers({
  slideout,
}: {
  slideout: SlideoutController | null;
}) {
  /**
   * Reports the save to whoever opened the panel and closes it, refreshing
   * the page behind when nobody was listening.
   */
  function closeAfterSave(data: ScreenPageProps | undefined): void {
    const handled = slideout?.saved({data});

    slideout?.close({force: true});

    if (!handled) {
      // Refresh the page behind to pick up whatever changed.
      router.reload();
    }
  }

  return {closeAfterSave};
}
