<script setup lang="ts">
  /**
   * A list of facts, each a `MetadataItem`, laid out as the Info sidebar lays out an
   * element's. See `craft-metadata-list` for the layouts.
   */
  import '@craftcms/ui/components/metadata-list/metadata-list';
  import type {MetadataListLayout} from '@craftcms/ui';

  const {layout = 'auto'} = defineProps<{
    layout?: MetadataListLayout;
  }>();
</script>

<template>
  <craft-metadata-list :layout="layout">
    <dl>
      <slot></slot>
    </dl>
  </craft-metadata-list>
</template>
