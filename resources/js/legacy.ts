import Cp from './bootstrap/cp.js';

// @ts-ignore
window.Cp = {
  ...(window.Cp || {}),
  ...Cp,
};

console.log('window.Cp defined', window.Cp);

/**
 * Components - dynamically imported after Craft is initialized
 */
import('@craftcms/cp/components/nav-list/nav-list.ts.mjs');
import('@craftcms/cp/components/nav-item/nav-item.ts.mjs');
import('./components/cp/CpGlobalSidebar/CpGlobalSidebar.js');
import('./components/cp/CpQueueIndicator/CpQueueIndicator.js');
