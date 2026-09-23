<script setup lang="ts">
  import {t} from '@craftcms/ui/utilities/translate';
  import {computed, ref} from 'vue';
  import Select from '@/common/form/Select.vue';

  const props = defineProps<{
    currentPage: number;
    lastPage: number;
    loading?: boolean;
  }>();

  const emit = defineEmits<{
    (e: 'move', page: number): void;
  }>();

  interface PopoverElement extends HTMLElement {
    hide(): Promise<void>;
  }

  const popover = ref<PopoverElement>();
  const targetPageRaw = ref<string | number>(props.currentPage);

  // Native select values are strings, but the page number is numeric.
  const targetPage = computed(() => parseInt(String(targetPageRaw.value), 10));

  const pages = Array.from({length: props.lastPage}, (_, i) => i + 1);

  function handleMove(): void {
    if (targetPage.value === props.currentPage) {
      return;
    }

    emit('move', targetPage.value);
    popover.value?.hide();
  }

  function onOpenedChanged(event: CustomEvent<boolean>): void {
    if (event.detail) {
      targetPageRaw.value = props.currentPage;
    }
  }
</script>

<template>
  <craft-popover
    ref="popover"
    placement="bottom-end"
    @opened-changed="onOpenedChanged"
  >
    <!-- The popover relocates its invoker; v-once prevents Vue patching the moved DOM. -->
    <craft-button type="button" slot="invoker" size="small" v-once>{{
      t('Move to page…')
    }}</craft-button>

    <div slot="content-body" class="flex flex-nowrap items-end gap-2">
      <Select
        :label="t('Choose a page')"
        v-model="targetPageRaw"
        :options="pages.map((p) => ({label: String(p), value: String(p)}))"
      />
      <craft-button
        type="button"
        variant="primary"
        size="small"
        :loading="loading"
        @click="handleMove"
        >{{ t('Move') }}</craft-button
      >
    </div>
  </craft-popover>
</template>
