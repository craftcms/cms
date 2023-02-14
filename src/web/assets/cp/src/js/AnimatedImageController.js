/** global: Craft */
/** global: Garnish */
/**
 * Animated Image Controller
 */
Craft.AnimatedImageController = Garnish.Base.extend({
  $images: null,

  init: function () {
    this.$images = Garnish.getPotentiallyAnimatedImages();

    console.log(this.$images);
  },
});
