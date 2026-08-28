<script setup lang="ts">
  import {computed} from 'vue';
  import {t} from '@craftcms/ui';
  import {useForm, usePage} from '@inertiajs/vue3';
  import CraftCombobox from '@/common/form/CraftCombobox.vue';
  import {useAppLayout} from '@/common/composables/useAppLayout';
  import Select from '@/common/form/Select.vue';
  import CheckboxGroup from '@/common/form/CheckboxGroup.vue';
  import HtmlFragmentRenderer from '@/common/components/HtmlFragmentRenderer.vue';
  import {useSettingsSave} from '@/modules/settings/composables/useSettingsSave';
  import {update} from '@actions/Users/PreferencesController';
  import UserScreen from '@/modules/user/components/UserScreen.vue';

  defineOptions({
    inheritAttrs: false,
  });

  type UserPreferencesViewModel =
    CraftCms.Cms.Http.ViewModels.UserPreferencesViewModel & {
      prefsHook?: CraftCms.Cms.View.HtmlFragment | null;
    };

  interface UserPreferencesForm {
    preferredLanguage: string;
    preferredLocale: string;
    weekStartDay: string;
    timeZone: string;
    statusIndicators: boolean;
    underlineLinks: boolean;
    disableAutofocus: boolean;
    notificationDuration: string;
    notificationPosition: string;
    slideoutPosition: string;
    showFieldHandles?: boolean;
    showExceptionView?: boolean;
    profileTemplates?: boolean;
  }

  const props = usePage<UserPreferencesViewModel>().props;
  const preferences = props.preferences;

  const initialForm: UserPreferencesForm = {
    preferredLanguage: preferences.preferredLanguage?.toString() ?? '',
    preferredLocale: preferences.preferredLocale?.toString() ?? '',
    weekStartDay: preferences.weekStartDay?.toString() ?? '0',
    timeZone: preferences.timeZone?.toString() ?? '__blank__',
    statusIndicators: Boolean(preferences['useShapes']),
    underlineLinks: Boolean(preferences.underlineLinks),
    disableAutofocus: Boolean(preferences.disableAutofocus),
    notificationDuration:
      preferences.notificationDuration?.toString() ?? '5000',
    notificationPosition:
      preferences.notificationPosition?.toString() ?? 'end-start',
    slideoutPosition: preferences.slideoutPosition?.toString() ?? 'end',
  };
  if (props.isAdmin) {
    initialForm.showFieldHandles = Boolean(preferences.showFieldHandles);
    initialForm.showExceptionView = Boolean(preferences.showExceptionView);
    initialForm.profileTemplates = Boolean(preferences.profileTemplates);
  }

  const form = useForm<UserPreferencesForm>(initialForm);

  const {save} = useSettingsSave(form, update, {
    transform: ({statusIndicators, ...data}) => ({
      ...data,
      ['useShapes']: statusIndicators,
    }),
  });

  useAppLayout({form, onSave: save});

  const displaySettings = computed({
    get: () => [
      ...(form.statusIndicators ? ['useShapes'] : []),
      ...(form.underlineLinks ? ['underlineLinks'] : []),
    ],
    set: (values: Array<string>) => {
      form.statusIndicators = values.includes('useShapes');
      form.underlineLinks = values.includes('underlineLinks');
    },
  });

  const generalSettings = computed({
    get: () => (form.disableAutofocus ? ['disableAutofocus'] : []),
    set: (values: Array<string>) => {
      form.disableAutofocus = values.includes('disableAutofocus');
    },
  });

  const developmentSettings = computed({
    get: () => [
      ...(form.showFieldHandles ? ['showFieldHandles'] : []),
      ...(form.profileTemplates ? ['profileTemplates'] : []),
      ...(form.showExceptionView ? ['showExceptionView'] : []),
    ],
    set: (values: Array<string>) => {
      form.showFieldHandles = values.includes('showFieldHandles');
      form.profileTemplates = values.includes('profileTemplates');
      form.showExceptionView = values.includes('showExceptionView');
    },
  });
</script>

