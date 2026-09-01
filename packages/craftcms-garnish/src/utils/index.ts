/**
 * Re-export barrel for all Garnish core utilities.
 */

export {
  coerceElements,
  getElement,
  hasAttr,
  nearestSibling,
  closestRegistered,
  getOffset,
  getOuterWidth,
  getOuterHeight,
  hitTest,
  isCursorOver,
  copyTextStyles,
} from './dom';

export {getScrollParent, isWindowScrollContainer} from './scroll';

export {
  isFocusable,
  isKeyboardFocusable,
  getFocusableElements,
  isVisible,
} from './focusable';

export {
  focusIsInside,
  firstFocusableElement,
  getKeyboardFocusableElements,
  getFocusedElement,
  trapFocusWithin,
  releaseFocusWithin,
  setFocusWithin,
} from './focus';

export {
  addModalAttributes,
  isScriptOrStyleElement,
  hasJsAriaClass,
  ariaHide,
  hideModalBackgroundLayers,
  resetModalBackgroundLayerVisibility,
} from './aria';

export {
  getInputBasename,
  getInputPostVal,
  findInputs,
  getPostData,
  copyInputValues,
} from './forms';

export {
  requestAnimationFrame,
  cancelAnimationFrame,
  prefersReducedMotion,
  getUserPreferredAnimationDuration,
  scrollContainerToElement,
  shake,
} from './animation';

export {
  isMobileBrowser,
  isPrimaryClick,
  isCtrlKeyPressed,
  getBodyScrollTop,
} from './env';

export {
  getDist,
  within,
  isString,
  isArray,
  isPlainObject,
  isTextNode,
  deferUntil,
  log,
  handleActivatingKeypress,
} from './misc';
