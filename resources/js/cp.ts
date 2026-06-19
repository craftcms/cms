import '@craftcms/cp';
import Cp from './bootstrap/cp.js';
import './modules/navigation/components/CpGlobalSidebar.js';
import './modules/navigation/components/CpQueueIndicator.js';
// Field Layout Designer — modern @craftcms/garnish port; assigns
// window.Craft.FieldLayoutDesigner so the PHP-emitted constructor keeps working.
import './modules/field-layout-designer';

window.Cp = Cp;

console.log('window.Cp defined', window.Cp);
