<script setup lang="ts">
  import {ref} from 'vue';
  import ElementChips from '@/modules/elements/components/ElementChips.vue';
  import {useSelectable} from '@/common/composables/useSelectable';

  interface ChipElement {
    id: number;
    label: string;
    siteId?: number | string | null;
    status?: {fill: string; label: string; draft: boolean} | null;
    thumbHtml?: string;
  }

  const props = defineProps<{
    elements: ChipElement[];
    inline?: boolean;
    selectable?: boolean;
    sortable?: boolean;
  }>();

  const data = ref([...props.elements]);
  const selection = useSelectable({
    ids: () => data.value.map((element) => element.id),
    enabled: () => props.selectable,
  });

  function reorder(startIndex: number, finishIndex: number): void {
    const [moved] = data.value.splice(startIndex, 1);
    data.value.splice(finishIndex, 0, moved!);
  }
</script>

<template>
  <ElementChips
    :data="data"
    :selection="selection"
    :inline="inline"
    :selectable="selectable"
    :sortable="sortable"
    @reorder="reorder"
  />
</template>
