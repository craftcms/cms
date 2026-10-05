import {actionClient, t} from '@craftcms/ui';
import {useEventListener, useTimeoutFn} from '@vueuse/core';
import {computed, nextTick, shallowRef, watch, type Ref} from 'vue';
import CreateElementController from '@/actions/CraftCms/Cms/Http/Controllers/Elements/CreateElementController';
import EditElementController from '@/actions/CraftCms/Cms/Http/Controllers/Elements/EditElementController';
import NestedElementsController from '@/actions/CraftCms/Cms/Http/Controllers/NestedElementsController';
import {useAnnouncer} from '@/common/composables/useAnnouncer';
import type {Selectable} from '@/common/composables/useSelectable';
import {canUseVueSlideout, openSlideout} from '@/common/slideouts';
import type {SlideoutSaveResult} from '@/common/slideouts';
import type {
  NestedOwnerContext,
  NestedOwnerEditor,
} from '@/modules/elements/nested-owner';
import {useCopiedElements} from '@/modules/matrix/copied-elements';
import {craft} from '@/modules/matrix/interop';
import {ELEMENT_QUICK_EDIT_CONTROL_SELECTOR} from '@/modules/elements/composables/useElementQuickEdit';
import {
  canOpenElement,
  focusNestedElement,
  isPasteable,
  nestedElementsErrorMessage,
  nestedElementReorderOffset,
  nestedOwnerParams,
  nestedPasteLabel,
  type NestedElementsManager,
  type NestedElement,
} from './nested-elements';

export interface NestedElementQuickEdit {
  readonly longPress: true;
  openRow(element: NestedElement, opener?: HTMLElement | null): void;
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

export interface UseNestedElementOperationsOptions {
  elements: Readonly<Ref<NestedElement[]>>;
  offset: Readonly<Ref<number>>;
  canReorder: Readonly<Ref<boolean>>;
  selection: Selectable<number>;
  count: Readonly<Ref<number | null>>;
  refresh(reset?: boolean): Promise<void>;
  manager: Readonly<Ref<NestedElementsManager | null>>;
  owner: NestedOwnerEditor | null;
  editable: Readonly<Ref<boolean>>;
  controlPath: string[];
  container: Readonly<Ref<HTMLElement | null | undefined>>;
  markModified(): void;
  refreshCreatedElementOnOpen?: boolean;
  busy?: Ref<boolean>;
  error?: Ref<string>;
  ownerId?: Ref<number | null>;
}

export function useNestedElementOperations(
  options: UseNestedElementOperationsOptions
) {
  const busy = options.busy ?? shallowRef(false);
  const error = options.error ?? shallowRef('');
  const ownerId = options.ownerId ?? shallowRef<number | null>(null);
  const preparedOwner = shallowRef<NestedOwnerContext | null>(null);
  const copied = useCopiedElements();
  const {announce} = useAnnouncer();
  let focusElementId: number | null = null;
  let focusElementIndex = -1;
  let resetAfterChildSave = false;

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
      pasteableData: manager.pasteableData,
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
    error.value = nestedElementsErrorMessage(
      cause,
      t('Could not update nested elements.')
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
        t('Could not save the owner draft. No nested elements were changed.')
      );
    }

    if (
      context.requiresDerivative &&
      !context.ownerIsUnpublishedDraft &&
      !context.ownerIsDerivative &&
      !context.ownerIsInDerivativeTree
    ) {
      throw new Error(
        t('Could not prepare the owner draft. No nested elements were changed.')
      );
    }

    preparedOwner.value = context;
    ownerId.value = context.ownerId;

