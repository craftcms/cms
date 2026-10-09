<script setup lang="ts">
  import {t} from '@craftcms/ui';
  import type {ActivityTarget} from '@/modules/activity/composables/useActivityTimeline';
  import ActivityTimelineActor from './ActivityTimelineActor.vue';

  withDefaults(
    defineProps<{
      actor: ActivityTarget;
      impersonator: ActivityTarget | null;
      origin?: string | null;
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
      ? t('commented')
      : text.charAt(0).toLocaleLowerCase() + text.slice(1);
  }
</script>

<template>
  <craft-card data-color="white">
    <div slot="label" class="activity-comment-card__heading">
      <ActivityTimelineActor
        :actor="actor"
        :impersonator="impersonator"
        :origin="origin"
      />
      <span
        v-if="descriptionHtml"
        class="activity-comment-card__description"
        v-html="descriptionHtml"
      />
      <span v-else class="activity-comment-card__description">
        {{ sentenceFragment(descriptionText) }}
      </span>
    </div>

    <div
      v-if="$slots.actions"
      slot="actions"
      class="activity-comment-card__actions"
    >
      <slot name="actions" />
    </div>

    <div class="activity-comment-card__body" v-html="html" />

    <slot />

    <div slot="badge" class="activity-comment-card__badge">
      <span v-if="edited">{{ t('Edited') }}</span>
    </div>
  </craft-card>
</template>

<style scoped>
  .activity-comment-card__description {
    margin-inline-start: 0.25em;
  }

  .activity-comment-card__actions {
    display: flex;
    align-items: center;
  }

  .activity-comment-card__badge {
    display: flex;
    align-items: center;
    color: var(--c-text-quiet);
    font-size: var(--c-text-xs);
  }

  .activity-comment-card__body :deep(> :last-child) {
    margin: 0;
  }
</style>
