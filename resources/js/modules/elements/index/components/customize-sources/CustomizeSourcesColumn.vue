<script setup lang="ts">
  /**
   * One column of the customize-sources modal: pages, sources, or the selected
   * source's settings. Gives them the same frame, so the columns line up and
   * separate the same way whatever they hold.
   */
  import {useId} from 'vue';

  defineProps<{
    /** The column's heading. Left off when the content brings its own. */
    heading?: string;
    /** Takes the width the fixed-width columns leave over. */
    fill?: boolean;
    /**
     * The column shown when the modal is too narrow for all of them side by
     * side, which then shows one at a time.
     */
    selected?: boolean;
    /**
     * Label for a button back to the previous column, shown only while the
     * columns are one at a time. Left off, there's no button.
     */
    back?: string;
    /**
     * Makes the column a navigation region named by its heading — the page and
     * source lists, which choose what the next column shows.
     */
    nav?: boolean;
  }>();

  const emit = defineEmits<{(e: 'back'): void}>();

  const headingId = useId();
</script>

<template>
  <section
    :class="{
      'cs-column': true,
      'cs-column--fill': fill,
      'cs-column--selected': selected,
    }"
    :role="nav ? 'navigation' : undefined"
    :aria-labelledby="nav && heading ? headingId : undefined"
  >
    <div class="cs-column__back" v-if="back">
      <craft-button
        type="button"
        size="small"
        variant="plain"
        flush
        icon="chevron-left"
        @click="emit('back')"
      >
        {{ back }}
      </craft-button>
    </div>

    <h2 v-if="heading" :id="headingId" class="cs-column__heading">
      {{ heading }}
    </h2>

    <!-- Scrolls on its own, beneath the heading, so a long list doesn't carry
      the other columns with it. -->
    <div class="cs-column__body">
      <slot />

      <!-- The column's own controls, below its content — an add button, say. -->
      <div v-if="$slots.footer" class="cs-column__footer">
        <slot name="footer" />
      </div>
    </div>
  </section>
</template>

<style scoped lang="scss">
  .cs-column {
    display: flex;
    flex-direction: column;
    flex: 0 0 20%;
    min-width: 200px;
    min-height: 0;
    border-inline-end: 1px solid var(--c-color-border-quiet);
    padding-inline-end: var(--c-spacing-lg);
  }

  // Scrolling clips on every side, which would cut off the focus rings of the
  // controls at its edges. Room for them is padded in and taken back out with
  // a matching negative margin, so the content doesn't move.
  .cs-column__body {
    --_ring-room: calc(
      var(--c-focus-outline-width) + max(var(--c-focus-outline-offset), 0px)
    );

    flex: 1;
    min-height: 0;
    overflow-y: auto;
    padding: var(--_ring-room);
    margin: calc(var(--_ring-room) * -1);
  }

  .cs-column + .cs-column {
    padding-inline-start: var(--c-spacing-lg);
  }

  .cs-column__heading {
    margin-block: 0 var(--c-spacing-md);
    font-size: var(--c-text-sm);
    font-weight: bold;
  }

  .cs-column--fill {
    flex: 1;
    min-width: 0;
    border-inline-end: none;
  }

  .cs-column__back {
    display: none;
    margin-block-end: var(--c-spacing-md);
  }

  // Too narrow for three columns side by side, so show one at a time, each
  // with a way back to the one before. The modal's body is the container: an
  // element can't match a container query on itself.
  @container (max-width: 699px) {
    .cs-column,
    .cs-column + .cs-column {
      flex-basis: 100%;
      padding-inline: 0;
      border-inline-end: none;
    }

    .cs-column:not(.cs-column--selected) {
      display: none;
    }

    .cs-column__back {
      display: inline-flex;
    }
  }
</style>
