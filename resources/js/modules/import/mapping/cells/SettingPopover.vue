<script setup lang="ts">
  /**
   * A cell's setting as a button that sums it up and opens a popover of its choices.
   * Without `editable`, the summary is shown as plain text instead.
   */
  import '@craftcms/ui/components/button/button';
  import '@craftcms/ui/components/icon/icon';
  import '@craftcms/ui/components/popover/popover';
  import '@craftcms/ui/components/radio/radio';
  import '@craftcms/ui/components/radio-group/radio-group';
  import {isInitialization} from '@/common/table/cells/types';

  export interface SettingOption<T extends string> {
    value: T;
    label: string;
    instructions?: string;
  }

  const {label, summary, icon, editable, options, value} = defineProps<{
    /** Names the setting and the field it's for, for the popover and its choices. */
    label: string;
    summary: string;
    icon?: string;
    editable: boolean;
    options: SettingOption<string>[];
    value: string;
  }>();

  const emit = defineEmits<{change: [value: string]}>();

  function onChange(event: Event): void {
    // each radio announces its own change too, before the group has caught up with it
    if (isInitialization(event) || event.target !== event.currentTarget) {
      return;
    }

    emit(
      'change',
      String((event.currentTarget as {modelValue?: unknown}).modelValue)
    );
  }
</script>

<template>
  <craft-popover v-if="editable" placement="bottom-start" :label="label">
    <craft-button
      slot="invoker"
      type="button"
      variant="fill"
      align="start"
      class="setting-popover"
    >
      <craft-icon v-if="icon" slot="prefix" :name="icon"></craft-icon>
      <span class="sr-only">{{ `${label}: ` }}</span>
      <span class="setting-popover__summary">{{ summary }}</span>
      <craft-icon slot="suffix" name="chevron-down"></craft-icon>
    </craft-button>

    <div slot="content-body" class="setting-popover__body">
      <craft-radio-group
        :label="label"
        .modelValue="value"
        @model-value-changed="onChange"
      >
        <craft-radio
          v-for="option in options"
          :key="option.value"
          :label="option.label"
          :help-text="option.instructions"
          .choiceValue="option.value"
          .checked="option.value === value"
        ></craft-radio>
      </craft-radio-group>
      <slot></slot>
    </div>
  </craft-popover>

  <span v-else class="setting-popover setting-popover--static">
    <craft-icon v-if="icon" :name="icon"></craft-icon>
    <span class="setting-popover__summary">{{ summary }}</span>
  </span>
</template>

<style scoped>
  .setting-popover {
    max-inline-size: 100%;
  }

  /* reads like the button's label, without being one */
  .setting-popover--static {
    display: inline-flex;
    align-items: center;
    gap: var(--c-spacing-xs);
    min-height: var(--c-size-control-sm);
    font-size: var(--c-text-sm);
  }

  .setting-popover__summary {
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
  }

  .setting-popover__body {
    display: grid;
    gap: var(--c-spacing-md);
    inline-size: min(18rem, calc(100vw - 2rem));
  }
</style>
