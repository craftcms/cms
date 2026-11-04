import {usePage} from '@inertiajs/vue3';
import {computed, shallowRef, watch} from 'vue';
import {getCmsEditions, getPluginsByHandles} from '../api';
import {cmsTrialRow, pluginTrialRow, type TrialRow} from '../cart';
import {isEditionFree} from '../pricing';
import {usePluginLicenseInfo} from './usePluginStoreData';
import craftIconUrl from '../images/craft.svg?url';
import type {
  CmsEdition,
  PluginLicenseInfo,
  PluginStoreContext,
  PluginSummary,
} from '../types';

/** A plugin is on trial while it runs an edition this install hasn't bought. */
export function isActiveTrial(
  plugin: PluginSummary,
  info: PluginLicenseInfo | null
): boolean {
  if (!info?.isInstalled) {
    return false;
  }

  if (
    !info.isTrial &&
    info.licenseKey &&
    info.edition === info.licensedEdition
  ) {
    return false;
  }

  const edition = plugin.editions.find((e) => e.handle === info.edition);

  return !(edition && isEditionFree(edition));
}

/** The installed plugins being run on a trial license. */
export function useActiveTrials() {
  const {licenseInfo, loaded: licenseInfoLoaded} = usePluginLicenseInfo();
  const plugins = shallowRef<PluginSummary[]>([]);
  const failed = shallowRef(false);
  const loaded = shallowRef(false);

  watch(
    licenseInfo,
    async (info, _old, onCleanup) => {
      if (!info) {
        return;
      }

      const handles = Object.keys(info).filter(
        (handle) => info[handle]?.isEnabled
      );

      if (handles.length === 0) {
        plugins.value = [];
        loaded.value = true;
        return;
      }

      const controller = new AbortController();
      onCleanup(() => controller.abort());

      try {
        const found = await getPluginsByHandles(handles, controller.signal);
        plugins.value = found.filter(
          (plugin): plugin is PluginSummary =>
            !!plugin && isActiveTrial(plugin, info[plugin.handle] ?? null)
        );
      } catch {
        if (!controller.signal.aborted) {
          failed.value = true;
        }
      } finally {
        loaded.value = true;
      }
    },
    {immediate: true}
  );

  return {
    trials: computed(() => plugins.value),
    failed: computed(() => failed.value),
    // Without license info there's nothing on trial to look up.
    loaded: computed(
      () => loaded.value || (licenseInfoLoaded.value && !licenseInfo.value)
    ),
  };
}

/** Everything on trial that can be bought: Craft's own edition, then each plugin. */
export function useTrialRows() {
  const page = usePage<{context: PluginStoreContext}>();
  const {licenseInfoFor} = usePluginLicenseInfo();
  const {trials, failed, loaded} = useActiveTrials();
  const cmsEditions = shallowRef<CmsEdition[] | null>(null);

  getCmsEditions()
    .then((response) => (cmsEditions.value = response.editions))
    .catch(() => (cmsEditions.value = []));

  const rows = computed<TrialRow[]>(() =>
    [
      cmsTrialRow(page.props.context, cmsEditions.value ?? [], craftIconUrl),
      ...trials.value.map((plugin) =>
        pluginTrialRow(plugin, licenseInfoFor(plugin.handle))
      ),
    ].filter((row): row is TrialRow => row !== null)
  );

  return {
    rows,
    failed,
    loaded: computed(() => loaded.value && cmsEditions.value !== null),
  };
}
