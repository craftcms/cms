import {
  Base,
  DragSort,
  closestRegistered,
  nearestSibling,
  type GarnishBaseSettings,
} from '@craftcms/garnish';
import {toHtmlElement} from '@craftcms/garnish/compat';
import type {
  CraftActionMenu,
  CraftReorderButton,
  ReorderDirection,
} from '@craftcms/ui';
import type {
  CraftComponentSelect,
  DefineChipActionsEventDetail,
} from '@/modules/component-select';
import {editEntryTypeOverrides} from './entry-type-override-settings';
import {groupedEntryTypeManagerData} from './support';

// `Craft` and `$` (jQuery) remain page globals; the manager leans on the same
// legacy seams as `craft-component-select` — `Craft.t`/`Craft.ui.icon` for the
// menu items it builds, `Craft.namespaceId`/`namespaceInputName` for the
// select-template cloning, and `Craft.sendActionRequest` +
// `Craft.ui.createSortableCheckboxSelect` (jQuery-returning) for the default
// table columns rebuild.
declare const Craft: any;
declare const $: any;

/**
 * Settings for {@link GroupedEntryTypeManager}. The legacy
 * `Craft.GroupedEntryTypeManager` settings keys are still accepted for
 * compatibility: `$defaultColumnsContainer` (jQuery) maps onto
 * `defaultColumnsContainer`, and `entryTypeSelectJs` is ignored (the
 * web-component select self-boots, so there is no JS blob to run).
 */
export interface GroupedEntryTypeManagerSettings extends GarnishBaseSettings {
  /**
   * The field settings' input namespace (legacy `settings.namespace`), used to
   * clone the select template and to name the rebuilt default-columns inputs.
   */
  namespace?: string | null;
  /**
   * Server-rendered `<craft-component-select>` markup for new groups, with
   * `TEMP_ID` placeholder ids (legacy `settings.entryTypeSelectHtml`).
   */
  entryTypeSelectHtml?: string | null;
  /**
   * The container the Default Table Columns checkbox select is rebuilt into
   * (legacy `settings.$defaultColumnsContainer`). May be a lazy resolver —
   * the container lives outside the manager's markup and may not be parsed
   * yet when the manager boots.
   */
  defaultColumnsContainer?: Element | (() => Element | null) | null;
  /**
   * Whether entry-type chips get the per-field override editor (legacy
   * `EntryTypeSelectInput` `allowOverrides`). Drives the chip "Settings" action
   * — see {@link handleDefineChipActions}. Set from the `allow-overrides`
   * attribute on `<craft-entry-type-manager>`.
   */
  allowOverrides?: boolean;
}

/** Group `li` → its {@link Group} instance (latest boot wins). */
const groupData = new WeakMap<Element, Group>();

/** Containers that already have the `define-chip-actions` listener. */
const chipActionsAttached = new WeakSet<Element>();

/** The `.entry-type-group` selector, shared by the sibling-group lookups. */
const GROUP_SELECTOR = 'li.entry-type-group';

/**
 * Class of the empty sentinel `<li>` appended to each group's chip list. It is
 * a valid drop target for the cross-group {@link GroupedEntryTypeManager.chipSort}
 * — giving an end-of-list, and crucially an *empty group*, somewhere to drop —
 * but is gated out of `canInsertAfter` so a chip can only land *before* it
 * (legacy `entry-type-group--caboose`).
 */
const CABOOSE_CLASS = 'entry-type-group--caboose';

/** The adjacent `.entry-type-group` in the given direction, or `null`. */
function siblingGroup(
  group: HTMLElement,
  direction: 'previous' | 'next'
): HTMLElement | null {
  return nearestSibling(group, GROUP_SELECTOR, direction);
}

/** The manager owning an element, resolved through the `support.ts` registry. */
function managerFor(el: Element): GroupedEntryTypeManager | null {
  return closestRegistered(el, groupedEntryTypeManagerData);
}

