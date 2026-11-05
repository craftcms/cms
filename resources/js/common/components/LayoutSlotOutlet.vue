<script setup lang="ts">
  import {
    computed,
    onBeforeUnmount,
    onMounted,
    useTemplateRef,
    watch,
  } from 'vue';
  import {layoutSlotDebugEnabled} from '@/common/composables/layoutSlotDebug';
  import {trackLayoutSlotDebugRegion} from '@/common/composables/layoutSlotDebugOverlay';
  import {useLayoutSlotRegistry} from '@/common/composables/layoutSlots';

  const props = defineProps<{name: string}>();

  const registry = useLayoutSlotRegistry();
  const filled = computed(() => registry.has(props.name));
  const debug = layoutSlotDebugEnabled();

  // Tell slots when the target they teleport into is a new element, or gone.
  const target = useTemplateRef<HTMLElement>('target');
  let hasTarget = false;
  watch(
    target,
    (element, previous) => {
      if (element === previous) {
        return;
      }

      if (hasTarget) {
        registry.targetUnmounted(props.name);
        hasTarget = false;
      }

      if (element) {
        registry.targetMounted(props.name);
        hasTarget = true;
      }
    },
    {flush: 'sync'}
  );

  const debugRegion = useTemplateRef<HTMLElement>('debugRegion');
  let untrack: (() => void) | null = null;

  onMounted(() => {
    if (debugRegion.value) {
      untrack = trackLayoutSlotDebugRegion(debugRegion.value, props.name);
    }
  });

  onBeforeUnmount(() => {
    untrack?.();

    if (hasTarget) {
      registry.targetUnmounted(props.name);
      hasTarget = false;
    }
  });
</script>

<template>
  <div
    v-if="debug"
    ref="debugRegion"
    class="layout-slot-debug"
    :data-layout-slot-debug="name"
  >
    <div
      ref="target"
      :data-layout-scope="registry.scope"
      :data-layout-slot="name"
      :style="{display: filled ? 'contents' : 'none'}"
    ></div>
    <slot v-if="!filled"></slot>
  </div>
  <template v-else>
    <div
      ref="target"
      :data-layout-scope="registry.scope"
      :data-layout-slot="name"
      :style="{display: filled ? 'contents' : 'none'}"
    ></div>
    <slot v-if="!filled"></slot>
  </template>
</template>

<style lang="css">
  .layout-slot-debug {
    display: contents;
  }

  .layout-slot-debug > [data-layout-slot] > *,
  .layout-slot-debug > :not([data-layout-slot]) {
    outline: 1px dashed rgb(217 70 239 / 45%);
    outline-offset: -1px;
  }

  .layout-slot-debug-layer {
    position: fixed;
    inset: 0;
    z-index: var(--c-layer-debug);
    pointer-events: none;
    overflow: hidden;
  }

  .layout-slot-debug-label {
    position: absolute;
    inset-block-start: 0;
    inset-inline-start: 0;
    padding-inline: 3px;
    border-radius: 3px;
    background-color: rgb(253 244 255 / 90%);
    color: rgb(162 28 175);
    font:
      500 10px/1.4 ui-monospace,
      SFMono-Regular,
      Menlo,
      monospace;
    white-space: nowrap;
  }
</style>
