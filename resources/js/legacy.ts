import Cp from './bootstrap/cp.js';

// We need to globally register these for the moment
import './components/Auth/totp/totp-form.js';
import './components/Auth/recovery-codes/recovery-code-form.js';

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
import('./components/CpGlobalSidebar.js');
import('./components/CpQueueIndicator.js');
