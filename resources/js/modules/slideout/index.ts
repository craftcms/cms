import {compatify} from '@craftcms/garnish/compat';
import {Slideout, type SlideoutSettings} from './slideout';
import {containerSlideouts} from './support';
import {registerCraftGlobals} from '@/common/craft-global';

/**
 * No custom element / `.ce.ts` here: slideouts are always constructed
 * programmatically around dynamic content — there's no server-rendered
 * markup for a custom element to boot from. This file's job is the
 * `Craft.*` globals and the class-level open/close hook.
 */

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

// Repositioning open HUDs when a slideout opens or closes used to be hooked
// up here, via `Garnish.on(Slideout, 'open close')`. It now lives in the
// shared panel stack, so Vue slideouts trigger it too.

export {Slideout, type SlideoutSettings, containerSlideouts};
