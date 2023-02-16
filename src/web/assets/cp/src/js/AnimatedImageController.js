/** global: Craft */
/** global: Garnish */
/**
 * Animated Image Controller
 */
Craft.AnimatedImageController = Garnish.Base.extend({
  $images: null,

  init: function () {
    this.$images = $();
    const $images = Garnish.getPotentiallyAnimatedImages();

    // Pause images based on system and control panel settings
    if (
      Garnish.prefersReducedMotion() ||
      Garnish.$bod.hasClass('prevent-autoplay')
    ) {
      if (Garnish.$bod.data('animation-controller')) {
        console.warn('Cannot instantiate another animation controller');
        return;
      }

      this.addImages($images);
    }
  },

  addImages: function ($images) {
    this.$images = this.$images.add($images);
    $images.data('animation-controller', this);

    // Go through each image and pause
    for (let i = 0; i < $images.length; i++) {
      this.pause($images[i]);
    }

    Garnish.$bod.data('animation-controller', this);
  },

  isWebpOrGif: function (image) {
    const $image = $(image);
    const imageSrc = $image.attr('src');
    const imageSrcset = $image.attr('srcset');

    let value = false;

    if (imageSrc) {
      value =
        imageSrc.indexOf('.gif') !== -1 || imageSrc.indexOf('.webp') !== -1;
    } else if (imageSrcset) {
      value =
        imageSrcset.indexOf('.gif') !== -1 ||
        imageSrcset.indexOf('.webp') !== -1;
    }

    return value;
  },

  getToggleEnabled: function (image) {
    return $(image).attr('data-animation-toggle') !== null;
  },

  coverImage: function (image) {
    const $image = $(image);
    const $parent = $image.parent();
    const width = $image.width();
    const height = $image.height();

    const $canvas = $('<canvas></canvas>')
      .attr({
        width: width,
        height: height,
        'aria-hidden': 'true',
        role: 'presentation',
      })
      .css({
        position: 'absolute',
        top: '50%',
        left: '50%',
        transform: 'translate(-50%, -50%)',
      });

    // Draw first frame on canvas
    $canvas[0].getContext('2d').drawImage($image[0], 0, 0, width, height);

    // Place canvas inside parent
    $parent.css({
      position: 'relative',
    });
    $canvas.insertBefore($image);
  },

  addToggle: function (image) {
    if (!this.getToggleEnabled(image)) return;

    const $image = $(image);
    const $wrapper = $image.parent();

    const $toggle = $('<button/>', {
      type: 'button',
      text: 'Play',
    });

    $wrapper.append($toggle);
    // $toggle.append($wrapper);
  },

  pauseAll: function () {},

  pause: function (image) {
    const $image = $(image);

    if ($image[0].complete) {
      this.coverImage($image);
      this.addToggle($image);
    } else {
      this.addListener($image, 'load', () => {
        this.coverImage($image);
        this.addToggle($image);
      });
    }
  },
});
