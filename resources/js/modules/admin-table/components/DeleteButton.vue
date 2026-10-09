<script setup lang="ts">
  import type {
    OptimisticCallback,
    UrlMethodPair,
    VisitOptions,
  } from '@inertiajs/core';
  import {router} from '@inertiajs/vue3';
  import {t} from '@craftcms/ui/utilities/translate';

  // Typed over the page's own props, which Inertia's callback type can't know.
  type DeleteVisitOptions = Omit<VisitOptions, 'optimistic'> & {
    optimistic?: (props: never) => Record<string, unknown> | void;
  };

  const emit = defineEmits<{
    (e: 'click'): void;
  }>();
  const props = withDefaults(
    defineProps<{
      confirm?: string;
      disabled?: boolean;
      label?: string;
      icon?: string;
      /** The visit made once confirmed: a URL (sent as DELETE) or a route. */
      action?: string | UrlMethodPair;
      /** Options for that visit, such as an `optimistic` update. */
      options?: DeleteVisitOptions;
    }>(),
    {disabled: false, label: t('Delete item'), icon: 'xmark-large'}
  );

  function handleClick(): void {
    if (props.disabled) {
      return;
    }

    if (props.confirm && !window.confirm(props.confirm)) {
      return;
    }

    emit('click');

    if (props.action) {
      router.visit(props.action, {
        method:
          typeof props.action === 'string' ? 'delete' : props.action.method,
        preserveScroll: true,
        ...props.options,
        optimistic: props.options?.optimistic as OptimisticCallback | undefined,
      });
    }
  }
</script>

<template>
  <craft-button
    type="button"
    @click="handleClick"
    :aria-disabled="disabled ? 'true' : undefined"
    size="small"
    variant="danger-plain"
    v-bind="$attrs"
  >
    <craft-icon :name="icon" :label="label"></craft-icon>
  </craft-button>
</template>

<style scoped lang="scss">
  craft-button[aria-disabled='true'] {
    cursor: default;
    opacity: 0.25;
  }
</style>
