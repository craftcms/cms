<script setup lang="ts">
  import {t} from '@craftcms/ui';
  import {usePhotoColors} from '@/common/composables/usePhotoColors';
  import type {ActivityTarget} from '@/modules/activity/composables/useActivityTimeline';
  import ActivityTimelineActor from './ActivityTimelineActor.vue';

  const props = withDefaults(
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

  const photoColors = usePhotoColors(() => props.actor.photoColors);

  function sentenceFragment(text: string | null): string {
    return text === null
      ? t('commented')
      : text.charAt(0).toLocaleLowerCase() + text.slice(1);
  }
</script>

<template>
  <div class="activity-comment-card" :style="photoColors">
    <div class="activity-comment-card__header">
      <div class="activity-comment-card__heading">
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

      <span v-if="edited" class="activity-comment-card__badge">
        {{ t('Edited') }}
      </span>

      <div v-if="$slots.actions" class="activity-comment-card__actions">
        <slot name="actions" />
      </div>
    </div>

    <div class="activity-comment-card__body">
      <div v-html="html" />

      <slot />
    </div>
  </div>
</template>

<style scoped>
  .activity-comment-card {
    position: relative;
    overflow: clip;
    border: 1px solid var(--c-border-raised);
    border-radius: var(--c-radius-md);
    background-color: color-mix(var(--c-surface-white), transparent 70%);
    background-image: linear-gradient(
      to top right,
      color-mix(
        in srgb,
        var(--photo-color-start, transparent) 15%,
        transparent
      ),
      color-mix(in srgb, var(--photo-color-end, transparent) 15%, transparent)
    );
    box-shadow: var(--c-shadow-xs);
    color: var(--c-text-default);
  }

  .activity-comment-card__header {
    display: flex;
    align-items: center;
    gap: var(--c-spacing-md);
    min-block-size: calc(var(--c-size-control-sm) + var(--c-spacing-xs) * 2);
    padding-block: var(--c-spacing-xs);
    padding-inline: var(--c-spacing-md);
    border-block-end: 1px solid var(--c-border-raised);
    background-color: var(--c-surface-white);
    font-size: 0.875em;
    box-sizing: border-box;
  }

  .activity-comment-card__heading {
    flex: 1;
    min-inline-size: 0;
  }

  .activity-comment-card__description {
    margin-inline-start: 0.25em;
  }

  .activity-comment-card__actions {
    display: flex;
    align-items: center;
  }

  .activity-comment-card__badge {
    color: var(--c-text-quiet);
    font-size: var(--c-text-xs);
  }

  .activity-comment-card__body {
    display: grid;
    gap: var(--c-spacing-md);
    padding: var(--c-spacing-md);
    font-size: 0.875em;
    overflow-wrap: anywhere;
  }

  .activity-comment-card__body :deep(> div > :last-child) {
    margin: 0;
  }
</style>
