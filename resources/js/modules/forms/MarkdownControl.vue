<script setup lang="ts">
  import type {TextExpanderTriggers} from '@craftcms/ui';
  import '@craftcms/ui/components/text-expander/text-expander';
  import {useId} from 'vue';
  import '../markdown-field/markdown-field';
  import type {FormControlPayload} from './types';
  import {inputName} from './runtime';

  type MarkdownControlProps = {
    rows?: number;
    placeholder?: string;
    maxLength?: number;
    toolbarButtons?: string[];
    showToolbar?: boolean;
    textExpanderTriggers?: TextExpanderTriggers;
  };

  defineProps<{
    control: FormControlPayload<MarkdownControlProps>;
    value: string | null;
    editable: boolean;
    invalid: boolean;
    required: boolean;
  }>();
  const inputId = useId();
  const emit = defineEmits<{
    (event: 'update:value', value: string, kind: 'typing'): void;
  }>();
</script>

<template>
  <craft-markdown-field
    v-bind="$attrs"
    :id="inputId"
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
    @input="
      emit(
        'update:value',
        ($event.target as HTMLTextAreaElement).value,
        'typing'
      )
    "
  />
  <craft-text-expander
    v-if="editable && control.props.textExpanderTriggers"
    slot="input"
    :for="inputId"
    .triggers="control.props.textExpanderTriggers"
  />
</template>
