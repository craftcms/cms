<script setup lang="ts">
  import {computed, useAttrs} from 'vue';
  import type {ObjectSelectOption} from '@craftcms/ui';
  import '@craftcms/ui/components/object-select/object-select';
  import type {FormElementBinding, JsonValue} from '../types';

  defineOptions({inheritAttrs: false});

  const props = defineProps<{
    config: Record<string, JsonValue>;
    attributes: Record<string, JsonValue>;
    binding?: FormElementBinding;
  }>();

  const emit = defineEmits<{
    'update:value': [value: unknown[]];
  }>();
  const attrs = useAttrs();
  const hostProperties = computed(() => ({...props.attributes, ...attrs}));
  const options = computed<ObjectSelectOption[]>(() =>
    Array.isArray(props.config.options)
      ? (props.config.options as ObjectSelectOption[])
      : []
  );
  const value = computed<unknown[]>(() =>
    Array.isArray(props.binding?.value) ? props.binding.value : []
  );
  const identityKey = computed(() =>
    typeof props.config.identityKey === 'string' ? props.config.identityKey : ''
  );

  function updateValue(event: Event): void {
    emit(
      'update:value',
      (event.target as HTMLElementTagNameMap['craft-object-select']).value
    );
  }
</script>

<template>
  <craft-object-select
    v-bind="hostProperties"
    .value="value"
    .options="options"
    :identity-key="identityKey"
    :readonly="binding?.readOnly ?? false"
    @input="updateValue"
  ></craft-object-select>
</template>
