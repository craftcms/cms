<script setup lang="ts">
  import {ref} from 'vue';

  const emit = defineEmits<{
    (e: 'close'): void;
  }>();
  withDefaults(
    defineProps<{
      visible?: boolean;
    }>(),
    {visible: false}
  );

  const shadeRef = ref<HTMLElement | null>(null);

  defineExpose({
    el: shadeRef,
  });
</script>

<template>
  <Transition name="fade">
    <div
      v-if="visible"
      ref="shadeRef"
      class="shade"
      @click="emit('close')"
      aria-hidden="true"
      v-bind="$attrs"
    ></div>
  </Transition>
</template>

<style scoped lang="scss">
  .shade {
    position: fixed;
    width: 100vw;
    height: 100vh;
    inset: 0;
    background-color: var(--c-surface-shade);
  }

  .fade-enter-active,
  .fade-leave-active {
    transition: opacity var(--c-transition-duration, 200ms) ease;
  }

  .fade-enter-from,
  .fade-leave-to {
    opacity: 0;
  }
</style>
