<script setup lang="ts">
  import {t} from '@craftcms/cp';
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
  import CalloutReadOnly from '@/components/CalloutReadOnly.vue';

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
      // @ts-ignore we're just going to trust that `name` is a key of `form` for now
      form[target.name] = target.modelValue;
    }
  }

  // Handle cmd + s events
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
    <AppLayout :title="t('General Settings')">
      <template #actions>
        <TransitionFade>
          <template v-if="form.recentlySuccessful && flash?.success">
            <div class="flex gap-1 items-center text-sm">
              <craft-icon
                name="circle-check"
                style="color: var(--c-color-success-fill-loud)"
              ></craft-icon>
              {{ flash.success }}
            </div>
          </template>
          <template v-if="form.hasErrors">
            <div class="tw:flex tw:gap-1 tw:items-center tw:text-sm">
              <craft-icon
                name="triangle-exclamation"
                style="color: var(--c-color-danger-fill-loud)"
              ></craft-icon>
              {{ t('Could not save settings') }}
            </div>
          </template>
        </TransitionFade>

        <craft-button-group v-if="!readOnly">
          <craft-button
            type="submit"
            variant="primary"
            :loading="form.processing"
          >
            {{ t('Save') }}
          </craft-button>
          <craft-action-menu>
            <craft-button slot="invoker" variant="primary" type="button" icon>
              <craft-icon name="chevron-down"></craft-icon>
            </craft-button>

            <div slot="content">
              <craft-action-item @click="save">
                {{ t('Save and continue editing') }}
                <craft-shortcut slot="suffix" class="ml-2">S</craft-shortcut>
              </craft-action-item>
            </div>
          </craft-action-menu>
        </craft-button-group>
      </template>

      <div
        class="bg-white border border-neutral-border-quiet rounded-sm shadow-sm"
      >
        <template v-if="readOnly">
          <CalloutReadOnly />
        </template>
        <div class="grid gap-3 p-5">
          <template v-if="form.hasErrors">
            <craft-callout variant="danger" icon="triangle-exclamation">
              <div slot="title" class="tw:font-bold">
                {{ t('Could not save settings') }}
              </div>
              <ul>
                <li v-for="(error, key) in errors">
                  {{ error }}
                </li>
              </ul>
            </craft-callout>
          </template>
          <craft-combobox
            :label="t('System Name')"
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
                {{ t('This can begin with an environment variable.') }}
                <a
                  href="https://craftcms.com/docs/5.x/configure.html#control-panel-settings"
                  >{{ t('Learn more') }}</a
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
            :label="t('System Status')"
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
                <span>{{ t('Online') }}</span>
              </div>
            </craft-option>
            <craft-option .choiceValue="'0'">
              <div class="tw:flex tw:items-center tw:gap-1">
                <craft-indicator variant="danger"></craft-indicator>
                <span>{{ t('Offline') }}</span>
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
              v-html="
                t(
                  'This can be set to an environment variable with a boolean value ({examples})',
                  {
                    examples:
                      '<code>yes</code>/<code>no</code>/<code>true</code>/<code>false</code>/<code>on</code>/<code>off</code>/<code>0</code>/<code>1</code>',
                  }
                )
              "
            >
            </craft-callout>

            <div slot="feedback">
              <ul class="error-list" v-if="errors.live">
                <li>{{ errors.live }}</li>
              </ul>
            </div>
          </craft-combobox>

          <craft-input
            :label="t('Retry Duration')"
            id="retry-duration"
            name="retryDuration"
            v-model="form.retryDuration"
            :has-feedback-for="errors?.retryDuration ? 'error' : ''"
            inputmode="numeric"
            maxlength="4"
            :disabled="readOnly"
          >
            <div
              slot="help-text"
              v-html="
                t(
                  'The number of seconds that the <code>Retry-After</code> HTTP header should be set to for 503 responses when the system is offline.'
                )
              "
            ></div>
            <ul class="error-list" v-if="errors?.retryDuration" slot="feedback">
              <li>{{ errors.retryDuration }}</li>
            </ul>
          </craft-input>

          <craft-combobox
            :label="t('Time Zone')"
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
              :label="t('Site Icon')"
              name="siteIcon"
              v-model="form.siteIcon"
              :help-text="
                t(
                  'Square SVG file recommended. The logo will be displayed at {size} by {size}.',
                  {size: '32px'}
                )
              "
              :thumbnail-size="32"
              :disabled="readOnly"
              :error="form.errors.siteIcon"
            />

            <FileUpload
              :label="t('Login Page Logo')"
              v-model="form.siteLogo"
              name="siteLogo"
              :help-text="
                t(
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
    border: 1px solid var(--c-color-neutral-border-quiet);
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
