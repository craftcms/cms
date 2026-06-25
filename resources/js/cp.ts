import '@craftcms/cp';
import Cp from './bootstrap/cp.js';
import './modules/navigation/components/CpGlobalSidebar.js';
import './modules/navigation/components/CpQueueIndicator.js';

/**
 * Legacy ports, assigns window.Craft.* so PHP-emitted code still works
 * someday these will be removed and the classes will be used directly
 */
import './modules/sortable-checkbox-select';
import './modules/field-layout-designer';
import './modules/editable-table';
import './modules/generated-fields';

window.Cp = Cp;

console.log('window.Cp defined', window.Cp);
