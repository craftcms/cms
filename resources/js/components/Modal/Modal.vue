<script setup lang="ts">
  import {onKeyStroke} from '@vueuse/core';
  import {computed} from 'vue';
  import Shade from '@/components/Shade.vue';

  export interface ModalProps {
    isActive?: boolean;
    overlay?: boolean;
    width?: string;
  }

  const emit = defineEmits<{
    (e: 'close'): void;
  }>();

  const props = withDefaults(defineProps<ModalProps>(), {
    isActive: false,
    overlay: true,
    size: 'md',
  });

  onKeyStroke('Escape', (e) => {
    emit('close');
  });

  const widthClass = computed(() => {
    return `w-${props.width}`;
  });
</script>

<template>
  <Teleport to="body">
    <Transition name="body">
      <div class="modal" v-if="isActive">
        <div
          :class="{
            content: true,
            [widthClass]: true,
          }"
          data-testid="modal-content"
        >
          <slot></slot>
        </div>
      </div>
    </Transition>

    <Shade :visible="isActive" v-if="overlay" @close="emit('close')" />
  </Teleport>
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

  .modal {
    position: fixed;
    width: 100vw;
    height: 100vh;
    inset: 0;
    z-index: 10002;
    display: grid;
    justify-content: center;
    align-items: center;
    pointer-events: none;
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
  }
</style>
