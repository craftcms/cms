import {actionClient, t} from '@craftcms/ui';
import {
  computed,
  inject,
  nextTick,
  onMounted,
  readonly,
  ref,
  shallowRef,
  toRaw,
  watch,
  type Ref,
} from 'vue';
import {
  createEntry,
  renderBlocks,
} from '@/actions/CraftCms/Cms/Http/Controllers/MatrixController';
import {NestedOwnerEditorKey} from '@/modules/elements/nested-owner';
import {craft, type CopiedElementInfo} from '@/modules/matrix/clipboard';
import {useCopiedElements} from '@/modules/matrix/copied-elements';
import {useMessages} from '@/modules/messages/useMessages';
import {isRecord, valueAt} from '../runtime';
import {
  NESTED_ELEMENT_UID_PREFIX,
  type NestedElementValue,
  type NestedUiPayload,
  type UiControlPayload,
  type UiPayload,
  type UiValue,
  type UiValues,
} from '../types';
import {isPasteable} from './nested-elements';
import type {
  BlockPresentation,
  CreatedBlock,
  NestedElementBlocksProps,
  UpdateBlocksValue,
} from './nested-element-blocks';

type BlockCreationOptions = {
  control: () => UiControlPayload<NestedElementBlocksProps>;
  value: () => NestedElementValue;
  editable: () => boolean;
  errors: () => UiPayload['errors'];
  host: Readonly<Ref<HTMLElement | undefined>>;
  /** The blocks a menu action on `uid` applies to. */
  actionTargets: (uid: string) => string[];
  highlightBlock: (uid: string) => void;
  /** Lets the rebuild a new block sets off scroll to it, not hold still. */
  setRevealing: (revealing: boolean) => void;
  update: UpdateBlocksValue;
};

/**
 * Adding, duplicating, copying and pasting blocks, and the ui and presentation
 * of the blocks the server mints along the way.
 */
