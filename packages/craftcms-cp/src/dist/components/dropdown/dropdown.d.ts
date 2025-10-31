import {default as WaDropdown} from '@awesome.me/webawesome/dist/components/dropdown/dropdown.js';
import {default as WaDropdownItem} from '@awesome.me/webawesome/dist/components/dropdown-item/dropdown-item.js';

export default class CraftDropdown extends WaDropdown {
    static get styles(): import('lit').CSSResultGroup[];
}
export declare class CraftDropdownItem extends WaDropdownItem {
    static get styles(): import('lit').CSSResultGroup[];
}
declare global {
    interface HTMLElementTagNameMap {
        'craft-dropdown': CraftDropdown;
        'craft-dropdown-item': CraftDropdownItem;
    }
}
