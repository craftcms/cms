<script setup lang="ts">
  import {t} from '@craftcms/cp/utilities/translate.ts.mjs';
  import AppLayout from '@/layout/AppLayout.vue';
  import {store} from '@/actions/CraftCms/Cms/Http/Controllers/Settings/GeneralSettingsController.js';
  import {
    Edition,
    type SystemData,
    type TimezoneOption,
  } from '@/types/settings';
  import {Form, usePage} from '@inertiajs/vue3';
  import useCraftData from '@/composables/useCraftData';
  import TransitionFade from '@/components/TransitionFade.vue';
  import {computed} from 'vue';

  defineProps<{
    readOnly?: boolean;
    system: SystemData;
    timezones: Array<TimezoneOption>;
    saveUrl: string;
    flash: Record<any, any>;
  }>();

  const page = usePage<{
    flash: {
      success?: string;
      error?: string;
    };
  }>();
  const flash = computed(() => page.props.flash);

  const {app} = useCraftData();
</script>

<template>
  <Form
    :action="store['/admin/settings/general']()"
    method="post"
    #default="{processing, recentlySuccessful}"
  >
    <AppLayout :title="t('app', 'General Settings')">
      <template #actions>
        <TransitionFade>
          <template v-if="recentlySuccessful && flash?.success">
            <div class="tw:flex tw:gap-1 tw:items-center tw:text-sm">
              <craft-icon
                name="circle-check"
                style="color: var(--c-color-success-bg-emphasis)"
              ></craft-icon>
              {{ flash.success }}
            </div>
          </template>
        </TransitionFade>

        <craft-button-group v-if="!readOnly">
          <craft-button type="submit" variant="primary" :loading="processing">
            {{ t('app', 'Save') }}
          </craft-button>
          <craft-button variant="primary" type="button" icon>
            <craft-icon name="chevron-down"></craft-icon>
          </craft-button>
        </craft-button-group>
      </template>
      <div
        class="tw:bg-white tw:border tw:border-border-subtle tw:mx-4 tw:rounded-sm tw:shadow-sm"
      >
        <div class="tw:grid tw:gap-3 tw:p-4">
          <!-- @TODO autosuggest -->
          <craft-input
            :label="t('app', 'System Name')"
            id="name"
            name="name"
            :value="system.name"
            :disabled="readOnly"
          >
            <div slot="after">
              <craft-callout
                variant="info"
                appearance="plain"
                class="tw:p-0"
                icon="lightbulb"
              >
                This can begin with an environment variable.
                <a
                  href="https://craftcms.com/docs/5.x/configure.html#control-panel-settings"
                  >Learn more</a
                >
              </craft-callout>
            </div>
          </craft-input>

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

            <craft-callout
              slot="after"
              variant="info"
              appearance="plain"
              class="tw:p-0"
              icon="lightbulb"
            >
              This can be set to an environment variable with a boolean value
              (<code>yes</code>/<code>no</code>/<code>true</code>/<code>false</code>/<code>on</code>/<code>off</code>/<code>0</code>/<code>1</code>).
            </craft-callout>
          </craft-select>

          <craft-input
            :label="t('app', 'Retry Duration')"
            id="retry-duration"
            name="retryDuration"
            :value="system.retryDuration"
            inputmode="numeric"
            size="4"
            :disabled="readOnly"
          >
            <div slot="help-text">
              The number of seconds that the <code>Retry-After</code> HTTP
              header should be set to for 503 responses when the system is
              offline.
              <!--              {{ t('app', `The number of seconds that the <code>Retry-After</code> HTTP header should be set to for 503 responses when the system is offline.`) }}-->
            </div>
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
            <craft-callout
              slot="after"
              variant="info"
              appearance="plain"
              class="tw:p-0"
              icon="lightbulb"
            >
              This can be set to an environment variable with a value of a
              <a
                href="https://www.php.net/manual/en/timezones.php"
                rel="noopener"
                target="_blank"
                >supported time zone</a
              >.
            </craft-callout>
          </craft-select>
        </div>

        <template v-if="app.edition.value >= Edition.Pro">
          <hr />
          <div class="tw:p-4 tw:grid tw:gap-3">
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
          </div>
        </template>
      </div>
    </AppLayout>
  </Form>
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
