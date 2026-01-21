<script setup lang="ts">
  import type {Site} from '@/types';
  import {ref} from 'vue';
  import DeleteSiteModal from '@/components/DeleteSiteModal.vue';

  withDefaults(
    defineProps<{
      site: Site;
    }>(),
    {}
  );

  const modalActive = ref(false);
</script>

<template>
  <div>
    <craft-button
      size="small"
      icon
      type="button"
      variant="danger"
      appearance="plain"
      :disabled="site.primary"
      @click="modalActive = true"
    >
      <craft-icon name="x" label="t('Delete site'"></craft-icon>
    </craft-button>

    <DeleteSiteModal
      :site="site"
      :open="modalActive"
      @close="modalActive = false"
    />
  </div>
</template>

<style scoped lang="scss"></style>