/**
 * Whether a chip's entry type may be overridden per-field. Read statelessly —
 * chips are wired by their select's own boot, which can run before the manager
 * boots (see {@link attachChipMoveActions}) — so it prefers the
 * `<craft-entry-type-manager>` element's `allow-overrides` attribute (always in
 * the DOM), falling back to the manager's setting for standalone construction.
 */
function overridesAllowed(chip: HTMLElement): boolean {
  const el = chip.closest<HTMLElement>('craft-entry-type-manager');
  if (el) {
    return el.hasAttribute('allow-overrides');
  }
  return !!managerFor(chip)?.settings.allowOverrides;
}

/** Rewrite the `group` key in a chip's hidden-input JSON value. */
function setChipGroupValue(chip: HTMLElement, name: string): void {
  const input = chip.querySelector('input');
  if (!input) {
    return;
  }
  try {
    const value = JSON.parse(input.value);
    value.group = name;
    input.value = JSON.stringify(value);
  } catch {
    // Not a JSON value (plugin-rendered chip?) — leave it alone.
  }
}

/**
 * Contribute the "Move to previous/next group" items to a chip's action menu
 * while its `craft-component-select` wires it (legacy
 * `GroupedEntryTypeSelectInput.defineComponentActions`), positioned before the
 * select's built-in Replace/Remove. Stateless: the initial `hidden` state
 * comes from the DOM at wiring time ({@link GroupedEntryTypeManager.refresh}
 * keeps it current afterwards), and activation resolves the manager through
 * the `support.ts` registry — so the listener can be attached before the
 * manager instance exists and survives a destroy/re-boot.
 */
function handleDefineChipActions(
  ev: CustomEvent<DefineChipActionsEventDetail>
): void {
  const {chip, actions} = ev.detail;
  const group = chip.closest<HTMLElement>('li.entry-type-group');
  if (!group) {
    return;
  }

  if (overridesAllowed(chip)) {
    // The chip's server-rendered "Entry type settings" item edits the *shared*
    // entry type globally; when per-field overrides are on it's replaced with a
    // Settings action scoped to this field (legacy
    // `EntryTypeSelectInput.addComponentInternal`'s `allowOverrides` branch,
    // which likewise hid `[data-edit-action]`).
    chip.querySelector('[data-edit-action]')?.setAttribute('hidden', '');
    actions.push({
      icon: 'gear',
      label: Craft.t('app', 'Settings'),
      onActivate: () => void editEntryTypeOverrides(chip),
    });
  }

  const ltr = Craft.orientation !== 'rtl';

  actions.push(
    {
      icon: ltr ? 'arrow-left' : 'arrow-right',
      label: Craft.t('app', 'Move to previous group'),
      onActivate: () => managerFor(chip)?.moveChipToGroup(chip, 'previous'),
      attributes: {
        'data-move-to-previous-group': true,
        hidden: !siblingGroup(group, 'previous') || null,
      },
    },
    {
      icon: ltr ? 'arrow-right' : 'arrow-left',
      label: Craft.t('app', 'Move to next group'),
      onActivate: () => managerFor(chip)?.moveChipToGroup(chip, 'next'),
      attributes: {
        'data-move-to-next-group': true,
        hidden: !siblingGroup(group, 'next') || null,
      },
    }
  );
}

/**
 * Attach the chip move-action listener to a manager container (idempotent).
 * `<craft-entry-type-manager>` calls this from its `connectedCallback` — ahead
 * of its deferred boot — so the listener is in place before any child select
 * boots and wires its chips; {@link GroupedEntryTypeManager.init} calls it too
 * for standalone (legacy-shim) construction. The listener stays for the
 * container's lifetime: it is stateless (see {@link handleDefineChipActions}),
 * so there is nothing to tear down on destroy.
 */
export function attachChipMoveActions(container: Element): void {
  if (chipActionsAttached.has(container)) {
    return;
  }
  chipActionsAttached.add(container);
  // SAFETY: This named handler implements EventListener's single Event parameter contract.
  container.addEventListener(
    'define-chip-actions',
    handleDefineChipActions as EventListener
  );
}

