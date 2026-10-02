import {css, html, LitElement, nothing} from 'lit';
import {property} from 'lit/decorators.js';

/**
 * @summary Renders in place of a component that could not be loaded — a field
 * type whose plugin is missing or disabled.
 *
 * It exists so a page with a broken component still renders, and says which
 * component and which plugin rather than leaving a gap.
 */
export default class CraftMissingComponent extends LitElement {
  static override styles = css`
    :host {
      display: block;
      margin-block: 14px;
      margin-inline: 0;
      padding-block: 7px;
      padding-inline: 10px;
      max-width: 400px;
      border: var(--pane-border);
      border-radius: var(--pane-border-radius);
      background: var(--gray-050, var(--c-color-neutral-fill-quiet));
      box-shadow: var(--pane-shadow, var(--c-shadow-sm));
      overflow-wrap: break-word;
      box-sizing: border-box;
    }

    .error {
      margin: 0;
      color: var(--error-color, var(--c-color-danger-on-quiet));
    }

    .install-plugin {
      display: grid;
      grid-template-columns: 32px minmax(0, 1fr);
      gap: 8px;
      margin-block: 7px -7px;
      border-block-start: 1px solid
        var(--border-hairline, var(--c-color-border-quiet));
      padding-block: 10px;
    }

    .icon {
      width: 32px;
      height: 32px;
    }

    ::slotted([slot='icon']) {
      width: 100%;
      height: 100%;
    }

    .plugin {
      display: flex;
      align-items: center;
    }

    h3 {
      flex: 1;
      margin-block: 8px;
      margin-inline: 0;
    }

    ::slotted([slot='action']) {
      margin: 0;
    }
  `;

  /** The message explaining why the component could not be loaded. */
  @property() error = '';

  /** Name of the plugin the missing component belonged to. */
  @property({attribute: 'plugin-name'}) pluginName = '';

  protected override render() {
    return html`
      <p class="error" role="alert">${this.error}</p>
      ${this.pluginName
        ? html`<div class="install-plugin">
            <div class="icon" aria-hidden="true"><slot name="icon"></slot></div>
            <div class="plugin">
              <h3>${this.pluginName}</h3>
              <slot name="action"></slot>
            </div>
          </div>`
        : nothing}
    `;
  }
}

if (!customElements.get('craft-missing-component')) {
  customElements.define('craft-missing-component', CraftMissingComponent);
}

declare global {
  interface HTMLElementTagNameMap {
    'craft-missing-component': CraftMissingComponent;
  }
}
