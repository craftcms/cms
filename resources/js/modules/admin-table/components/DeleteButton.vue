<script setup lang="ts">
  import type {
    OptimisticCallback,
    UrlMethodPair,
    VisitOptions,
  } from '@inertiajs/core';
  import {router} from '@inertiajs/vue3';
  import {t} from '@craftcms/ui/utilities/translate';
  import {onMounted, ref, useId, useTemplateRef} from 'vue';

  defineOptions({inheritAttrs: false});

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
      disabledReason?: string | null;
      label?: string;
      icon?: string;
      /** The visit made once confirmed: a URL (sent as DELETE) or a route. */
      action?: string | UrlMethodPair;
      /** Options for that visit, such as an `optimistic` update. */
      options?: DeleteVisitOptions;
    }>(),
    {disabled: false, label: t('Delete item'), icon: 'xmark-large'}
  );
  const tooltipId = useId();
  const button =
    useTemplateRef<HTMLElementTagNameMap['craft-button']>('button');
  const buttonReady = ref(false);

  onMounted(async () => {
    await button.value?.updateComplete;
    // Lion clears an initial aria-disabled attribute on its first update.
    buttonReady.value = true;
  });

  function handleClick(): void {
    if (props.disabled || props.disabledReason) {
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
  <span class="inline-flex" :id="tooltipId">
    <craft-button
      ref="button"
      type="button"
      @click="handleClick"
      :aria-disabled="
        buttonReady && (disabled || disabledReason) ? 'true' : undefined
      "
      :aria-description="disabledReason || undefined"
      size="small"
      variant="danger-plain"
      v-bind="$attrs"
    >
      <craft-icon :name="icon" :label="label"></craft-icon>
    </craft-button>
    <craft-tooltip v-if="disabledReason" :for="tooltipId">{{
      disabledReason
    }}</craft-tooltip>
  </span>
</template>

<style scoped lang="scss">
  craft-button[aria-disabled='true'] {
    cursor: default;
    opacity: 0.25;
  }
</style>
