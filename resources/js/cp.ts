import '@craftcms/ui';
import Cp from './bootstrap/cp.js';
import './modules/navigation/components/cp-global-sidebar.js';
import './modules/navigation/components/cp-queue-indicator.js';

/**
 * Legacy ports, assigns window.Craft.* so PHP-emitted code still works
 * someday these will be removed and the classes will be used directly
 */
import './modules/sortable-checkbox-select';
import './modules/listbox';
import './modules/matrix';
import './modules/auth/elevated-session';
import './modules/field-layout-designer';
import './modules/editable-table';
import './modules/generated-fields';
import './modules/component-select';
import './modules/grouped-entry-type-manager';
import './modules/queue';
import './modules/slideout';
import './modules/auth-method-setup';
import './modules/nested-element-manager';
import './modules/ui';

window.Cp = Cp as unknown as typeof window.Cp;

console.log('window.Cp defined', window.Cp);
