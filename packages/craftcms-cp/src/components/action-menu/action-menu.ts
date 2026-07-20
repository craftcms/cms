import {css, html, type PropertyValues, render} from 'lit';
import {property, queryAssignedElements} from 'lit/decorators.js';
import type CraftActionItem from '@src/components/action-item/action-item';
import {uuid} from '@lion/ui/core.js';
import {t} from '@src/utilities/translate';
import {Variant} from '@src/constants/variants';
import CraftPopover from '../popover/popover.js';
import type {
  ActionMenuActions,
  ActionMenuItem,
  ActionMenuItemButton,
  ActionMenuItemLink,
} from './action-menu.types.js';

import '../action-item/action-item.js';

export type ActionMenuChangeDetail =
  | {item: ActionMenuItemButton | ActionMenuItemLink}
  | {item: CraftActionItem};

export type {
  ActionMenuActions,
  ActionMenuItem,
  ActionMenuItemHr,
  ActionMenuItemDisplay,
  ActionMenuItemButton,
  ActionMenuItemLink,
  ActionMenuItemsProvider,
  ActionShortcut,
} from './action-menu.types.js';

/**
 * An action menu built on craft-popover.
 *
 * The web component is the single source of truth for action-menu behaviour.
 * It supports two mutually-compatible modes:
 *
 * 1. **Slot-based** (default / backwards compatible): the consumer slots their
 *    own `invoker`/`content` light-DOM children.
 * 2. **Data-driven**: the consumer sets the `actions` property and the
 *    component renders the full menu itself (default invoker + items),
 *    mirroring the logic that used to live in `ActionMenu.vue` (normalize,
 *    danger-sort, hr/display/link/button rendering).
 *
 * In data-driven mode the generated invoker + content are appended as
 * *light-DOM* children of the host. The generated `slot="content"` element is
 * projected into craft-popover's shadow `.popover-pane`, which provides the
 * container chrome (border/background/shadow/sizing); this component only adds
 * the menu item layout. A consumer-slotted invoker always takes precedence over
 * the generated default.
 *
 * @slot invoker - Element that triggers the menu.
 * @slot content - Action items to be rendered in the menu.
 *
 * @fires {CustomEvent<ActionMenuChangeDetail>} change - Emitted when the user
 *   clicks an item. In data-driven mode `event.detail.item` is the
 *   `ActionMenuItemButton` or `ActionMenuItemLink` descriptor; in slot-based
 *   mode it is the clicked `craft-action-item` element.
 */
export default class CraftActionMenu extends CraftPopover {
  static override styles = [
    ...CraftPopover.styles,
    css`
      ::slotted([slot='content']) {
        display: grid;
        gap: var(--c-spacing-xs);
        padding: var(--c-spacing-sm);
        font-size: var(--c-text-base);
        font-weight: 400;
      }

      ::slotted([slot='content']) hr {
        margin: 0;
      }

      :host([disabled]) ::slotted([slot='invoker']) {
        cursor: not-allowed;
        opacity: 0.5;
        pointer-events: none;
      }
    `,
  ];

  /**
   * Data-driven menu items. When provided, the component renders the full menu
   * itself. When `undefined`, the component behaves exactly as the legacy
   * slot-based version.
   *
   * May be either a static array, or a provider function returning the items.
   * When a function is supplied, it is evaluated initially and **re-evaluated
   * each time the menu opens**, so it may return state-dependent items.
   *
   * This is a JS property only (not an attribute) — set it via `.actions` in
   * Lit templates or `el.actions = [...]` imperatively.
   */
  @property({attribute: false}) actions?: ActionMenuActions;

  /** Accessible label for the generated default invoker. */
  @property() label: string = t('Actions');

  /** Icon name for the generated default invoker. */
  @property() icon: string = 'ellipsis';

  /**
   * Disables the menu. When `true`, the popover is prevented from opening
   * (click or keyboard activation of the invoker is a no-op) and `aria-disabled`
   * is applied to the invoker — whether it's the generated default invoker
   * (data-driven mode) or a consumer-slotted one (slot-based mode). The
   * generated `craft-button` invoker is also rendered with its native
   * `disabled` state. Reflected as an attribute so `:host([disabled])` styling
   * applies. Defaults to `false`.
   */
  @property({type: Boolean, reflect: true}) disabled = false;

