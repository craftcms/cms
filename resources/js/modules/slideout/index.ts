import {Garnish, HUD, isCtrlKeyPressed} from '@craftcms/garnish';
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

// `craft:edit-element` — the action-system event carried by element edit
// buttons (see `ElementHtml::cardEditButtonConfig()`). Opens an element
// editor slideout for the element, or its edit page in a new window on
// ctrl-click. Contexts that need richer editor wiring (e.g. the nested
// element manager's draft handling) strip the button's `action` and attach
// their own behavior instead.
window.addEventListener('craft:edit-element', ((ev: CustomEvent) => {
  const {elementType, settings, cpEditUrl, trigger, sourceEvent} =
    ev.detail ?? {};

  if (cpEditUrl && sourceEvent && isCtrlKeyPressed(sourceEvent)) {
    window.open(cpEditUrl);
    return;
  }

  // Focus the trigger so that when the slideout is closed, focus is
  // returned to it.
  (trigger as HTMLElement | undefined)?.focus();

  const editorSettings = {...(settings ?? {}), elementType};
  // If the settings have a draftId but the (possibly since-replaced) card no
  // longer has a `data-draft-id` attribute, drop it so the editor retrieves
  // the current element.
  if (
    editorSettings.draftId &&
    trigger instanceof Element &&
    !trigger.closest('[data-draft-id]')
  ) {
    delete editorSettings.draftId;
  }

  const slideout = new ElementEditorSlideout(null, editorSettings);

  // Re-announce saves as a window event, so hosts that render element data
  // themselves (e.g. Inertia pages) can refresh it.
  slideout.on('submit', (submitEv: any) => {
    window.dispatchEvent(
      new CustomEvent('craft:element-saved', {
        detail: {element: submitEv?.response?.data?.element ?? null},
      })
    );
  });
}) as EventListener);

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
