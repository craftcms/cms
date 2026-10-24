<script setup lang="ts">
  /**
   * One labelled run of results — Recents, Actions, or Results.
   *
   * A grid, as the popup of the palette's search combobox: each row has the
   * item itself, a menu of its secondary actions right after its label when
   * it has any, and its description at the end. The item's hint sits below
   * its label, clear of the menu, and the hint and description both describe
   * the item for screen readers. The item's hit area covers the whole row,
   * menu excepted.
   * The search input keeps focus while the arrow keys move the selection,
   * which it points to with `aria-activedescendant`; Tab moves into the rows
   * themselves.
   */
  import {t, type BaseAction} from '@craftcms/ui';
  import CpLink from '@/common/components/CpLink.vue';
  import ActionMenu from '@/common/components/ActionMenu.vue';
  import type {ActionItem} from '@/common/types';
  import {createElementActionMenu} from '@/modules/elements/composables/useElementActionMenu';
  import type {CommandPaletteItem} from '../types';

  export interface ResultRow {
    item: CommandPaletteItem;
    /** Its position across every section, which the selection is kept by. */
    index: number;
    /** The id of the cell holding the item, for `aria-activedescendant`. */
    cellId: string;
  }

  defineProps<{
    gridId: string;
    heading: string;
    rows: ResultRow[];
    selectedIndex: number;
  }>();

  const emit = defineEmits<{
    (event: 'activate', item: CommandPaletteItem, trigger: MouseEvent): void;
    (event: 'select', index: number): void;
    /** A menu action was chosen, and the palette should get out of its way. */
    (event: 'dismiss'): void;
  }>();

  /** The hint and description, which screen readers read after the item's label. */
  function describedBy(row: ResultRow): string | undefined {
    const ids = [
      row.item.hint ? `${row.cellId}-hint` : null,
      row.item.description ? `${row.cellId}-description` : null,
    ].filter((id) => id !== null);

    return ids.length ? ids.join(' ') : undefined;
  }

  /** Items with nothing to do but go somewhere are links; everything else is a button. */
  function isLink(item: CommandPaletteItem): boolean {
    return Boolean(item.href && !item.view && !item.run && !item.action);
  }

  const elementActionItems = createElementActionMenu();

  function hasMenu(item: CommandPaletteItem): boolean {
    return Boolean(item.actions?.length || item.elementActions?.length);
  }

  /**
   * The item's menu: its own actions, then the element's, mapped the way
   * element chips map them. Choosing any of them closes the palette first, so
   * a slideout or modal it opens isn't left behind the palette.
   */
  function menuActions(item: CommandPaletteItem): ActionItem[] {
    const own = (item.actions ?? []).map(
      (action): ActionItem =>
        action.href
          ? {
              type: 'link',
              href: action.href,
              label: action.label ?? '',
              icon: action.icon ?? undefined,
              external: action.external,
              attrs: action.linkAttributes as Record<string, string>,
            }
          : {
              label: action.label ?? '',
              icon: action.icon ?? undefined,
              action: (action.action as BaseAction | null) ?? undefined,
              variant: action.variant ?? undefined,
            }
    );

    return [...own, ...elementActionItems(item.elementActions ?? [])].map(
      (action): ActionItem => {
        if (
          action.type === 'hr' ||
          action.type === 'display' ||
          action.type === 'group'
        ) {
          return action;
        }

        const onClick = action.onClick;

        return {
          ...action,
          onClick: (event: Event) => {
            emit('dismiss');
            onClick?.(event);
          },
        };
      }
    );
  }

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

  function onClick(row: ResultRow, event: MouseEvent): void {
    emit('activate', row.item, event);
  }
</script>

