<script setup lang="ts">
  import type {TextExpanderTriggers} from '@craftcms/ui';
  import '@craftcms/ui/components/text-expander/text-expander';
  import {useId} from 'vue';
  import '../markdown-field/markdown-field';
  import type {LinkTypeConfig} from '../link-field/craft-link-field';
  import type {UiControlPayload} from './types';
  import {inputName} from './runtime';

  type MarkdownControlProps = {
    rows?: number;
    placeholder?: string;
    maxLength?: number;
    toolbarButtons?: string[];
    showToolbar?: boolean;
    showStats?: boolean;
    types?: LinkTypeConfig[];
    showLabelField?: boolean;
    advancedFields?: string[];
    assetSources?: string[];
    assetCondition?: Record<string, unknown> | null;
    assetAnyUploader?: boolean;
    uploadFolderId?: number | null;
    uploadSiteId?: number | null;
    textExpanderTriggers?: TextExpanderTriggers;
  };

  defineProps<{
    control: UiControlPayload<MarkdownControlProps>;
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
    .showToolbar="control.props.showToolbar ?? true"
    .showStats="control.props.showStats ?? false"
    .linkTypes="control.props.types ?? []"
    .showLinkLabelField="control.props.showLabelField ?? false"
    .linkAdvancedFields="control.props.advancedFields ?? []"
    .assetSources="control.props.assetSources ?? []"
    .assetCondition="control.props.assetCondition ?? null"
    .assetAnyUploader="control.props.assetAnyUploader ?? false"
    .uploadFolderId="editable ? (control.props.uploadFolderId ?? null) : null"
    .uploadSiteId="control.props.uploadSiteId ?? ''"
    sanitize-html
    .readonly="control.mode === 'readOnly'"
    .disabled="!editable && control.mode !== 'readOnly'"
    .required="editable && required"
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