  @queryAssignedElements({slot: 'invoker'})
  invokerNodes!: HTMLElement[];

  @queryAssignedElements({slot: 'content'})
  contentNodes!: HTMLElement[];

  private uid: string = uuid();

  /** Generated light-DOM invoker (data-driven mode only). */
  private _generatedInvoker: HTMLElement | null = null;

  /** Generated light-DOM content container (data-driven mode only). */
  private _generatedContent: HTMLElement | null = null;

  private _addEventListeners() {
    const content = this.contentNodes[0];
    if (!content) return;

    content
      .querySelectorAll<CraftActionItem>('craft-action-item')
      .forEach((item) => {
        item.addEventListener('click', () => {
          this.opened = false;
          // In data-driven mode the 'change' event is dispatched from
          // _renderItem (which has access to the descriptor). For slot-based
          // mode this is the only click handler, so dispatch it here.
          if (this.actions === undefined) {
            this._dispatchChange(item);
          }
        });
      });
  }

  private _dispatchChange(
    item: ActionMenuItemButton | ActionMenuItemLink | CraftActionItem
  ): void {
    this.dispatchEvent(
      new CustomEvent<ActionMenuChangeDetail>('change', {
        bubbles: true,
        composed: true,
        detail: {item} as ActionMenuChangeDetail,
      })
    );
  }

  private _setupInvoker() {
    const firstInvoker = this.invokerNodes[0];
    if (firstInvoker) {
      firstInvoker.setAttribute('id', `invoker-${this.uid}`);
      firstInvoker.setAttribute('aria-controls', `content-${this.uid}`);
      firstInvoker.setAttribute('aria-haspopup', 'true');
    }
    this._syncInvokerDisabled();
  }

  /**
   * Reflect `disabled` onto the current invoker (slotted or generated) as
   * `aria-disabled`, so Lion's overlay controller (which checks
   * `invokerNode.disabled || invokerNode.getAttribute('aria-disabled') ===
   * 'true'` before toggling open) refuses to open the popover, and assistive
   * tech announces the invoker as disabled. Runs for both slot-based and
   * data-driven modes — a consumer-slotted invoker doesn't otherwise know
   * about the host's `disabled` state.
   */
  private _syncInvokerDisabled(): void {
    const invoker = this.invokerNodes[0];
    if (!invoker) return;

    if (this.disabled) {
      invoker.setAttribute('aria-disabled', 'true');
    } else {
      invoker.removeAttribute('aria-disabled');
    }
  }

  private _setupContent() {
    const firstContent = this.contentNodes[0];
    if (firstContent) {
      firstContent.setAttribute('id', `content-${this.uid}`);
      firstContent.setAttribute('role', 'none');
    }
  }

  override _setupOverlayCtrl() {
    super._setupOverlayCtrl();
    this._setupInvoker();
    this._setupContent();
    this._addEventListeners();
  }

  override firstUpdated(changed: PropertyValues) {
    super.firstUpdated(changed);
    // craft-popover owns the shadow render, so wire the invoker slotchange
    // listener imperatively rather than overriding render().
    this.shadowRoot
      ?.querySelector<HTMLSlotElement>('slot[name="invoker"]')
      ?.addEventListener('slotchange', this._onInvokerSlotChange);
  }

  /**
   * Generate/refresh the light-DOM invoker + content before each render when in
   * data-driven mode. Doing this in `willUpdate` (synchronously, before
   * `updateComplete` resolves) ensures the nodes exist by the time Lion's
   * deferred `_setupOverlayCtrl()` runs on first connect.
   */
  protected override willUpdate(changed: PropertyValues): void {
    super.willUpdate(changed);

    // Prevent opening while disabled, regardless of slot-based vs data-driven
    // mode — a consumer-slotted invoker shouldn't be able to open the menu
    // either.
    if (changed.has('opened') && this.opened && this.disabled) {
      this.opened = false;
      return;
    }

    if (this.actions === undefined) {
      // Slot-based mode — tear down anything we previously generated.
      this._removeGeneratedNodes();
      return;
    }

    // Re-evaluate a provider function each time the menu opens (before Lion's
    // `updated()` shows the overlay), so it can return state-dependent items.
    const openingWithProvider =
      changed.has('opened') && this.opened && this._hasActionsProvider();

    if (
      changed.has('actions') ||
      changed.has('label') ||
      changed.has('icon') ||
      changed.has('disabled') ||
      openingWithProvider ||
      !this._generatedContent
    ) {
      this._renderDataDrivenMenu();
    }
  }

