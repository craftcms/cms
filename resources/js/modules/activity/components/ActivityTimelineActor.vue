<script setup lang="ts">
  import {t} from '@craftcms/ui';
  import {computed} from 'vue';
  import type {ActivityTarget} from '@/modules/activity/composables/useActivityTimeline';

  const props = defineProps<{
    actor: ActivityTarget;
    impersonator: ActivityTarget | null;
  }>();

  const identities = computed(() => [
    ...(props.impersonator
      ? [{role: 'impersonator', target: props.impersonator}]
      : []),
    {role: 'actor', target: props.actor},
  ]);
</script>

<template>
  <template v-for="(identity, index) in identities" :key="identity.role">
    <span v-if="index" class="activity-timeline__as">{{ t('as') }}</span>
    <a
      v-if="identity.target.url"
      :href="identity.target.url"
      class="font-semibold"
    >
      {{ identity.target.label }}
    </a>
    <span v-else class="font-semibold">
      {{ identity.target.label }}
      <span v-if="identity.target.deleted">({{ t('deleted') }})</span>
    </span>
  </template>
</template>

<style scoped>
  .activity-timeline__as {
    margin-inline: 0.25em;
  }
</style>
