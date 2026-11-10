<script setup lang="ts">
  import {actionClient, ButtonVariant, t} from '@craftcms/ui';
  import {
    computed,
    nextTick,
    ref,
    shallowRef,
    useTemplateRef,
    watch,
  } from 'vue';
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
  const root = useTemplateRef<HTMLElement>('root');
  let latestRequest = 0;

  async function focusWithin(selector: string): Promise<void> {
    await nextTick();
    root.value?.querySelector<HTMLElement>(selector)?.focus();
  }

  async function load(restoreFocus = false): Promise<void> {
    const request = ++latestRequest;
    initial.value = null;
    failed.value = false;

    try {
      const {data} = await actionClient.post(action, params.value);

      if (request === latestRequest) {
        initial.value = data.props as ContentIndexData;
        generation.value++;

        if (restoreFocus) {
          void focusWithin('input, button, a[href]');
        }
      }
    } catch {
      if (request === latestRequest) {
        showFailure();
      }
    }
  }

  function showFailure(): void {
    failed.value = true;
    void focusWithin('craft-button');
  }

  watch(
    () => JSON.stringify(params.value),
    () => void load(),
    {immediate: true}
  );
</script>

<template>
  <div ref="root">
    <div v-if="failed" role="alert" class="flex items-center gap-md">
      <span>{{ t('The list couldn’t be loaded.') }}</span>
      <craft-button
        type="button"
        :variant="ButtonVariant.Plain"
        @click="load(true)"
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
      @error="showFailure"
    />
    <ElementPickerPlaceholder v-else :element-type="elementType" />
  </div>
</template>
