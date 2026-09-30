<script setup lang="ts">
  import {ButtonVariant, t} from '@craftcms/ui';
  import {onLongPress} from '@vueuse/core';
  import {computed, useTemplateRef} from 'vue';
  import type {Selectable} from '@/common/composables/useSelectable';
  import ElementCards from '@/modules/elements/components/ElementCards.vue';
  import NestedEntriesCreateButton from './NestedEntriesCreateButton.vue';
  import NestedEntryCardActions from './NestedEntryCardActions.vue';
  import {
    nestedCreateChoices,
    type NestedEntry,
    type NestedEntriesManager,
  } from './nested-entries';
  import {useNestedEntryActionEvents} from './nested-entry-actions';
  import type {
    NestedEntryOperations,
    NestedEntryQuickEdit,
  } from './useNestedEntryOperations';

  const props = defineProps<{
    cards: NestedEntry[];
    manager: NestedEntriesManager | null;
    selection: Selectable<number>;
    singleColumn: boolean;
    editable: boolean;
    operations: NestedEntryOperations;
    quickEdit: NestedEntryQuickEdit;
    editOpener?: HTMLElement | null;
  }>();
  const container = useTemplateRef<HTMLElement>('container');
  const createChoices = computed(() => nestedCreateChoices(props.manager));
  const reorderable = computed(
    () => props.operations.canReorder.value && !props.operations.busy.value
  );

  function actionIds(entry: NestedEntry): number[] {
    return props.selection.isSelected(entry.id)
      ? [...props.selection.selectedIds.value]
      : [entry.id];
  }

  useNestedEntryActionEvents(container, {
    entries: () => props.cards,
    actionIds,
    editable: () => props.editable,
    busy: props.operations.busy,
    handlers: {
      perform: (item, ids, trigger) =>
        void props.operations.performElementAction(item, ids, trigger),
      paste: props.operations.paste,
      move: (from, to) => props.operations.reorder(from, to, false),
    },
  });

  onLongPress(container, (event) => {
    if (event.pointerType === 'touch') {
      props.quickEdit.onDblClick(event);
    }
  });
</script>

<template>
  <div>
    <div
      ref="container"
      class="min-w-0 max-w-full"
      :aria-busy="operations.busy.value"
      @mousedown.capture="quickEdit.suppressPrimaryLinkVisit"
      @mouseup.capture="quickEdit.suppressPrimaryLinkVisit"
      @click.capture="quickEdit.onPrimaryLink"
      @dblclick="quickEdit.onDblClick"
    >
      <ElementCards
        :data="cards"
        :item-behavior="{
          attrs: (element) => ({
            'data-index-id': element.id,
            'data-nested-id': element.id,
          }),
        }"
        :selection="selection"
        :selectable="editable"
        :read-only="!editable"
        :single-column="singleColumn"
        :sortable="reorderable"
        :loading="operations.busy.value && !cards.length"
        :render-server-actions="false"
        @reorder="operations.reorder"
      >
        <template #actions="{element}">
          <NestedEntryCardActions
            :entry="element as NestedEntry"
            :entries="cards"
            :selection="selection"
            :single-column="singleColumn"
            :editable="editable"
            :busy="operations.busy.value"
            :reorderable="reorderable"
            :operations="operations"
            :quick-edit="quickEdit"
            :edit-opener="editOpener"
          />
        </template>
        <template #empty>
          <craft-empty>{{ t('Nothing yet.') }}</craft-empty>
        </template>
      </ElementCards>
    </div>
    <div v-if="editable" class="nested-create">
      <craft-button
        v-if="operations.canPaste.value"
        type="button"
        :variant="ButtonVariant.Fill"
        .disabled="operations.busy.value || !operations.canAdd(1)"
        @click="operations.paste()"
        >{{ operations.pasteButtonLabel.value }}</craft-button
      >
      <NestedEntriesCreateButton
        v-if="manager?.canCreate"
        class="grow basis-full"
        :choices="createChoices"
        :label="manager.createButtonLabel || t('New entry')"
        :disabled="operations.busy.value || !operations.canAdd(1)"
        @create="operations.create"
      />
    </div>
  </div>
</template>

<style scoped>
  :deep(.card-grid:not(.card-grid--single)) {
    grid-template-columns: repeat(auto-fill, minmax(min(270px, 100%), 1fr));
  }

  .nested-create {
    display: flex;
    flex-wrap: wrap;
    gap: var(--c-spacing-sm);
    align-items: center;
    margin-block: var(--c-spacing-md);
  }
</style>
