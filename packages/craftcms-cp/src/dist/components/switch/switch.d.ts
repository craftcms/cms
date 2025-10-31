import {LionSwitch} from '@lion/ui/switch.js';
import {default as CraftSwitchButton} from '../switch-button/switch-button.js';

export default class CraftSwitch extends LionSwitch {
    static get styles(): (import('lit').CSSResultOrNative | import('lit').CSSResultArray)[];
    get slots(): {
        input: () => HTMLElement;
    };
    static get scopedElements(): {
        'craft-switch-button': typeof CraftSwitchButton;
        'lion-switch-button': typeof import('@lion/ui/switch.js').LionSwitchButton;
    };
}
declare global {
    interface HTMLElementTagNameMap {
        'craft-switch': CraftSwitch;
    }
}
