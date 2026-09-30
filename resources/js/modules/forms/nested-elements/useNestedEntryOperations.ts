import {actionClient, t} from '@craftcms/ui';
import {runAction} from '@craftcms/ui/actions.mjs';
import {useEventListener, useTimeoutFn} from '@vueuse/core';
import {computed, nextTick, shallowRef, watch, type Ref} from 'vue';
import CreateElementController from '@/actions/CraftCms/Cms/Http/Controllers/Elements/CreateElementController';
import EditElementController from '@/actions/CraftCms/Cms/Http/Controllers/Elements/EditElementController';
import NestedElementsController from '@/actions/CraftCms/Cms/Http/Controllers/NestedElementsController';
import PerformElementActionController from '@/actions/CraftCms/Cms/Http/Controllers/Elements/PerformElementActionController';
import {useAnnouncer} from '@/common/composables/useAnnouncer';
import type {Selectable} from '@/common/composables/useSelectable';
import {canUseVueSlideout, openSlideout} from '@/common/slideouts';
import type {SlideoutSaveResult} from '@/common/slideouts';
import type {
  NestedOwnerContext,
  NestedOwnerEditor,
} from '@/modules/elements/nested-owner';
import {copyElements} from '@/modules/elements/index/copy-elements';
import {
  DELETE_ACTION,
  DUPLICATE_ACTION,
  isCopyAction,
} from '@/modules/elements/index/element-action-identity';
import {
  selectionAllows,
  type BulkActionItem,
  type ElementActionSelection,
} from '@/modules/elements/types/actions';
import {useCopiedElements} from '@/modules/matrix/copied-elements';
import {craft} from '@/modules/matrix/interop';
import {
  canOpenEntry,
  isPasteable,
  nestedEntriesErrorMessage,
  nestedEntryReorderOffset,
  nestedOwnerParams,
  nestedPasteLabel,
  type NestedEntriesManager,
  type NestedEntry,
} from './nested-entries';

const QUICK_EDIT_CONTROL_SELECTOR = [
  'a[href]',
  'button',
  'input',
  'select',
  'textarea',
  'label',
  '[role="button"]',
  '[role="link"]',
  '[role="menuitem"]',
  '[contenteditable="true"]',
  '.move',
  'craft-button',
  'craft-checkbox',
  'craft-action-menu',
  'craft-reorder-button',
].join(', ');

export interface NestedEntryQuickEdit {
  readonly longPress: true;
  openRow(entry: NestedEntry, opener?: HTMLElement | null): void;
  openById(id: string | number, opener?: HTMLElement | null): void;
  onDblClick(event: MouseEvent): void;
  onPrimaryLink(event: MouseEvent): void;
  suppressPrimaryLinkVisit(event: MouseEvent): void;
}

