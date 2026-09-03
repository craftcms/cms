<script setup lang="ts">
  /**
   * Stands in for the part of the form that a `rebuildsForm` control replaces.
   *
   * Renders nothing until such a refresh is in flight; {@link FormNodeList}
   * drops the sibling nodes after this one for the same span, so the region
   * about to be replaced reads as pending rather than as settings that still
   * apply.
   */
  import {inject} from 'vue';
  // Deep import: the package barrel would pull in every component.
  import {t} from '@craftcms/ui/utilities/translate';
  import {FormRebuilding} from './runtime';

  const rebuilding = inject(FormRebuilding, undefined);
</script>

<template>
  <div v-if="rebuilding" class="flex justify-center py-8">
    <craft-spinner :label="t('Loading')" />
  </div>
</template>
