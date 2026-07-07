import '@craftcms/ui';
import './bootstrap/cp.js';
import './modules/navigation/components/cp-global-sidebar.js';
import './modules/navigation/components/cp-queue-indicator.js';

/**
 * Legacy ports, assigns window.Craft.* so PHP-emitted code still works
 * someday these will be removed and the classes will be used directly
 */
import './modules/sortable-checkbox-select';
import './modules/listbox';
import './modules/field-layout-designer';
import './modules/editable-table';
import './modules/generated-fields';
