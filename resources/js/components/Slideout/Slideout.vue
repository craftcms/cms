<script setup lang="ts">
  import {computed, nextTick, onUnmounted, ref, watch} from 'vue';
  import {onKeyStroke} from '@vueuse/core';
  import {useUiLayerManager} from '@/composables/useUiLayerManager';
  import Pane from '@/components/Pane/Pane.vue';

  export interface SlideoutProps {
    modelValue?: boolean;
    position?: 'start' | 'end';
    title?: string;
    showHeader?: boolean;
    showFooter?: boolean;
    closeOnEscape?: boolean;
    closeOnBackdropClick?: boolean;
    action?: string;
  }

  const props = withDefaults(defineProps<SlideoutProps>(), {
    modelValue: false,
    position: 'end',
    showHeader: true,
    showFooter: true,
    closeOnEscape: true,
    closeOnBackdropClick: true,
  });

  const emit = defineEmits<{
    (e: 'update:modelValue', value: boolean): void;
    (e: 'open'): void;
    (e: 'beforeClose'): void;
    (e: 'close'): void;
  }>();

  const layerManager = useUiLayerManager();

  // Generate unique ID for this instance
  const id = `slideout-${Math.random().toString(36).substring(2, 9)}`;

  // Template refs
  const panelRef = ref<HTMLElement | null>(null);
  const backdropRef = ref<HTMLElement | null>(null);

  // Internal state
  const isVisible = ref(false);
  const isOpen = ref(false);
  const triggerElement = ref<HTMLElement | null>(null);

  // Computed
  const isActive = computed(() => props.modelValue);

  // Methods
  const show = () => {
    if (isOpen.value) return;

    triggerElement.value = document.activeElement as HTMLElement;
    isVisible.value = true;
    emit('update:modelValue', true);

    // Prevent body scroll
    document.body.style.overflow = 'hidden';

    nextTick(() => {
      // Register with layer manager
      layerManager.add({
        id,
        type: 'slideout',
        position: props.position,
        panel: panelRef.value,
        backdrop: backdropRef.value,
      });

      // Trigger animation on next frame
      requestAnimationFrame(() => {
        requestAnimationFrame(() => {
          isOpen.value = true;
          setFocusWithin();
        });
      });

      emit('open');
    });
  };

  const hide = () => {
    if (!isOpen.value) return;

    emit('beforeClose');
    isOpen.value = false;
    emit('update:modelValue', false);

    // Remove from layer manager
    layerManager.remove(id);

    // Restore body scroll only if no other layers are open
    if (!layerManager.hasOpenLayers.value) {
      document.body.style.overflow = '';
    }

    // Wait for transition to complete
    const handleTransitionEnd = () => {
      isVisible.value = false;
      restoreFocus();
      emit('close');
    };

    if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
      handleTransitionEnd();
    } else {
      panelRef.value?.addEventListener('transitionend', handleTransitionEnd, {
        once: true,
      });
    }
  };

  const toggle = () => {
    if (isOpen.value) {
      hide();
    } else {
      show();
    }
  };

  const handleBackdropClick = () => {
    if (props.closeOnBackdropClick) {
      hide();
    }
  };

  const setFocusWithin = () => {
    const focusable = panelRef.value?.querySelector<HTMLElement>(
      'button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])'
    );
    focusable?.focus();
  };

  const restoreFocus = () => {
    if (
      triggerElement.value &&
      typeof triggerElement.value.focus === 'function'
    ) {
      triggerElement.value.focus();
    }
  };

  // Watch for external changes to modelValue
  watch(
    () => props.modelValue,
    (newValue) => {
      if (newValue && !isOpen.value) {
        show();
      } else if (!newValue && isOpen.value) {
        hide();
      }
    }
  );

  // Escape key handler - only topmost slideout responds
  onKeyStroke('Escape', (e) => {
    if (props.closeOnEscape && isOpen.value && layerManager.isTopmost(id)) {
      e.preventDefault();
      hide();
    }
  });

  // Cleanup on unmount
  onUnmounted(() => {
    layerManager.remove(id);
    if (!layerManager.hasOpenLayers.value) {
      document.body.style.overflow = '';
    }
  });

  // Expose methods for programmatic control
  defineExpose({
    show,
    hide,
    toggle,
  });
</script>