/**
 * Grouped entry type manager — a port of `Craft.GroupedEntryTypeManager` (+
 * the cross-group parts of `Craft.GroupedEntryTypeSelectInput`) onto
 * `@craftcms/garnish` `Base`, orchestrating the self-booting
 * `<craft-component-select>` elements instead of the legacy jQuery
 * `ComponentSelectInput` classes. Setup lives in {@link init}, invoked from
 * the constructor only for the leaf class (`new.target` guard) — the same
 * construction contract as the other ports.
 *
 * The container holds `ul.entry-type-groups > li.entry-type-group[data-name]`,
 * each with a `.entry-type-group--titlebar` (`span` heading) and a select of
 * entry-type chips; each group gets a {@link Group}. Manager-level behavior:
 * the Add Group button, the group `DragSort` (each titlebar's
 * `<craft-reorder-button>` is the handle), the cross-group chip `DragSort`
 * (see {@link initChipSort}), cross-group Choose-menu option sync (an entry
 * type selected in ANY group is hidden in EVERY group's menu — driven from the
 * selects' bubbling `change` events rather than a select subclass), per-chip
 * "Move to previous/next group" actions (see {@link attachChipMoveActions}),
 * group-aware `{id, group}` hidden-input values (each select's `getInputValue`
 * hook), and the Default Table Columns rebuild on membership changes.
 *
 * Cross-group chip drag (legacy `Craft.GroupedEntryTypeSelectInput` +
 * `entry-type-group--caboose`): a chip can be *dragged* from one group into
 * another. Each `craft-component-select` runs its own chip `DragSort`, and one
 * per-select sorter can't drop into a sibling's list, so the manager takes
 * ownership — each child select {@link CraftComponentSelect.releaseSort
 * releases its sorter} and the manager runs a single {@link chipSort} across
 * every group's `ul.components` (see {@link initChipSort} /
 * {@link syncChipSort}). The menu-based moves stay for touch, where there is
 * no `DragSort` at all.
 */
export class GroupedEntryTypeManager extends Base<GroupedEntryTypeManagerSettings> {
  container: HTMLElement | null = null;
  override settings: GroupedEntryTypeManagerSettings = {};
  groupsList: HTMLUListElement | null = null;
  addGroupBtn: HTMLElement | null = null;
  groupSort: any = null;
  chipSort: any = null;

  constructor(container?: any, settings?: any) {
    super();
    if (new.target === GroupedEntryTypeManager) {
      this.init(container, settings);
    }
  }

  init(container: any, settings: any = {}): void {
    this.container = toHtmlElement(container);
    this.settings = {
      namespace: settings?.namespace ?? null,
      entryTypeSelectHtml: settings?.entryTypeSelectHtml ?? null,
      // `$defaultColumnsContainer` is the legacy (jQuery) key.
      defaultColumnsContainer:
        settings?.defaultColumnsContainer ??
        toHtmlElement(settings?.$defaultColumnsContainer),
      allowOverrides: settings?.allowOverrides ?? false,
    };

    if (!this.container) {
      return;
    }

    this.groupsList = this.container.querySelector('ul.entry-type-groups');
    if (!this.groupsList) {
      return;
    }

    // Object back-reference for event-time resolution (the chip actions) and
    // external consumers; replaces the legacy `$container.data(...)` write —
    // nothing legacy reads that anymore.
    groupedEntryTypeManagerData.set(this.container, this);

    // Usually already attached by `<craft-entry-type-manager>`; needed here
    // for standalone construction.
    attachChipMoveActions(this.container);

    this.container.addEventListener('change', this.handleChange);

    this.initAddGroupBtn();
    this.initGroupSort();
    this.initChipSort();

    for (const el of this.groupEls()) {
      this.initGroup(el);
      this.groupSort?.addItems(el);
    }

    // First cross-group sync once every select has booted (they wire their own
    // chips first; the union of selected ids isn't complete until then).
    this.whenSelectsReady(() => this.refresh());
  }

