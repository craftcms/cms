<script setup lang="ts">
  import {t} from '@craftcms/cp';
  import Modal, {type ModalProps} from '@/common/components/Modal.vue';
  import Pane from '@/common/components/Pane.vue';

  const emit = defineEmits<{
    (e: 'close'): void;
    (e: 'submit'): void;
  }>();
  withDefaults(
    defineProps<
      ModalProps & {
        loading?: boolean;
        title?: string;
        resetLabel?: string;
        submitLabel?: string;
      }
    >(),
    {
      title: undefined,
      overlay: true,
      loading: false,
      resetLabel: t('Cancel'),
      submitLabel: t('Save'),
    }
  );

  function submitHandler() {
    emit('submit');
  }
</script>

<template>
  <Modal
    :is-active="isActive"
    :overlay="overlay"
    @close="emit('close')"
    :width="width"
    :height="height"
    :max-height="maxHeight"
  >
    <form @submit.prevent="submitHandler">
      <Pane :title="title">
        <!-- Forward all slots from parent to Pane -->
        <template v-for="(_, slotName) in $slots" :key="slotName" #[slotName]>
          <slot :name="slotName"></slot>
        </template>

        <slot></slot>
        <template #secondary-action>
          <craft-button type="reset" @click="emit('close')" appearance="plain">
            {{ resetLabel }}
          </craft-button>
        </template>
        <template #primary-action>
          <craft-button type="submit" variant="accent" :loading="loading">
            {{ submitLabel }}
          </craft-button>
        </template>
      </Pane>
    </form>
  </Modal>
</template>

<style scoped lang="scss"></style>
