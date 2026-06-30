import '../../cms-assets/resources/legacy/cp/dist/css/cp.css';

// We need to globally register these for the moment because an
// elevated session modal can be called from pretty much anywhere
import './modules/auth/components/login/login-form.js';
import './modules/auth/components/set-password/set-password-form.js';
import './modules/auth/components/verify-email/verify-email-form.js';
import './modules/auth/components/totp/totp-form.js';
import './modules/auth/components/recovery-codes/recovery-code-form.js';

import './modules/listbox/index';
import './modules/field-layout-designer/index';
import './modules/sortable-checkbox-select/index';

const {default: Cp} = await import('./bootstrap/cp.js');

window.Cp = Cp as unknown as typeof window.Cp;

/**
 * Components - dynamically imported after Craft is initialized
 */
import('@craftcms/cp/components/nav-list/nav-list.ts.mjs');
import('@craftcms/cp/components/nav-item/nav-item.ts.mjs');
import('./modules/navigation/components/cp-global-sidebar.js');
import('./modules/navigation/components/cp-queue-indicator.js');
import('./modules/markdown-field/markdown-field.js');
