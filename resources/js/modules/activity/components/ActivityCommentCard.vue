<script setup lang="ts">
  import {t} from '@craftcms/ui';
  import type {ActivityTarget} from '@/modules/activity/composables/useActivityTimeline';
  import ActivityTimelineActor from './ActivityTimelineActor.vue';

  withDefaults(
    defineProps<{
      actor: ActivityTarget;
      impersonator: ActivityTarget | null;
      descriptionText?: string | null;
      descriptionHtml?: string | null;
      html: string | null;
      occurredAt: string;
      formattedOccurredAt: {
        time: string;
        full: string;
      };
      edited?: boolean;
    }>(),
    {
      descriptionText: null,
      descriptionHtml: null,
      edited: false,
    }
  );

  function sentenceFragment(text: string | null): string {
    return text === null
      ? t('commented.')
      : text.charAt(0).toLocaleLowerCase() + text.slice(1);
  }
</script>

<template>
  <craft-card>
    <div slot="label" class="activity-comment-card__heading">
      <ActivityTimelineActor :actor="actor" :impersonator="impersonator" />
      <span
        v-if="descriptionHtml"
        class="activity-comment-card__description"
        v-html="descriptionHtml"
      />
      <span v-else class="activity-comment-card__description">
        {{ sentenceFragment(descriptionText) }}
      </span>
    </div>

    <div v-if="$slots.actions" slot="actions">
      <slot name="actions" />
    </div>

    <div class="activity-comment-card__body" v-html="html" />

    <slot />

    <div slot="footer" class="activity-comment-card__footer">
      <span v-if="edited">{{ t('Edited') }}</span>
      <time :datetime="occurredAt" :title="formattedOccurredAt.full">
        {{ formattedOccurredAt.time }}
      </time>
    </div>
  </craft-card>
</template>

<style scoped>
  .activity-comment-card__description {
    margin-inline-start: 0.25em;
  }

  .activity-comment-card__footer {
    display: flex;
    width: 100%;
    color: var(--c-text-quiet);
    font-size: var(--c-text-xs);
  }

  .activity-comment-card__footer time {
    margin-inline-start: auto;
    white-space: nowrap;
  }

  .activity-comment-card__body :deep(> :last-child) {
    margin: 0;
  }
</style>
