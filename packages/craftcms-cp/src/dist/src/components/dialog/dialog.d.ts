import {default as WaDialog} from '@awesome.me/webawesome/dist/components/dialog/dialog.js';

/**
 * craft-dialog extends wa-dialog from web awesome and adds custom styling.
 * Anything you can do with that works here.
 */
export default class CraftDialog extends WaDialog {
    static get styles(): import('lit').CSSResultGroup[];
}