<template>
  <section class="command-palette-section">
    <h2 :id="`${gridId}-heading`" class="command-palette-section__heading">
      {{ heading }}
    </h2>
    <div role="grid" :id="gridId" :aria-labelledby="`${gridId}-heading`">
      <div
        v-for="row in rows"
        :key="row.item.id"
        role="row"
        class="command-palette-row"
        :class="{
          'command-palette-row--selected': row.index === selectedIndex,
          'command-palette-row--danger': row.item.variant === 'danger',
        }"
        :aria-selected="row.index === selectedIndex ? 'true' : 'false'"
      >
        <div role="gridcell" :id="row.cellId" class="command-palette-row__cell">
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
          <component
            :is="
              isLink(row.item) && !row.item.external
                ? CpLink
                : isLink(row.item)
                  ? 'a'
                  : 'button'
            "
            :href="isLink(row.item) ? row.item.href : undefined"
            :type="isLink(row.item) ? undefined : 'button'"
            class="command-palette-row__item"
            :data-command-palette-index="row.index"
            :aria-describedby="describedBy(row)"
            @click="onClick(row, $event)"
            @focus="emit('select', row.index)"
          >
            <span
              v-if="row.item.labelHtml"
              class="command-palette-row__label"
              v-html="row.item.labelHtml"
            ></span>
            <span v-else class="command-palette-row__label">{{
              row.item.label
            }}</span>
          </component>
          <span
            v-if="row.item.hintHtml"
            :id="`${row.cellId}-hint`"
            class="command-palette-row__hint"
            :lang="row.item.hintLang ?? undefined"
            v-html="row.item.hintHtml"
          ></span>
          <span
            v-else-if="row.item.hint"
            :id="`${row.cellId}-hint`"
            class="command-palette-row__hint"
            :lang="row.item.hintLang ?? undefined"
            >{{ row.item.hint }}</span
          >
        </div>
        <div role="gridcell" class="command-palette-row__actions">
          <ActionMenu
            v-if="hasMenu(row.item)"
            :actions="menuActions(row.item)"
            :label="t('Actions for {item}', {item: row.item.label})"
          />
        </div>
        <div
          role="gridcell"
          :id="`${row.cellId}-description`"
          class="command-palette-row__description"
        >
          {{ row.item.description }}
        </div>
      </div>
    </div>
  </section>
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
  }

  .command-palette-row + .command-palette-row {
    margin-block-start: var(--c-spacing-xs);
  }

  @media (hover: hover) {
    .command-palette-row:hover:not(.command-palette-row--selected) {
      background-color: var(--c-color-neutral-fill-quiet);
      color: var(--c-color-neutral-on-quiet);
    }

    .command-palette-row--danger:hover:not(.command-palette-row--selected) {
      background-color: var(--c-color-danger-fill-quiet);
      color: var(--c-color-danger-on-quiet);
    }
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

  .command-palette-row:has(.command-palette-row__item:focus-visible)::before {
    --_ring-reach: calc(
      var(--c-focus-outline-offset) + var(--c-focus-outline-width)
    );
    content: '';
    position: absolute;
    inset: calc(var(--_ring-reach) * -1);
    border: var(--c-focus-outline-width) var(--c-focus-outline-style)
      var(--c-color-focus-outline);
    border-radius: calc(var(--_radius) + var(--_ring-reach));
    pointer-events: none;
  }

  .command-palette-row__cell {
    grid-column: 1 / -1;
    grid-row: 1 / -1;
    display: grid;
    grid-template-columns: subgrid;
    grid-template-rows: subgrid;
    align-items: center;
  }

  .command-palette-row__item {
    grid-column: 2;
    grid-row: 1;
    min-inline-size: 0;
    padding: 0;
    border: 0;
    background: none;
    color: inherit;
    font: inherit;
    text-align: start;
    text-decoration: none;
    cursor: pointer;
  }

  .command-palette-row__item:focus-visible {
    outline: none;
  }

  /* Stretches the item's hit area across the whole row, menu excepted. */
  .command-palette-row__item::after {
    content: '';
    position: absolute;
    inset: 0;
    border-radius: inherit;
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

  /* A flex box, so the menu button centers on the label's line instead of sitting on a line box. */
  .command-palette-row__actions {
    grid-column: 3;
    grid-row: 1;
    display: flex;
    align-items: center;
    position: relative;
    z-index: 1;
    --c-focus-outline-offset: calc(var(--c-focus-outline-width) * -1);
  }

  .command-palette-row--selected .command-palette-row__actions {
    --c-color-focus-outline: currentColor;
  }

  @media (forced-colors: active) {
    .command-palette-row--selected {
      outline: 1px solid Highlight;
    }

    .command-palette-row:has(.command-palette-row__item:focus-visible)::before {
      border-color: Highlight;
    }
  }
</style>
