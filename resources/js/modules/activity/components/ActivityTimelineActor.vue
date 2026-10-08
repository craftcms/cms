<script setup lang="ts">
  import {t} from '@craftcms/ui';
  import {computed} from 'vue';
  import type {ActivityTarget} from '@/modules/activity/composables/useActivityTimeline';

  const props = defineProps<{
    actor: ActivityTarget;
    impersonator: ActivityTarget | null;
    origin?: string | null;
  }>();

  const identities = computed(() => [
    ...(props.impersonator
      ? [{role: 'impersonator', target: props.impersonator}]
      : []),
    {role: 'actor', target: props.actor},
  ]);
</script>

<template>
  <span class="activity-timeline__actor">
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
    <span v-if="origin" class="activity-timeline__origin">
      {{ t('Via {origin}', {origin}) }}
    </span>
  </span>
</template>

<style scoped>
  .activity-timeline__actor {
    overflow-wrap: anywhere;
  }

  .activity-timeline__origin {
    margin-inline-start: 0.25em;
  }

  .activity-timeline__as {
    margin-inline: 0.25em;
  }
</style>
