import {QueueService} from '@craftcms/cp/src/services/Queue.js';
import {ConfigService} from '@craftcms/cp/src/services/Config.js';

/**
 * Components
 */
import '@craftcms/cp/dist/components/nav-list/nav-list.ts';
import '@craftcms/cp/dist/components/nav-item/nav-item.ts';

import './components/CpGlobalSidebar.js';
import './components/CpQueueIndicator.js';

let bootedCallbacks: Array<(instance: any) => void> =
  window.bootedCallbacks || [];

const config = ConfigService.getInstance();
const queue = QueueService.getInstance();

export const Craft = {
  get $config() {
    return config;
  },

  get $queue() {
    return queue;
  },

  booted(callback: (instance: any) => void) {
    bootedCallbacks.push(callback);
  },

  boot() {
    config.initialize(window.Craft);
    queue.initialize({
      enabled: true,
      appId: config.get('systemUid', ''),
      canAccessQueueManager: config.get('canAccessQueueManager', false),
    });

    bootedCallbacks.forEach((callback) => callback(this));
    bootedCallbacks = [];
  },
};

Craft.boot();
