<script setup lang="ts">
  import {ComboboxOption} from '@headlessui/vue';
  import type {SelectOption} from '@/common/types';

  defineProps<{
    option: SelectOption;
  }>();
</script>

<template>
  <ComboboxOption v-slot="{active, selected}" :value="option" as="template">
    <slot name="option" :option="option" :active="active" :selected="selected">
      <craft-option
        :active="active"
        :checked="selected"
        :hint="option.data?.hint"
      >
        <div class="flex gap-2 items-center">
          <craft-icon
            v-if="option.data?.addOption"
            name="plus"
            class="size-3"
          ></craft-icon>
          <template v-if="option.data?.indicator">
            <craft-indicator v-bind="option.data.indicator"></craft-indicator>
          </template>
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
        </div>
      </craft-option>
    </slot>
  </ComboboxOption>
</template>

<style scoped lang="scss"></style>
