/**
 * Garnish custom DOM events — native installers replacing `$.event.special`.
 */

export {installActivate} from './activate';
export {installTextchange, type TextchangeOptions} from './textchange';
export {installResize} from './resize';

export interface ActivateOptions {
  /* none today */
}
