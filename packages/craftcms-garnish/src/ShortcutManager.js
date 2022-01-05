/** global: Garnish */
/**
 * Keyboard shortcut manager class
 *
 * This can be used to map keyboard events to the current UI "layer" (whether that's the base document,
 * a modal, an HUD, or a menu).
 */
Garnish.ShortcutManager = Garnish.Base.extend(
    {
        shortcuts: null,
        layer: 0,

        init: function() {
            this.shortcuts = [[]];
            this.addListener(Garnish.$bod, 'keydown', 'triggerShortcut');
        },

        addLayer: function() {
            this.layer++;
            this.shortcuts.push([]);
            return this;
        },

        removeLayer: function() {
            if (this.layer === 0) {
                throw 'Can’t remove the base layer.';
            }
            this.layer--;
            this.shortcuts.pop();
            return this;
        },

        registerShortcut: function(shortcut, callback, layer) {
            shortcut = this._normalizeShortcut(shortcut);
            if (typeof layer === 'undefined') {
                layer = this.layer;
            }
            this.shortcuts[layer].push({
                key: JSON.stringify(shortcut),
                shortcut: shortcut,
                callback: callback,
            });
            return this;
        },

        unregisterShortcut: function(shortcut, layer) {
            shortcut = this._normalizeShortcut(shortcut);
            var key = JSON.stringify(shortcut);
            if (typeof layer === 'undefined') {
                layer = this.layer;
            }
            for (var i = 0; i < this.shortcuts[layer].length; i++) {
                if (this.shortcuts[layer][i].key === key) {
                    this.shortcuts[layer].splice(i, 1);
                    break;
                }
            }
            return this;
        },

        _normalizeShortcut: function(shortcut) {
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

        triggerShortcut: function(ev) {
            var shortcut;
            for (var i = 0; i < this.shortcuts[this.layer].length; i++) {
                shortcut = this.shortcuts[this.layer][i].shortcut;
                if (
                    shortcut.keyCode === ev.keyCode &&
                    shortcut.ctrl === Garnish.isCtrlKeyPressed(ev) &&
                    shortcut.shift === ev.shiftKey &&
                    shortcut.alt === ev.altKey
                ) {
                    ev.preventDefault();
                    this.shortcuts[this.layer][i].callback(ev);
                    break;
                }
            }
        },
    }
);

Garnish.shortcutManager = new Garnish.ShortcutManager();
