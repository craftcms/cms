/** global: Craft */
/** global: Garnish */

/**
 * @callback constructorCallback
 */
/**
 * Form observer
 */
Craft.FormObserver = Garnish.Base.extend({
  $container: null,
  /**
   * @type {constructorCallback}
   * @param {string} formData
   */
  _callback: null,
  _pauseLevel: 0,
  _timeout: null,
  _recentKeypress: false,
  _formData: null,
  /**
   * @type {MutationObserver}
   */
  _mutationObserver: null,
  _selectizeInputs: null,

  get isActive() {
    return this._pauseLevel === 0;
  },

  /**
   * @param {(jQuery|HTMLElement|string)} container
   * @param {constructorCallback} callback
   */
  init(container, callback) {
    this.$container = $(container);
    this._callback = callback;
    this._serialize();

    this.addListener(this.$container, 'change,input,keypress,keyup', (ev) => {
      if (this.isActive) {
        // slow down when actively typing
        if (['keypress', 'keyup'].includes(ev.type)) {
          this._recentKeypress = true;
        }
        this._checkFormAfterDelay();
      }
    });

    this._mutationObserver = new MutationObserver((records) => {
      for (const record of records) {
        if (this.isActive && this._formChanged(record)) {
          this._checkFormAfterDelay();
        }

        for (const node of record.addedNodes) {
          if (node instanceof Element) {
            this._initSelectizeInputs(node);
          }
        }

        if (
          record.attributeName === 'class' &&
          record.target instanceof Element &&
          record.target.classList.contains('selectized')
        ) {
          this._initSelectizeInput(record.target);
        }
      }
    });

    this._mutationObserver.observe(this.$container[0], {
      childList: true,
      subtree: true,
      characterData: true,
      attributeFilter: ['name', 'value', 'disabled', 'class'],
    });

    this._initSelectizeInputs(this.$container[0]);
  },

  /**
   * @param {MutationRecord} record
   * @returns {boolean}
   */
  _formChanged(record) {
    switch (record.type) {
      case 'childList':
        return (
          // was this for the text node of a <textarea>?
          (record.target.nodeName === 'TEXTAREA' &&
            record.target.hasAttribute('name')) ||
          // maybe a `[name]` node was added/removed
          this._hasNamedNodes(record.addedNodes) ||
          this._hasNamedNodes(record.removedNodes)
        );
      case 'attributes':
        switch (record.attributeName) {
          case 'name':
            // only matters if the element isn't disabled
            return !record.target.disabled;
          case 'value':
            // only matters if the element has a name attribute and isn't disabled
            return (
              record.target.hasAttribute('name') && !record.target.disabled
            );
          case 'disabled':
            // only matters if the element has a name attribute
            return record.target.hasAttribute('name');
        }
      case 'characterData':
        // maybe a <textarea> change
        return (
          record.target.parentNode instanceof Element &&
          record.target.parentNode.hasAttribute('name')
        );
      default:
        return false;
    }
  },

  /**
   * @param {Element} container
   */
  _initSelectizeInputs(container) {
    // we're now using selectize select_on_focus plugin which clears the dropdown's value on dropdown open;
    // that triggers a change event which triggers saving a draft and causes conditional fields/tabs to misbehave;
    // because of that, we are now emitting selectize dropdown open and close events;
    // we pause listening for changes on dropdown open (it happens before the focus event, so before the value is cleared)
    // and we resume on dropdown close to register the change in value (if one actually occurred);
    if (container.classList.contains('selectized')) {
      this._initSelectizeInput(container);
    } else {
      const inputs = container.querySelectorAll('.selectized');
      for (const input of inputs) {
        this._initSelectizeInput(input);
      }
    }
  },

  _initSelectizeInput(input) {
    // just in case the element was detached and re-inserted into the DOM
    this.removeAllListeners(input);
    this.addListener(input, 'selectizedropdownopen', () => {
      this.pause();
    });
    this.addListener(input, 'selectizedropdownclose', () => {
      setTimeout(() => {
        this.resume();
      }, 100);
    });
  },

  /**
   * @param {Node[]} nodes
   * @returns {boolean}
   */
  _hasNamedNodes(nodes) {
    for (const node of nodes) {
      if (
        node instanceof Element &&
        (node.hasAttribute('name') || node.querySelectorAll('[name]').length)
      ) {
        return true;
      }
    }
    return false;
  },

  _checkFormAfterDelay() {
    clearTimeout(this._timeout);
    this._timeout = setTimeout(
      () => {
        this.checkForm();
      },
      this._recentKeypress ? 1000 : 100
    );
  },

  checkForm() {
    clearTimeout(this._timeout);
    this._recentKeypress = false;
    if (this._formData !== this._serialize()) {
      this._callback(this._formData);
    }
  },

  _serialize() {
    if (this.$container[0].nodeName === 'FORM') {
      this._formData = this.$container.serialize();
    } else {
      this._formData = $('<form/>').append(this.$container.clone()).serialize();
    }
    return this._formData;
  },

  pause() {
    this._pauseLevel++;
  },

  resume() {
    if (this._pauseLevel === 0) {
      throw 'Craft.FormObserver::resume() should only be called after pause().';
    }

    // Only actually resume operation if this has been called the same
    // number of times that pause() was called
    this._pauseLevel--;

    if (this.isActive) {
      this.checkForm();
    }
  },

  destroy() {
    clearTimeout(this._timeout);
    this._mutationObserver.disconnect();
    delete this._mutationObserver;
    this.base();
  },
});
