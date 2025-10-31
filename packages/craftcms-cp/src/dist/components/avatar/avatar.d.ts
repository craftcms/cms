import {CSSResultGroup, LitElement} from 'lit';

/**
 * @summary Container to represent a user or object
 *
 * @cssproperty [--color-start=red] - Start color of the gradient
 * @cssproperty [--color-end=blue] - End color of the gradient
 * @cssproperty [--color-text=currentColor] - Color of the text
 * @cssproperty [--size=calc(30rem / 16)] - Overall size of the avatar. Defaults to 30px.
 */
export default class CraftAvatar extends LitElement {
    static styles: CSSResultGroup;
    /** Accessible label for the avatar. */
    label: string | null;
    /** Unique ID for the svg gradient */
    private _gradientId;
    connectedCallback(): void;
    private text;
    render(): import('lit-html').TemplateResult<1>;
}
declare global {
    interface HTMLElementTagNameMap {
        'craft-avatar': CraftAvatar;
    }
}
