import type {PropertyValues, TemplateResult} from 'lit';
import {html, nothing} from 'lit';
import {property, state} from 'lit/decorators.js';
import {t} from '@src/utilities/translate.js';
import type {ElementSelectorController} from '@src/core/element-selector/index.js';
import CraftDialog from '../dialog/dialog.js';
import {ElementSelectorHostController} from './controller-host.js';
import styles from './element-selector-modal.styles.js';
import '../button/button.js';
import '../spinner/spinner.js';

/** Below this the index hides its sidebar behind a toggle. */
const NARROW_THRESHOLD = 550;

/**
 * craft-element-selector-modal is the chrome around an element index.
 *
 * It renders the dialog, the heading and the footer, and nothing else — the
 * index itself is slotted in, because the index is Vue in this app and a legacy
 * jQuery widget in the asset-move flow, and neither belongs in a Lit component.
 *
 * Bind it to an {@link ElementSelectorController} through the `controller`
 * property and it becomes a view of that controller: state flows out of the
 * controller into this component's reflected properties, and user intent flows
 * back in through `submit()` and `cancel()`. Nothing passes between this
 * component and the slotted index — they only ever talk to the controller, which
 * is what stops a web component and a Vue component from drifting apart.
 *
 * Without a controller it falls back to its own attributes, so it can be driven
 * declaratively in Storybook or by a consumer that only wants the chrome.
 *
 * @slot - The element index.
 * @slot secondary-actions - Footer content pinned left.
 * @slot primary-actions - Footer buttons placed before Cancel and Select.
 * @csspart footer - The footer row.
 * @csspart secondary-actions - The left-hand footer group.
 * @csspart primary-actions - The right-hand footer group.
 * @csspart cancel - The Cancel button.
 * @csspart select - The Select button.
 *
 * @fires craft-select - The user asked to accept the selection.
 * @fires craft-cancel - The user asked to dismiss the modal.
 *
 * @example
 * const modal = document.createElement('craft-element-selector-modal');
 * modal.controller = new ElementSelectorController({elementType, onSelect});
 * modal.append(index);
 * document.body.append(modal);
 * await modal.controller.open();
 */
export default class CraftElementSelectorModal extends CraftDialog {
  static override styles = [...(CraftDialog.styles as []), styles];

  /**
   * The business layer. Set as a property, never constructed here — one
   * controller may be shared with a Vue view of the same modal.
   *
   * The adapter type is deliberately loose: the chrome never touches the index,
   * so a subclass that narrows it (the folder picker, which requires a
   * `sourcePath`) has to remain assignable here.
   */
  @property({attribute: false})
  controller: ElementSelectorController<any> | null = null;

  /** Show the heading. When false it stays in the a11y tree but is hidden. */
  @property({type: Boolean, attribute: 'show-title', reflect: true})
  showTitle = false;

  @property({attribute: 'select-label'}) selectLabel = '';

  @property({attribute: 'cancel-label'}) cancelLabel = '';

  /** A submit is in flight. */
  @property({type: Boolean, reflect: true}) busy = false;

  /** The index body is being fetched. */
  @property({type: Boolean, reflect: true}) loading = false;

  @property({type: Boolean, attribute: 'can-submit', reflect: true})
  canSubmit = false;

  /**
   * Reflected from the surface's width. The slotted index keys its sidebar
   * toggle off it — the component itself does not act on it.
   */
  @property({type: Boolean, reflect: true}) narrow = false;

  @state() private hasIndex = false;

  #binding = new ElementSelectorHostController(this, () => this.controller);

  #resizeObserver: ResizeObserver | null = null;

  constructor() {
    super();

    // This modal's dismissal is the footer's Cancel button, and its header
    // override renders no close button — so a close button must not be what
    // keeps the header alive. With `show-title` off the header then collapses
    // instead of reserving a band of padding above the index.
    this.noClose = true;
  }

