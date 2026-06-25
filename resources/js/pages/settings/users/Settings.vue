<script setup lang="ts">
  import {t} from '@craftcms/cp';
  import {computed, ref} from 'vue';
  import {useForm} from '@inertiajs/vue3';
  import {store} from '@actions/Settings/Users/UserSettingsController';
  import {create as createVolume} from '@actions/Settings/VolumesController';
  import IndexLayout from '@/common/layouts/IndexLayout.vue';
  import CraftCombobox from '@/common/form/CraftCombobox.vue';
  import Select from '@/common/form/Select.vue';
  import CraftInput from '@craftcms/cp/vue/CraftInput.vue';
  import {useSettingsSave} from '@/modules/settings/composables/useSettingsSave';
  import useCraftData from '@/common/composables/useCraftData';
  import type {BaseOption, SelectOption} from '@/common/types';

  const createVolumeOptionValue = '__createVolume__';

  interface UserSettingsForm {
    photoVolumeUid: string;
    photoSubpath: string;
    require2fa: CraftCms.Cms.User.Data.UserSettings['require2fa'];
    requireEmailVerification: boolean;
    allowPublicRegistration: boolean;
    validateOnPublicRegistration: boolean;
    deactivateByDefault: boolean;
    defaultGroup: string;
  }

  interface VolumeSlideoutSubmitEvent {
    data: {
      name: string;
      uid: string;
    };
  }

  const props = defineProps<{
    settings: CraftCms.Cms.User.Data.UserSettings;
    canRequire2fa: boolean;
    canManagePublicRegistration: boolean;
    photoVolumeOptions: Array<BaseOption>;
    userGroupOptions: Array<BaseOption>;
  }>();

  const {readOnly} = useCraftData();

  const form = useForm<UserSettingsForm>({
    photoVolumeUid: props.settings.photoVolumeUid?.toString() ?? '',
    photoSubpath: props.settings.photoSubpath ?? '',
    require2fa: props.settings.require2fa,
    requireEmailVerification: props.settings.requireEmailVerification,
    allowPublicRegistration: props.settings.allowPublicRegistration,
    validateOnPublicRegistration: props.settings.validateOnPublicRegistration,
    deactivateByDefault: props.settings.deactivateByDefault,
    defaultGroup: props.settings.defaultGroup ?? '',
  });

  const createdPhotoVolumeOptions = ref<Array<BaseOption>>([]);

  const photoVolumeUid = computed({
    get() {
      return form.photoVolumeUid;
    },
    set(value: string) {
      if (value === createVolumeOptionValue) {
        createPhotoVolume();
        return;
      }

      form.photoVolumeUid = value;
    },
  });

  const photoVolumeOptions = computed<Array<SelectOption>>(() => {
    const optionValues = props.photoVolumeOptions.map((option) => option.value);
    const newOptions = createdPhotoVolumeOptions.value.filter((option) => {
      return !optionValues.includes(option.value);
    });

    return [
      ...props.photoVolumeOptions,
      ...newOptions,
      {
        label: t('Create a new volume…'),
        value: createVolumeOptionValue,
        data: {
          addOption: true,
        },
      },
    ];
  });

  const defaultGroupOptions = computed(() => [
    {label: t('None'), value: ''},
    ...props.userGroupOptions,
  ]);

  const require2faValues = computed(() => {
    if (form.require2fa === 'all') {
      return ['all'];
    }

    return form.require2fa || [];
  });

  const {save} = useSettingsSave(form, store, {
    transform: (data) => ({
      ...data,
      photoVolumeUid: data.photoVolumeUid === '' ? null : data.photoVolumeUid,
      defaultGroup: data.defaultGroup || null,
    }),
  });

  function updateRequire2fa(event: CustomEvent) {
    const target = event.currentTarget as HTMLElement & {
      modelValue?: Array<string>;
    };
    const values = target.modelValue ?? [];

    if (values.includes('all')) {
      form.require2fa = 'all';
      return;
    }

    const selectedValues = values.filter(Boolean);
    form.require2fa = selectedValues.length > 0 ? selectedValues : false;
  }

  function createPhotoVolume() {
    const slideout = new Craft.CpScreenSlideout(createVolume.url());

    slideout.on('submit', ({data}: VolumeSlideoutSubmitEvent) => {
      createdPhotoVolumeOptions.value = [
        ...createdPhotoVolumeOptions.value.filter((option) => {
          return option.value !== data.uid;
        }),
        {
          label: data.name,
          value: data.uid,
        },
      ];

      form.photoVolumeUid = data.uid;
    });
  }
</script>

