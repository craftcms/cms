<script setup lang="ts">
  import ElevatedSessionModal from '@/components/Auth/ElevatedSessionModal.vue';
  import {useElevatedSession} from '@/composables/useElevatedSession';

  const emit = defineEmits<{
    (e: 'confirmed'): void;
  }>();

  const {modalOpen, requireElevatedSession, onConfirmed, onCancel} =
    useElevatedSession();

  function elevate() {
    requireElevatedSession(() => {
      emit('confirmed');
    });
  }

  function handleConfirmed(expiresAt: number | false) {
    onConfirmed(expiresAt);
  }
</script>

<template>
  <slot :elevate="elevate" />

  <ElevatedSessionModal
    :is-active="modalOpen"
    @confirmed="handleConfirmed"
    @close="onCancel"
  />
</template>

<style scoped lang="scss"></style>
