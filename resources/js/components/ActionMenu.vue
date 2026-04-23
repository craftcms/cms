<script setup lang="ts">
  import {t} from '@craftcms/cp/utilities/translate.ts.mjs';
  import {computed} from 'vue';
  import type {ActionItem} from '@craftcms/cp/actions';

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

  const sortedActions = computed(() => {
    const actions = safeActions.value;

    if (dangerousActions.value.length) {
      actions.push({type: 'hr'});
      actions.push(...dangerousActions.value);
    }

    return actions;
  });
</script>

<template>
  <craft-action-menu>
    <slot name="invoker" :label="label">
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
        <template v-if="action.type && action.type === 'hr'">
          <hr class="m-0" />
        </template>
        <template v-else>
          <craft-action-item @click="action.onClick" v-bind="action">{{
            action.label
          }}</craft-action-item>
        </template>
      </template>
    </div>
  </craft-action-menu>
</template>

<style scoped lang="scss">
  craft-action-item {
    min-width: 200px;
  }
</style>
