import '@craftcms/cp';
import Cp from './bootstrap/cp.js';
import './components/cp/CpGlobalSidebar/CpGlobalSidebar.js';
import './components/cp/CpQueueIndicator/CpQueueIndicator.js';

// @ts-ignore
window.Cp = {
  ...(window.Cp || {}),
  ...Cp,
};

console.log('window.Cp defined', window.Cp);