  /**
   * Detach listeners and dispose the group sorter so the controller can be
   * re-booted. The groups' titlebar wiring stays on their DOM (it resolves the
   * live {@link Group} through `groupData` at event time), as does the
   * container's `define-chip-actions` listener (stateless; resolves through
   * the registry, which this clears).
   */
  override destroy(): void {
    this.container?.removeEventListener('change', this.handleChange);
    this.addGroupBtn?.removeEventListener('click', this.handleAddGroupClick);

    this.groupSort?.destroy?.();
    this.groupSort = null;

    this.chipSort?.destroy?.();
    this.chipSort = null;

    if (this.container) {
      groupedEntryTypeManagerData.delete(this.container);
    }

    super.destroy();
  }

  // --- Groups ---------------------------------------------------------------------

  initGroup(el: HTMLElement): Group {
    return new Group(this, el);
  }

  /** The group `li`s, in DOM order. */
  groupEls(): HTMLElement[] {
    if (!this.groupsList) {
      return [];
    }
    return Array.from(
      this.groupsList.querySelectorAll<HTMLElement>(
        ':scope > li.entry-type-group'
      )
    );
  }

  /** The {@link Group} instances, in DOM order. */
  get groups(): Group[] {
    return this.groupEls()
      .map((el) => groupData.get(el))
      .filter((group): group is Group => group !== undefined);
  }

  /** Every group's select, in group order. */
  selects(): CraftComponentSelect[] {
    return this.groups
      .map((group) => group.select)
      .filter((select): select is CraftComponentSelect => select !== null);
  }

  /** The dashed Add Group button (legacy `$addGroupBtn`), created once. */
  initAddGroupBtn(): void {
    if (!this.container) {
      return;
    }

    let btn = this.container.querySelector<HTMLElement>(
      ':scope > .add-group-btn'
    );
    if (!btn) {
      btn = document.createElement('craft-button');
      btn.setAttribute('type', 'button');
      btn.setAttribute('icon', 'plus');
      btn.setAttribute('appearance', 'outline');
      btn.setAttribute('--command', 'add-group');
      btn.textContent = Craft.t('app', 'Add Group');
      this.container.append(btn);
    }
    // (Re-)attach even when the button survived a destroy/re-boot — destroy
    // removed the previous instance's listener.
    btn.addEventListener('click', this.handleAddGroupClick);
    this.addGroupBtn = btn;

    // Without a select template there is nothing to add (defensive; the Twig
    // always renders it on the editable path).
    this.addGroupBtn.classList.toggle(
      'hidden',
      !this.settings.entryTypeSelectHtml
    );
  }

  handleAddGroupClick = (): void => {
    this.addGroup();
  };

  /**
   * Drag-to-sort on the groups themselves. The titlebar's
   * `<craft-reorder-button>` is the handle — scoped so the chips' reorder
   * buttons inside each group's select can't grab a whole group. On touch
   * there's no sorter; the reorder button's menu handles moving.
   */
  initGroupSort(): void {
    if (this.groupSort || !Craft.hasMousePointerEvents()) {
      return;
    }

    this.groupSort = new DragSort({
      container: this.groupsList,
      handle:
        ':scope > .entry-type-group--titlebar > .entry-type-group--actions > craft-reorder-button',
      ignoreHandleSelector: null,
      magnetStrength: 4,
      helperLagBase: 1.5,
    });
    this.groupSort.on('sortChange', () => this.refresh());
  }

  /**
   * One `DragSort` spanning every group's chip list (legacy `entryTypeSort`),
   * so a chip can be dragged between groups. The chips' `<craft-reorder-button>`
   * is the handle — matching each `craft-component-select`'s own chip sort,
   * whose ownership the manager takes over ({@link syncChipSort} calls
   * {@link CraftComponentSelect.releaseSort} on each). No axis lock: groups flow
   * horizontally while chips stack vertically, so drops are 2-D. The
   * `entry-type-group--caboose` sentinels are valid drop targets but never
   * insert-after (so a chip lands *before* them, i.e. inside the group). On
   * touch there's no sorter; the reorder button's menu handles moving. Items
   * are (un)registered lazily in {@link syncChipSort}.
   */
  initChipSort(): void {
    if (this.chipSort || !Craft.hasMousePointerEvents()) {
      return;
    }

    this.chipSort = new DragSort({
      container: this.groupsList,
      handle: 'craft-reorder-button',
      collapseDraggees: true,
      magnetStrength: 4,
      helperLagBase: 1.5,
      canInsertAfter: (item: HTMLElement) =>
        !item.classList.contains(CABOOSE_CLASS),
    });
    this.chipSort.on('sortChange', () => this.refresh());
  }

