<script setup lang="ts">
  import {t} from '@craftcms/cp/utilities/translate.ts.mjs';
  import type {VariantKey} from '@craftcms/cp/types/index.ts';
  import {computed} from 'vue';

  export interface ActionItem {
    id?: string;
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
      variant="inherit"
      appearance="plain"
    >
      <craft-icon :name="icon" :label="label"></craft-icon>
    </craft-button>

    <div slot="content" class="m-sm">
      <craft-action-item
        v-for="(action, idx) in safeActions"
        :id="action.id"
        :key="`safe-${idx}`"
        :icon="action.icon"
        @click="action.onClick"
        >{{ action.label }}</craft-action-item
      >
      <hr class="m-0" />
      <craft-action-item
        v-for="(action, idx) in dangerousActions"
        :id="action.id"
        :key="`dangerous-${idx}`"
        :icon="action.icon"
        :variant="action.variant"
        @click="action.onClick"
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