<template>
  <IndexLayout :form="form" :default-form-actions="[]" @save="save">
    <div class="grid gap-6 p-4">
      <section class="grid gap-3">
        <h2 v-if="canRequire2fa" class="text-base">{{ t('User Photos') }}</h2>

        <div class="grid gap-3">
          <div>
            <div class="mb-2">
              <h3 class="text-sm">{{ t('User Photo Location') }}</h3>
              <p
                class="text-sm text-neutral-text-quiet"
                v-html="
                  t(
                    'Where do you want to store user photos? Note that the subfolder path can contain variables like <code>{username}</code>.'
                  )
                "
              ></p>
            </div>

            <div class="grid gap-3 md:grid-cols-[minmax(0,18rem)_1fr]">
              <CraftCombobox
                v-model="photoVolumeUid"
                :label="t('User Photo Volume')"
                id="photoVolumeUid"
                name="photoVolumeUid"
                :options="photoVolumeOptions"
                :error="form.errors.photoVolumeUid"
                :disabled="readOnly"
                :require-option-match="true"
                show-all-on-empty
                clearable
              />

              <CraftInput
                v-model="form.photoSubpath"
                :label="t('Subpath')"
                id="photoSubpath"
                name="photoSubpath"
                class="ltr"
                :placeholder="t('path/to/subfolder')"
                :error="form.errors.photoSubpath"
                :disabled="readOnly"
              />
            </div>
          </div>
        </div>
      </section>

      <template v-if="canRequire2fa">
        <hr />

        <section class="grid gap-3">
          <h2 class="text-base">{{ t('Security') }}</h2>

          <craft-checkbox-group
            name="require2fa"
            :label="t('Require two-step verification')"
            .modelValue="require2faValues"
            :disabled="readOnly"
            @model-value-changed="updateRequire2fa"
          >
            <div slot="help-text">
              {{
                t(
                  'Choose which users must use two-step verification to sign in.'
                )
              }}
            </div>

            <craft-checkbox .choiceValue="'all'">
              <label slot="label">
                <b>{{ t('All users') }}</b>
              </label>
            </craft-checkbox>

            <craft-checkbox
              .choiceValue="'admins'"
              :disabled="readOnly || form.require2fa === 'all'"
            >
              <label slot="label">{{ t('Admins') }}</label>
            </craft-checkbox>

            <craft-checkbox
              v-for="group in userGroupOptions"
              :key="group.value"
              .choiceValue="group.value"
              :disabled="readOnly || form.require2fa === 'all'"
            >
              <label slot="label">{{ group.label }}</label>
            </craft-checkbox>
          </craft-checkbox-group>

          <template v-if="canManagePublicRegistration">
            <craft-switch
              name="requireEmailVerification"
              :label="t('Verify email addresses')"
              :checked="form.requireEmailVerification"
              :disabled="readOnly"
              @checked-changed="
                form.requireEmailVerification = $event.target?.checked
              "
            >
              <div slot="help-text">
                {{
                  t(
                    'Should new email addresses be verified before getting saved to user accounts? (This also affects new user registration.)'
                  )
                }}
              </div>
            </craft-switch>
          </template>
        </section>
      </template>

      <template v-if="canManagePublicRegistration">
        <hr />

        <section class="grid gap-3">
          <h2 class="text-base">{{ t('Public Registration') }}</h2>

          <craft-switch
            name="allowPublicRegistration"
            :label="t('Allow public registration')"
            :checked="form.allowPublicRegistration"
            :disabled="readOnly"
            @checked-changed="
              form.allowPublicRegistration = $event.target?.checked
            "
          ></craft-switch>

          <div v-if="form.allowPublicRegistration" class="grid gap-3">
            <craft-switch
              name="validateOnPublicRegistration"
              :label="t('Validate custom fields on public registration')"
              :checked="form.validateOnPublicRegistration"
              :disabled="readOnly"
              @checked-changed="
                form.validateOnPublicRegistration = $event.target?.checked
              "
            >
              <div slot="help-text">
                {{
                  t(
                    'Whether custom fields should be validated during public registration.'
                  )
                }}
              </div>
            </craft-switch>

            <craft-switch
              name="deactivateByDefault"
              :label="t('Deactivate users by default')"
              :checked="form.deactivateByDefault"
              :disabled="readOnly"
              @checked-changed="
                form.deactivateByDefault = $event.target?.checked
              "
            >
              <div slot="help-text">
                {{
                  t(
                    'Should users who register their own accounts be deactivated by default? This will prevent them from receiving an activation email or logging in.'
                  )
                }}
              </div>
            </craft-switch>

            <div>
              <Select
                v-model="form.defaultGroup"
                :label="t('Default User Group')"
                name="defaultGroup"
                :options="defaultGroupOptions"
                :help-text="
                  t(
                    'Choose a user group that publicly-registered members will be added to by default.'
                  )
                "
                :disabled="readOnly"
              />
            </div>
          </div>
        </section>
      </template>
    </div>
  </IndexLayout>
</template>