  /** Add Group (legacy `addGroup`): prompt for a name, clone the select template. */
  addGroup(): void {
    if (!this.groupsList || !this.settings.entryTypeSelectHtml) {
      return;
    }

    const name = prompt(Craft.t('app', 'Group Name'));
    if (name === null || name === '') {
      return;
    }

    const el = document.createElement('li');
    el.className = 'entry-type-group';
    el.dataset.name = name;

    const titlebar = document.createElement('div');
    titlebar.className = 'entry-type-group--titlebar';
    const heading = document.createElement('span');
    heading.textContent = name;
    titlebar.append(heading);
    el.append(titlebar);

    // Swap the template's namespaced TEMP_ID placeholders for a unique
    // namespaced id, then let the cloned `<craft-component-select>` self-boot.
    const namespace = this.settings.namespace ?? null;
    const tempId = Craft.namespaceId('TEMP_ID', namespace);
    const id = Craft.namespaceId(
      `entry-type-select-${Math.floor(Math.random() * 1000000)}`,
      namespace
    );
    el.insertAdjacentHTML(
      'beforeend',
      this.settings.entryTypeSelectHtml.replaceAll(tempId, id)
    );

    this.groupsList.append(el);
    this.initGroup(el);
    this.groupSort?.addItems(el);

    // Hide the other groups' selections in the new select (and set its
    // reorder position) once it has booted.
    this.whenSelectsReady(() => this.refresh());
  }

  // --- Chips ----------------------------------------------------------------------

  /**
   * Move a chip into the adjacent group (legacy
   * `moveEntryTypeToPreviousGroup`/`moveEntryTypeToNextGroup`): the target
   * select adopts the chip's `li` — both selects' observers transfer sorter
   * membership and reorder positions — and the chip's input JSON gets the new
   * group name.
   */
  moveChipToGroup(chip: HTMLElement, direction: 'previous' | 'next'): void {
    const li = chip.closest('li');
    const groupEl = chip.closest<HTMLElement>('li.entry-type-group');
    if (!li || !groupEl) {
      return;
    }

    const target = siblingGroup(groupEl, direction);
    const targetSelect = target ? groupData.get(target)?.select : null;
    if (!target || !targetSelect) {
      return;
    }

    targetSelect.adoptChip(li);
    setChipGroupValue(chip, target.dataset.name ?? '');
    this.refresh();
  }

  // --- Sync -----------------------------------------------------------------------

  /**
   * Membership somewhere changed (a select's bubbling `change`): re-sync the
   * cross-group state and rebuild the default table columns (legacy `Group`'s
   * `componentSelect.on('change')`). Native `change` events from other
   * controls inside the container (e.g. a Choose menu's search input) are
   * ignored.
   */
  handleChange = (ev: Event): void => {
    if (
      !(ev.target instanceof Element) ||
      !ev.target.matches('craft-component-select')
    ) {
      return;
    }
    this.refresh();
    void this.updateDefaultColumns();
  };

  /**
   * Recompute all cross-group state: each group's position-dependent state
   * (via {@link Group.refresh}), and the Choose-menu option visibility — an
   * entry type selected in ANY group is hidden in EVERY group's menu (legacy
   * `showOption`/`hideOption` propagation).
   */
  refresh(): void {
    const groups = this.groups;
    groups.forEach((group, index) => group.refresh(index, groups.length));

    // Keep the cross-group chip sorter's items + cabooses in step with the
    // current DOM (new/removed chips, added/removed groups).
    this.syncChipSort();

    // Choose-menu options: hidden iff selected in any group. Option ids are
    // enumerated from the select's light DOM (the Choose menu's
    // `craft-action-item[data-id]`s — items inside chips' action menus don't
    // count), then toggled through the select's public API so its add/create
    // button visibility stays correct.
    const selects = this.selects();
    const selected = new Set(
      selects.flatMap((select) => select.selectedIds.map(String))
    );
    for (const select of selects) {
      const options = Array.from(
        select.querySelectorAll<HTMLElement>('craft-action-item[data-id]')
      ).filter((option) => !option.closest('craft-chip'));
      for (const option of options) {
        const id = option.dataset.id ?? '';
        if (selected.has(id)) {
          select.hideOption(id);
        } else {
          select.showOption(id);
        }
      }
    }
  }

