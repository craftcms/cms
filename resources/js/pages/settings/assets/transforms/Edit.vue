<script setup lang="ts">
  import {t} from '@craftcms/ui';
  import CraftInput from '@craftcms/ui/vue/CraftInput.vue';
  import CraftSelect from '@craftcms/ui/vue/CraftSelect.vue';
  import type {UrlMethodPair} from '@inertiajs/core';
  import type {
    UiControlOverrideProps,
    UiControlPayload,
    UiPayload,
    UiValue,
  } from '@/modules/ui/types';
  import {inputName} from '@/modules/ui/runtime';
  import UiPage from '@/pages/Ui.vue';

  type ChoiceValue = boolean | number | string;
  type ChoiceOption = {
    label: string;
    value: ChoiceValue;
    disabled?: boolean;
  };

  defineProps<{
    ui: UiPayload;
    submit: UrlMethodPair;
    refreshUrl: string | null;
  }>();

  function options(control: UiControlPayload): ChoiceOption[] {
    // SAFETY: choice controls serialize this documented option shape.
    return control.props.options as ChoiceOption[];
  }

  function qualityPickerValue(
    control: UiControlPayload,
    quality: UiValue
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
    setValue: UiControlOverrideProps['setValue']
  ): void {
    const numericValue = Number(value);

    setValue(numericValue ? String(numericValue) : '', 'discrete');
  }
</script>

<template>
  <UiPage :ui="ui" :submit="submit" :refresh-url="refreshUrl ?? undefined">
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
          <select slot="input" :aria-label="t('Quality Preset')">
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
  </UiPage>
</template>

<style scoped>
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
    border: 1px solid var(--c-ui-control-border-color);
    border-radius: var(--c-radius-sm);
    background-color: var(--c-ui-control-fill);
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
