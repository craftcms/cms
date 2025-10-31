import {LionSelectInvoker, LionSelectRich} from '@lion/ui/select-rich.js';
import {default as CraftIcon} from '../icon/icon.js';

export default class CraftSelect extends LionSelectRich {
    static get styles(): (import('lit').CSSResultOrNative | import('lit').CSSResultArray)[];
    /** @type {SlotsMap} */
    get slots(): {
        invoker: () => import('lit-html').TemplateResult<1>;
    };
}
export declare class CraftSelectInvoker extends LionSelectInvoker {
    static get styles(): (import('lit').CSSResult & (import('lit').CSSResultOrNative | import('lit').CSSResultArray))[];
    get slots(): {
        after: () => CraftIcon;
    };
}
declare global {
    interface HTMLElementTagNameMap {
        'craft-select': CraftSelect;
        'craft-select-invoker': CraftSelectInvoker;
    }
}
