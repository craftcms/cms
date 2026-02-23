<script setup lang="ts">
  import {CraftCombobox} from '@craftcms/cp/components/combobox/combobox.ts';
  import type {SelectOption, SuggestionGroup} from '@/types';
  import {computed} from 'vue';

  const emit = defineEmits<{
    (e: 'update:modelValue', value: string | null): void;
  }>();
  const props = withDefaults(
    defineProps<{
      label?: string;
      id?: string;
      name?: string;
      helpText?: string;
      modelValue?: any;
      options?: Array<SelectOption | SuggestionGroup>;
      error?: any;
    }>(),
    {
      options: () => [],
    }
  );

  function handleUpdate(event: CustomEvent) {
    const target = event.target as CraftCombobox | null;
    if (target) {
      emit('update:modelValue', target.value ?? target.modelValue);
    }
  }

  function handleInputUpdate(event: InputEvent) {
    const target = event.target as HTMLInputElement | null;
    if (target) {
      emit('update:modelValue', target.value);
    }
  }

  const grouped = computed(() => {
    return props.options.some((option) => option.data?.length > 0);
  });
</script>

<template>
  <craft-combobox
    :label="label"
    :id="id"
    :name="name"
    :help-text="helpText"
    :require-option-match="false"
    :has-feedback-for="error ? 'error' : ''"
    :show-all-on-empty="true"
    :value="modelValue"
    .modelValue="modelValue"
    @input="handleInputUpdate"
    @model-value-changed="handleUpdate"
    v-bind="$attrs"
  >
    <template v-if="grouped">
      <template v-for="(group, idx) in options" :key="idx">
        <span class="group-label">{{ group.label }}</span>
        <craft-option
          v-for="suggestion in group.data"
          :key="suggestion.name"
          .choiceValue="suggestion.name"
          .hint="suggestion.hint"
          >{{ suggestion.name }}</craft-option
        >
      </template>
    </template>
    <template v-else>
      <craft-option
        v-for="(item, idx) in options"
        :key="idx"
        .choiceValue="item.value"
        ><slot name="item" :item="item">
          {{ item.label }}
        </slot></craft-option
      >
    </template>

    <div slot="after">
      <slot name="after"></slot>
    </div>

    <div slot="feedback">
      <ul class="error-list" v-if="error">
        <li>{{ error }}</li>
      </ul>
    </div>
  </craft-combobox>
</template>

<style scoped lang="scss">
  .group-label {
    font-size: 0.8em;
    text-transform: uppercase;
    padding-inline: var(--c-spacing-md);
    padding-block: var(--c-spacing-sm);
    color: var(--c-color-neutral-on-normal);
  }
</style>
