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
  import {useUiPreview} from './uiPreview';

  type LinkControlProps = {
    types: LinkTypeConfig[];
    showLabelField?: boolean;
    advancedFields?: LinkAdvancedField[];
    compact?: boolean;
    registeredTypes?: string[] | null;
  };
  type LinkValue = Pick<LinkFieldValue, 'type' | 'value'> &
    Partial<Omit<LinkFieldValue, 'type' | 'value'>>;

  const props = defineProps<{
    control: UiControlPayload<LinkControlProps>;
    value: LinkValue;
    editable: boolean;
    label?: string;
  }>();
  const emit = defineEmits<{
    (
      event: 'update:value',
      value: LinkValue,
      kind: LinkChangeDetail['kind']
    ): void;
  }>();

  const previewing = useUiPreview();

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
  <craft-link-field
    :types="control.props.types"
    .modelValue="value"
    :name="editable ? inputName(control.path) : ''"
    .live="true"
    .compact="Boolean(control.props.compact)"
    .contextLabel="label ?? ''"
    .showLabelField="control.props.showLabelField"
    .advancedFields="control.props.advancedFields"
    .disabled="!editable"
    .previewing="previewing"
    @link-change="change"
  />
</template>
