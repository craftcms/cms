<script setup lang="ts">
  import {t} from '@craftcms/ui';
  import ActionMenu from '@/common/components/ActionMenu.vue';
  import type {Selectable} from '@/common/composables/useSelectable';
  import {canOpenElement, type NestedElement} from './nested-elements';
  import {useUiPreview} from '../uiPreview';
  import {nestedElementActions} from './nested-element-actions';
  import type {
    NestedElementOperations,
    NestedElementQuickEdit,
  } from './useNestedElementOperations';

  const props = defineProps<{
    element: NestedElement;
    elements: NestedElement[];
    selection: Selectable<number>;
    singleColumn: boolean;
    editable: boolean;
    busy: boolean;
    reorderable: boolean;
    operations: NestedElementOperations;
    quickEdit: NestedElementQuickEdit;
    editOpener?: HTMLElement | null;
  }>();

  function actionIds(): number[] {
    return props.selection.isSelected(props.element.id)
      ? [...props.selection.selectedIds.value]
      : [props.element.id];
  }

  function actions() {
    const ids = actionIds();
    const selectedElements = props.elements.filter((element) =>
      ids.includes(element.id)
    );
    const index = props.elements.findIndex(
      (candidate) => candidate.id === props.element.id
    );

    return nestedElementActions({
      element: props.element,
      selectedElements,
      ids,
      index,
      count: props.elements.length,
      editable: props.editable,
      busy: props.busy,
      canPaste: props.operations.canPaste.value,
      canReorder: props.reorderable,
      canAdd: props.operations.canAdd,
      pasteLabel: props.operations.pasteActionLabel(
        props.singleColumn ? 'above' : 'before'
      ),
    });
  }

  /**
   * Previews can't open slideouts over their modal, so their elements open in
   * a new tab instead, as the draft or revision they belong to.
   */
  const previewing = useUiPreview();

  function openLabel(): string {
    return t('Open {title} in a new tab', {
      title:
        props.element.cardAttributes?.data?.label ?? `#${props.element.id}`,
    });
  }

  function editLabel(): string {
    return t('Edit {title}', {
      title:
        props.element.cardAttributes?.data?.label ?? `#${props.element.id}`,
    });
  }

  function actionLabel(): string {
    return t('Actions for {title}', {
      title:
        props.element.cardAttributes?.data?.label ?? `#${props.element.id}`,
    });
  }
</script>

<template>
  <craft-button
    v-if="previewing && canOpenElement(element)"
    data-open-element
    variant="plain"
    inherit
    size="small"
    icon="external"
    :href="element.editUrl"
    target="_blank"
    :aria-label="openLabel()"
    :title="t('Open in a new tab')"
  ></craft-button>
  <craft-button
    v-else-if="quickEdit.canOpen(element)"
    data-edit-element
    variant="plain"
    inherit
    type="button"
    size="small"
    icon="edit"
    :aria-label="editLabel()"
    :title="t('Edit')"
    .disabled="busy"
    @click.stop="
      quickEdit.openRow(
        element,
        editOpener ?? ($event.currentTarget as HTMLElement)
      )
    "
  ></craft-button>
  <ActionMenu
    v-if="
      !element.cardAttributes?.data?.['revision-id'] && actions().length > 0
    "
    :label="actionLabel()"
    :actions="actions()"
    :flush="false"
  />
</template>
