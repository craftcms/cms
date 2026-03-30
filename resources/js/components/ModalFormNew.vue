<script setup lang="ts">
  import {t} from '@craftcms/cp';
  import Pane from '@/components/Pane.vue';

  const emit = defineEmits<{
    (e: 'close'): void;
    (e: 'submit'): void;
  }>();
  withDefaults(
    defineProps<{
      loading?: boolean;
      title?: string;
      resetLabel?: string;
      submitLabel?: string;
      isOpen?: boolean;
    }>(),
    {
      loading: false,
      resetLabel: t('Cancel'),
      submitLabel: t('Save'),
      isOpen: false,
    }
  );

  function submitHandler() {
    emit('submit');
  }

  function resetHandler(event: Event) {
    emit('close');
  }

  function escapeHandler() {
    emit('close');
  }
</script>

<template>
  <craft-modal :opened="isOpen" :title="title" @keyup.esc="escapeHandler">
    <div slot="content">
      <Pane as="form" :title="title" @submit.prevent="submitHandler">
        <!-- Forward all slots from parent to Pane -->
        <template v-for="(_, slotName) in $slots" :key="slotName" #[slotName]>
          <slot :name="slotName"></slot>
        </template>

        <slot></slot>
        <template #secondary-action>
          <craft-button type="reset" @click="resetHandler" appearance="plain">
            {{ resetLabel }}
          </craft-button>
        </template>
        <template #primary-action>
          <craft-button type="submit" variant="primary" :loading="loading">
            {{ submitLabel }}
          </craft-button>
        </template>
      </Pane>
    </div>
  </craft-modal>
</template>

<style scoped lang="scss"></style>
