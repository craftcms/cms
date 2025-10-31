import {default as WaTooltip} from '@awesome.me/webawesome/dist/components/tooltip/tooltip.js';

export default class CraftTooltip extends WaTooltip {
    static get styles(): import('lit').CSSResultGroup[];
}
declare global {
    interface HTMLElementTagNameMap {
        'craft-tooltip': CraftTooltip;
    }
}
