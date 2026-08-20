<script setup lang="ts">
  import {ref, watch} from 'vue';
  import {t} from '@craftcms/ui';
  import ModalForm from '@/common/components/ModalForm.vue';
  import IconPicker from '@/common/form/IconPicker.vue';
  import type {PageRow} from './types';

  const props = defineProps<{
    isActive: boolean;
    /** The page being edited, or null when adding one. */
    page: PageRow | null;
    /** Returns an error message, or null when the name is acceptable. */
    validateName: (name: string, page: PageRow | null) => string | null;
  }>();

  const emit = defineEmits<{
    (e: 'close'): void;
    (e: 'save', name: string, icon: string | null): void;
  }>();

  const name = ref('');
  const icon = ref<string | undefined>(undefined);
  const error = ref<string | null>(null);

  watch(
    () => props.isActive,
    (active) => {
      if (!active) return;
      name.value = props.page?.name ?? '';
      icon.value = props.page?.icon ?? undefined;
      error.value = null;
    },
    {immediate: true}
  );

  function submit(): void {
    error.value = props.validateName(name.value, props.page);

    if (error.value === null) {
      emit('save', name.value, icon.value || null);
    }
  }
</script>

<template>
  <ModalForm
    :is-active="isActive"
    :title="page ? t('Page Settings') : t('New page')"
    width="sm"
    @close="emit('close')"
    @submit="submit"
  >
    <craft-field :label="t('Page Name')" required :has-errors="!!error">
      <craft-input slot="input">
        <input slot="input" v-model="name" type="text" required />
      </craft-input>
      <div v-if="error" slot="errors">{{ error }}</div>
    </craft-field>

    <IconPicker v-model="icon" :label="t('Icon')" />
  </ModalForm>
</template>
