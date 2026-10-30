<script setup lang="ts">
  /**
   * A card in the AI assistant’s chat, such as a proposed change or an approval
   * request, which the user opens and collapses from its heading. Whether it’s
   * open is the parent’s, so each kind of card decides how it starts.
   *
   * `badge` and `actions` go in the card’s header, and the default slot is its
   * body.
   */
  import {useId} from 'vue';

  defineProps<{
    label: string;
    /**
     * The heading’s id, which the parent’s buttons can be labelled by. The
     * toggle’s is the same, ending in `-toggle`, for the parent to focus it.
     */
    headingId: string;
  }>();

  const expanded = defineModel<boolean>('expanded', {required: true});

  const bodyId = useId();
</script>

<template>
  <article :aria-labelledby="headingId">
    <craft-card padding="md" :collapsed="!expanded">
      <h3 :id="headingId" slot="label" class="assistant-card__label">
        <craft-disclosure
          appearance="plain"
          :state="expanded ? 'expanded' : 'collapsed'"
          @craft-show="expanded = true"
          @craft-hide="expanded = false"
        >
          <button
            :id="`${headingId}-toggle`"
            type="button"
            class="assistant-card__toggle"
            :aria-controls="bodyId"
          >
            <span
              class="assistant-card__chevron"
              :class="{'assistant-card__chevron--collapsed': !expanded}"
            >
              <craft-icon
                :name="expanded ? 'chevron-down' : 'chevron-right'"
              ></craft-icon>
            </span>
            {{ label }}
          </button>
        </craft-disclosure>
      </h3>
      <span v-if="$slots.badge" slot="badge" class="contents">
        <slot name="badge"></slot>
      </span>
      <span v-if="$slots.actions" slot="actions" class="contents">
        <slot name="actions"></slot>
      </span>
      <div :id="bodyId" class="assistant-card__body">
        <slot></slot>
      </div>
    </craft-card>
  </article>
</template>

<style scoped>
  .assistant-card__label {
    font-size: var(--c-text-sm);
    font-weight: 600;
    overflow-wrap: anywhere;
  }

  /* At least 24px tall, with its first line in the middle of the 24px. */
  .assistant-card__toggle {
    display: flex;
    align-items: start;
    gap: var(--c-spacing-xs);
    padding-block: max(0px, (24px - 1lh) / 2);
    padding-inline: 0;
    border: 0;
    background: none;
    color: inherit;
    font: inherit;
    text-align: start;
    cursor: pointer;
  }

  .assistant-card__toggle:focus-visible {
    outline: var(--c-focus-outline-width) var(--c-focus-outline-style)
      var(--c-color-focus-outline);
    outline-offset: var(--c-focus-outline-offset);
  }

  /* As tall as the label's first line, with the icon in its middle. The
    icon's own font size is smaller, so it can't be lined up by itself. */
  .assistant-card__chevron {
    display: flex;
    flex: none;
    align-items: center;
    block-size: 1lh;
  }

  /* Collapsed, it points toward the end of the line. */
  .assistant-card__chevron--collapsed:dir(rtl) {
    transform: scaleX(-1);
  }

  .assistant-card__body {
    display: flex;
    flex-direction: column;
    gap: var(--c-spacing-sm);
  }
</style>