  /**
   * Bring {@link chipSort} in line with the current DOM: take sort ownership
   * from each child select (they defer to this manager-level sorter, like the
   * legacy `GroupedEntryTypeSelectInput.initComponentSort` no-op), make sure
   * every group's list ends in a caboose drop-target sentinel, and register
   * every chip `li` + caboose as a sort item — dropping any that have gone away
   * (removed chips, a caboose whose group is gone). `$items` is re-sorted into
   * DOM order afterward so the sorter's prev/next walk stays correct across the
   * separate group lists. No-op on touch, where {@link chipSort} is `null`.
   */
  syncChipSort(): void {
    if (!this.chipSort) {
      return;
    }

    const wanted: HTMLElement[] = [];

    for (const group of this.groups) {
      const select = group.select;
      const list = select?.querySelector<HTMLElement>(':scope > ul');
      if (!select || !list) {
        continue;
      }

      // Hand chip sorting to this manager (idempotent — safe every refresh,
      // and re-releases a select that re-booted its own sorter).
      select.releaseSort();

      for (const chip of group.chips()) {
        const li = chip.closest<HTMLElement>('li');
        if (li) {
          wanted.push(li);
        }
      }

      wanted.push(this.ensureCaboose(list));
    }

    // Drop stale items first (so a released select's handles are free), then
    // register newcomers; both calls are idempotent.
    const keep = new Set(wanted);
    // SAFETY: chipSort is initialized only with HTMLElement chip items.
    for (const item of [...(this.chipSort.$items as HTMLElement[])]) {
      if (!keep.has(item)) {
        this.chipSort.removeItems(item);
      }
    }
    this.chipSort.addItems(wanted);

    // `addItems` appends newcomers, so re-sort into DOM order for the prev/next
    // walk (the sorter only re-sorts once a drag actually moves something).
    // SAFETY: chipSort is initialized only with HTMLElement chip items.
    (this.chipSort.$items as HTMLElement[]).sort((a, b) =>
      a.compareDocumentPosition(b) & Node.DOCUMENT_POSITION_FOLLOWING ? -1 : 1
    );
  }

  /**
   * Ensure `list` (a group's `ul.components`) ends in a
   * {@link CABOOSE_CLASS} sentinel `<li>`, creating it if missing and keeping
   * it last (a Choose-menu add appends the new chip after it). Returns the
   * caboose so {@link syncChipSort} can register it as a drop target.
   */
  ensureCaboose(list: HTMLElement): HTMLElement {
    let caboose = list.querySelector<HTMLElement>(
      `:scope > li.${CABOOSE_CLASS}`
    );
    if (!caboose) {
      caboose = document.createElement('li');
      caboose.className = CABOOSE_CLASS;
      caboose.setAttribute('aria-hidden', 'true');
    }
    if (caboose !== list.lastElementChild) {
      list.append(caboose);
    }
    return caboose;
  }

