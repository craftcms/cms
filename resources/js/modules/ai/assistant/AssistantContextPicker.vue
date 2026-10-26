<script setup lang="ts">
  /**
   * An element’s chip in the assistant’s context, which chooses which of its
   * editor’s fields are in the next message’s context: all of them, some, or
   * none.
   */
  import {t} from '@craftcms/ui';
  import '@craftcms/ui/components/checkbox-select/checkbox-select';
  import type CraftCheckboxSelect from '@craftcms/ui/components/checkbox-select/checkbox-select';
  import {computed} from 'vue';
  import type {AssistantEditor} from './context';
  import {
    elementContextFields,
    elementFieldOptions,
    loadElementFields,
    setElementContext,
  } from './element-context';

  const props = defineProps<{
    editor: AssistantEditor;
    /** The chip’s label, which names the element and its chosen fields. */
    label: string;
  }>();

  const ALL_FIELDS = '*';

  const options = computed(() => elementFieldOptions(props.editor));

  const selected = computed<string[]>(
    () =>
      elementContextFields(props.editor)?.map((field) => field.path) ?? [
        ALL_FIELDS,
      ]
  );

  /** Loads the fields each time the picker opens. */
  function load(): Promise<void> {
    return loadElementFields(props.editor);
  }

  function onChange(event: Event): void {
    // Its checkboxes fire their own `model-value-changed`, which bubbles up
    // to this listener too.
    if (event.target !== event.currentTarget) {
      return;
    }

    // SAFETY: The listener is bound to the `craft-checkbox-select`.
    const value = (event.currentTarget as CraftCheckboxSelect).modelValue;

    setElementContext(
      props.editor,
      value.includes(ALL_FIELDS)
        ? null
        : options.value
            .filter((option) => value.includes(option.value))
            .map((option) => ({path: option.value, label: option.label}))
    );
  }
</script>

<template>
  <craft-popover
    :label="t('Fields of “{name}”', {name: editor.label()})"
    placement="top-start"
    @craft-show="load"
  >
    <craft-button
      slot="invoker"
      type="button"
      variant="plain"
      size="small"
      flush
      icon="chevron-down"
      icon-position="suffix"
    >
      {{ label }}
      <span class="sr-only">{{ t('Choose fields') }}</span>
    </craft-button>
    <div slot="content" class="assistant-context-picker">
      <p class="assistant-context-picker__note">
        {{
          t(
            'Built-in details, such as the title, slug, and dates, are always included.'
          )
        }}
      </p>
      <craft-checkbox-select
        :all-label="t('All fields')"
        :all-value="ALL_FIELDS"
        .options="options"
        .modelValue="selected"
        @model-value-changed="onChange"
      ></craft-checkbox-select>
    </div>
  </craft-popover>
</template>

<style scoped>
  .assistant-context-picker {
    display: flex;
    flex-direction: column;
    gap: var(--c-spacing-sm);
    max-block-size: 20rem;
    max-inline-size: 18rem;
    overflow-y: auto;
    padding: var(--c-spacing-sm);
    white-space: normal;
  }

  .assistant-context-picker__note {
    color: var(--c-text-quiet);
    font-size: var(--c-text-sm);
  }
</style>
