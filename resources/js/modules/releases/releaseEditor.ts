import {inject, provide, type InjectionKey} from 'vue';
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
