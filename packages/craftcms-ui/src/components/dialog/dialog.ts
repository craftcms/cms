import {css, type PropertyValues} from 'lit';
import {property} from 'lit/decorators.js';
import {LionDialog} from '@lion/ui/dialog.js';
import {t} from '../../utilities/translate';
import {wireOverlayLifecycleEvents} from '../../utilities/overlay-events.js';
import '../icon/icon.js';

/**
 * Styles for the dialog's generated light-DOM content. The content lives in
 * the component's light DOM (Lion slots it into a native <dialog> wrapper),
 * so shadow styles can't reach its descendants; adopt a sheet into the root
 * node instead.
 */
const contentStyles =
  typeof CSSStyleSheet !== 'undefined' ? new CSSStyleSheet() : null;
contentStyles?.replaceSync(`
  .craft-dialog {
    background-color: var(--c-surface-raised);
    border-radius: var(--c-radius-md);
    box-shadow: var(--c-shadow-lg);
    min-width: min(90vw, 24rem);
    max-width: min(90vw, 40rem);
  }

  .craft-dialog__header {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: var(--c-spacing-md);
    padding-inline: var(--c-spacing-lg);
    padding-block-start: var(--c-spacing-lg);
    padding-block-end: var(--c-spacing-md);
  }

  .craft-dialog__title {
    font-size: 1.25em;
    margin: 0;
  }

  .craft-dialog__close {
    background: none;
    border: none;
    cursor: pointer;
    color: inherit;
    padding: var(--c-spacing-xs);
    line-height: 1;
  }

  .craft-dialog__body {
    padding-inline: var(--c-spacing-lg);
    padding-block-end: var(--c-spacing-lg);
  }

  .craft-dialog__footer {
    display: flex;
    justify-content: flex-end;
    gap: var(--c-spacing-sm);
    padding-inline: var(--c-spacing-lg);
    padding-block-end: var(--c-spacing-lg);
  }
`);

/**
 * @summary A modal dialog. Set the `open` attribute to show it;
 * default-slot children render as the body, `slot="footer"` children render
 * in a footer, and any descendant with `data-dialog="close"` closes the
 * dialog when clicked.
 *
 * Being modal, it takes focus and blocks the page behind it — so reach for it
 * when a person genuinely has to deal with something before continuing, and
 * for anything less use a `craft-popover` or an inline `craft-callout`.
 *
 * @slot - The dialog's body content.
 * @slot footer - Actions, rendered right-aligned below the body.
 *
 * Note: Lion's overlay system already defines an `open()` *method*, so there
 * is no `open` boolean property — use the `open` attribute or the `opened`
 * property.
 *
 * @example
 * const dialog = document.createElement('craft-dialog');
 * dialog.setAttribute('open', '');
 * dialog.append(message);
 * document.body.appendChild(dialog);
 *
 * @fires craft-show - The overlay is opening. Fires as the state flips.
 * @fires craft-after-show - The overlay has opened and its update has settled.
 * @fires craft-hide - The overlay is closing. Fires as the state flips.
 * @fires craft-after-hide - The overlay has closed and its update has settled.
 */
export default class CraftDialog extends LionDialog {
  /**
   * Backs the `open` attribute (Web Awesome-era API). Named `openAttribute`
   * because Lion already uses `open` as a method; consumers should use the
   * attribute form or `opened`.
   */
  @property({type: Boolean, attribute: 'open', reflect: true})
  openAttribute = false;

  /** Title shown in the dialog header. */
  @property() label = '';

  #contentWrapper: HTMLElement | null = null;

  #titleElement: HTMLElement | null = null;

  constructor() {
    super();
    wireOverlayLifecycleEvents(this);
    this.addEventListener('opened-changed', () => {
      // Lion's JSDoc types `opened` as boxed Boolean; coerce to primitive.
      const opened = Boolean(this.opened);
      if (this.openAttribute !== opened) {
        this.openAttribute = opened;
      }
    });
  }

  static override get styles() {
    return [
      css`
        :host {
          display: contents;
        }

        dialog::backdrop {
          background-color: rgb(0 0 0 / 0.25);
        }
      `,
    ];
  }

  override connectedCallback() {
    this.#adoptContentStyles();
    this.#ensureContentWrapper();
    super.connectedCallback();
  }

  #adoptContentStyles() {
    const root = this.getRootNode();
    if (
      contentStyles &&
      (root instanceof Document || root instanceof ShadowRoot) &&
      !root.adoptedStyleSheets.includes(contentStyles)
    ) {
      root.adoptedStyleSheets = [...root.adoptedStyleSheets, contentStyles];
    }
  }

  /**
   * Lion expects dialog content as a light-DOM child with `slot="content"`.
   * Build it from the consumer's default-slot and footer-slot children.
   */
  #ensureContentWrapper() {
    if (this.#contentWrapper?.isConnected) {
      return;
    }

    const wrapper = document.createElement('div');
    wrapper.slot = 'content';
    wrapper.classList.add('craft-dialog');

    const body = document.createElement('div');
    body.classList.add('craft-dialog__body');
    body.append(
      ...Array.from(this.childNodes).filter(
        (node) => !(node instanceof Element) || node.slot === ''
      )
    );

    const footerNodes = Array.from(this.children).filter(
      (child) => child.slot === 'footer'
    );

    wrapper.append(this.#buildHeader(), body);

    if (footerNodes.length > 0) {
      const footer = document.createElement('footer');
      footer.classList.add('craft-dialog__footer');
      footer.append(...footerNodes);
      wrapper.append(footer);
    }

    wrapper.addEventListener('click', (event) => {
      const target = event.target as HTMLElement;
      if (target.closest?.('[data-dialog="close"]')) {
        this.opened = false;
      }
    });

    this.append(wrapper);
    this.#contentWrapper = wrapper;
  }

  #buildHeader(): HTMLElement {
    const header = document.createElement('header');
    header.classList.add('craft-dialog__header');

    const title = document.createElement('h2');
    title.classList.add('craft-dialog__title');
    title.textContent = this.label;
    this.#titleElement = title;

    const close = document.createElement('button');
    close.type = 'button';
    close.classList.add('craft-dialog__close');
    close.setAttribute('aria-label', t('Close'));
    close.setAttribute('data-dialog', 'close');

    const icon = document.createElement('craft-icon');
    icon.setAttribute('name', 'xmark');
    close.append(icon);

    header.append(title, close);
    return header;
  }

  protected override updated(changed: PropertyValues) {
    super.updated(changed);

    if (changed.has('openAttribute') && this.openAttribute !== this.opened) {
      this.opened = this.openAttribute;
    }

    if (changed.has('label') && this.#titleElement) {
      this.#titleElement.textContent = this.label;
    }
  }
}

if (!customElements.get('craft-dialog')) {
  customElements.define('craft-dialog', CraftDialog);
}

declare global {
  interface HTMLElementTagNameMap {
    'craft-dialog': CraftDialog;
  }
}
