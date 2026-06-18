import {firstFocusableElement} from '@craftcms/garnish';
import type {Tab} from './Tab';
import type {Element as FldElement} from './Element';

/**
 * Native replacements for the legacy jQuery `$.data()` back-references the FLD
 * used to stash object instances on DOM nodes. Each key the legacy code stored
 * (`fld-tab`, `fld-element`, `hud`, `cvd`) becomes a module-level WeakMap keyed
 * by the element, exactly mirroring how `@craftcms/garnish` replaced `$.data`.
 *
 * Simple string/JSON values that lived in `data-*` attributes are NOT here —
 * those are read straight off `element.dataset` (JSON-parsed where needed).
 */

/** Legacy `$container.data('fld-tab', tab)` / `.data('fld-tab')`. */
export const fldTabData = new WeakMap<Element, Tab>();

/** Legacy `$container.data('fld-element', element)` / `.data('fld-element')`. */
export const fldElementData = new WeakMap<Element, FldElement>();

/** Legacy `$(hud.$hud).data('hud', hud)` — looked up via `getActiveHud()`. */
export const hudData = new WeakMap<Element, any>();

/** Legacy `$container.data('cvd', cvd)`. */
export const cvdData = new WeakMap<Element, any>();

/**
 * Native equivalent of jQuery `$(html)` — parse an HTML string and return its
 * first element. Uses a `<template>` so any tag (option, tr, …) parses.
 */
export function htmlToElement(html: string): HTMLElement {
  const template = document.createElement('template');
  template.innerHTML = html.trim();
  return template.content.firstElementChild as HTMLElement;
}

/**
 * Native equivalent of jQuery `$(el).siblings().find(':focusable:first')` — the
 * first focusable element found among `el`'s siblings' descendants.
 */
export function firstFocusableInSiblings(el: Element): HTMLElement | null {
  const parent = el.parentElement;
  if (!parent) {
    return null;
  }
  for (const sibling of Array.from(parent.children)) {
    if (sibling === el) {
      continue;
    }
    const focusable = firstFocusableElement(sibling);
    if (focusable) {
      return focusable;
    }
  }
  return null;
}
