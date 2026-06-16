import '@craftcms/cp';
import Cp from './bootstrap/cp.js';
import './modules/navigation/components/CpGlobalSidebar.js';
import './modules/navigation/components/CpQueueIndicator.js';

window.Cp = Cp as unknown as typeof window.Cp;

console.log('window.Cp defined', window.Cp);
