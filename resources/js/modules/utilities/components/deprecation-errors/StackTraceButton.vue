<script setup lang="ts">
  import {t} from '@craftcms/ui';
  import Modal from '@/common/components/Modal.vue';
  import {ref} from 'vue';
  import StackTrace from '@/modules/utilities/components/deprecation-errors/StackTrace.vue';

  const isActive = ref(false);

  defineProps<{
    logId: number;
  }>();
</script>

<template>
  <craft-button size="small" type="button" @click.prevent="isActive = true">
    {{ t('Stack Trace') }}
  </craft-button>

  <Teleport to="body">
    <Modal :is-active="isActive" @close="isActive = false">
      <StackTrace :log-id="logId" />
    </Modal>
  </Teleport>
</template>

<style scoped lang="scss"></style>