export interface LegacyElementEditorSlideout {
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

export interface UseNestedEntryOperationsOptions {
  entries: Readonly<Ref<NestedEntry[]>>;
  offset: Readonly<Ref<number>>;
  canReorder: Readonly<Ref<boolean>>;
  selection: Selectable<number>;
  count: Readonly<Ref<number | null>>;
  refresh(reset?: boolean): Promise<void>;
  manager: Readonly<Ref<NestedEntriesManager | null>>;
  owner: NestedOwnerEditor | null;
  editable: Readonly<Ref<boolean>>;
  controlPath: string[];
  container: Readonly<Ref<HTMLElement | null | undefined>>;
  markModified(): void;
  refreshCreatedEntryOnOpen?: boolean;
  busy?: Ref<boolean>;
  error?: Ref<string>;
  ownerId?: Ref<number | null>;
}

export function useNestedEntryOperations(
  options: UseNestedEntryOperationsOptions
) {
  const busy = options.busy ?? shallowRef(false);
  const error = options.error ?? shallowRef('');
  const ownerId = options.ownerId ?? shallowRef<number | null>(null);
  const preparedOwner = shallowRef<NestedOwnerContext | null>(null);
  const copied = useCopiedElements();
  const {announce} = useAnnouncer();
  let focusEntryId: number | null = null;
  let focusEntryIndex = -1;
  let restoreFocusAfterLoad = false;
  let resetAfterChildSave = false;
  let restoreFocusAfterChildSave: (() => void) | undefined;

  function canAdd(count: number): boolean {
    const manager = options.manager.value;
    const total = options.count.value;

    return Boolean(
      options.editable.value &&
      manager?.canCreate &&
      total !== null &&
      (!manager.maxElements || total + count <= manager.maxElements)
    );
  }
  const canPaste = computed(() => {
    const manager = options.manager.value;
    if (!manager) {
      return false;
    }

    return isPasteable(copied.value, {
      elementType: manager.elementType,
      entryTypeIds: manager.pasteableEntryTypeIds,
      room: manager.canPaste && canAdd(copied.value.length),
    });
  });
  const pasteButtonLabel = computed(() =>
    nestedPasteLabel(options.manager.value?.elementType, copied.value.length)
  );

  function pasteActionLabel(position: 'above' | 'before'): string {
    return nestedPasteLabel(
      options.manager.value?.elementType,
      copied.value.length,
      position
    );
  }

  function setError(cause: unknown): void {
    error.value = nestedEntriesErrorMessage(
      cause,
      t('Could not update nested entries.')
    );
  }

  async function refreshOwner(): Promise<void> {
    if (!options.owner?.refresh) {
      return;
    }

    await options.owner.refresh();
    await nextTick();
  }

  async function prepare(): Promise<number> {
    if (!options.editable.value || !options.owner || !options.manager.value) {
      throw new Error(t('This field cannot be edited here.'));
    }

    options.markModified();
    await nextTick();
    const context = await options.owner.prepare(options.controlPath);

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
    ownerId.value = context.ownerId;

    return context.ownerId;
  }

  function prepareEdit(
    entry: NestedEntry
  ): (() => Promise<number | undefined>) | undefined {
    if (!options.editable.value) {
      return undefined;
    }

    return async () => {
      if (
        !entry.ownerIsCanonical ||
        entry.isUnpublishedDraft ||
        options.manager.value?.ownerIsUnpublishedDraft
      ) {
        return undefined;
      }

      const preparedOwnerId = await prepare();
      const context = preparedOwner.value;

      return (context?.ownerIsDerivative || context?.ownerIsInDerivativeTree) &&
        !context.ownerIsUnpublishedDraft
        ? preparedOwnerId
        : undefined;
    };
  }

  async function withOwner(
    operation: (preparedOwnerId: number) => Promise<void>
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
    operation: (preparedOwnerId: number) => Promise<void | false>,
    mutationOptions: {reset?: boolean} = {}
  ): Promise<void> {
    await withOwner(async (preparedOwnerId) => {
      let refresh = true;
      let completed = false;

      try {
        refresh = (await operation(preparedOwnerId)) !== false;
        completed = true;
      } finally {
        if (refresh) {
          await refreshOwner();
          await options.refresh(completed && mutationOptions.reset);
          window.Craft?.Preview?.refresh();
        }
      }
    });
  }

  const draftRefresh = useTimeoutFn(
    () => {
      const reset = resetAfterChildSave;
      const restoreFocus = restoreFocusAfterChildSave;
      resetAfterChildSave = false;
      restoreFocusAfterChildSave = undefined;
      void refreshAfterChildSave(true, reset, restoreFocus);
    },
    1000,
    {immediate: false}
  );

  function onChildSaved(
    draft = false,
    reset = false,
    restoreFocus?: () => void
  ): void {
    window.Craft?.Preview?.refresh();
    draftRefresh.stop();
    resetAfterChildSave ||= reset;
    restoreFocusAfterChildSave = restoreFocus ?? restoreFocusAfterChildSave;

    if (draft) {
      draftRefresh.start();

      return;
    }

    const shouldReset = resetAfterChildSave;
    const restore = restoreFocusAfterChildSave;
    resetAfterChildSave = false;
    restoreFocusAfterChildSave = undefined;
    void refreshAfterChildSave(false, shouldReset, restore);
  }

  async function refreshAfterChildSave(
    draft = false,
    reset = false,
    restoreFocus?: () => void
  ): Promise<void> {
    try {
      if (!draft) {
        options.markModified();
        await nextTick();
      }

      await refreshOwner();
      await options.refresh(reset);

      if (
        document.activeElement === document.body ||
        document.activeElement === options.container.value
      ) {
        (restoreFocus ?? restoreEntryFocus)();
      }
    } catch (cause) {
      setError(cause);
    }
  }

  function openLegacyEditor(
    settings: Record<string, unknown>,
    onBeforeSubmit?: (slideout: LegacyElementEditorSlideout) => Promise<void>,
    onSaved: (draft: boolean) => void = onChildSaved
  ): LegacyElementEditorSlideout {
    const slideout = Craft.createElementEditor(
      options.manager.value!.elementType,
      {
        ...settings,
        onBeforeSubmit: () => onBeforeSubmit?.(slideout) ?? Promise.resolve(),
      }
    ) as unknown as LegacyElementEditorSlideout;

    slideout.on('submit', ({response}) => {
      onSaved(Boolean(response?.data?.draft));
    });

    return slideout;
  }

  function rememberEntry(entry: NestedEntry): void {
    focusEntryId = entry.id;
    focusEntryIndex = options.entries.value.findIndex(
      (candidate) => candidate.id === entry.id
    );
  }

  function openEntry(entry: NestedEntry, opener: HTMLElement | null): void {
    if (!canOpenEntry(entry)) {
      return;
    }

    rememberEntry(entry);
    const prepareNestedOwner = prepareEdit(entry);
    const onSaved = ({draft} = {} as SlideoutSaveResult): void => {
      onChildSaved(draft, false, restoreEntryFocus);
    };

    if (canUseVueSlideout()) {
      void openSlideout(entry.editUrl, {
        opener,
        prepareNestedOwner,
        onSaved,
      });

      return;
    }

    (opener ?? options.container.value)?.focus();
    const editUrl = new URL(entry.editUrl, window.location.origin);

    openLegacyEditor(
      Object.fromEntries(editUrl.searchParams),
      prepareNestedOwner
        ? async (slideout) => {
            const preparedOwnerId = await prepareNestedOwner();
            if (!preparedOwnerId) {
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
              newOwnerId: preparedOwnerId,
            };
          }
        : undefined,
      (draft) => onSaved({draft})
    );
  }

  function entryById(id: string | number): NestedEntry | null {
    return (
      options.entries.value.find((entry) => String(entry.id) === String(id)) ??
      null
    );
  }

  function entryFor(target: Element): NestedEntry | null {
    const item = target.closest<HTMLElement>('[data-index-id]');

    return item?.dataset.indexId ? entryById(item.dataset.indexId) : null;
  }

  function openQuickEdit(
    entry: NestedEntry,
    opener: HTMLElement | null = null
  ): void {
    if (options.editable.value) {
      openEntry(entry, opener);
    }
  }

  function openQuickEditById(
    id: string | number,
    opener: HTMLElement | null = null
  ): void {
    const entry = entryById(id);
    if (entry) {
      openQuickEdit(entry, opener);
    }
  }

  function quickEditRow(target: Element): HTMLElement | null {
    return target.closest<HTMLElement>('[data-index-id]');
  }

  function onQuickEditDblClick(event: MouseEvent): void {
    const target = event.target;
    if (!(target instanceof Element)) {
      return;
    }

    const row = quickEditRow(target);
    const entry = row ? entryFor(row) : null;
    const control = target.closest(QUICK_EDIT_CONTROL_SELECTOR);
    if (!row || !entry || (control && row.contains(control))) {
      return;
    }

    event.preventDefault();
    window.getSelection()?.removeAllRanges();

    if ((event.ctrlKey || event.metaKey) && entry.cpEditUrl) {
      window.open(entry.cpEditUrl, '_blank', 'noopener');

      return;
    }

    openQuickEdit(entry, row);
  }

  function primaryQuickEditLink(event: MouseEvent): NestedEntry | null {
    if (
      event.button !== 0 ||
      event.metaKey ||
      event.ctrlKey ||
      event.shiftKey ||
      event.altKey ||
      !(event.target instanceof Element)
    ) {
      return null;
    }

    const anchor = event.target.closest<HTMLAnchorElement>('a[href]');
    const entry = entryFor(event.target);
    if (!anchor || !entry?.editUrl || !options.editable.value) {
      return null;
    }

    return new URL(anchor.href, window.location.origin).href ===
      new URL(entry.editUrl, window.location.origin).href
      ? entry
      : null;
  }

  function suppressPrimaryLinkVisit(event: MouseEvent): void {
    if (primaryQuickEditLink(event)) {
      event.preventDefault();
      event.stopPropagation();
    }
  }

  function onPrimaryLink(event: MouseEvent): void {
    const entry = primaryQuickEditLink(event);
    if (!entry) {
      return;
    }

    event.preventDefault();
    event.stopPropagation();
    openQuickEdit(
      entry,
      event.target instanceof HTMLElement
        ? event.target.closest<HTMLAnchorElement>('a[href]')
        : null
    );
  }

  const quickEdit: NestedEntryQuickEdit = {
    longPress: true,
    openRow: openQuickEdit,
    openById: openQuickEditById,
    onDblClick: onQuickEditDblClick,
    onPrimaryLink,
    suppressPrimaryLinkVisit,
  };

  function findEntries(ids: number[]): NestedEntry[] {
    return options.entries.value.filter((entry) => ids.includes(entry.id));
  }

  function allCan(ids: number[], item: BulkActionItem): boolean {
    const entries = findEntries(ids);

    return (
      ids.length > 0 &&
      entries.length === ids.length &&
      selectionAllows(item, entries)
    );
  }

  async function create(
    attributes: Record<string, string | number> = {},
    opener?: HTMLElement
  ): Promise<void> {
    await mutate(async (preparedOwnerId) => {
      const manager = options.manager.value!;
      const {data} = await actionClient.post(CreateElementController.url(), {
        elementType: manager.elementType,
        ownerId: preparedOwnerId,
        fieldId: manager.fieldId,
        siteId: manager.ownerSiteId,
        ...attributes,
      });
      let firstSave = true;
      const onSaved = (draft: boolean) => {
        onChildSaved(draft, firstSave);
        firstSave = false;
      };

      if (!canUseVueSlideout()) {
        opener?.focus();
        openLegacyEditor(
          {
            elementId: data.element.id,
            siteId: data.element.siteId,
            fieldId: manager.fieldId,
            ownerId: preparedOwnerId,
            draftId: data.element.draftId,
            params: {fresh: 1},
          },
          undefined,
          onSaved
        );

        return options.refreshCreatedEntryOnOpen === false ? false : undefined;
      }

      const editUrl = EditElementController.url(undefined, {
        query: {
          elementId: data.element.id,
          siteId: data.element.siteId,
          fieldId: manager.fieldId,
          ownerId: preparedOwnerId,
          draftId: data.element.draftId,
          fresh: 1,
        },
      });
      await openSlideout(editUrl, {
        opener,
        onSaved: ({draft} = {}) => onSaved(Boolean(draft)),
      });

      return options.refreshCreatedEntryOnOpen === false ? false : undefined;
    });
  }

  async function performElementAction(
    item: BulkActionItem,
    ids: number[],
    trigger?: HTMLElement
  ): Promise<boolean> {
    const manager = options.manager.value;
    if (
      !manager ||
      !item.action ||
      !allCan(ids, item) ||
      (item.key === DUPLICATE_ACTION && !canAdd(ids.length))
    ) {
      return false;
    }

    const action = item.action;

    if (action.type === 'event') {
      if (!isCopyAction(item)) {
        return false;
      }

      const serverElements =
        (action.detail?.elements as ElementActionSelection[] | undefined) ?? [];

      copyElements(manager.elementType, ids, [
        ...serverElements,
        ...findEntries(ids),
      ]);

      return true;
    }

    if (action.type !== 'http') {
      return false;
    }

    let performed = false;
    await mutate(async (preparedOwnerId) => {
      try {
        await runAction(
          {
            ...action,
            url: PerformElementActionController.url(),
            body: {
              ...action.body,
              ...nestedOwnerParams(manager, preparedOwnerId),
              elementType: manager.elementType,
              source: '__IMP__',
              context: 'embeddedIndex',
              elementIds: ids,
            },
          },
          {trigger}
        );
        performed = true;
        options.selection.clear();
        window.Craft?.cp?.displayNotice?.(t('Done'));
      } catch (cause) {
        window.Craft?.cp?.displayError?.(
          cause instanceof Error ? cause.message : t('A server error occurred.')
        );

        throw cause;
      }
    });

    if (performed && item.key === DELETE_ACTION) {
      focusCreateButton();
    }

    return performed;
  }

  async function paste(beforeId?: number): Promise<void> {
    if (!canPaste.value) {
      return;
    }

    await mutate(
      async (preparedOwnerId) => {
        const manager = options.manager.value!;
        const pasted = await craft().cp.pasteElements({
          primaryOwnerId: preparedOwnerId,
          ownerId: preparedOwnerId,
          fieldId: manager.fieldId,
          siteId: manager.ownerSiteId,
        });
        const beforeIndex = options.entries.value.findIndex(
          (entry) => entry.id === beforeId
        );
        if (beforeIndex >= 0 && pasted.length && options.canReorder.value) {
          await actionClient.post(NestedElementsController.reorder.url(), {
            ...nestedOwnerParams(manager, preparedOwnerId),
            elementIds: pasted.map((entry) => entry.id),
            offset: options.offset.value + beforeIndex,
          });
        }
        focusEntryId = pasted[0]?.id ?? null;
        focusEntryIndex = Math.max(0, beforeIndex);
      },
      {reset: beforeId === undefined}
    );
    restoreEntryFocus();
  }

  async function reorder(
    from: number,
    to: number,
    group = true
  ): Promise<void> {
    const moved = options.entries.value[from];
    if (!options.canReorder.value || !moved) {
      return;
    }

    const ids =
      group && options.selection.isSelected(moved.id)
        ? options.entries.value
            .filter((entry) => options.selection.isSelected(entry.id))
            .map((entry) => entry.id)
        : [moved.id];
    const targetOffset = nestedEntryReorderOffset(
      options.entries.value,
      ids,
      from,
      to,
      options.offset.value
    );
    if (targetOffset === null) {
      return;
    }

    focusEntryId = moved.id;
    await reorderIds(ids, targetOffset);
    restoreEntryFocus();
  }

  async function reorderIds(ids: number[], offset: number): Promise<boolean> {
    if (!options.canReorder.value) {
      return false;
    }

    let reordered = false;
    await mutate(async (preparedOwnerId) => {
      const manager = options.manager.value!;
      const {data} = await actionClient.post(
        NestedElementsController.reorder.url(),
        {
          ...nestedOwnerParams(manager, preparedOwnerId),
          elementIds: ids,
          offset,
        }
      );
      reordered = true;
      window.Craft?.cp?.displayNotice(data.message);
      window.Craft?.broadcaster?.postMessage({
        pageId: window.Craft.pageId,
        event: 'reorderNestedElements',
        canonicalId: preparedOwner.value?.canonicalId,
        draftId: preparedOwner.value?.draftId,
        isProvisionalDraft: preparedOwner.value?.isProvisionalDraft,
        ownerId: preparedOwnerId,
        attribute: manager.attribute,
        elementType: manager.elementType,
        elementIds: ids,
      });
    });

    return reordered;
  }

  async function moveSelectionToPage(
    ids: number[],
    offset: number
  ): Promise<boolean> {
    const moved = await reorderIds(ids, offset);
    if (moved) {
      focusEntryId = ids[0] ?? null;
      focusEntryIndex = 0;
      restoreFocusAfterLoad = true;
    }

    return moved;
  }

  function restoreEntryFocus(): void {
    const container = options.container.value;
    if (!container) {
      return;
    }

    const items = container.querySelectorAll<HTMLElement>('[data-nested-id]');
    const target =
      [...items].find(
        (item) => Number(item.dataset.nestedId) === focusEntryId
      ) ?? items.item(focusEntryIndex);
    const entry = options.entries.value.find(
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

  function focusCreateButton(): void {
    options.container.value
      ?.querySelector<HTMLElement>('[data-create-entry]')
      ?.focus();
  }

  async function onLoaded(): Promise<void> {
    if (
      restoreFocusAfterLoad ||
      document.activeElement === options.container.value
    ) {
      restoreFocusAfterLoad = false;
      await nextTick();
      restoreEntryFocus();
    }
  }

  function onContainerFocus(): void {
    void nextTick().then(restoreEntryFocus);
  }

  async function refreshFromBroadcaster(): Promise<void> {
    await refreshOwner();
    await options.refresh();
  }

  useEventListener(
    () => window.Craft?.broadcaster,
    'message',
    (event: MessageEvent) => {
      const data = event.data;
      if (
        data.event === 'reorderNestedElements' &&
        data.ownerId ===
          (preparedOwner.value?.ownerId ?? options.manager.value?.ownerId) &&
        data.attribute === options.manager.value?.attribute &&
        !busy.value
      ) {
        void refreshFromBroadcaster().catch(setError);
      }
    }
  );

  watch(
    () => options.manager.value?.ownerId,
    () => {
      ownerId.value = null;
    }
  );

  return {
    busy,
    error,
    preparedOwner,
    quickEdit,
    canAdd,
    canPaste,
    canReorder: options.canReorder,
    pasteButtonLabel,
    pasteActionLabel,
    mutate,
    prepare,
    onChildSaved,
    openLegacyEditor,
    create,
    performElementAction,
    paste,
    reorder,
    moveSelectionToPage,
    onLoaded,
    focusCreateButton,
    onContainerFocus,
  };
}

export type NestedEntryOperations = ReturnType<typeof useNestedEntryOperations>;
