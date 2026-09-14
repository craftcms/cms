<script setup lang="ts">
  import {t} from '@craftcms/ui';
  import {computed} from 'vue';
  import ElementChips from '@/modules/elements/components/ElementChips.vue';
  import {useSelectable} from '@/common/composables/useSelectable';

  interface ElementReference {
    elementType: string;
    id: number;
    label: string;
    siteId: number | null;
  }

  const props = defineProps<{value: unknown}>();
  const selection = useSelectable<number>({ids: [], enabled: false});
  const elements = computed(() =>
    Array.isArray(props.value) && props.value.every(isElementReference)
      ? props.value
      : null
  );

  function isElementReference(value: unknown): value is ElementReference {
    return (
      typeof value === 'object' &&
      value !== null &&
      'elementType' in value &&
      'id' in value &&
      'label' in value
    );
  }

  function valueText(value: unknown): string {
    if (value === null || (Array.isArray(value) && value.length === 0)) {
      return t('None');
    }

    if (typeof value === 'boolean') {
      return value ? t('Yes') : t('No');
    }

    if (typeof value === 'string' || typeof value === 'number') {
      return String(value);
    }

    return JSON.stringify(value) ?? '';
  }
</script>

<template>
  <ElementChips
    v-if="elements !== null && elements.length > 0"
    :data="elements"
    :selection="selection"
    inline
    read-only
  />
  <span v-else class="activity-timeline-change-value__text">
    {{ valueText(value) }}
  </span>
</template>

<style scoped>
  .activity-timeline-change-value__text {
    white-space: pre-wrap;
  }
</style>
