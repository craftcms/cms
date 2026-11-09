<script setup lang="ts">
  import {actionClient} from '@craftcms/ui';
  import {onMounted, shallowRef} from 'vue';
  import type {ContentIndexData} from '@/modules/elements/index/composables/useContentIndexData';
  import ElementPicker from './ElementPicker.vue';
  import ElementPickerPlaceholder from './ElementPickerPlaceholder.vue';

  /**
   * An element index embedded in a screen: it lists the elements matching
   * `criteria`, each linked to its edit page, with no selection.
   */
  const props = defineProps<{
    elementType: string;
    criteria?: Record<string, unknown>;
    /** Keeps this index's view settings apart from other indexes of the same element type. */
    storageKey?: string;
  }>();

  const action = 'element-selector-modals/body';
  const params = {
    context: 'modal',
    elementType: props.elementType,
    criteria: {...props.criteria},
    readOnly: true,
  };
  const initial = shallowRef<ContentIndexData | null>(null);

  onMounted(async () => {
    const {data} = await actionClient.post(action, params);
    initial.value = data.props as ContentIndexData;
  });
</script>

<template>
  <ElementPicker
    v-if="initial"
    :action="action"
    :initial="initial"
    :params="params"
    read-only
    :storage-key="storageKey"
  />
  <ElementPickerPlaceholder v-else :element-type="elementType" />
</template>
