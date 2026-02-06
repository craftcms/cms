import '@craftcms/cp';
import Craft from './bootstrap/craft.js';
import './components/CpGlobalSidebar.js';
import './components/CpQueueIndicator.js';

// @ts-ignore
window.Craft = {
  ...(window.Craft || {}),
  ...Craft,
};

console.log('window.Craft defined', window.Craft);
