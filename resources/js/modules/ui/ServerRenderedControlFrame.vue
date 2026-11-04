<script setup lang="ts">
  import {computed} from 'vue';
  import {t} from '@craftcms/ui';
  import type {ServerRenderedControlStatus} from './useServerRenderedControl';

  /**
   * Wraps a server-rendered control, holding its space with a placeholder
   * until its HTML first loads, and offering a retry when a render fails.
   */
  const props = withDefaults(
    defineProps<{
      status: ServerRenderedControlStatus;
      /** Whether the control's HTML has loaded at least once. */
      hasHtml: boolean;
      /** Placeholder chip rows, one per selected item. */
      chips?: number;
      /** Placeholder buttons below the chips. */
      buttons?: number;
      /** The block size of a single placeholder block, for controls with no chips. */
      blockSize?: string;
    }>(),
    {chips: 0, buttons: 0, blockSize: undefined}
  );
  const emit = defineEmits<{
    (event: 'retry'): void;
  }>();

  const showPlaceholder = computed(
    () => !props.hasHtml && props.status === 'loading'
  );
</script>

<template>
  <div>
    <div
      v-if="showPlaceholder"
      class="server-rendered-control-placeholder"
      aria-hidden="true"
      data-server-rendered-control-placeholder
    >
      <div
        v-if="blockSize"
        class="server-rendered-control-placeholder__block"
        :style="{blockSize}"
      ></div>
      <ul v-if="chips" class="server-rendered-control-placeholder__chips">
        <li
          v-for="index in chips"
          :key="index"
          class="server-rendered-control-placeholder__block server-rendered-control-placeholder__chip"
        ></li>
      </ul>
      <div
        v-if="buttons"
        class="server-rendered-control-placeholder__footer"
        :class="{
          'server-rendered-control-placeholder__footer--after-chips': chips,
        }"
      >
        <div
          v-for="index in buttons"
          :key="index"
          class="server-rendered-control-placeholder__block server-rendered-control-placeholder__button"
        ></div>
      </div>
    </div>
    <slot />
    <div
      v-if="status === 'error'"
      class="server-rendered-control-error"
      role="alert"
    >
      <p>{{ t('Couldn’t load this field.') }}</p>
      <craft-button
        type="button"
        size="small"
        variant="outline"
        @click="emit('retry')"
      >
        {{ t('Retry') }}
      </craft-button>
    </div>
  </div>
</template>

<style scoped>
  .server-rendered-control-placeholder__chips {
    display: flex;
    flex-direction: column;
    gap: var(--c-spacing-sm);
  }

  .server-rendered-control-placeholder__footer {
    display: flex;
    gap: var(--c-spacing-sm);
    padding-block-start: var(--c-spacing-md);
  }

  .server-rendered-control-placeholder__footer--after-chips {
    margin-block-start: var(--c-spacing-md);
  }

  .server-rendered-control-placeholder__block {
    block-size: var(--c-size-control-md);
    max-inline-size: 100%;
    border: 1px solid transparent;
    border-radius: var(--c-radius-md);
    background-color: var(--c-surface-sunken);
    animation: var(--animate-pulse);
  }

  .server-rendered-control-placeholder__chip {
    inline-size: 14rem;
  }

  .server-rendered-control-placeholder__button {
    inline-size: 6rem;
  }

  .server-rendered-control-error {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: var(--c-spacing-md);
    color: var(--c-text-quiet);
  }

  .server-rendered-control-error p {
    margin: 0;
  }

  @media (prefers-reduced-motion: reduce) {
    .server-rendered-control-placeholder__block {
      animation: none;
    }
  }
</style>
