<script setup lang="ts">
  /** Who else is working on the element right now. */
  import {t} from '@craftcms/ui';
  import type {ElementActivityEntry} from '@/modules/elements/composables/useElementActivity';

  defineProps<{
    entries: ReadonlyArray<ElementActivityEntry>;
  }>();
</script>

<template>
  <div
    v-if="entries.length"
    role="region"
    :aria-label="t('Recent Activity')"
    class="flex items-center gap-1"
  >
    <span
      v-for="entry in entries"
      :key="entry.userId"
      :title="entry.message"
      :aria-label="entry.message"
      class="element-activity-avatar inline-flex"
      v-html="entry.userThumb"
    />
  </div>
</template>

<style scoped>
  .element-activity-avatar {
    --c-thumb-icon-size: var(--c-size-control-sm);
    --c-thumbnail-size: var(--c-size-control-sm);
  }
</style>
