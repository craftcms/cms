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
export default class CraftStatus extends LitElement {
    static styles: CSSResultGroup;
    /** Accessible label for the status. */
    label: string | null;
    /** The status of the indicator. */
    status: 'live' | 'pending' | 'expired' | 'disabled' | 'enabled' | null;
    getLabel(): string | null;
    render(): import('lit-html').TemplateResult<1>;
}
declare global {
    interface HTMLElementTagNameMap {
        'craft-status': CraftStatus;
    }
}
