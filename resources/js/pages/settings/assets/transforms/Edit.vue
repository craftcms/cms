<script setup lang="ts">
  import {t} from '@craftcms/ui';
  import CraftInput from '@craftcms/ui/vue/CraftInput.vue';
  import CraftSelect from '@craftcms/ui/vue/CraftSelect.vue';
  import type {UrlMethodPair} from '@inertiajs/core';
  import type {
    FormControlOverrideProps,
    FormControlPayload,
    FormPayload,
    FormValue,
  } from '@/modules/forms/types';
  import {inputName} from '@/modules/forms/runtime';
  import FormPage from '@/pages/Form.vue';
  import cropImageUrl from '/images/transforms/crop.svg';
  import fitImageUrl from '/images/transforms/fit.svg';
  import letterboxImageUrl from '/images/transforms/letterbox.svg';
  import stretchImageUrl from '/images/transforms/stretch.svg';

  type ChoiceValue = boolean | number | string;
  type ChoiceOption = {
    label: string;
    value: ChoiceValue;
    disabled?: boolean;
  };

  defineProps<{
    form: FormPayload;
    submit: UrlMethodPair;
    refreshUrl: string | null;
  }>();

  const modeImageUrls = {
    crop: cropImageUrl,
    fit: fitImageUrl,
    letterbox: letterboxImageUrl,
    stretch: stretchImageUrl,
  };

  function isTransformMode(value: string): value is keyof typeof modeImageUrls {
    return Object.hasOwn(modeImageUrls, value);
  }

  function modeImageUrl(value: FormValue): string {
    const mode = String(value);
    if (isTransformMode(mode)) {
      return modeImageUrls[mode];
    }

    throw new Error(`Unsupported transform mode: ${mode}`);
  }

  function options(control: FormControlPayload): ChoiceOption[] {
    // SAFETY: choice controls serialize this documented option shape.
    return control.props.options as ChoiceOption[];
  }

  function qualityPickerValue(
    control: FormControlPayload,
    quality: FormValue
  ): string {
    const numericQuality = Number(quality);

    if (!numericQuality) {
      return '';
    }

    return String(
      options(control)
        .filter((option) => Number(option.value) > 0)
        .reduce<ChoiceValue>(
          (matchedValue, option) =>
            numericQuality >= Number(option.value)
              ? option.value
              : matchedValue,
          10
        )
    );
  }

  function setQualityPreset(
    value: ChoiceValue | undefined,
    setValue: FormControlOverrideProps['setValue']
  ): void {
    const numericValue = Number(value);

    setValue(numericValue ? String(numericValue) : '', 'discrete');
  }
</script>

<template>
  <FormPage
    :form="form"
    :submit="submit"
    :refresh-url="refreshUrl ?? undefined"
  >
    <template
      #mode="{control, value, setValue, editable, invalid, required, label}"
    >
      <div
        class="mode-options"
        role="radiogroup"
        :aria-label="label"
        :aria-invalid="invalid ? 'true' : undefined"
        :aria-required="required ? 'true' : undefined"
      >
        <label
          v-for="option in options(control)"
          :key="String(option.value)"
          class="mode-option"
          :class="{'mode-option--selected': value === option.value}"
        >
          <img
            class="mode-option__image"
            :src="modeImageUrl(option.value)"
            width="113"
            height="75"
            alt=""
          />
          <span class="mode-option__label">
            <input
              type="radio"
              :name="editable ? inputName(control.path) : ''"
              :value="String(option.value)"
              :checked="value === option.value"
              :disabled="!editable || option.disabled"
              :required="editable && required"
              @change="setValue(String(option.value), 'discrete')"
            />
            {{ option.label }}
          </span>
        </label>
      </div>
    </template>

    <template #position="{control, value, setValue, editable, invalid, label}">
      <div
        class="position-grid"
        role="radiogroup"
        :aria-label="label"
        :aria-invalid="invalid ? 'true' : undefined"
      >
        <label
          v-for="option in options(control)"
          :key="String(option.value)"
          class="position-option"
          :class="{
            'position-option--selected': value === option.value,
            'position-option--disabled': !editable || option.disabled,
          }"
          :title="option.label"
        >
          <input
            type="radio"
            :name="editable ? inputName(control.path) : ''"
            :value="String(option.value)"
            :checked="value === option.value"
            :disabled="!editable || option.disabled"
            :aria-label="option.label"
            @change="setValue(String(option.value), 'discrete')"
          />
          <span class="position-option__marker"></span>
        </label>
      </div>
    </template>

    <template #quality="{control, value, setValue, editable}">
      <div class="quality-field">
        <CraftSelect
          :model-value="qualityPickerValue(control, value)"
          :disabled="!editable"
          @update:model-value="setQualityPreset($event, setValue)"
        >
          <select slot="input" :aria-label="t('Quality preset')">
            <option
              v-for="option in options(control)"
              :key="String(option.value)"
              :value="String(option.value)"
            >
              {{ option.label }}
            </option>
          </select>
        </CraftSelect>

        <CraftInput
          v-show="qualityPickerValue(control, value) !== ''"
          :name="editable ? inputName(control.path) : ''"
          :model-value="String(value ?? '')"
          :disabled="!editable"
          :aria-label="t('Quality')"
          type="number"
          min="1"
          max="100"
          size="5"
          @update:model-value="setValue(String($event ?? ''), 'typing')"
        />
      </div>
    </template>
  </FormPage>
</template>

<style scoped>
  .mode-options {
    display: flex;
    flex-wrap: wrap;
    gap: var(--c-spacing-lg);
  }

  .mode-option {
    display: grid;
    gap: var(--c-spacing-xs);
    inline-size: 113px;
    cursor: pointer;
    text-align: center;
  }

  .mode-option__image {
    inline-size: 113px;
    block-size: 75px;
    border: 1px solid transparent;
    border-radius: var(--c-radius-md);
  }

  .mode-option--selected .mode-option__image {
    border-color: var(--c-color-accent-border-loud);
  }

  .mode-option__label {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: var(--c-spacing-xs);
  }

  .quality-field {
    display: flex;
    align-items: end;
    gap: var(--c-spacing-sm);
  }

  .position-grid {
    display: grid;
    grid-template-columns: repeat(3, 2rem);
    gap: var(--c-spacing-xs);
    inline-size: max-content;
  }

  .position-option {
    position: relative;
    display: flex;
    align-items: center;
    justify-content: center;
    inline-size: 2rem;
    block-size: 2rem;
    border: 1px solid var(--c-form-control-border-color);
    border-radius: var(--c-radius-sm);
    background-color: var(--c-form-control-fill);
    cursor: pointer;
  }

  .position-option--disabled {
    cursor: default;
    opacity: 0.65;
  }

  .position-option input {
    position: absolute;
    inset: 0;
    margin: 0;
    opacity: 0;
  }

  .position-option__marker {
    inline-size: 0.5rem;
    block-size: 0.5rem;
    border-radius: 50%;
    background-color: var(--c-text-quiet);
    opacity: 0.4;
  }

  .position-option--selected {
    border-color: var(--c-color-accent-border-loud);
    background-color: var(--c-color-accent-fill-quiet);
  }

  .position-option--selected .position-option__marker {
    background-color: var(--c-color-accent-on-quiet);
    opacity: 1;
  }

  .position-option input:focus-visible + .position-option__marker {
    box-shadow: 0 0 0 3px var(--c-color-accent-border-normal);
  }
</style>
