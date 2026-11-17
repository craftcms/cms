/**
 * Uploads a file through an upload session: starts the session, sends the file
 * where the session says (straight to storage, or to Craft), and completes it.
 *
 * Mirrors Craft 6’s `Craft.Uploads.FileUpload`.
 */

/** Defaults for new uploads, such as `url` and `csrfToken`. */
export const defaults = {};

/** Headers browsers set themselves, and refuse to let scripts set. */
const forbiddenHeaders = ['content-length', 'host'];

export class UploadError extends Error {
  /**
   * @param {string} message
   * @param {number} status The HTTP status code, or 0 if there was no response
   */
  constructor(message, status = 0) {
    super(message);
    this.name = 'UploadError';
    this.status = status;
  }
}

export class FileUpload {
  /**
   * @param {File} file
   * @param {Object} options
   * @param {string} [options.url] The `uploads/start` action URL
   * @param {string} [options.csrfToken]
   * @param {Object} [options.parameters] The upload handler’s parameters, such as `folderId`
   * @param {function(number, number)} [options.onProgress] Called with the bytes sent and the file’s size
   * @param {function(string)} [options.onStateChange] Called with `uploading`, `completing`, `completed`, `failed` or `canceled`
   */
  constructor(file, options = {}) {
    this.file = file;
    this.options = Object.assign({}, defaults, options);
    this.state = 'ready';
    this._session = null;
    this._transferred = false;
    this._result = null;
    this._running = null;
    this._xhr = null;
    this._canceled = false;
    this._cancelSent = false;
  }

  /**
   * Uploads the file, resolving with the upload handler’s result. Calling it
   * again after a failure picks up where the upload left off.
   *
   * @returns {Promise<Object>}
   */
  upload() {
    this._running ??= this._run().finally(() => {
      this._running = null;
    });

    return this._running;
  }

  /**
   * Stops the upload and deletes anything it sent.
   *
   * @returns {Promise<void>}
   */
  async cancel() {
    if (this.state === 'completed' || this.state === 'canceled') {
      return;
    }

    if (this.state === 'completing') {
      throw new UploadError(
        'The file is being saved and can no longer be canceled.',
        409
      );
    }

    this._canceled = true;
    this._setState('canceled');
    this._xhr?.abort();
    await this._sendCancel();
  }

  async _run() {
    if (this._result) {
      return this._result.value;
    }

    this._throwIfCanceled();
    this._setState('uploading');

    try {
      if (!this._session) {
        this._session = await this._post(this.options.url, {
          ...this.options.parameters,
          filename: this.file.name,
          size: this.file.size,
        });

        if (this._canceled) {
          // It was canceled before there was a session to cancel
          await this._sendCancel();
        }
      }

      this._throwIfCanceled();

      if (!this._transferred) {
        try {
          await this._transfer(this._session.transport);
        } catch (error) {
          // Craft's transfer URL only accepts one file, so a conflict means an
          // earlier attempt's file arrived after all. Completing checks it.
          if (error.status !== 409) {
            throw error;
          }
        }
        this._transferred = true;
      }

      this._throwIfCanceled();
      this._setState('completing');

      try {
        this._result = {value: await this._post(this._session.urls.complete)};
      } catch (error) {
        // Craft deletes files it rejects, such as one of the wrong size, and
        // reports files that never arrived, so retries send the file again
        if (error.status === 400) {
          this._transferred = false;
        }
        throw error;
      }
      this._setState('completed');

      return this._result.value;
    } catch (error) {
      if (!this._canceled) {
        this._setState('failed');
      }

      throw error;
    }
  }

  _transfer(transport) {
    if (transport?.type !== 'put') {
      return Promise.reject(
        new UploadError(`Unsupported upload transport “${transport?.type}”.`)
      );
    }

    const {url, method = 'PUT', headers = {}} = transport.options;

    return new Promise((resolve, reject) => {
      const xhr = (this._xhr = new XMLHttpRequest());
      xhr.open(method, url);

      // Send every signed header, since storage rejects requests without them
      for (const [name, value] of Object.entries(headers)) {
        if (!forbiddenHeaders.includes(name.toLowerCase())) {
          xhr.setRequestHeader(name, value);
        }
      }

      xhr.upload.addEventListener('progress', (event) => {
        this.options.onProgress?.(event.loaded, this.file.size);
      });
      xhr.addEventListener('load', () => {
        if (xhr.status >= 200 && xhr.status < 300) {
          this.options.onProgress?.(this.file.size, this.file.size);
          resolve();
        } else {
          reject(responseError(xhr.status, xhr.responseText));
        }
      });
      xhr.addEventListener('error', () => {
        reject(new UploadError('The file could not be sent.'));
      });
      xhr.addEventListener('abort', () => {
        reject(new UploadError('The upload was canceled.'));
      });
      xhr.addEventListener('loadend', () => {
        this._xhr = null;
      });

      xhr.send(this.file);
    });
  }

  async _sendCancel() {
    if (!this._session || this._cancelSent) {
      return;
    }

    this._cancelSent = true;

    try {
      await this._post(this._session.urls.cancel);
    } catch {
      // Craft deletes the session when it expires anyway
    }
  }

  async _post(url, data = {}) {
    const headers = {
      Accept: 'application/json',
      'Content-Type': 'application/json',
      'X-Requested-With': 'XMLHttpRequest',
    };

    if (this.options.csrfToken) {
      headers['X-CSRF-Token'] = this.options.csrfToken;
    }

    let response;

    try {
      response = await fetch(url, {
        method: 'POST',
        headers,
        body: JSON.stringify(data),
        credentials: 'same-origin',
      });
    } catch {
      throw new UploadError('Craft could not be reached.');
    }

    const text = await response.text();

    if (!response.ok) {
      throw responseError(response.status, text);
    }

    return JSON.parse(text);
  }

  _throwIfCanceled() {
    if (this._canceled) {
      throw new UploadError('The upload was canceled.');
    }
  }

  _setState(state) {
    if (this.state !== state) {
      this.state = state;
      this.options.onStateChange?.(state);
    }
  }
}

/**
 * Reads the error message from a Craft (JSON) or storage (XML) response.
 */
function responseError(status, body) {
  let message;

  try {
    const data = JSON.parse(body);
    message = data.message || data.error;
  } catch {
    message = body?.match(/<Message>([^<]*)<\/Message>/)?.[1];
  }

  return new UploadError(
    message || `The upload failed with status ${status}.`,
    status
  );
}
