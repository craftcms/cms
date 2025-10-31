import {LionOption} from '@lion/ui/listbox.js';

export default class CraftOption extends LionOption {
    static get styles(): import('lit').CSSResult[];
}
declare global {
    interface HTMLElementTagNameMap {
        'craft-option': CraftOption;
    }
}
