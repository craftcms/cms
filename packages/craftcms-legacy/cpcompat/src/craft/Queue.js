/** global: Craft */
import {PromiseQueue} from '../../../../../resources/js/common/utils/promise-queue';

// Assigned here rather than from a Vite module: the legacy `Craft.cp` uses the
// queue as soon as it's constructed.
Craft.Queue = PromiseQueue;
Craft.queue = new PromiseQueue();
