import {inject, provide, type InjectionKey} from 'vue';

const UiPreviewKey: InjectionKey<true> = Symbol('UiPreview');

/**
 * Marks the UIs rendered below as previews of a field’s value, such as
 * “Compare changes”, which show in a modal that nothing else can open over.
 */
export function provideUiPreview(): void {
  provide(UiPreviewKey, true);
}

/** Whether this UI is a preview of a field’s value. */
export function useUiPreview(): boolean {
  return inject(UiPreviewKey, false);
}
