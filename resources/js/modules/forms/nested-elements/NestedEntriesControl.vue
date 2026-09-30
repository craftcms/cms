<script setup lang="ts">
  import {actionClient, t} from '@craftcms/ui';
  import axios from 'axios';
  import {useEventListener} from '@vueuse/core';
  import {
    computed,
    inject,
    nextTick,
    onBeforeUnmount,
    ref,
    shallowRef,
    watch,
  } from 'vue';
  import {useAnnouncer} from '@/common/composables/useAnnouncer';
  import {useSelectable} from '@/common/composables/useSelectable';
  import {canUseVueSlideout, openSlideout} from '@/common/slideouts';
  import CreateElementController from '@/actions/CraftCms/Cms/Http/Controllers/Elements/CreateElementController';
  import EditElementController from '@/actions/CraftCms/Cms/Http/Controllers/Elements/EditElementController';
  import NestedElementsController from '@/actions/CraftCms/Cms/Http/Controllers/NestedElementsController';
  import {duplicate as duplicateElement} from '@/actions/CraftCms/Cms/Http/Controllers/Elements/DuplicateElementController';
  import {craft} from '@/modules/matrix/interop';
  import {useCopiedElements} from '@/modules/matrix/copied-elements';
  import {
    NestedOwnerEditorKey,
    type NestedOwnerContext,
  } from '@/modules/elements/nested-owner';
  import type {FormControlPayload, FormValue} from '../types';
  import {inputName} from '../runtime';
  import NestedEntriesCards from './NestedEntriesCards.vue';
  import {
    entryCan,
    markInvalidEntries,
    type NestedEntriesProps,
    type NestedEntry,
    type NestedEntryCapability,
  } from './nested-entries';

  interface LegacyElementEditorSlideout {
    on(
      event: string,
      callback: (event: {response?: {data?: {draft?: boolean}}}) => void
    ): void;
    elementEditor: {
      settings: {
        draftId?: number | null;
        saveParams?: Record<string, unknown> | null;
      };
      saveDraft(): Promise<void>;
    };
  }

  const props = defineProps<{
    control: FormControlPayload<NestedEntriesProps>;
    value: FormValue;
    editable: boolean;
  }>();
  const emit = defineEmits<{
    (event: 'update:value', value: FormValue, kind: 'discrete'): void;
  }>();
  const owner = inject(NestedOwnerEditorKey, null);
  const controlElement = ref<HTMLElement | null>(null);
  const busy = shallowRef(false);
  const error = shallowRef('');
  const invalidEntryIds = ref<number[]>([]);
  const preparedOwner = shallowRef<NestedOwnerContext | null>(null);
  let focusEntryId: number | null = null;
  let focusEntryIndex = -1;

  const manager = computed(() => props.control.props.manager);
  const cards = computed(() =>
    markInvalidEntries(props.control.props.cards, invalidEntryIds.value)
  );
  const selection = useSelectable({
    ids: () => cards.value.map((entry) => entry.id),
    enabled: () => props.editable,
    readOnly: () => !props.editable || busy.value,
  });
  watch(cards, () => selection.prune());
  const copied = useCopiedElements();
  const {announce} = useAnnouncer();

  function canAdd(count: number): boolean {
    return Boolean(
      props.editable &&
      manager.value?.canCreate &&
      (!manager.value.maxElements ||
        cards.value.length + count <= manager.value.maxElements)
    );
  }

  const canPaste = computed(() => {
    const pasteableData = manager.value?.pasteableData;
    const candidates = copied.value;

    return (
      Boolean(manager.value?.canPaste) &&
      candidates.length > 0 &&
      canAdd(candidates.length) &&
      candidates.every(
        (candidate) =>
          candidate.type === manager.value?.elementType &&
          (!pasteableData ||
            pasteableData.values.includes(
              (candidate.data as Record<string, string | number> | undefined)?.[
                pasteableData.attribute
              ] ?? ''
            ))
      )
    );
  });
  const canReorder = computed(() =>
    Boolean(props.editable && manager.value?.sortable)
  );

  function markModified(): void {
    emit('update:value', '*', 'discrete');
  }

  function setError(cause: unknown): void {
    error.value = axios.isAxiosError<{message?: string}>(cause)
      ? (cause.response?.data?.message ?? t('Could not update nested entries.'))
      : cause instanceof Error
        ? cause.message
        : t('Could not update nested entries.');
  }

  async function refreshOwner(): Promise<void> {
    if (!owner?.refresh) {
      return;
    }

    await owner.refresh();
    await nextTick();
  }

  /**
   * A nested editor autosaves as the user types. Its cards only need to catch
   * up once the typing settles, not on every draft save.
   */
  let childDraftRefresh: ReturnType<typeof setTimeout> | undefined;
  onBeforeUnmount(() => clearTimeout(childDraftRefresh));

  function onChildSaved(draft = false): void {
    window.Craft?.Preview?.refresh();
    clearTimeout(childDraftRefresh);

    if (draft) {
      childDraftRefresh = setTimeout(
        () => void refreshAfterChildSave(true),
        1000
      );

      return;
    }

    void refreshAfterChildSave();
  }

  async function refreshAfterChildSave(draft = false): Promise<void> {
    try {
      if (!draft) {
        markModified();
        await nextTick();
      }

      await refreshOwner();

      if (
        document.activeElement === document.body ||
        document.activeElement === controlElement.value
      ) {
        restoreEntryFocus();
      }
    } catch (cause) {
      setError(cause);
    }
  }

  function ownerParams(ownerId: number) {
    return {
      ownerElementType: manager.value!.ownerElementType,
      ownerId,
      ownerSiteId: manager.value!.ownerSiteId,
      attribute: manager.value!.attribute,
    };
  }

  function findEntries(ids: number[]): NestedEntry[] {
    return cards.value.filter((entry) => ids.includes(entry.id));
  }

  function openLegacyEditor(
    settings: Record<string, unknown>,
    onBeforeSubmit?: (slideout: LegacyElementEditorSlideout) => Promise<void>
  ): LegacyElementEditorSlideout {
    const slideout = Craft.createElementEditor(manager.value!.elementType, {
      ...settings,
      onBeforeSubmit: () => onBeforeSubmit?.(slideout) ?? Promise.resolve(),
    }) as unknown as LegacyElementEditorSlideout;

    slideout.on('submit', ({response}) => {
      onChildSaved(Boolean(response?.data?.draft));
    });

    return slideout;
  }

  function allCan(ids: number[], capability: NestedEntryCapability): boolean {
    const found = findEntries(ids);

    return (
      ids.length > 0 &&
      found.length === ids.length &&
      found.every((entry) => entryCan(entry, capability))
    );
  }

  async function prepare(): Promise<number> {
    if (!props.editable || !owner || !manager.value) {
      throw new Error(t('This field cannot be edited here.'));
    }

    markModified();
    await nextTick();
    const context = await owner.prepare(props.control.path);

    if (!context) {
      throw new Error(
        t('Could not save the owner draft. No nested entries were changed.')
      );
    }

    if (
      context.requiresDerivative &&
      !context.ownerIsUnpublishedDraft &&
      !context.ownerIsDerivative &&
      !context.ownerIsInDerivativeTree
    ) {
      throw new Error(
        t('Could not prepare the owner draft. No nested entries were changed.')
      );
    }

    preparedOwner.value = context;

    return context.ownerId;
  }

  async function withOwner(
    operation: (ownerId: number) => Promise<void>
  ): Promise<void> {
    if (busy.value) {
      return;
    }

    busy.value = true;
    error.value = '';
    announce(t('Loading'));

    try {
      await operation(await prepare());
    } catch (cause) {
      setError(cause);
    } finally {
      busy.value = false;
      announce(t('Loading complete'));
    }
  }

  async function mutate(
    operation: (ownerId: number) => Promise<void>
  ): Promise<void> {
    await withOwner(async (ownerId) => {
      try {
        await operation(ownerId);
      } finally {
        await refreshOwner();
        window.Craft?.Preview?.refresh();
      }
    });
  }

  async function create(
    attributes: Record<string, string | number> = {},
    opener?: HTMLElement
  ): Promise<void> {
    await mutate(async (ownerId) => {
      const {data} = await actionClient.post(CreateElementController.url(), {
        elementType: manager.value!.elementType,
        ownerId,
        fieldId: manager.value!.fieldId,
        siteId: manager.value!.ownerSiteId,
        ...attributes,
      });
      if (!canUseVueSlideout()) {
        opener?.focus();
        openLegacyEditor({
          elementId: data.element.id,
          siteId: data.element.siteId,
          fieldId: manager.value!.fieldId,
          ownerId,
          draftId: data.element.draftId,
          params: {fresh: 1},
        });

        return;
      }

      const editUrl = EditElementController.url(undefined, {
        query: {
          elementId: data.element.id,
          siteId: data.element.siteId,
          fieldId: manager.value!.fieldId,
          ownerId,
          draftId: data.element.draftId,
          fresh: 1,
        },
      });
      await openSlideout(editUrl, {
        opener,
        onSaved: ({draft} = {}) => onChildSaved(draft),
      });
    });
  }

  async function remove(ids: number[]): Promise<void> {
    if (
      !allCan(ids, 'deletable') ||
      !window.confirm(
        ids.length === 1
          ? (manager.value?.deleteConfirmationMessage ??
              t('Are you sure you want to delete this entry?'))
          : (manager.value?.bulkDeleteConfirmationMessage ??
              t('Are you sure you want to delete the selected entries?'))
      )
    ) {
      return;
    }

    await mutate(async (ownerId) => {
      for (const elementId of ids) {
        const {data} = await actionClient.post(
          NestedElementsController.destroy.url(),
          {...ownerParams(ownerId), elementId}
        );
        window.Craft?.cp?.displayNotice(data.message);
      }
      selection.clear();
    });
    controlElement.value
      ?.querySelector<HTMLElement>('[data-create-entry]')
      ?.focus();
  }

  function copy(ids: number[]): void {
    if (!manager.value || !allCan(ids, 'copyable')) {
      return;
    }

    craft().cp.copyElements(
      findEntries(ids).map((entry) => ({
        type: manager.value!.elementType,
        id: entry.id,
        siteId: entry.siteId,
        ownerId: entry.ownerId ?? undefined,
        fieldId: manager.value!.fieldId,
        draftId: entry.cardAttributes?.data?.['draft-id'],
        revisionId: entry.cardAttributes?.data?.['revision-id'],
        data: {entryTypeId: entry.entryTypeId},
      }))
    );
  }

  async function duplicate(ids: number[]): Promise<void> {
    if (!allCan(ids, 'duplicatable') || !canAdd(ids.length)) {
      return;
    }

    await mutate(async (ownerId) => {
      const indexOf = (id: number) =>
        cards.value.findIndex((entry) => entry.id === id);
      const orderedIds = [...ids].sort((a, b) => indexOf(b) - indexOf(a));

      for (const elementId of orderedIds) {
        const {data} = await actionClient.post(duplicateElement.url(), {
          elementType: manager.value!.elementType,
          ownerId,
          siteId: manager.value!.ownerSiteId,
          fieldId: manager.value!.fieldId,
          elementId,
        });
        if (data.message) {
          window.Craft?.cp?.displayNotice(data.message);
        }

        const sourceIndex = indexOf(elementId);
        if (!canReorder.value || sourceIndex < 0) {
          continue;
        }

        await actionClient.post(NestedElementsController.reorder.url(), {
          ...ownerParams(ownerId),
          elementIds: [data.element.id],
          offset: sourceIndex + 1,
        });
      }
    });
  }

  async function paste(beforeId?: number): Promise<void> {
    if (!canPaste.value) {
      return;
    }

    await mutate(async (ownerId) => {
      const pasted = await craft().cp.pasteElements({
        primaryOwnerId: ownerId,
        ownerId,
        fieldId: manager.value!.fieldId,
        siteId: manager.value!.ownerSiteId,
      });
      const beforeIndex = cards.value.findIndex(
        (entry) => entry.id === beforeId
      );
      if (beforeIndex >= 0 && pasted.length && canReorder.value) {
        await actionClient.post(NestedElementsController.reorder.url(), {
          ...ownerParams(ownerId),
          elementIds: pasted.map((entry) => entry.id),
          offset: beforeIndex,
        });
      }
      focusEntryId = pasted[0]?.id ?? null;
      focusEntryIndex = Math.max(0, beforeIndex);
    });
    restoreEntryFocus();
  }

  async function reorder(
    from: number,
    to: number,
    group = true
  ): Promise<void> {
    const moved = cards.value[from]?.id;
    if (
      !canReorder.value ||
      from === to ||
      moved === undefined ||
      !cards.value[to]
    ) {
      return;
    }

    const selected =
      group && selection.isSelected(moved)
        ? cards.value
            .filter((entry) => selection.isSelected(entry.id))
            .map((entry) => entry.id)
        : [moved];
    const target = cards.value[to]!;
    const remaining = cards.value.filter(
      (entry) => !selected.includes(entry.id)
    );
    const targetIndex = remaining.findIndex((entry) => entry.id === target.id);
    if (targetIndex < 0) {
      return;
    }

    focusEntryId = moved;
    await reorderIds(selected, targetIndex + (to > from ? 1 : 0));
    restoreEntryFocus();
  }

  async function reorderIds(ids: number[], offset: number): Promise<boolean> {
    if (!canReorder.value) {
      return false;
    }

    let reordered = false;
    await mutate(async (ownerId) => {
      const {data} = await actionClient.post(
        NestedElementsController.reorder.url(),
        {...ownerParams(ownerId), elementIds: ids, offset}
      );
      reordered = true;
      window.Craft?.cp?.displayNotice(data.message);
      window.Craft?.broadcaster?.postMessage({
        pageId: window.Craft.pageId,
        event: 'reorderNestedElements',
        canonicalId: preparedOwner.value?.canonicalId,
        draftId: preparedOwner.value?.draftId,
        isProvisionalDraft: preparedOwner.value?.isProvisionalDraft,
        ownerId,
        attribute: manager.value!.attribute,
        elementType: manager.value!.elementType,
        elementIds: ids,
      });
    });

    return reordered;
  }

  async function edit(entry: NestedEntry): Promise<void> {
    if (!entry.editUrl) {
      return;
    }

    focusEntryId = entry.id;
    focusEntryIndex = cards.value.findIndex(
      (candidate) => candidate.id === entry.id
    );

    const prepareNestedOwner = props.editable
      ? async () => {
          if (
            !entry.ownerIsCanonical ||
            entry.isUnpublishedDraft ||
            manager.value?.ownerIsUnpublishedDraft
          ) {
            return undefined;
          }
          const ownerId = await prepare();

          return (preparedOwner.value?.ownerIsDerivative ||
            preparedOwner.value?.ownerIsInDerivativeTree) &&
            !preparedOwner.value.ownerIsUnpublishedDraft
            ? ownerId
            : undefined;
        }
      : undefined;

    if (!canUseVueSlideout()) {
      controlElement.value?.focus();
      const url = new URL(entry.editUrl, window.location.origin);
      openLegacyEditor(
        Object.fromEntries(url.searchParams),
        prepareNestedOwner
          ? async (slideout) => {
              const ownerId = await prepareNestedOwner();
              if (!ownerId) {
                return;
              }

              if (!slideout.elementEditor.settings.draftId) {
                await slideout.elementEditor.saveDraft();
              }
              if (!slideout.elementEditor.settings.draftId) {
                throw new Error(t('Could not save the nested entry draft.'));
              }

              slideout.elementEditor.settings.saveParams = {
                ...slideout.elementEditor.settings.saveParams,
                action: 'elements/save-nested-element-for-derivative',
                newOwnerId: ownerId,
              };
            }
          : undefined
      );

      return;
    }

    await openSlideout(entry.editUrl, {
      opener: controlElement.value,
      prepareNestedOwner,
      onSaved: ({draft} = {}) => onChildSaved(draft),
    });
  }

  function restoreEntryFocus(): void {
    const container = controlElement.value;
    if (!container) {
      return;
    }

    const items = container.querySelectorAll<HTMLElement>('[data-nested-id]');
    const target =
      [...items].find(
        (item) => Number(item.dataset.nestedId) === focusEntryId
      ) ?? items.item(focusEntryIndex);
    const entry = cards.value.find(
      (item) => item.id === Number(target?.dataset.nestedId)
    );
    const link = [
      ...(target?.querySelectorAll<HTMLAnchorElement>('a[href]') ?? []),
    ].find((candidate) => candidate.getAttribute('href') === entry?.editUrl);
    const button = target?.querySelector<HTMLElement>(
      '[data-edit-entry], craft-action-menu craft-button[slot="invoker"]'
    );
    (link ?? button)?.focus();
  }

  function onControlFocus(): void {
    void nextTick().then(restoreEntryFocus);
  }

  useEventListener(window, 'craft:nested-validation', (event: Event) => {
    if (
      event.target instanceof Element &&
      event.target.contains(controlElement.value)
    ) {
      invalidEntryIds.value = (
        event as CustomEvent<{ids: number[]}>
      ).detail.ids;
    }
  });

  useEventListener(
    () => window.Craft?.broadcaster,
    'message',
    (event: MessageEvent) => {
      const data = event.data;
      if (
        data.event === 'reorderNestedElements' &&
        data.ownerId ===
          (preparedOwner.value?.ownerId ?? manager.value?.ownerId) &&
        data.attribute === manager.value?.attribute &&
        !busy.value
      ) {
        void refreshOwner().catch(setError);
      }
    }
  );
</script>

<template>
  <div
    ref="controlElement"
    class="nested-entries"
    tabindex="-1"
    @focus.self="onControlFocus"
  >
    <input
      v-if="editable && value === '*'"
      type="hidden"
      :name="inputName(control.path)"
      value="*"
    />
    <p v-if="control.props.unavailableMessage">
      {{ control.props.unavailableMessage }}
    </p>
    <div v-if="error" role="alert">{{ error }}</div>
    <NestedEntriesCards
      v-if="!control.props.unavailableMessage"
      :cards="cards"
      :manager="manager"
      :selection="selection"
      :single-column="control.props.viewMode === 'cards'"
      :editable="editable"
      :busy="busy"
      :can-add="canAdd"
      :can-paste="canPaste"
      :can-reorder="canReorder"
      @edit="edit"
      @duplicate="duplicate"
      @copy="copy"
      @delete="remove"
      @paste="paste"
      @reorder="reorder"
      @move="(from, to) => reorder(from, to, false)"
      @create="create"
    />
  </div>
</template>

<style scoped>
  .nested-entries {
    min-width: 0;
    max-width: 100%;
  }
</style>
