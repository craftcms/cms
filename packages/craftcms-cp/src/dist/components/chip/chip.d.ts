import {CSSResultGroup, LitElement} from 'lit';

/**
 * @summary Short summary of the component's intended use.
 *
 * @event craft-event-name - Emitted as an example.
 *
 * @slot - The default slot.
 * @slot example - An example slot.
 *
 * @csspart base - The component's base wrapper.
 *
 * @cssproperty --example - An example CSS custom property.
 */
export default class CraftChip extends LitElement {
    static styles: CSSResultGroup;
    /** Size of the chip. */
    size: 'small' | 'medium' | 'large' | '';
    /** Variant of the chip. `plain` will render with no border or padding */
    variant: 'plain' | '';
    render(): import('lit-html').TemplateResult<1>;
}
declare global {
    interface HTMLElementTagNameMap {
        'craft-chip': CraftChip;
    }
}
