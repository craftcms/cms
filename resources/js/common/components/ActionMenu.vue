<script setup lang="ts">
  import {t} from '@craftcms/cp/utilities/translate.ts.mjs';
  import type {VariantKey} from '@craftcms/cp/types/index.ts';
  import {type Component, computed, type VNode} from 'vue';

  interface ActionItemHr {
    type: 'hr';
  }

  interface ActionItemDisplay {
    type: 'display';
    is: () => VNode | 'string' | Component;
  }

  interface ActionItemButton {
    type?: 'button';
    label: string;
    variant?: VariantKey;
    icon?: string;
    onClick?: () => void;
  }

  interface ActionItemLink {
    type?: 'link';
    href: string;
    label: string;
    variant?: VariantKey;
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
      label?: string;
      actions: Array<ActionItem & {onClick?: (event: Event) => void}>;
    }>(),
    {
      icon: 'ellipsis',
      label: t('Actions'),
    }
  );

  const normalizedActions = computed((): ActionItems => {
    return props.actions.map((action): ActionItem => {
      if (action.type === 'hr' || action.type === 'display') {
        return action;
      }

      return {
        ...action,
        type:
          action.type ??
          (('href' in action ? 'link' : 'button') as 'link' | 'button'),
      };
    });
  });

  const sortedActions = computed(() => {
    return [...normalizedActions.value].sort((a, b) => {
      const aDanger = 'variant' in a && a.variant === 'danger' ? 1 : 0;
      const bDanger = 'variant' in b && b.variant === 'danger' ? 1 : 0;
      return aDanger - bDanger;
    });
  });
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
          v-bind="action"
          :href="action.href"
        >
          {{ action.label }}
        </craft-action-item>
        <craft-action-item v-else @click="action.onClick?.()" v-bind="action">{{
          action.label
        }}</craft-action-item>
      </template>
    </div>
  </craft-action-menu>
</template>

<style scoped lang="scss">
  craft-action-item {
    min-width: 200px;
  }
</style>
