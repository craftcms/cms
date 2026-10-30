<script setup lang="ts">
  import '@craftcms/ui/components/chip/chip';
  import {actionClient, t} from '@craftcms/ui';
  import {computed, reactive, ref, useId} from 'vue';
  import ActionMenu from '@/common/components/ActionMenu.vue';
  import ElementList from '@/modules/elements/components/ElementList.vue';
  import {useElementList} from '@/modules/elements/composables/useElementList';
  import {createElementActionMenu} from '@/modules/elements/composables/useElementActionMenu';
  import type {UiChangeKind, UiControlPayload} from './types';
  import {inputName} from './runtime';
  import AssetUploadButton from '@/pages/assets/AssetUploadButton.vue';
  import {openSlideout} from '@/common/slideouts';
  import EditElementController from '@/actions/CraftCms/Cms/Http/Controllers/Elements/EditElementController';
  import SelectionToolbar from './element-select/SelectionToolbar.vue';
  import {chipMenu, selectionMenu} from './element-select/chipMenu';
  import {usePasteableElements} from './element-select/usePasteableElements';
  import type {
    ElementPresentation,
    ElementSelectProps,
  } from './element-select/types';

  const props = defineProps<{
    control: UiControlPayload<ElementSelectProps>;
    value: Array<number | string> | number | string | null | undefined;
    editable: boolean;
  }>();
  const emit = defineEmits<{
    (
      event: 'update:value',
      value: number[] | number | null,
      kind: UiChangeKind
    ): void;
  }>();

  const presentations = reactive(new Map<number, ElementPresentation>());
  const id = useId();
  const providedPresentations = computed(
    () =>
      new Map(
        props.control.props.elements.map((element) => [element.id, element])
      )
  );

  const limit = computed(() => props.control.props.limit);

  /**
   * Uploads come from `AssetSelect`, which is the only Control that sends these
   * props — every other element type omits them entirely.
   */
  const showUpload = computed(
    () => props.editable && props.control.props.canUpload === true
  );
  const uploadKinds = computed(() => {
    const kind = props.control.props.criteria.kind;

    return kind === undefined ? undefined : ([kind].flat() as string[]);
  });

  const ids = computed(() =>
    (Array.isArray(props.value)
      ? props.value
      : // Loose on purpose: absent means the same as empty here — a relation
        // nested in a block the server has just minted resolves its path to
        // undefined for a beat.
        props.value == null || props.value === ''
        ? []
        : [props.value]
    ).map(elementId)
  );

  function updateValue(ids: number[]): void {
    emit(
      'update:value',
      props.control.props.single ? (ids[0] ?? null) : ids,
      'discrete'
    );
  }

  /** `next`, cut down to the field's limit from the start. */
  function withinLimit(next: number[]): number[] {
    return limit.value === null
      ? next
      : next.slice(0, Math.max(limit.value, 1));
  }

  /**
   * A field that maintains hierarchy keeps its elements in structure order,
   * nested by level, as Craft 5's `_elements/structurelist` drew them.
   */
  const maintainHierarchy = computed(
    () => props.control.props.maintainHierarchy === true
  );

  /**
   * One relation can't be reordered, and a read-only field can't be either. A
   * hierarchy's order is the structure's, so it can't be reordered by hand.
   */
  const sortable = computed(
    () => props.editable && ids.value.length > 1 && !maintainHierarchy.value
  );

  /**
   * Whether the field is full. At the limit the add and upload controls come
   * down, single-relation fields included — a full field's selection changes
   * through the chip's Replace action rather than by picking again.
   */
  const atLimit = computed(() => {
    return limit.value !== null && ids.value.length >= limit.value;
  });

  function elementId(value: number | string): number {
    return Number(value);
  }

  function presentation(value: number | string): ElementPresentation {
    const id = elementId(value);

    return (
      providedPresentations.value.get(id) ??
      presentations.get(id) ?? {id, label: String(id)}
    );
  }

  // ───────────────────────────── selection ─────────────────────────────

  /**
   * Chip selection, with the shift-range and toggle behavior of the element
   * index. Selection is the input to bulk removal: "Remove" on a selected chip
   * removes the whole selection, which is what Craft 5's
   * `defineElementActions` does via `elementSelect.isSelected()`.
   *
   * It's only worth offering when more than one element can be related — a
   * single-relation field has nothing to bulk-act on. A `null` limit is
   * unlimited, so it counts as more than one.
   */
  const selectable = computed(() => props.editable && limit.value !== 1);

  const viewMode = computed(() => props.control.props.viewMode);

  /**
   * What the card and thumb bodies draw, in the field's current order.
   *
   * Built from `value` rather than `control.props.elements` for the same reason
   * the chips are: the props list is whatever the server last rendered, so it goes
   * stale the moment something is added or removed client-side, leaving the bodies
   * drawing a different set from the one selection is tracking.
   *
   * Elements picked since the last server render have no card or thumb parts yet
   * (the selector only reports a label), so those come through blank until the
   * next round-trip.
   */
  const listData = computed(() =>
    ids.value.map((selectedValue) => ({
      ...presentation(selectedValue),
      id: elementId(selectedValue),
      ...(maintainHierarchy.value ? {} : {level: undefined}),
    }))
  );

  const list = useElementList({
    ids,
    viewMode,
    selectable,
    readOnly: () => !props.editable,
  });

  const {selectedIds, isSelected, prune: pruneSelection} = list.selection;

  // ───────────────────────────── reordering ─────────────────────────────

  function reorder(startIndex: number, finishIndex: number): void {
    const next = [...ids.value];
    const [moved] = next.splice(startIndex, 1);

    if (moved === undefined) {
      return;
    }

    next.splice(finishIndex, 0, moved);
    updateValue(next);
  }

  // ───────────────────────────── hierarchy ─────────────────────────────

  /**
   * Relates `next`. A hierarchy is then put back in structure order by the
   * server, which fills in missing ancestors and applies the branch limit, as
   * `relational-fields/structured-input-html` does for Craft 5's input. The
   * chips show straight away, and move into place when the server answers,
   * unless the selection has changed again in the meantime.
   */
  async function relate(next: number[], siteId?: number | string | null) {
    updateValue(next);

    if (!maintainHierarchy.value || next.length === 0) {
      return;
    }

    let elements: ElementPresentation[];

    try {
      const {data} = await actionClient.post<{
        elements?: ElementPresentation[];
      }>('relational-fields/structured-elements', {
        elementType: props.control.props.elementType,
        elementIds: next,
        siteId: siteId ?? null,
        branchLimit: props.control.props.branchLimit ?? null,
      });
      elements = data.elements ?? [];
    } catch {
      return;
    }

    if (ids.value.join() !== next.join()) {
      return;
    }

    for (const element of elements) {
      presentations.set(element.id, element);
    }

    updateValue(elements.map((element) => element.id));
  }

  /** `remove`, plus the descendants of each, which can't stay without their ancestor. */
  function withDescendants(remove: Set<number>): Set<number> {
    if (!maintainHierarchy.value) {
      return remove;
    }

    const removing = new Set<number>();
    let branchLevel: number | null = null;

    for (const element of listData.value) {
      const level = element.level ?? 1;

      if (branchLevel !== null && level > branchLevel) {
        removing.add(element.id);
        continue;
      }

      branchLevel = remove.has(element.id) ? level : null;

      if (branchLevel !== null) {
        removing.add(element.id);
      }
    }

    return removing;
  }

  // ───────────────────────────── selection modal ─────────────────────────────

  const addButton = ref<HTMLElement | null>(null);
  /** The pane doubles as the upload drop target, as `$container` does in Craft 5. */
  const dropZone = ref<HTMLElement | null>(null);

  /**
   * Learn an element's label from the modal so a freshly-added chip renders as
   * itself rather than as its ID. `control.props.elements` only refreshes on the
   * next server render.
   */
  function remember(elements: Array<Record<string, unknown>>): number[] {
    return elements.map((element) => {
      const id = Number(element.id);

      presentations.set(id, {
        id,
        label: String(element.label ?? id),
        siteId: (element.siteId as number | null) ?? null,
      });

      return id;
    });
  }

  /**
   * Opens the element selector.
   *
   * `replacing` is the id being replaced — the chip's "Replace" action — in
   * which case the chosen element takes its place instead of being appended.
   *
   * The modal is imported lazily, matching `createElementSelectorModal`'s own
   * reason for being async: a page full of relation fields shouldn't pay for the
   * element index unless someone actually opens one.
   */
  async function openSelector(replacing: number | null = null): Promise<void> {
    const {createElementSelectorModal} =
      await import('@/modules/element-selector-modal/create-element-selector-modal');

    const remaining =
      limit.value === null
        ? null
        : Math.max(
            1,
            limit.value -
              (replacing === null ? ids.value.length : ids.value.length - 1)
          );

    const modal = await createElementSelectorModal(
      props.control.props.elementType,
      {
        context: props.control.props.context,
        sources: props.control.props.sources,
        criteria: props.control.props.criteria as Record<string, unknown>,
        condition: props.control.props.selectionCondition,
        showSiteMenu: props.control.props.showSiteMenu,
        indexSettings: {showFolders: props.control.props.showFolders ?? true},
        multiSelect: replacing === null && remaining !== 1,
        // Already-related elements can't be picked again — except the one being
        // replaced, which would otherwise disable the obvious no-op choice.
        disabledElementIds: ids.value.filter((id) => id !== replacing),
        modalTitle: props.control.props.selectionLabel,
        selectBtnLabel: props.control.props.selectionLabel,
        triggerElement: () => addButton.value,
        onSelect: (elements) => {
          const chosen = remember(
            elements as unknown as Array<Record<string, unknown>>
          );

          if (chosen.length === 0) {
            return;
          }

          // A branch limit of one means a new choice replaces the old one, as
          // it does in Craft 5.
          const current =
            maintainHierarchy.value &&
            props.control.props.branchLimit === 1 &&
            replacing === null
              ? []
              : ids.value;

          void relate(
            withinLimit(
              replacing === null
                ? [...current, ...chosen]
                : current.flatMap((id) => (id === replacing ? chosen : [id]))
            ),
            presentation(chosen[0]!).siteId
          );
        },
      }
    );

    modal.on('close', () => modal.destroy());
    await modal.show();
  }

  // ───────────────────────────── pasting ─────────────────────────────

  const pasteable = usePasteableElements({
    field: () => props.control.props,
    editable: () => props.editable,
    ids,
    atLimit,
    relate: (elements) => {
      for (const element of elements) {
        presentations.set(element.id, element);
      }

      const kept = withinLimit([
        ...ids.value,
        ...elements.map((element) => element.id),
      ]);
      void relate(kept, elements[0]?.siteId);

      return kept;
    },
    container: dropZone,
    fallbackFocus: addButton,
  });

  // ───────────────────────────── chip actions ─────────────────────────────

  function removeIds(remove: Set<number>): void {
    remove = withDescendants(remove);
    const next = ids.value.filter((id) => !remove.has(id));
    pruneSelection(next);
    updateValue(next);
  }

  /** One dispatcher for every chip's menu, rather than one per element. */
  const toActionItems = createElementActionMenu();

  const menuField = computed(() => ({
    elementType: props.control.props.elementType,
    elementDisplayName: props.control.props.elementDisplayName,
    editable: props.editable,
    toActionItems,
  }));

  const bulkActions = computed(() =>
    selectionMenu(selectedIds.value.map(presentation), menuField.value, () =>
      removeIds(new Set(selectedIds.value))
    )
  );

  const editLabel = computed(() =>
    t('Edit {type}', {type: props.control.props.elementDisplayName})
  );

  function canEditElement(value: number | string): boolean {
    return presentation(value).canEdit === true;
  }

  /**
   * Each chip's menu, built once per render rather than per binding. Cards get
   * a pencil straight to the editor, so their menu leaves the edit item out.
   */
  const chipMenus = computed(
    () =>
      new Map(
        listData.value.map((element) => [
          element.id,
          chipMenu(element, menuField.value, {
            edit: list.isCards.value
              ? undefined
              : {label: editLabel.value, open: () => openEditor(element.id)},
            replace: () => void openSelector(element.id),
            remove: () =>
              removeIds(
                isSelected(element.id)
                  ? new Set(selectedIds.value)
                  : new Set([element.id])
              ),
          }),
        ])
      )
  );

  /**
   * A freshly uploaded asset joins the selection, the way choosing one does.
   *
   * Craft 5 renders the chip server-side and appends it; here the field already
   * knows how to draw a chip from an id and a label, so the upload response's
   * `assetId`/`filename` is all it needs.
   */
  function attachUploaded(asset: {id: number; label: string}): void {
    if (atLimit.value) {
      return;
    }

    presentations.set(asset.id, {id: asset.id, label: asset.label});

    if (ids.value.includes(asset.id)) {
      return;
    }

    const limit = props.control.props.limit;
    const next = [...ids.value, asset.id];

    updateValue(limit === null ? next : next.slice(-Math.max(limit, 1)));
  }

  /** Double-click opens the element's editor slideout, as it does on chips elsewhere. */
  function openEditor(value: number | string): void {
    const element = presentation(value);
    const href =
      element.cpEditUrl ??
      EditElementController['/{cpTrigger?}/{actionTrigger?}/elements/edit'].url(
        undefined,
        {
          query: {
            elementType: props.control.props.elementType,
            elementId: element.id,
            siteId: element.siteId ?? null,
          },
        }
      );

    void openSlideout(href, {
      // Registering a handler keeps a save from reloading the page behind,
      // which would throw away the owner's unsaved edits.
      onSaved: ({data}) =>
        replaceAsset(data?.replacedAssetId, data?.newAssetId),
    });
  }

  /** An image saved as a new asset takes the edited asset's place. */
  function replaceAsset(replacedId: unknown, newId: unknown): void {
    const from = Number(replacedId);
    const to = Number(newId);

    if (!from || !to || !ids.value.includes(from)) {
      return;
    }

    presentations.set(to, {...presentation(from), id: to});
    updateValue(ids.value.map((id) => (id === from ? to : id)));
  }