<template>
  <UserScreen>
    <craft-pane appearance="raised" padding="0">
      <div class="cp:grid cp:gap-6 cp:p-4">
        <section class="cp:grid cp:gap-3">
          <h2 class="cp:text-base">{{ t('General') }}</h2>

          <craft-field-group>
            <CraftCombobox
              v-model="form.preferredLanguage"
              :label="t('Language')"
              :help-text="t('The language that the control panel should use.')"
              id="preferredLanguage"
              name="preferredLanguage"
              :options="props.languageOptions"
              :error="form.errors.preferredLanguage"
              :require-option-match="true"
              show-all-on-empty
            />

            <CraftCombobox
              v-model="form.preferredLocale"
              :label="t('Formatting Locale')"
              :help-text="
                t(
                  'The locale that should be used for date and number formatting.'
                )
              "
              id="preferredLocale"
              name="preferredLocale"
              :options="props.localeOptions"
              :error="form.errors.preferredLocale"
              :require-option-match="true"
              show-all-on-empty
            />

            <Select
              v-model="form.weekStartDay"
              :label="t('Week Start Day')"
              id="weekStartDay"
              name="weekStartDay"
              :options="props.weekStartDayOptions"
              :error="form.errors.weekStartDay"
            />

            <CraftCombobox
              v-model="form.timeZone"
              :label="t('Time Zone')"
              id="time-zone"
              name="timeZone"
              :options="props.timeZoneOptions"
              :error="form.errors.timeZone"
              :require-option-match="true"
              show-all-on-empty
            />
          </craft-field-group>
        </section>

        <hr />

        <section class="cp:grid cp:gap-3">
          <h2 class="cp:text-base">{{ t('Accessibility') }}</h2>

          <craft-field-group>
            <CheckboxGroup
              v-model="displaySettings"
              :label="t('Display Settings')"
              :options="props.displaySettingOptions"
            />

            <CheckboxGroup
              v-model="generalSettings"
              :label="t('General Settings')"
              :options="props.generalSettingOptions"
            />

            <Select
              v-model="form.notificationDuration"
              :label="t('Notification Duration')"
              :help-text="
                t(
                  'How long notifications should be shown before they disappear automatically.'
                )
              "
              name="notificationDuration"
              id="notificationDuration"
              :options="props.notificationDurationOptions"
              :error="form.errors.notificationDuration"
            />

            <craft-field-group>
              <h3 class="cp:text-sm">{{ t('Notification Position') }}</h3>
              <craft-button-group role="group">
                <craft-button
                  v-for="option in props.notificationPositionOptions"
                  :key="option.value"
                  type="button"
                  variant="outline"
                  :active="
                    form.notificationPosition === option.value ? 'true' : null
                  "
                  :aria-pressed="form.notificationPosition === option.value"
                  :title="option.label"
                  @click="form.notificationPosition = option.value"
                >
                  <craft-icon :name="option.icon" :label="option.label" />
                </craft-button>
              </craft-button-group>
              <ul class="error-list" v-if="form.errors.notificationPosition">
                <li>{{ form.errors.notificationPosition }}</li>
              </ul>
            </craft-field-group>

            <craft-field-group>
              <h3 class="cp:text-sm">{{ t('Slideout Position') }}</h3>
              <craft-button-group role="group">
                <craft-button
                  v-for="option in props.slideoutPositionOptions"
                  :key="option.value"
                  type="button"
                  variant="outline"
                  :active="
                    form.slideoutPosition === option.value ? 'true' : null
                  "
                  :aria-pressed="form.slideoutPosition === option.value"
                  :title="option.label"
                  @click="form.slideoutPosition = option.value"
                >
                  <craft-icon :name="option.icon" :label="option.label" />
                </craft-button>
              </craft-button-group>
              <ul class="error-list" v-if="form.errors.slideoutPosition">
                <li>{{ form.errors.slideoutPosition }}</li>
              </ul>
            </craft-field-group>
          </craft-field-group>
        </section>

        <template v-if="props.isAdmin">
          <hr />

          <section class="cp:grid cp:gap-3">
            <h2 class="cp:text-base">{{ t('Development') }}</h2>

            <craft-field-group>
              <CheckboxGroup
                v-model="developmentSettings"
                :label="t('Development Settings')"
                :options="props.developmentSettingOptions"
              />
            </craft-field-group>
          </section>
        </template>

        <HtmlFragmentRenderer :fragment="props.prefsHook" />
      </div>
    </craft-pane>
  </UserScreen>
</template>
