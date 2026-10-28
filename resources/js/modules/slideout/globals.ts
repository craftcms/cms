import {compatify} from '@craftcms/garnish/compat';
import {Slideout} from './slideout';
import {CpScreenSlideout} from './cp-screen-slideout';
import {ElementEditorSlideout} from './element-editor-slideout';
import {registerCraftGlobals} from '@/common/craft-global';

// `compatify`, not the plain class: plugins subclass this global via the
// legacy `Garnish.Base.extend()` API, whose `this.base()` calls must
// dispatch into the modern methods.
const CompatSlideout = compatify(Slideout);

// Mirror the static API onto the compatified constructor. All of these are
// shared references: `defaults` may be mutated directly by consumers, and
// `instances`/`openPanels` are kept live by `removePanel`/`destroy` mutating
// them in place rather than reassigning. The static methods close over
// `Slideout` (the modern class), not `this`, so no `.bind()` is needed.
Object.assign(CompatSlideout, {
  defaults: Slideout.defaults,
  instances: Slideout.instances,
  openPanels: Slideout.openPanels,
  positionProp: Slideout.positionProp,
  totalPanels: Slideout.totalPanels,
  addPanel: Slideout.addPanel,
  removePanel: Slideout.removePanel,
  updateStyles: Slideout.updateStyles,
});

registerCraftGlobals({Slideout: CompatSlideout});

// Same reasoning one level down: plugins subclass CP screen slideouts via
// the legacy `.extend()` API.
const CompatCpScreenSlideout = compatify(CpScreenSlideout);

// `Craft.ElementEditorSlideout.defaults` is a separate object built by that
// file's own `.extend()` call; only this one needs mirroring here.
Object.assign(CompatCpScreenSlideout, {
  defaults: CpScreenSlideout.defaults,
});

registerCraftGlobals({CpScreenSlideout: CompatCpScreenSlideout});

// And once more for element editors — nothing in core `.extend()`s this
// anymore, but it stays compatified so plugin subclasses keep working.
const CompatElementEditorSlideout = compatify(ElementEditorSlideout);

Object.assign(CompatElementEditorSlideout, {
  defaults: ElementEditorSlideout.defaults,
});

registerCraftGlobals({ElementEditorSlideout: CompatElementEditorSlideout});
