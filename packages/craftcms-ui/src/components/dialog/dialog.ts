import type {PropertyValues, TemplateResult} from 'lit';
import {html, LitElement, nothing} from 'lit';
import {property} from 'lit/decorators.js';
import hostStyles from '@src/styles/host.styles.js';
import {t} from '@src/utilities/translate.js';
import {trapFocus} from '@src/utilities/focus-trap.js';
import styles from './dialog.styles.js';
import '../icon/icon.js';

let nextId = 0;

/**
 * How many open dialogs are holding the page's scroll.
 *
 * Counted rather than toggled, because dialogs stack — a selector modal opened
 * from a slideout — and only the last one to close should hand scrolling back.
 * `no-scroll` is the CP's existing convention (the legacy stylesheet and
 * `panel-stack.css` both define it), so this cooperates with the slideouts
 * rather than fighting them over `body.style.overflow`.
 */
let scrollLocks = 0;

function lockPageScroll(): void {
  if (scrollLocks++ === 0) {
    document.body.classList.add('no-scroll');
  }
}

function releasePageScroll(): void {
  if (scrollLocks > 0 && --scrollLocks === 0) {
    document.body.classList.remove('no-scroll');
  }
}

/**
 * craft-dialog is a modal dialog over a native `<dialog>`.
 *
 * Set the `open` attribute (or the `opened` property) to show it; default-slot
 * children render as the body, `slot="footer"` children render in a footer, and
 * any descendant with `data-dialog="close"` closes the dialog when clicked.
 *
 * The chrome lives in the shadow root and content is projected through real
 * `<slot>` elements, so slotted nodes are never moved. That matters for
 * framework-rendered content: Vue crashes if it re-patches a subtree a web
 * component has relocated, and any child arriving after `connectedCallback`
 * would miss a one-shot relocation pass anyway.
 *
 * Subclasses override {@link renderHeader}, {@link renderBody} and
 * {@link renderFooter} rather than `render()`, and resize themselves through the
 * `--c-dialog-*` custom properties.
 *
 * @slot - The dialog body.
 * @slot footer - Footer content, typically buttons.
 * @csspart dialog - The native `<dialog>` element.
 * @csspart surface - The visible panel inside it.
 * @csspart header - The header row.
 * @csspart title - The heading.
 * @csspart close - The header close button.
 *
 * The header renders when there is something to put in it — a `label`, a close
 * button, or both. Set `no-close` on a dialog that dismisses itself some other
 * way; with no label either, the header is dropped rather than left as an empty
 * band of padding.
 * @csspart body - The scrolling body region.
 * @csspart footer - The footer row.
 *
 * @fires craft-show - The dialog has opened.
 * @fires craft-hide - The dialog has closed.
 * @fires craft-after-show - The dialog has opened and finished updating.
 * @fires craft-after-hide - The dialog has closed and finished updating.
 *
 * @example
 * const dialog = document.createElement('craft-dialog');
 * dialog.setAttribute('open', '');
 * dialog.append(message);
 * document.body.appendChild(dialog);
 */
export default class CraftDialog extends LitElement {
  static override styles = [hostStyles, styles];

  /**
   * Whether the dialog is showing.
   *
   * Backed by the `open` attribute. Both spellings are public — `opened` is the
   * property, `open` the attribute — and they stay in sync in both directions.
   */
  @property({type: Boolean, attribute: 'open', reflect: true}) opened = false;

  /** Title shown in the dialog header. */
  @property() label = '';

  /**
   * Open with `show()` instead of `showModal()`, keeping the dialog out of the
   * top layer.
   *
   * The top layer paints above everything, including menus that append
   * themselves to `<body>` — which is most of the legacy jQuery CP. A dialog
   * hosting that kind of content has to stay in the normal stacking context.
   * The platform stops managing the backdrop, Escape and focus containment in
   * this mode, so the component supplies all three.
   */
  @property({type: Boolean, attribute: 'non-modal', reflect: true})
  nonModal = false;

  /** Fill the viewport. */
  @property({type: Boolean, reflect: true}) fullscreen = false;

