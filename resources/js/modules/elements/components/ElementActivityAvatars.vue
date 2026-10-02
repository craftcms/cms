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
      class="inline-flex"
      v-html="entry.userThumb"
    />
  </div>
</template>
