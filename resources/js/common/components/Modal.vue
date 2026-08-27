<script setup lang="ts">
  import {onKeyStroke} from '@vueuse/core';
  import {computed} from 'vue';

  export interface ModalProps {
    isActive?: boolean;
    overlay?: boolean;
    width?: string;
    height?: string;
    maxHeight?: string;
  }

  const emit = defineEmits<{
    (e: 'close'): void;
    (e: 'opened', el: Element): void;
  }>();

  const props = withDefaults(defineProps<ModalProps>(), {
    isActive: false,
    overlay: true,
    width: 'md',
  });

  onKeyStroke('Escape', () => {
    emit('close');
  });

  const widthClass = computed(() => {
    return `w-${props.width}`;
  });

  const contentStyle = computed(() => {
    const viewportCap = 'calc(100vh - (var(--c-spacing-lg) * 2))';
    const style: Record<string, string> = {};
    if (props.height) {
      style.height = `min(${props.height}, ${viewportCap})`;
    }
    if (props.maxHeight) {
      style.maxHeight = `min(${props.maxHeight}, ${viewportCap})`;
    }
    return Object.keys(style).length ? style : undefined;
  });
</script>

<template>
  <Transition name="body" @after-enter="(el) => emit('opened', el as Element)">
    <div class="cp-modal" v-if="isActive">
      <div
        :class="{
          content: true,
          [widthClass]: true,
        }"
        :style="contentStyle"
      >
        <slot></slot>
      </div>
    </div>
  </Transition>

  <Transition name="fade" v-if="overlay">
    <div class="cp-overlay" v-if="isActive" @click="emit('close')"></div>
  </Transition>
</template>

<style scoped>
  .content {
    max-width: calc(100vw - (var(--c-spacing-lg) * 2));
    max-height: calc(100vh - (var(--c-spacing-lg) * 2));
    box-shadow: var(--c-modal-shadow);
    -webkit-overflow-scrolling: touch;
    border-radius: var(--c-modal-radius);
    border-width: var(--c-modal-border-width);
    border-style: var(--c-modal-border-style);
    border-color: var(--c-modal-border-color);
    position: relative;
    overflow-y: scroll;
    pointer-events: auto;
  }

  .cp-modal,
  .cp-overlay {
    position: fixed;
    width: 100vw;
    height: 100vh;
    inset: 0;
  }

  .cp-modal {
    z-index: var(--c-z-modal);
    display: grid;
    justify-content: center;
    align-items: center;
    pointer-events: none;
  }

  .cp-overlay {
    z-index: var(--c-z-modal-shade);
    background-color: rgba(0, 0, 0, 0.5);
  }

  /* Only animate when the user is cool with it */
  @media (prefers-reduced-motion: no-preference) {
    .body-enter-active {
      animation: body-in 250ms;
    }
    .body-leave-active {
      animation: body-in 250ms reverse;
    }

    @keyframes body-in {
      0% {
        opacity: 0;
        transform: scale(0.9) translateY(2rem);
      }
      100% {
        opacity: 1;
        transform: scale(1) translateY(0);
      }
    }

    .fade-enter-active,
    .fade-leave-active {
      transition: opacity 0.1s ease;
    }

    .fade-enter-from,
    .fade-leave-to {
      opacity: 0;
    }
  }
</style>
