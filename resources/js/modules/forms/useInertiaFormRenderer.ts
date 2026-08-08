import type {InertiaForm} from '@inertiajs/vue3';
import {
  computed,
  shallowRef,
  toRaw,
  toValue,
  watch,
  type MaybeRefOrGetter,
} from 'vue';
import type {FormPayload} from './types';

interface FormRendererInstance {
  advanceBaseline(): void;
  currentValues(): FormPayload['values'];
}

interface Options<
  T extends Record<string, any>,
  Key extends Extract<keyof T, string>,
> {
  mutationKey?: Key;
  mapErrorPath?: (path: string) => string[] | null;
}

/**
 * Bridges a FormRenderer island into an Inertia form. The Inertia field always
 * contains the editable mutation; `values` contains the full current values.
 */
export function useInertiaFormRenderer<
  T extends Record<string, any>,
  Key extends Extract<keyof T, string> = Extract<keyof T, string>,
>(
  form: InertiaForm<T>,
  payload: MaybeRefOrGetter<FormPayload | null>,
  {mutationKey, mapErrorPath}: Options<T, Key> = {}
) {
  const renderer = shallowRef<FormRendererInstance | null>(null);
  const values = shallowRef(clone(toValue(payload)?.values ?? {}));
  const formData = form as T;
  const rootKeys = new Set(Object.keys(form.data()));

  replaceMutation({});
  form.defaults();

  watch(
    () => toValue(payload),
    (currentPayload) => (values.value = clone(currentPayload?.values ?? {}))
  );

  const errors = computed(() =>
    Object.entries(form.errors).flatMap(([path, message]) => {
      const mappedPath = mapErrorPath
        ? mapErrorPath(path)
        : defaultErrorPath(path, toValue(payload)?.scope ?? []);

      return mappedPath
        ? [{path: mappedPath, messages: [String(message)]}]
        : [];
    })
  );

  function onMutation(mutation: FormPayload['values']): void {
    replaceMutation(mutation);
    values.value = renderer.value?.currentValues() ?? values.value;
  }

  function replaceMutation(mutation: FormPayload['values']): void {
    if (mutationKey !== undefined) {
      formData[mutationKey] = clone(mutation[mutationKey] ?? {}) as T[Key];

      return;
    }

    for (const key of rootKeys) {
      Reflect.deleteProperty(formData, key);
    }

    for (const [key, value] of Object.entries(mutation)) {
      if (!rootKeys.has(key)) {
        form.defaults({[key]: undefined} as unknown as Partial<T>);
        rootKeys.add(key);
      }

      formData[key as Extract<keyof T, string>] = clone(value) as T[Extract<
        keyof T,
        string
      >];
    }
  }

  function advanceBaseline(): void {
    renderer.value?.advanceBaseline();
    form.defaults();
  }

  return {advanceBaseline, errors, onMutation, renderer, values};
}

function defaultErrorPath(path: string, scope: string[]): string[] | null {
  const segments = path.split('.');

  return scope.every((segment, index) => segments[index] === segment)
    ? segments
    : null;
}

function clone<T>(value: T): T {
  return structuredClone(toRaw(value));
}
