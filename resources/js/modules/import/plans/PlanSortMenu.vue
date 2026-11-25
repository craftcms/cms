<script setup lang="ts">
  /**
   * What the import plans are sorted by and which way, as a menu whose button names the
   * order they're in.
   */
  import '@craftcms/ui/components/button/button';
  import '@craftcms/ui/components/icon/icon';
  import {ButtonVariant, t} from '@craftcms/ui';
  import {computed, nextTick, useTemplateRef} from 'vue';
  import ActionMenu from '@/common/components/ActionMenu.vue';
  import type {ActionItems} from '@/common/types';

  export type PlanSortField = 'name' | 'lastRun';
  export type PlanSortDirection = 'asc' | 'desc';

  const field = defineModel<PlanSortField>('field', {required: true});
  const direction = defineModel<PlanSortDirection>('direction', {
    required: true,
  });

  const fields: Array<{
    value: PlanSortField;
    label: string;
    defaultDirection: PlanSortDirection;
    orders: Record<PlanSortDirection, {label: string; icon: string}>;
  }> = [
    {
      value: 'name',
      label: t('Name'),
      defaultDirection: 'asc',
      orders: {
        asc: {label: t('A to Z'), icon: 'arrow-down-a-z'},
        desc: {label: t('Z to A'), icon: 'arrow-down-z-a'},
      },
    },
    {
      value: 'lastRun',
      label: t('Last run'),
      defaultDirection: 'desc',
      orders: {
        asc: {label: t('Oldest'), icon: 'arrow-up-short-wide'},
        desc: {label: t('Newest'), icon: 'arrow-down-wide-short'},
      },
    },
  ];

  const current = computed(
    () => fields.find((option) => option.value === field.value) ?? fields[0]!
  );
  const order = computed(() => current.value.orders[direction.value]);

  const invoker = useTemplateRef<HTMLElement>('invoker');

  /** The menu is rebuilt to relabel its button, so focus goes back to the new one. */
  async function choose(
    nextField: PlanSortField,
    nextDirection: PlanSortDirection
  ): Promise<void> {
    field.value = nextField;
    direction.value = nextDirection;
    await nextTick();
    invoker.value?.focus();
  }

  const actions = computed<ActionItems>(() => [
    {
      type: 'group',
      heading: t('Sort by'),
      items: fields.map((option) => ({
        label: option.label,
        selected: option.value === field.value,
        onClick: () =>
          void choose(
            option.value,
            option.value === field.value
              ? direction.value
              : option.defaultDirection
          ),
      })),
    },
    {type: 'hr'},
    {
      type: 'group',
      heading: t('Order'),
      items: (['asc', 'desc'] as const).map((value) => ({
        label: current.value.orders[value].label,
        icon: current.value.orders[value].icon,
        selected: value === direction.value,
        onClick: () => void choose(field.value, value),
      })),
    },
  ]);
</script>

<template>
  <ActionMenu
    :key="`${field}-${direction}`"
    :label="t('Sort')"
    :actions="actions"
  >
    <template #invoker>
      <craft-button
        ref="invoker"
        slot="invoker"
        type="button"
        size="small"
        :variant="ButtonVariant.Fill"
      >
        <craft-icon slot="prefix" :name="order.icon"></craft-icon>
        <span class="sr-only">{{
          t('Sort by {field}:', {field: current.label})
        }}</span>
        {{ order.label }}
        <craft-icon slot="suffix" name="chevron-down"></craft-icon>
      </craft-button>
    </template>
  </ActionMenu>
</template>
