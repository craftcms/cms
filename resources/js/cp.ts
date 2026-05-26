import '@craftcms/cp';
import Cp from './bootstrap/cp.js';
import './modules/navigation/components/CpGlobalSidebar.js';
import './modules/navigation/components/CpQueueIndicator.js';

// @ts-expect-error — window.Cp is a partial type at assignment time
window.Cp = {
  ...(window.Cp || {}),
  ...Cp,
};

console.log('window.Cp defined', window.Cp);