<template>
  <Teleport to="body">
    <template v-if="isVisible">
      <div
        ref="backdropRef"
        :class="{
          'craft-slideout-backdrop': true,
          'craft-slideout-backdrop--visible': isOpen,
        }"
        aria-hidden="true"
        @click="handleBackdropClick"
      ></div>

      <div
        ref="panelRef"
        :class="{
          'craft-slideout-panel': true,
          'craft-slideout-panel--start': props.position === 'start',
          'craft-slideout-panel--end': props.position === 'end',
          'craft-slideout-panel--open': isOpen,
        }"
        role="dialog"
        aria-modal="true"
      >
        <div class="slideout-inner">
          <Pane
            as="form"
            :action="action"
            appearance="slideout"
            :title="title"
            class="craft-slideout-pane"
          >
            <!-- Forward all slots from parent to Pane -->
            <template
              v-for="(_, slotName) in $slots"
              :key="slotName"
              #[slotName]
            >
              <slot :name="slotName"></slot>
            </template>
          </Pane>
          <!--<div v-if="showHeader" class="craft-slideout-panel__header">-->
          <!--  <slot name="header"></slot>-->
          <!--</div>-->

          <!--<div class="craft-slideout-panel__body">-->
          <!--  <slot></slot>-->
          <!--</div>-->

          <!--<div v-if="showFooter" class="craft-slideout-panel__footer">-->
          <!--  <slot name="footer"></slot>-->
          <!--</div>-->
        </div>
      </div>
    </template>
  </Teleport>
</template>

<style>
  .craft-slideout-backdrop {
    position: fixed;
    inset: 0;
    background-color: var(--c-backdrop-color, rgba(0, 0, 0, 0.5));
    opacity: 0;
    visibility: hidden;
    transition:
      opacity var(--c-transition-duration, 200ms) ease,
      visibility 0s linear var(--c-transition-duration, 200ms);
    z-index: var(--c-z-overlay-backdrop, 100);
  }

  .craft-slideout-backdrop--visible {
    opacity: 1;
    visibility: visible;
    transition:
      opacity var(--c-transition-duration, 200ms) ease,
      visibility 0s linear 0s;
  }

  .craft-slideout-panel {
    --_padding-inline: var(--c-spacing-md);
    --_padding-block: var(--c-spacing-md);
    flex-grow: 1;
    position: fixed;
    inset-block: 0;
    width: 55%;
    background-color: var(--c-surface-overlay);
    box-shadow: var(--c-shadow-overlay);
    border-radius: var(--c-modal-radius);
    border: 1px solid var(--c-color-neutral-border-quiet);
    z-index: var(--c-z-overlay, 101);
    display: flex;
    flex-direction: column;
    transition:
      translate var(--c-transition-duration, 200ms) ease,
      inset-inline-start var(--c-transition-duration, 200ms) ease,
      inset-inline-end var(--c-transition-duration, 200ms) ease;
    overflow: hidden;
  }

  .craft-slideout-panel--end {
    inset-inline-end: 0;
    inset-inline-start: auto;
    translate: 100% 0;
  }

  .craft-slideout-panel--start {
    inset-inline-end: auto;
    inset-inline-start: 0;
    translate: -100% 0;
  }

  .craft-slideout-panel--open {
    translate: 0 0;
  }

  .slideout-inner {
    display: flex;
    flex: 1;
    height: 100vh;
  }

  .craft-slideout-pane {
    flex-grow: 1;
  }

  /*
  .craft-slideout-panel__header {
    padding-inline: var(--_padding-inline);
    padding-block: var(--_padding-block);
    border-block-end: 1px solid var(--c-color-neutral-border-quiet);
    background-color: var(--c-color-neutral-fill-quiet);
    flex-shrink: 0;
  }

  .craft-slideout-panel__body {
    padding-inline: var(--_padding-inline);
    padding-block: var(--_padding-block);
    flex: 1;
    overflow-y: auto;
    -webkit-overflow-scrolling: touch;
  }

  .craft-slideout-panel__footer {
    padding-inline: var(--_padding-inline);
    padding-block: var(--_padding-block);
    border-block-start: 1px solid var(--c-color-neutral-border-quiet);
    background-color: var(--c-color-neutral-fill-quiet);
    flex-shrink: 0;
    display: flex;
    justify-content: end;
    gap: var(--c-spacing-md);
  }
   */

  @media (prefers-reduced-motion: reduce) {
    .craft-slideout-backdrop,
    .craft-slideout-panel {
      transition: none;
    }
  }
</style>
