import {ThumbnailLoader} from '@craftcms/ui/utilities/thumbnail-loader';
import {registerCraftGlobals} from '@/common/craft-global';

/**
 * Legacy `Craft.ElementThumbLoader` — a thin bridge over `@craftcms/ui`'s
 * `ThumbnailLoader` (see the package's `components/thumbnail-loader/README.md`).
 *
 * Callers (`Craft.cp.elementThumbLoader`, element index views, slideouts,
 * modals, previews) pass `load()` a jQuery collection and expect every element
 * in it to be scanned for `.thumb[data-sizes]` descendants, so the override
 * fans the collection out to the modern `load(root, selector)` signature.
 * `Preview`/`LivePreview` call the static `Craft.ElementThumbLoader.retryAll()`,
 * inherited from `ThumbnailLoader`.
 *
 * Already modern code (it imported `@craftcms/ui` even in the legacy bundle);
 * this just relocates it into `resources/js`.
 */
class ElementThumbLoader extends ThumbnailLoader {
  override load($elements: any): void {
    for (let i = 0; i < $elements.length; i++) {
      super.load($elements[i], '.thumb[data-sizes]');
    }
  }
}

registerCraftGlobals({ElementThumbLoader});

export {ElementThumbLoader};
