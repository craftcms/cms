import {
  Base,
  DragSort,
  closestRegistered,
  nearestSibling,
  type GarnishBaseSettings,
} from '@craftcms/garnish';
import {toHtmlElement} from '@craftcms/garnish/compat';
import type {ReorderDirection} from '@craftcms/ui';
import {html, render, type TemplateResult} from 'lit';
import {unsafeHTML} from 'lit/directives/unsafe-html.js';
import type {
  CraftComponentSelect,
  DefineChipActionsEventDetail,
} from '@/modules/component-select';
import {editEntryTypeOverrides} from './entry-type-override-settings';
import {groupedEntryTypeManagerData} from './support';

declare const Craft: any;
declare const $: any;

/** `$defaultColumnsContainer` is accepted as a jQuery alias. */
export interface GroupedEntryTypeManagerSettings extends GarnishBaseSettings {
  /** Applied to cloned select ids and the default-columns inputs. */
  namespace?: string | null;
  /** Select markup for new groups, with `TEMP_ID` placeholder ids. */
  entryTypeSelectHtml?: string | null;
  /** May be a resolver, since it can be parsed after the manager boots. */
  defaultColumnsContainer?: Element | (() => Element | null) | null;
  /** Whether chips can override the entry type for this field. */
  allowOverrides?: boolean;
}

/** Re-booting replaces each group's entry. */
const groupData = new WeakMap<Element, Group>();

const chipActionsAttached = new WeakSet<Element>();

const GROUP_SELECTOR = 'li.entry-type-group';

/**
 * An empty last item in each chip list, so a chip can be dropped at the end
 * of a group, even an empty one.
 */
const CABOOSE_CLASS = 'entry-type-group--caboose';

/**
 * Render a template into a fresh fragment. Lit keeps one render per container,
 * so rendering straight into a shared parent would replace earlier output.
 */
function renderFragment(template: TemplateResult): DocumentFragment {
  const fragment = document.createDocumentFragment();
  render(template, fragment);
  return fragment;
}

function siblingGroup(
  group: HTMLElement,
  direction: 'previous' | 'next'
): HTMLElement | null {
  return nearestSibling(group, GROUP_SELECTOR, direction);
}

function managerFor(el: Element): GroupedEntryTypeManager | null {
  return closestRegistered(el, groupedEntryTypeManagerData);
}

/** Reads the attribute, since chips can be wired before the manager boots. */
function overridesAllowed(chip: HTMLElement): boolean {
  const el = chip.closest<HTMLElement>('craft-entry-type-manager');
  if (el) {
    return el.hasAttribute('allow-overrides');
  }
  return !!managerFor(chip)?.settings.allowOverrides;
}

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
    // Not a JSON value; leave it alone.
  }
}

