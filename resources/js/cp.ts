import './shims/legacy-cp'; // ⚠️ TEMP legacy globals — remove when server-HTML jQuery is gone
import '@craftcms/cp';
import Cp from './bootstrap/cp.js';
import './modules/navigation/components/CpGlobalSidebar.js';
import './modules/navigation/components/CpQueueIndicator.js';

window.Cp = Cp;
