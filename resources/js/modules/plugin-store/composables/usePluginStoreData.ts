import {createGlobalState} from '@vueuse/core';
import {computed, shallowRef} from 'vue';
import {getCoreData, getPluginLicenseInfo} from '../api';
import type {CoreData, PluginLicenseInfo} from '../types';

/**
 * Categories and sort options. They rarely change, so they're fetched once and
 * kept across visits, the way the standalone app kept them for its lifetime.
 */
export const useCoreData = createGlobalState(() => {
  const data = shallowRef<CoreData | null>(null);
  const failed = shallowRef(false);

  getCoreData()
    .then((response) => (data.value = response))
    .catch(() => (failed.value = true));

  return {
    coreData: computed(() => data.value),
    categories: computed(() => data.value?.categories ?? []),
    failed: computed(() => failed.value),
  };
});

/** How each plugin stands on this install: installed, trial, licensed edition… */
export const usePluginLicenseInfo = createGlobalState(() => {
  const info = shallowRef<Record<string, PluginLicenseInfo> | null>(null);
  const failed = shallowRef(false);

  async function refresh(): Promise<void> {
    try {
      info.value = await getPluginLicenseInfo();
      failed.value = false;
    } catch {
      failed.value = true;
    }
  }

  void refresh();

  return {
    licenseInfo: computed(() => info.value),
    loaded: computed(() => info.value !== null || failed.value),
    licenseInfoFor: (handle: string) => info.value?.[handle] ?? null,
    isInstalled: (handle: string) => info.value?.[handle]?.isInstalled ?? false,
    refresh,
  };
});
