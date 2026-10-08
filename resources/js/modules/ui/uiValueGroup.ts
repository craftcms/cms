import {inject, provide, shallowReactive, type InjectionKey} from 'vue';
import {valueAt} from './runtime';
import type {UiValue, UiValues} from './types';

interface UiValueGroup {
  register(values: UiValues): () => void;
  valueAt(path: string[]): UiValue;
}

const UiValueGroupKey: InjectionKey<UiValueGroup> = Symbol('UiValueGroup');

/**
 * Shares values between UI renderers that make up one logical form.
 */
export function provideUiValueGroup(): void {
  const sources = shallowReactive(new Set<UiValues>());

  provide(UiValueGroupKey, {
    register(values) {
      sources.add(values);

      return () => sources.delete(values);
    },
    valueAt(path) {
      for (const source of sources) {
        const value = valueAt(source, path);

        if (value !== undefined) {
          return value;
        }
      }

      return undefined;
    },
  });
}

export function useUiValueGroup(): UiValueGroup | undefined {
  return inject(UiValueGroupKey, undefined);
}
