<script setup lang="ts">
  import {t} from '@craftcms/cp/utilities/translate.ts.mjs';
  import AppLayout from '@/layout/AppLayout.vue';
  import {store} from '@/actions/CraftCms/Cms/Http/Controllers/Settings/GeneralSettingsController';
  import {Edition, type SystemData, type TimezoneOption,} from '@/types/settings';
  import {useForm} from '@inertiajs/vue3';
  import useCraftData from '@/composables/useCraftData';
  import TransitionFade from '@/components/TransitionFade.vue';
  import {computed} from 'vue';
  import {useEventListener} from '@vueuse/core';
  import type {SelectOption, SuggestionGroup} from '@/types';

  const props = defineProps<{
    readOnly?: boolean;
    system: SystemData;
    nameSuggestions?: Array<SuggestionGroup>;
    timezoneOptions?: Array<TimezoneOption>;
    systemStatusOptions?: Array<SelectOption>;
    saveUrl: string;
    flash?: Record<any, any>;
    errors: Record<any, any>;
  }>();

  const flash = computed(() => props.flash);
  const errors = computed(() => props.errors);
  const {app} = useCraftData();

  const form = useForm({
    name: props.system.name,
    live: props.system.live,
    retryDuration: props.system.retryDuration,
    timeZone: props.system.timeZone,
  });

  function handleUpdate(event: CustomEvent) {
    const target = event.target as HTMLSelectElement & {modelValue: string};
    if (target) {
      form[target.name] = target.modelValue;
    }
  }

  useEventListener('keydown', (event) => {
    if ((event.metaKey || event.ctrlKey) && event.key === 's') {
      event.preventDefault();
      save();
    }
  });

  function save() {
    form.clearErrors();
    form.submit(store());
  }
</script>

