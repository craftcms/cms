import {LionCollapsible} from '@lion/ui/collapsible.js';
import {css, html, type PropertyValues} from 'lit';
import {property} from 'lit/decorators.js';
import type {WindowWithCraft} from '@src/types/globals';
import CraftButton from '../button/button.js';

const CHEVRONS = [
  'chevron-down',
  'chevron-up',
  'chevron-right',
  'chevron-left',
];

/**
 * @summary A trigger that shows and hides content, in either of two modes:
 *
 * **Slotted (collapsible) mode** — Lion's collapsible convention: a
 * `slot="invoker"` trigger and `slot="content"` collapsible content. Without a
 * slotted invoker, renders a default one from the `label` attribute:
 *
 *     <craft-button type="button" variant="plain" flush icon="chevron-right" icon-position="suffix">${label}</craft-button>
 *
 * The chevron trails the label, and points toward the end of the line while
 * the disclosure is closed (right, or left in right-to-left text) and down
 * while it's open. A slotted `craft-button` invoker with a chevron `icon`
 * follows along, its chevron moved to its suffix.
 *
 * By default the content hangs off a hairline at the disclosure's start edge.
 * Set `appearance="plain"` to drop the hairline and indent.
 *
 * **External-target mode** — wraps a bare `button[type="button"]` whose
 * `aria-controls` names an element elsewhere in the document; toggling flips
 * that target's `data-state` between `expanded`/`collapsed` (the consumer
 * provides the CSS), mirrors `aria-expanded` on the button, and optionally
 * persists the state to a cookie (`cookie-name`). This is the contract of the
 * legacy `CraftDisclosure` element and `_includes/disclosure-toggle.twig`.
 * The mode is chosen automatically when such a button is present.
 *
 * @fires craft-show - The content was expanded.
 * @fires craft-hide - The content was collapsed.
 */
export default class CraftDisclosure extends LionCollapsible {
  static override get styles() {
    return [
      ...super.styles,
      css`
        ::slotted([slot='content']) {
          margin-block-start: var(--c-spacing-lg);
        }

        :host(:not([appearance='plain'])) ::slotted([slot='content']) {
          padding-inline-start: var(--c-spacing-lg);
          border-inline-start: 1px solid var(--c-color-neutral-border-quiet);
        }
      `,
    ];
  }

  /** Text for the default invoker button (slotted mode). */
  @property() label = '';

  /**
   * `line` hangs the content off a hairline at the disclosure's start edge;
   * `plain` drops the hairline and indent.
   */
  @property({reflect: true}) appearance: 'line' | 'plain' = 'line';

  /** Size of the default invoker. */
  @property({reflect: true}) size: 'xsmall' | 'small' | 'medium' | 'large' =
    'medium';

  /** External-target mode state. Reflected so consumers can set/read it. */
  @property({reflect: true}) state: string | null = null;

  /** Cookie to persist the external-target state under (external mode). */
  @property({attribute: 'cookie-name'}) cookieName: string | null = null;

  private __defaultInvoker: HTMLElement | null = null;

  private __externalTrigger: HTMLElement | null = null;

  private __externalExpanded = false;

  /** The external element named by the trigger's `aria-controls`. */
  private get __externalTarget(): HTMLElement | null {
    const targetSelector =
      this.__externalTrigger?.getAttribute('aria-controls');

    return targetSelector ? document.getElementById(targetSelector) : null;
  }

  override render() {
    return html`
      <slot name="invoker"></slot>
      <slot name="content"></slot>
      <slot></slot>
    `;
  }

  override connectedCallback() {
    this.__externalTrigger = this.querySelector(
      ':scope > button[type="button"][aria-controls]:not([slot])'
    );

    if (!this.__externalTrigger) {
      // Lion wires the click listener and aria state onto the slotted invoker
      // during connect, so the default one has to exist in the light DOM first.
      this.__ensureDefaultInvoker();
    }

    super.connectedCallback();

    if (this.__externalTrigger) {
      this.__setupExternalMode();
    }
  }