  /**
   * Rebuild the Default Table Columns checkbox select from the current
   * membership (legacy `updateDefaultColumns`), preserving the checked values.
   */
  async updateDefaultColumns(): Promise<void> {
    const {defaultColumnsContainer} = this.settings;
    const container =
      defaultColumnsContainer instanceof Function
        ? defaultColumnsContainer()
        : (defaultColumnsContainer ?? null);
    if (!container) {
      return;
    }

    const values = Array.from(
      container.querySelectorAll<HTMLInputElement>('input:checked')
    ).map((input) => input.value);

    try {
      const {data} = await Craft.sendActionRequest(
        'POST',
        'matrix/default-table-column-options',
        {
          data: {
            entryTypeIds: this.selects().flatMap(
              (select) => select.selectedIds
            ),
          },
        }
      );

      $(container)
        .empty()
        .append(
          Craft.ui.createSortableCheckboxSelect({
            name: Craft.namespaceInputName(
              'defaultTableColumns',
              this.settings.namespace ?? null
            ),
            options: data.options,
            values,
          })
        );
    } catch {
      // Same silence as the legacy implementation — the next membership
      // change retries.
    }
  }

  /**
   * Run `callback` once every group's select reports `initialized` (rAF-polled
   * — the selects have their own retrying boots), bailing if the container
   * leaves the document meanwhile.
   */
  whenSelectsReady(callback: () => void): void {
    const poll = (): void => {
      if (!this.container?.isConnected) {
        return;
      }
      if (this.selects().some((select) => !select.initialized)) {
        requestAnimationFrame(poll);
        return;
      }
      callback();
    };
    poll();
  }
}

/**
 * A single entry type group within a {@link GroupedEntryTypeManager} — the
 * port of the legacy `Craft.GroupedEntryTypeManager.Group`. Owns the titlebar
 * controls: a `craft-action-menu` (Rename / Remove) plus a horizontal
 * `<craft-reorder-button>` that is both the group's drag handle and its Move
 * forward/backward menu (the groups flow horizontally, wrapping — this
 * replaces the legacy `.move` handle and the menu's Move backward/forward
 * items). Also installs the select's `getInputValue` hook so new chips carry
 * `{id, group}` JSON (legacy `GroupedEntryTypeSelectInput.renderSettings`).
 *
 * The titlebar wiring is created only when missing (a destroy/re-boot keeps
 * the DOM), and its handlers resolve the *live* Group through `groupData` at
 * event time, so a re-booted manager's instances take over seamlessly.
 */
export class Group extends Base {
  manager: GroupedEntryTypeManager;
  container: HTMLElement;

  constructor(manager?: GroupedEntryTypeManager, container?: any) {
    super();
    // Assigned here (not just in init) so TS sees them as definitely assigned.
    this.manager = manager!;
    this.container = toHtmlElement(container)!;
    if (new.target === Group) {
      this.init(manager!, container);
    }
  }

  init(manager: GroupedEntryTypeManager, container: any): void {
    this.manager = manager;
    this.container = toHtmlElement(container)!;

    groupData.set(this.container, this);

    const titlebar = this.container.querySelector<HTMLElement>(
      ':scope > .entry-type-group--titlebar'
    );
    if (titlebar && !titlebar.querySelector('.entry-type-group--actions')) {
      const actions = document.createElement('div');
      actions.className = 'entry-type-group--actions';
      actions.append(this.buildActionMenu(), this.buildReorderButton());
      titlebar.append(actions);
    }

    // New chips' hidden inputs carry `{id, group}` JSON. Resolved live so
    // renames and group moves are always reflected.
    const select = this.select;
    if (select) {
      select.getInputValue = (id) => {
        const numeric = Number(id);
        return JSON.stringify({
          id: id !== '' && !Number.isNaN(numeric) ? numeric : id,
          group: this.container.dataset.name ?? '',
        });
      };
    }
  }

  /** The group's name (the `data-name` attribute, kept current by {@link rename}). */
  get name(): string {
    return this.container.dataset.name ?? '';
  }

  /** The group's `craft-component-select`, or `null` (e.g. mid-parse). */
  get select(): CraftComponentSelect | null {
    return this.container.querySelector('craft-component-select');
  }

  /** The group's chips, skipping any mid removal-fade. */
  chips(): HTMLElement[] {
    return this.select?.chips ?? [];
  }

