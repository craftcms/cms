<script setup lang="ts">
  import {t} from '@craftcms/ui';
  import CpLink from '@/common/components/CpLink.vue';
  import type {ElementEditPayload} from '@/modules/elements/composables/useElementEditor';

  // The panel's own props come through as well; none of them belong on the link.
  defineOptions({inheritAttrs: false});

  defineProps<{
    payload: ElementEditPayload;
  }>();
</script>

<template>
  <CpLink
    v-if="payload.activityPageUrl"
    :href="payload.activityPageUrl"
    class="text-sm me-md"
  >
    {{ t('View all activity') }}
    <craft-icon name="circle-arrow-right" />
  </CpLink>
</template>
