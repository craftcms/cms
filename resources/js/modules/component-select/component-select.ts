import {
  Base,
  DragSort,
  Select,
  Y_AXIS,
  closestRegistered,
  prefersReducedMotion,
  type GarnishBaseSettings,
} from '@craftcms/garnish';
import type {CraftActionMenu, ReorderDirection} from '@craftcms/ui';
import {componentSelectData} from './support';

// `Craft` and `$` (jQuery) remain page globals. The Choose menu and the
// *chip* action menus are both modern, self-booting `craft-action-menu`s
// now, but the controller still pairs `@craftcms/garnish` `DragSort` with
// still-jQuery Craft seams — `Craft.addActionsToChip` (which also carries a
// jQuery `disclosureMenu()` fallback for old/plugin-rendered chip markup)
// and `Craft.sendActionRequest` — so jQuery survives there.
declare const Craft: any;
declare const $: any;

/** Removal fade duration, ms (legacy `Craft.ComponentSelectInput.REMOVE_FX_DURATION`). */
const REMOVE_FX_DURATION = 200;

/**
 * Chips that already have their one-time wiring (hidden option, reorder
 * button, action-menu items). Keyed on the chip element itself, so it survives
 * moves within a list and a disconnect/reconnect of the select (the wiring
 * lives on the chip's DOM and stays valid). Module-scoped — not per-instance —
 * so a chip `li` adopted by a *different* select (see
 * {@link ComponentSelect.adoptChip}) keeps its wiring instead of being wired
 * twice; the chip's actions resolve their owning select at event time.
 */
const wiredChips = new WeakSet<HTMLElement>();

/**
 * Detail for the bubbling `define-chip-actions` `CustomEvent` dispatched while
 * a chip gets its one-time wiring. Listeners — `<craft-entry-type-manager>`
 * adds its "Move to previous/next group" items this way — may push
 * `Craft.addActionsToChip` action descriptors into `actions`; contributed
 * actions land before the built-in Replace/Remove items.
 */
export interface DefineChipActionsEventDetail {
  /** The chip being wired. */
  chip: HTMLElement;
  /** `Craft.addActionsToChip` action descriptors to add to the chip's menu. */
  actions: any[];
}

/**
 * Detail for the bubbling, cancelable `replace-component` `CustomEvent`
 * dispatched from a chip when its Replace action is activated. The owning
 * select's container listener provides the default behavior (opening the
 * Choose menu); consumers may take over the replace flow instead by calling
 * `preventDefault()` — from a listener between the chip and the select's
 * container, or from a capture-phase listener anywhere above it.
 */
export interface ReplaceComponentEventDetail {
  /** The chip whose Replace action was activated. */
  chip: HTMLElement;
}

/**
 * Attribute-driven configuration, replacing the legacy `{% js %}` settings boot.
 * Defaults match `Craft.ComponentSelectInput.defaults`, except
 * `addItemsToActionMenus` (folded into always-on for now). Fully resolved
 * (attributes + defaults) by `<craft-component-select>` before the controller
 * is constructed — see `component-select.ce.ts`'s `#parseSettings`.
 */
export interface ComponentSelectSettings extends GarnishBaseSettings {
  /** Base input name used when rendering newly-added chips' hidden inputs. */
  name: string | null;
  /** Max number of selected components; `null` → unlimited. */
  limit: number | null;
  /** Whether the chips can be reordered. Forced off when `limit` is 1. */
  sortable: boolean;
  /**
   * Whether chips can be selected (click / shift-click / ⌘-click), which enables
   * Backspace/Delete removal of the selection and dragging a multi-selection as
   * a group. Matches the legacy `Craft.ComponentSelectInput` default of `true`;
   * multi-selection itself follows {@link sortable} (legacy `multi: sortable`).
   */
  selectable: boolean;
  /** Whether newly-rendered chips should show component handles. */
  showHandles: boolean;
  /** Whether newly-rendered chips should show component descriptions. */
  showDescription: boolean;
  /** Whether newly-rendered chips should include an action menu. */
  showActionMenus: boolean;
  /** Whether chip labels should hyperlink to the component's edit page. */
  hyperlinks: boolean;
  /** CP screen action for the Create button's slideout, if there is one. */
  createAction: string | null;
  /** Whether the select is read-only (no behavior is wired at all). */
  disabled: boolean;
  /**
   * Lazy accessor for the per-instance {@link ComponentSelectSettings} input
   * hook: the ELEMENT owns the mutable `getInputValue` property (consumers —
   * `<craft-entry-type-manager>`'s `Group` — set it directly on
   * `<craft-component-select>`, possibly before this controller has booted),
   * so the controller can't just close over a snapshot. `component-select.ce.ts`
   * passes `() => this.#getInputValue`, and every read below goes through
   * `this.settings.getInputValue?.()?.(id)` to always see the current value.
   */
  getInputValue?: () => ((id: string | number) => string | null) | null;
}

