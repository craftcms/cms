<script setup lang="ts">
  import {computed, useAttrs} from 'vue';
  import '@craftcms/ui/components/color-palette/color-palette';
  import type {ColorPaletteRow} from '@craftcms/ui';
  import type {FormElementBinding, JsonValue} from '../types';

  defineOptions({inheritAttrs: false});

  const props = defineProps<{
    config: Record<string, JsonValue>;
    attributes: Record<string, JsonValue>;
    binding?: FormElementBinding;
  }>();

  const emit = defineEmits<{
    'update:value': [value: ColorPaletteRow[]];
  }>();
  const attrs = useAttrs();
  const hostAttributes = computed(() => ({...props.attributes, ...attrs}));
  const value = computed<ColorPaletteRow[]>(() =>
    Array.isArray(props.binding?.value)
      ? (props.binding.value as ColorPaletteRow[])
      : []
  );

  function updateValue(event: Event): void {
    emit(
      'update:value',
      (event.target as HTMLElementTagNameMap['craft-color-palette']).value
    );
  }
</script>

<template>
  <craft-color-palette
    v-bind="hostAttributes"
    .value="value"
    @input="updateValue"
  ></craft-color-palette>
</template>
