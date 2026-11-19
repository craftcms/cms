<script setup lang="ts">
  /** Names a source of your own: a new one, or one being renamed. */
  import {ref, watch} from 'vue';
  import {t} from '@craftcms/ui';
  import ModalForm from '@/common/components/ModalForm.vue';

  const props = defineProps<{
    isActive: boolean;
    title: string;
    /** The name it starts with. */
    name?: string;
    loading?: boolean;
    error?: string | null;
  }>();

  const emit = defineEmits<{
    close: [];
    save: [name: string];
  }>();

  const value = ref('');

  watch(
    () => props.isActive,
    (active) => {
      if (active) value.value = props.name ?? '';
    },
    {immediate: true}
  );

  function submit(): void {
    if (value.value.trim()) emit('save', value.value.trim());
  }
</script>

<template>
  <ModalForm
    :is-active="isActive"
    :title="title"
    :loading="loading"
    :submit-disabled="!value.trim()"
    width="sm"
    @close="emit('close')"
    @submit="submit"
  >
    <craft-field :label="t('Name')" required .hasErrors="!!error">
      <craft-input slot="input">
        <input slot="input" v-model="value" type="text" required autofocus />
      </craft-input>
      <div v-if="error" slot="errors">{{ error }}</div>
    </craft-field>
  </ModalForm>
</template>
