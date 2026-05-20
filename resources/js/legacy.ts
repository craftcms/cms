// We need to globally register these for the moment because an
// elevated session modal can be called from pretty much anywhere
import './components/Auth/login/login-form.js';
import './components/Auth/totp/totp-form.js';
import './components/Auth/recovery-codes/recovery-code-form.js';
import Cp from './bootstrap/cp.js';

window.Cp = Cp;

/**
 * Components - dynamically imported after Craft is initialized
 */
import('@craftcms/cp/components/nav-list/nav-list.ts.mjs');
import('@craftcms/cp/components/nav-item/nav-item.ts.mjs');
import('./components/CpGlobalSidebar.js');
import('./components/CpQueueIndicator.js');
