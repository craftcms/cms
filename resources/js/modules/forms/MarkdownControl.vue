<script setup lang="ts">
  import '../markdown-field/markdown-field';
  import type {FormControlPayload} from './types';
  import {inputName} from './runtime';

  type MarkdownControlProps = {
    rows?: number;
    placeholder?: string;
    maxLength?: number;
    toolbarButtons?: string[];
    showToolbar?: boolean;
  };

  defineProps<{
    control: FormControlPayload<MarkdownControlProps>;
    value: string | null;
    editable: boolean;
    invalid: boolean;
    required: boolean;
  }>();
  const emit = defineEmits<{
    (event: 'update:value', value: string, kind: 'typing'): void;
  }>();

  function onInput(event: Event): void {
    emit('update:value', (event.target as HTMLTextAreaElement).value, 'typing');
  }
</script>

<template>
  <craft-markdown-field
    :name="editable ? inputName(control.path) : ''"
    :rows="control.props.rows ?? 8"
    :placeholder="control.props.placeholder"
    :max-length="control.props.maxLength"
    .toolbarButtons="control.props.toolbarButtons ?? []"
    :show-toolbar="control.props.showToolbar ?? true"
    sanitize-html
    :disabled="!editable"
    :required="editable && required"
    :aria-invalid="invalid ? 'true' : undefined"
    .value="value ?? ''"
    @input="onInput"
  />
</template>