/**
 * Component select controller — a `@craftcms/garnish` `Base` port of the
 * legacy jQuery `Craft.ComponentSelectInput`, orchestrating the server-rendered
 * markup from `_includes/forms/componentSelect.twig` — a `ul.components` of
 * `li > craft-chip` (each chip carries `data-type`/`data-id` and a hidden
 * input with its value), a Choose `craft-action-menu` (its `[command="--choose-item"]` invoker
 * + `craft-action-item[data-id]` options), and an optional Create button
 * (`[command="--create-item"]`). Setup lives in {@link init}, invoked from the constructor
 * only for the leaf class (`new.target` guard), the same construction
 * contract as the other garnish ports.
 *
 * This is one half of `<craft-component-select>`'s split: the element
 * (`component-select.ce.ts`) is a `ControllerElement` facade — the public API
 * consumers see (`selectedIds`, {@link addComponent}, {@link removeComponent},
 * the seams `<craft-entry-type-manager>` drives: {@link showOption} /
 * {@link hideOption}, {@link adoptChip}, {@link moveComponent},
 * {@link openChooseMenu}, the `getInputValue` hook) — while this class does
 * the work. Nothing renders into a shadow root, so the server markup stays
 * the source of truth and works without JS.
 *
 * @fires change - Garnish `trigger('change')` whenever membership or order
 *   changes (add, remove, drag-sort, or a reorder-menu move); the
 *   `ControllerElement` bridge re-emits it as a bubbling DOM `CustomEvent` on
 *   `<craft-component-select>`. Suppressed during boot.
 */
export class ComponentSelect extends Base<ComponentSelectSettings> {
  container: HTMLElement;
  override settings: ComponentSelectSettings;

  #list: HTMLUListElement | null = null;
  #addBtn: HTMLElement | null = null;
  #createBtn: HTMLElement | null = null;

  /** The Choose `craft-action-menu` (the `--add-item` is its slotted invoker). */
  #menu: CraftActionMenu | null = null;

  #dragSort: any = null;
  #select: Select | null = null;
  #listObserver: MutationObserver | null = null;

  /**
   * When set, an outer coordinator owns chip drag-sorting (the grouped entry
   * type manager runs one `DragSort` across every group), so this select never
   * builds its own — see {@link releaseSort}.
   */
  #sortManagedExternally = false;

  /** jQuery-wrapped Create button the activate handler is bound to. */
  #$createBtn: any = null;

  #initialized = false;

  constructor(container: HTMLElement, settings: ComponentSelectSettings) {
    super();
    this.container = container;
    this.settings = settings;
    if (new.target === ComponentSelect) {
      this.init(container, settings);
    }
  }

