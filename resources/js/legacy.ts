import '../../cms-assets/resources/legacy/cp/dist/css/cp.css';

/**
 * Register the full `craft-*` element set, same as `cp.ts` does.
 *
 * Legacy-rendered pages emit components that aren't imported anywhere in this
 * entrypoint's module graph (`craft-callout`, `craft-pane`, `craft-action-menu`,
 * `craft-info-icon`, …) — both from `resources/templates/**` directly and from
 * the PHP `src/Cp/Components/*` builders. Until now those only got defined
 * because the cpcompat webpack bundle happened to inline the whole
 * `@craftcms/ui` barrel, which also gave the page a second copy of Lit. The
 * barrel belongs here instead, so there is one Lit instance and one set of
 * element definitions on every CP page.
 */
import '@craftcms/ui';

// We need to globally register these for the moment because an
// elevated session modal can be called from pretty much anywhere
import './modules/auth/components/login/login-form.js';
import './modules/auth/components/set-password/set-password-form.js';
import './modules/auth/components/verify-email/verify-email-form.js';
import './modules/auth/components/totp/totp-form.js';
import './modules/auth/components/recovery-codes/recovery-code-form.js';
import {mountElevatedSessionHost} from './modules/auth/elevated-session';
import {defineDashboardWidgetSettingsFormHost} from './modules/forms/dashboard-widget-settings-form-host';
import {defineEntryFieldLayoutFormHost} from './modules/forms/entry-field-layout-form-host';
import {defineLayoutComponentSettingsFormHost} from './modules/forms/layout-component-settings-form-host';

import './modules/listbox/index';
import './modules/matrix/index';
import './modules/field-layout-designer/index';
import './modules/sortable-checkbox-select/index';
import './modules/editable-table/index';
import './modules/grouped-entry-type-manager/index';
import './modules/queue/index';
import './modules/slideout/index';
import './modules/auth-method-setup/index';
import './modules/input-generators/index';
import './modules/icon-picker/index';
import './modules/field-toggle/index';
import './modules/proxy-scrollbar/index';
import './modules/element-label/index';
import './modules/form-observer/index';
import './modules/interval-manager/index';
import './modules/entry-mover/index';
import './modules/prompt-handler/index';
import './modules/link-field/index';
import './modules/cp-modal/index';
import './modules/tabs/index';
import './modules/grid/index';
import './modules/chart/index';
import './modules/data-table-sorter/index';
import './modules/element-action-trigger/index';
import './modules/element-thumb-loader/index';
import './modules/element-table-sorter/index';
import './modules/asset-mover/index';
import './modules/element-selector-modal/index';
import './modules/element-select-input/index';
import './modules/preview-file-modal/index';
import './modules/asset-select-input/index';
import './modules/element-deletion-manager/index';
import './modules/uploader/index';
import './modules/nested-element-manager/index';
import './modules/ui/index';

const {default: Cp} = await import('./bootstrap/cp.js');

window.Cp = Cp;

// Legacy-rendered pages don't go through `app.blade.php`'s
// `Cp.config(CpConfig); Cp.start()` boot, so initialize the modern services
// here from `window.Craft` — the same `Cp::config()` payload, emitted by the
// CpAsset bootstrap — or modules that rely on them (the action client,
// elevated sessions, the queue) have no config on these pages. `init()`
// only: `start()` mounts the Inertia app, which legacy pages must not do.
Cp.config(window.Craft ?? {});
Cp.init();
defineDashboardWidgetSettingsFormHost(Cp.$components);
defineEntryFieldLayoutFormHost(Cp.$components);
defineLayoutComponentSettingsFormHost(Cp.$components);

mountElevatedSessionHost();

/**
 * Components - dynamically imported after Craft is initialized
 */
import('@craftcms/ui/components/nav-list/nav-list');
import('@craftcms/ui/components/nav-item/nav-item');
import('./modules/navigation/components/cp-global-sidebar.js');
import('./modules/navigation/components/cp-queue-indicator.js');
import('./modules/markdown-field/markdown-field.js');
