<script setup lang="ts">
  import {t} from '@craftcms/cp';
  import Modal from '@/components/Modal.vue';
  import LoginOrchestrator from '@/components/Auth/LoginOrchestrator.vue';
  import Pane from '@/components/Pane.vue';

  defineProps<{
    isActive?: boolean;
  }>();

  const emit = defineEmits<{
    (e: 'confirmed', expiresAt: number | false): void;
    (e: 'close'): void;
  }>();
</script>

<template>
  <Modal :is-active="isActive" @close="emit('close')">
    <Pane>
      <div class="mb-3">
        <h1>{{ t('Confirm your identity.') }}</h1>
        <p>{{ t('You must reverify your identity before proceeding.') }}</p>
      </div>
      <LoginOrchestrator context="modal" @confirmed="emit('confirmed', $event)" />
    </Pane>
  </Modal>
</template>

<style scoped lang="scss"></style>