  /** The titlebar's Rename / Remove menu, in data-driven `.actions` mode. */
  buildActionMenu(): CraftActionMenu {
    // No global tag-map entry for the cp components, hence the cast.
    // SAFETY: The registered craft-action-menu custom element implements CraftActionMenu.
    const menu = document.createElement('craft-action-menu') as CraftActionMenu;
    const container = this.container;

    // Items generated from `.actions` live in the menu's content container,
    // which doesn't auto-close on click — dispatch `close-overlay` manually,
    // mirroring `Craft.addActionsToChip`.
    const close = (ev: Event): void => {
      if (!(ev.target instanceof Element)) {
        return;
      }
      ev.target.dispatchEvent(new Event('close-overlay', {bubbles: true}));
    };

    menu.actions = [
      {
        icon: 'pencil',
        label: Craft.t('app', 'Rename'),
        onClick: (ev: Event) => {
          close(ev);
          groupData.get(container)?.rename();
        },
      },
      {type: 'hr'},
      {
        icon: 'trash',
        label: Craft.t('app', 'Remove'),
        variant: 'danger',
        onClick: (ev: Event) => {
          close(ev);
          groupData.get(container)?.remove();
        },
      },
    ];

    return menu;
  }

  /**
   * The titlebar's `<craft-reorder-button>` — drag handle + move menu.
   * `position`/`disabled` are kept current by {@link refresh}.
   */
  buildReorderButton(): CraftReorderButton {
    // SAFETY: The registered craft-reorder-button element implements CraftReorderButton.
    const btn = document.createElement(
      'craft-reorder-button'
    ) as CraftReorderButton;
    const container = this.container;
    btn.setAttribute('orientation', 'horizontal');
    btn.addEventListener('craft-reorder', (event: Event) => {
      // SAFETY: craft-reorder-button emits this registered detail contract.
      const {direction} = (event as CustomEvent<{direction: ReorderDirection}>)
        .detail;
      groupData.get(container)?.move(direction);
    });
    return btn;
  }

  /** Rename (legacy `showNamePrompt`): heading, `data-name`, chip JSON. */
  rename(): void {
    const name = prompt(Craft.t('app', 'Group Name'), this.name);
    if (name === null || name === '') {
      return;
    }

    this.container.dataset.name = name;

    const heading = this.container.querySelector(
      ':scope > .entry-type-group--titlebar > span'
    );
    if (heading) {
      heading.textContent = name;
    }

    for (const chip of this.chips()) {
      setChipGroupValue(chip, name);
    }
  }

  /**
   * Remove (legacy `remove`): drop the `li` — its select tears itself down on
   * disconnect — then restore its selections to the other groups' Choose menus
   * and rebuild the default table columns.
   */
  remove(): void {
    this.manager.groupSort?.removeItems(this.container);
    this.container.remove();
    this.manager.refresh();
    void this.manager.updateDefaultColumns();
    this.destroy();
  }

  /** Move the group one slot toward the start (`'up'`) or the end (`'down'`). */
  move(direction: ReorderDirection): void {
    const sibling = siblingGroup(
      this.container,
      direction === 'up' ? 'previous' : 'next'
    );
    if (!sibling) {
      return;
    }

    if (direction === 'up') {
      sibling.before(this.container);
    } else {
      sibling.after(this.container);
    }

    this.manager.refresh();
  }

  /**
   * Recompute this group's position-dependent state: the reorder button's
   * `position`/`disabled`, the chips' `{group}` hidden-input value (so a chip
   * dragged in from another group picks up this group's name — legacy
   * `refresh`), and the chips' "Move to previous/next group" item visibility
   * (hidden at the ends).
   */
  refresh(index: number, total: number): void {
    const btn = this.container.querySelector(
      ':scope > .entry-type-group--titlebar craft-reorder-button'
    );
    btn?.toggleAttribute('disabled', total < 2);
    btn?.setAttribute(
      'position',
      index === 0 ? 'first' : index === total - 1 ? 'last' : 'middle'
    );

    for (const chip of this.chips()) {
      setChipGroupValue(chip, this.name);
      chip
        .querySelector('[data-move-to-previous-group]')
        ?.toggleAttribute('hidden', index === 0);
      chip
        .querySelector('[data-move-to-next-group]')
        ?.toggleAttribute('hidden', index === total - 1);
    }
  }
}
