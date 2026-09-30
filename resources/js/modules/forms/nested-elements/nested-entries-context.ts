import {inject, type InjectionKey} from 'vue';

export interface NestedEntriesControlContext {
  path: string[];
  markModified(): void;
}

export const NestedEntriesControlKey: InjectionKey<NestedEntriesControlContext> =
  Symbol('nested-entries-control');

export function useNestedEntriesControl(): NestedEntriesControlContext {
  const context = inject(NestedEntriesControlKey);

  if (!context) {
    throw new Error('Nested entry shells require a nested entries control.');
  }

  return context;
}
