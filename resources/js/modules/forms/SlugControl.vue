<script setup lang="ts">
  import {useInputGenerator} from '@/common/composables/useInputGenerator';
  import {generateSlug} from '@/modules/input-generators/slug-generator';
  import {useFormValueGroup} from './formValueGroup';
  import {valueAt} from './runtime';
  import TextControl from './TextControl.vue';
  import type {
    FormChangeKind,
    FormControlPayload,
    FormPayload,
    TextControlProps,
  } from './types';

  defineOptions({inheritAttrs: false});

  type SlugControlProps = TextControlProps & {
    source?: string[];
    charMap?: Record<string, string>;
  };

  const props = defineProps<{
    control: FormControlPayload<SlugControlProps>;
    value: unknown;
    label?: string;
    editable: boolean;
    invalid: boolean;
    required: boolean;
    values: FormPayload['values'];
  }>();
  const emit = defineEmits<{
    (event: 'update:value', value: string, kind?: FormChangeKind): void;
    (event: 'change', change: Event): void;
  }>();
  const valueGroup = useFormValueGroup();
  const sourcePath = props.control.props.source;
  const charMap = props.control.props.charMap;
  const generator = useInputGenerator(sourceValue, (sourceValue) => {
    if (props.editable) {
      emit('update:value', generateSlug(sourceValue, charMap), 'typing');
    }
  });

  function sourceValue(): string {
    if (!sourcePath) {
      return '';
    }

    const path = [...props.control.path.slice(0, -1), ...sourcePath];

    return String(
      valueGroup?.valueAt(path) ?? valueAt(props.values, path) ?? ''
    );
  }

  function onChange(event: Event): void {
    generator.markDirty();
    emit('change', event);
  }
</script>

<template>
  <TextControl
    v-bind="$attrs"
    :control="control"
    :value="value"
    :label="label"
    :editable="editable"
    :invalid="invalid"
    :required="required"
    @update:value="emit('update:value', $event, 'typing')"
    @change="onChange"
  />
</template>
