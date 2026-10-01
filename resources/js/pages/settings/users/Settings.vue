<script setup lang="ts">
  import type {UrlMethodPair} from '@inertiajs/core';
  import type {SelectOption} from '@/common/types';
  import type {
    FormControlOverrideProps,
    FormControlPayload,
    FormPayload,
    FormValue,
  } from '@/modules/forms/types';
  import {inputName} from '@/modules/forms/runtime';
  import FormPage from '@/pages/Form.vue';

  const props = defineProps<{
    form: FormPayload;
    submit: UrlMethodPair;
    refreshUrl: string | null;
  }>();

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
    :default-form-actions="[]"
  >
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
