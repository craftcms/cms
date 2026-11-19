<script setup lang="ts" generic="T extends string">
  import {useId} from 'vue';

  /** A view setting's choices, as a row of buttons. */
  defineProps<{
    label: string;
    options: Array<{value: T; label: string; icon?: string}>;
  }>();

  const value = defineModel<T>({required: true});

  // Unique per instance, since an index can share the page with another (e.g.
  // an element selection modal).
  const name = `view-setting-${useId()}`;

  function onChange(event: Event): void {
    value.value = (event.currentTarget as HTMLElement & {value: T}).value;
  }
</script>

<template>
  <craft-radio-group
    appearance="button-group"
    :name="name"
    :label="label"
    label-sr-only
    .value="value"
    @change="onChange"
  >
    <craft-radio
      v-for="option in options"
      :key="option.value"
      .value="option.value"
      .checked="value === option.value"
    >
      <input
        slot="input"
        :id="`${name}-${option.value}`"
        type="radio"
        :name="name"
        :value="option.value"
        :checked="value === option.value"
      />
      <label slot="label" :for="`${name}-${option.value}`">
        <craft-icon v-if="option.icon" :name="option.icon"></craft-icon>
        {{ option.label }}
      </label>
    </craft-radio>
  </craft-radio-group>
</template>
