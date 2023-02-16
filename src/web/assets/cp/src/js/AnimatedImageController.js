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
        this.pause(image);
      });

      // Add mutation observer to listen for new images
      // const observer = new MutationObserver((mutations) => {
      //   for (let i = 0; i < mutations.length; i++) {
      //     for (var j = 0; j < mutations[i].addedNodes.length; j++) {
      //       this.checkNode(mutations[i].addedNodes[j]);
      //     }
      //   }
      // });
      //
      // observer.observe(document.documentElement, {
      //   childList: true,
      //   subtree: true,
      // });
    }
  },

  // checkNode: function (node) {
  //   if (node.nodeType === 1 && node.tagName === 'IMG') {
  //     if (!this.isWebpOrGif(node)) return;
  //     this.pause(node);
  //   } else if ($(node).find('img').length > 0) {
  //     const $childImages = $(node).find('img');
  //
  //     $childImages.each((index, image) => {
  //       if (this.isWebpOrGif(image)) {
  //         this.pause(image);
  //       }
  //     });
  //   }
  // },

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

  pauseAll: function () {},

  pause: function (image) {
    const $image = $(image);

    if ($image[0].complete) {
      this.coverImage($image);
    } else {
      this.addListener($image, 'load', () => {
        this.coverImage($image);
      });
    }
  },
});