</script>

<template>
  <!-- `ref="dropZone"`: the whole field accepts dropped files, as `$container`
       does for Craft 5's `AssetSelectInput`. -->
  <div ref="dropZone" class="w-full">
    <input
      v-if="editable"
      type="hidden"
      :name="inputName(control.path)"
      value=""
    />
    <component :is="control.props.customElement" :id="id">
      <div class="relative" v-if="ids.length > 0">
        <!--
          A field that can't be selected (a single relation) has no use for
          the selection toolbar.
        -->
        <SelectionToolbar
          v-if="selectable"
          :selection="list.selection"
          :actions="bulkActions"
          :total="ids.length"
          :limit="limit"
        />
        <div class="elements-stage">
          <ElementList
            :data="listData"
            :view-mode="viewMode"
            :selection="list.selection"
            :selectable="selectable"
            :select-all="false"
            :read-only="!editable"
            :sortable="sortable"
            @edit="(element) => openEditor(element.id)"
            @reorder="reorder"
          >
            <template #append="{element}">
              <input
                v-if="editable"
                type="hidden"
                :name="`${inputName(control.path)}${control.props.single ? '' : '[]'}`"
                :value="String(element.id)"
              />
            </template>
            <!--
              Cards get a pencil straight to the editor, so their menu leaves
              the edit item out; a chip keeps it in the menu.
            -->
            <template #actions="{element}">
              <craft-button
                v-if="list.isCards.value && canEditElement(element.id)"
                type="button"
                size="small"
                variant="plain"
                icon="edit"
                :aria-label="editLabel"
                @click="openEditor(element.id)"
              ></craft-button>
              <ActionMenu
                v-if="chipMenus.get(element.id)?.length"
                :actions="chipMenus.get(element.id)!"
                :flush="
                  list.isList.value
                    ? 'inline-start'
                    : list.isThumbs.value
                      ? false
                      : undefined
                "
              />
            </template>
          </ElementList>
        </div>
      </div>

      <div
        v-if="editable && !atLimit"
        class="flex gap-md"
        :class="{'pt-md': ids.length > 0}"
      >
        <craft-button
          ref="addButton"
          type="button"
          variant="dashed"
          icon="plus"
          data-element-select-add=""
          :aria-label="control.props.selectionLabel"
          @click="openSelector()"
        >
          {{ control.props.selectionLabel }}
        </craft-button>

        <AssetUploadButton
          v-if="control.props.canUpload !== undefined"
          variant="dashed"
          :can-upload="control.props.canUpload"
          :folder-id="control.props.uploadFolderId ?? undefined"
          :allowed-kinds="uploadKinds"
          :drop-zone="dropZone"
          :reload-on-complete="false"
          :disabled="!showUpload"
          @uploaded="attachUploaded"
        />

        <craft-button
          v-if="pasteable.candidates.value.length"
          type="button"
          variant="outline"
          icon="custom-icons/duplicate"
          class="paste-btn"
          data-element-select-paste=""
          @click="pasteable.paste()"
        >
          {{ pasteable.label.value }}
        </craft-button>
      </div>

      <div slot="footer">
        <div class="element-selection-footer"></div>
      </div>
    </component>
  </div>
</template>

<style lang="scss" scoped>
  .element-selection-footer {
    display: flex;
    justify-content: space-between;

    &:not(:empty) {
      margin-block-start: var(--c-spacing-sm);
    }
  }
</style>
