<script setup lang="ts">
  import {ButtonVariant, t} from '@craftcms/ui';
  import {useEventListener} from '@vueuse/core';
  import {
    computed,
    h,
    inject,
    normalizeClass,
    ref,
    shallowRef,
    watch,
    type ComputedRef,
  } from 'vue';
  import ActionMenu from '@/common/components/ActionMenu.vue';
  import {createCraftColumnHelper} from '@/common/table/createCraftColumnHelper';
  import type {ColumnDef} from '@tanstack/vue-table';
  import type {CraftTableFeatures} from '@/common/table/craftTable';
  import ElementIndex from '@/modules/elements/index/components/ElementIndex.vue';
  import ElementBulkActionsBar from '@/modules/elements/index/components/ElementBulkActionsBar.vue';
  import {saveInlineElements} from '@/modules/elements/index/save-inline-elements';
  import {DUPLICATE_ACTION} from '@/modules/elements/index/element-action-identity';
  import {NestedOwnerEditorKey} from '@/modules/elements/nested-owner';
  import type {
    BulkActionEventDetail,
    BulkActionItem,
  } from '@/modules/elements/types/actions';
  import type {ElementIndexRow} from '@/modules/elements/index/composables/useContentIndexData';
  import NestedElementCardActions from './NestedElementCardActions.vue';
  import NestedElementsCreateButton from './NestedElementsCreateButton.vue';
  import {useNestedElementsControl} from './nested-elements-context';
  import {
    nestedCreateChoices,
    nestedIndexParams,
    type NestedElementsProps,
    type NestedElement,
    type NestedIndexElement,
  } from './nested-elements';
  import {
    nestedElementActions,
    useNestedElementActions,
    useNestedElementActionEvents,
  } from './nested-element-actions';
  import {useNestedElementsQuery} from './useNestedElementsQuery';
  import {
    useNestedElementOperations,
    type NestedElementOperations,
  } from './useNestedElementOperations';

  const props = defineProps<{
    control: NestedElementsProps;
    editable: boolean;
  }>();

  const owner = inject(NestedOwnerEditorKey, null);
  const controlContext = useNestedElementsControl();
  const container = ref<HTMLElement>();
  const busy = shallowRef(false);
  const error = shallowRef('');
  const preparedOwnerId = shallowRef<number | null>(null);
  const manager = computed(() => props.control.manager);
  const editable = computed(() => props.editable);
  const effectiveControl = computed<NestedElementsProps>(() => ({
    ...props.control,
    manager: manager.value
      ? {
          ...manager.value,
          ownerId: preparedOwnerId.value ?? manager.value.ownerId,
        }
      : null,
  }));
  const columnHelper = createCraftColumnHelper<NestedIndexElement>();
  let queryState!: ReturnType<typeof useNestedElementsQuery>;
  let operations!: NestedElementOperations;
  let serverActions!: ComputedRef<BulkActionItem[]>;
  let canInteractWithOrder!: ComputedRef<boolean>;
  let movedElementId: number | null = null;

  const elements = computed<NestedIndexElement[]>(() =>
    queryState ? queryState.elements.value : []
  );
  const loading = computed(() => queryState?.loading.value ?? false);

  queryState = useNestedElementsQuery({
    props: () => effectiveControl.value,
    busy,
    error,
    onLoaded: async () => {
      if (movedElementId !== null) {
        operations.focusElement(movedElementId);
        movedElementId = null;
      } else if (document.activeElement === container.value) {
        operations?.onContainerFocus();
      }
    },
    editable: () => props.editable,
    readOnly: () => !props.editable,
    saveInline: saveInlineBody,
    rowReorder: {
      enabled: () =>
        props.editable && Boolean(queryState?.payload.value.reorderable),
      move: (from, to) => operations?.reorder(from, to),
    },
    columns: (columns, {elementIndex}) =>
      computed(() => [
        ...columns.value.map((column) => ({
          ...column,
          enableSorting: elementIndex.sortOptions.some(
            (option) => option.value === column.id
          ),
        })),
        ...((elementIndex.data as NestedIndexElement[]).some(
          (element) => element.actionMenuItems?.length
        )
          ? [
              columnHelper.actions(({row}) => [
                hActionMenu(row.original as NestedIndexElement),
              ]),
            ]
          : []),
      ]) as ComputedRef<Array<ColumnDef<CraftTableFeatures, ElementIndexRow>>>,
  });

  const {
    model,
    payload,
    table,
    search,
    mode,
    page,
    pagination,
    unfilteredTotal,
  } = queryState;
  const canReorder = computed(
    () => props.editable && payload.value.reorderable
  );
  const pageOffset = (targetPage = page.value): number =>
    (targetPage - 1) * pagination.value.per_page;
  const offset = computed(() => pageOffset());
  canInteractWithOrder = computed(
    () => canReorder.value && !busy.value && !loading.value
  );
  const {selection, selectedIds, clearSelection} = queryState.selection;

  operations = useNestedElementOperations({
    elements,
    offset,
    canReorder,
    selection,
    count: unfilteredTotal,
    refresh,
    manager,
    owner,
    editable,
    controlPath: controlContext.path,
    container,
    markModified: controlContext.markModified,
    refreshCreatedElementOnOpen: false,
    busy,
    error,
    ownerId: preparedOwnerId,
  });
  const actions = useNestedElementActions({
    elements,
    manager,
    initial: () => props.control.index?.initial,
    selection,
    operations,
    busy: () => busy.value || loading.value,
  });

  const createChoices = computed(() => nestedCreateChoices(manager.value));
  const canCreate = computed(() => props.editable && manager.value?.canCreate);
  const selectedElements = computed(() =>
    table
      .getSelectedRowModel()
      .rows.map((row) => row.original as NestedIndexElement)
  );
  serverActions = computed(() =>
    (queryState.payloadActions.value ?? []).map((action) => ({
      ...action,
      disabled:
        action.disabled ||
        busy.value ||
        loading.value ||
        (action.key === DUPLICATE_ACTION &&
          !operations.canAdd(selectedElements.value.length)),
    }))
  );
  const movePage = ref(page.value === 1 ? 2 : page.value - 1);
  const quickEdit = operations.quickEdit;

  function openSelectedElement(detail: BulkActionEventDetail): void {
    if (detail.elementIds.length === 1) {
      quickEdit.openById(detail.elementIds[0]!, detail.trigger);
    }
  }

  function actionElements(element: NestedElement): NestedElement[] {
    return selection.isSelected(element.id)
      ? selectedElements.value
      : [element];
  }

  function rowActions(element: NestedElement) {
    const selectedActionElements = actionElements(element);
    const ids = selectedActionElements.map((item) => item.id);
    const index = elements.value.findIndex(
      (candidate) => candidate.id === element.id
    );

    return nestedElementActions({
      element,
      selectedElements: selectedActionElements,
      ids,
      index,
      count: elements.value.length,
      editable: props.editable,
      busy: busy.value || loading.value,
      canPaste: operations.canPaste.value,
      canReorder: canInteractWithOrder.value,
      canAdd: operations.canAdd,
      pasteLabel: operations.pasteActionLabel('before'),
    });
  }

  function hActionMenu(element: NestedElement) {
    return h(ActionMenu, {
      label: t('Actions for {name}', {
        name: String(element.cardAttributes?.data?.label ?? element.id),
      }),
      actions: rowActions(element),
    });
  }

  useNestedElementActionEvents(container, {
    elements,
    actionIds: (element) =>
      actionElements(element).map((selected) => selected.id),
    editable,
    busy: computed(() => busy.value || loading.value),
    enabled: computed(() => mode.value === 'table' || mode.value === 'cards'),
    handlers: {
      perform: (item, ids, trigger) =>
        void actions.performRow(item, ids, trigger),
      paste: operations.paste,
      move: (from, to) => operations.reorder(from, to, false),
    },
  });

  async function saveInlineBody(body: URLSearchParams) {
    const currentManager = manager.value;
    if (!currentManager) {
      return false as const;
    }

    let result:
      | {errors?: Record<number, Record<string, string[]>>; message?: string}
      | false = false;
    await operations.mutate(async (ownerId) => {
      result = await saveInlineElements(body, {
        ...nestedIndexParams(
          currentManager,
          ownerId,
          props.control.index?.initial
        ),
        elementType: currentManager.elementType,
        siteId: currentManager.ownerSiteId,
        context: 'embeddedIndex',
      });

      if (!result) {
        return false;
      }

      if (result.errors && Object.keys(result.errors).length) {
        return false;
      }
    });

    return result;
  }

  async function moveSelectionToPage(): Promise<void> {
    const targetPage = Math.min(
      Math.max(1, Math.trunc(movePage.value)),
      pagination.value.last_page
    );
    if (targetPage === page.value) {
      return;
    }

    const ids = [...selectedIds.value] as number[];
    const moved = await operations.reorderIds(ids, pageOffset(targetPage));
    if (moved) {
      movedElementId = ids[0] ?? null;
      page.value = targetPage;
    }
  }

  function onEmbeddedInputKeydown(event: KeyboardEvent): void {
    if (
      event.key !== 'Enter' ||
      !(event.target instanceof HTMLInputElement) ||
      event.target.closest('.element-toolbar__filter')
    ) {
      return;
    }

    if (event.target.closest('[data-inline-id]')) {
      return;
    }

    event.preventDefault();
    event.stopImmediatePropagation();
  }

  async function refresh(reset = false): Promise<void> {
    if (reset && (search.value || page.value !== 1)) {
      await queryState.resetSearch();

      return;
    }

    await queryState.load();
  }

  watch(page, (currentPage) => {
    movePage.value = currentPage === 1 ? 2 : currentPage - 1;
  });
  watch([page, mode], clearSelection);
  watch(elements, () => selection.prune(elements.value.map(({id}) => id)));

  useEventListener(window, 'craft:nested-validation', (event: Event) => {
    if (
      event.target instanceof Element &&
      event.target.contains(container.value ?? null)
    ) {
      queryState.setInvalidElements(
        (event as CustomEvent<{ids: number[]}>).detail.ids
      );
    }
  });
