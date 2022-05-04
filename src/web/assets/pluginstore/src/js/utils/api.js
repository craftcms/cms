/* global Craft */

class Api {
  abortController = null;

  setAbortController(abortController) {
    this.abortController = abortController;
  }

  /**
   * Cancel requests.
   */
  cancelRequests() {
    if (this.abortController) {
      this.abortController.abort();
    }
  }

  /**
   * Send API request.
   *
   * @param {string} method
   * @param {string} uri
   * @param {object} options
   *
   * @returns {Promise}
   */
  sendApiRequest(method, uri, options) {
    options = this.prepareOptions(options);
    return Craft.sendApiRequest(method, uri, options);
  }

  /**
   * Send action request.
   *
   * @param {string} method
   * @param {string} action
   * @param {object} options
   *
   * @returns {Promise}
   */
  sendActionRequest(method, action, options) {
    options = this.prepareOptions(options);
    return Craft.sendActionRequest(method, action, options);
  }

  /**
   * Prepare options.
   *
   * @param {object} options
   *
   * @returns {object}
   */
  prepareOptions(options) {
    if (
      !this.abortController ||
      (this.abortController &&
        this.abortController.signal &&
        this.abortController.signal.aborted)
    ) {
      this.abortController = new AbortController();
    }

    if (!options) {
      options = {};
    }

    // Add abort signal if needed
    if (!options.signal) {
      options.signal = this.abortController.signal;
    }

    return options;
  }
}

const ApiInstance = new Api();

export default ApiInstance;
