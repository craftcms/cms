import '@craftcms/cp';
import './components/CpGlobalSidebar.js';
import './components/CpQueueIndicator.js';
import Craft from './bootstrap/craft';

// @ts-ignore
window.Craft = Craft;

// @TODO ideally we wouldn't have to wait until load for this
document.addEventListener('DOMContentLoaded', () => {
  // noinspection JSIgnoredPromiseFromCall
  Craft.start();
});
