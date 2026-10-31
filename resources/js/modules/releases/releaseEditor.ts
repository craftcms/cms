import {
  inject,
  onBeforeUnmount,
  onMounted,
  provide,
  shallowRef,
  type InjectionKey,
} from 'vue';
import type {ReleaseOption} from '@/modules/elements/composables/useElementEditor';

export interface ReleaseEditorActions {
  select(release: ReleaseOption): void;
  create(opener: HTMLElement | null): void;
}

export const ReleaseEditorActionsKey: InjectionKey<ReleaseEditorActions> =
  Symbol('ReleaseEditorActions');

export function provideReleaseEditorActions(
  actions: ReleaseEditorActions
): void {
  provide(ReleaseEditorActionsKey, actions);
}

export function useReleaseEditorActions(): ReleaseEditorActions | null {
  return inject(ReleaseEditorActionsKey, null);
}

/** The release whose preview is open, if any. */
const previewedReleaseId = shallowRef<number | null>(null);

/**
 * Marks a release's preview as open while the calling component is mounted,
 * so its drafts' editors know they were opened from it.
 */
export function useReleasePreviewScope(releaseId: () => number): void {
  onMounted(() => (previewedReleaseId.value = releaseId()));
  onBeforeUnmount(() => (previewedReleaseId.value = null));
}

export function isReleasePreviewOpen(releaseId: number): boolean {
  return previewedReleaseId.value === releaseId;
}
