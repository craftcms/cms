<script setup lang="ts">
  /** A table cell's checkbox, labeled for screen readers only. */
  import '@craftcms/ui/components/checkbox/checkbox';

  defineProps<{
    checked: boolean;
    label: string;
    disabled?: boolean;
  }>();

  const emit = defineEmits<{change: [checked: boolean]}>();

  function onChange(event: Event): void {
    emit(
      'change',
      Boolean((event.currentTarget as {checked?: boolean}).checked)
    );
  }
</script>

<template>
  <div class="cp-table-control">
    <craft-checkbox
      label-sr-only
      .checked="checked"
      .disabled="disabled"
      @change="onChange"
    >
      <label slot="label">{{ label }}</label>
    </craft-checkbox>
  </div>
</template>

<style scoped>
  .cp-table-control {
    display: flex;
    align-items: center;
    min-height: var(--c-size-control-md);
  }
</style>
