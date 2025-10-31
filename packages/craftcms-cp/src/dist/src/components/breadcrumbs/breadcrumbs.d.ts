import {LitElement} from 'lit';

export default class CraftBreadcrumbs extends LitElement {
    static styles: CSSStyleSheet[];
    defaultSlot: HTMLSlotElement;
    separatorSlot: HTMLSlotElement;
    private breadcrumbsElements;
    /**
     * The label to use for the breadcrumb control. This will not be shown on the screen, but it will be announced by
     * screen readers and other assistive devices to provide more context for users.
     */
    label: string;
    private items;
    private visibleItems;
    private resizeObserver;
    private firstRender;
    private getSeparator;
    /**
     * We need to understand how much space (px) each breadcrumb item occupies,
     * in order to know if it fits the available horizontal space.
     */
    private calculateBreadcrumbItemsWidth;
    private handleSlotChange;
    connectedCallback(): void;
    private adjustOverflow;
    disconnectedCallback(): void;
    render(): import('lit-html').TemplateResult<1>;
}
declare global {
    interface HTMLElementTagNameMap {
        'craft-breadcrumbs': CraftBreadcrumbs;
    }
}
