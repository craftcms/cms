<script setup lang="ts">
  import type {BaseOption} from '@/common/types';
  import CraftSelect from '@craftcms/ui/vue/CraftSelect.vue';
  import {computed} from 'vue';

  const emit = defineEmits<{
    (e: 'update:modelValue', value: string | number): void;
  }>();
  const props = defineProps<{
    modelValue: string | number;
    options: Array<BaseOption> | Array<string> | Array<number>;
    error?: string;
  }>();

  const normalizedOptions = computed(() => {
    return props.options.map((option) => {
      if (!(option instanceof Object)) {
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
        <slot
          name="option-label"
          :option="option"
          :selected="option.value === modelProxy"
        >
          {{ option.label }}
        </slot>
      </option>
    </select>

    <ul class="error-list" v-if="error" slot="feedback">
      <li>{{ error }}</li>
    </ul>
  </CraftSelect>
</template>

<style scoped lang="scss"></style>
