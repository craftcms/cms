/** global: Craft */
import {PromiseQueue} from '../../../../../resources/js/common/utils/promise-queue';

// Assigned here rather than from a Vite module: `Craft.cp` is constructed while
// this bundle evaluates, and it uses the queue straight away.
Craft.Queue = PromiseQueue;
Craft.queue = new PromiseQueue();
