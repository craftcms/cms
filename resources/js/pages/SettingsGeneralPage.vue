<script setup lang="ts">
  import {t} from '@craftcms/cp';
  import AppLayout from '@/layout/AppLayout.vue';
  import {store} from '@/actions/CraftCms/Cms/Http/Controllers/Settings/GeneralSettingsController';
  import {type SystemData} from '@/types/settings';
  import {useForm} from '@inertiajs/vue3';
  import useCraftData from '@/composables/useCraftData';
  import TransitionFade from '@/components/TransitionFade.vue';
  import {computed} from 'vue';
  import {useEventListener} from '@vueuse/core';
  import type {SelectItem} from '@/types';
  import CalloutReadOnly from '@/components/CalloutReadOnly.vue';
  import CraftCombobox from '@/components/form/CraftCombobox.vue';
  import CraftInput from '@craftcms/cp/vue/CraftInput.vue';
  import {transformBooleanOptions} from '@/utils/transformBooleanOptions';

  const props = defineProps<{
    system: SystemData;
    nameSuggestions?: Array<SelectItem>;
    timezoneOptions?: Array<SelectItem>;
    systemStatusOptions?: Array<SelectItem>;
    flash?: Record<any, any>;
    errors: Record<any, any>;
  }>();

  const flash = computed(() => props.flash);
  const errors = computed(() => props.errors);
  const {readOnly} = useCraftData();

  const form = useForm({
    name: props.system.name ?? '',
    live: props.system.live,
    retryDuration: props.system.retryDuration,
    timeZone: props.system.timeZone,
  });

  // Handle cmd + s events
  useEventListener('keydown', (event) => {
    if ((event.metaKey || event.ctrlKey) && event.key === 's') {
      event.preventDefault();
      save();
    }
  });

  const statusOptions = computed(() => {
    return [
      {
        label: t('Online'),
        value: true,
        data: {
          indicator: {variant: 'success'},
        },
      },
      {
        label: t('Offline'),
        value: false,
        data: {
          indicator: {variant: 'empty'},
        },
      },
      ...transformBooleanOptions(props.systemStatusOptions ?? [], {
        trueLabel: t('Online'),
        falseLabel: t('Offline'),
      }),
    ];
  });

  function save() {
    form.clearErrors().submit(store());
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
            variant="accent"
            :loading="form.processing"
          >
            {{ t('Save') }}
          </craft-button>
          <craft-action-menu>
            <craft-button slot="invoker" variant="accent" type="button" icon>
              <craft-icon
                name="chevron-down"
                :label="t('More actions')"
              ></craft-icon>
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

          <CraftCombobox
            :label="t('System Name')"
            id="name"
            name="name"
            v-model="form.name"
            :error="errors.name"
            :disabled="readOnly"
            :require-option-match="false"
            show-all-on-empty
            :options="nameSuggestions"
          >
            <template #after>
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
            </template>
          </CraftCombobox>

          <CraftCombobox
            :label="t('System Status')"
            id="live"
            name="live"
            v-model="form.live"
            :error="errors?.live"
            :disabled="readOnly"
            show-all-on-empty
            :options="statusOptions"
          >
            <template #after>
              <craft-callout
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
            </template>
          </CraftCombobox>

          <CraftInput
            :label="t('Retry Duration')"
            id="retry-duration"
            name="retryDuration"
            v-model="form.retryDuration"
            :error="errors?.retryDuration"
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
          </CraftInput>

          <CraftCombobox
            :label="t('Time Zone')"
            id="time-zone"
            name="timeZone"
            v-model="form.timeZone"
            :error="errors.timeZone"
            :disabled="readOnly"
            show-all-on-empty
            :options="timezoneOptions"
          >
            <template #after>
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
            </template>
          </CraftCombobox>
        </div>
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
