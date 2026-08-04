<script setup lang="ts">
  import type {
    CmsLicenseData,
    PluginInfo,
    PluginLicenseResponseData,
  } from '@/modules/plugin-manager/types/plugins';
  import PluginsList from '@/modules/plugin-manager/components/PluginsList.vue';
  import {useHttp} from '@inertiajs/vue3';
  import {useApiClient} from '@/common/composables/useFetch';
  import {computed, ref, watch} from 'vue';
  import PluginsController from '@actions/App/PluginsController';

  const props = withDefaults(
    defineProps<{
      pluginInfo: Record<string, PluginInfo>;
      readOnly?: boolean;
    }>(),
    {pluginInfo: () => ({}), readOnly: false}
  );

  // Immediately makes a request to get the cms-license
  const {data: cmsLicenseData} = useApiClient<{license: CmsLicenseData}>(
    'cms-licenses'
  );

  const pluginLicenseData = ref<Record<string, PluginLicenseResponseData>>({});
  const pluginLicenses = computed(() => {
    return cmsLicenseData.value?.license.pluginLicenses || [];
  });

  const http = useHttp<
    {pluginLicenses: Array<any>},
    Record<string, PluginLicenseResponseData>
  >({
    pluginLicenses: pluginLicenses.value,
  });

  // After the cms license response comes back, check the plugin licenses
  watch(pluginLicenses, () => {
    http.post(PluginsController.getLicenseInfo().url, {
      onSuccess: (data) => {
        pluginLicenseData.value = data;
      },
    });
  });

  // Merge the plugin license data with our initial plugin data for rendering.
  const fullPluginInfo = computed(() => {
    return Object.fromEntries(
      Object.entries(props.pluginInfo).map(([key, value]) => [
        key,
        {
          ...value,
          ...pluginLicenseData.value[key],
        },
      ])
    );
  });
</script>

<template>
  <PluginsList :read-only="readOnly" :plugin-info="fullPluginInfo" />
</template>
