/** global: Craft */
/** global: Garnish */
/**
 * Animated Image Controller
 */
Craft.AnimatedImageController = Garnish.Base.extend({
  $images: null,

  init: function () {
    this.$images = Garnish.getPotentiallyAnimatedImages();

    // Pause images based on system and control panel settings
    if (
      Garnish.prefersReducedMotion() ||
      Garnish.$bod.hasClass('prevent-autoplay')
    ) {
      this.$images.each((index, image) => {
        if (image.complete) {
          this.pause(image);
        }
      });
    }
  },

  coverImage: function (image) {
    const $image = $(image).first();
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

  pauseAll: function () {},

  pause: function (image) {
    const $image = $(image).first();
    this.coverImage($image);
  },
});
