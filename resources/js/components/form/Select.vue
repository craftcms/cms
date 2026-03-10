<script setup lang="ts">
  import type {SelectOption} from '@/types';
  import CraftSelect from '@craftcms/cp/vue/CraftSelect.vue';
  import {computed} from 'vue';

  const emit = defineEmits<{
    (e: 'update:modelValue', value: string | number): void;
  }>();
  const props = defineProps<{
    modelValue: string | number;
    options: Array<SelectOption> | Array<string> | Array<number>;
    error?: string;
  }>();

  const normalizedOptions = computed(() => {
    return props.options.map((option) => {
      if (typeof option === 'string' || typeof option === 'number') {
        return {
          label: option.toString(),
          value: option,
        };
      }

      return option;
    });
  });

  const modelProxy = computed({
    get() {
      return props.modelValue.toString();
    },
    set(newValue) {
      emit('update:modelValue', newValue);
    },
  });
</script>

<template>
  <CraftSelect v-model="modelProxy" v-bind="$attrs">
    <select slot="input">
      <option
        v-for="option in normalizedOptions"
        :key="option.value"
        :value="option.value"
      >
        {{ option.label }}
      </option>
    </select>

    <ul class="error-list" v-if="error" slot="feedback">
      <li>{{ error }}</li>
    </ul>
  </CraftSelect>
</template>

<style scoped lang="scss"></style>
