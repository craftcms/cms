import {LionButtonSubmit} from '@lion/ui/button.js';

/**
 * @summary Interactive element that triggers an action or event.
 * @since 1.0
 *
 * @dependency craft-spinner
 *
 * @slot - The button's label.
 * @slot prefix - Content to display before the label (typically an icon).
 * @slot suffix - Content to display after the label (typically an icon).
 *
 * @csspart content - The button's content wrapper.
 * @csspart prefix - The button's prefix slot.
 * @csspart label - The button's label slot.
 * @csspart suffix - The button's suffix slot.
 * @csspart spinner - Spinner that shows when the button is in a loading state.
 */
export default class CraftButton extends LionButtonSubmit {
    static get styles(): import('lit').CSSResult[];
    /** Visual appearance of the button */
    appearance: 'accent' | 'plain';
    /** Theme variant of the button. Defaults to "default" */
    variant: 'primary' | 'default' | 'danger';
    /** Size of the button. Defaults to "medium" */
    size: 'zero' | 'small' | 'medium' | 'large';
    /** Show a spinner instead of the label */
    loading: boolean;
    render(): import('lit-html').TemplateResult<1>;
}
declare global {
    interface HTMLElementTagNameMap {
        'craft-button': CraftButton;
    }
}
