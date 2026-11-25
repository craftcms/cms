<script setup lang="ts">
  /**
   * An import step as a card: a rail with its number, what it imports, its source and its
   * settings at a glance, beside whatever the card is about, such as its mapping or what
   * a run did with its items.
   */
  import {t} from '@craftcms/ui';
  import MetadataItem from '@/common/components/MetadataItem.vue';
  import MetadataList from '@/common/components/MetadataList.vue';
  import StepNumber from './StepNumber.vue';

  defineProps<{
    /** The step's place in its plan, from 1. */
    number: number;
    /** What the step imports, e.g. “Entries”. */
    label: string;
    /** The step's source file's name. */
    source?: string | null;
    /** The source in full, for its tooltip. */
    sourceTitle?: string | null;
    settings: Array<{label: string; value: string}>;
    /** The id of the element that names the step, to label the card by. */
    labelId?: string;
  }>();
</script>

<template>
  <div class="step-card__card">
    <aside class="step-card__rail">
      <div class="flex items-center justify-between gap-sm">
        <StepNumber class="step-card__number" :number="number" />
        <div v-if="$slots.actions" class="flex items-center gap-sm">
          <slot name="actions"></slot>
        </div>
      </div>

      <div class="grid gap-xs">
        <div :id="labelId" class="flex items-center">
          <div class="sr-only">{{ `${t('Step {number}', {number})}: ` }}</div>
          <div class="text-xl/snug font-bold">{{ label }}</div>
          <slot name="label-badge"></slot>
        </div>
        <code
          v-if="source"
          class="step-card__source text-quiet"
          :title="sourceTitle ?? source"
          >{{ source }}</code
        >
      </div>

      <slot name="notice"></slot>

      <MetadataList v-if="settings.length" class="step-card__settings">
        <MetadataItem
          v-for="setting in settings"
          :key="setting.label"
          :label="setting.label"
          >{{ setting.value }}</MetadataItem
        >
      </MetadataList>

      <div v-if="$slots.footer" class="mt-auto pt-md">
        <slot name="footer"></slot>
      </div>
    </aside>

    <section class="step-card__main">
      <slot></slot>
    </section>
  </div>
</template>

<style scoped>
  .step-card__card {
    display: grid;
    grid-template-columns: minmax(14rem, 18rem) minmax(0, 1fr);
    background-color: var(--c-surface-default);
    /* the card's own edge can stand out from the lines within it */
    border: 1px solid
      var(
        --step-card-outline-color,
        var(--step-card-border-color, var(--c-color-neutral-border-quiet))
      );
    border-radius: var(--c-radius-lg);
    color: var(--c-text-default);
    /* clipped rather than hidden, so a sticky footer in it still sticks */
    overflow: clip;
    box-shadow: var(--c-shadow-sm);
  }

  .step-card__rail {
    display: flex;
    flex-direction: column;
    gap: var(--c-spacing-md);
    padding: var(--c-spacing-lg);
    background-color: var(--c-surface-sunken);
    border-inline-end: 1px solid
      var(--step-card-border-color, var(--c-color-neutral-border-quiet));
  }

  /* A lil optical adjustment */
  .step-card__number {
    margin-inline-start: -0.25em;
  }

  .step-card__source {
    overflow-wrap: anywhere;
  }

  .step-card__settings {
    margin-block: var(--c-spacing-md);
  }

  .step-card__main {
    display: flex;
    flex-direction: column;
    gap: var(--c-spacing-md);
    min-inline-size: 0;
    padding: var(--c-spacing-lg);
  }

  @media (width < var(--breakpoint-md)) {
    .step-card__card {
      grid-template-columns: minmax(0, 1fr);
    }

    .step-card__rail {
      border-inline-end: 0;
      border-block-end: 1px solid
        var(--step-card-border-color, var(--c-color-neutral-border-quiet));
    }
  }
</style>