/** Stateless, so it works before the manager boots and survives a re-boot. */
function handleDefineChipActions(
  ev: CustomEvent<DefineChipActionsEventDetail>
): void {
  const {chip, actions} = ev.detail;
  const group = chip.closest<HTMLElement>('li.entry-type-group');
  if (!group) {
    return;
  }

  if (overridesAllowed(chip)) {
    // The built-in edit action changes the shared entry type; overrides apply
    // to this field only.
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

/** Called before the manager boots, so chips wired early get move actions. */
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
 * Chip sorting is owned here rather than by each select, since a per-select
 * sorter can't drop into another group's list.
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

    groupedEntryTypeManagerData.set(this.container, this);

    // The element attaches this earlier; needed for standalone construction.
    attachChipMoveActions(this.container);

    this.container.addEventListener('change', this.handleChange);

    this.initAddGroupBtn();
    this.initGroupSort();
    this.initChipSort();

    for (const el of this.groupEls()) {
      this.initGroup(el);
      this.groupSort?.addItems(el);
    }

    // Selected ids aren't complete until every select has booted.
    this.whenSelectsReady(() => this.refresh());
  }

  /** Titlebar wiring stays in the DOM and resolves the live Group. */
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

  initGroup(el: HTMLElement): Group {
    return new Group(this, el);
  }

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

  get groups(): Group[] {
    return this.groupEls()
      .map((el) => groupData.get(el))
      .filter((group): group is Group => group !== undefined);
  }

  selects(): CraftComponentSelect[] {
    return this.groups
      .map((group) => group.select)
      .filter((select): select is CraftComponentSelect => select !== null);
  }

  initAddGroupBtn(): void {
    if (!this.container) {
      return;
    }

    let btn = this.container.querySelector<HTMLElement>(
      ':scope > .add-group-btn'
    );
    if (!btn) {
      btn = renderFragment(html`
        <craft-button
          class="add-group-btn"
          type="button"
          icon="plus"
          variant="dashed"
          --command="add-group"
          >${Craft.t('app', 'Add Group')}</craft-button
        >
      `).querySelector<HTMLElement>('craft-button')!;
      this.container.append(btn);
    }
    // The button survives a re-boot, but destroy removed its listener.
    btn.addEventListener('click', this.handleAddGroupClick);
    this.addGroupBtn = btn;

    this.addGroupBtn.classList.toggle(
      'hidden',
      !this.settings.entryTypeSelectHtml
    );
  }

  handleAddGroupClick = (): void => {
    this.addGroup();
  };

  /** On touch there's no sorter; the reorder button's menu handles moves. */
  initGroupSort(): void {
    if (this.groupSort || !Craft.hasMousePointerEvents()) {
      return;
    }

    this.groupSort = new DragSort({
      container: this.groupsList,
      // Scoped so the chips' reorder buttons can't grab a whole group.
      handle:
        ':scope > .entry-type-group--titlebar > .entry-type-group--actions > craft-reorder-button',
      ignoreHandleSelector: null,
      magnetStrength: 4,
      helperLagBase: 1.5,
    });
    this.groupSort.on('sortChange', () => this.refresh());
  }

  /** No axis lock: groups flow horizontally while chips stack vertically. */
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
      // Chips land before a caboose, keeping them inside the group.
      canInsertAfter: (item: HTMLElement) =>
        !item.classList.contains(CABOOSE_CLASS),
    });
    this.chipSort.on('sortChange', () => this.refresh());
  }

  addGroup(): void {
    if (!this.groupsList || !this.settings.entryTypeSelectHtml) {
      return;
    }

    const name = prompt(Craft.t('app', 'Group Name'));
    if (name === null || name === '') {
      return;
    }

    // Each cloned select needs its own id in place of TEMP_ID.
    const namespace = this.settings.namespace ?? null;
    const tempId = Craft.namespaceId('TEMP_ID', namespace);
    const id = Craft.namespaceId(
      `entry-type-select-${Math.floor(Math.random() * 1000000)}`,
      namespace
    );
    const selectHtml = this.settings.entryTypeSelectHtml.replaceAll(tempId, id);

    const el = renderFragment(html`
      <li class="entry-type-group" data-name=${name}>
        <div class="entry-type-group--titlebar"><span>${name}</span></div>
        ${unsafeHTML(selectHtml)}
      </li>
    `).querySelector<HTMLElement>('li')!;

    this.groupsList.append(el);
    this.initGroup(el);
    this.groupSort?.addItems(el);

    // The new select can only sync once it has booted.
    this.whenSelectsReady(() => this.refresh());
  }

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

  /** Ignores native `change` events, such as from the menu's search input. */
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

  /** An entry type selected in any group is hidden from every Choose menu. */
  refresh(): void {
    const groups = this.groups;
    groups.forEach((group, index) => group.refresh(index, groups.length));

    this.syncChipSort();

    // Toggled through the select so its Choose button visibility stays in sync.
    const selects = this.selects();
    const selected = new Set(
      selects.flatMap((select) => select.selectedIds.map(String))
    );
    for (const select of selects) {
      // Skip the chips' own action menu items.
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

    this.announceChange();
  }

  /** Chip values are rewritten in place, which fires no event of its own. */
  announceChange(): void {
    this.container?.dispatchEvent(new Event('change', {bubbles: true}));
  }

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

      // Also re-releases a select that re-booted its own sorter.
      select.releaseSort();

      for (const chip of group.chips()) {
        const li = chip.closest<HTMLElement>('li');
        if (li) {
          wanted.push(li);
        }
      }

      wanted.push(this.ensureCaboose(list));
    }

    // Remove stale items first so their handles are free.
    const keep = new Set(wanted);
    // SAFETY: chipSort is initialized only with HTMLElement chip items.
    for (const item of [...(this.chipSort.$items as HTMLElement[])]) {
      if (!keep.has(item)) {
        this.chipSort.removeItems(item);
      }
    }
    this.chipSort.addItems(wanted);

    // `addItems` appends, but the sorter walks items in DOM order.
    // SAFETY: chipSort is initialized only with HTMLElement chip items.
    (this.chipSort.$items as HTMLElement[]).sort((a, b) =>
      a.compareDocumentPosition(b) & Node.DOCUMENT_POSITION_FOLLOWING ? -1 : 1
    );
  }

  /** Kept last, since chips added from the Choose menu are appended. */
  ensureCaboose(list: HTMLElement): HTMLElement {
    let caboose = list.querySelector<HTMLElement>(
      `:scope > li.${CABOOSE_CLASS}`
    );
    caboose ??= renderFragment(
      html`<li class=${CABOOSE_CLASS} aria-hidden="true"></li>`
    ).querySelector<HTMLElement>('li')!;
    if (caboose !== list.lastElementChild) {
      list.append(caboose);
    }
    return caboose;
  }

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
      // The next membership change retries.
    }
  }

  /** Polls, since each select boots on its own schedule. */
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
 * Titlebar handlers resolve the live Group through `groupData`, so a re-booted
 * manager takes over the existing DOM.
 */
