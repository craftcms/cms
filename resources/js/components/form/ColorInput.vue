<script setup lang="ts">
  import CraftInput from '@craftcms/cp/vue/CraftInput.vue';
  import {t} from '@craftcms/cp';
  import {computed} from 'vue';

  const model = defineModel<string | null>({default: ''});

  defineProps<{
    id: string;
    name?: string;
    label?: string;
    disabled?: boolean;
    error?: string;
  }>();

  const textValue = computed({
    get() {
      return (model.value ?? '').replace(/^#/, '');
    },
    set(value: string) {
      model.value = value.trim().replace(/^#/, '');
    },
  });

  const expandedHexValue = computed(() => {
    const value = textValue.value;

    if (/^[0-9a-fA-F]{3}$/.test(value)) {
      return value
        .split('')
        .map((character) => character + character)
        .join('');
    }

    if (/^[0-9a-fA-F]{6}$/.test(value)) {
      return value;
    }

    return null;
  });

  const colorValue = computed({
    get() {
      return expandedHexValue.value ? `#${expandedHexValue.value}` : '#ffffff';
    },
    set(value: string) {
      model.value = value.replace(/^#/, '');
    },
  });

  const swatchStyle = computed(() => {
    return expandedHexValue.value
      ? {backgroundColor: `#${expandedHexValue.value}`}
      : {};
  });
</script>

<template>
  <div class="color-field">
    <label v-if="label" class="color-field__label" :for="id">
      {{ label }}
    </label>

    <div class="color-field__control">
      <label
        class="color-field__swatch"
        :class="{'color-field__swatch--disabled': disabled}"
      >
        <span class="color-field__preview" :style="swatchStyle"></span>
        <input
          v-model="colorValue"
          :disabled="disabled"
          class="color-field__picker"
          type="color"
          :aria-label="t('Choose a color')"
        />
      </label>

      <CraftInput
        :id="id"
        v-model="textValue"
        :name="name"
        :label="label ?? t('Color hex value')"
        :disabled="disabled"
        :error="error"
        class="color-field__input"
        spellcheck="false"
        autocorrect="off"
        autocapitalize="off"
        inputmode="text"
        label-sr-only
      >
        <span slot="prefix" class="color-field__prefix" aria-hidden="true"
          >#</span
        >
      </CraftInput>
    </div>
  </div>
</template>

<style scoped>
  .color-field {
    display: grid;
    gap: var(--c-spacing-xs);
  }

  .color-field__label {
    font-weight: var(--font-weight-bold);
  }

  .color-field__control {
    display: flex;
    align-items: center;
    gap: var(--c-spacing-sm);
  }

  .color-field__swatch {
    position: relative;
    display: block;
    inline-size: var(--c-form-control-height);
    block-size: var(--c-form-control-height);
    border-radius: 50%;
    overflow: hidden;
    background:
      linear-gradient(
        45deg,
        var(--c-color-neutral-fill-quiet) 25%,
        transparent 25%
      ),
      linear-gradient(
        -45deg,
        var(--c-color-neutral-fill-quiet) 25%,
        transparent 25%
      ),
      linear-gradient(
        45deg,
        transparent 75%,
        var(--c-color-neutral-fill-quiet) 75%
      ),
      linear-gradient(
        -45deg,
        transparent 75%,
        var(--c-color-neutral-fill-quiet) 75%
      );
    background-position:
      0 0,
      0 0.375rem,
      0.375rem -0.375rem,
      -0.375rem 0;
    background-size: 0.75rem 0.75rem;
  }

  .color-field__swatch:not(.color-field__swatch--disabled) input {
    cursor: pointer;
  }

  .color-field__swatch:focus-within {
    box-shadow: 0 0 0 2px var(--c-color-accent-border-normal);
  }

  .color-field__preview {
    position: absolute;
    inset: 0;
    border-radius: 50%;
    box-shadow: inset 0 0 0 1px rgb(0 0 0 / 15%);
  }

  .color-field__picker {
    position: absolute;
    inset: 0;
    inline-size: 100%;
    block-size: 100%;
    border: 0;
    margin: 0;
    padding: 0;
    opacity: 0;
  }

  .color-field__prefix {
    color: var(--c-text-quiet);
    user-select: none;
    font-family: var(--c-font-mono);
  }

  .color-field__input {
    flex: 0 0 7.25rem;
    inline-size: 7.25rem;
    font-family: var(--c-font-mono);
  }
</style>
