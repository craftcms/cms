<script setup lang="ts">
  /**
   * One section of a new plan's wizard, as a card. Only the open section shows its body; a
   * finished one collapses to a summary of what was chosen, and opens again to change it.
   */
  import '@craftcms/ui/components/button/button';
  import '@craftcms/ui/components/card/card';
  import '@craftcms/ui/components/icon/icon';
  import {t} from '@craftcms/ui';

  defineProps<{
    title: string;
    /** What the section asks for, shown under its title while it's open. */
    description?: string | null;
    /** What was chosen, shown while the section is collapsed. */
    summary?: string | null;
    /** A `locked` section has nothing it can ask for yet. */
    state: 'open' | 'done' | 'todo' | 'locked';
    /** Keeps the body mounted while it's collapsed, for a form whose values live in it. */
    keepAlive?: boolean;
  }>();

  defineEmits<{
    open: [];
  }>();
</script>

<template>
  <craft-card
    :class="['import-wizard-section', `import-wizard-section--${state}`]"
    :collapsed="state !== 'open' || undefined"
  >
    <h2 slot="label" class="import-wizard-section__heading m-0">
      <button
        type="button"
        class="import-wizard-section__toggle"
        :aria-expanded="state === 'open'"
        :disabled="state === 'open' || state === 'locked'"
        @click="$emit('open')"
      >
        <span
          v-if="state === 'done'"
          class="import-wizard-section__check"
          aria-hidden="true"
        >
          <craft-icon name="check"></craft-icon>
        </span>
        <span class="import-wizard-section__text">
          <span class="import-wizard-section__title-row">
            <span class="import-wizard-section__title">{{ title }}</span>
            <span
              v-if="state !== 'open' && summary"
              class="import-wizard-section__summary"
              >{{ summary }}</span
            >
          </span>
          <span
            v-if="state === 'open' && description"
            class="import-wizard-section__description"
            >{{ description }}</span
          >
        </span>
        <craft-icon
          v-if="state !== 'locked'"
          class="import-wizard-section__chevron"
          :name="state === 'open' ? 'chevron-down' : 'chevron-right'"
          aria-hidden="true"
        ></craft-icon>
      </button>
    </h2>
    <craft-button
      v-if="state === 'done'"
      slot="actions"
      type="button"
      variant="plain"
      size="small"
      :aria-label="t('Edit {title}', {title})"
      @click="$emit('open')"
    >
      {{ t('Edit') }}
    </craft-button>
    <div
      v-if="keepAlive || state === 'open'"
      v-show="state === 'open'"
      class="import-wizard-section__body"
    >
      <slot></slot>
    </div>
    <div
      v-if="state === 'open' && $slots.footer"
      slot="footer"
      class="import-wizard-section__footer"
    >
      <slot name="footer"></slot>
    </div>
  </craft-card>
</template>

<style scoped>
  .import-wizard-section {
    scroll-margin-block-start: var(--c-spacing-lg);
  }

  .import-wizard-section__heading {
    flex: 1;
    min-inline-size: 0;
    font-size: var(--c-text-base);
  }

  .import-wizard-section__toggle {
    display: flex;
    align-items: center;
    gap: var(--c-spacing-sm) var(--c-spacing-md);
    inline-size: 100%;
    padding: 0;
    border: 0;
    background: none;
    color: inherit;
    font: inherit;
    text-align: start;
    cursor: pointer;
  }

  .import-wizard-section__toggle:disabled {
    cursor: default;
  }

  .import-wizard-section--locked .import-wizard-section__toggle {
    color: var(--c-text-quiet);
  }

  .import-wizard-section__check {
    display: inline-grid;
    place-items: center;
    flex: none;
    inline-size: 1.6em;
    block-size: 1.6em;
    border-radius: 50%;
    background-color: var(--c-color-success-fill-loud);
    color: var(--c-color-success-on-loud);
    font-size: 0.85em;
  }

  .import-wizard-section__chevron {
    flex: none;
    margin-inline-start: auto;
    color: var(--c-text-quiet);
    font-size: 0.8em;
  }

  .import-wizard-section__chevron:dir(rtl)[name='chevron-right'] {
    transform: scaleX(-1);
  }

  .import-wizard-section__text {
    display: grid;
    flex: 1;
    gap: calc(var(--c-spacing-xs) / 2);
    min-inline-size: 0;
  }

  .import-wizard-section__title-row {
    display: flex;
    align-items: baseline;
    gap: var(--c-spacing-sm);
    min-inline-size: 0;
  }

  .import-wizard-section__description {
    color: var(--c-text-quiet);
    font-size: var(--c-text-sm);
    font-weight: 400;
  }

  .import-wizard-section__footer {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: var(--c-spacing-sm) var(--c-spacing-md);
  }

  .import-wizard-section__title {
    font-weight: 700;
  }

  .import-wizard-section__summary {
    flex: 1;
    min-inline-size: 0;
    overflow: hidden;
    color: var(--c-text-quiet);
    font-size: var(--c-text-sm);
    font-weight: 400;
    text-overflow: ellipsis;
    white-space: nowrap;
  }

  .import-wizard-section__body {
    display: flex;
    flex-direction: column;
    gap: var(--c-spacing-lg);
  }
</style>
