import {CSSResultGroup, LitElement} from 'lit';

/**
 * @summary Wrapper component used to group a set of buttons together.
 *
 * @slot - The default slot.
 */
export default class CraftButtonGroup extends LitElement {
    static styles: CSSResultGroup;
    render(): import('lit-html').TemplateResult<1>;
}
declare global {
    interface HTMLElementTagNameMap {
        'craft-button-group': CraftButtonGroup;
    }
}
