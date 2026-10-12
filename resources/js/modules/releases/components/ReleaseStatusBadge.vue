<script setup lang="ts">
  import {t} from '@craftcms/ui';
  import {computed} from 'vue';
  import type {ReleaseStatus} from '@/modules/releases/types';

  interface StatusPresentation {
    fill: 'neutral' | 'info' | 'success' | 'error';
    label: () => string;
  }

  const statusPresentations: Record<ReleaseStatus, StatusPresentation> = {
    draft: {fill: 'neutral', label: () => t('Draft')},
    scheduled: {fill: 'info', label: () => t('Scheduled')},
    publishing: {fill: 'info', label: () => t('Publishing')},
    published: {fill: 'success', label: () => t('Published')},
    failed: {fill: 'error', label: () => t('Failed')},
  };

  const props = defineProps<{status: ReleaseStatus}>();
  const presentation = computed(() => {
    const {fill, label} = statusPresentations[props.status];

    return {fill, label: label()};
  });
</script>

<template>
  <craft-badge :fill="presentation.fill">{{ presentation.label }}</craft-badge>
</template>
