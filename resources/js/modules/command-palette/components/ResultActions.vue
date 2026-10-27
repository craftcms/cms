<script lang="ts">
  /** Whether focus is on a result's actions, or in their open menu. */
  export function actionsHaveFocus(): boolean {
    return Boolean(document.activeElement?.closest('.command-palette-actions'));
  }
</script>

<script setup lang="ts">
  /**
   * A result's own actions, in a menu after the palette's listbox: the
   * active result's, or the one under the pointer's. Its button is named for
   * the result, so it needs no group around it to say whose actions they are.
   *
   * A listbox option can't hold controls, so the menu lives out here instead,
   * where Tab from the search input reaches it. It places itself over the
   * space its option keeps for it, so it still looks like part of the row.
   * Key it by the item: the menu's button is rendered once, label and all.
   */
  import {t, type BaseAction} from '@craftcms/ui';
  import {
    onBeforeUnmount,
    onMounted,
    shallowRef,
    useTemplateRef,
    watch,
  } from 'vue';
  import ActionMenu from '@/common/components/ActionMenu.vue';
  import type {ActionItem} from '@/common/types';
  import {createElementActionMenu} from '@/modules/elements/composables/useElementActionMenu';
  import type {CommandPaletteItem} from '../types';

  const props = defineProps<{
    item: CommandPaletteItem;
    /** Its option's id, over whose space for the actions it goes. */
    optionId: string;
    /** Whether its row is the active option, rather than just under the pointer. */
    active: boolean;
  }>();

  const emit = defineEmits<{
    /** A menu action was chosen, and the palette should get out of its way. */
    (event: 'dismiss'): void;
  }>();

  const elementActionItems = createElementActionMenu();

  const root = useTemplateRef<HTMLElement>('root');
  const placement = shallowRef<Record<string, string>>({});

  /**
   * Goes over the space its option keeps for it, measured from the results it
   * scrolls with. Called again by the palette when the results change.
   */
  function place(): void {
    const container = root.value?.offsetParent;
    const space = document
      .getElementById(props.optionId)
      ?.querySelector('[data-command-palette-actions-slot]');

    if (!space || !container) {
      return;
    }

    const box = space.getBoundingClientRect();
    const outer = container.getBoundingClientRect();
    placement.value = {
      top: `${box.top - outer.top + container.scrollTop}px`,
      left: `${box.left - outer.left + container.scrollLeft}px`,
      width: `${box.width}px`,
      height: `${box.height}px`,
    };
  }

  watch(() => props.optionId, place, {flush: 'post'});
  onMounted(() => {
    place();
    window.addEventListener('resize', place);
  });
  onBeforeUnmount(() => window.removeEventListener('resize', place));

  defineExpose({place});

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
    ref="root"
    class="command-palette-actions"
    :style="placement"
    :class="{
      'command-palette-actions--quiet': !props.active,
      'command-palette-actions--danger': props.item.variant === 'danger',
    }"
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
