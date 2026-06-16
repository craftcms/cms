import {ThumbnailLoader} from '@craftcms/cp/utilities/thumbnail-loader.ts.mjs';

/** global: Craft */
/** global: Garnish */
/**
 * Base Element Index View
 */
Craft.ElementThumbLoader = class extends ThumbnailLoader {
  load($elements) {
    super.load($elements[0], '.thumb[data-sizes]');
  }
};