  /** Close when the backdrop is clicked. Off by default. */
  @property({type: Boolean, attribute: 'close-on-outside-click'})
  closeOnOutsideClick = false;

  /**
   * Drop the header's close button.
   *
   * Negated because the button is on by default, and a boolean attribute can
   * only express presence. Set it on a dialog that provides its own dismissal —
   * a Cancel button in the footer, say — so the header isn't kept alive by a
   * control that isn't there.
   */
  @property({type: Boolean, attribute: 'no-close', reflect: true})
  noClose = false;

  protected readonly titleId = `craft-dialog-title-${++nextId}`;

  /**
   * Mirrors `opened` so lifecycle events fire on a real transition rather than
   * on the first render, where Lit reports every property as changed.
   */
  #lastOpened = false;

  #releaseFocusTrap: (() => void) | null = null;

  #holdsScrollLock = false;

  /**
   * Watches light-DOM children so an empty footer collapses.
   *
   * Deliberately not `slotchange`: happy-dom assigns slotted nodes but never
   * dispatches that event, so anything keyed off it is dead under the unit
   * tests. A childList observer behaves the same in both.
   */
  #childObserver: MutationObserver | null = null;

  override connectedCallback(): void {
    super.connectedCallback();

    this.addEventListener('click', this.#onHostClick);
    this.addEventListener('keydown', this.#onHostKeydown);

    this.#childObserver = new MutationObserver(() => this.requestUpdate());
    this.#childObserver.observe(this, {childList: true});
  }

  override disconnectedCallback(): void {
    super.disconnectedCallback();

    this.removeEventListener('click', this.#onHostClick);
    this.removeEventListener('keydown', this.#onHostKeydown);

    this.#childObserver?.disconnect();
    this.#childObserver = null;

    this.#releaseFocusTrap?.();
    this.#releaseFocusTrap = null;

    // Removed while still open — an unmounting Vue view, say — must not leave
    // the page stuck.
    this.#releaseScrollLock();
  }

  /** The native element, once rendered. */
  protected get dialogElement(): HTMLDialogElement | null {
    return this.shadowRoot?.querySelector('dialog') ?? null;
  }

  protected get hasFooter(): boolean {
    return this.querySelector(':scope > [slot="footer"]') !== null;
  }

  override render(): TemplateResult {
    return html`
      ${this.nonModal && this.opened
        ? html`<div
            class="backdrop"
            part="backdrop"
            @click=${this.#onBackdropClick}
          ></div>`
        : nothing}
      <dialog
        part="dialog"
        aria-labelledby=${this.hasHeader && this.label !== ''
          ? this.titleId
          : nothing}
        @cancel=${this.#onNativeCancel}
        @close=${this.#onNativeClose}
        @click=${this.#onDialogClick}
      >
        <div class="surface" part="surface">
          ${this.hasHeader ? this.renderHeader() : nothing} ${this.renderBody()}
          ${this.renderFooter()}
        </div>
      </dialog>
    `;
  }

  /**
   * Whether to render the header at all.
   *
   * It goes when there is nothing to put in it — no label *and* no close
   * button — because the row is otherwise just a band of padding above the
   * body. A close button on its own is reason enough to keep it; it needs
   * somewhere to live.
   *
   * Also governs `aria-labelledby`, which must not point at a heading that
   * isn't rendered.
   */
  protected get hasHeader(): boolean {
    return this.label !== '' || !this.noClose;
  }

  protected renderHeader(): TemplateResult {
    return html`
      <header class="header" part="header">
        <h2 class="title" part="title" id=${this.titleId}>${this.label}</h2>
        ${this.noClose
          ? nothing
          : html`<button
              type="button"
              class="close"
              part="close"
              aria-label=${t('Close')}
              @click=${() => this.requestClose()}
            >
              <craft-icon name="xmark"></craft-icon>
            </button>`}
      </header>
    `;
  }

  protected renderBody(): TemplateResult {
    return html`<div class="body" part="body"><slot></slot></div>`;
  }

  protected renderFooter(): TemplateResult {
    return html`
      <footer class="footer" part="footer" ?hidden=${!this.hasFooter}>
        <slot name="footer"></slot>
      </footer>
    `;
  }

  protected override updated(changed: PropertyValues): void {
    super.updated(changed);

    if (this.opened === this.#lastOpened) {
      return;
    }

    this.#lastOpened = this.opened;
    this.opened ? this.#showDialog() : this.#hideDialog();
    this.#emitLifecycle(this.opened);
  }

  #showDialog(): void {
    const dialog = this.dialogElement;

    if (dialog && !dialog.open) {
      // `showModal()` throws if the dialog is already open, hence the guard.
      this.nonModal ? dialog.show() : dialog.showModal();
    }

    // `showModal()` contains focus for us; `show()` does not.
    if (this.nonModal) {
      this.#releaseFocusTrap?.();
      this.#releaseFocusTrap = trapFocus(this);
    }

    // Neither `showModal()` nor `show()` stops the page behind from scrolling.
    if (!this.#holdsScrollLock) {
      this.#holdsScrollLock = true;
      lockPageScroll();
    }
  }

  #hideDialog(): void {
    const dialog = this.dialogElement;

    if (dialog?.open) {
      dialog.close();
    }

    this.#releaseFocusTrap?.();
    this.#releaseFocusTrap = null;

    this.#releaseScrollLock();
  }

  /** Idempotent, so teardown can't release a lock this dialog never took. */
  #releaseScrollLock(): void {
    if (this.#holdsScrollLock) {
      this.#holdsScrollLock = false;
      releasePageScroll();
    }
  }

  /**
   * `craft-show`/`craft-hide` fire on the transition; the `after` pair fires
   * once this update has settled. All four bubble and are composed, so
   * listeners outside the host's shadow root receive them.
   */
  #emitLifecycle(opened: boolean): void {
    this.#dispatch(opened ? 'craft-show' : 'craft-hide');

    void this.updateComplete.then(() => {
      if (this.opened !== opened) {
        return;
      }

      this.#dispatch(opened ? 'craft-after-show' : 'craft-after-hide');
    });
  }

  #dispatch(name: string): void {
    this.dispatchEvent(new CustomEvent(name, {bubbles: true, composed: true}));
  }

