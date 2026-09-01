import {LionRadioGroup} from '@lion/ui/radio-group.js';
import {inputStyles} from '@src/styles/form.styles';
import {css, type PropertyValues} from 'lit';
import {property} from 'lit/decorators.js';

export default class CraftRadioGroup extends LionRadioGroup {
  /**
   * Lays the options out as a row of tiles rather than a stacked list, for
   * groups whose options carry a thumbnail.
   */
  @property({type: Boolean, reflect: true}) thumbnails = false;

  private __ssrNameAdopted = false;

  /**
   * Adopts the group name from server-rendered radio inputs. Lion syncs
   * each registered child's name to the group's (`name || ''`), so a
   * nameless group would strip the SSR'd names and break native posting.
   * Runs before `super.connectedCallback()` so the name is set before any
   * child registers; `willUpdate` is the fallback for streaming parsing,
   * where the element can connect before its children exist.
   */
  override connectedCallback() {
    this.__adoptSlottedName();
    super.connectedCallback();
  }

  protected override willUpdate(changedProperties: PropertyValues) {
    this.__adoptSlottedName();
    super.willUpdate(changedProperties);
  }

  private __adoptSlottedName() {
    if (this.__ssrNameAdopted || this.name) {
      return;
    }

    const input = this.querySelector<HTMLInputElement>(
      'input[type="radio"][name]'
    );

    if (!input) {
      // Leave the flag unset so a later pass can still adopt once children
      // have been parsed.
      return;
    }

    this.__ssrNameAdopted = true;
    this.name = input.name;
  }

  static override get styles() {
    return [
      ...super.styles,
      inputStyles,
      css`
        .input-group {
          display: grid;
          gap: var(--c-spacing-xs);
        }

        /*
         * Options illustrated with a thumbnail read as a row of tiles rather
         * than a stacked list — the layout Craft 5 hand-rolled for View Mode.
         */
        :host([thumbnails]) .input-group {
          grid-auto-flow: column;
          justify-content: start;
          align-items: start;
          gap: var(--c-spacing-lg);
        }
      `,
    ];
  }
}

if (!customElements.get('craft-radio-group')) {
  customElements.define('craft-radio-group', CraftRadioGroup);
}

declare global {
  interface HTMLElementTagNameMap {
    'craft-radio-group': CraftRadioGroup;
  }
}
