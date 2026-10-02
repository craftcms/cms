import {inject, provide, shallowReactive, type InjectionKey} from 'vue';
import {valueAt} from './runtime';
import type {FormValue, FormValues} from './types';

interface FormValueGroup {
  register(values: FormValues): () => void;
  valueAt(path: string[]): FormValue;
}

const FormValueGroupKey: InjectionKey<FormValueGroup> =
  Symbol('FormValueGroup');

/**
 * Shares values between Form renderers that make up one logical form.
 */
export function provideFormValueGroup(): void {
  const sources = shallowReactive(new Set<FormValues>());

  provide(FormValueGroupKey, {
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

export function useFormValueGroup(): FormValueGroup | undefined {
  return inject(FormValueGroupKey, undefined);
}
