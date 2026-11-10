<script setup lang="ts">
  import {actionClient, ButtonVariant, t} from '@craftcms/ui';
  import {computed, ref, shallowRef, watch} from 'vue';
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
    /** The source to list, for element types whose default source would exclude the criteria's matches. */
    source?: string;
    /** Keeps this index's view settings apart from other indexes of the same element type. */
    storageKey?: string;
  }>();

  const action = 'element-selector-modals/body';
  const params = computed(() => ({
    context: 'modal',
    elementType: props.elementType,
    criteria: {...props.criteria},
    ...(props.source ? {source: props.source} : {}),
    readOnly: true,
  }));
  const initial = shallowRef<ContentIndexData | null>(null);
  const failed = ref(false);
  const generation = ref(0);
  let latestRequest = 0;

  async function load(): Promise<void> {
    const request = ++latestRequest;
    initial.value = null;
    failed.value = false;

    try {
      const {data} = await actionClient.post(action, params.value);

      if (request === latestRequest) {
        initial.value = data.props as ContentIndexData;
        generation.value++;
      }
    } catch {
      if (request === latestRequest) {
        failed.value = true;
      }
    }
  }

  watch(
    () => JSON.stringify(params.value),
    () => void load(),
    {immediate: true}
  );
</script>

<template>
  <div v-if="failed" role="alert" class="flex items-center gap-md">
    <span>{{ t('The list couldn’t be loaded.') }}</span>
    <craft-button
      type="button"
      :variant="ButtonVariant.Plain"
      @click="load()"
      >{{ t('Try again') }}</craft-button
    >
  </div>
  <ElementPicker
    v-else-if="initial"
    :key="generation"
    :action="action"
    :initial="initial"
    :params="params"
    read-only
    :storage-key="storageKey"
    @error="failed = true"
  />
  <ElementPickerPlaceholder v-else :element-type="elementType" />
</template>
