import {LionTextarea} from '@lion/ui/textarea.js';

export default class CraftTextarea extends LionTextarea {
    static get styles(): (import('lit').CSSResultOrNative | import('lit').CSSResultArray)[];
}
declare global {
    interface HTMLElementTagNameMap {
        'craft-textarea': CraftTextarea;
    }
}
