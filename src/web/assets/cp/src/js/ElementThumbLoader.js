/** global: Craft */
/** global: Garnish */
/**
 * Base Element Index View
 */
Craft.ElementThumbLoader = Garnish.Base.extend(
  {
    queue: null,
    workers: [],

    init: function () {
      this.queue = [];

      for (let i = 0; i < 3; i++) {
        this.workers.push(new Craft.ElementThumbLoader.Worker(this));
      }
    },

    load: function ($elements) {
      // Only immediately load the visible images
      let $thumbs = $elements.find('.thumb[data-sizes]');
      for (let i = 0; i < $thumbs.length; i++) {
        let $thumb = $thumbs.eq(i);
        if ($thumb.is(':visible')) {
          this.addToQueue($thumb[0]);
        } else {
          let key = 'thumb' + Math.floor(Math.random() * 1000000);
          Craft.ElementThumbLoader.invisibleThumbs[key] = [this, $thumb];
          Garnish.$doc.on(
            `scroll.${key}`,
            {
              $thumb: $thumb,
              key: key,
            },
            (ev) => {
              if ($thumb) {
                delete Craft.ElementThumbLoader.invisibleThumbs[ev.data.key];
                this.addToQueue(ev.data.$thumb[0]);
              }
            }
          );
        }
      }
    },

    addToQueue: function (thumb) {
      this.queue.push(thumb);

      // See if there are any inactive workers
      for (let i = 0; i < this.workers.length; i++) {
        if (!this.workers[i].active) {
          this.workers[i].loadNext();
        }
      }
    },

    destroy: function () {
      for (let i = 0; i < this.workers.length; i++) {
        this.workers[i].destroy();
      }

      this.base();
    },
  },
  {
    invisibleThumbs: {},
    retryAll: function () {
      for (let key in Craft.ElementThumbLoader.invisibleThumbs) {
        let [queue, $thumb] = Craft.ElementThumbLoader.invisibleThumbs[key];
        delete Craft.ElementThumbLoader.invisibleThumbs[key];
        queue.load($thumb.parent());
      }
    },
  }
);

Craft.ElementThumbLoader.Worker = Garnish.Base.extend({
  loader: null,
  active: false,
  container: null,
  _interval: null,
  _timeout: null,

  init: function (loader) {
    this.loader = loader;
  },

  activate: function () {
    if (this.active) {
      return;
    }
    this.active = true;
    // keep track of whether the current container is actually in the DOM
    this.clearInterval();
    this._interval = setInterval(() => {
      this.loadNextIfRemoved();
    }, 500);
  },

  deactivate: function () {
    if (!this.active) {
      return;
    }
    this.active = false;
    this.clearInterval();
    this.clearTimeout();
  },

  clearInterval: function () {
    if (this._interval) {
      clearInterval(this._interval);
      this._interval = null;
    }
  },

  clearTimeout: function () {
    if (this._timeout) {
      clearTimeout(this._timeout);
      this._timeout = null;
    }
  },

  loadNext: function () {
    this.clearTimeout();

    this.container = this.loader.queue.shift();
    if (typeof this.container === 'undefined') {
      this.deactivate();
      return;
    }

    if (this.loadNextIfRemoved()) {
      return;
    }

    const $container = $(this.container);
    if ($container.find('img').length) {
      this.loadNext();
      return;
    }

    this.activate();

    // give up after 30 seconds
    this._timeout = setTimeout(() => {
      this.loadNext();
    }, 30000);

    const $img = $('<img/>', {
      sizes: $container.attr('data-sizes'),
      srcset: $container.attr('data-srcset'),
      alt: $container.attr('data-alt') || '',
    });
    this.addListener($img, 'load,abort,error', 'loadNext');
    $img.appendTo($container);
    picturefill({
      elements: [$img[0]],
    });
  },

  loadNextIfRemoved() {
    if (this.container && !document.body.contains(this.container)) {
      this.loadNext();
      return true;
    }
    return false;
  },
});