    return context.ownerId;
  }

  function prepareEdit(
    element: NestedElement
  ): (() => Promise<number | undefined>) | undefined {
    if (!options.editable.value) {
      return undefined;
    }

    return async () => {
      if (
        !element.ownerIsCanonical ||
        element.isUnpublishedDraft ||
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

  async function mutate(
    operation: (preparedOwnerId: number) => Promise<void | false>,
    mutationOptions: {reset?: boolean} = {}
  ): Promise<void> {
    if (busy.value) {
      return;
    }

    busy.value = true;
    error.value = '';
    announce(t('Loading'));

    try {
      const preparedOwnerId = await prepare();
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
    } catch (cause) {
      setError(cause);
    } finally {
      busy.value = false;
      announce(t('Loading complete'));
    }
  }

  const draftRefresh = useTimeoutFn(
    () => {
      const reset = resetAfterChildSave;
      resetAfterChildSave = false;
      void refreshAfterChildSave(true, reset);
    },
    1000,
    {immediate: false}
  );

  function onChildSaved(draft = false, reset = false): void {
    window.Craft?.Preview?.refresh();
    draftRefresh.stop();
    resetAfterChildSave ||= reset;

    if (draft) {
      draftRefresh.start();

      return;
    }

    const shouldReset = resetAfterChildSave;
    resetAfterChildSave = false;
    void refreshAfterChildSave(false, shouldReset);
  }

  async function refreshAfterChildSave(
    draft = false,
    reset = false
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
        restoreElementFocus();
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

  function rememberElement(element: NestedElement): void {
    focusElementId = element.id;
    focusElementIndex = options.elements.value.findIndex(
      (candidate) => candidate.id === element.id
    );
  }

  function openElement(
    element: NestedElement,
    opener: HTMLElement | null
  ): void {
    if (!canOpenElement(element)) {
      return;
    }

    rememberElement(element);
    const prepareNestedOwner = prepareEdit(element);
    const onSaved = ({draft} = {} as SlideoutSaveResult): void => {
      onChildSaved(draft);
    };

    if (canUseVueSlideout()) {
      void openSlideout(element.editUrl, {
        opener,
        prepareNestedOwner,
        onSaved,
      });

      return;
    }

    (opener ?? options.container.value)?.focus();
    const editUrl = new URL(element.editUrl, window.location.origin);

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
              throw new Error(t('Could not save the nested element draft.'));
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

  function elementById(id: string | number): NestedElement | null {
    return (
      options.elements.value.find(
        (element) => String(element.id) === String(id)
      ) ?? null
    );
  }

  function elementFor(target: Element): NestedElement | null {
    const item = target.closest<HTMLElement>('[data-index-id]');

    return item?.dataset.indexId ? elementById(item.dataset.indexId) : null;
  }

  function openQuickEdit(
    element: NestedElement,
    opener: HTMLElement | null = null
  ): void {
    if (options.editable.value) {
      openElement(element, opener);
    }
  }

  function openQuickEditById(
    id: string | number,
    opener: HTMLElement | null = null
  ): void {
    const element = elementById(id);
    if (element) {
      openQuickEdit(element, opener);
    }
  }

  function onQuickEditDblClick(event: MouseEvent): void {
    const target = event.target;
    if (!(target instanceof Element)) {
      return;
    }

    const row = target.closest<HTMLElement>('[data-index-id]');
    const element = row ? elementFor(row) : null;
    const control = target.closest(ELEMENT_QUICK_EDIT_CONTROL_SELECTOR);
    if (!row || !element || (control && row.contains(control))) {
      return;
    }

    event.preventDefault();
    window.getSelection()?.removeAllRanges();

    if ((event.ctrlKey || event.metaKey) && element.cpEditUrl) {
      window.open(element.cpEditUrl, '_blank', 'noopener');

      return;
    }

    openQuickEdit(element, row);
  }

  function primaryQuickEditLink(event: MouseEvent): NestedElement | null {
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
    const element = elementFor(event.target);
    if (!anchor || !element?.editUrl || !options.editable.value) {
      return null;
    }

    return new URL(anchor.href, window.location.origin).href ===
      new URL(element.editUrl, window.location.origin).href
      ? element
      : null;
  }

  function suppressPrimaryLinkVisit(event: MouseEvent): void {
    if (primaryQuickEditLink(event)) {
      event.preventDefault();
      event.stopPropagation();
    }
  }

  function onPrimaryLink(event: MouseEvent): void {
    const element = primaryQuickEditLink(event);
    if (!element) {
      return;
    }

    event.preventDefault();
    event.stopPropagation();
    openQuickEdit(
      element,
      event.target instanceof HTMLElement
        ? event.target.closest<HTMLAnchorElement>('a[href]')
        : null
    );
  }

  const quickEdit: NestedElementQuickEdit = {
    longPress: true,
    openRow: openQuickEdit,
    openById: openQuickEditById,
    onDblClick: onQuickEditDblClick,
    onPrimaryLink,
    suppressPrimaryLinkVisit,
  };

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
      const editParams = {
        elementId: data.element.id,
        siteId: data.element.siteId,
        fieldId: manager.fieldId,
        ownerId: preparedOwnerId,
        draftId: data.element.draftId,
      };
      let firstSave = true;
      const onSaved = (draft: boolean) => {
        onChildSaved(draft, firstSave);
        firstSave = false;
      };

      if (!canUseVueSlideout()) {
        opener?.focus();
        openLegacyEditor(
          {
            ...editParams,
            params: {fresh: 1},
          },
          undefined,
          onSaved
        );

        return options.refreshCreatedElementOnOpen === false
          ? false
          : undefined;
      }

      const editUrl = EditElementController[
        '/{cpTrigger?}/{actionTrigger?}/elements/edit'
      ].url(undefined, {
        query: {
          ...editParams,
          fresh: 1,
        },
      });
      await openSlideout(editUrl, {
        opener,
        onSaved: ({draft} = {}) => onSaved(Boolean(draft)),
      });

      return options.refreshCreatedElementOnOpen === false ? false : undefined;
    });
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
        const beforeIndex = options.elements.value.findIndex(
          (element) => element.id === beforeId
        );
        if (beforeIndex >= 0 && pasted.length && options.canReorder.value) {
          await actionClient.post(NestedElementsController.reorder.url(), {
            ...nestedOwnerParams(manager, preparedOwnerId),
            elementIds: pasted.map((element) => element.id),
            offset: options.offset.value + beforeIndex,
          });
        }
        focusElementId = pasted[0]?.id ?? null;
        focusElementIndex = Math.max(0, beforeIndex);
      },
      {reset: beforeId === undefined}
    );
    restoreElementFocus();
  }

  async function reorder(
    from: number,
    to: number,
    group = true
  ): Promise<void> {
    const moved = options.elements.value[from];
    if (!options.canReorder.value || !moved) {
      return;
    }

    const ids =
      group && options.selection.isSelected(moved.id)
        ? options.elements.value
            .filter((element) => options.selection.isSelected(element.id))
            .map((element) => element.id)
        : [moved.id];
    const targetOffset = nestedElementReorderOffset(
      options.elements.value,
      ids,
      from,
      to,
      options.offset.value
    );
    if (targetOffset === null) {
      return;
    }

    focusElementId = moved.id;
    await reorderIds(ids, targetOffset);
    restoreElementFocus();
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

  function restoreElementFocus(): void {
    focusNestedElement(
      options.container.value,
      options.elements.value,
      focusElementId,
      focusElementIndex
    );
  }

  function focusElement(id: number, fallbackIndex = 0): void {
    focusElementId = id;
    focusElementIndex = fallbackIndex;
    restoreElementFocus();
  }

  function focusCreateButton(): void {
    options.container.value
      ?.querySelector<HTMLElement>('[data-create-element]')
      ?.focus();
  }

  function onContainerFocus(): void {
    void nextTick().then(restoreElementFocus);
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
    quickEdit,
    canAdd,
    canPaste,
    canReorder: options.canReorder,
    pasteButtonLabel,
    pasteActionLabel,
    mutate,
    create,
    paste,
    reorder,
    reorderIds,
    focusElement,
    focusCreateButton,
    onContainerFocus,
  };
}

export type NestedElementOperations = ReturnType<
  typeof useNestedElementOperations
>;
