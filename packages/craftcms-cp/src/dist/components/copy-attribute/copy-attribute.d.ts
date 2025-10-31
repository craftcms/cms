import {CSSResultGroup, LitElement} from 'lit';
import {default as CraftCopyButton} from '../copy-button/copy-button.js';

/**
 * @summary Displays a field handle and allows quick copying
 *
 * @event craft-copy - Emitted when the value is copied to the clipboard.
 * @event craft-error - Emitted when the value could not be copied to the clipboard.
 *
 * @slot - The default slot.
 */
export default class CraftCopyAttribute extends LitElement {
    static styles: CSSResultGroup;
    status: 'rest' | 'success' | 'error';
    copyIconEl: HTMLSlotElement;
    successIconEl: HTMLSlotElement;
    errorIconEl: HTMLSlotElement;
    copyButtonEl: CraftCopyButton;
    /** The text value to copy */
    value: string;
    /** Disables the copy button. */
    disabled: boolean;
    /** The length of time to show feedback before restoring the default trigger. */
    feedbackDuration: number;
    tooltipLabel: string;
    constructor();
    getId(): string;
    showStatus(status: 'success' | 'error'): Promise<void>;
    render(): import('lit-html').TemplateResult<1>;
}
declare global {
    interface HTMLElementTagNameMap {
        'craft-copy-attribute': CraftCopyAttribute;
    }
}
