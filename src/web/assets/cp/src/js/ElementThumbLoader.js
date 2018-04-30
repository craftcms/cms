/** global: Craft */
/** global: Garnish */
/**
 * Base Element Index View
 */
Craft.ElementThumbLoader = Garnish.Base.extend(
    {
        queue: null,
        workers: [],

        init: function() {
            this.queue = [];

            for (var i = 0; i < 3; i++) {
                this.workers.push(new Craft.ElementThumbLoader.Worker(this));
            }
        },

        load: function($elements) {
            this.queue = this.queue.concat($elements.find('.elementthumb').toArray());

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

Craft.ElementThumbLoader.Worker = Garnish.Base.extend(
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
            if ($container.find('img').length) {
                this.loadNext();
                return;
            }
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
