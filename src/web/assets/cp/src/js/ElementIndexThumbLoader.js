/** global: Craft */
/** global: Garnish */
/**
 * Base Element Index View
 */
Craft.ElementIndexThumbLoader = Garnish.Base.extend(
    {
        queue: null,
        workers: [],

        init: function() {
            this.queue = [];

            for (var i = 0; i < 3; i++) {
                this.workers.push(new Craft.ElementIndexThumbLoader.Worker(this));
            }
        },

        load: function($thumbs) {
            this.queue = this.queue.concat($thumbs.toArray());

            if (this.queue.length) {
                // See if there are any inactive workers
                for (var i = 0; i < this.workers.length; i++) {
                    if (!this.workers[i].active) {
                        this.workers[i].loadNext();
                    }
                }
            }
        },

        destroy: function() {
            for (var i = 0; i < this.workers.length; i++) {
                this.workers[i].destroy();
            }

            this.base();
        }
    }
);

Craft.ElementIndexThumbLoader.Worker = Garnish.Base.extend(
    {
        loader: null,
        active: false,

        init: function(loader) {
            this.loader = loader;
        },

        loadNext: function() {
            var container = this.loader.queue.shift();
            if (typeof container === 'undefined') {
                this.active = false;
                return;
            }

            this.active = true;
            var $container = $(container);
            var $img = $('<img/>', {
                sizes: $container.attr('data-sizes'),
                srcset: $container.attr('data-srcset'),
                alt: ''
            });
            this.addListener($img, 'load', 'loadNext');
            $img.appendTo($container);
            picturefill({
                elements: [$img[0]]
            });
        }
    }
);
