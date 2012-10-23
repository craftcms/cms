/**
 * Ajax Queue Manager
 * @param workers amount of simultaneous workers
 * @param callback callback to perform when a queue is finished
 * @constructor
 * @package Assets
 *
 * @author Andris Sevcenko <andris@pixelandtonic.com>
 * @copyright Copyright (c) 2012 Pixel & Tonic, Inc
 */
function AjaxQueueManager (workers, callback, context) {
    this._workers = workers;
    this._queue = [];
    this._callback = callback;
    this._context = context;
    this._busyWorkers = 0;
}

/**
 * Add item to the queue
 * @param target Ajax POST target
 * @param parameters POST parameters
 * @param callback callback to perform on data
 */
AjaxQueueManager.prototype.addItem = function (target, parameters, callback) {
    this._queue.push({
        target: target,
        parameters: parameters,
        callback: callback
    });
};

/**
 * Process an item from the queue
 */
AjaxQueueManager.prototype.processItem = function () {

    if (this._queue.length == 0) {
        if (this._busyWorkers == 0) {
            this._callback();
        }
        return;
    }

    this._busyWorkers++;
    var item = this._queue.shift();
    var _t = this;
    $.post(item.target, item.parameters, function (data) {
        if (typeof item.callback == "function") {
            item.callback(data);
        }

        _t._busyWorkers--;

        // call final callback, if all done or queue another job if we can
        if (_t._busyWorkers == 0 && _t._queue.length == 0 && typeof _t._callback == "function") {
            if (typeof _t._context == "undefined"){
                _t._callback();
            } else {
                _t._callback.call(_t._context);
            }
        } else if (_t._queue.length > 0 && _t._busyWorkers < _t._workers) {
            // always keep the workers busy, even if we started  slow
            while (_t._busyWorkers < _t._workers && _t._queue.length > 0) {
                _t.processItem();
            }
        }

    });
};

/**
 * Start the queue
 */
AjaxQueueManager.prototype.startQueue = function () {
    while (this._busyWorkers < this._workers && this._queue.length > 0) {
        this.processItem();
    }
};