  /**
   * Resolve the server-rendered children and wire behavior. The
   * `ControllerElement` base already retried until `:scope > ul` resolved
   * before constructing this controller, so — unlike the pre-split element —
   * there's no boot retry loop here.
   */
  init(container: HTMLElement, settings: ComponentSelectSettings): void {
    this.container = container;
    this.settings = settings;

    const list = this.container.querySelector<HTMLUListElement>(':scope > ul');
    if (!list) {
      return;
    }

    this.#list = list;
    this.#addBtn = this.container.querySelector<HTMLElement>(
      '[command="--choose-item"]'
    );
    this.#createBtn = this.container.querySelector<HTMLElement>(
      '[command="--create-item"]'
    );
    // The Choose menu hosts the add button as its slotted invoker. (Chips
    // never contain a light-DOM craft-action-menu, so resolving through the
    // invoker just guards against future markup around it.)
    this.#menu = this.#addBtn?.closest('craft-action-menu') ?? null;

    // Object back-reference for event-time resolution (the chip actions'
    // `owner()` lookups) and external consumers.
    componentSelectData.set(this.container, this);

    // Disabled selects render static markup; the legacy Twig never booted JS
    // for them either.
    if (this.settings.disabled) {
      this.#initialized = true;
      return;
    }

    this.#initSelect();
    this.#initDragSort();

    for (const chip of this.#chips()) {
      this.#initChip(chip);
      // Sorter/selection membership is (re-)added here rather than in #initChip
      // so a disconnect/reconnect — which builds a fresh DragSort + Select but
      // keeps the chips' one-time wiring — still repopulates both.
      const li = chip.closest('li');
      if (li && this.settings.sortable) {
        this.#dragSort?.addItems(li);
      }
      if (this.settings.selectable) {
        this.#select?.addItems(chip);
      }
    }

    this.#observeList();
    this.#updateButtons();
    this.#updateReorderPositions();

    // One delegated listener covers current options and any added later by the
    // create flow. Shadow retargeting makes `ev.target` the option host, and
    // the item's native <button> fires click on Enter/Space, so this is the
    // whole activation story.
    this.#menu?.addEventListener('click', this.#handleMenuClick);

    // Default handler for the chips' `replace-component` events — bound on
    // the container so a chip's event finds its owning select by bubbling,
    // with no instance lookup.
    this.container.addEventListener(
      'replace-component',
      this.#handleReplaceComponent
    );

    if (this.#createBtn && this.settings.createAction) {
      this.#$createBtn = $(this.#createBtn);
      this.#$createBtn.on('activate', this.#handleCreateActivate);
    }

    this.#initialized = true;
  }

  /**
   * Detach listeners, dispose the sorter/observer, and release the back-
   * reference so the controller can be re-booted (mirrors the pre-split
   * element's `disconnectedCallback`).
   */
  override destroy(): void {
    this.#listObserver?.disconnect();
    this.#listObserver = null;

    this.#dragSort?.destroy?.();
    this.#dragSort = null;

    this.#select?.destroy();
    this.#select = null;

    this.#menu?.removeEventListener('click', this.#handleMenuClick);
    this.#menu = null;

    this.container.removeEventListener(
      'replace-component',
      this.#handleReplaceComponent
    );

    this.#$createBtn?.off('activate', this.#handleCreateActivate);
    this.#$createBtn = null;

    this.#initialized = false;
    componentSelectData.delete(this.container);

    super.destroy();
  }

  // --- Public API -------------------------------------------------------------

  /** Whether boot has completed (markup resolved and behavior wired). */
  get initialized(): boolean {
    return this.#initialized;
  }

  /**
   * The ids of the currently selected components, in DOM order (legacy
   * `getSelectedComponentIds`). Numeric ids come back as numbers, matching the
   * legacy jQuery `.data('id')` coercion.
   */
  get selectedIds(): Array<string | number> {
    return this.chips.map((chip) => {
      const id = chip.dataset.id ?? '';
      const numeric = Number(id);
      return id !== '' && !Number.isNaN(numeric) ? numeric : id;
    });
  }

  /**
   * The selected component chips, in DOM order (legacy `getComponents`). Chips
   * mid removal-fade (`li[data-removing]`) are excluded. Public so wrapping
   * elements — `<craft-entry-type-manager>` — can enumerate chips without
   * re-querying the select's internal DOM structure.
   */
  get chips(): HTMLElement[] {
    return this.#chips();
  }

  /**
   * Show/hide the Choose-menu option for a component id (legacy
   * `showOption`/`hideOption`, minus the cross-select propagation — that is
   * `<craft-entry-type-manager>`'s job now). Refreshes the add/create button
   * visibility, which depends on the remaining visible options.
   */
  showOption(id: string | number): void {
    this.#showOption(id);
    this.#updateButtons();
  }

  hideOption(id: string | number): void {
    this.#hideOption(id);
    this.#updateButtons();
  }

  /** Open the Choose menu (the chip Replace action's path). */
  openChooseMenu(): void {
    const menu = this.#menu;

    if (!menu) {
      return;
    }

    // At the limit, #updateButtons() has hidden the menu (and possibly its
    // footer wrapper), leaving the popover nothing visible to anchor to — the
    // replace flow still needs it, so reveal it for the duration of the
    // choose and let #updateButtons() recompute the hidden state once the
    // menu closes. (The legacy jQuery menu re-anchored itself to the chip
    // instead; Lion's dropdown anchors to the invoker.)
    if (menu.classList.contains('hidden')) {
      menu.classList.remove('hidden');
      menu
        .closest('[data-id="component-select-footer"]')
        ?.classList.remove('hidden');

      const restoreOnClose = (): void => {
        if (!menu.opened) {
          menu.removeEventListener('opened-changed', restoreOnClose);
          this.#updateButtons();
        }
      };
      menu.addEventListener('opened-changed', restoreOnClose);
    }

    menu.opened = true;
  }

  /**
   * Adopt a chip `li` that was rendered — and wired — by ANOTHER
   * `craft-component-select` (the `<craft-entry-type-manager>` "Move to
   * previous/next group" path), appending it to this select's list. Both
   * selects' list observers transfer sorter membership; the chip's one-time
   * wiring travels with it (see the module-scoped `wiredChips`), and its
   * actions resolve their owning select at event time, so nothing is re-wired.
   * The caller owns updating the chip's hidden-input value and any
   * cross-select option visibility.
   */
  adoptChip(li: HTMLElement): void {
    this.#list?.append(li);
  }

  /**
   * Hand chip drag-sorting to an outer coordinator (the
   * `<craft-entry-type-manager>` runs one `DragSort` across all groups so a
   * chip can be dragged *between* them — one sorter can't drop into a sibling
   * select's list). Destroys this select's own `DragSort` and stops it from
   * being recreated; the legacy `GroupedEntryTypeSelectInput.initComponentSort`
   * no-op'd for the same reason. The chips' `<craft-reorder-button>`s stay —
   * they are the outer sorter's drag handles and still drive the touch move
   * menu — and the caller owns registering the chip `li`s with its sorter.
   * Idempotent, so the manager can call it again after any re-boot.
   */
  releaseSort(): void {
    this.#sortManagedExternally = true;
    this.#dragSort?.destroy?.();
    this.#dragSort = null;
  }

  /**
   * Render + append a chip for the given component via the
   * `app/render-components` action (legacy `addComponent`). When the select is
   * at its `limit`, the last chip is replaced. The response's head/body HTML is
   * appended so any chip-level JS boots. Pass `addToMenu: true` (the create
   * flow) to also add a — hidden — Choose-menu option for the component; see
   * {@link #addMenuOption}.
   */
  async addComponent(
    type: string,
    id: string | number,
    addToMenu = false
  ): Promise<void> {
    const {data} = await Craft.sendActionRequest(
      'POST',
      'app/render-components',
      {
        data: {
          components: [
            {
              type,
              id,
              instances: [this.#renderSettings(id)],
            },
          ],
        },
      }
    );

    // At the limit, adding replaces the last chip (legacy behavior, sans fade).
    if (!this.#canAddMore()) {
      const chips = this.#chips();
      const last = chips[chips.length - 1];
      if (last) {
        this.removeComponent(last, false);
      }
    }

    const li = document.createElement('li');
    li.innerHTML = data.components[type][id][0];
    this.#list?.append(li); // the list observer wires the chip + sorter

    if (addToMenu) {
      this.#addMenuOption(type, id, li.querySelector('craft-chip'));
    }

    await Craft.appendHeadHtml(data.headHtml);
    await Craft.appendBodyHtml(data.bodyHtml);

    if (this.settings.showDescription) {
      // Descriptions render an info icon that needs a UI-elements boot.
      Craft.initUiElements($(li));
    }

    this.#onChange();
  }

  /**
   * Remove a chip — `el` may be the `craft-chip`, its `li`, or anything inside
   * either (legacy `removeComponent` + `removeComponents`). Hidden inputs are
   * disabled first so the value can't post if the form submits mid-removal,
   * the chip's Choose-menu option is restored, and focus moves to the next
   * chip or the add button.
   *
   * All state changes (inputs, option, `change`) land immediately, matching
   * the legacy order; only the DOM node lingers through the fade. The fading
   * `li` is flagged `data-removing` so counts, `selectedIds`, and reorder
   * positions already exclude it, and it leaves the sorter right away.
   */
  removeComponent(el: HTMLElement, animate = true): void {
    const chip =
      el.closest<HTMLElement>('craft-chip') ??
      el.querySelector<HTMLElement>('craft-chip');
    const li = chip?.closest('li') ?? null;
    if (!chip || !li) {
      return;
    }

    // Form-submit safety: neutralize the inputs before the node goes away.
    for (const input of Array.from(chip.querySelectorAll('input'))) {
      input.disabled = true;
      input.removeAttribute('name');
    }

    this.#showOption(chip.dataset.id);
    wiredChips.delete(chip);
    this.#select?.removeItems(chip);

    li.dataset.removing = '';
    this.#dragSort?.removeItems(li);

    // The next still-selected chip, for the focus handoff.
    let nextLi =
      li.nextElementSibling instanceof HTMLElement
        ? li.nextElementSibling
        : null;
    while (nextLi && nextLi.dataset.removing !== undefined) {
      nextLi =
        nextLi.nextElementSibling instanceof HTMLElement
          ? nextLi.nextElementSibling
          : null;
    }

    const finish = (): void => {
      li.remove(); // the list observer re-confirms the sorter removal
    };

    if (animate) {
      this.#animateAway(li, finish);
    } else {
      finish();
    }

    this.#focusAfterRemoval(nextLi);
    this.#onChange();
  }

  /**
   * Move a chip's `li` one slot toward the start (`'up'` — Move forward on
   * horizontal lists) or the end (`'down'`) — the reorder button's menu path.
   * Public so the reorder handler can call it on the chip's *current* owner
   * after an {@link adoptChip} move.
   */
  moveComponent(li: HTMLElement, direction: ReorderDirection): void {
    const sibling =
      direction === 'up' ? li.previousElementSibling : li.nextElementSibling;
    if (!sibling) {
      return;
    }

    if (direction === 'up') {
      sibling.before(li);
    } else {
      sibling.after(li);
    }

    this.#onChange();
  }

  // --- Boot ---------------------------------------------------------------------

  /** Delegated Choose-menu option activation. */
  #handleMenuClick = (ev: Event): void => {
    const option =
      ev.target instanceof Element
        ? ev.target.closest<HTMLElement>('craft-action-item[data-id]')
        : null;
    if (!option || option.hasAttribute('hidden')) {
      return;
    }

    // craft-action-menu only auto-closes for default-slot items; these live in
    // the content container, so close explicitly.
    if (this.#menu) {
      this.#menu.opened = false;
    }

    this.addComponent(option.dataset.type ?? '', option.dataset.id ?? '');
  };

  /**
   * Create button activation: open a CP screen slideout for `create-action`,
   * add the newly-created component on submit (plus a Choose-menu option for
   * it), and hand focus back to the Create button when the slideout closes
   * (legacy `ComponentSelectInput.init`).
   */
  #handleCreateActivate = (): void => {
    const slideout = new Craft.CpScreenSlideout(this.settings.createAction);

    slideout.on('submit', (ev: any) => {
      const data = ev.response.data;
      this.addComponent(data.modelClass, data.modelId, true);
    });

    slideout.on('close', () => {
      this.#createBtn?.focus();
    });
  };

  /**
   * Default behavior for a chip's `replace-component` event: open the Choose
   * menu. Skipped when a consumer already took the flow over via
   * `preventDefault()` — see {@link ReplaceComponentEventDetail}.
   */
  #handleReplaceComponent = (ev: Event): void => {
    if (ev.defaultPrevented) {
      return;
    }

    ev.preventDefault();
    this.openChooseMenu();
  };

  // --- Chips ----------------------------------------------------------------------

  /**
   * The selected component chips, in DOM order (legacy `getComponents`).
   * Chips mid removal-fade (`li[data-removing]`) are already excluded.
   */
  #chips(): HTMLElement[] {
    if (!this.#list) {
      return [];
    }
    return Array.from(
      this.#list.querySelectorAll<HTMLElement>(
        ':scope > li:not([data-removing]) > craft-chip'
      )
    );
  }

  /**
   * One-time wiring for a chip: hide its Choose-menu option, give its `li` a
   * reorder button, and add its action-menu items. WeakSet-guarded so a `li`
   * moved within the list (which re-fires the observer as remove+add) isn't
   * wired twice.
   */
  #initChip(chip: HTMLElement): void {
    if (wiredChips.has(chip)) {
      return;
    }
    wiredChips.add(chip);

    this.#hideOption(chip.dataset.id);

    const li = chip.closest('li');
    if (li && this.settings.sortable) {
      this.#initReorderButton(li);
    }

    this.#addChipActions(chip);
    this.#initChipEditShortcut(chip);

    if (this.settings.selectable) {
      this.#initChipRemovalShortcut(chip);
    }
  }

  /**
   * Backspace/Delete while a chip (or a focusable inside it) is focused removes
   * the whole current selection (legacy `addComponents` keydown handler). Like
   * the other chip handlers this lives on the chip and needs no teardown — it
   * goes away with the chip — and resolves the chip's owning select at event
   * time, since a chip can be adopted by a different select.
   */
  #initChipRemovalShortcut(chip: HTMLElement): void {
    chip.addEventListener('keydown', (ev) => {
      if (ev.key === 'Backspace' || ev.key === 'Delete') {
        ev.stopPropagation();
        ev.preventDefault();
        // oxfmt-ignore
        (closestRegistered(chip, componentSelectData) ?? this).#removeSelected();
      }
    });
  }

  /** Remove every currently-selected chip (the Backspace/Delete path). */
  #removeSelected(): void {
    // Snapshot: removeComponent mutates the selection as each chip leaves.
    const selected = this.#select?.getSelectedItems() ?? [];
    for (const chip of selected) {
      this.removeComponent(chip);
    }
  }

  /**
   * Add the chip's action-menu items through the `Craft.addActionsToChip` seam
   * (it finds — or creates — the self-booting `craft-action-menu` inside the
   * `craft-chip`'s `[slot="suffix"]`, building `craft-action-item`s from these
   * descriptors). A bubbling `define-chip-actions` event — dispatched natively
   * on {@link container} (NOT via garnish `trigger`, since its `detail.actions`
   * array is synchronously mutated by ancestor listeners) — lets a wrapping
   * element contribute extra actions ahead of the built-in Replace/Remove —
   * see {@link DefineChipActionsEventDetail}. The legacy Move
   * forward/backward actions are intentionally not ported — the
   * `<craft-reorder-button>` owns reordering now.
   *
   * Like the legacy handlers, these avoid a hard `this`: a chip CAN end up
   * assigned to a different component select (see {@link adoptChip}), so each
   * handler resolves the chip's owning select — via the `support.ts` registry —
   * at event time.
   */
  #addChipActions(chip: HTMLElement): void {
    if (!Craft?.addActionsToChip) {
      return;
    }

    const owner = (): ComponentSelect =>
      closestRegistered(chip, componentSelectData) ?? this;

    const actions: any[] = [];

    this.container.dispatchEvent(
      new CustomEvent<DefineChipActionsEventDetail>('define-chip-actions', {
        bubbles: true,
        detail: {chip, actions},
      })
    );

    if (this.#menu) {
      actions.push({
        icon: 'arrows-rotate',
        label: Craft.t('app', 'Replace'),
        callback: () => {
          // Announce rather than act: the owning select's container listener
          // opens the Choose menu as the default (picking an option adds the
          // replacement and, at the limit, drops this chip), and wrappers can
          // take the flow over via `preventDefault()` — see
          // {@link ReplaceComponentEventDetail}. Dispatched from the chip so
          // bubbling finds whichever select currently owns it, with no
          // instance lookup.
          chip.dispatchEvent(
            new CustomEvent<ReplaceComponentEventDetail>('replace-component', {
              bubbles: true,
              cancelable: true,
              detail: {chip},
            })
          );
        },
      });
    }

    actions.push({
      icon: 'remove',
      label: Craft.t('app', 'Remove'),
      onActivate: () => owner().removeComponent(chip),
      destructive: true,
    });

    Craft.addActionsToChip($(chip), actions);
  }

  /**
   * dblclick — and taphold, via the jQuery Touch Events plugin the CP loads —
   * on a chip triggers its action menu's `[data-edit-action]` item, matching
   * the legacy port. The listeners live on the chip itself, so they need no
   * teardown; they go away with the chip.
   */
  #initChipEditShortcut(chip: HTMLElement): void {
    $(chip).on('dblclick taphold', (ev: any) => {
      // Don't open the edit screen when tapholding a button — that's a drag.
      if (ev.type === 'taphold' && ev.target.nodeName === 'BUTTON') {
        return;
      }

      const menu = this.#chipDisclosureMenu(chip);
      if (menu) {
        // `$container` is jQuery on the legacy DisclosureMenu and a native
        // element on the modern port; `$()` normalizes both.
        $(menu.$container).find('[data-edit-action]').trigger('click');
      }
    });
  }

  /**
   * The chip's own action menu (legacy `getDisclosureMenu`), in whichever
   * shape it's in — mirroring `Craft.addActionsToChip`'s own resolution:
   *
   * - Modern self-booting `craft-action-menu` (what
   *   `ElementHtml::componentActionMenu()` renders now): returns
   *   `{$container}` pointing at its `[slot="content"]` node — a plain
   *   object, not a real `Garnish.DisclosureMenu`, since there isn't one.
   * - Legacy shape (old markup, or a `[data-disclosure-trigger]` a plugin
   *   still renders into the suffix slot, or the old `.chip-actions
   *   .action-btn`): booted on demand through the jQuery
   *   `disclosureMenu()` plugin, returning the real instance.
   */
  #chipDisclosureMenu(chip: HTMLElement): any {
    const actionMenu = chip.querySelector<HTMLElement>(
      '[slot="suffix"] craft-action-menu'
    );
    if (actionMenu) {
      const container =
        actionMenu.querySelector<HTMLElement>('[slot="content"]') ?? actionMenu;
      return {$container: container};
    }

    const $trigger = $(chip)
      .find(
        '[slot="suffix"] [data-disclosure-trigger], .chip-actions .action-btn'
      )
      .first();

    return $trigger.length
      ? $trigger.disclosureMenu().data('disclosureMenu')
      : null;
  }

  /**
   * Settings each rendered chip instance is requested with (legacy
   * `renderSettings`). Includes an `inputValue` when the
   * {@link ComponentSelectSettings.getInputValue} hook supplies one (legacy
   * `EntryTypeSelectInput.renderSettings`) — read lazily through
   * `this.settings.getInputValue?.()` so a hook set on the element after boot
   * is still honored.
   */
  #renderSettings(id: string | number) {
    return {
      showActionMenu: this.settings.showActionMenus,
      showHandle: this.settings.showHandles,
      showDescription: this.settings.showDescription,
      inputName: this.settings.name,
      hyperlink: this.settings.hyperlinks,
      inputValue: this.settings.getInputValue?.()?.(id),
    };
  }

  // --- Sorting ---------------------------------------------------------------------

  /**
   * Chip selection (legacy `initComponentSelect`): a `@craftcms/garnish`
   * `Select` over the chips, enabling click / shift-click / ⌘-click selection.
   * `multi` follows `sortable` (legacy `multi: this.settings.sortable`); the
   * filter keeps clicks on a chip's own links/buttons (its label link, action
   * menu, reorder button) from selecting it. `makeFocusable` is off — selection
   * exists for Backspace/Delete removal and group drag, not keyboard roving.
   *
   * A window `mousedown` outside the container clears the selection, mirroring
   * the legacy deselect-on-outside-click. Bound via garnish `addListener`, so
   * `Base.destroy` (via {@link destroy}'s `super.destroy()`) tears it down.
   */
  #initSelect(): void {
    if (!this.settings.selectable) {
      return;
    }

    this.#select = new Select({
      multi: this.settings.sortable,
      filter: (target: EventTarget | null) =>
        !(target instanceof Element
          ? target.closest('a[href],button,[role=button]')
          : null),
      makeFocusable: false,
    });

    this.addListener(window, 'mousedown', (ev: any) => {
      const target = ev.target instanceof Node ? ev.target : null;
      if (target !== this.container && !this.container.contains(target)) {
        this.#select?.deselectAll();
      }
    });
  }

  /**
   * Drag-to-sort on the chips list, mirroring the sortable-checkbox-select
   * setup: the `<craft-reorder-button>` is the handle, and membership is kept
   * in sync by {@link init} and the list observer. Vertical lists sort on the
   * y axis; `inline-chips` lists are unrestricted (legacy
   * `getComponentSortAxis`). On touch there's no sorter — the reorder button's
   * menu handles reordering. When `selectable`, a `filter` drags the whole
   * selection as a group whenever the grabbed chip is part of it (legacy
   * `initComponentSort`'s filter).
   */
  #initDragSort(): void {
    if (
      this.#dragSort ||
      this.#sortManagedExternally ||
      !this.settings.sortable ||
      !Craft.hasMousePointerEvents()
    ) {
      return;
    }

    this.#dragSort = new DragSort({
      container: this.#list,
      handle: 'craft-reorder-button',
      axis: this.#list?.classList.contains('inline-chips') ? null : Y_AXIS,
      filter: this.settings.selectable ? () => this.#draggeeFilter() : null,
      collapseDraggees: true,
      magnetStrength: 4,
      helperLagBase: 1.5,
    });
    this.#dragSort.on('sortChange', () => this.#onChange());
  }

  /**
   * The `li`s a drag should move: the whole selection (each selected chip's
   * `li`) when the grabbed chip is selected, otherwise just the grabbed `li`
   * (legacy `initComponentSort` filter, which keyed off the target chip's `sel`
   * class). Runs at drag start off the sorter's current `$targetItem`.
   */
  #draggeeFilter(): HTMLElement[] {
    const targetLi = this.#dragSort?.$targetItem;
    if (!(targetLi instanceof HTMLElement)) {
      return [];
    }

    const chip = targetLi.querySelector<HTMLElement>(':scope > craft-chip');
    if (chip && this.#select?.isSelected(chip)) {
      return this.#select
        .getSelectedItems()
        .map((selectedChip: HTMLElement) => selectedChip.closest('li'))
        .filter((li: HTMLLIElement | null): li is HTMLLIElement => li !== null);
    }

    return [targetLi];
  }

  /**
   * Append the `<craft-reorder-button>` to the chip's suffix slot, beside the
   * action menu — it is both the DragSort handle and the move-action menu,
   * replacing the legacy `.move` handle button and the chip menu's Move
   * forward/backward actions. `inline-chips` lists get the button's
   * `horizontal` orientation (Move forward/backward labels with RTL-aware
   * arrows).
   */
  #initReorderButton(li: HTMLElement): void {
    const chip = li.querySelector('craft-chip');
    if (!chip || li.querySelector('craft-reorder-button')) {
      return;
    }

    // The server always renders the suffix container (it holds the action
    // menu), but be defensive for chips built elsewhere.
    let suffix = chip.querySelector(':scope > [slot="suffix"]');
    if (!suffix) {
      suffix = document.createElement('div');
      suffix.setAttribute('slot', 'suffix');
      chip.append(suffix);
    }

    const btn = document.createElement('craft-reorder-button');
    if (this.#list?.classList.contains('inline-chips')) {
      btn.setAttribute('orientation', 'horizontal');
    }
    btn.addEventListener('reorder', (event: Event) => {
      if (!(event instanceof CustomEvent)) {
        return;
      }
      // SAFETY: craft-reorder-button emits this registered detail contract.
      const {direction} = event.detail as {direction: ReorderDirection};
      // Resolve the owning select at event time — the li may have been
      // adopted by a different select since this listener was bound.
      (closestRegistered(li, componentSelectData) ?? this).moveComponent(
        li,
        direction
      );
    });
    suffix.append(btn);
  }

  /**
   * Watch the list for `li`s added or removed after boot — an added chip gets
   * its one-time wiring and joins the sorter; a removed one leaves it. A move
   * within the list fires as remove+add for the same node, which is why sorter
   * membership is re-added here and chip wiring is WeakSet-guarded. Watches
   * direct children only (`childList`, no `subtree`), so appending the reorder
   * button inside an `li` can't re-trigger it.
   */
  #observeList(): void {
    if (!this.#list) {
      return;
    }

    this.#listObserver = new MutationObserver((mutations) => {
      let changed = false;

      for (const mutation of mutations) {
        for (const node of mutation.addedNodes) {
          if (node instanceof HTMLElement && node.matches('li')) {
            const chip = node.querySelector<HTMLElement>(':scope > craft-chip');
            if (chip) {
              this.#initChip(chip);
              if (this.settings.sortable) {
                this.#dragSort?.addItems(node); // no-op if already registered
              }
              if (this.settings.selectable) {
                this.#select?.addItems(chip); // no-op if already registered
              }
              changed = true;
            }
          }
        }

        for (const node of mutation.removedNodes) {
          if (node instanceof HTMLElement && node.matches('li')) {
            this.#dragSort?.removeItems(node);
            if (this.settings.selectable) {
              const chip = node.querySelector<HTMLElement>(
                ':scope > craft-chip'
              );
              if (chip) {
                this.#select?.removeItems(chip);
              }
            }
            changed = true;
          }
        }
      }

      // Button visibility + reorder positions only; the `change` event stays
      // with the explicit mutation paths (add/remove/move/drag) so a drag
      // doesn't double-fire it.
      if (changed) {
        this.#updateButtons();
        this.#updateReorderPositions();
      }
    });

    this.#listObserver.observe(this.#list, {childList: true});
  }

  /**
   * Set each reorder button's `position` (`first`/`middle`/`last`) so Move up
   * is disabled on the first chip and Move down on the last, and disable the
   * buttons entirely when there's nothing to reorder against.
   */
  #updateReorderPositions(): void {
    if (!this.#list || !this.settings.sortable) {
      return;
    }

    // Chip `li`s only — a grouped select's list can also hold a manager-owned
    // `entry-type-group--caboose` sentinel `li` (no chip), which must not count
    // toward the reorder count or it'd wrongly enable a lone chip's button.
    const lis = this.#chips()
      .map((chip) => chip.closest<HTMLElement>('li'))
      .filter((li): li is HTMLElement => li !== null);

    lis.forEach((li, index) => {
      const btn = li.querySelector('craft-reorder-button');
      if (!btn) {
        return;
      }

      btn.toggleAttribute('disabled', lis.length < 2);
      btn.setAttribute(
        'position',
        index === 0 ? 'first' : index === lis.length - 1 ? 'last' : 'middle'
      );
    });
  }

  // --- Choose menu (craft-action-menu) ----------------------------------------

  /**
   * The Choose menu's option elements. A descendant query on the menu host —
   * not `[slot="content"] >` — because Lion's overlay controller relocates the
   * content node inside its wrapper (dropping the `slot` attribute) once the
   * overlay boots.
   */
  #options(): HTMLElement[] {
    if (!this.#menu) {
      return [];
    }
    return Array.from(
      this.#menu.querySelectorAll<HTMLElement>('craft-action-item[data-id]')
    );
  }

  #getOption(id: string | number): HTMLElement | null {
    const key = String(id);
    return this.#options().find((option) => option.dataset.id === key) ?? null;
  }

  /**
   * Option visibility rides the consumer-owned `hidden` attribute; the menu's
   * `searchable` filter uses its own `data-search-hidden` channel, so the two
   * compose without clobbering each other.
   */
  #showOption(id: string | number | undefined): void {
    if (id !== undefined) {
      this.#getOption(id)?.removeAttribute('hidden');
    }
  }

  #hideOption(id: string | number | undefined): void {
    if (id !== undefined) {
      this.#getOption(id)?.setAttribute('hidden', '');
    }
  }

  /**
   * Add a Choose-menu option for a newly-created component (the create flow):
   * a `craft-action-item` built client-side from the rendered chip's
   * `data-label`/`data-handle`, replacing the legacy server round-trip
   * (`withMenuItems: true` + `menuId`). `data-keywords` carries the label +
   * handle for the menu's `searchable` filter, matching the server-rendered
   * options. Starts `hidden` — the component was just selected. Activation
   * needs no wiring; the boot-time delegated click listener covers it.
   * (Trade-off vs. server markup: no icon/color.)
   */
  #addMenuOption(
    type: string,
    id: string | number,
    chip: HTMLElement | null
  ): void {
    // Append beside an existing option when there is one; otherwise fall back
    // to the content container (which may have lost its `slot` attribute to
    // Lion's relocation, hence the two-step lookup).
    const container =
      this.#options().pop()?.parentElement ??
      this.#menu?.querySelector<HTMLElement>('[slot="content"]');
    if (!container) {
      return;
    }

    const label = chip?.dataset.label ?? String(id);
    const handle = chip?.dataset.handle;

    const option = document.createElement('craft-action-item');
    option.dataset.type = type;
    option.dataset.id = String(id);
    option.dataset.keywords = [label, handle].filter(Boolean).join(' ');
    option.setAttribute('hidden', '');

    const labelWrap = document.createElement('span');
    labelWrap.className = 'inline-flex cp:flex-col cp:items-start gap-2xs';

    const labelEl = document.createElement('span');
    labelEl.textContent = label;
    labelWrap.append(labelEl);

    if (this.settings.showHandles && handle) {
      const handleEl = document.createElement('span');
      handleEl.className = 'menu-item-description mt-2xs smalltext light code';
      handleEl.textContent = handle;
      labelWrap.append(handleEl);
    }

    option.append(labelWrap);
    container.append(option);
  }

  // --- Limit / focus ------------------------------------------------------------

  /** Whether another component may be added under the `limit`. */
  #canAddMore(): boolean {
    return !this.settings.limit || this.#chips().length < this.settings.limit;
  }

  /**
   * Hide/show the add + create buttons per the `limit` and remaining options,
   * and collapse their flex wrapper when both are hidden (legacy
   * `updateButtons`). The add button is the menu's slotted invoker, so the
   * wrapper is resolved via `closest` and the check reads the buttons
   * directly rather than the wrapper's immediate children.
   */
  #updateButtons(): void {
    const canAdd = this.#canAddMore();

    if (this.#menu) {
      const hasVisibleOptions = this.#options().some(
        (option) => !option.hasAttribute('hidden')
      );
      this.#menu.classList.toggle('hidden', !canAdd || !hasVisibleOptions);
    }

    if (this.#createBtn) {
      this.#createBtn.classList.toggle('hidden', !canAdd);
    }

    const isHidden = (btn: HTMLElement | null): boolean =>
      !btn || btn.classList.contains('hidden');

    const wrapper = (this.#menu ?? this.#createBtn)?.closest<HTMLElement>(
      '[data-id="component-select-footer"]'
    );
    wrapper?.classList.toggle(
      'hidden',
      isHidden(this.#menu) && isHidden(this.#createBtn)
    );
  }

  /**
   * After a removal, move focus to the following chip's action button, else to
   * the add button (when more can be added), else to the last remaining chip's
   * action button (legacy `focusNextLogicalElement`).
   */
  #focusAfterRemoval(nextLi: HTMLElement | null): void {
    const target =
      this.#chipActionButton(nextLi) ??
      (this.#canAddMore() && this.#addBtn
        ? this.#addBtn
        : this.#chipActionButton(this.#chips().pop()?.closest('li') ?? null));

    target?.focus();
  }

  /** A focusable control within a chip's `li` — its action-menu trigger. */
  #chipActionButton(li: HTMLElement | null): HTMLElement | null {
    return (
      li?.querySelector<HTMLElement>(
        'craft-chip [data-disclosure-trigger], craft-chip button, craft-chip a[href]'
      ) ?? null
    );
  }

  /**
   * Fade + collapse a chip's `li` before removal — the legacy velocity
   * `animateComponentAway`, as a WAAPI animation with no velocity dependency.
   * Collapses the layout axis (width for `inline-chips`, height otherwise) and
   * runs immediately when the user prefers reduced motion or WAAPI is
   * unavailable.
   */
  #animateAway(li: HTMLElement, done: () => void): void {
    if (prefersReducedMotion() || !(li.animate instanceof Function)) {
      done();
      return;
    }

    const inline = this.#list?.classList.contains('inline-chips') ?? false;
    const property = inline ? 'width' : 'height';
    const size = inline ? li.offsetWidth : li.offsetHeight;

    li.style.overflow = 'hidden';
    // The chip is already removed state-wise; don't let the ghost take clicks.
    li.style.pointerEvents = 'none';

    const animation = li.animate(
      [
        {opacity: 1, [property]: `${size}px`},
        {opacity: 0, [property]: '0px'},
      ],
      {duration: REMOVE_FX_DURATION, easing: 'ease-out', fill: 'forwards'}
    );

    animation.addEventListener('finish', done);
    animation.addEventListener('cancel', done);
  }

  // --- Change -------------------------------------------------------------------

  /**
   * Membership or order changed: refresh button visibility + reorder positions
   * and emit `change` (suppressed during boot, legacy parity) via garnish
   * `trigger` — the `ControllerElement` bridge re-dispatches it as a bubbling
   * DOM `CustomEvent` on `<craft-component-select>`.
   */
  #onChange(): void {
    // Re-sync the selection's cached item order after add/remove/reorder/drag
    // (legacy `onChange`'s `componentSelect?.resetItemOrder()`).
    this.#select?.resetItemOrder();

    this.#updateButtons();
    this.#updateReorderPositions();

    if (this.#initialized) {
      this.trigger('change');
    }
  }
}
