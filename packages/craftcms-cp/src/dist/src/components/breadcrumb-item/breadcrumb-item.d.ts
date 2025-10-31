import {default as WaBreadcrumbItem} from '@awesome.me/webawesome/dist/components/breadcrumb-item/breadcrumb-item.js';

export default class CraftBreadcrumbItem extends WaBreadcrumbItem {
    static get styles(): import('lit').CSSResultGroup[];
}
declare global {
    interface HTMLElementTagNameMap {
        'craft-breadcrumb-item': CraftBreadcrumbItem;
    }
}
