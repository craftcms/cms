<script setup lang="ts">
  import {t} from '@craftcms/cp/utilities/translate.ts.mjs';
  import type {VariantKey} from '@craftcms/cp/types/index.ts';
  import {computed} from 'vue';

  export interface ActionItem {
    label: string;
    variant?: VariantKey;
    icon?: string;
    onClick?: () => void;
  }

  const props = withDefaults(
    defineProps<{
      icon?: string;
      label?: string;
      actions: Array<ActionItem>;
    }>(),
    {
      icon: 'ellipsis',
      label: t('Actions'),
    }
  );

  const dangerousActions = computed(() =>
    props.actions.filter(
      (action) => action.variant && action.variant === 'danger'
    )
  );

  const safeActions = computed(() =>
    props.actions.filter(
      (action) => !action.variant || action.variant !== 'danger'
    )
  );
</script>

<template>
  <craft-action-menu>
    <craft-button
      type="button"
      slot="invoker"
      icon
      size="small"
      appearance="plain"
    >
      <craft-icon :name="icon" :label="label"></craft-icon>
    </craft-button>

    <div slot="content" class="m-sm">
      <craft-action-item
        v-for="(action, idx) in safeActions"
        :key="`safe-${idx}`"
        @click="action.onClick?.()"
        v-bind="action"
        >{{ action.label }}</craft-action-item
      >
      <hr class="m-0" />
      <craft-action-item
        v-for="(action, idx) in dangerousActions"
        :key="`dangerous-${idx}`"
        @click="action.onClick?.()"
        v-bind="action"
        >{{ action.label }}</craft-action-item
      >
    </div>
  </craft-action-menu>
</template>

<style scoped lang="scss">
  craft-action-item {
    min-width: 200px;
  }
</style>
