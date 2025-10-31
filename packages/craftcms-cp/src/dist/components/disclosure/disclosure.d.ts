/**
 * Very simple disclosure trigger.
 *
 * Allows you to wrap a button[type="button"] and target an element to toggle the `data-state` attribute on.
 * Set `aria-expanded` on the button
 */
export default class CraftDisclosure extends HTMLElement {
    static observedAttributes: string[];
    private cookieName;
    private state;
    private expanded;
    get trigger(): Element | null;
    get target(): HTMLElement | null;
    connectedCallback(): void;
    disconnectedCallback(): void;
    attributeChangedCallback(name: string, oldValue: string, newValue: string): void;
    toggle(): void;
    handleOpen: () => void;
    open(): void;
    handleClose: () => void;
    close(): void;
}
declare global {
    interface HTMLElementTagNameMap {
        'craft-disclosure': CraftDisclosure;
    }
}
