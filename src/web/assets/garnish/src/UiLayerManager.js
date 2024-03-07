import Garnish from './Garnish.js';
import Base from './Base.js';
import $ from 'jquery';

/**
 * UI Layer Manager class
 *
 * This is used to manage the visible UI “layers”, including the base document, and any open modals, HUDs, slideouts, or menus.
 */
export default Base.extend({
  layers: null,

  init: function () {
    this.layers = [
      {
        $container: Garnish.$bod,
        shortcuts: [],
        options: {
          bubble: false,
        },
      },
    ];
    this.addListener(Garnish.$bod, 'keydown', 'triggerShortcut');
  },

  get layer() {
    return this.layers.length - 1;
  },

  get currentLayer() {
    return this.layers[this.layer];
  },

  get modalLayers() {
    return this.layers.filter((layer) => layer.isModal === true);
  },

  get highestModalLayer() {
    return this.modalLayers.pop();
  },

  /**
   * Registers a new UI layer.
   *
   * @param {jQuery|HTMLElement} [container]
   * @param {Object} [options]
   */
  addLayer: function (container, options) {
    if ($.isPlainObject(container)) {
      options = container;
      container = null;
    }

    options = Object.assign(
      {
        bubble: false,
      },
      options || {}
    );

    this.layers.push({
      $container: container ? $(container) : null,
      shortcuts: [],
      isModal: container ? $(container).attr('aria-modal') === 'true' : false,
      options: options,
    });
    this.trigger('addLayer', {
      layer: this.layer,
      $container: this.currentLayer.$container,
      options: options,
    });
    return this;
  },

  removeLayer: function (layer) {
    if (this.layer === 0) {
      throw 'Can’t remove the base layer.';
    }

    if (layer) {
      const layerIndex = this.getLayerIndex(layer);
      if (layerIndex) {
        this.removeLayerAtIndex(layerIndex);
      }
    } else {
      this.layers.pop();
      this.trigger('removeLayer');
      return this;
    }
  },

  getLayerIndex: function (layer) {
    layer = $(layer).get(0);
    let layerIndex;

    $(this.layers).each(function (index) {
      if (this.$container !== null && this.$container.get(0) === layer) {
        layerIndex = index;
        return false;
      }
    });

    return layerIndex;
  },

  removeLayerAtIndex: function (index) {
    this.layers.splice(index, 1);
    this.trigger('removeLayer');
    return this;
  },

  registerShortcut: function (shortcut, callback, layer) {
    shortcut = this._normalizeShortcut(shortcut);
    if (typeof layer === 'undefined') {
      layer = this.layer;
    }
    this.layers[layer].shortcuts.push({
      key: JSON.stringify(shortcut),
      shortcut: shortcut,
      callback: callback,
    });
    return this;
  },

  unregisterShortcut: function (shortcut, layer) {
    shortcut = this._normalizeShortcut(shortcut);
    const key = JSON.stringify(shortcut);
    if (typeof layer === 'undefined') {
      layer = this.layer;
    }
    const index = this.layers[layer].shortcuts.findIndex((s) => s.key === key);
    if (index !== -1) {
      this.layers[layer].shortcuts.splice(index, 1);
    }
    return this;
  },

  _normalizeShortcut: function (shortcut) {
    if (typeof shortcut === 'number') {
      shortcut = {keyCode: shortcut};
    }

    if (typeof shortcut.keyCode !== 'number') {
      throw 'Invalid shortcut';
    }

    return {
      keyCode: shortcut.keyCode,
      ctrl: !!shortcut.ctrl,
      shift: !!shortcut.shift,
      alt: !!shortcut.alt,
    };
  },

  triggerShortcut: function (ev, layerIndex) {
    if (typeof layerIndex === 'undefined') {
      layerIndex = this.layer;
    }
    const layer = this.layers[layerIndex];
    const shortcut = layer.shortcuts.find(
      (s) =>
        s.shortcut.keyCode === ev.keyCode &&
        s.shortcut.ctrl === Garnish.isCtrlKeyPressed(ev) &&
        s.shortcut.shift === ev.shiftKey &&
        s.shortcut.alt === ev.altKey
    );

    ev.bubbleShortcut = () => {
      if (layerIndex > 0) {
        this.triggerShortcut(ev, layerIndex - 1);
      }
    };

    if (shortcut) {
      ev.preventDefault();
      shortcut.callback(ev);
    } else if (layer.options.bubble) {
      ev.bubbleShortcut();
    }
  },
});
