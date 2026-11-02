import './globals';
import {Slideout, type SlideoutSettings} from './slideout';
import {containerSlideouts} from './support';

/**
 * No custom element / `.ce.ts` here: slideouts are always constructed
 * programmatically around dynamic content — there's no server-rendered
 * markup for a custom element to boot from.
 */

// Repositioning open HUDs when a slideout opens or closes used to be hooked
// up here, via `Garnish.on(Slideout, 'open close')`. It now lives in the
// shared panel stack, so Vue slideouts trigger it too.

export {Slideout, type SlideoutSettings, containerSlideouts};
