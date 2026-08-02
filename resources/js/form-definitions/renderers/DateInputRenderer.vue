<script setup lang="ts">
  import {computed} from 'vue';
  import CraftInput from '@craftcms/ui/vue/CraftInput.vue';

  const props = defineProps<{
    modelValue?: string | null;
    readonly?: boolean;
  }>();

  const emit = defineEmits<{
    'update:modelValue': [value: string | null];
  }>();

  const value = computed(() =>
    typeof props.modelValue === 'string' ? props.modelValue.slice(0, 10) : ''
  );

  function updateValue(value: string | number | undefined): void {
    emit(
      'update:modelValue',
      value === '' || value === undefined ? null : String(value)
    );
  }
</script>

<template>
  <CraftInput
    type="date"
    :model-value="value"
    :readonly="readonly"
    @update:model-value="updateValue"
  />
</template>
