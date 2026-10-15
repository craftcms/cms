import {compatify} from '@craftcms/garnish/compat';
import {registerCraftGlobals} from '@/common/craft-global';
import {CpScreenSlideout} from './cp-screen-slideout';
import {ElementEditorSlideout} from './element-editor-slideout';

/**
 * Craft 5's CP screen and element editor slideout API, for plugins. Core opens
 * these screens with `Craft.openSlideout()`; both classes hand off to it
 * whenever the page has the Vue slideout stack.
 */

// `compatify`, not the plain class: plugins subclass these globals via the
// legacy `Garnish.Base.extend()` API, whose `this.base()` calls must dispatch
// into the modern methods.
const CompatCpScreenSlideout = compatify(CpScreenSlideout);

// `Craft.ElementEditorSlideout.defaults` is a separate object built by that
// file's own `.extend()` call; only this one needs mirroring here.
Object.assign(CompatCpScreenSlideout, {
  defaults: CpScreenSlideout.defaults,
});

const CompatElementEditorSlideout = compatify(ElementEditorSlideout);

Object.assign(CompatElementEditorSlideout, {
  defaults: ElementEditorSlideout.defaults,
});

/**
 * Opens an element editor slideout. Accepts `(elementType, settings)` or
 * `(elementType, element, settings)`, where `element` is the chip or card
 * being edited.
 */
function createElementEditor(
  elementType: string,
  element?: unknown,
  settings?: Record<string, unknown>
): ElementEditorSlideout {
  if (settings === undefined && element?.constructor === Object) {
    settings = element as Record<string, unknown>;
    element = null;
  }

  return new CompatElementEditorSlideout(element, {
    elementType,
    ...settings,
  }) as ElementEditorSlideout;
}

registerCraftGlobals({
  CpScreenSlideout: CompatCpScreenSlideout,
  ElementEditorSlideout: CompatElementEditorSlideout,
  createElementEditor,
});

export {CpScreenSlideout, ElementEditorSlideout, createElementEditor};
