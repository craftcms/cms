import {Garnish, HUD} from '@craftcms/garnish';
import {compatify} from '@craftcms/garnish/compat';
import {Slideout, type SlideoutSettings} from './slideout';
import {
  CpScreenSlideout,
  type CpScreenSlideoutSettings,
} from './cp-screen-slideout';
import {
  ElementEditorSlideout,
  type ElementEditorSlideoutSettings,
} from './element-editor-slideout';
import {containerSlideouts} from './support';

/**
 * No custom element / `.ce.ts` here: slideouts are always constructed
 * programmatically around dynamic content — there's no server-rendered
 * markup for a custom element to boot from. This file's job is the
 * `Craft.*` globals and the class-level open/close hook.
 */

// `compatify`, not the plain class: `Craft.AuthMethodSetup.Slideout` still
// subclasses this global via the legacy `Garnish.Base.extend()` API, whose
// `this.base()` calls must dispatch into the modern methods.
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

const craft = (window as any).Craft ?? ((window as any).Craft = {});
craft.Slideout = CompatSlideout;

// Same reasoning one level down: plugins subclass CP screen slideouts via
// the legacy `.extend()` API.
const CompatCpScreenSlideout = compatify(CpScreenSlideout);

// `Craft.ElementEditorSlideout.defaults` is a separate object built by that
// file's own `.extend()` call; only this one needs mirroring here.
Object.assign(CompatCpScreenSlideout, {
  defaults: CpScreenSlideout.defaults,
});

craft.CpScreenSlideout = CompatCpScreenSlideout;

// And once more for element editors — nothing in core `.extend()`s this
// anymore, but it stays compatified so plugin subclasses keep working.
const CompatElementEditorSlideout = compatify(ElementEditorSlideout);

Object.assign(CompatElementEditorSlideout, {
  defaults: ElementEditorSlideout.defaults,
});

craft.ElementEditorSlideout = CompatElementEditorSlideout;

// Reposition any open HUDs whenever a slideout opens or closes. Registered
// against the modern `Slideout` class: the class-event dispatch is an
// `instanceof` check, and `compatify()`/`.extend()` build real subclasses,
// so this fires for legacy-extended slideouts too.
Garnish.on(Slideout, 'open close', () => {
  // The legacy garnish bundle keeps its own `Garnish.HUD` instance registry,
  // separate from the modern class's — sweep both.
  const legacyInstances = (window as any).Garnish?.HUD?.instances ?? [];
  for (const hud of [...HUD.instances, ...legacyInstances]) {
    if (hud.showing) {
      hud.updateSizeAndPosition(true);
    }
  }
});

export {
  Slideout,
  type SlideoutSettings,
  CpScreenSlideout,
  type CpScreenSlideoutSettings,
  ElementEditorSlideout,
  type ElementEditorSlideoutSettings,
  containerSlideouts,
};
