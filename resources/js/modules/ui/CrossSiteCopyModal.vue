<script setup lang="ts">
  import '@craftcms/ui/components/select/select';
  import {t} from '@craftcms/ui';
  import {nextTick, ref, useTemplateRef, watch} from 'vue';
  import ModalForm from '@/common/components/ModalForm.vue';

  const props = defineProps<{
    active: boolean;
    label?: string | null;
    sites: Array<{id: number; name: string}>;
    loading: boolean;
  }>();
  const emit = defineEmits<{
    (event: 'close'): void;
    (event: 'submit', siteId: number): void;
  }>();
  const select = useTemplateRef<HTMLSelectElement>('select');
  const siteId = ref<number>();

  watch(
    () => props.active,
    async (active) => {
      if (!active) {
        return;
      }

      siteId.value = props.sites[0]?.id;
      await nextTick();
      select.value?.focus();
    }
  );

  function submit(): void {
    if (siteId.value !== undefined) {
      emit('submit', siteId.value);
    }
  }
</script>

<template>
  <ModalForm
    :is-active="active"
    :title="t('Copy “{name}” value', {name: label || t('Field')})"
    :submit-label="t('Copy')"
    :loading="loading"
    width="sm"
    @close="emit('close')"
    @submit="submit"
  >
    <div data-cross-site-copy-modal>
      <craft-select :label="t('Copy from')" required>
        <select
          ref="select"
          slot="input"
          :value="siteId"
          required
          @change="siteId = Number(($event.target as HTMLSelectElement).value)"
        >
          <option v-for="site in sites" :key="site.id" :value="site.id">
            {{ site.name }}
          </option>
        </select>
      </craft-select>
    </div>
  </ModalForm>
</template>
