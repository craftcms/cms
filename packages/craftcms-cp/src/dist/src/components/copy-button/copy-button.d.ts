import {CSSResultGroup, LitElement} from 'lit';

/**
 * @summary Copy values to the clipboard on click.
 *
 * @event craft-copy - Emitted when the value is copied to the clipboard.
 * @event craft-error - Emitted when the value could not be copied to the clipboard.
 *
 * @slot - The default slot.
 *
 * @csspart button - The main button element.
 */
export default class CraftCopyButton extends LitElement {
    static styles: CSSResultGroup;
    isCopying: boolean;
    /** Value to copy on click */
    value: string;
    disabled: boolean;
    copyValue(): Promise<void>;
    render(): import('lit-html').TemplateResult<1>;
}
declare global {
    interface HTMLElementTagNameMap {
        'craft-copy-button': CraftCopyButton;
    }
}