export class Group extends Base {
  manager: GroupedEntryTypeManager;
  container: HTMLElement;

  constructor(manager?: GroupedEntryTypeManager, container?: any) {
    super();
    // Also assigned in init; here so TS sees them as definitely assigned.
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
      titlebar.append(
        renderFragment(html`
          <div class="entry-type-group--actions">
            ${this.actionMenuTemplate()} ${this.reorderButtonTemplate()}
          </div>
        `)
      );
    }

    // Read the name at call time so renames are reflected.
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

  get name(): string {
    return this.container.dataset.name ?? '';
  }

  get select(): CraftComponentSelect | null {
    return this.container.querySelector('craft-component-select');
  }

  chips(): HTMLElement[] {
    return this.select?.chips ?? [];
  }

  actionMenuTemplate(): TemplateResult {
    const container = this.container;

    // Items generated from `.actions` don't close the menu on click.
    const close = (ev: Event): void => {
      if (!(ev.target instanceof Element)) {
        return;
      }
      ev.target.dispatchEvent(new Event('close-overlay', {bubbles: true}));
    };

    return html`
      <craft-action-menu
        .actions=${[
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
        ]}
      ></craft-action-menu>
    `;
  }

  /** `position` and `disabled` are kept current by {@link refresh}. */
  reorderButtonTemplate(): TemplateResult {
    const container = this.container;

    return html`
      <craft-reorder-button
        orientation="horizontal"
        @craft-reorder=${(event: CustomEvent<{direction: ReorderDirection}>) =>
          groupData.get(container)?.move(event.detail.direction)}
      ></craft-reorder-button>
    `;
  }

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

    this.manager.announceChange();
  }

  /** The select tears itself down on disconnect. */
  remove(): void {
    this.manager.groupSort?.removeItems(this.container);
    this.container.remove();
    this.manager.refresh();
    void this.manager.updateDefaultColumns();
    this.destroy();
  }

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

  /** Also re-stamps group values on chips dragged in from another group. */
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
