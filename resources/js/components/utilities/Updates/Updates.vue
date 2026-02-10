<script setup lang="ts">
  import {t} from '@craftcms/cp/utilities/translate.ts.mjs';
  import {computed, ref} from 'vue';
  import {useApiClient} from '@/composables/useFetch';
  import Empty from '@/components/Empty.vue';
  import Update from '@/components/utilities/Updates/Update.vue';
  import rawDummyData from './dummyData.js';
  import {Form} from '@inertiajs/vue3';
  import UpdaterController from '@actions/Updates/UpdaterController';

  // Set to true to use dummy data for testing all variations
  const USE_DUMMY_DATA = false;

  // ============================================================================
  // Type Definitions
  // ============================================================================

  interface Release {
    version: string;
    date: string | null;
    critical: boolean;
    notes: string | null;
  }

  interface UpdateInfo {
    status: string;
    statusText?: string;
    renewalPrice: string | null;
    renewalCurrency: string | null;
    renewalUrl: string | null;
    releases: Release[];
    phpConstraint: string | null;
    packageName: string;
    abandoned: boolean;
    replacementName: string | null;
    replacementHandle: string | null;
    replacementUrl: string | null;
    handle: string;
    name: string;
    latestVersion: string | null;
    ctaText?: string;
    ctaUrl?: string | false;
    altCtaText?: string;
    altCtaUrl?: string;
  }

  interface UpdatesResponse {
    total: number;
    critical: boolean;
    allowUpdates: boolean;
    updates: {
      cms: UpdateInfo;
      plugins: UpdateInfo[];
    };
  }

  // ============================================================================
  // State & API
  // ============================================================================

  const {
    data: apiData,
    isLoading,
    isError,
    isSuccess,
  } = useApiClient<UpdatesResponse>('updates', {
    params: {
      forceRefresh: true,
      includeDetails: true,
    },
  });

  // ============================================================================
  // Dummy Data for Testing
  // ============================================================================

  const dummyData = ref<UpdatesResponse>(rawDummyData);

  // Use dummy data or API data based on flag
  const data = computed(() =>
    USE_DUMMY_DATA ? dummyData.value : apiData.value
  );

  // ============================================================================
  // Computed Properties
  // ============================================================================

  const allowUpdates = computed(() => data.value?.allowUpdates ?? false);

  const cmsUpdate = computed(() => {
    const cms = data.value?.updates?.cms;
    if (!cms) {
      return null;
    }
    if (cms.releases.length === 0 && !cms.abandoned) {
      return null;
    }
    return cms;
  });

  const pluginUpdates = computed(() => {
    const plugins = data.value?.updates?.plugins ?? [];
    return plugins.filter((p) => p.releases.length > 0 || p.abandoned);
  });

  const showUpdates = computed(() => {
    return cmsUpdate.value !== null || pluginUpdates.value.length > 0;
  });

  const availableUpdatesCount = computed(() => {
    let count = 0;
    if (cmsUpdate.value && isAvailable(cmsUpdate.value)) {
      count++;
    }
    pluginUpdates.value.forEach((p) => {
      if (isAvailable(p)) {
        count++;
      }
    });
    return count;
  });

  const installableUpdates = computed(() => {
    const updates: UpdateInfo[] = [];
    if (cmsUpdate.value && isInstallable(cmsUpdate.value)) {
      updates.push(cmsUpdate.value);
    }
    pluginUpdates.value.forEach((p) => {
      if (isInstallable(p)) {
        updates.push(p);
      }
    });
    return updates;
  });

  const headingText = computed(() => {
    return t(
      '{num, plural, =1{# Available Update} other{# Available Updates}}',
      {
        num: availableUpdatesCount.value,
      }
    );
  });

  // ============================================================================
  // Helper Functions
  // ============================================================================

  function isInstallable(update: UpdateInfo): boolean {
    if (update.status === 'phpIssue' || update.status === 'expired') {
      return false;
    }
    if (!update.releases.length) {
      return false;
    }
    if (update.latestVersion === null) {
      return false;
    }
    return true;
  }

  function isAvailable(update: UpdateInfo): boolean {
    if (update.status === 'phpIssue') {
      return false;
    }
    if (!update.releases.length) {
      return false;
    }
    if (update.latestVersion === null) {
      return false;
    }
    return true;
  }
</script>

<template>
  <!-- Loading State -->
  <Empty v-if="isLoading" :label="t('Checking for updates…')">
    <template #graphic>
      <craft-spinner style="--size: 3rem" :visible="true"></craft-spinner>
    </template>
  </Empty>

  <!-- Error State -->
  <Empty
    v-else-if="isError"
    icon="alert-circle"
    :label="t('Unable to fetch updates at this time.')"
  />

  <!-- Success State -->
  <template v-else-if="isSuccess">
    <!-- No Updates Available -->
    <Empty
      v-if="!showUpdates"
      icon="check"
      :label="t('You’re all up to date!')"
    />

    <!-- Updates Available -->
    <div v-else class="updates-utility">
      <!-- Header with Update All button -->
      <div
        v-if="allowUpdates && installableUpdates.length > 1"
        class="updates-header"
      >
        <h1 class="text-2xl font-semibold">{{ headingText }}</h1>
        <Form
          method="post"
          :action="UpdaterController.index['/admin/actions/updater']()"
        >
          <input type="hidden" name="return" value="utilities/updates" />
          <template
            v-for="update in installableUpdates"
            :key="`all-${update.handle}`"
          >
            <input
              type="hidden"
              :name="`install[${update.handle}]`"
              :value="`^${update.latestVersion}`"
            />
            <input
              type="hidden"
              :name="`packageNames[${update.handle}]`"
              :value="update.packageName"
            />
          </template>
          <craft-button type="submit" variant="primary">
            {{ t('Update all') }}
          </craft-button>
        </Form>
      </div>

      <!-- Updates Grid -->
      <div class="updates-grid">
        <!-- CMS Update -->
        <Update
          v-if="cmsUpdate"
          v-bind="cmsUpdate"
          :allowUpdates="allowUpdates"
        />

        <!-- Plugin Updates -->
        <Update
          v-for="plugin in pluginUpdates"
          :key="plugin.handle"
          v-bind="plugin"
          :allowUpdates="allowUpdates"
        />
      </div>
    </div>
  </template>
</template>

<style scoped lang="scss">
  .updates-utility {
    padding: var(--c-spacing-lg);
  }

  .updates-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-block-end: var(--c-spacing-xl);
  }

  .updates-grid {
    display: grid;
    gap: var(--c-spacing-xl);
  }

  /**
  Compat styles because markup comes from Craftnet/the API and we don't want
  to change the markup right now
  */
  :deep(blockquote) {
    margin: 0;
    padding: 0.5em 1em;
    border-radius: var(--c-radius-md);
    border: 1px solid var(--c-color-neutral-border-subtle);
    background-color: var(--c-color-neutral-bg-subtle);
    color: var(--c-color-neutral-on-subtle);
  }

  :deep(blockquote.warning) {
    border: 1px solid var(--c-color-warning-border-subtle);
    background-color: var(--c-color-warning-bg-subtle);
    color: var(--c-color-warning-on-subtle);
  }

  :deep(blockquote.note) {
    border: 1px solid var(--c-color-info-border-subtle);
    background-color: var(--c-color-info-bg-subtle);
    color: var(--c-color-info-on-subtle);
  }

  :deep(blockquote.note.ineligible) {
    border: 1px solid var(--c-color-warning-border-subtle);
    background-color: var(--c-color-warning-bg-subtle);
    color: var(--c-color-warning-on-subtle);
  }
</style>
