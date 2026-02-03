import Craft from './bootstrap/craft';

// @ts-ignore
window.Craft = {
  ...(window.Craft || {}),
  ...Craft,
};

// @TODO ideally we wouldn't have to wait until load for this
document.addEventListener('DOMContentLoaded', () => {
  // noinspection JSIgnoredPromiseFromCall
  Craft.start();
});

/**
 * Components - dynamically imported after Craft is initialized
 */
import('@craftcms/cp/dist/components/nav-list/nav-list.ts');
import('@craftcms/cp/dist/components/nav-item/nav-item.ts');
import('./components/CpGlobalSidebar.js');
import('./components/CpQueueIndicator.js');
