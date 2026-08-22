import {firstFocusableElement} from '@craftcms/garnish';
import type {Tab} from './tab';
import type {Element as FldElement} from './element';

/**
 * WeakMaps replacing the legacy jQuery `$.data()` back-references the FLD used to
 * stash instances on DOM nodes (`fld-tab`, `fld-element`, `hud`, `cvd`), matching
 * how `@craftcms/garnish` replaced `$.data`. Plain `data-*` string/JSON values
 * stay on `element.dataset`.
 */

/** Legacy `$container.data('fld-tab', tab)` / `.data('fld-tab')`. */
export const fldTabData = new WeakMap<Element, Tab>();

/** Legacy `$container.data('fld-element', element)` / `.data('fld-element')`. */
export const fldElementData = new WeakMap<Element, FldElement>();

export interface FieldLayoutHud {
  $trigger: HTMLElement;
  hide(): void;
}

/** Legacy `$(hud.$hud).data('hud', hud)` — looked up via `getActiveHud()`. */
export const hudData = new WeakMap<Element, FieldLayoutHud>();

/** Legacy `$container.data('cvd', cvd)`. */
export const cvdData = new WeakMap<Element, any>();

/**
 * Native equivalent of jQuery `$(html)` — parse an HTML string and return its
 * first element. Uses a `<template>` so any tag (option, tr, …) parses.
 */
export function htmlToElement(html: string): HTMLElement {
  const template = document.createElement('template');
  template.innerHTML = html.trim();
  const element = template.content.firstElementChild;
  if (!(element instanceof HTMLElement)) {
    throw new Error('Expected the HTML fragment to contain an element.');
  }
  return element;
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
