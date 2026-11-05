import {compatify} from '@craftcms/garnish/compat';
import {createElementEditor} from '@/common/slideouts/create-element-editor';
import {CpScreenSlideout} from './cp-screen-slideout';
import {ElementEditorSlideout} from './element-editor-slideout';
import {registerCraftGlobals} from '@/common/craft-global';

// Plugins subclass CP screen slideouts via the legacy `.extend()` API.
const CompatCpScreenSlideout = compatify(CpScreenSlideout);

Object.assign(CompatCpScreenSlideout, {
  defaults: CpScreenSlideout.defaults,
});

registerCraftGlobals({CpScreenSlideout: CompatCpScreenSlideout});

// It runs the legacy `Craft.ElementEditor`, which only the adapter loads.
// Compatified so plugin subclasses keep working.
const CompatElementEditorSlideout = compatify(ElementEditorSlideout);

Object.assign(CompatElementEditorSlideout, {
  defaults: ElementEditorSlideout.defaults,
});

registerCraftGlobals({ElementEditorSlideout: CompatElementEditorSlideout});

// The legacy element indexes open element editors through it.
registerCraftGlobals({createElementEditor});
