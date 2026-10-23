<script setup lang="ts">
  import {onMounted, onUnmounted, ref} from 'vue';
  import type {
    SourcePathSegment,
    VolumeFolderSelectorController,
  } from '@craftcms/ui';
  import '@craftcms/ui/components/element-selector-modal/element-selector-modal';
  import ElementPicker from './ElementPicker.vue';
  import type {FolderCrumb, SelectedElement} from './useModalElementIndex';
  import {useElementSelectorController} from './useElementSelectorController';

  const props = defineProps<{controller: VolumeFolderSelectorController}>();

  const {indexBody} = useElementSelectorController(props.controller);

  const index = ref<InstanceType<typeof ElementPicker> | null>(null);

  /**
   * Where the index is, for the controller: Select with nothing highlighted
   * chooses the folder that's open.
   */
  let sourcePath: SourcePathSegment[] = [];

  function onFolderPathChange(path: FolderCrumb[]) {
    sourcePath = path.map(({folderId, label}) => ({folderId, label}));

    // Whether the open folder can be chosen depends on which one it is, so the
    // controller re-runs its rules.
    props.controller.setSelection([...props.controller.state.selection]);
  }

  onMounted(() => {
    props.controller.attachIndex({
      clearSelection: () => index.value?.clearSelection(),
      get sourcePath() {
        return sourcePath;
      },
    });
  });

  onUnmounted(() => props.controller.detachIndex());
</script>

<template>
  <craft-element-selector-modal :controller="controller">
    <ElementPicker
      v-if="indexBody"
      ref="index"
      :action="controller.options.bodyAction"
      :initial="indexBody.props as any"
      :params="controller.indexParams()"
      select-folders
      :disabled-folder-ids="controller.disabledFolderIds"
      @selection-change="
        (elements: SelectedElement[]) => controller.setSelection(elements)
      "
      @choose="() => controller.submit()"
      @folder-path-change="onFolderPathChange"
    />
  </craft-element-selector-modal>
</template>
