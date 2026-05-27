<script setup lang="ts">
  import {t, type VariantKey} from '@craftcms/cp';
  import type {ActionFeedback, BaseAction} from '@craftcms/cp/actions.mjs';
  import {type Component, computed} from 'vue';

  interface ActionItemHr {
    type: 'hr';
  }

  interface ActionItemDisplay {
    type: 'display';
    is: Component;
  }

  interface ActionItemButton {
    type?: 'button';
    label: string;
    variant?: VariantKey | string;
    icon?: string;
    action?: BaseAction;
    disabled?: boolean;
    feedback?: ActionFeedback;
    onClick?: (event?: Event) => void;
    shortcut?: string | {alt?: boolean; shift?: boolean; key: string} | null;
    [key: string]: unknown;
  }

  interface ActionItemLink {
    type: 'link';
    href: string;
    label: string;
    variant?: VariantKey | string;
    onClick?: () => void;
    icon?: string;
    disabled?: boolean;
    [key: string]: unknown;
  }

  export type ActionItem =
    | ActionItemDisplay
    | ActionItemHr
    | ActionItemButton
    | ActionItemLink;

  export type ActionItems = Array<ActionItem>;

  const props = withDefaults(
    defineProps<{
      icon?: string;
      label?: string | null;
      actions: Array<any>;
    }>(),
    {
      icon: 'ellipsis',
      label: t('Actions'),
    }
  );

  const normalizedActions = computed(
    (): Array<ActionItem & {onClick?: (event: Event) => void}> => {
      return props.actions.map(
        (action): ActionItem & {onClick?: (event: Event) => void} => {
          if (action.type === 'hr' || action.type === 'display') {
            return action as ActionItem;
          }

          if ('href' in action) {
            return {
              ...action,
              label: action.label ?? '',
              type: action.type ?? 'link',
            } as ActionItem;
          }

          return {
            ...action,
            label: action.label ?? '',
            type:
              action.type ??
              ('href' in action && action.href ? 'link' : 'button'),
          } as ActionItem & {onClick?: (event: Event) => void};
        }
      );
    }
  );

  const sortedActions = computed(() => {
    return [...normalizedActions.value].sort((a, b) => {
      const aDanger = 'variant' in a && a.variant === 'danger' ? 1 : 0;
      const bDanger = 'variant' in b && b.variant === 'danger' ? 1 : 0;
      return aDanger - bDanger;
    });
  });

  function actionProps(action: ActionItem): Record<string, unknown> {
    if (action.type === 'hr' || action.type === 'display') {
      return {};
    }

    const attrs = {...action};

    delete (attrs as {onClick?: unknown}).onClick;

    return attrs;
  }

  function runAction(action: ActionItem, event: Event) {
    if ('onClick' in action) {
      action.onClick?.(event);
    }
  }
</script>

<template>
  <craft-action-menu>
    <slot name="invoker" :label="label" :attributes="{slot: 'invoker'}">
      <craft-button
        type="button"
        slot="invoker"
        icon
        size="small"
        variant="inherit"
        appearance="plain"
      >
        <craft-icon :name="icon" :label="label"></craft-icon>
      </craft-button>
    </slot>

    <div slot="content" class="m-sm">
      <template v-for="(action, idx) in sortedActions" :key="idx">
        <hr class="m-0" v-if="action.type === 'hr'" />
        <component v-else-if="action.type === 'display'" :is="action.is" />
        <craft-action-item
          v-else-if="action.type === 'link'"
          v-bind="actionProps(action)"
          :href="action.href"
        >
          {{ action.label }}
        </craft-action-item>
        <craft-action-item
          v-else
          @click="runAction(action, $event)"
          v-bind="actionProps(action)"
        >
          {{ action.label }}
        </craft-action-item>
      </template>
    </div>
  </craft-action-menu>
</template>

<style scoped lang="scss">
  craft-action-item {
    min-width: 200px;
  }
</style>
