import {LitElement} from 'lit';

/**
 *
 */
export default class CraftNavItem extends LitElement {
    static styles: import('lit').CSSResult;
    /** Icon to render within the prefix. */
    icon: string;
    /** The URL of the navigation item. */
    url: string;
    /** Displays the item as active. */
    active: boolean;
    /** Opens the item in a new tab and displays an external link icon in the suffix. */
    external: boolean;
    /** Displays an indicator in the prefix. */
    indicator: boolean;
    id: string;
    iconOnly: boolean;
    subnavState: string;
    constructor();
    connectedCallback(): void;
    toggleSubnav(event: Event): void;
    renderIconItem(hasSubnav: boolean): import('lit-html').TemplateResult<1>;
    renderItem(hasSubnav: boolean): import('lit-html').TemplateResult<1>;
    render(): import('lit-html').TemplateResult<1>;
}
declare global {
    interface HTMLElementTagNameMap {
        'craft-nav-item': CraftNavItem;
    }
}
