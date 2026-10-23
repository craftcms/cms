<script setup lang="ts">
  import {Color, t, type ColorValue} from '@craftcms/ui';
  import {computed} from 'vue';
  import type {ReleaseStatus} from '@/modules/releases/types';

  interface StatusPresentation {
    fill: ColorValue;
    label: () => string;
  }

  const statusPresentations: Record<ReleaseStatus, StatusPresentation> = {
    draft: {fill: Color.Gray, label: () => t('Draft')},
    scheduled: {fill: Color.Info, label: () => t('Scheduled')},
    publishing: {fill: Color.Info, label: () => t('Publishing')},
    published: {fill: Color.Success, label: () => t('Published')},
    failed: {fill: Color.Danger, label: () => t('Failed')},
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
