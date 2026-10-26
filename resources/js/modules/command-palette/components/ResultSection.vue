<script setup lang="ts">
  /**
   * One labelled group of results — Recents, Actions, or Results — as a
   * `craft-grid` named by its heading.
   *
   * Each row has the item itself, with its hint and description, and a menu
   * of its secondary actions right after its label when it has any. The hint
   * and description stay in the item's cell, so the arrow keys move only
   * between the item and its menu, and both describe the item for screen
   * readers. The item's hit area covers the whole row, menu excepted.
   */
  import {t, type BaseAction} from '@craftcms/ui';
  import CpLink from '@/common/components/CpLink.vue';
  import ActionMenu from '@/common/components/ActionMenu.vue';
  import type {ActionItem} from '@/common/types';
  import {createElementActionMenu} from '@/modules/elements/composables/useElementActionMenu';
  import type {CommandPaletteItem} from '../types';

  const props = defineProps<{
    gridId: string;
    heading: string;
    items: CommandPaletteItem[];
    /** Whether links open in a new tab, which each one then says. */
    newTab?: boolean;
  }>();

  const emit = defineEmits<{
    (event: 'activate', item: CommandPaletteItem, trigger: MouseEvent): void;
    /** A menu action was chosen, and the palette should get out of its way. */
    (event: 'dismiss'): void;
  }>();

  function itemId(index: number): string {
    return `${props.gridId}-item-${index}`;
  }

  /** The hint and description, which screen readers read after the item's label. */
  function describedBy(
    item: CommandPaletteItem,
    index: number
  ): string | undefined {
    const ids = [
      item.hint || item.hintHtml ? `${itemId(index)}-hint` : null,
      item.description ? `${itemId(index)}-description` : null,
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
</script>

<template>
  <section class="command-palette-section">
    <h2 :id="`${gridId}-heading`" class="command-palette-section__heading">
      {{ heading }}
    </h2>
    <craft-grid :id="gridId" :aria-labelledby="`${gridId}-heading`">
      <craft-grid-row
        v-for="(item, index) in items"
        :key="item.id"
        class="command-palette-row"
        :class="{'command-palette-row--danger': item.variant === 'danger'}"
      >
        <craft-grid-cell class="command-palette-row__cell">
          <span class="command-palette-row__icon" aria-hidden="true">
            <span
              v-if="item.thumbHtml"
              class="command-palette-row__thumb"
              v-html="item.thumbHtml"
            ></span>
            <span
              v-else-if="item.iconSvg"
              class="command-palette-row__svg"
              v-html="item.iconSvg"
            ></span>
            <craft-icon
              v-else
              :name="item.icon ?? 'arrow-right'"
              :style="
                iconColorStyle(
                  item.variant === 'danger' ? null : item.iconColor
                )
              "
            ></craft-icon>
          </span>
          <component
            :is="
              isLink(item) && !item.external && !newTab
                ? CpLink
                : isLink(item)
                  ? 'a'
                  : 'button'
            "
            :href="isLink(item) ? item.href : undefined"
            :target="isLink(item) && newTab ? '_blank' : undefined"
            :rel="isLink(item) && newTab ? 'noopener' : undefined"
            :type="isLink(item) ? undefined : 'button'"
            class="command-palette-row__item"
            :data-command-palette-item="item.id"
            :aria-describedby="describedBy(item, index)"
            @click="emit('activate', item, $event)"
          >
            <span
              v-if="item.labelHtml"
              class="command-palette-row__label"
              v-html="item.labelHtml"
            ></span>
            <span v-else class="command-palette-row__label">{{
              item.label
            }}</span>
            <template v-if="isLink(item) && newTab">
              <craft-icon
                name="arrow-up-right-from-square"
                class="command-palette-row__new-tab"
                aria-hidden="true"
              ></craft-icon>
              <span class="sr-only">{{ t('Opens in a new window') }}</span>
            </template>
          </component>
          <span
            v-if="item.hintHtml"
            :id="`${itemId(index)}-hint`"
            class="command-palette-row__hint"
            :lang="item.hintLang ?? undefined"
            v-html="item.hintHtml"
          ></span>
          <span
            v-else-if="item.hint"
            :id="`${itemId(index)}-hint`"
            class="command-palette-row__hint"
            :lang="item.hintLang ?? undefined"
            >{{ item.hint }}</span
          >
          <span
            v-if="item.description"
            :id="`${itemId(index)}-description`"
            class="command-palette-row__description"
          >
            {{ item.description }}
          </span>
        </craft-grid-cell>
        <craft-grid-cell
          v-if="hasMenu(item)"
          class="command-palette-row__actions"
        >
          <ActionMenu
            :actions="menuActions(item)"
            :label="t('Actions for {item}', {item: item.label})"
          />
        </craft-grid-cell>
      </craft-grid-row>
    </craft-grid>
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
    .command-palette-row:hover:not(:focus-within) {
      background-color: var(--c-color-neutral-fill-quiet);
      color: var(--c-color-neutral-on-quiet);
    }

    .command-palette-row--danger:hover:not(:focus-within) {
      background-color: var(--c-color-danger-fill-quiet);
      color: var(--c-color-danger-on-quiet);
    }
  }

  .command-palette-row:focus-within {
    background-color: var(--c-color-neutral-fill-loud);
    color: var(--c-color-neutral-on-loud);
  }

  /* Still red once focused: the danger pair stands in for the neutral one. */
  .command-palette-row--danger:focus-within {
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

  .command-palette-row--danger:not(:focus-within)
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

  .command-palette-row__new-tab {
    margin-inline-start: var(--c-spacing-xs);
    vertical-align: middle;
  }

  .command-palette-row__hint {
    grid-column: 2 / -1;
    grid-row: 2;
    font-size: var(--c-text-sm);
    color: var(--c-text-quiet);
    overflow-wrap: anywhere;
  }

  .command-palette-row:focus-within .command-palette-row__hint {
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

  .command-palette-row:focus-within .command-palette-row__description {
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

  .command-palette-row:focus-within .command-palette-row__actions {
    --c-color-focus-outline: currentColor;
  }

  @media (forced-colors: active) {
    .command-palette-row:focus-within {
      outline: 1px solid Highlight;
    }

    .command-palette-row:has(.command-palette-row__item:focus-visible)::before {
      border-color: Highlight;
    }
  }
</style>
