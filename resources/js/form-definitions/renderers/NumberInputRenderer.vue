<script setup lang="ts">
  import {computed} from 'vue';
  import CraftInput from '@craftcms/ui/vue/CraftInput.vue';

  const props = defineProps<{
    min?: number;
    max?: number;
    step?: number;
    modelValue?: number | null;
    readonly?: boolean;
  }>();

  const emit = defineEmits<{
    'update:modelValue': [value: number | null];
  }>();

  const value = computed(() => String(props.modelValue ?? ''));

  function updateValue(value: string | number | undefined): void {
    emit(
      'update:modelValue',
      value === '' || value === undefined ? null : Number(value)
    );
  }
</script>

<template>
  <CraftInput
    type="number"
    :model-value="value"
    :min="min"
    :max="max"
    :step="step"
    :readonly="readonly"
    @update:model-value="updateValue"
  />
</template>
