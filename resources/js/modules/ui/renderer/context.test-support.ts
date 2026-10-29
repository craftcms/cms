import {computed, shallowRef} from 'vue';
import {setValue} from '../runtime';
import type {UiValues} from '../types';
import {UiRendererKey, type UiRendererContext} from './context';

/**
 * A UiRenderer context writing into `values`, for mounting a node on its own.
 * Spread into `app.provide()`.
 */
export function stubUiRenderer(
  values: UiValues
): [typeof UiRendererKey, UiRendererContext] {
  return [
    UiRendererKey,
    {
      fail: () => {},
      setValue: (path, value) => setValue(values, path, value),
      synchronizeStructure: () => {},
      registerBehavior: () => () => {},
      clearChildErrors: () => {},
      childErrorsCleared: () => false,
      overrides: {},
      pending: shallowRef(false),
      modifiedGroups: computed(() => new Set<string>()),
      changedPaths: computed(() => new Set<string>()),
      refreshingFields: computed(() => new Set<string>()),
    },
  ];
}