export function useBlockCreation(options: BlockCreationOptions) {
  const owner = inject(NestedOwnerEditorKey, null);
  const messages = useMessages();
  const copiedElements = useCopiedElements();

  /**
   * UI definitions for blocks the server minted since the last full payload. They're
   * dropped as soon as that payload catches up and carries them itself.
   */
  // `shallowRef`, not `ref`: a ui payload's values are a recursive type that
  // Vue can't unwrap for deep reactivity, and both maps are replaced wholesale
  // rather than written into.
  const created = shallowRef(new Map<string, NestedUiPayload>());
  /**
   * Presentation for those same blocks. Without it a block the server has just
   * minted renders as a blank card — no entry type color, no icon, no menu —
   * until the next save brings the field's own copy round.
   */
  const createdBlocks = shallowRef(new Map<string, BlockPresentation>());

  /** A block's presentation, whether it arrived with the field or was just minted. */
  function block(uid: string): BlockPresentation | undefined {
    return (
      options.control().props.blocks?.[uid] ?? createdBlocks.value.get(uid)
    );
  }

  const adding = ref<string | null>(null);
  const pasting = ref(false);
  /** Whether the server is mid-flight on a block, so nothing else starts one. */
  const busy = computed(() => adding.value !== null || pasting.value);

  const uis = computed(() => {
    const map = new Map<string, NestedUiPayload>();

    for (const [uid, ui] of created.value) {
      map.set(uid, ui);
    }

    for (const ui of options.control().uis ?? []) {
      // Entries added client-side are keyed with a `uid:` prefix, which the
      // server strips before saving — so their uis come back scoped to the
      // bare UUID while the block is still keyed with the prefix.
      const uid = ui.scope.at(-1)!;
      map.set(uid, ui);
      map.set(`${NESTED_ELEMENT_UID_PREFIX}${uid}`, ui);
    }

    return map;
  });
  const canAdd = computed(() => hasRoomFor(1));

  function hasRoomFor(count: number): boolean {
    const maximum = options.control().props.maxEntries;

    return (
      options.editable() &&
      (!maximum || options.value().sortOrder.length + count <= maximum)
    );
  }

  /**
   * The clipboard, when all of it could land in this field — Craft 5's
   * `canPaste()`. Empty otherwise, which is what hides every paste target.
   *
   * `entryTypeId` arrives with the chip `Craft.cp` renders for each copied
   * element, so the check waits for that rather than offering a paste the server
   * would then refuse.
   */
  const pasteable = computed<CopiedElementInfo[]>(() => {
    const create = options.control().props.create;
    const elementType = options.control().props.elementType;
    const elements = copiedElements.value as CopiedElementInfo[];

    if (
      !create ||
      !elementType ||
      !elements.length ||
      !hasRoomFor(elements.length)
    ) {
      return [];
    }

    const fits = isPasteable(elements, {
      elementType,
      pasteableData: {
        attribute: 'entryTypeId',
        values: Object.values(create.entryTypeIds),
      },
      room: true,
    });

    return fits ? elements : [];
  });
  const creationTypes = computed(() => {
    const catalog = options.control().props.entryTypes ?? [];
    const handles = options.control().props.createEntryTypes;
    return handles
      ? handles.flatMap((handle) =>
          catalog.filter((type) => type.value === handle)
        )
      : catalog;
  });
  const createChoices = computed(() =>
    creationTypes.value.map((type) => ({
      ...type,
      icon: type.icon?.name,
    }))
  );

  /**
   * Blocks the server says are collapsed (a new block whose posted `collapsed`
   * came back) are adopted into storage once, so the two agree from then on.
   */
  watch(
    () => options.control().uis,
    (serverUis) => {
      if (!created.value.size || !serverUis?.length) {
        return;
      }

      const known = new Set(serverUis.map((ui) => ui.scope.at(-1)));
      const next = new Map(
        [...created.value].filter(([uid]) => !known.has(uid))
      );

      if (next.size !== created.value.size) {
        created.value = next;
      }
    }
  );

  onMounted(() => {
    void initializeMinimumEntries();
  });

  async function initializeMinimumEntries(): Promise<void> {
    const types = creationTypes.value;
    const control = options.control();
    const minimum = control.props.minEntries ?? 0;
    if (
      !options.editable() ||
      !control.props.create ||
      types.length !== 1 ||
      options
        .errors()
        .some((error) =>
          control.path.every((segment, index) => error.path[index] === segment)
        )
    ) {
      return;
    }

    try {
      while (
        options.host.value?.isConnected &&
        options.value().sortOrder.length < minimum &&
        canAdd.value &&
        !busy.value
      ) {
        await addBlock(types[0]!.value, undefined, undefined, false);
      }
    } catch {
      // addBlock has already reported the failure to the user.
    }
  }

  /**
   * Adds a block, letting the server mint it the way Craft 5 did: the button
   * shows a loading state while `matrix/create-entry` persists the entry as a
   * draft and hands back its ui nodes, which render through UiNodeList like
   * any other ui. The identity is the server's, so nothing has to be
   * reconciled when the next save comes around.
   *
   * `duplicate` names an existing element to copy the new block from — the same
   * endpoint, the same response, so Duplicate is Add with a source.
   *
   * Without a `create` config (an unsaved owner, or an Addresses field on this
   * same Control) the browser mints the block and the next save materializes it.
   */
  async function addBlock(
    entryType: string,
    beforeUid?: string,
    duplicateUid?: string,
    reveal = true
  ): Promise<void> {
    if (!canAdd.value || busy.value) {
      return;
    }

    if (
      duplicateUid === undefined &&
      !creationTypes.value.some((type) => type.value === entryType)
    ) {
      return;
    }

    const create = options.control().props.create;
    const index = insertionIndex(beforeUid);

    if (!create) {
      await insertBlocks(
        [
          {
            uid: `${NESTED_ELEMENT_UID_PREFIX}${crypto.randomUUID()}`,
            type: entryType,
          },
        ],
        index,
        reveal
      );

      return;
    }

    adding.value = entryType;

    try {
      const ownerId = await prepareOwner();
      const sourceId =
        duplicateUid === undefined ? undefined : elementId(duplicateUid);
      const duplicate =
        sourceId === undefined
          ? undefined
          : (owner?.resolveElementId?.(Number(sourceId)) ?? sourceId);
      if (duplicateUid !== undefined && duplicate === undefined) {
        throw new Error(t('Couldn’t duplicate {type}.', {type: t('entry')}));
      }
      const {data} = await actionClient.post<CreatedBlock>(createEntry.url(), {
        fieldId: create.fieldId,
        entryTypeId:
          duplicateUid === undefined
            ? create.entryTypeIds[entryType]
            : (block(duplicateUid)?.data?.['type-id'] ??
              create.entryTypeIds[entryType]),
        ownerId,
        ownerElementType: create.ownerElementType,
        siteId: create.siteId,
        path: options.control().path,
        ...(duplicate === undefined ? {} : {duplicate}),
      });

      await insertBlocks([data], index, reveal);
    } catch (error) {
      messages.error(
        duplicateUid === undefined
          ? t('Couldn’t create {type}.', {type: t('entry')})
          : t('Couldn’t duplicate {type}.', {type: t('entry')})
      );
      throw error;
    } finally {
      adding.value = null;
    }
  }

  async function prepareOwner(): Promise<number> {
    const create = options.control().props.create!;
    await nextTick();

    if (!options.editable() || !options.host.value?.isConnected) {
      throw new Error(t('This field cannot be edited here.'));
    }

    if (!owner && create.ownerHasDrafts !== true) {
      return create.ownerId;
    }

    const context = await owner?.prepare(options.control().path);
    await nextTick();

    if (
      !options.editable() ||
      !options.host.value?.isConnected ||
      !context ||
      (context.requiresDerivative &&
        !context.ownerIsUnpublishedDraft &&
        !context.ownerIsDerivative &&
        !context.ownerIsInDerivativeTree)
    ) {
      throw new Error(
        t('Could not prepare the owner draft. No nested elements were changed.')
      );
    }

    return context.ownerId;
  }

  /**
   * Duplicates a block — or the whole selection, when the invoking block is part
   * of one. Each copy lands directly after its source, the way Craft 5 placed it.
   */
  async function duplicateBlocks(uid: string): Promise<void> {
    for (const target of options.actionTargets(uid)) {
      const id = elementId(target);
      const type = options.value().entries[target]?.type;

      if (id === undefined || type === undefined || !canAdd.value) {
        continue;
      }

      const order = options.value().sortOrder;
      const after = order[order.indexOf(target) + 1];
      await addBlock(type, after, target);
    }
  }

  /**
   * Hands the blocks to the CP's element clipboard, which is shared with the
   * legacy stack and with other tabs. `Craft.cp` owns the confirmation toast.
   */
  function copyBlocks(uid: string): void {
    const elementType = options.control().props.elementType;
    const elements = options
      .actionTargets(uid)
      .map((target) => {
        const data = block(target)?.data;

        // The attributes are strings once they've been through the DOM, and
        // numbers here; the clipboard wants them numeric either way.
        const numeric = (value: number | string | undefined): number | null =>
          value === undefined || value === '' || Number.isNaN(Number(value))
            ? null
            : Number(value);

        const id = numeric(data?.['element-id']);

        return id === null || !elementType
          ? null
          : {
              type: elementType,
              id,
              draftId: numeric(data?.['draft-id']),
              revisionId: numeric(data?.['revision-id']),
              fieldId: numeric(data?.['field-id']),
              ownerId: numeric(data?.['owner-id']),
              siteId: numeric(data?.['site-id']),
            };
      })
      .filter((element) => element !== null);

    if (elements.length) {
      craft().cp.copyElements(elements);
    }
  }

  /**
   * Pastes the clipboard in above `beforeUid`, or at the end.
   *
   * `Craft.cp` duplicates the copied elements onto this field and owner and hands
   * back the new ones; `matrix/render-blocks` then returns their ui nodes, the
   * same shape a newly minted block comes back in.
   */
  async function pasteBlocks(beforeUid?: string): Promise<void> {
    const create = options.control().props.create;

    if (!create || !pasteable.value.length || busy.value) {
      return;
    }

    const index = insertionIndex(beforeUid);
    pasting.value = true;

    try {
      const ownerId = await prepareOwner();
      const pasted = await craft().cp.pasteElements({
        primaryOwnerId: ownerId,
        ownerId,
        fieldId: create.fieldId,
        siteId: create.siteId,
      });

      if (!pasted.length) {
        return;
      }

      const {data} = await actionClient.post<{blocks: CreatedBlock[]}>(
        renderBlocks.url(),
        {
          entryIds: pasted.map((element) => element.id),
          siteId: create.siteId,
          path: options.control().path,
        }
      );

      await insertBlocks(data.blocks, index);
    } catch (error) {
      messages.error(t('Couldn’t paste {type}.', {type: t('entries')}));
      throw error;
    } finally {
      pasting.value = false;
    }
  }

  /** Where a block goes when it's added above `beforeUid`, or at the end. */
  function insertionIndex(beforeUid?: string): number {
    const sortOrder = options.value().sortOrder;
    const at = beforeUid ? sortOrder.indexOf(beforeUid) : sortOrder.length;

    return at < 0 ? sortOrder.length : at;
  }

  /** The element behind a block, absent for one the browser minted. */
  function elementId(uid: string): number | string | undefined {
    return block(uid)?.data?.['element-id'];
  }

  /**
   * Deferred a tick on purpose. Changing sortOrder rebuilds the field,
   * which tears the whole subtree down and rebuilds it — and the button that was
   * clicked lives in there. Doing that while its click is still dispatching
   * leaves Vue patching against DOM a Lion overlay inside a block has already
   * moved, which throws `insertBefore` on null and takes the ui down with it.
   */
  async function insertBlocks(
    blocks: ReadonlyArray<CreatedBlock | {uid: string; type: string}>,
    index: number,
    reveal = true
  ): Promise<void> {
    await nextTick();

    const uis = new Map(created.value);
    const presentations = new Map(createdBlocks.value);
    const control = options.control();
    const next = structuredClone(toRaw(options.value()));

    blocks.forEach((added, offset) => {
      let values: UiValues = {};

      if ('ui' in added) {
        uis.set(added.uid, added.ui);
        presentations.set(added.uid, added.block);
        // The block's own field values ride along in the same emit. Writing them
        // straight into `values` wouldn't survive: the Control's value is written
        // back wholesale at its own path, which would drop anything under the
        // block that wasn't part of it.
        const blockValues = valueAt(added.values as UiValue, added.ui.scope);
        values = isRecord(blockValues) ? blockValues : {};
      }

      next.entries[added.uid] = {
        ...values,
        type: added.type,
        enabled: true,
        enabledForSite: true,
      };
      next.sortOrder.splice(index + offset, 0, added.uid);
    });

    created.value = uis;
    createdBlocks.value = presentations;
    // The rebuild this sets off would otherwise be held in place; `revealBlock`
    // is about to scroll somewhere better.
    options.setRevealing(reveal);
    options.update(next, {
      ...control,
      uis: [
        ...new Map(
          [...(control.uis ?? []), ...uis.values()].map((ui) => [
            JSON.stringify(ui.scope),
            ui,
          ])
        ).values(),
      ],
      props: {
        ...control.props,
        blocks: {
          ...Object.fromEntries(presentations),
          ...control.props.blocks,
        },
      },
    });

    for (const added of blocks) {
      options.highlightBlock(added.uid);
    }

    // A paste lands several at once; the first is where the group starts.
    if (reveal && blocks[0]) {
      await revealBlock(blocks[0].uid);
    }

    options.setRevealing(false);
  }

  /**
   * Brings a block that has just appeared into view and puts the cursor in it.
   *
   * Deferred past the render that adds it — and past the one that swaps its
   * spinner for the ui, which is what there is to focus.
   */
  async function revealBlock(uid: string): Promise<void> {
    await nextTick();

    const element = options.host.value?.querySelector<HTMLElement>(
      `[data-id="${CSS.escape(uid)}"]`
    );

    if (!element) {
      return;
    }

    element.scrollIntoView({behavior: 'smooth', block: 'nearest'});

    await nextTick();
    element
      .querySelector<HTMLElement>(
        '.fields input:not([type="hidden"]), .fields textarea, .fields select, .fields [tabindex]:not([tabindex="-1"])'
      )
      ?.focus({preventScroll: true});
  }

  return {
    uis,
    block,
    adding: readonly(adding),
    pasting: readonly(pasting),
    busy,
    canAdd,
    pasteable,
    creationTypes,
    createChoices,
    addBlock,
    duplicateBlocks,
    copyBlocks,
    pasteBlocks,
  };
}
