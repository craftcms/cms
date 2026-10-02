import type {UseStorageOptions} from '@vueuse/core';
import {useStorage as _useStorage} from '@vueuse/core';
import type {RemovableRef} from '@vueuse/shared';
import type {MaybeRefOrGetter} from 'vue';

declare const Craft: {systemUid: string};

function prefixedKey(key: string): string {
  return `Craft-${Craft.systemUid}.${key}`;
}

export function useStorage(
  key: string,
  initialValue: MaybeRefOrGetter<string>,
  storage?: Storage,
  options?: UseStorageOptions<string>
): RemovableRef<string>;
export function useStorage(
  key: string,
  initialValue: MaybeRefOrGetter<boolean>,
  storage?: Storage,
  options?: UseStorageOptions<boolean>
): RemovableRef<boolean>;
export function useStorage(
  key: string,
  initialValue: MaybeRefOrGetter<number>,
  storage?: Storage,
  options?: UseStorageOptions<number>
): RemovableRef<number>;
export function useStorage<T>(
  key: string,
  initialValue: MaybeRefOrGetter<T>,
  storage?: Storage,
  options?: UseStorageOptions<T>
): RemovableRef<T>;
export function useStorage<T = unknown>(
  key: string,
  initialValue: MaybeRefOrGetter<null>,
  storage?: Storage,
  options?: UseStorageOptions<T>
): RemovableRef<T>;
export function useStorage(
  key: string,
  initialValue: MaybeRefOrGetter<unknown>,
  storage: Storage = localStorage,
  options?: UseStorageOptions<any>
) {
  return _useStorage(prefixedKey(key), initialValue, storage, options);
}

export function useLocalStorage(
  key: string,
  initialValue: MaybeRefOrGetter<string>,
  options?: UseStorageOptions<string>
): RemovableRef<string>;
export function useLocalStorage(
  key: string,
  initialValue: MaybeRefOrGetter<boolean>,
  options?: UseStorageOptions<boolean>
): RemovableRef<boolean>;
export function useLocalStorage(
  key: string,
  initialValue: MaybeRefOrGetter<number>,
  options?: UseStorageOptions<number>
): RemovableRef<number>;
export function useLocalStorage<T>(
  key: string,
  initialValue: MaybeRefOrGetter<T>,
  options?: UseStorageOptions<T>
): RemovableRef<T>;
export function useLocalStorage<T = unknown>(
  key: string,
  initialValue: MaybeRefOrGetter<null>,
  options?: UseStorageOptions<T>
): RemovableRef<T>;
export function useLocalStorage(
  key: string,
  initialValue: MaybeRefOrGetter<unknown>,
  options?: UseStorageOptions<any>
) {
  return useStorage(key, initialValue, localStorage, options);
}

export function useSessionStorage(
  key: string,
  initialValue: MaybeRefOrGetter<string>,
  options?: UseStorageOptions<string>
): RemovableRef<string>;
export function useSessionStorage(
  key: string,
  initialValue: MaybeRefOrGetter<boolean>,
  options?: UseStorageOptions<boolean>
): RemovableRef<boolean>;
export function useSessionStorage(
  key: string,
  initialValue: MaybeRefOrGetter<number>,
  options?: UseStorageOptions<number>
): RemovableRef<number>;
export function useSessionStorage<T>(
  key: string,
  initialValue: MaybeRefOrGetter<T>,
  options?: UseStorageOptions<T>
): RemovableRef<T>;
export function useSessionStorage<T = unknown>(
  key: string,
  initialValue: MaybeRefOrGetter<null>,
  options?: UseStorageOptions<T>
): RemovableRef<T>;
export function useSessionStorage(
  key: string,
  initialValue: MaybeRefOrGetter<unknown>,
  options?: UseStorageOptions<any>
) {
  return useStorage(key, initialValue, sessionStorage, options);
}
