<script setup lang="ts">
  import CraftCombobox from '@craftcms/ui/vue/CraftCombobox.vue';
  import type {UrlMethodPair} from '@inertiajs/core';
  import {ref} from 'vue';
  import {create as createVolume} from '@actions/Settings/VolumesController';
  import {openSlideout} from '@/common/slideouts';
  import type {SelectOption} from '@/common/types';
  import type {
    FormControlOverrideProps,
    FormControlPayload,
    FormPayload,
    FormValue,
  } from '@/modules/forms/types';
  import {inputName, serverErrorValidators} from '@/modules/forms/runtime';
  import FormPage from '@/pages/Form.vue';

  const createVolumeOptionValue = '__createVolume__';

  interface VolumeSaveData {
    volume?: {
      name: string;
      uid: string;
    };
  }

  const props = defineProps<{
    form: FormPayload;
    submit: UrlMethodPair;
    refreshUrl: string | null;
  }>();

  const createdPhotoVolumeOptions = ref<SelectOption[]>([]);

  function options(control: FormControlPayload): SelectOption[] {
    const options = control.props.options;
    if (!Array.isArray(options)) {
      return [];
    }
    return options.flatMap((option) => {
      if (
        !(option instanceof Object) ||
        Array.isArray(option) ||
        Object(option.label).constructor !== String ||
        Object(option.value).constructor !== String
      ) {
        return [];
      }
      return [{label: String(option.label), value: String(option.value)}];
    });
  }

  function photoVolumeOptions(control: FormControlPayload): SelectOption[] {
    const configured = options(control);
    const configuredValues = configured.map((option) => option.value);
    const created = createdPhotoVolumeOptions.value.filter(
      (option) => !configuredValues.includes(option.value)
    );
    const createOption = configured.filter(
      (option) => option.value === createVolumeOptionValue
    );

    return [
      ...configured.filter(
        (option) => option.value !== createVolumeOptionValue
      ),
      ...created,
      ...createOption,
    ];
  }

  function updatePhotoVolume(
    value: string | number | boolean | undefined,
    setValue: FormControlOverrideProps['setValue']
  ): void {
    if (value === createVolumeOptionValue) {
      createPhotoVolume(setValue);
      return;
    }

    setValue(String(value ?? ''), 'typing');
  }

  function createPhotoVolume(
    setValue: FormControlOverrideProps['setValue']
  ): void {
    void openSlideout(createVolume.url(), {
      onSaved: ({data}) => {
        // SAFETY: the volume create endpoint returns its documented saved volume.
        const volume = (data as VolumeSaveData | undefined)?.volume;

        if (!volume) {
          throw new Error(
            'The saved volume response did not include a volume.'
          );
        }

        createdPhotoVolumeOptions.value = [
          ...createdPhotoVolumeOptions.value.filter(
            (option) => option.value !== volume.uid
          ),
          {label: volume.name, value: volume.uid},
        ];
        setValue(volume.uid);
      },
    });
  }

  function require2faValues(value: FormValue): string[] {
    if (value === 'all') {
      return ['all'];
    }

    return Array.isArray(value) ? value.map(String) : [];
  }

  function updateRequire2fa(
    event: CustomEvent,
    setValue: FormControlOverrideProps['setValue']
  ): void {
    const target = event.currentTarget;
    const values =
      target instanceof HTMLElement &&
      'modelValue' in target &&
      Array.isArray(target.modelValue)
        ? target.modelValue.map(String)
        : [];

    setValue(
      values.includes('all') ? 'all' : values.length > 0 ? values : false
    );
  }
</script>

<template>
  <FormPage
    :form="form"
    :submit="submit"
    :refresh-url="refreshUrl ?? undefined"
    full-width
    :default-form-actions="[]"
  >
    <template
      #photoVolumeUid="{
        control,
        value,
        label,
        setValue,
        editable,
        invalid,
        required,
      }"
    >
      <CraftCombobox
        :name="editable ? inputName(control.path) : ''"
        :model-value="String(value ?? '')"
        :options="photoVolumeOptions(control)"
        :clearable="Boolean(control.props.clearable)"
        :require-option-match="Boolean(control.props.requireOptionMatch)"
        :show-all-on-empty="Boolean(control.props.showAllOnEmpty)"
        :required="editable && required"
        :readonly="control.mode === 'readOnly'"
        :disabled="control.mode === 'disabled'"
        :validators="serverErrorValidators(invalid)"
        :aria-label="label"
        @update:model-value="updatePhotoVolume($event, setValue)"
      />
    </template>

    <template
      #require2fa="{control, value, label, setValue, editable, invalid}"
    >
      <craft-checkbox-group
        :name="editable ? inputName(control.path) : ''"
        :aria-label="label"
        .modelValue="require2faValues(value)"
        :disabled="!editable"
        :aria-invalid="invalid ? 'true' : undefined"
        @model-value-changed="updateRequire2fa($event, setValue)"
      >
        <craft-checkbox
          v-for="option in options(control)"
          :key="option.value"
          .choiceValue="option.value"
          :disabled="!editable || (value === 'all' && option.value !== 'all')"
        >
          <label slot="label" :class="{'font-bold': option.value === 'all'}">
            {{ option.label }}
          </label>
        </craft-checkbox>
      </craft-checkbox-group>
    </template>
  </FormPage>
</template>