</script>

<template>
  <section
    ref="container"
    class="nested-index"
    tabindex="-1"
    :aria-busy="loading || busy"
    @focus.self="operations.onContainerFocus"
    @keydown.capture="onEmbeddedInputKeydown"
  >
    <div v-if="error" role="alert">{{ error }}</div>
    <ElementIndex
      :view="model.view"
      :toolbar-as-form="false"
      :quick-edit="quickEdit"
      :footer-active="queryState.selection.hasSelection.value"
      :item-behavior="{
        attrs: (row) => ({
          'data-nested-id': row.id,
          class: {
            error: normalizeClass(
              (row as NestedIndexElement).cardAttributes?.class
            )
              .split(/\s+/)
              .includes('error'),
          },
        }),
      }"
    >
      <template #toolbar-actions>
        <craft-button
          v-if="editable && operations.canPaste.value"
          type="button"
          :variant="ButtonVariant.Fill"
          .disabled="busy || loading"
          @click="operations.paste()"
          >{{ operations.pasteButtonLabel.value }}</craft-button
        >
      </template>

      <template #toolbar-actions-after>
        <craft-button
          type="button"
          :variant="ButtonVariant.Fill"
          .disabled="busy || loading"
          @click="queryState.load()"
          >{{ t('Refresh') }}</craft-button
        >
        <NestedElementsCreateButton
          v-if="canCreate && manager"
          :choices="createChoices"
          :label="manager.createButtonLabel || t('New element')"
          :disabled="busy || loading || !operations.canAdd(1)"
          @create="operations.create"
        />
      </template>

      <template #footer>
        <ElementBulkActionsBar
          :selected-ids="selectedIds"
          :selected-elements="selectedElements"
          :actions="serverActions"
          :element-type="model.view.elementIndex.elementType"
          :source="model.view.elementIndex.source?.key"
          :context="model.view.elementIndex.context"
          :params="
            manager
              ? nestedIndexParams(
                  manager,
                  manager.ownerId,
                  control.index?.initial
                )
              : {}
          "
          :perform="actions.perform"
          @edit="openSelectedElement"
          @view="openSelectedElement"
          @clear="clearSelection"
        >
          <label
            v-if="canReorder && pagination.last_page > 1"
            class="flex items-center gap-md"
          >
            {{ t('Move to page') }}
            <select v-model.number="movePage" class="text small">
              <option
                v-for="targetPage in pagination.last_page"
                :key="targetPage"
                :value="targetPage"
                :disabled="targetPage === page"
              >
                {{ targetPage }}
              </option>
            </select>
          </label>
          <craft-button
            v-if="canReorder && pagination.last_page > 1"
            type="button"
            :variant="ButtonVariant.Plain"
            .disabled="busy || loading || movePage === page"
            @click="moveSelectionToPage"
            >{{ t('Move') }}</craft-button
          >
        </ElementBulkActionsBar>
      </template>

      <template #card-actions="{element}">
        <NestedElementCardActions
          :element="element as NestedElement"
          :elements="elements"
          :selection="selection"
          :single-column="false"
          :editable="editable"
          :busy="busy || loading"
          :reorderable="canInteractWithOrder"
          :operations="operations"
          :quick-edit="quickEdit"
        />
      </template>
    </ElementIndex>
  </section>
</template>

<style scoped>
  .nested-index {
    --cp-container-padding: 0;
    container: cp-content-view / inline-size;
    min-width: 0;
    max-width: 100%;
  }

  .nested-index :deep(.element-index__footer) {
    position: static;
  }

  .nested-index :deep(craft-card::part(body)) {
    padding: var(--c-spacing-md);
  }

  .nested-index :deep(.card-grid:not(.card-grid--single)) {
    grid-template-columns: repeat(auto-fill, minmax(min(270px, 100%), 1fr));
  }

  @media (width < var(--breakpoint-sm)) {
    .nested-index :deep(.card-grid) {
      grid-template-columns: minmax(0, 1fr);
    }
  }
</style>
