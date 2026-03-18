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
    }>(),
    {
      overlay: true,
      loading: false,
      resetLabel: t('Cancel'),
      submitLabel: t('Save'),
    }
  );

  function submitHandler() {
    emit('submit');
  }

  function resetHandler(event: Event) {
    const {target} = event;
    target!.dispatchEvent(new Event('close-overlay', {bubbles: true}));
    emit('close');
  }
</script>

<template>
  <form @submit.prevent="submitHandler">
    <Pane :title="title">
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
  </form>
</template>

<style scoped lang="scss"></style>
