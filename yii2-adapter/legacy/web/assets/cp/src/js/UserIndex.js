/** global: Craft */
/** global: Garnish */
/**
 * User index class
 */
Craft.UserIndex = Craft.BaseElementIndex.extend({
  init: function (elementType, $container, settings) {
    this.on('selectSource', this.updateUrl.bind(this));
    this.base(elementType, $container, settings);
  },

  getDefaultSourceKey: function () {
    // Did they request a specific group in the URL?
    if (
      this.settings.context === 'index' &&
      typeof defaultSourceSlug !== 'undefined'
    ) {
      for (let i = 0; i < this.$sources.length; i++) {
        const $source = $(this.$sources[i]);
        if ($source.data('slug') === defaultSourceSlug) {
          return $source.data('key');
        }
      }
    }

    return this.base();
  },

  updateUrl: function () {
    if (this.settings.context === 'index') {
      let uri = 'users';
      const slug = this.$source.data('slug');
      if (slug) {
        uri += `/${slug}`;
      }
      Craft.setPath(uri);
    }
  },
});

// Register it!
Craft.registerElementIndexClass('craft\\elements\\User', Craft.UserIndex);
