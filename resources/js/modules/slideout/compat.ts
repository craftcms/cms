import {compatify} from '@craftcms/garnish/compat';
import {ElementEditorSlideout} from './element-editor-slideout';
import {registerCraftGlobals} from '@/common/craft-global';

// It runs the legacy `Craft.ElementEditor`, which only the adapter loads.
// Compatified so plugin subclasses keep working.
const CompatElementEditorSlideout = compatify(ElementEditorSlideout);

Object.assign(CompatElementEditorSlideout, {
  defaults: ElementEditorSlideout.defaults,
});

registerCraftGlobals({ElementEditorSlideout: CompatElementEditorSlideout});
