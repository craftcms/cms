<script setup lang="ts">
  /**
   * One labelled run of results — Recents, Actions, or Results — as a group
   * in the listbox popup of the palette's search combobox.
   *
   * The options take no focus and hold no controls: the search input keeps
   * focus, the arrow keys move the active option, which the input points to
   * with `aria-activedescendant`, and a click chooses one. Each option's text
   * — its label, hint and description, and whether Tab reaches its actions —
   * is its accessible name. A result's
   * own actions live outside the listbox, in `ResultActions`, which sits over
   * the space kept for it here.
   */
  import {t} from '@craftcms/ui';
  import {hasActions, type CommandPaletteItem, type ResultRow} from '../types';

  withDefaults(
    defineProps<{
      groupId: string;
      heading: string;
      /**
       * Whether it's a named group in the listbox. A lone section isn't, as the
       * listbox's own name says the same: its heading is only seen.
       */
      grouped?: boolean;
      rows: ResultRow[];
      selectedIndex: number;
      /** The row under the pointer, highlighted while its actions sit over it. */
      hoveredIndex?: number;
    }>(),
    {grouped: true, hoveredIndex: -1}
  );

  const emit = defineEmits<{
    (event: 'activate', item: CommandPaletteItem, trigger: MouseEvent): void;
    (event: 'hover', index: number): void;
  }>();

  /**
   * The icon's color, as `craft-action-item` paints it: the color's loud
   * tone, which stays vibrant in both themes. Black and white, which would
   * vanish against one theme's surface, take the text color instead.
   */
  function iconColorStyle(color?: string | null): Record<string, string> {
    if (!color) {
      return {};
    }

    return {
      color:
        color === 'black' || color === 'white'
          ? 'var(--c-text-default)'
          : `var(--c-color-${color}-fill-loud, currentColor)`,
    };
  }
</script>

<template>
  <div
    :id="groupId"
    :role="grouped ? 'group' : undefined"
    class="command-palette-section"
    :aria-labelledby="grouped ? `${groupId}-label` : undefined"
  >
    <div
      :id="`${groupId}-label`"
      :role="grouped ? 'presentation' : undefined"
      :aria-hidden="grouped ? undefined : 'true'"
      class="command-palette-section__heading"
    >
      {{ heading }}
    </div>
    <!-- A press keeps focus in the search input, which owns the active option. -->
    <div
      v-for="row in rows"
      :id="row.optionId"
      :key="row.item.id"
      role="option"
      class="command-palette-row"
      :class="{
        'command-palette-row--selected': row.index === selectedIndex,
        'command-palette-row--hovered': row.index === hoveredIndex,
        'command-palette-row--danger': row.item.variant === 'danger',
      }"
      :aria-selected="row.index === selectedIndex ? 'true' : 'false'"
      @mousedown.prevent
      @click="emit('activate', row.item, $event)"
      @pointermove="emit('hover', row.index)"
    >
      <span class="command-palette-row__icon" aria-hidden="true">
        <span
          v-if="row.item.thumbHtml"
          class="command-palette-row__thumb"
          v-html="row.item.thumbHtml"
        ></span>
        <span
          v-else-if="row.item.iconSvg"
          class="command-palette-row__svg"
          v-html="row.item.iconSvg"
        ></span>
        <craft-icon
          v-else
          :name="row.item.icon ?? 'arrow-right'"
          :style="
            iconColorStyle(
              row.item.variant === 'danger' ? null : row.item.iconColor
            )
          "
        ></craft-icon>
      </span>
      <span
        v-if="row.item.labelHtml"
        class="command-palette-row__label"
        v-html="row.item.labelHtml"
      ></span>
      <span v-else class="command-palette-row__label">{{
        row.item.label
      }}</span>
      <!-- Room for the result's actions, which sit over it from outside the listbox. -->
      <span
        v-if="hasActions(row.item)"
        class="command-palette-row__actions"
        data-command-palette-actions-slot
      ></span>
      <span
        v-if="row.item.hintHtml"
        class="command-palette-row__hint"
        :lang="row.item.hintLang ?? undefined"
        v-html="row.item.hintHtml"
      ></span>
      <span
        v-else-if="row.item.hint"
        class="command-palette-row__hint"
        :lang="row.item.hintLang ?? undefined"
        >{{ row.item.hint }}</span
      >
      <span
        v-if="row.item.description"
        class="command-palette-row__description"
      >
        {{ row.item.description }}
      </span>
      <!-- Read with the option, as the button beside it is only seen. -->
      <span v-if="hasActions(row.item)" class="sr-only">{{
        t('Press Tab for actions')
      }}</span>
    </div>
  </div>
