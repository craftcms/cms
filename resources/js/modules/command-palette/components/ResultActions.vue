<script setup lang="ts">
  /**
   * A result's own actions, in a group after the palette's listbox: the
   * active result's, or the one under the pointer's.
   *
   * A listbox option can't hold controls, so the menu lives out here instead,
   * where Tab from the search input reaches it. It's drawn over the space the
   * active option keeps for it, so it still looks like part of the row. Key it
   * by the item: the menu's button is rendered once, label and all.
   */
  import {t, type BaseAction} from '@craftcms/ui';
  import ActionMenu from '@/common/components/ActionMenu.vue';
  import type {ActionItem} from '@/common/types';
  import {createElementActionMenu} from '@/modules/elements/composables/useElementActionMenu';
  import type {CommandPaletteItem} from '../types';

  const props = defineProps<{
    item: CommandPaletteItem;
    /** Whether its row is the active option, rather than just under the pointer. */
    active: boolean;
  }>();

  const emit = defineEmits<{
    /** A menu action was chosen, and the palette should get out of its way. */
    (event: 'dismiss'): void;
  }>();

  const elementActionItems = createElementActionMenu();

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
</script>

<template>
  <div
    role="group"
    class="command-palette-actions"
    :class="{
      'command-palette-actions--quiet': !props.active,
      'command-palette-actions--danger': props.item.variant === 'danger',
    }"
    :aria-label="t('Actions for {item}', {item: props.item.label})"
  >
    <ActionMenu
      :actions="menuActions(props.item)"
      :label="t('Actions for {item}', {item: props.item.label})"
    />
  </div>
</template>

<style scoped>
  /* Placed over the active option's row by the palette, in that row's colors. */
  .command-palette-actions {
    position: absolute;
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--c-color-neutral-on-loud);
    --c-focus-outline-offset: calc(var(--c-focus-outline-width) * -1);
    --c-color-focus-outline: currentColor;
  }

  .command-palette-actions--danger {
    color: var(--c-color-danger-on-normal);
  }

  .command-palette-actions--quiet {
    color: var(--c-color-neutral-on-quiet);
  }

  .command-palette-actions--quiet.command-palette-actions--danger {
    color: var(--c-color-danger-on-quiet);
  }

  @media (forced-colors: active) {
    .command-palette-actions {
      --c-color-focus-outline: Highlight;
    }
  }
</style>
