<script setup lang="ts">
  import {onKeyStroke} from '@vueuse/core';

  export interface ModalProps {
    isActive?: boolean;
    overlay?: boolean;
  }

  const emit = defineEmits<{
    (e: 'close'): void;
  }>();

  withDefaults(defineProps<ModalProps>(), {
    isActive: false,
    overlay: true,
  });

  onKeyStroke('Escape', (e) => {
    emit('close');
  });
</script>

<template>
  <Transition name="body">
    <div class="modal" v-if="isActive">
      <div class="content">
        <slot></slot>
      </div>
    </div>
  </Transition>

  <Transition name="fade" v-if="overlay">
    <div class="overlay" v-if="isActive" @click="emit('close')"></div>
  </Transition>
</template>

<style scoped>
  .content {
    max-width: calc(100vw - (var(--c-spacing-lg) * 2));
    max-height: calc(100vh - (var(--c-spacing-lg) * 2));
    box-shadow: var(--c-modal-shadow);
    -webkit-overflow-scrolling: touch;
    border-radius: var(--c-modal-radius);
    border: var(--c-modal-border);
    position: relative;
    overflow-y: scroll;
    pointer-events: auto;
  }

  .modal,
  .overlay {
    position: fixed;
    width: 100vw;
    height: 100vh;
    inset: 0;
  }

  .modal {
    z-index: 10002;
    display: grid;
    justify-content: center;
    align-items: center;
    pointer-events: none;
  }

  .overlay {
    /**
    Action menu items are z-index 10000, so we want to be above that
    @TODO make this less fragile/weird
     */
    z-index: 10001;
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