  /**
   * Re-point the (possibly already-initialized) overlay controller at the
   * freshly generated light-DOM nodes and re-apply aria wiring + item
   * listeners. Runs after the light-DOM children are in place.
   */
  protected override updated(changed: PropertyValues): void {
    super.updated(changed);

    if (changed.has('disabled')) {
      this._syncInvokerDisabled();
    }

    if (this.actions === undefined) {
      return;
    }

    // After an on-open provider re-evaluation (done in `willUpdate`), the
    // generated content node is reused (only its children are swapped), so the
    // overlay controller already points at the right node — we just need to
    // (re)bind click listeners to the freshly created items.
    if (changed.has('opened') && this.opened && this._hasActionsProvider()) {
      this._setupContent();
      this._addEventListeners();
    }

    if (changed.has('actions') || changed.has('label') || changed.has('icon')) {
      this._rewireGeneratedMenu();
    }
  }

  /**
   * Re-point the overlay controller at the freshly generated light-DOM nodes
   * and re-apply aria wiring + item listeners. Shared by the `actions`/`label`/
   * `icon` change path and the on-open provider re-evaluation path.
   */
  private _rewireGeneratedMenu(): void {
    if (this._overlayCtrl) {
      this._overlayCtrl.updateConfig({
        contentNode: this._overlayContentNode,
        invokerNode: this._overlayInvokerNode,
      });
    }

    this._setupInvoker();
    this._setupContent();
    this._addEventListeners();
  }

  /**
   * Whether the consumer has slotted their own invoker. A consumer-provided
   * invoker always overrides the generated default.
   */
  private _hasSlottedInvoker(): boolean {
    return Array.from(this.children).some(
      (child) => child.slot === 'invoker' && child !== this._generatedInvoker
    );
  }

  private _removeGeneratedNodes(): void {
    if (this._generatedInvoker?.isConnected) {
      this._generatedInvoker.remove();
    }
    if (this._generatedContent?.isConnected) {
      this._generatedContent.remove();
    }
    this._generatedInvoker = null;
    this._generatedContent = null;
  }

  /**
   * Normalize actions: default the `type`, default the `label`.
   */
  private _normalizeActions(actions: ActionMenuItem[]): ActionMenuItem[] {
    return actions.map((action): ActionMenuItem => {
      if (action.type === 'hr' || action.type === 'display') {
        return action;
      }

      if ('href' in action && action.href) {
        return {
          ...action,
          type: 'link',
          label: action.label ?? '',
        } as ActionMenuItemLink;
      }

      return {
        ...action,
        type: action.type ?? 'button',
        label: action.label ?? '',
      } as ActionMenuItemButton;
    });
  }

  /**
   * Sort so `variant === 'danger'` items move to the bottom (stable).
   */
  private _sortActions(actions: ActionMenuItem[]): ActionMenuItem[] {
    return [...actions].sort((a, b) => {
      const aDanger = 'variant' in a && a.variant === Variant.Danger ? 1 : 0;
      const bDanger = 'variant' in b && b.variant === Variant.Danger ? 1 : 0;
      return aDanger - bDanger;
    });
  }

  /**
   * Forward arbitrary item props (e.g. `action`, `feedback`, `confirm`) onto a
   * `craft-action-item`, excluding the descriptor-only keys we handle directly.
   */
  private _applyItemProps(
    el: CraftActionItem,
    action: ActionMenuItemButton | ActionMenuItemLink
  ): void {
    const reserved = new Set(['type', 'label', 'onClick', 'href']);
    for (const [key, value] of Object.entries(action)) {
      if (reserved.has(key) || value === undefined) {
        continue;
      }
      // Assign as a JS property so object values (action/feedback/shortcut)
      // are passed through, not stringified.
      (el as unknown as Record<string, unknown>)[key] = value;
    }
  }

