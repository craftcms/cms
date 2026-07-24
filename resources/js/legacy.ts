import '../../cms-assets/resources/legacy/cp/dist/css/cp.css';

// We need to globally register these for the moment because an
// elevated session modal can be called from pretty much anywhere
import './modules/auth/components/login/login-form.js';
import './modules/auth/components/set-password/set-password-form.js';
import './modules/auth/components/verify-email/verify-email-form.js';
import './modules/auth/components/totp/totp-form.js';
import './modules/auth/components/recovery-codes/recovery-code-form.js';
import {mountElevatedSessionHost} from './modules/auth/elevated-session';

import './modules/listbox/index';
import './modules/matrix/index';
import './modules/field-layout-designer/index';
import './modules/sortable-checkbox-select/index';
import './modules/editable-table/index';
import './modules/grouped-entry-type-manager/index';
import './modules/queue/index';
import './modules/slideout/index';
import './modules/auth-method-setup/index';
import './modules/nested-element-manager/index';

const {default: Cp} = await import('./bootstrap/cp.js');

window.Cp = Cp as unknown as typeof window.Cp;

// Legacy-rendered pages don't go through `app.blade.php`'s
// `Cp.config(CpConfig); Cp.start()` boot, so initialize the modern services
// here from `window.Craft` — the same `Cp::config()` payload, emitted by the
// CpAsset bootstrap — or modules that rely on them (the action client,
// elevated sessions, the queue) have no config on these pages. `init()`
// only: `start()` mounts the Inertia app, which legacy pages must not do.
Cp.config((window as any).Craft ?? {});
Cp.init();

mountElevatedSessionHost();

/**
 * Components - dynamically imported after Craft is initialized
 */
import('@craftcms/ui/components/nav-list/nav-list');
import('@craftcms/ui/components/nav-item/nav-item');
import('./modules/navigation/components/cp-global-sidebar.js');
import('./modules/navigation/components/cp-queue-indicator.js');
import('./modules/markdown-field/markdown-field.js');