  override disconnectedCallback() {
    super.disconnectedCallback();

    if (this.__externalTrigger) {
      // Leave the target visible when the disclosure goes away (parity with
      // the legacy element).
      this.__handleExternalOpen();
      this.__externalTrigger.removeEventListener(
        'click',
        this.__toggleExternal
      );
    }
  }

  protected override updated(changedProperties: PropertyValues) {
    super.updated(changedProperties);

    if (changedProperties.has('label') && this.__defaultInvoker) {
      this.__defaultInvoker.textContent = this.label;
    }

    if (changedProperties.has('size') && this.__defaultInvoker) {
      this.__defaultInvoker.setAttribute('size', this.size);
    }

    if (changedProperties.has('opened')) {
      this.__syncInvokerChevron();
    }

    if (changedProperties.has('state') && this.__externalTrigger) {
      if (this.state === 'expanded') {
        this.__handleExternalOpen();
      } else if (this.state === 'collapsed') {
        this.__handleExternalClose();
      }
    }
  }

  private __ensureDefaultInvoker() {
    if (this._invokerNode) {
      return;
    }

    const button = document.createElement('craft-button');
    button.slot = 'invoker';
    button.setAttribute('type', 'button');
    button.setAttribute('variant', 'plain');
    button.setAttribute('flush', '');
    button.setAttribute('icon', 'chevron-right');
    button.setAttribute('icon-position', 'suffix');
    button.textContent = this.label;

    this.__defaultInvoker = button;
    this.prepend(button);
  }

  /**
   * Points a chevron invoker down while the content is open, and toward the
   * end of the line while it's closed, with the chevron trailing the label.
   * Uses the `icon` property rather than the attribute, since Vue sets the
   * property and `craft-button` doesn't reflect it.
   */
  private __syncInvokerChevron() {
    const invoker = this._invokerNode;

    if (!(invoker instanceof CraftButton) || !invoker.icon) {
      return;
    }

    if (!CHEVRONS.includes(invoker.icon)) {
      return;
    }

    const rtl = getComputedStyle(this).direction === 'rtl';

    invoker.iconPosition = 'suffix';
    invoker.icon = this.opened
      ? 'chevron-down'
      : rtl
        ? 'chevron-left'
        : 'chevron-right';
  }

  private __setupExternalMode() {
    const trigger = this.__externalTrigger!;

    if (!this.__externalTarget) {
      console.error(
        `No target with id ${trigger.getAttribute(
          'aria-controls'
        )} found for disclosure.`,
        trigger
      );
      return;
    }

    trigger.addEventListener('click', this.__toggleExternal);

    // Default to expanded when no initial state was given; the state change
    // is applied by `updated()`.
    if (this.state !== 'expanded' && this.state !== 'collapsed') {
      this.state = 'expanded';
    }
  }

  private __toggleExternal = () => {
    this.state = this.__externalExpanded ? 'collapsed' : 'expanded';
  };

  private __handleExternalOpen() {
    this.__externalExpanded = true;
    this.__externalTrigger?.setAttribute('aria-expanded', 'true');
    this.dispatchEvent(new CustomEvent('craft-show'));

    const target = this.__externalTarget;
    if (target) {
      target.dataset.state = 'expanded';
    }

    this.__persistExternalState('expanded');
  }

  private __handleExternalClose() {
    this.__externalExpanded = false;
    this.__externalTrigger?.setAttribute('aria-expanded', 'false');
    this.dispatchEvent(new CustomEvent('craft-hide'));

    const target = this.__externalTarget;
    if (target) {
      target.dataset.state = 'collapsed';
    }

    this.__persistExternalState('collapsed');
  }

  private __persistExternalState(state: string) {
    if (this.cookieName) {
      (window as WindowWithCraft).Craft?.setCookie(this.cookieName, state);
    }
  }
}

if (!customElements.get('craft-disclosure')) {
  customElements.define('craft-disclosure', CraftDisclosure);
}

declare global {
  interface HTMLElementTagNameMap {
    'craft-disclosure': CraftDisclosure;
  }
}
