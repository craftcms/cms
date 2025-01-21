/** global: Craft */
/** global: Garnish */
/**
 * IntervalManager class
 */
Craft.IntervalManager = Garnish.Base.extend(
  {
    _intervalId: null,

    init: function (settings) {
      this.setSettings(settings, Craft.IntervalManager.defaults);
    },

    start: function () {
      this._intervalId = setInterval(() => {
        this.settings.onInterval();
      }, this.settings.interval);
    },

    stop: function () {
      clearInterval(this._intervalId);
      this._intervalId = null;
    },
  },
  {
    defaults: {
      interval: 5000,
      onInterval: $.noop,
    },
  }
);
