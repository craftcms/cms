<script setup lang="ts">
  import {computed, useTemplateRef, watch} from 'vue';
  import type {ActionMenuItem, ButtonVariant} from '@craftcms/ui';

  export type TypePickerOption = {
    value: string;
    label: string;
    hint?: string | null;
    showHint?: boolean;
    group?: string | null;
  };

  const props = defineProps<{
    types: TypePickerOption[];
    label: string;
    disabled?: boolean;
    adding?: boolean;
    buttonVariant?: ButtonVariant;
  }>();
  const emit = defineEmits<{select: [value: string]}>();

  // The invoker renders once, so later variant changes are applied directly.
  const invoker = useTemplateRef<HTMLElement & {variant: string}>('invoker');
  const invokerVariant = computed(
    () => props.buttonVariant ?? (props.adding ? 'dashed' : 'fill')
  );
  watch(invokerVariant, (variant) => {
    if (invoker.value) {
      invoker.value.variant = variant;
    }
  });

  const actions = computed<ActionMenuItem[]>(() => {
    const groups = new Map<
      string,
      Extract<ActionMenuItem, {type: 'group'}>['items']
    >();

    for (const type of props.types) {
      const key = type.group ?? '';
      const items = groups.get(key) ?? [];

      items.push({
        label:
          type.label + (type.showHint && type.hint ? ` – ${type.hint}` : ''),
        onClick: () => emit('select', type.value),
      });
      groups.set(key, items);
    }

    return [...groups].map(([heading, items]) => ({
      type: 'group',
      heading,
      items,
    }));
  });
</script>

<template>
  <craft-action-menu
    :actions="actions"
    .disabled="disabled"
    :label="label"
    searchable
  >
    <craft-button
      ref="invoker"
      slot="invoker"
      v-once
      type="button"
      :variant="invokerVariant"
      icon="chevron-down"
      icon-position="suffix"
    >
      <craft-icon v-if="adding" name="plus" slot="prefix" />
      {{ label }}
    </craft-button>
  </craft-action-menu>
</template>
