<script setup lang="ts">
  import '../link-field/craft-link-field';
  import type {
    LinkAdvancedField,
    LinkChangeDetail,
    LinkFieldValue,
    LinkTypeConfig,
  } from '../link-field/craft-link-field';
  import type {UiControlPayload} from './types';
  import {inputName} from './runtime';

  type LinkControlProps = {
    types: LinkTypeConfig[];
    showLabelField?: boolean;
    advancedFields?: LinkAdvancedField[];
  };
  type LinkValue = Pick<LinkFieldValue, 'type' | 'value'> &
    Partial<Omit<LinkFieldValue, 'type' | 'value'>>;

  defineProps<{
    control: UiControlPayload<LinkControlProps>;
    value: LinkValue;
    editable: boolean;
  }>();
  const emit = defineEmits<{
    (
      event: 'update:value',
      value: LinkValue,
      kind: LinkChangeDetail['kind']
    ): void;
  }>();

  function change(event: Event): void {
    if (!(event instanceof CustomEvent)) {
      throw new TypeError('Expected a link-field custom event.');
    }

    const {kind, value}: LinkChangeDetail = event.detail;
    // `href` is derived, so it isn't part of the field's value.
    const {href: _href, ...linkValue} = value;
    emit('update:value', linkValue, kind);
  }
</script>

<template>
  <div>
    <craft-link-field
      live
      :types="control.props.types"
      .modelValue="value"
      :name="editable ? inputName(control.path) : ''"
      .showLabelField="control.props.showLabelField"
      .advancedFields="control.props.advancedFields"
      :disabled="!editable"
      @link-change="change"
    />
  </div>
</template>
