<script setup lang="ts">
  import {computed} from 'vue';

  const modelValue = defineModel<{
    url: string;
    name: string;
  } | null>();
  const props = withDefaults(
    defineProps<{
      label?: string;
      name?: string;
      buttonLabel?: string;
      helpText?: string;
      thumbnailSize?: string | number;
      disabled?: boolean;
      multiple?: boolean;
      error?: string | null;
    }>(),
    {
      disabled: false,
      buttonLabel: 'Select file',
      multiple: false,
      thumbnailSize: 120,
      error: null,
    }
  );

  const thumbSize = computed(() => {
    // If the value is a string, use it as is
    if (isNaN(Number(props.thumbnailSize))) {
      return props.thumbnailSize;
    }

    return `calc(${props.thumbnailSize}rem / 16)`;
  });

  function handleListChange(event: CustomEvent) {
    // If multiple is true, return the array; otherwise return the first file
    modelValue.value = props.multiple
      ? event.detail?.newFiles
      : event.detail?.newFiles?.[0] || null;
  }

  function handleFileRemoved(event: CustomEvent) {
    modelValue.value = null;
  }

  const uploadResponse = computed(() => {
    if (!modelValue.value) {
      return [];
    }

    const staticValue = Array.isArray(modelValue.value)
      ? modelValue.value
      : [modelValue.value];

    return staticValue.map((value) => {
      return {
        name: value.name,
        status: 'SUCCESS',
        downloadUrl: value.url,
        errorMessage: '',
        id: value.name,
      };
    });
  });
</script>

<template>
  <craft-input-file
    :label="label"
    :name="name"
    :button-label="buttonLabel"
    :help-text="helpText"
    :disabled="disabled"
    :multiple="multiple"
    .uploadResponse="uploadResponse"
    @file-removed="handleFileRemoved"
    @file-list-changed="handleListChange"
    :has-feedback-for="error ? 'error' : ''"
    :type="modelValue ? 'hidden' : 'file'"
    :style="{
      '--thumbnail-size': thumbSize,
    }"
  >
    <ul class="error-list" v-if="error" slot="feedback">
      <li>{{ error }}</li>
    </ul>
  </craft-input-file>
</template>

<style scoped lang="scss">
  .preview {
    display: flex;
    width: v-bind(thumbSize);
  }
</style>
