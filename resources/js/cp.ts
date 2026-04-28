import '@craftcms/cp';
import Cp from './bootstrap/cp.js';
import './components/CpGlobalSidebar.js';
import './components/CpQueueIndicator.js';

// @ts-ignore
window.Cp = {
  ...(window.Cp || {}),
  ...Cp,
};

console.log('window.Cp defined', window.Cp);
