<script setup lang="ts">
  import {t} from '@craftcms/cp';
  import Modal, {type ModalProps} from '@/components/Modal.vue';
  import Pane from '@/components/Pane.vue';

  const emit = defineEmits<{
    (e: 'close'): void;
    (e: 'submit'): void;
  }>();
  withDefaults(
    defineProps<
      ModalProps & {
        loading?: boolean;
      }
    >(),
    {overlay: true, loading: false}
  );
</script>

<template>
  <Modal :isActive="isActive" :overlay="overlay" @close="emit('close')">
    <form @submit.prevent="emit('submit')">
      <Pane class="w-[60ch] mx-auto">
        <slot></slot>
        <template #secondary-action>
          <craft-button type="reset" @click="emit('close')" appearance="plain">
            {{ t('Cancel') }}
          </craft-button>
        </template>
        <template #primary-action>
          <craft-button type="submit" variant="primary" :loading="loading">
            {{ t('Save') }}
          </craft-button>
        </template>
      </Pane>
    </form>
  </Modal>
</template>

<style scoped lang="scss"></style>
