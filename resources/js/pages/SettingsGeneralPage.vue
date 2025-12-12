<script setup lang="ts">
  import {t} from '@craftcms/cp/utilities/translate.ts.mjs';
  import AppLayout from '@/layout/AppLayout.vue';
  import {store} from '@/actions/CraftCms/Cms/Http/Controllers/Settings/GeneralSettingsController';
  import {
    Edition,
    type SystemData,
    type TimezoneOption,
  } from '@/types/settings';
  import {useForm} from '@inertiajs/vue3';
  import useCraftData from '@/composables/useCraftData';
  import TransitionFade from '@/components/TransitionFade.vue';
  import {computed} from 'vue';
  import {useEventListener} from '@vueuse/core';
  import type {SelectOption, SuggestionGroup} from '@/types';
  import FileUpload from '@/components/FileUpload.vue';

  const props = defineProps<{
    readOnly?: boolean;
    system: SystemData;
    nameSuggestions?: Array<SuggestionGroup>;
    timezoneOptions?: Array<TimezoneOption>;
    systemStatusOptions?: Array<SelectOption>;
    siteIcon?: any;
    siteLogo?: any;
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
    siteIcon: props.siteIcon,
    siteLogo: props.siteLogo,
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
    form
      .transform((data) => {
        /**
         * I'm not convinced this is the right approach but it works for the moment.
         *
         * When you first upload a file, we get a `File` object, that gets passed
         * to the server and processed. All is well there.
         *
         * When we display the file you've uploaded, we send back an array of just
         * the URL and the filename. We then use that information to set the
         * uploadResponses on `craft-file-input` which is how we pre-fill the form.
         *
         * This means that the URL and name get sent up to the server on updates
         * but the server doesn't know what to do with that. Instead of adding
         * logic on the server, we just remove the `siteIcon` and `siteLogo`
         * if they're not an instance of File and therefore weren't uploaded
         * in this request.
         */
        if (data.siteIcon !== null && !(data.siteIcon instanceof File)) {
          delete data.siteIcon;
        }

        if (data.siteLogo !== null && !(data.siteLogo instanceof File)) {
          delete data.siteLogo;
        }

        return data;
      })
      .clearErrors()
      .submit(store());
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
        <template v-if="readOnly">
          <craft-callout
            appearance="fill"
            rounded="start"
            class="border border-b-border-subtle"
          >
            <span slot="icon" class="c-icon">
              <!-- @TODO replace this once we have our own icon system in place -->
              <svg
                xmlns="http://www.w3.org/2000/svg"
                viewBox="0 0 640 512"
                width="1em"
                height="1em"
              >
                <path
                  d="M630.8 469.1l-95.4-74.8c1.4-2.1 2.7-4.3 4-6.5l4.7-8.1c6.1-11 11.4-22.4 15.8-34.3c3.2-8.7 .5-18.4-6.4-24.6l-43.3-39.4c1.1-8.3 1.7-16.8 1.7-25.4s-.6-17.1-1.7-25.4l43.3-39.4c6.9-6.2 9.6-15.9 6.4-24.6h.1c-4.4-12-9.7-23.4-15.8-34.4l-4.7-8.1c-6.6-11-14-21.4-22.1-31.2c-5.9-7.1-15.7-9.6-24.5-6.8l-55.7 17.7c-13.4-10.3-28.2-18.9-44-25.4l-12.5-57.1c-2-9.1-9-16.3-18.2-17.8C348.8 1.2 334.5 0 320 0s-28.7 1.2-42.5 3.6c-9.2 1.5-16.2 8.7-18.2 17.8l-12.5 57.1c-15.8 6.5-30.6 15.1-44 25.4l-55.7-17.7c-2-.6-4.1-1-6.2-1.1L38.8 5.1C28.4-3.1 13.3-1.2 5.1 9.2S-1.2 34.7 9.2 42.9l592 464c10.4 8.2 25.5 6.3 33.7-4.1s6.3-25.5-4.1-33.7zM320 176c44.2 0 80 35.8 80 80s-1.8 19.4-5.1 28.2l-120.1-94.1c12.9-8.8 28.4-14 45.2-14zM247.4 289.6L82.5 160.3c-.8 2.1-1.7 4.2-2.4 6.3c-3.2 8.7-.5 18.4 6.4 24.6l43.3 39.4c-1.1 8.3-1.7 16.8-1.7 25.4s.6 17.1 1.7 25.5l-43.3 39.4c-6.9 6.2-9.6 15.9-6.4 24.6c4.4 11.9 9.7 23.3 15.8 34.3l4.7 8.1c6.6 11 14 21.4 22.1 31.2c5.9 7.1 15.7 9.6 24.5 6.8l55.6-17.8c13.4 10.3 28.2 18.9 44 25.4l12.5 57.1c2 9.1 9 16.3 18.2 17.8c13.8 2.3 28 3.5 42.5 3.5s28.7-1.2 42.5-3.5c9.2-1.5 16.2-8.7 18.2-17.8l12.5-57.1c8-3.3 15.8-7.2 23.3-11.5l-111.6-87.5c-25.5-4.9-46.7-22-57.4-45z"
                />
              </svg>
            </span>
            {{
              t(
                'app',
                'Changes to these settings aren’t permitted in this environment.'
              )
            }}
          </craft-callout>
        </template>
        <div class="grid gap-3 p-3">
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
            <FileUpload
              :label="t('app', 'Site Icon')"
              name="siteIcon"
              v-model="form.siteIcon"
              :help-text="
                t(
                  'app',
                  'Square SVG file recommended. The logo will be displayed at {size} by {size}.',
                  {size: '32px'}
                )
              "
              :thumbnail-size="32"
              :disabled="readOnly"
              :error="form.errors.siteIcon"
            />

            <FileUpload
              :label="t('app', 'Login Page Logo')"
              v-model="form.siteLogo"
              name="siteLogo"
              :help-text="
                t(
                  'app',
                  'SVG file recommended. The logo will be displayed at {size} wide.',
                  {size: '288px'}
                )
              "
              :disabled="readOnly"
              :thumbnail-size="288"
              :error="form.errors.siteLogo"
            />
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
