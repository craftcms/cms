<script setup lang="ts">
  import {ComboboxOption} from '@headlessui/vue';
  import type {SelectOption} from '@/types';

  defineProps<{
    option: SelectOption;
  }>();
</script>

<template>
  <ComboboxOption v-slot="{active, selected}" :value="option">
    <slot name="option" :option="option" :active="active" :selected="selected">
      <craft-option
        :active="active"
        :checked="selected"
        :hint="option.data?.hint"
      >
        <template
          v-if="option.label.startsWith('$') || option.label.startsWith('@')"
        >
          <code>
            {{ option.label }}
          </code>
        </template>
        <template v-else>
          {{ option.label }}
        </template>
      </craft-option>
    </slot>
  </ComboboxOption>
</template>

<style scoped lang="scss"></style>
