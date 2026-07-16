<script setup lang="ts">
  import {t} from '@craftcms/cp';
  import {useAppLayout} from '@/common/composables/useAppLayout';
  import {store} from '@/actions/CraftCms/Cms/Http/Controllers/Settings/GeneralSettingsController';
  import {type SystemData} from '@/modules/user/types/settings';
  import {useForm} from '@inertiajs/vue3';
  import {computed} from 'vue';
  import type {SelectItem} from '@/common/types';
  import {useSettingsSave} from '@/modules/settings/composables/useSettingsSave';
  import Pane from '@/common/components/Pane.vue';
  import CraftCombobox from '@/common/form/CraftCombobox.vue';
  import CraftInput from '@craftcms/cp/vue/CraftInput.vue';
  import {transformBooleanOptions} from '@/common/utils/transformBooleanOptions';
  import useCraftData from '@/common/composables/useCraftData';

  const props = withDefaults(
    defineProps<{
      system: SystemData;
      nameSuggestions?: Array<SelectItem>;
      timezoneOptions?: Array<SelectItem>;
      systemStatusOptions?: Array<SelectItem>;
      flash?: Record<any, any>;
      errors: Record<any, any>;
    }>(),
    {
      systemStatusOptions: () => [],
      timezoneOptions: () => [],
      nameSuggestions: () => [],
    }
  );

  const errors = computed(() => props.errors);
  const {readOnly} = useCraftData();

  const form = useForm({
    name: props.system.name ?? '',
    live: props.system.live,
    retryDuration: props.system.retryDuration,
    timeZone: props.system.timeZone,
  });

  const {save} = useSettingsSave(form, store);

  useAppLayout({title: t('General Settings'), form, onSave: save});

  const systemStatusOptions = computed(() => {
    return transformBooleanOptions(props.systemStatusOptions, {
      trueLabel: t('Online'),
      falseLabel: t('Offline'),
    });
  });

  const liveOptions = computed(() => {
    return [
      {
        value: '1',
        label: t('Online'),
        data: {
          indicator: {variant: 'success'},
        },
      },
      {
        value: '0',
        label: t('Offline'),
        data: {
          indicator: {variant: 'empty'},
        },
      },
      ...systemStatusOptions.value,
    ];
  });
</script>

<template>
  <Pane appearance="raised">
    <div class="grid gap-3">
      <CraftCombobox
        :label="t('System Name')"
        id="name"
        name="name"
        v-model="form.name"
        :has-feedback-for="errors?.name ? 'error' : ''"
        :disabled="readOnly"
        :require-option-match="false"
        show-all-on-empty
        :options="nameSuggestions"
        :error="errors?.name"
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
        :options="liveOptions"
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
        :error="errors?.timeZone"
        :disabled="readOnly"
        show-all-on-empty
        :options="timezoneOptions"
      >
        <template #item="{item}">
          {{ item.label }}{{ item.data?.hint ? ` — ${item.data.hint}` : '' }}
        </template>
        <template #after>
          <craft-callout
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
  </Pane>
</template>
