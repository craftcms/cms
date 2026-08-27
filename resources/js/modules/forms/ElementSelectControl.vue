<script setup lang="ts">
  import '@craftcms/ui/components/chip/chip';
  import {t} from '@craftcms/ui';
  import {computed, reactive, ref, useId} from 'vue';
  import ActionMenu from '@/common/components/ActionMenu.vue';
  import ElementList from '@/modules/elements/components/ElementList.vue';
  import {useElementList} from '@/modules/elements/composables/useElementList';
  import {
    createElementActionMenu,
    type ElementActionMenuItem,
  } from '@/modules/elements/composables/useElementActionMenu';
  import type {ActionItem} from '@/common/types';
  import type {
    FormChangeKind,
    FormControlPayload,
    FormProperties,
  } from './types';
  import {inputName} from './runtime';
  import AssetUploadButton from '@/pages/assets/AssetUploadButton.vue';

  /**
   * TODO: Extract the element-select markup into a reusable Vue component
   * equivalent to `_includes/forms/elementSelect.twig`.
   */
  type ElementPresentation = {
    id: number;
    label: string;
    siteId?: number | string | null;
    /**
     * What the element itself permits, decided server-side. Absent for elements
     * learned from the selector modal, which only reports the common six —
     * those chips get the field's own actions until the next server render
     * fills the rest in.
     */
    url?: string | null;
    canEdit?: boolean;
    canCopy?: boolean;
    draftId?: number | null;
    revisionId?: number | null;
    /**
     * The chip's status dot, already resolved to a fill and a label — which
     * status definitions exist, and what they're called, is an element-type
     * concern. `null` for types that don't show one.
     */
    status?: {fill: string; label: string; draft: boolean} | null;
    /**
     * The element's own action menu, as the server describes it — including the
     * extras an element type adds (an asset's Preview file, Download, Show in
     * folder, Open in Image Editor).
     */
    actions?: ElementActionMenuItem[];
    /**
     * View-mode extras, rendered server-side for the active mode only — the
     * control asks for card parts in card modes and a thumbnail in thumbs mode,
     * so a list field pays for neither.
     */
    cardAttributes?: Record<
      string,
      string | number | boolean | null | undefined
    >;
    cardHeaderHtml?: string;
    cardContentHtml?: string;
    cardFooterHtml?: string;
    thumbHtml?: string;
  };

  /** Mirrors `ElementSelect::viewModes()`, itself a mirror of the field setting. */
  type ElementSelectViewMode =
    | 'list'
    | 'list-inline'
    | 'thumbs'
    | 'cards'
    | 'cards-grid';
  type ElementSelectElement =
    | 'craft-element-select-input'
    | 'craft-entry-select-input'
    | 'craft-asset-select-input';
  type ElementSelectProps = {
    elementType: string;
    customElement: ElementSelectElement;
    elements: ElementPresentation[];
    sources: string[] | null;
    criteria: FormProperties;
    selectionLabel: string;
    limit: number | null;
    showSiteMenu: boolean;
    viewMode: ElementSelectViewMode;
    /** Lower-cased, for "Edit entry" / "Copy entry". */
    elementDisplayName: string;
    /** `AssetSelect` only; absent for every other element type. */
    canUpload?: boolean;
    uploadFolderId?: number | null;
    fsType?: string | null;
  };
  const props = defineProps<{
    control: FormControlPayload<ElementSelectProps>;
    value: Array<number | string>;
    editable: boolean;
  }>();
  const emit = defineEmits<{
    (event: 'update:value', value: number[], kind: FormChangeKind): void;
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

  const ids = computed(() => props.value.map(elementId));

  /** One relation can't be reordered, and a read-only field can't be either. */
  const sortable = computed(() => props.editable && ids.value.length > 1);

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
   * index — the anchor is the last individually-toggled chip, and shift selects
   * the inclusive range between it and the clicked one.
   *
   * Selection is the input to bulk removal: "Remove" on a selected chip removes
   * the whole selection, which is what Craft 5's `defineElementActions` does via
   * `elementSelect.isSelected()`.
   */
  /**
   * Selection is only worth offering when more than one element can be related —
   * a single-relation field has nothing to bulk-act on. A `null` limit is
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
    props.value.map((selectedValue) => ({
      ...presentation(selectedValue),
      id: elementId(selectedValue),
    }))
  );

  const list = useElementList({
    ids,
    viewMode,
    selectable,
    readOnly: () => !props.editable,
    // Chips behave like a file list: a plain click collapses the selection to the
    // one clicked, and ctrl/cmd adds to it.
    click: 'replace',
  });

  const {
    selectedIds,
    hasSelection,
    allSelected,
    someSelected,
    isSelected,
    setChecked,
    toggleAll,
    clear: clearSelection,
    handleClick: selectChip,
    prune: pruneSelection,
  } = list.selection;

  /**
   * Built here rather than inline: the ICU braces in the plural form read as
   * nested interpolation to the template compiler.
   */
  const selectionCountLabel = computed(() =>
    t('{num, number} {num, plural, =1{item} other{items}} selected', {
      num: selectedIds.value.length,
    })
  );

  /**
   * `craft-checkbox` dispatches from the host rather than an inner `<input>`, so
   * the checked state has to be read off the target as a plain property.
   */
  function checkboxValue(event: Event): boolean {
    return Boolean((event.target as {checked?: boolean} | null)?.checked);
  }

  // ───────────────────────────── reordering ─────────────────────────────

  function reorder(startIndex: number, finishIndex: number): void {
    const next = [...ids.value];
    const [moved] = next.splice(startIndex, 1);

    if (moved === undefined) {
      return;
    }

    next.splice(finishIndex, 0, moved);
    emit('update:value', next, 'discrete');
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
        sources: props.control.props.sources,
        criteria: props.control.props.criteria as Record<string, unknown>,
        showSiteMenu: props.control.props.showSiteMenu,
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

          const next =
            replacing === null
              ? [...ids.value, ...chosen]
              : ids.value.flatMap((id) => (id === replacing ? chosen : [id]));

          emit(
            'update:value',
            limit.value === null
              ? next
              : next.slice(0, Math.max(limit.value, 1)),
            'discrete'
          );
        },
      }
    );

    modal.on('close', () => modal.destroy());
    await modal.show();
  }

  // ───────────────────────────── chip actions ─────────────────────────────

  function removeIds(remove: Set<number>): void {
    const next = ids.value.filter((id) => !remove.has(id));
    pruneSelection(next);
    emit('update:value', next, 'discrete');
  }

  /** One dispatcher for every chip's menu, rather than one per element. */
  const toActionItems = createElementActionMenu();

  /**
   * The element's own actions.
   *
   * The server describes these per element, so an element type's extras arrive
   * without the field knowing what any of them are. Elements picked since the
   * last render have no descriptors yet — the selector reports only the common
   * few — so those fall back to what can be derived client-side until the next
   * server render fills them in.
   */
  function elementActions(value: number | string): ActionItem[] {
    const element = presentation(value);
    const type = props.control.props.elementDisplayName;

    if (element.actions?.length) {
      return toActionItems(element.actions);
    }

    const actions: ActionItem[] = [];

    if (element.url) {
      actions.push({
        icon: 'share',
        label: t('View in a new tab'),
        onClick: () => window.open(element.url!, '_blank', 'noopener'),
      });
    }

    if (element.canCopy) {
      actions.push({
        icon: 'clone-dashed',
        label: t('Copy {type}', {type}),
        // `Craft.cp` owns the clipboard, including its confirmation toast.
        onClick: () => Craft.cp?.copyElements?.([copyDescriptor(value)]),
      });
    }

    return actions;
  }

  /** What `Craft.cp.copyElements()` wants for one element. */
  type CopyDescriptor = {
    type: string;
    id: string | number;
    siteId?: number | null;
    draftId?: number | null;
    revisionId?: number | null;
  };

  function copyDescriptor(value: number | string): CopyDescriptor {
    const element = presentation(value);

    return {
      type: props.control.props.elementType,
      id: element.id,
      siteId: element.siteId === null ? null : Number(element.siteId),
      draftId: element.draftId ?? null,
      revisionId: element.revisionId ?? null,
    };
  }

  /**
   * The selection's own actions, for the toolbar menu.
   *
   * Copy covers only the elements the server said may be copied — a revision or
   * one the user can't read is skipped rather than silently failing — so the item
   * is offered whenever any of the selection is copyable. Remove needs an editable
   * field: a read-only one can show and copy what it can't detach.
   */
  const bulkActions = computed<ActionItem[]>(() => {
    if (!hasSelection.value) {
      return [];
    }

    const actions: ActionItem[] = [];
    const copyable = selectedIds.value.filter((id) => presentation(id).canCopy);

    if (copyable.length) {
      actions.push({
        icon: 'clone-dashed',
        label: t('Copy selected'),
        onClick: () => Craft.cp?.copyElements?.(copyable.map(copyDescriptor)),
      });
    }

    if (props.editable) {
      actions.push({
        icon: 'remove',
        label: t('Remove selected'),
        variant: 'danger',
        onClick: () => removeIds(new Set(selectedIds.value)),
      });
    }

    return actions;
  });

  /**
   * Opening the element's editor.
   *
   * The field's own affordance rather than one of the element's: the server's
   * descriptors never include an edit item, because they're written for the
   * editor itself, where you're already editing the thing.
   */
  const editLabel = computed(() =>
    t('Edit {type}', {type: props.control.props.elementDisplayName})
  );

  function canEditElement(value: number | string): boolean {
    return presentation(value).canEdit === true;
  }

  function editAction(value: number | string): ActionItem | null {
    if (!canEditElement(value)) {
      return null;
    }

    return {
      icon: 'edit',
      label: editLabel.value,
      onClick: () => openEditor(value),
    };
  }

  /** The menu for a chip or card, with the edit item where it belongs. */
  function menuActions(value: number | string, index: number): ActionItem[] {
    return chipActions(value, index, {withEdit: !list.isCards.value});
  }

  /**
   * Joins menu sections with a separator between each.
   *
   * Empty sections drop out, so a menu never opens or closes on a rule, and two
   * adjacent empty sections can't produce a double one.
   */
  function withSeparators(sections: ActionItem[][]): ActionItem[] {
    return sections
      .filter((section) => section.length > 0)
      .flatMap((section, index) =>
        index === 0 ? section : [{type: 'hr'} as ActionItem, ...section]
      );
  }

  /**
   * A chip or card's menu, in three sections: what the element itself offers,
   * what the field offers for it, and detaching it.
   *
   * This is the place to regroup the menu — move an item between the arrays, or
   * merge two sections into one, and the separators follow.
   *
   * `withEdit` is off in the card modes, where the pencil button beside the menu
   * already opens the editor.
   */
  function chipActions(
    value: number | string,
    index: number,
    {withEdit = true}: {withEdit?: boolean} = {}
  ): ActionItem[] {
    const id = elementId(value);

    // What the element itself offers — server-described, so an element type's
    // own actions (an asset's Preview file, Download, …) arrive here.
    const elementSection = elementActions(value);

    // What the field offers for that element.
    const fieldSection: ActionItem[] = [];
    const edit = withEdit ? editAction(value) : null;

    if (edit) {
      fieldSection.push(edit);
    }

    // Detaching it from the field. Separated because it's the one that changes
    // the field's value.
    const removeSection: ActionItem[] = [];

    // A read-only field still offers the element's own actions and its editor —
    // you can view, edit and copy what you can't detach.
    if (props.editable) {
      if (props.control.props.elementType) {
        fieldSection.push({
          icon: 'arrows-rotate',
          label: t('Replace'),
          onClick: () => void openSelector(id),
        });
      }

      removeSection.push({
        icon: 'remove',
        label: t('Remove'),
        variant: 'danger',
        onClick: () =>
          removeIds(
            isSelected(id) ? new Set(selectedIds.value) : new Set([id])
          ),
      });
    }

    return withSeparators([elementSection, fieldSection, removeSection]);
  }

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

    emit(
      'update:value',
      limit === null ? next : next.slice(-Math.max(limit, 1)),
      'discrete'
    );
  }

  /**
   * Double-click opens the element's editor slideout, as it does on chips
   * everywhere else in the CP (Craft 5 binds the same gesture in
   * `BaseElementSelectInput`, via `createElementEditor`).
   */
  function openEditor(value: number | string): void {
    const element = presentation(value);

    Craft.createElementEditor(props.control.props.elementType, {
      elementId: element.id,
      siteId: element.siteId ?? null,
    });
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
      <div v-if="editable && !atLimit" class="flex gap-2 py-2" slot="header">
        <craft-button
          ref="addButton"
          type="button"
          variant="dashed"
          icon="plus"
          data-element-select-add=""
          :disabled="atLimit || undefined"
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
          :fs-type="control.props.fsType ?? undefined"
          :drop-zone="dropZone"
          :reload-on-complete="false"
          :disabled="!showUpload"
          @uploaded="attachUploaded"
        />
      </div>

      <div
        class="border border-(--c-color-neutral-border-quiet) rounded-sm inset-shadow-sm bg-(--c-color-neutral-fill-quiet) relative"
        v-if="value.length > 0"
      >
        <!--
          Selection toolbar. The whole bar is selection-only, so a field that
          can't be selected (a single relation) has no use for it.
        -->
        <div
          v-if="selectable"
          class="flex justify-between items-center border-b border-b-(--c-color-neutral-border-quiet) p-(--c-spacing-sm) shadow-sm"
        >
          <div class="flex items-center gap-2">
            <craft-checkbox
              label-sr-only
              .checked="allSelected"
              .indeterminate="someSelected"
              @model-value-changed="toggleAll(checkboxValue($event))"
            >
              <label slot="label">{{ t('Select all') }}</label>
            </craft-checkbox>

            <template v-if="hasSelection">
              <div class="text-xs font-bold">{{ selectionCountLabel }}</div>
              <craft-button
                type="button"
                size="small"
                variant="plain"
                @click="clearSelection()"
              >
                {{ t('Clear selection') }}
              </craft-button>
            </template>
          </div>

          <ActionMenu
            v-if="hasSelection && bulkActions.length"
            :actions="bulkActions"
          >
            <template #invoker="{attributes}">
              <craft-button type="button" size="small" v-bind="attributes">
                {{ t('Actions') }}
                <craft-icon name="chevron-down" slot="suffix"></craft-icon>
              </craft-button>
            </template>
          </ActionMenu>
        </div>
        <div class="p-(--c-spacing-md)">
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
                :name="`${inputName(control.path)}[]`"
                :value="String(element.id)"
              />
            </template>
            <!--
              Cards get a pencil straight to the editor, so their menu leaves
              the edit item out; a chip keeps it in the menu.
            -->
            <template #actions="{element, index}">
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
                v-if="menuActions(element.id, index).length"
                :actions="menuActions(element.id, index)"
              />
            </template>
          </ElementList>
        </div>
        <div class="absolute inset-e-1 inset-be-1" v-if="limit && limit > 1">
          <craft-badge size="small" no-prefix
            >{{ value.length }}/{{ limit }}</craft-badge
          >
        </div>
      </div>

      <div slot="footer">
        <div class="flex justify-between mt-1"></div>
      </div>
    </component>
  </div>
</template>