  /**
   * A request to dismiss — Escape, the close button, a `data-dialog="close"`
   * click, or the backdrop.
   *
   * Every dismissal path funnels through here so a subclass can route the
   * intent somewhere else (a controller that may refuse it, say) instead of
   * intercepting four listeners and the platform's own Escape handling.
   */
  protected requestClose(): void {
    this.opened = false;
  }

  /**
   * The platform's Escape handling for a modal dialog. Prevented so the close
   * goes through {@link requestClose} like every other dismissal; the base
   * implementation then closes it anyway.
   */
  #onNativeCancel = (event: Event): void => {
    event.preventDefault();
    this.requestClose();
  };

  /**
   * The platform closes a modal dialog on Escape by itself and fires `close`;
   * syncing here covers that as well as any direct `dialogElement.close()`.
   */
  #onNativeClose = (): void => {
    this.opened = false;
  };

  /** Escape is ours to handle only when the platform isn't managing the dialog. */
  #onHostKeydown = (event: KeyboardEvent): void => {
    if (this.nonModal && this.opened && event.key === 'Escape') {
      event.preventDefault();
      this.requestClose();
    }
  };

  /**
   * Honors the `data-dialog="close"` convention on slotted content. Shadow-tree
   * clicks retarget to the host and can't be matched with `closest()`, so the
   * header's own close button is wired directly instead.
   */
  #onHostClick = (event: MouseEvent): void => {
    const target = event.target as HTMLElement | null;

    if (target?.closest?.('[data-dialog="close"]')) {
      this.requestClose();
    }
  };

  /** A click on the backdrop of a modal dialog targets the dialog itself. */
  #onDialogClick = (event: MouseEvent): void => {
    if (this.closeOnOutsideClick && event.target === this.dialogElement) {
      this.requestClose();
    }
  };

  #onBackdropClick = (): void => {
    if (this.closeOnOutsideClick) {
      this.requestClose();
    }
  };
}

if (!customElements.get('craft-dialog')) {
  customElements.define('craft-dialog', CraftDialog);
}