</template>

<style scoped>
  .command-palette-section__heading {
    margin: 0;
    padding-block: var(--c-spacing-sm) var(--c-spacing-xs);
    padding-inline: var(--c-spacing-md);
    font-size: var(--c-text-sm);
    font-weight: var(--font-weight-semibold);
    color: var(--c-text-quiet);
  }

  /* Only after another section — not after the loading spinner, which comes first while an item runs. */
  .command-palette-section
    ~ .command-palette-section
    .command-palette-section__heading {
    margin-block-start: var(--c-spacing-sm);
  }

  /*
   * The icon, the label, the menu and the description share the first line,
   * so the icon centers on the label; the hint has the second to itself. The
   * hint reaches into the description's flexible track, so it never widens
   * the label's and pushes the menu along.
   */
  .command-palette-row {
    --_radius: var(--c-radius-sm);
    position: relative;
    display: grid;
    grid-template-columns: auto fit-content(60%) auto minmax(0, 1fr);
    grid-template-rows: auto auto;
    align-content: center;
    align-items: center;
    column-gap: var(--c-spacing-xs);
    min-block-size: var(--c-size-touch-target);
    padding-block: var(--c-spacing-xs);
    padding-inline: var(--c-spacing-md);
    border-radius: var(--_radius);
    cursor: pointer;
  }

  .command-palette-row + .command-palette-row {
    margin-block-start: var(--c-spacing-xs);
  }

  .command-palette-row--hovered:not(.command-palette-row--selected) {
    background-color: var(--c-color-neutral-fill-quiet);
    color: var(--c-color-neutral-on-quiet);
  }

  .command-palette-row--danger.command-palette-row--hovered:not(
      .command-palette-row--selected
    ) {
    background-color: var(--c-color-danger-fill-quiet);
    color: var(--c-color-danger-on-quiet);
  }

  .command-palette-row--selected {
    background-color: var(--c-color-neutral-fill-loud);
    color: var(--c-color-neutral-on-loud);
  }

  /* Still red once selected: the danger pair stands in for the neutral one. */
  .command-palette-row--selected.command-palette-row--danger {
    background-color: var(--c-color-danger-fill-normal);
    color: var(--c-color-danger-on-normal);
  }

  .command-palette-row--danger:not(.command-palette-row--selected)
    :is(.command-palette-row__label, .command-palette-row__icon) {
    color: var(--c-color-danger-on-quiet);
  }

  .command-palette-row__icon {
    grid-column: 1;
    grid-row: 1;
    margin-inline-end: var(--c-spacing-xs);
    display: inline-flex;
    flex-shrink: 0;
    align-items: center;
    justify-content: center;
    width: 1.5rem;
    height: 1.5rem;
  }

  .command-palette-row__thumb,
  .command-palette-row__svg {
    display: contents;
  }

  .command-palette-row__thumb :deep(craft-thumbnail) {
    --c-thumbnail-size: 1.5rem;
    --c-thumbnail-image-radius: max(
      2px,
      calc(var(--c-radius-md) - 1px - round(0.125em, 1px))
    );
  }

  .command-palette-row__thumb :deep(img),
  .command-palette-row__svg :deep(svg) {
    max-width: 100%;
    max-height: 100%;
    object-fit: contain;
  }

  .command-palette-row__svg :deep(svg) {
    fill: currentColor;
  }

  .command-palette-row__label {
    grid-column: 2;
    grid-row: 1;
    min-inline-size: 0;
    overflow-wrap: anywhere;
  }

  .command-palette-row__hint {
    grid-column: 2 / -1;
    grid-row: 2;
    font-size: var(--c-text-sm);
    color: var(--c-text-quiet);
    overflow-wrap: anywhere;
  }

  .command-palette-row--selected .command-palette-row__hint {
    color: inherit;
  }

  /* Takes whatever room the label leaves. */
  .command-palette-row__description {
    grid-column: 4;
    grid-row: 1;
    font-size: var(--c-text-sm);
    color: var(--c-text-quiet);
    text-align: end;
    overflow-wrap: anywhere;
  }

  .command-palette-row--selected .command-palette-row__description {
    color: inherit;
  }

  .command-palette-row__actions {
    grid-column: 3;
    grid-row: 1;
    inline-size: var(--c-size-control-sm);
    block-size: var(--c-size-control-sm);
  }

  @media (forced-colors: active) {
    .command-palette-row--selected {
      outline: 1px solid Highlight;
    }
  }
</style>
