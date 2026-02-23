<script setup lang="ts">
  import {t} from '@craftcms/cp/utilities/translate.ts.mjs';
  import Release from '@/components/utilities/Updates/Release.vue';
  import {computed, ref} from 'vue';
  import CpLink from '@/components/cp/CpLink/CpLink.vue';
  import UpdaterController from '@actions/Updates/UpdaterController';
  import {Form, router} from '@inertiajs/vue3';

  interface ReleaseInfo {
    version: string;
    date: string | null;
    critical: boolean;
    notes: string | null;
  }

  const props = withDefaults(
    defineProps<{
      name?: string;
      handle?: string;
      packageName?: string;
      releases?: ReleaseInfo[];
      status?: string;
      statusText?: string;
      abandoned?: boolean;
      latestVersion?: string | null;
      ctaText?: string;
      ctaUrl?: string | false;
      altCtaText?: string;
      altCtaUrl?: string;
      allowUpdates?: boolean;
    }>(),
    {
      releases: () => [],
      status: 'eligible',
      abandoned: false,
      allowUpdates: true,
    }
  );

  // Computed: should we show the update button?
  const showUpdateCta = computed(() => {
    return (
      props.allowUpdates &&
      props.latestVersion &&
      typeof props.ctaUrl !== 'undefined'
    );
  });

  // Computed: CTA button text
  const ctaButtonText = computed(() => {
    return props.ctaText || t('Update');
  });

  // Loading state for update button
  const isUpdating = ref(false);

  // Start the update process
  function startUpdate() {
    if (!props.handle || !props.latestVersion || !props.packageName) {
      return;
    }

    isUpdating.value = true;

    router.post(
      UpdaterController.index['/admin/actions/updater'](),
      {
        return: 'utilities/updates',
        install: {[props.handle]: `^${props.latestVersion}`},
        packageNames: {[props.handle]: props.packageName},
      },
      {
        onFinish: () => {
          isUpdating.value = false;
        },
      }
    );
  }

  // Copy handle to clipboard
  const initialHandleLabel = t('Copy plugin handle');
  const copyHandleLabel = ref(initialHandleLabel);

  async function copyHandle() {
    try {
      await navigator.clipboard.writeText(props.handle ?? '');
      copyHandleLabel.value = t('Copied!');
      setTimeout(() => {
        copyHandleLabel.value = initialHandleLabel;
      }, 1500);
    } catch (e) {
      copyHandleLabel.value = t('Failed to copy');
    }
  }

  // Copy package name to clipboard
  const initialPackageLabel = t('Copy package name');
  const copyPackageLabel = ref(initialPackageLabel);

  async function copyPackage() {
    try {
      await navigator.clipboard.writeText(props.packageName ?? '');
      copyPackageLabel.value = t('Copied!');
      setTimeout(() => {
        copyPackageLabel.value = initialPackageLabel;
      }, 1500);
    } catch (e) {
      copyPackageLabel.value = t('Failed to copy');
    }
  }
</script>

<template>
  <div class="update">
    <div class="update-header">
      <h2 class="text-xl font-semibold">{{ name }}</h2>

      <div class="update-actions">
        <!-- Primary CTA -->
        <template v-if="showUpdateCta">
          <!-- External URL CTA -->
          <CpLink
            v-if="ctaUrl"
            :href="ctaUrl"
            target="_blank"
            variant="primary"
            appearance="button"
          >
            {{ ctaButtonText }}
          </CpLink>
          <!-- Update button -->
          <Form
            v-else
            :action="UpdaterController.index['/admin/actions/updater']()"
            method="post"
            #default="{processing}"
          >
            <input type="hidden" name="return" value="utilities/updates" />
            <input
              type="hidden"
              :name="`install[${handle}]`"
              :value="`^${latestVersion}`"
            />
            <input
              type="hidden"
              :name="`packageNames[${handle}]`"
              :value="packageName"
            />
            <craft-button type="submit" variant="primary" :loading="processing">
              {{ ctaButtonText }}
            </craft-button>
          </Form>
        </template>

        <!-- Alternative CTA -->
        <template v-if="allowUpdates && altCtaText">
          <!-- External URL Alt CTA -->
          <CpLink
            v-if="altCtaUrl"
            :href="altCtaUrl"
            appearance="button"
            variant="default"
          >
            {{ altCtaText }}
          </CpLink>
        </template>

        <!-- Action Menu -->
        <craft-action-menu>
          <craft-button type="button" slot="invoker" icon>
            <craft-icon name="ellipsis" :label="t('Actions')"></craft-icon>
          </craft-button>

          <div slot="content">
            <craft-action-item icon="clipboard" @click="copyHandle">
              {{ copyHandleLabel }}
            </craft-action-item>
            <craft-action-item icon="clipboard" @click="copyPackage">
              {{ copyPackageLabel }}
            </craft-action-item>
          </div>
        </craft-action-menu>
      </div>
    </div>

    <!-- Abandoned Notice -->
    <blockquote v-if="abandoned" class="note">
      <p>{{ statusText }}</p>
    </blockquote>

    <!-- Ineligible Notice (expired license, PHP issues, etc.) -->
    <blockquote v-else-if="status !== 'eligible'" class="note ineligible">
      <p>{{ statusText }}</p>
    </blockquote>

    <!-- Releases -->
    <div class="releases">
      <Release
        v-for="release in releases"
        :key="release.version"
        v-bind="release"
      />
    </div>
  </div>
</template>

<style scoped lang="scss">
  .update {
  }

  .update-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding-block-end: var(--c-spacing-md);
    border-block-end: 1px solid var(--c-border-subtle);
    margin-block-end: var(--c-spacing-lg);
  }

  .update-actions {
    display: flex;
    gap: var(--c-spacing-md);
    align-items: center;
  }

  .note {
    background: var(--c-color-warning-bg-subtle);
    border-left: 3px solid var(--c-color-warning-border);
    padding: var(--c-spacing-md);
    margin-block-end: var(--c-spacing-md);
    border-radius: var(--c-radius-sm);

    &.ineligible {
      background: var(--c-color-neutral-bg-subtle);
      border-left-color: var(--c-color-neutral-border);
    }

    p {
      margin: 0;
    }
  }

  .releases {
    display: grid;
    gap: var(--c-spacing-sm);
  }
</style>
