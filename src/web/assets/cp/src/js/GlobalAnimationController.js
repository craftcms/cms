/** global: Craft */
/** global: Garnish */
/**
 * Global Animation Controller
 */
Craft.GlobalAnimationController = Garnish.Base.extend({
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

    // Go through each image and create toggle + cover
    for (let i = 0; i < $images.length; i++) {
      const $image = $($images[i]);

      if ($image[0].complete) {
        this.coverImage($image);
        this.createToggle($image);
      } else {
        this.addListener($image, 'load', () => {
          this.coverImage($image);
          this.createToggle($image);
        });
      }
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

  getAnimationToggleButton: function (image) {
    return $(image).parent().find('[data-animation-toggle-btn]');
  },

  getAnimationCoverImage: function (image) {
    return $(image).parent().find('canvas');
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

  createToggle: function (image) {
    if (!this.getToggleEnabled(image)) return;

    const $image = $(image);
    const $wrapper = $image.parent();

    const $toggle = $('<button/>', {
      type: 'button',
      'data-icon': 'play',
      'data-animation-state': 'paused',
      'data-animation-toggle-btn': true,
      'aria-label': Craft.t('app', 'Play'),
      class: 'animated-image-toggle btn',
    });

    $wrapper.append($toggle);

    this.addListener($toggle, 'click', (ev) => {
      this.handleToggleClick(ev);
    });
  },

  handleToggleClick: function (event) {
    const $toggle = $(event.target);
    const isPaused = $toggle.attr('data-animation-state') === 'paused';
    const $image = $toggle.parent().find('img');

    if (isPaused) {
      this.play($image);
    } else {
      this.pause($image);
    }
  },

  pauseAll: function () {
    for (let i = 0; i < this.$images.length; i++) {
      this.pause(this.$images[i]);
    }
  },

  pause: function (image) {
    const $image = $(image);
    const $coverImage = this.getAnimationCoverImage($image);
    const $toggleBtn = this.getAnimationToggleButton($image);

    $coverImage.removeClass('hidden');
    $toggleBtn.attr({
      'aria-label': Craft.t('app', 'Play'),
      'data-animation-state': 'paused',
      'data-icon': 'play',
    });
  },

  playAll: function () {
    for (let i = 0; i < this.$images.length; i++) {
      this.play(this.$images[i]);
    }
  },

  play: function (image) {
    const $image = $(image);
    const $coverImage = this.getAnimationCoverImage($image);
    const $toggleBtn = this.getAnimationToggleButton($image);

    $coverImage.addClass('hidden');
    $toggleBtn.attr({
      'aria-label': Craft.t('app', 'Pause'),
      'data-animation-state': 'playing',
      'data-icon': 'pause',
    });
  },
});
