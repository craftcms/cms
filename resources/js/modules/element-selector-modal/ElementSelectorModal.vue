<script setup lang="ts">
  import {onMounted, onUnmounted, ref} from 'vue';
  import type {ElementSelectorController} from '@craftcms/ui';
  import '@craftcms/ui/components/element-selector-modal/element-selector-modal';
  import ModalElementIndex from './ModalElementIndex.vue';
  import type {SelectedElement} from './useModalElementIndex';
  import {useElementSelectorController} from './useElementSelectorController';

  const props = defineProps<{controller: ElementSelectorController}>();

  /**
   * No emits: the controller is the event bus.
   *
   * Anything that wants to know about a selection listens to the controller (or
   * passes `onSelect`), which is also what the web component and the imperative
   * openers do. Re-emitting here would give one event two sources.
   */
  const {indexBody, disabledElementIds} = useElementSelectorController(
    props.controller
  );

  const index = ref<InstanceType<typeof ModalElementIndex> | null>(null);

  onMounted(() => {
    props.controller.attachIndex({
      clearSelection: () => index.value?.clearSelection(),
    });
  });

  onUnmounted(() => props.controller.detachIndex());
</script>

<template>
  <craft-element-selector-modal :controller="controller">
    <!--
      The index talks to the controller, never to the chrome around it — it emits
      what happened and the controller decides what that means. That is what lets
      the same index sit inside the web component here and inside a plugin's own
      presentation elsewhere.
    -->
    <ModalElementIndex
      v-if="indexBody"
      ref="index"
      :action="controller.options.bodyAction"
      :initial="indexBody.props as any"
      :params="controller.indexParams()"
      :disabled-element-ids="disabledElementIds"
      @selection-change="
        (elements: SelectedElement[]) => controller.setSelection(elements)
      "
      @choose="() => controller.submit()"
    />
  </craft-element-selector-modal>
</template>
