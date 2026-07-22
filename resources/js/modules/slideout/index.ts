import {Garnish, HUD} from '@craftcms/garnish';
import {compatify} from '@craftcms/garnish/compat';
import {Slideout, type SlideoutSettings} from './slideout';
import {containerSlideouts} from './support';

/**
 * No custom element / `.ce.ts` here, unlike most other ports: a slideout is
 * always constructed programmatically around dynamic (often server-fetched,
 * see `Craft.CpScreenSlideout`) content — `new Craft.Slideout(...)`,
 * `new Craft.CpScreenSlideout(action, settings)`, etc. — never booted from
 * server-rendered markup a custom element could wrap. The absence of a `.ce.ts`
 * is therefore the intentional "shim" for this module, the same way
 * `component-select` deliberately has no `window.Craft.*` assignment.
 */

// `compatify`, not the plain class: `Craft.CpScreenSlideout` still subclasses
// `Craft.Slideout` via the legacy `Garnish.Base.extend({...}, {...})` API and
// calls `this.base(contents, settings)` from its own `init` — see
// `slideout.ts`'s class docblock and `packages/craftcms-legacy/cp/src/js/CpScreenSlideout.js`.
const CompatSlideout = compatify(Slideout);

// Mirror the static API to the compatified constructor. `defaults` is a plain
// object reference (plugins mutate it directly, e.g. `Craft.Slideout.defaults.x
// = …`, so sharing the reference is correct); `instances`/`openPanels` are
// shared mutable state that `slideout.ts`'s `removePanel`/`destroy` mutate
// IN PLACE (splice/delete, not reassignment) specifically so these references
// never go stale. The static methods are copied by reference too — they close
// over `Slideout` (the modern class), not `this`, so no `.bind()` is needed.
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

// Legacy class-level hook (was `Garnish.on(Craft.Slideout, ['open', 'close'], …)`):
// reposition any open HUDs whenever a slideout opens or closes. Registered
// against the MODERN `Slideout` class, not the compatified constructor —
// `Base.trigger`'s class-event dispatch does an `instanceof` check
// (`packages/craftcms-garnish/src/events.ts`'s `ClassEventBus.dispatch`), and
// `compatify()` builds `Craft.CpScreenSlideout` etc. as REAL subclasses (native
// prototype chain), so their instances still satisfy `instanceof Slideout` —
// this fires for every legacy-extended slideout too.
Garnish.on(Slideout, 'open close', () => {
  // Legacy pages have their own `Garnish.HUD` with its own instance registry,
  // separate from the modern class — sweep both so legacy HUDs reposition too.
  const legacyInstances = (window as any).Garnish?.HUD?.instances ?? [];
  for (const hud of [...HUD.instances, ...legacyInstances]) {
    if (hud.showing) {
      hud.updateSizeAndPosition(true);
    }
  }
});

export {Slideout, type SlideoutSettings, containerSlideouts};
