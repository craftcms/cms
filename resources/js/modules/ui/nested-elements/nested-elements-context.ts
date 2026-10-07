import {inject, type InjectionKey} from 'vue';

export interface NestedElementsControlContext {
  path: string[];
  markModified(): void;
}

export const NestedElementsControlKey: InjectionKey<NestedElementsControlContext> =
  Symbol('nested-elements-control');

export function useNestedElementsControl(): NestedElementsControlContext {
  const context = inject(NestedElementsControlKey);

  if (!context) {
    throw new Error('Nested element shells require a nested elements control.');
  }

  return context;
}
