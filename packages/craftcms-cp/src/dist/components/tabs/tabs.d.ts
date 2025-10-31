import {LionTabs} from '@lion/ui/tabs.js';

export default class CraftTabs extends LionTabs {
    static get styles(): import('lit').CSSResult[];
}
declare global {
    interface HTMLElementTagNameMap {
        'craft-tabs': CraftTabs;
    }
}
