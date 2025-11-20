<script setup lang="ts">
  import {t} from '@craftcms/cp/utilities/translate.ts.mjs';
  import AppLayout from '@/layout/AppLayout.vue';
  import VarDump from '@/components/VarDump.vue';
  import {
    Edition,
    type SystemData,
    type TimezoneOption,
  } from '@/types/settings';
  import {Form} from '@inertiajs/vue3';
  import useCraftData from '@/composables/useCraftData';

  defineProps<{
    readOnly?: boolean;
    system: SystemData;
    timezones: Array<TimezoneOption>;
    save_url: string;
    flash: Record<any, any>
  }>();

  const {app} = useCraftData();
</script>

<template>
  <AppLayout :title="t('app', 'General Settings')">
    <div class="tw:px-4">
      <Form id="settings-form" :action="save_url" method="post">
        <div class="tw:grid tw:gap-3">
          <!-- @TODO autosuggest -->
          <craft-input
            :label="t('app', 'System Name')"
            id="name"
            name="name"
            :value="system.name"
            :disabled="readOnly"
          ></craft-input>

          <craft-select
            :label="t('app', 'System Status')"
            id="live"
            name="live"
            .modelValue="system.live ? '0' : '1'"
            :disabled="readOnly"
          >
            <select slot="input">
              <option value="1">Online</option>
              <option value="0">Offline</option>
            </select>
          </craft-select>

          <craft-input
            :label="t('app', 'Retry Duration')"
            :help-text="
              t(
                'app',
                'The number of seconds that the `Retry-After` HTTP header should be set to for 503 responses when the system is offline.'
              )
            "
            id="retry-duration"
            name="retryDuration"
            :value="system.retryDuration"
            inputmode="numeric"
            size="4"
            :disabled="readOnly"
          >
          </craft-input>

          <craft-select
            :label="t('app', 'Time Zone')"
            id="time-zone"
            name="timeZone"
            .modelValue="system.timeZone"
            :disabled="readOnly"
          >
            <select slot="input">
              <option
                v-for="timezone in timezones"
                :key="timezone.value"
                :value="timezone.value"
              >
                {{ timezone.label }} – {{ timezone.data?.hint }}
              </option>
            </select>
          </craft-select>

          <template v-if="app.edition.value >= Edition.Pro">
            <craft-input
              :label="t('app', 'Site Icon')"
              :help-text="
                t(
                  'app',
                  'Square SVG file recommended. The logo will be displayed at {size} by {size}.',
                  {size: '32px'}
                )
              "
              :disabled="readOnly"
              type="hidden"
            >
              <div slot="before" class="tw:flex tw:gap-2">
                <div class="preview preview--icon"></div>
                <craft-button type="button">{{
                  t('app', 'Upload Icon')
                }}</craft-button>
              </div>
            </craft-input>

            <craft-input
              :label="t('app', 'Login Page Logo')"
              :help-text="
                t(
                  'app',
                  'SVG file recommended. The logo will be displayed at {size} wide.',
                  {size: '288px'}
                )
              "
              :disabled="readOnly"
              type="hidden"
            >
              <div slot="before" class="tw:flex tw:gap-2 tw:items-center">
                <div class="preview preview--logo" style="width: 288px"></div>
                <craft-button type="button">{{
                  t('app', 'Upload Logo')
                }}</craft-button>
              </div>
            </craft-input>
          </template>
        </div>

        <craft-button-group v-if="!readOnly">
          <craft-button type="submit" variant="primary" form="settings-form">
            {{ t('app', 'Save') }}
          </craft-button>
          <craft-button variant="primary" type="button" icon>
            <craft-icon name="chevron-down"></craft-icon>
          </craft-button>
        </craft-button-group>
      </Form>
    </div>
  </AppLayout>
</template>

<style scoped lang="scss">
  .stage {
    padding: var(--c-spacing-md);
  }
  .preview {
    border: 1px solid var(--c-color-neutral-border-subtle);
  }

  .preview--icon {
    aspect-ratio: 1;
    width: 32px;
  }
  .preview--logo {
    aspect-ratio: 16/9;
    width: 288px;
    height: auto;
  }
</style>
