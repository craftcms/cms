<script setup lang="ts">
  import {t, ButtonVariant} from '@craftcms/ui';
  import CpLink from '@/common/components/CpLink.vue';
  import type {PluginInfo} from '@/modules/plugin-manager/types/plugins';
  import {useForm, usePage} from '@inertiajs/vue3';
  import {computed} from 'vue';
  import CraftInput from '@craftcms/ui/vue/CraftInput.vue';
  import PluginsController from '@actions/App/PluginsController';

  const props = defineProps<{
    plugin: PluginInfo;
  }>();

  const page = usePage<{
    readOnly: boolean;
  }>();

  const form = useForm({
    handle: props.plugin.handle,
    key: formatLicenseKey(props.plugin.licenseKey) ?? '',
  });

  const rawValue = computed(() => form.key?.replace(/-/g, ''));

  function formatLicenseKey(key: string | null): string | null {
    if (!key || key.startsWith('$')) {
      return key; // Environment variables displayed as-is
    }

    return key
      .replace(/[^a-zA-Z0-9]/g, '') // Remove non-alphanumeric
      .toUpperCase()
      .replace(/.{4}/g, '$&-')
      .replace(/-$/, '');
  }

  const displayValue = computed(() => {
    return formatLicenseKey(form.key);
  });

  const readOnly = computed(
    () => page.props.readOnly || !props.plugin.isComposerInstalled
  );
  const showBuyButton = computed(
    () =>
      !readOnly.value &&
      props.plugin.buyUrl &&
      props.plugin.licenseKeyStatus === 'trial'
  );

  const showUpdateButton = computed(() => {
    return (
      !readOnly.value &&
      ((!props.plugin.licenseKey && form.key) ||
        (props.plugin.licenseKey && rawValue.value !== props.plugin.licenseKey))
    );
  });

  function updatePluginLicense() {
    form
      .transform((data) => {
        return {
          ...data,
          key: rawValue.value,
        };
      })
      .submit(PluginsController.updateLicense());
  }

  function handleBlur() {
    form.key = formatLicenseKey(displayValue.value) ?? '';
  }
</script>

<template>
  <form @submit.prevent="updatePluginLicense()">
    <div class="flex gap-1 items-start mb-1">
      <CraftInput
        v-model="form.key"
        class="font-mono"
        :label="t('License Key')"
        label-sr-only
        placeholder="XXXX-XXXX-XXXX-XXXX-XXXX-XXXX"
        :readonly="readOnly"
        maxlength="29"
        @blur="handleBlur"
        :style="{
          width: `36ch`,
        }"
      >
        <craft-copy-button slot="suffix" :value="rawValue"></craft-copy-button>
      </CraftInput>

      <template v-if="showBuyButton">
        <CpLink
          appearance="button"
          :inertia="false"
          :href="plugin.buyUrl"
          :variant="plugin.licenseIssues.length > 0 ? 'accent' : 'neutral'"
          >{{ t('Buy now') }}</CpLink
        >
      </template>
      <template v-if="showUpdateButton">
        <craft-button
          type="submit"
          :loading="form.processing"
          :variant="ButtonVariant.Fill"
        >
          {{ t('Update') }}
        </craft-button>
      </template>
    </div>
  </form>
</template>

<style scoped lang="scss"></style>
