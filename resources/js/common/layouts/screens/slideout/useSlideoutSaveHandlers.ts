import type {Ref} from 'vue';
import {router} from '@inertiajs/vue3';
import {t} from '@craftcms/ui/utilities/translate';
import type {ScreenPageProps} from '@/common/composables/screen';
import {firstMessages} from '@/common/slideouts/errors';
import type {SlideoutController} from '@/common/slideouts/types';

/**
 * What a slideout does once its screen has saved, whichever way it saved:
 * the element editor's callbacks, and the close-and-report step every save
 * path ends with.
 *
 * Errors land in the shell's `errors`, which it shows in place of the
 * server's summary when there wasn't one.
 */
export function useSlideoutSaveHandlers({
  slideout,
  errors,
}: {
  slideout: SlideoutController | null;
  errors: Ref<Record<string, string> | null>;
}) {
  const craft = () => window.Craft;

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

  // eslint-disable-next-line @typescript-eslint/no-explicit-any
  function onElementSaved(response: any): void {
    const data = response?.data ?? {};

    if (data.message) {
      craft()?.cp?.displaySuccess(data.message, data.notificationSettings);
    }

    if (data.modelClass && data.modelId) {
      craft()?.refreshComponentInstances?.(data.modelClass, data.modelId);
    }

    // Tell other tabs — and the legacy element index behind this panel — that
    // the element changed, exactly as `ElementEditorSlideout` does.
    if (data.element?.id) {
      craft()?.broadcaster?.postMessage({
        event: 'saveElement',
        id: data.element.id,
      });
    }

    craft()?.Preview?.refresh?.();

    closeAfterSave(data);
  }

  /**
   * An autosaved draft. The panel stays open — this only lets the opener show
   * the provisional changes, the way the legacy index picks up a `saveDraft`
   * broadcast.
   */
  // eslint-disable-next-line @typescript-eslint/no-explicit-any
  function onElementDraftSaved(response: any): void {
    slideout?.saved({draft: true, data: response?.data});
  }

  // eslint-disable-next-line @typescript-eslint/no-explicit-any
  function onElementSaveError(error: any): void {
    const data = error?.response?.data;

    if (!data) {
      throw error;
    }

    craft()?.cp?.displayError(data.message);

    // The server's own summary gets drawn into the screen HTML, split across
    // its tabs. Only stand in for it when there wasn't one.
    errors.value = data.errorSummary
      ? null
      : data.errors
        ? firstMessages(data.errors)
        : {error: data.message ?? t('Couldn’t save.')};
  }

  return {
    closeAfterSave,
    onElementSaved,
    onElementDraftSaved,
    onElementSaveError,
  };
}
