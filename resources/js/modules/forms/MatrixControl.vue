<script setup lang="ts">
  // `craft-action-menu`'s `actions` is a JS property (`attribute: false`), so the
  // element has to be defined before Vue patches it — otherwise the assignment
  // shadows the accessor and the menu never renders. Same reason ActionMenuNode
  // imports it. Leaf module, not the barrel.
  import '@craftcms/ui/components/action-menu/action-menu';
  import '@craftcms/ui/components/button/button';
  import '@craftcms/ui/components/icon/icon';
  import '@craftcms/ui/components/status/status';
  import '@craftcms/ui/components/spinner/spinner';
  import {actionClient, t} from '@craftcms/ui';
  import {
    computed,
    onBeforeUnmount,
    nextTick,
    onMounted,
    ref,
    toRaw,
    useId,
    watch,
  } from 'vue';
  import '@/modules/matrix';
  import {
    collapsedBlockId,
    isBlockCollapsed,
    setBlockCollapsed,
  } from '@/modules/matrix/collapsed-blocks';
  import {useCopiedElements} from '@/modules/matrix/copied-elements';
  import {
    NEW_BLOCK_CLASS,
    NEW_BLOCK_HIGHLIGHT_MS,
  } from '@/modules/matrix/new-block';
  import {blockPreviewParts} from '@/modules/matrix/preview-text';
  import {craft, type CopiedElementInfo} from '@/modules/matrix/interop';
  import ActionMenu from '@/common/components/ActionMenu.vue';
  import {useElementSize} from '@vueuse/core';
  import {useSelectable} from '@/common/composables/useSelectable';
  import SelectableCardList from '@/common/components/SelectableCardList.vue';
  import FormNodeList from './FormNodeList.vue';
  import type {ActionItems} from '@/common/types';
  import {useFlashMessages} from '@/common/composables/useFlashMessages';
  import {
    NESTED_ELEMENT_UID_PREFIX,
    type FormChange,
    type FormControlPayload,
    type FormPayload,
    type FormValue,
    type FormValues,
    type NestedElementValue,
    type NestedFormPayload,
  } from './types';
  import {inputName, isRecord, valueAt} from './runtime';

  /** What a block is called, what it looks like, and what can be done to it. */
  type BlockPresentation = {
    label?: string;
    icon?: {name: string; family: string} | null;
    color?: string | null;
    actions?: ActionItems;
    /** The block's identity, as `data-*` attributes. See `Matrix::blockData()`. */
    data?: Record<string, number | string>;
    /** Whether the last save left validation errors on the block. */
    error?: boolean;
  };
  type EntryType = {
    value: string;
    label: string;
    icon?: {name: string; family: string} | null;
    color?: string | null;
    group?: string | null;
  };
  type MatrixProps = {
    entryTypes?: EntryType[];
    addLabel: string;
    minEntries?: number | null;
    maxEntries?: number | null;
    /** Per-block presentation, keyed by identity. Server-built; never posted. */
    blocks?: Record<string, BlockPresentation>;
    /**
     * What `matrix/create-entry` needs to mint a block, or absent when the server
     * can't — an unsaved owner, or a nested element field that isn't Matrix-backed.
     */
    /** The blocks' element class, for the CP's element clipboard. */
    elementType?: string | null;
    create?: {
      fieldId: number;
      ownerId: number;
      ownerElementType: string;
      siteId: number;
      entryTypeIds: Record<string, number>;
    } | null;
  };
  type CreatedBlock = {
    uid: string;
    type: string;
    form: NestedFormPayload;
    values: FormValues;
    block: BlockPresentation;
  };
  /** The instance-local half of a block's menu. See `Matrix::blockActions()`. */
  type BlockActionDetail = {
    action:
      | 'collapse'
      | 'expand'
      | 'disable'
      | 'enable'
      | 'delete'
      | 'add'
      | 'duplicate'
      | 'copy'
      | 'paste';
    uid: string;
    entryType?: string;
    trigger?: unknown;
  };
  type MatrixValue = NestedElementValue;

  const props = defineProps<{
    control: FormControlPayload<MatrixProps>;
    /**
     * Undefined for a beat whenever this control's path isn't in the values tree
     * yet — a nested repeater inside a block whose identity the server has just
     * rewritten, say. Read {@link model} rather than this.
     */
    value: MatrixValue | undefined;
    values: FormPayload['values'];
    errors: FormPayload['errors'];
    touchedPaths: Set<string>;
    editable: boolean;
  }>();
  const emit = defineEmits<{
    (event: 'update:value', value: MatrixValue, kind: 'discrete'): void;
    (event: 'change', change: FormChange): void;
  }>();
  const EMPTY: MatrixValue = {entries: {}, sortOrder: []};
  const model = computed<MatrixValue>(() => props.value ?? EMPTY);
  const matrixHost = ref<HTMLElement>();
  const matrixId = useId();
  /**
   * Forms for blocks the server minted since the last full payload. They're
   * dropped as soon as that payload catches up and carries them itself.
   */
  const created = ref(new Map<string, NestedFormPayload>());
  /**
   * Presentation for those same blocks. Without it a block the server has just
   * minted renders as a blank card — no entry type colour, no icon, no menu —
   * until the next save brings the field's own copy round.
   */
  const createdBlocks = ref(new Map<string, BlockPresentation>());

  /**
   * Blocks that have just appeared, for as long as their highlight runs.
   *
   * Held as state rather than put on the element the way the other stacks do
   * it: Vue owns these blocks' classes and would patch a stray one away on the
   * next render.
   */
  const justAdded = ref(new Set<string>());
  const highlights = new Set<ReturnType<typeof setTimeout>>();

  function highlightBlock(uid: string): void {
    justAdded.value = new Set(justAdded.value).add(uid);

    const timer = setTimeout(() => {
      highlights.delete(timer);
      const next = new Set(justAdded.value);
      next.delete(uid);
      justAdded.value = next;
    }, NEW_BLOCK_HIGHLIGHT_MS);

    highlights.add(timer);
  }

  onBeforeUnmount(() => {
    for (const timer of highlights) {
      clearTimeout(timer);
    }
    highlights.clear();
  });

  /** A block's presentation, whether it arrived with the field or was just minted. */
  function block(uid: string): BlockPresentation | undefined {
    return props.control.props.blocks?.[uid] ?? createdBlocks.value.get(uid);
  }
  const {flash} = useFlashMessages();
  const adding = ref<string | null>(null);
  const pasting = ref(false);
  /** Whether the server is mid-flight on a block, so nothing else starts one. */
  const busy = computed(() => adding.value !== null || pasting.value);
  const copiedElements = useCopiedElements();

  const forms = computed(() => {
    const map = new Map<string, NestedFormPayload>();

    for (const [uid, form] of created.value) {
      map.set(uid, form);
    }

    for (const form of props.control.forms ?? []) {
      // Entries added client-side are keyed with a `uid:` prefix, which the
      // server strips before saving — so their forms come back scoped to the
      // bare UUID while the block is still keyed with the prefix.
      const uid = form.scope.at(-1)!;
      map.set(uid, form);
      map.set(`${NESTED_ELEMENT_UID_PREFIX}${uid}`, form);
    }

    return map;
  });
  const canAdd = computed(() => hasRoomFor(1));

  function hasRoomFor(count: number): boolean {
    const maximum = props.control.props.maxEntries;

    return (
      props.editable &&
      (!maximum || model.value.sortOrder.length + count <= maximum)
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
    const create = props.control.props.create;
    const elementType = props.control.props.elementType;
    const elements = copiedElements.value as CopiedElementInfo[];

    if (
      !create ||
      !elementType ||
      !elements.length ||
      !hasRoomFor(elements.length)
    ) {
      return [];
    }

    const typeIds = new Set(Object.values(create.entryTypeIds));
    const fits = elements.every(
      (element) =>
        element.type === elementType &&
        typeof element.data?.entryTypeId === 'number' &&
        typeIds.has(element.data.entryTypeId)
    );

    return fits ? elements : [];
  });
  const entryTypes = computed(() =>
    (props.control.props.entryTypes ?? []).map((type, index) => ({
      id: index + 1,
      handle: type.value,
      name: type.label,
    }))
  );
  /**
   * How to offer the entry types. One button each while they fit; a menu once
   * they don't, or once there are groups to file them under. Craft 5 collapsed
   * the row the same way.
   *
   * The row's natural width is measured while it's shown and remembered, so the
   * two states can't chase each other: what's compared is always the width the
   * buttons would take, not the width they're taking now.
   */
  const addArea = ref<HTMLElement>();
  const addButtons = ref<HTMLElement>();
  const {width: addAreaWidth} = useElementSize(addArea);
  const buttonsWidth = ref(0);
  const entryTypeGroups = computed(() => {
    const groups = new Map<string, EntryType[]>();

    for (const type of props.control.props.entryTypes ?? []) {
      const group = type.group ?? '';
      groups.set(group, [...(groups.get(group) ?? []), type]);
    }

    return [...groups];
  });
  const addFromMenu = computed(
    () =>
      entryTypeGroups.value.length > 1 ||
      (buttonsWidth.value > 0 &&
        addAreaWidth.value > 0 &&
        addAreaWidth.value < buttonsWidth.value)
  );
  /** Craft 5's threshold: past this many, the menu is worth searching. */
  const addMenuSearchable = computed(
    () => (props.control.props.entryTypes?.length ?? 0) > 5
  );

  watch(
    [addButtons, () => props.control.props.entryTypes],
    async () => {
      await nextTick();

      if (addButtons.value) {
        buttonsWidth.value = addButtons.value.scrollWidth;
      }
    },
    {immediate: true}
  );

  const addMenuActions = computed<ActionItems>(() =>
    entryTypeGroups.value.map(([group, types]) => ({
      type: 'group' as const,
      ...(group === '' ? {} : {heading: group}),
      items: types.map((type) => ({
        label: t('Add {type}', {type: type.label}),
        icon: type.icon?.name ?? 'plus',
        iconColor: type.color ?? undefined,
        disabled: busy.value,
        onClick: () => void addBlock(type.value),
      })),
    }))
  );

  const settings = computed(() =>
    JSON.stringify({
      formControl: true,
      maxEntries: props.control.props.maxEntries,
    })
  );
  const key = computed(() =>
    JSON.stringify([
      model.value.sortOrder,
      props.control.forms?.map((form) => form.scope),
      props.editable,
    ])
  );

  function sync(event?: Event): void {
    const value = structuredClone(toRaw(model.value));
    const source =
      event?.currentTarget instanceof HTMLElement
        ? event.currentTarget
        : matrixHost.value;
    const entries = [
      ...(source?.querySelectorAll<HTMLElement>('.matrixblock') ?? []),
    ];
    value.sortOrder = entries.map((entry) => entry.dataset.id!);
    value.entries = Object.fromEntries(
      entries.map((entry) => {
        const uid = entry.dataset.id!;

        return [
          uid,
          value.entries[uid] ?? {type: entry.dataset.type ?? '', enabled: true},
        ];
      })
    );
    emit('update:value', value, 'discrete');
  }

  /**
   * Collapsed blocks live in localStorage, not the value — it's a view
   * preference, and posting it would mark the form dirty just for collapsing
   * something. Craft 5 did the same. `collapsedTick` re-reads storage after a
   * write, since a plain module read isn't reactive.
   */
  const collapsedTick = ref(0);
  /**
   * What each folded-up block says about itself when it has no UI label, taken
   * off its inputs as it folds — the same moment Craft 5 took it, and the only
   * one where the fields are still on screen to read.
   */
  const previews = ref(new Map<string, string[]>());

  function isCollapsed(uid: string): boolean {
    void collapsedTick.value;

    return isBlockCollapsed(uid);
  }

  function setCollapsed(uid: string, collapsed: boolean): void {
    setCollapsedMany([uid], collapsed);
  }

  /**
   * Folds several blocks at once. One emit for the lot: a per-block emit would
   * have each one overwrite the last, since the Control's value is written back
   * whole at its own path.
   */
  function setCollapsedMany(uids: readonly string[], collapsed: boolean): void {
    const next = structuredClone(toRaw(model.value));
    let posts = false;

    for (const uid of uids) {
      setBlockCollapsed(uid, collapsed);
      capturePreview(uid, collapsed);

      // A block the browser minted isn't in storage under an identity the server
      // knows yet, so its state also rides along in the posted value — the same
      // job Craft 5's hidden `[collapsed]` input did for new blocks.
      if (uid.startsWith(NESTED_ELEMENT_UID_PREFIX) && next.entries[uid]) {
        next.entries[uid].collapsed = collapsed;
        posts = true;
      }
    }

    collapsedTick.value++;

    if (posts) {
      emit('update:value', next, 'discrete');
    }
  }

  /**
   * Blocks the server says are collapsed (a new block whose posted `collapsed`
   * came back) are adopted into storage once, so the two agree from then on.
   */
  watch(
    () => props.control.forms,
    (serverForms) => {
      if (!created.value.size || !serverForms?.length) {
        return;
      }

      const known = new Set(serverForms.map((form) => form.scope.at(-1)));
      const next = new Map(
        [...created.value].filter(([uid]) => !known.has(uid))
      );

      if (next.size !== created.value.size) {
        created.value = next;
      }
    }
  );

  /** Reads (or drops) a block's summary as it folds up or opens out. */
  function capturePreview(uid: string, collapsed: boolean): void {
    if (!collapsed) {
      previews.value.delete(uid);

      return;
    }

    // The first `.fields` under the block is its own; the ones after it belong
    // to whatever the block nests.
    const fields = matrixHost.value
      ?.querySelector(`[data-id="${CSS.escape(uid)}"]`)
      ?.querySelector<HTMLElement>('.fields');

    previews.value.set(uid, fields ? blockPreviewParts(fields) : []);
  }

  function isDisabled(uid: string): boolean {
    return model.value.entries[uid]?.enabled === false;
  }

  onMounted(() => {
    for (const uid of model.value.sortOrder) {
      // A disabled block isn't being edited, so it opens out of the way. It can
      // still be expanded from its menu — this only decides where it starts.
      const startsCollapsed =
        model.value.entries[uid]?.collapsed || isDisabled(uid);

      if (startsCollapsed && !isBlockCollapsed(uid)) {
        setBlockCollapsed(uid, true);
      }
    }
    collapsedTick.value++;

    // A block that opens folded up still needs its summary, and its fields are
    // rendered but hidden — so they're there to read once Vue has laid them out.
    void nextTick(() => {
      for (const uid of model.value.sortOrder) {
        if (isBlockCollapsed(uid)) {
          capturePreview(uid, true);
        }
      }
      collapsedTick.value++;
    });
  });

  /**
   * The card frame — selection, the select checkbox, drag-sort and the reorder
   * handle — comes from SelectableCardList, shared with the element index.
   */
  const selection = useSelectable<string>({
    ids: () => model.value.sortOrder,
    enabled: () => props.editable,
  });

  /**
   * Adds a block, letting the server mint it the way Craft 5 did: the button
   * shows a loading state while `matrix/create-entry` persists the entry as a
   * draft and hands back its form nodes, which render through FormNodeList like
   * any other form. The identity is the server's, so nothing has to be
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
    duplicate?: number | string
  ): Promise<void> {
    if (!canAdd.value || busy.value) {
      return;
    }

    const create = props.control.props.create;
    const index = insertionIndex(beforeUid);

    if (!create) {
      await insertBlocks(
        [
          {
            uid: `${NESTED_ELEMENT_UID_PREFIX}${crypto.randomUUID()}`,
            type: entryType,
          },
        ],
        index
      );

      return;
    }

    adding.value = entryType;

    try {
      const {data} = await actionClient.post<CreatedBlock>(
        'matrix/create-entry',
        {
          fieldId: create.fieldId,
          entryTypeId: create.entryTypeIds[entryType],
          ownerId: create.ownerId,
          ownerElementType: create.ownerElementType,
          siteId: create.siteId,
          path: props.control.path,
          ...(duplicate === undefined ? {} : {duplicate}),
        }
      );

      await insertBlocks([data], index);
    } catch (error) {
      flash(
        'error',
        duplicate === undefined
          ? t('Couldn’t create {type}.', {type: t('entry')})
          : t('Couldn’t duplicate {type}.', {type: t('entry')})
      );
      throw error;
    } finally {
      adding.value = null;
    }
  }

  /**
   * Duplicates a block — or the whole selection, when the invoking block is part
   * of one. Each copy lands directly after its source, the way Craft 5 placed it.
   */
  async function duplicateBlocks(uid: string): Promise<void> {
    for (const target of actionTargets(uid)) {
      const id = elementId(target);
      const type = model.value.entries[target]?.type;

      if (id === undefined || type === undefined || !canAdd.value) {
        continue;
      }

      const order = model.value.sortOrder;
      const after = order[order.indexOf(target) + 1];
      await addBlock(type, after, id);
    }
  }

  /**
   * Hands the blocks to the CP's element clipboard, which is shared with the
   * legacy stack and with other tabs. `Craft.cp` owns the confirmation toast.
   */
  function copyBlocks(uid: string): void {
    const elementType = props.control.props.elementType;
    const elements = actionTargets(uid)
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
   * back the new ones; `matrix/render-blocks` then returns their form nodes, the
   * same shape a newly minted block comes back in.
   */
  async function pasteBlocks(beforeUid?: string): Promise<void> {
    const create = props.control.props.create;

    if (!create || !pasteable.value.length || busy.value) {
      return;
    }

    const index = insertionIndex(beforeUid);
    pasting.value = true;

    try {
      const pasted = await craft().cp.pasteElements({
        primaryOwnerId: create.ownerId,
        ownerId: create.ownerId,
        fieldId: create.fieldId,
        siteId: create.siteId,
      });

      if (!pasted.length) {
        return;
      }

      const {data} = await actionClient.post<{blocks: CreatedBlock[]}>(
        'matrix/render-blocks',
        {
          entryIds: pasted.map((element) => element.id),
          siteId: create.siteId,
          path: props.control.path,
        }
      );

      await insertBlocks(data.blocks, index);
    } catch (error) {
      flash('error', t('Couldn’t paste {type}.', {type: t('entries')}));
      throw error;
    } finally {
      pasting.value = false;
    }
  }

  /** Where a block goes when it's added above `beforeUid`, or at the end. */
  function insertionIndex(beforeUid?: string): number {
    const at = beforeUid
      ? model.value.sortOrder.indexOf(beforeUid)
      : model.value.sortOrder.length;

    return at < 0 ? model.value.sortOrder.length : at;
  }

  /** The element behind a block, absent for one the browser minted. */
  function elementId(uid: string): number | string | undefined {
    return block(uid)?.data?.['element-id'];
  }

  /**
   * Deferred a tick on purpose. Changing sortOrder re-keys `craft-matrix-input`,
   * which tears the whole subtree down and rebuilds it — and the button that was
   * clicked lives in there. Doing that while its click is still dispatching
   * leaves Vue patching against DOM a Lion overlay inside a block has already
   * moved, which throws `insertBefore` on null and takes the form down with it.
   */
  async function insertBlocks(
    blocks: ReadonlyArray<CreatedBlock | {uid: string; type: string}>,
    index: number
  ): Promise<void> {
    await nextTick();

    const forms = new Map(created.value);
    const presentations = new Map(createdBlocks.value);
    const next = structuredClone(toRaw(model.value));

    blocks.forEach((added, offset) => {
      let values: FormValues = {};

      if ('form' in added) {
        forms.set(added.uid, added.form);
        presentations.set(added.uid, added.block);
        // The block's own field values ride along in the same emit. Writing them
        // straight into `values` wouldn't survive: the Control's value is written
        // back wholesale at its own path, which would drop anything under the
        // block that wasn't part of it.
        const blockValues = valueAt(
          added.values as FormValue,
          added.form.scope
        );
        values = isRecord(blockValues) ? blockValues : {};
      }

      next.entries[added.uid] = {...values, type: added.type, enabled: true};
      next.sortOrder.splice(index + offset, 0, added.uid);
    });

    created.value = forms;
    createdBlocks.value = presentations;
    emit('update:value', next, 'discrete');

    for (const added of blocks) {
      highlightBlock(added.uid);
    }

    // A paste lands several at once; the first is where the group starts.
    if (blocks[0]) {
      await revealBlock(blocks[0].uid);
    }
  }

  /**
   * Brings a block that has just appeared into view and puts the cursor in it.
   *
   * Deferred past the render that adds it — and past the one that swaps its
   * spinner for the form, which is what there is to focus.
   */
  async function revealBlock(uid: string): Promise<void> {
    await nextTick();

    const element = matrixHost.value?.querySelector<HTMLElement>(
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

  /**
   * Moves the block at `from` to `to`, keeping `entries` untouched.
   *
   * The whole selection travels when the block being moved is part of one, the
   * way Craft 5's drag-sort did — the drag engine only ever reports the one
   * block, so the rest are gathered here and land together, in the order they
   * were already in.
   */
  function move(from: number, to: number): void {
    if (from === to || from < 0 || to < 0) {
      return;
    }

    const next = structuredClone(toRaw(model.value));
    const [uid] = next.sortOrder.splice(from, 1);

    if (uid === undefined) {
      return;
    }

    const group = actionTargets(uid);
    let index = to;

    // Each block pulled out from before the landing point drags it back one.
    for (const id of group) {
      const at = next.sortOrder.indexOf(id);

      if (at === -1) {
        continue;
      }

      next.sortOrder.splice(at, 1);

      if (at < index) {
        index--;
      }
    }

    next.sortOrder.splice(index, 0, ...group);
    emit('update:value', next, 'discrete');
  }

  /** Collapsing is the card's own state, so it can fold away body and footer. */
  function blockCardAttrs(uid: string): Record<string, unknown> {
    return {collapsed: isCollapsed(uid)};
  }

  function blockIcon(uid: string): {name: string; family: string} | null {
    return block(uid)?.icon ?? null;
  }

  /**
   * `.matrixblock` stays the direct child of the blocks container: the legacy
   * `craft-matrix-input` still finds its entries through it, and `sync()` reads
   * the identity back off `data-id`.
   */
  function blockAttrs(uid: string): Record<string, unknown> {
    const presentation = block(uid);

    return {
      ...blockData(uid),
      'data-id': uid,
      'data-type': model.value.entries[uid]?.type ?? '',
      // The CP's generated colorable rules turn this into the whole `--c-color-*`
      // alias set, which the card and everything in it paints from.
      'data-color': presentation?.color ?? undefined,
      'data-ui-label': uiLabel(uid) || undefined,
      'data-collapsed': isCollapsed(uid) ? '' : undefined,
      'data-matrix-block': '',
      role: 'listitem',
      class: {
        collapsed: isCollapsed(uid),
        'disabled-entry': model.value.entries[uid]?.enabled === false,
        [NEW_BLOCK_CLASS]: justAdded.value.has(uid),
      },
    };
  }

  /**
   * The block's identity as `data-*` attributes. The CP's element clipboard reads
   * it back off the DOM, so copy and paste need it there rather than only in the
   * payload. Absent for a block the browser minted — there's no element yet.
   */
  function blockData(uid: string): Record<string, number | string> {
    return Object.fromEntries(
      Object.entries(block(uid)?.data ?? {}).map(([name, value]) => [
        `data-${name}`,
        value,
      ])
    );
  }

  /**
   * The blocks a menu action applies to: the whole selection when the invoking
   * block is part of a multi-selection, otherwise just that block. Craft 5 spelled
   * this `bulkActionMode()`.
   */
  function actionTargets(uid: string): string[] {
    const selected = selection.selectedIds.value;

    return selected.length > 1 && selection.isSelected(uid)
      ? model.value.sortOrder.filter((id) => selected.includes(id))
      : [uid];
  }

  function blockEvent(
    uid: string,
    action: string,
    detail: Record<string, unknown> = {}
  ) {
    return {
      type: 'event' as const,
      name: 'craft:matrix-block-action',
      detail: {action, uid, ...detail},
    };
  }

  /** Collapse/Expand and Disable/Enable, resolved against the block right now. */
  function stateActions(uid: string): ActionItems {
    const block = model.value.entries[uid];

    return [
      isCollapsed(uid)
        ? {
            label: t('Expand'),
            icon: 'up-right-and-down-left-from-center',
            action: blockEvent(uid, 'expand'),
          }
        : {
            label: t('Collapse'),
            icon: 'down-left-and-up-right-to-center',
            action: blockEvent(uid, 'collapse'),
          },
      block?.enabled === false
        ? {
            label: t('Enable'),
            icon: 'circle',
            action: blockEvent(uid, 'enable'),
          }
        : {
            label: t('Disable'),
            icon: 'circle-dashed',
            action: blockEvent(uid, 'disable'),
          },
    ];
  }

  /**
   * A block the browser minted has no server-built menu until the next save
   * materializes it, so compose the half that needs no server data. Duplicate,
   * Copy and Paste are all absent — each of them needs an element to point at.
   */
  function localActions(uid: string): ActionItems {
    return [
      ...stateActions(uid),
      {type: 'hr'},
      {
        label: t('Delete'),
        icon: 'trash',
        variant: 'danger',
        action: blockEvent(uid, 'delete'),
      },
      {type: 'hr'},
      ...(props.control.props.entryTypes ?? []).map((type) => ({
        label: t('Add {type} above', {type: type.label}),
        icon: 'plus',
        hidden: !canAdd.value,
        disabled: busy.value,
        action: blockEvent(uid, 'add', {entryType: type.value}),
      })),
    ];
  }

  /**
   * Announced while the server mints or pastes a block. The add buttons show a
   * spinner, but "Add {type} above" is a menu item with nowhere to put one.
   */
  const statusMessage = computed(() => (busy.value ? t('Loading') : ''));

  /**
   * What a folded-up block is called. Its own fields aren't on screen to
   * identify it, so the UI label stands in.
   *
   * The server's copy is authoritative but only as fresh as the last save, so a
   * live title wins while it's being typed.
   */
  function uiLabel(uid: string): string {
    const title = model.value.entries[uid]?.title;

    if (typeof title === 'string' && title.trim() !== '') {
      return title;
    }

    return block(uid)?.label ?? '';
  }

  /**
   * What a folded-up block shows in its header: its UI label, or — for an entry
   * type that has none — a summary of its own field values, the way Craft 5
   * fell back.
   */
  function previewText(uid: string): string {
    void collapsedTick.value;

    return uiLabel(uid) || (previews.value.get(uid) ?? []).join(' | ');
  }

  /** Whether the block has errors the header should own up to. */
  function hasErrors(uid: string): boolean {
    if (block(uid)?.error) {
      return true;
    }

    const scope = [...props.control.path, 'entries', uid];

    return props.errors.some((error) =>
      scope.every((segment, index) => error.path[index] === segment)
    );
  }

  /**
   * The label an action takes when it applies to a whole selection rather than
   * one block. Craft 5 swapped these in as the menu opened.
   */
  const BULK_LABEL: Record<string, () => string> = {
    collapse: () => t('Collapse selected blocks'),
    expand: () => t('Expand selected blocks'),
    disable: () => t('Disable selected {type}', {type: t('blocks')}),
    enable: () => t('Enable selected {type}', {type: t('blocks')}),
    duplicate: () => t('Duplicate selected {type}', {type: t('blocks')}),
    copy: () => t('Copy selected {type}', {type: t('blocks')}),
    delete: () => t('Delete selected {type}', {type: t('blocks')}),
  };

  /**
   * The block's menu, resolved against the state the server couldn't know: what
   * the block is doing right now, how much of the field is selected, whether
   * there's room for another block, and what's on the clipboard.
   *
   * Resolved in place rather than rebuilt so the server keeps ownership of which
   * items exist and in what order — its list already reflects the permissions.
   */
  function blockActions(uid: string): ActionItems {
    const server = block(uid)?.actions;

    if (!server?.length) {
      return localActions(uid);
    }

    const bulk = actionTargets(uid).length > 1;

    return server.map((item) => {
      const action = 'action' in item ? item.action : undefined;
      const name =
        action?.type === 'event' && action.name === 'craft:matrix-block-action'
          ? (action.detail?.action as string | undefined)
          : undefined;

      if (name === undefined) {
        return item;
      }

      return {
        ...item,
        ...(bulk && BULK_LABEL[name] ? {label: BULK_LABEL[name]()} : {}),
        // Craft 5 relabelled paste with what was actually on the clipboard.
        ...(name === 'paste' && pasteable.value.length
          ? {
              label: t('Paste {type} above', {
                type: pasteable.value.length === 1 ? t('block') : t('blocks'),
              }),
            }
          : {}),
        ...actionState(name, uid),
      };
    });
  }

  /** Whether one of the server's items is shown, and whether it can be used. */
  function actionState(
    name: string,
    uid: string
  ): {hidden?: boolean; disabled?: boolean} {
    switch (name) {
      case 'collapse':
        return {hidden: isCollapsed(uid)};
      case 'expand':
        return {hidden: !isCollapsed(uid)};
      case 'disable':
        return {hidden: isDisabled(uid)};
      case 'enable':
        return {hidden: !isDisabled(uid)};
      case 'add':
      case 'duplicate':
        return {hidden: !canAdd.value, disabled: busy.value};
      case 'paste':
        return {hidden: pasteable.value.length === 0, disabled: busy.value};
      default:
        return {};
    }
  }

  /**
   * `runAction()` dispatches `event` actions on `window`, so every Matrix on the
   * page hears every block action. Scope by the invoking element: the menu keeps
   * its content in place, so the trigger is still inside the block it belongs to.
   */
  function onBlockAction(event: Event): void {
    const detail = (event as CustomEvent<BlockActionDetail>).detail;

    if (!detail || !matrixHost.value) {
      return;
    }

    const trigger = detail.trigger;
    const owned =
      trigger instanceof HTMLElement &&
      trigger.closest('craft-matrix-input') === matrixHost.value;

    if (!owned) {
      return;
    }

    const targets = actionTargets(detail.uid);
    const next = structuredClone(toRaw(model.value));

    switch (detail.action) {
      case 'collapse':
      case 'expand':
        setCollapsedMany(targets, detail.action === 'collapse');

        return;

      case 'disable':
      case 'enable': {
        const enabled = detail.action === 'enable';

        for (const uid of targets) {
          if (next.entries[uid]) {
            next.entries[uid].enabled = enabled;

            // Disabling folds the block away; enabling brings it back, which is
            // what Craft 5's enable did too. Written straight onto `next` rather
            // than through `setCollapsed`, whose own emit this one would clobber.
            if (uid.startsWith(NESTED_ELEMENT_UID_PREFIX)) {
              next.entries[uid].collapsed = !enabled;
            }
          }

          setBlockCollapsed(uid, !enabled);
        }
        collapsedTick.value++;
        break;
      }

      case 'delete': {
        const minimum = props.control.props.minEntries ?? 0;
        const removable = targets.slice(
          0,
          Math.max(next.sortOrder.length - minimum, 0)
        );

        if (!removable.length) {
          return;
        }

        for (const uid of removable) {
          delete next.entries[uid];
          selection.select(uid, false);
        }
        next.sortOrder = next.sortOrder.filter(
          (uid) => !removable.includes(uid)
        );
        break;
      }

      case 'add':
        void addBlock(detail.entryType ?? '', detail.uid);

        return;

      case 'duplicate':
        void duplicateBlocks(detail.uid);

        return;

      case 'copy':
        copyBlocks(detail.uid);

        return;

      case 'paste':
        void pasteBlocks(detail.uid);

        return;

      default:
        return;
    }

    emit('update:value', next, 'discrete');
  }

  /**
   * The field's own "Expand/Collapse all blocks" items. `modules/fields` handles
   * these through each block's MatrixEntry controller, which only server-rendered
   * blocks have — the ones here are Vue's, so this control applies them itself.
   *
   * Scoped by field rather than by the Matrix host the way block actions are: the
   * invoking item lives in the field's header, outside the input. Comparing the
   * fields also keeps a nested Matrix out of its parent's reach.
   */
  function onToggleAll(event: Event): void {
    const detail = (
      event as CustomEvent<{collapse?: boolean; trigger?: unknown}>
    ).detail;
    const field = matrixHost.value?.closest('craft-field');

    if (
      !field ||
      !(detail?.trigger instanceof HTMLElement) ||
      detail.trigger.closest('craft-field') !== field
    ) {
      return;
    }

    setCollapsedMany(model.value.sortOrder, detail.collapse === true);
  }

  onMounted(() => {
    window.addEventListener('craft:matrix-block-action', onBlockAction);
    window.addEventListener('craft:matrix-toggle-all', onToggleAll);
  });
  onBeforeUnmount(() => {
    window.removeEventListener('craft:matrix-block-action', onBlockAction);
    window.removeEventListener('craft:matrix-toggle-all', onToggleAll);
  });

  function entryType(uid: string): EntryType | undefined {
    const handle = model.value.entries[uid]?.type;

    return props.control.props.entryTypes?.find(
      (type) => type.value === handle
    );
  }

  function nestedChange(change: FormChange, form: NestedFormPayload): void {
    emit('change', {
      ...change,
      scope: form.scope,
      refreshable: form.refreshable && change.refreshable,
    });
  }
</script>

<template>
  <craft-matrix-input
    ref="matrixHost"
    :key="key"
    form-control
    :entry-types="JSON.stringify(entryTypes)"
    :input-name-prefix="inputName(control.path)"
    :settings="settings"
    :min-entries="control.props.minEntries ?? 0"
    @form-change="sync"
  >
    <input v-if="editable" type="hidden" :name="inputName(control.path)" />
    <div :id="matrixId" class="matrix matrix-field">
      <span role="status" class="sr-only" data-status-message>{{
        statusMessage
      }}</span>
      <!-- `data-matrix-blocks` sits on the list itself: the legacy
           `craft-matrix-input` finds its entries with `:scope > .matrixblock`,
           so a wrapper between the two hides every block from it. -->
      <SelectableCardList
        role="list"
        data-matrix-blocks
        :ids="model.sortOrder"
        :selection="selection"
        :selectable="editable"
        :sortable="editable"
        :read-only="!editable"
        single-column
        tag="div"
        item-tag="div"
        list-class="grid gap-1"
        :item-class="() => 'matrixblock js-deletable'"
        :item-attrs="blockAttrs"
        :card-attrs="blockCardAttrs"
        @reorder="move"
        @item-click="(uid, event) => selection.handleClick(uid, event)"
      >
        <template #label="{id: uid}">
          <div
            class="blocktype flex flex-nowrap gap-1 items-center"
            :class="{error: hasErrors(uid)}"
          >
            <craft-icon v-if="blockIcon(uid)" v-bind="blockIcon(uid)!" />
            {{ entryType(uid)?.label ?? uid }}
            <craft-icon
              v-if="hasErrors(uid)"
              name="triangle-exclamation"
              :aria-label="t('Error')"
            />

            <div class="preview" v-if="isCollapsed(uid)">
              {{ previewText(uid) }}
            </div>
          </div>
        </template>

        <template #actions="{id: uid}">
          <craft-status
            v-if="isDisabled(uid)"
            status="disabled"
            :label="t('Disabled')"
          />
          <ActionMenu
            v-if="editable"
            :actions="blockActions(uid)"
            :label="
              t('{type} actions', {
                type: entryType(uid)?.label ?? uid,
              })
            "
          />
        </template>

        <template #default="{id}">
          <template v-if="editable">
            <input
              type="hidden"
              :name="`${inputName(control.path)}[sortOrder][]`"
              :value="id"
            />
            <input
              type="hidden"
              :name="`${inputName(control.path)}[entries][${id}][type]`"
              :value="model.entries[id]?.type ?? ''"
            />
          </template>
          <div class="fields">
            <template v-if="forms.get(id)">
              <FormNodeList
                :nodes="forms.get(id)!.nodes"
                :values="values"
                :errors="errors"
                :touched-paths="touchedPaths"
                :scope="forms.get(id)!.scope"
                :refreshable="forms.get(id)!.refreshable"
                @change="nestedChange($event, forms.get(id)!)"
              />
            </template>
            <craft-spinner v-else :label="t('Loading')" />
          </div>
        </template>
      </SelectableCardList>
      <div v-if="canAdd" ref="addArea" class="mt-3">
        <ActionMenu
          v-if="addFromMenu"
          :actions="addMenuActions"
          :searchable="addMenuSearchable"
          :label="control.props.addLabel"
        >
          <template #invoker="{attributes}">
            <craft-button
              v-bind="attributes"
              type="button"
              variant="dashed"
              icon="plus"
              :disabled="busy"
            >
              {{ control.props.addLabel }}
            </craft-button>
          </template>
        </ActionMenu>
        <div v-else ref="addButtons" class="flex flex-wrap gap-1 items-center">
          <craft-button
            v-for="type in control.props.entryTypes"
            :key="type.value"
            type="button"
            variant="dashed"
            :icon="type.icon?.name ?? 'plus'"
            :data-color="type.color ?? undefined"
            :loading="adding === type.value"
            :disabled="busy"
            :data-form-matrix-add="type.value"
            @click.stop.prevent="addBlock(type.value)"
          >
            {{
              control.props.entryTypes?.length === 1
                ? control.props.addLabel
                : t('Add {type}', {type: type.label})
            }}
          </craft-button>
        </div>
        <craft-button
          v-if="pasteable.length"
          type="button"
          variant="dashed"
          icon="duplicate"
          class="mt-1"
          :loading="pasting"
          :disabled="busy"
          @click.stop.prevent="pasteBlocks()"
        >
          {{
            t('Paste {type}', {
              type: pasteable.length === 1 ? t('block') : t('blocks'),
            })
          }}
        </craft-button>
      </div>
    </div>
  </craft-matrix-input>
</template>
