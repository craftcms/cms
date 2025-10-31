import {default as WaPopover} from '@awesome.me/webawesome/dist/components/popover/popover.js';

export default class CraftPopover extends WaPopover {
    static get styles(): import('lit').CSSResultGroup[];
}
declare global {
    interface HTMLElementTagNameMap {
        'craft-popover': CraftPopover;
    }
}
