<script setup lang="ts">
  /** Expands or collapses a structure row's descendants. */
  import '@craftcms/ui/components/button/button';
  import '@craftcms/ui/components/icon/icon';
  import {t} from '@craftcms/ui';

  defineProps<{
    expanded: boolean;
    /** The row's label, which names what the toggle shows or hides. */
    label: string;
    /** Whether a request to show or hide the descendants is still in flight. */
    pending?: boolean;
    flush?: 'inline-start';
  }>();

  defineEmits<{
    toggle: [];
  }>();
</script>

<template>
  <craft-button
    class="cp-table-structure-toggle"
    type="button"
    variant="plain"
    size="small"
    :flush="flush"
    icon
    .loading="pending ?? false"
    :aria-expanded="String(expanded)"
    @click.stop="$emit('toggle')"
  >
    <craft-icon
      :name="expanded ? 'chevron-down' : 'chevron-right'"
      :label="
        expanded
          ? t('Collapse {title}', {title: label})
          : t('Expand {title}', {title: label})
      "
    ></craft-icon>
  </craft-button>
</template>

<style scoped>
  .cp-table-structure-toggle:dir(rtl) craft-icon[name='chevron-right'] {
    transform: scaleX(-1);
  }
</style>
