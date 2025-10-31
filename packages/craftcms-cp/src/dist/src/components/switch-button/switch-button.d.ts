import {LionSwitchButton} from '@lion/ui/switch.js';

export default class CraftSwitchButton extends LionSwitchButton {
    static get styles(): import('lit').CSSResult[];
}
declare global {
    interface HTMLElementTagNameMap {
        'craft-switch-button': CraftSwitchButton;
    }
}
