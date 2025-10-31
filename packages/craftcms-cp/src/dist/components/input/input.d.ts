import {LionInput} from '@lion/ui/input.js';

export default class CraftInput extends LionInput {
    static get styles(): (import('lit').CSSResultOrNative | import('lit').CSSResultArray)[];
    size: string;
    connectedCallback(): void;
}
declare global {
    interface HTMLElementTagNameMap {
        'craft-input': CraftInput;
    }
}