  private _renderItem(action: ActionMenuItem): Node | null {
    if (action.type === 'hr') {
      const hr = document.createElement('hr');
      hr.className = 'action-menu__separator';
      // The separator lives in light DOM (inside the slotted content
      // container), so the shadow `::slotted()` selector can't reach it —
      // style it directly here.
      Object.assign(hr.style, {
        margin: '0',
        border: '0',
        borderBlockStart: '1px solid var(--c-color-neutral-border-quiet)',
      });
      return hr;
    }

    if (action.type === 'display') {
      return typeof action.node === 'function' ? action.node() : action.node;
    }

    const item = document.createElement('craft-action-item') as CraftActionItem;

    if (action.type === 'link') {
      item.href = action.href;
    }

    this._applyItemProps(item, action);

    if (action.label) {
      item.textContent = action.label;
    }

    if (typeof action.onClick === 'function') {
      const onClick = action.onClick;
      item.addEventListener('click', (event) => onClick(event));
    }

    item.addEventListener('click', () => this._dispatchChange(action));

    return item;
  }

  /**
   * Resolve the `actions` property to a concrete array. When a provider
   * function is supplied it is invoked here, so callers get fresh,
   * state-dependent items at the moment of resolution (initial render + each
   * open).
   */
  private _resolveActions(): ActionMenuItem[] {
    if (typeof this.actions === 'function') {
      return this.actions();
    }
    return this.actions ?? [];
  }

  /** Whether the `actions` property is a provider function. */
  private _hasActionsProvider(): boolean {
    return typeof this.actions === 'function';
  }

  /**
   * Build (or rebuild) the light-DOM invoker + content for data-driven mode.
   */
  private _renderDataDrivenMenu(): void {
    const actions = this._sortActions(
      this._normalizeActions(this._resolveActions())
    );

    // --- Invoker ---------------------------------------------------------
    if (this._hasSlottedInvoker()) {
      // Consumer-provided invoker wins; drop any generated one.
      if (this._generatedInvoker?.isConnected) {
        this._generatedInvoker.remove();
      }
      this._generatedInvoker = null;
    } else {
      if (!this._generatedInvoker) {
        const invoker = document.createElement('craft-button');
        invoker.setAttribute('slot', 'invoker');
        invoker.setAttribute('type', 'button');
        invoker.setAttribute('icon', '');
        invoker.setAttribute('size', 'small');
        invoker.setAttribute('variant', 'inherit');
        invoker.setAttribute('appearance', 'plain');
        this._generatedInvoker = invoker;
        this.appendChild(invoker);
      }
      // Keep the disabled state in sync with the host's `disabled` property.
      (this._generatedInvoker as HTMLButtonElement).disabled = this.disabled;
      this._generatedInvoker.toggleAttribute('disabled', this.disabled);
      // Keep the icon/label in sync.
      render(
        html`<craft-icon
          name="${this.icon}"
          label="${this.label}"
        ></craft-icon>`,
        this._generatedInvoker
      );
    }

    // --- Content ---------------------------------------------------------
    if (!this._generatedContent) {
      const content = document.createElement('div');
      content.setAttribute('slot', 'content');
      this._generatedContent = content;
      this.appendChild(content);
    }

    // Render items imperatively (display items can be arbitrary Nodes).
    const content = this._generatedContent;
    content.replaceChildren();
    for (const action of actions) {
      const node = this._renderItem(action);
      if (node) {
        content.appendChild(node);
      }
    }
  }

  /**
   * When a consumer-slotted invoker arrives (or leaves) after `actions` was
   * set, re-evaluate whether our generated default invoker should exist and
   * re-point the overlay controller accordingly.
   */
  private _onInvokerSlotChange = (): void => {
    if (this.actions === undefined) {
      return;
    }

    const hadGenerated = !!this._generatedInvoker;
    this._renderDataDrivenMenu();
    const hasGenerated = !!this._generatedInvoker;

    if (hadGenerated !== hasGenerated && this._overlayCtrl) {
      this._overlayCtrl.updateConfig({
        invokerNode: this._overlayInvokerNode,
      });
      this._setupInvoker();
    }
  };
}

if (!customElements.get('craft-action-menu')) {
  customElements.define('craft-action-menu', CraftActionMenu);
}

declare global {
  interface HTMLElementTagNameMap {
    'craft-action-menu': CraftActionMenu;
  }
}
