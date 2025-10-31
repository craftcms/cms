import {default as WaIcon} from '@awesome.me/webawesome/dist/components/icon/icon.js';

/**
 * craft-icon is just an alias to wa-icon from web awesome.
 *
 * Anything you can do over there you can do here.
 */
export default class CraftIcon extends WaIcon {
    static get styles(): import('lit').CSSResultGroup[];
}
