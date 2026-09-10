<script setup lang="ts">
  import '../link-field/craft-link-field';
  import type {
    LinkFieldValue,
    LinkTypeConfig,
  } from '../link-field/craft-link-field';
  import type {FormControlPayload} from './types';
  import {controlValue, inputName} from './runtime';

  type LinkControlProps = {
    types: LinkTypeConfig[];
    showLabelField?: boolean;
    advancedFields?: Array<'urlSuffix' | 'title'>;
  };
  type LinkValue = Pick<LinkFieldValue, 'type' | 'value'> &
    Partial<Pick<LinkFieldValue, 'label' | 'title' | 'urlSuffix'>>;

  const props = defineProps<{
    control: FormControlPayload<LinkControlProps>;
    /** Read {@link model} rather than this. See {@link controlValue}. */
    value: LinkValue | undefined;
    editable: boolean;
  }>();
  const emit = defineEmits<{
    (event: 'update:value', value: LinkValue, kind: 'discrete'): void;
  }>();
  const model = controlValue<LinkValue>(() => props.value, {} as LinkValue);

  function apply(event: Event): void {
    if (!(event instanceof CustomEvent)) {
      throw new TypeError('Expected a link-field custom event.');
    }

    const detail: LinkFieldValue = event.detail;
    const {label, title, type, urlSuffix, value} = detail;
    emit('update:value', {label, title, type, urlSuffix, value}, 'discrete');
  }
</script>

<template>
  <div>
    <craft-link-field
      :types="control.props.types"
      .modelValue="model"
      :name="editable ? inputName(control.path) : ''"
      :show-label-field="control.props.showLabelField"
      .advancedFields="control.props.advancedFields"
      :disabled="!editable"
      @apply="apply"
    />
  </div>
</template>
