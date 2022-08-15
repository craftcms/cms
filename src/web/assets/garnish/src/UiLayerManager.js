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
   */
  addLayer: function (container) {
    this.layers.push({
      $container: container ? $(container) : null,
      shortcuts: [],
      isModal: container ? $(container).attr('aria-modal') === 'true' : false,
    });
    this.trigger('addLayer', {
      layer: this.layer,
      $container: this.currentLayer.$container,
    });
    return this;
  },

  removeLayer: function () {
    if (this.layer === 0) {
      throw 'Can’t remove the base layer.';
    }
    this.layers.pop();
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

  triggerShortcut: function (ev) {
    const shortcut = this.layers[this.layer].shortcuts.find(
      (s) =>
        s.shortcut.keyCode === ev.keyCode &&
        s.shortcut.ctrl === Garnish.isCtrlKeyPressed(ev) &&
        s.shortcut.shift === ev.shiftKey &&
        s.shortcut.alt === ev.altKey
    );

    if (shortcut) {
      ev.preventDefault();
      shortcut.callback(ev);
    }
  },
});