<template>
  <form @submit.prevent="save">
    <AppLayout :title="t('app', 'General Settings')">
      <template #actions>
        <TransitionFade>
          <template v-if="form.recentlySuccessful && flash?.success">
            <div class="flex gap-1 items-center text-sm">
              <craft-icon
                name="circle-check"
                style="color: var(--c-color-success-bg-emphasis)"
              ></craft-icon>
              {{ flash.success }}
            </div>
          </template>
          <template v-if="form.hasErrors">
            <div class="tw:flex tw:gap-1 tw:items-center tw:text-sm">
              <craft-icon
                name="exclamation-triangle"
                style="color: var(--c-color-danger-bg-emphasis)"
              ></craft-icon>
              {{ t('app', 'Could not save settings') }}
            </div>
          </template>
        </TransitionFade>

        <craft-button-group v-if="!readOnly">
          <craft-button
            type="submit"
            variant="primary"
            :loading="form.processing"
          >
            {{ t('app', 'Save') }}
          </craft-button>
          <craft-action-menu>
            <craft-button
              slot="invoker"
              variant="primary"
              type="button"
              icon
              @click="console.error('TODO: Not yet implemented')"
            >
              <craft-icon name="chevron-down"></craft-icon>
            </craft-button>

            <div slot="content">
              <craft-action-item @click="save">
                {{ t('app', 'Save and continue editing') }}
                <span slot="suffix"><code>⌘</code>+<code>s</code></span>
              </craft-action-item>
            </div>
          </craft-action-menu>
        </craft-button-group>
      </template>
      <div
        class="bg-white border border-border-subtle mx-4 rounded-sm shadow-sm"
      >
        <div class="grid gap-3 p-4">
          <template v-if="form.hasErrors">
            <craft-callout variant="danger" icon="exclamation-triangle">
              <div slot="title" class="tw:font-bold">
                Could not save settings
              </div>
              <ul>
                <li v-for="(error, key) in errors">
                  {{ error }}
                </li>
              </ul>
            </craft-callout>
          </template>
          <craft-combobox
            :label="t('app', 'System Name')"
            id="name"
            name="name"
            v-model="form.name"
            :has-feedback-for="errors?.name ? 'error' : ''"
            :disabled="readOnly"
            :require-option-match="false"
            show-all-on-empty
          >
            <template v-for="(group, idx) in nameSuggestions" :key="idx">
              <craft-option
                v-for="suggestion in group.data"
                :key="suggestion.name"
                .choiceValue="suggestion.name"
                .hint="suggestion.hint"
                >{{ suggestion.name }}</craft-option
              >
            </template>
            <div slot="after">
              <craft-callout
                variant="info"
                appearance="plain"
                class="p-0"
                icon="lightbulb"
              >
                This can begin with an environment variable.
                <a
                  href="https://craftcms.com/docs/5.x/configure.html#control-panel-settings"
                  >Learn more</a
                >
              </craft-callout>
            </div>

            <div slot="feedback">
              <ul class="error-list" v-if="errors?.name">
                <li>{{ errors.name }}</li>
              </ul>
            </div>
          </craft-combobox>

          <craft-combobox
            :label="t('app', 'System Status')"
            id="live"
            name="live"
            .modelValue="system.live ? '1' : '0'"
            :has-feedback-for="errors?.live ? 'error' : ''"
            @model-value-changed="handleUpdate"
            :disabled="readOnly"
            show-all-on-empty
          >
            <craft-option .choiceValue="'1'">
              <div class="tw:flex tw:items-center tw:gap-1">
                <craft-indicator variant="success"></craft-indicator>
                <span>Online</span>
              </div>
            </craft-option>
            <craft-option .choiceValue="'0'">
              <div class="tw:flex tw:items-center tw:gap-1">
                <craft-indicator variant="danger"></craft-indicator>
                <span>Offline</span>
              </div>
            </craft-option>

            <template v-for="option in systemStatusOptions" :key="option.label">
              <template v-if="option.optgroup"></template>
              <template v-else>
                <craft-option .choiceValue="option.value">
                  <div class="tw:flex tw:items-center tw:gap-1">
                    <craft-indicator
                      :variant="Boolean(option.value) ? 'success' : 'error'"
                    ></craft-indicator>
                    <span class="tw:font-mono">{{ option.label }}</span>
                  </div>
                </craft-option>
              </template>
            </template>

            <craft-callout
              slot="after"
              variant="info"
              appearance="plain"
              class="p-0"
              icon="lightbulb"
            >
              This can be set to an environment variable with a boolean value
              (<code>yes</code>/<code>no</code>/<code>true</code>/<code>false</code>/<code>on</code>/<code>off</code>/<code>0</code>/<code>1</code>).
            </craft-callout>

            <div slot="feedback">
              <ul class="error-list" v-if="errors.live">
                <li>{{ errors.live }}</li>
              </ul>
            </div>
          </craft-combobox>

          <craft-input
            :label="t('app', 'Retry Duration')"
            id="retry-duration"
            name="retryDuration"
            v-model="form.retryDuration"
            :has-feedback-for="errors?.retryDuration ? 'error' : ''"
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
            <ul class="error-list" v-if="errors?.retryDuration" slot="feedback">
              <li>{{ errors.retryDuration }}</li>
            </ul>
          </craft-input>

          <craft-combobox
            :label="t('app', 'Time Zone')"
            id="time-zone"
            name="timeZone"
            .modelValue="form.timeZone"
            @model-value-changed="handleUpdate"
            :has-feedback-for="errors?.timeZone ? 'error' : ''"
            :disabled="readOnly"
            show-all-on-empty
          >
            <craft-option
              v-for="timezone in timezoneOptions"
              :key="timezone.value"
              .choiceValue="timezone.value"
            >
              {{ timezone.label
              }}{{ timezone.data?.hint ? ` — ${timezone.data.hint}` : '' }}
            </craft-option>
            <craft-callout
              slot="after"
              variant="info"
              appearance="plain"
              class="p-0"
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
            <ul class="error-list" v-if="errors?.timeZone" slot="feedback">
              <li>{{ errors.timeZone }}</li>
            </ul>
          </craft-combobox>
        </div>

        <template v-if="app.edition.value >= Edition.Pro">
          <hr />
          <div class="p-4 grid gap-3">
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
              <div slot="before" class="flex gap-2">
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
              <div slot="before" class="flex gap-2 items-center">
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
  </form>
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
