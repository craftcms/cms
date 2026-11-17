/** global: Craft */

Object.assign(Craft, {
  _elementIndexClasses: {},

  /**
   * Registers an element index class for a given element type.
   *
   * @param {string} elementType
   * @param {function} func
   */
  registerElementIndexClass: function (elementType, func) {
    if (typeof this._elementIndexClasses[elementType] !== 'undefined') {
      throw (
        'An element index class has already been registered for the element type “' +
        elementType +
        '”.'
      );
    }

    this._elementIndexClasses[elementType] = func;
  },

  /**
   * Creates a new element index for a given element type.
   *
   * @param {string} elementType
   * @param {jQuery} $container
   * @param {Object} settings
   * @returns {BaseElementIndex}
   */
  createElementIndex: function (elementType, $container, settings) {
    var func;

    if (typeof this._elementIndexClasses[elementType] !== 'undefined') {
      func = this._elementIndexClasses[elementType];
    } else {
      func = Craft.BaseElementIndex;
    }

    return new func(elementType, $container, settings);
  },
});
