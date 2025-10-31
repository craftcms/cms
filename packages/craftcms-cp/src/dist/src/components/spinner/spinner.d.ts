import {LitElement} from 'lit';

export default class CraftSpinner extends LitElement {
    static styles: import('lit').CSSResult[];
    protected render(): import('lit-html').TemplateResult<1>;
}
declare global {
    interface HTMLElementTagNameMap {
        'craft-spinner': CraftSpinner;
    }
}
