<script setup lang="ts">
  import {computed} from 'vue';
  import type {ActionMenuItem} from '@craftcms/ui';
  import type {BuilderPayload} from './types';

  const props = defineProps<{
    types: BuilderPayload['ruleTypes'];
    label: string;
    disabled?: boolean;
    adding?: boolean;
  }>();
  const emit = defineEmits<{select: [value: string]}>();

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
    :disabled="disabled"
    :label="label"
    searchable
  >
    <span slot="invoker" style="display: inline-flex" v-once>
      <craft-button
        type="button"
        :variant="adding ? 'dashed' : 'fill'"
        icon="chevron-down"
        icon-position="suffix"
      >
        <craft-icon v-if="adding" name="plus" slot="prefix" />
        {{ label }}
      </craft-button>
    </span>
  </craft-action-menu>
</template>