  override connectedCallback(): void {
    super.connectedCallback();

    if (typeof ResizeObserver !== 'undefined') {
      this.#resizeObserver = new ResizeObserver(([entry]) => {
        if (entry) {
          this.narrow = entry.contentRect.width < NARROW_THRESHOLD;
        }
      });
    }
  }

  override disconnectedCallback(): void {
    super.disconnectedCallback();
    this.#resizeObserver?.disconnect();
  }

  protected override willUpdate(changed: PropertyValues): void {
    if (changed.has('controller')) {
      this.#binding.resubscribe();
    }

    // One direction only: when bound, the controller is the source of truth for
    // every one of these, and the properties are a reflection of it rather than
    // an input to it.
    const state = this.#binding.state;

    if (state) {
      this.opened = state.open;
      this.busy = state.busy;
      this.loading = state.loading;
      this.canSubmit = state.canSubmit;
      this.label = state.title;
      this.showTitle = state.showTitle;
      this.selectLabel = state.selectLabel;
      this.hasIndex = state.indexBody !== null;
    }

    super.willUpdate(changed);
  }

  protected override firstUpdated(changed: PropertyValues): void {
    super.firstUpdated(changed);

    const surface = this.shadowRoot?.querySelector('.surface');

    if (surface) {
      this.#resizeObserver?.observe(surface);
    }
  }

  /** Heading only — dismissal lives in the footer's Cancel button. */
  protected override renderHeader(): TemplateResult {
    return html`
      <header class="header" part="header">
        <h1
          class=${this.showTitle ? 'title' : 'title title--hidden'}
          part="title"
          id=${this.titleId}
        >
          ${this.label}
        </h1>
      </header>
    `;
  }

  protected override renderBody(): TemplateResult {
    return html`
      <div class="body" part="body" ?inert=${this.busy}>
        ${this.loading && !this.hasIndex
          ? html`<div class="loading"><craft-spinner></craft-spinner></div>`
          : nothing}
        <slot></slot>
      </div>
    `;
  }

  protected override renderFooter(): TemplateResult {
    return html`
      <footer class="footer" part="footer">
        <div
          class="footer__group footer__group--secondary"
          part="secondary-actions"
          ?inert=${this.busy}
        >
          <slot name="secondary-actions"></slot>
        </div>
        <div class="footer__group" part="primary-actions">
          <slot name="primary-actions"></slot>
          <craft-button
            part="cancel"
            type="button"
            variant="fill"
            ?disabled=${this.busy}
            @click=${this.#onCancelClick}
          >
            ${this.cancelLabel || t('Cancel')}
          </craft-button>
          <craft-button
            part="select"
            type="button"
            variant="primary"
            ?disabled=${!this.canSubmit}
            ?loading=${this.busy}
            @click=${this.#onSelectClick}
          >
            ${this.selectLabel || t('Select')}
          </craft-button>
        </div>
      </footer>
    `;
  }

  /** Accept the current selection. */
  submit(): void {
    if (!this.canSubmit) {
      return;
    }

    this.dispatchEvent(
      new CustomEvent('craft-select', {bubbles: true, composed: true})
    );

    // The event is a notification for onlookers, not the mechanism — routing the
    // action through it as well would give one intent two paths.
    void this.controller?.submit();
  }

  /**
   * Every dismissal — Cancel, Escape, the backdrop — arrives here through
   * `CraftDialog`. When bound, the controller decides: it refuses while busy,
   * which is what keeps the modal up mid-save.
   */
  protected override requestClose(): void {
    this.dispatchEvent(
      new CustomEvent('craft-cancel', {bubbles: true, composed: true})
    );

    if (this.controller) {
      this.controller.cancel();
      return;
    }

    super.requestClose();
  }

  #onSelectClick = (): void => {
    this.submit();
  };

  #onCancelClick = (): void => {
    if (this.busy) {
      return;
    }

    this.requestClose();
  };
}

if (!customElements.get('craft-element-selector-modal')) {
  customElements.define(
    'craft-element-selector-modal',
    CraftElementSelectorModal
  );
}
