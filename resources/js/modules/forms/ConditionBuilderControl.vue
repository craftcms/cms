<script setup lang="ts">
  import {computed} from 'vue';
  import ConditionBuilder from '@/modules/conditions/ConditionBuilder.vue';
  import type {
    BuilderPayload,
    ConditionConfig,
  } from '@/modules/conditions/types';
  import type {FormControlPayload, FormPayload} from './types';
  import {pathsMatch} from './runtime';

  const props = defineProps<{
    control: FormControlPayload<{builder: BuilderPayload}>;
    value: ConditionConfig;
    editable: boolean;
    errors?: FormPayload['errors'];
  }>();

  const emit = defineEmits<{
    (event: 'update:value', value: ConditionConfig, kind: 'discrete'): void;
    /**
     * Never emitted — declared only to stop FieldNode's blanket `@change`
     * listener from falling through onto `ConditionBuilder`'s own `change`
     * (its current value, already handled below via `update:value`), which
     * would otherwise hand `recordChange()` a `ConditionConfig` in place of a
     * `FormChange` and crash on its missing `path`.
     */
    (event: 'change'): void;
  }>();

  const errors = computed(() =>
    (props.errors ?? [])
      .filter((error) =>
        pathsMatch(
          error.path.slice(0, props.control.path.length),
          props.control.path
        )
      )
      .map((error) => ({
        ...error,
        path: [
          '_conditionRules',
          ...error.path.slice(props.control.path.length),
        ],
      }))
  );
</script>

<template>
  <ConditionBuilder
    :payload="control.props.builder"
    :value="value"
    :editable="editable"
    :errors="errors"
    @change="emit('update:value', $event, 'discrete')"
  />
</template>
