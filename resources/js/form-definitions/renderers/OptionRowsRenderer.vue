<script setup lang="ts">
  import {computed, useAttrs} from 'vue';
  import type {OptionRow} from '@craftcms/ui';
  import '@/modules/icon-picker';
  import '@craftcms/ui/components/option-rows/option-rows';
  import type {FormElementBinding, JsonValue} from '../types';

  defineOptions({inheritAttrs: false});

  const props = defineProps<{
    config: Record<string, JsonValue>;
    attributes: Record<string, JsonValue>;
    binding?: FormElementBinding;
  }>();

  const emit = defineEmits<{
    'update:value': [value: OptionRow[]];
  }>();
  const attrs = useAttrs();
  const hostAttributes = computed(() => ({...props.attributes, ...attrs}));
  const value = computed<OptionRow[]>(() =>
    Array.isArray(props.binding?.value)
      ? (props.binding.value as OptionRow[])
      : []
  );

  function updateValue(event: Event): void {
    emit(
      'update:value',
      (event.target as HTMLElementTagNameMap['craft-option-rows']).value
    );
  }
</script>

<template>
  <craft-option-rows
    v-bind="hostAttributes"
    .value="value"
    .multipleDefaults="config.multipleDefaults === true"
    .optgroups="config.optgroups === true"
    .icons="config.icons === true"
    .colors="config.colors === true"
    :readonly="binding?.readOnly ?? false"
    @input="updateValue"
  ></craft-option-rows>
</template>
