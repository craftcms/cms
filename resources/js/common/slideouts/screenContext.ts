import {provide} from 'vue';
import SlideoutScreen from '@/common/layouts/screens/SlideoutScreen.vue';
import {
  createScreenPropsStore,
  provideScreenContext,
  ScreenPagePropsKey,
  ScreenPropsStoreKey,
  ScreenShellKey,
} from '@/common/composables/screen';
import {
  SlideoutControllerKey,
  type SlideoutController,
  type SlideoutInstance,
} from './types';

/**
 * Sets up the calling component to render a screen as a slideout does: in
 * the slideout shell, configuring its own props store rather than the base
 * page's, and reporting to `controller`.
 */
export function provideSlideoutScreen(
  instance: () => SlideoutInstance,
  controller: Omit<SlideoutController, 'instance'>
): void {
  provide(ScreenShellKey, SlideoutScreen);
  provide(ScreenPropsStoreKey, createScreenPropsStore());
  provideScreenContext('slideout');

  // Without this the shell would read its chrome off `usePage()` — the page
  // *behind* the slideout — and show that screen's title and edit URL.
  provide(ScreenPagePropsKey, () => instance().props);

  provide(SlideoutControllerKey, {
    get instance() {
      return instance();
    },
    close: controller.close,
    reload: controller.reload,
    saved: controller.saved,
  });
}
