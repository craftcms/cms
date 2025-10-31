import {LionInput} from '@lion/ui/input.js';

export default class CraftInputPassword extends LionInput {
    protected _visible: boolean;
    static get styles(): (import('lit').CSSResultOrNative | import('lit').CSSResultArray)[];
    constructor();
    reveal: () => void;
    renderSuffix: () => import('lit-html').TemplateResult<1>;
    get slots(): {
        suffix: () => {
            template: import('lit-html').TemplateResult<1>;
        };
        input: () => HTMLInputElement;
    };
}
declare global {
    interface HTMLElementTagNameMap {
        'craft-input-password': CraftInputPassword;
    }
}
