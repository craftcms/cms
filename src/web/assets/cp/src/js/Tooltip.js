/** global: Craft */
/** global: Garnish */
import $ from 'jquery';

/**
 * Tooltip
 */
Craft.Tooltip = Garnish.Base.extend({
  _$trigger: null,
  _message: null,
  hud: null,
  $p: null,
  hoverTimeout: null,
  triggerHit: false,
  shownViaHover: false,
  ignoreFocus: false,

  init: function (trigger, message) {
    this.$trigger = $(trigger);
    this.message = message;

    // do our own mouseover/mouseout checks since the native ones are unreliable
    this.addListener(Garnish.$bod, 'mousemove', (ev) => {
      if (
        this.triggerHit !==
        (this.triggerHit = Garnish.hitTest(ev.pageX, ev.pageY, this._$trigger))
      ) {
        if (this.triggerHit) {
          if (!this.showing) {
            this.hoverTimeout = setTimeout(() => {
              this.show();
              this.shownViaHover = true;
            }, 500);
          }
        } else {
          clearTimeout(this.hoverTimeout);
          if (this.shownViaHover) {
            this.hide();
          }
        }
      }
    });
  },

  get showing() {
    return this.hud && this.hud.showing;
  },

  get $trigger() {
    return this._$trigger;
  },

  set $trigger($trigger) {
    if (this._$trigger) {
      this.removeAllListeners(this._$trigger);
    }

    this._$trigger = $trigger;

    this._$trigger.on('focus', () => {
      if (!this.ignoreFocus) {
        this.show();
      }
    });
    this._$trigger.on('blur', () => {
      this.hide();
    });
    this._$trigger.on('activate', () => {
      this.toggle();
      this.ignoreFocus = true;
      this._$trigger.focus();
      this.ignoreFocus = false;
    });

    if (this.hud) {
      this.hud.$trigger = $trigger;
      if (this.hud.showing) {
        this.hud.updateSizeAndPosition(true);
      }
    }
  },

  get message() {
    return this._message;
  },

  set message(message) {
    this._message = message;

    if (this.$p) {
      this.$p.text(message);
    }
  },

  show: function (userId) {
    this.shownViaHover = false;

    if (this.showing) {
      return;
    }

    if (!this.hud) {
      this.$p = $('<p/>', {text: this._message});
      this.hud = new Garnish.HUD(this._$trigger, this.$p, {
        withShade: false,
        onShow: () => {
          this.onShow();
        },
        onHide: () => {
          this.onHide();
        },
      });
    } else {
      this.hud.show();
    }
  },

  hide: function () {
    if (!this.showing) {
      return;
    }

    if (this.hud) {
      this.hud.hide();
    }
  },

  toggle: function () {
    if (this.showing) {
      this.hide();
    } else {
      this.show();
    }
  },

  onShow: function () {
    clearTimeout(this.hoverTimeout);
  },

  onHide: function () {
    clearTimeout(this.hoverTimeout);
  },
});
