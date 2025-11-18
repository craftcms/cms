<script setup lang="ts">
  import {ref, watch} from 'vue';

  const props = withDefaults(
    defineProps<{
      isActive?: boolean;
      overlay?: boolean;
    }>(),
    {isActive: false, overlay: true}
  );

  const overlayActive = ref(false);

  watch(
    () => props.isActive,
    (newValue) => {
      if (newValue) {
        setTimeout(() => {
          overlayActive.value = newValue;
        }, 200);
      }
    }
  );
</script>

<template>
  <Transition name="body">
    <div class="modal" v-if="overlayActive">
      <slot></slot>
    </div>
  </Transition>

  <Transition name="fade" @after-enter="overlayActive = true" v-if="overlay">
    <div class="overlay" v-if="isActive"></div>
  </Transition>
</template>

<style scoped>
  .modal {
    display: grid;
    overflow: hidden;
    align-content: center;
    justify-content: center;
    max-width: calc(100vw - (var(--c-spacing-lg) * 2));
    max-height: calc(100vh - (var(--c-spacing-lg) * 2));
    box-shadow: var(--c-modal-shadow);
    -webkit-overflow-scrolling: touch;
    border-radius: var(--c-modal-radius);
    border: var(--c-modal-border);
    position: relative;
    z-index: 1;
  }

  .overlay {
    position: fixed;
    inset: 0;
    background-color: rgba(0, 0, 0, 0.5);
  }

  /* Tone down the animation to avoid vestibular motion triggers. */
  @media (prefers-reduced-motion: reduce) {
    .body-enter-active {
      animation: body-in 200ms;
    }
    .body-leave-active {
      animation: body-in 200ms reverse;
    }

    @keyframes body-in {
      0% {
        opacity: 0;
        transform: scale(0.9) translateY(3rem);
      }
      100% {
        opacity: 1;
        transform: scale(1) translateY(0);
      }
    }

    .fade-enter-active,
    .fade-leave-active {
      transition: opacity 0.5s ease;
    }

    .fade-enter-from,
    .fade-leave-to {
      opacity: 0;
    }
  }
</style>
