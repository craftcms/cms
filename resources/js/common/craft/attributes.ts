import {isPlainObject} from './params';

/** Escapes text for use in HTML. `null` and `undefined` escape to nothing. */
export function escapeHtml(str: unknown): string {
  const div = document.createElement('div');
  div.textContent = String(str ?? '');

  return div.innerHTML;
}

// Properties jQuery's `css()` leaves unitless when given a bare number.
const UNITLESS = new Set([
  'animationIterationCount',
  'aspectRatio',
  'borderImageSlice',
  'columnCount',
  'flexGrow',
  'flexShrink',
  'fontWeight',
  'gridArea',
  'gridColumn',
  'gridColumnEnd',
  'gridColumnStart',
  'gridRow',
  'gridRowEnd',
  'gridRowStart',
  'lineHeight',
  'opacity',
  'order',
  'orphans',
  'scale',
  'widows',
  'zIndex',
  'zoom',
  'fillOpacity',
  'floodOpacity',
  'stopOpacity',
  'strokeMiterlimit',
  'strokeOpacity',
]);

function setStyle(element: HTMLElement, styles: Record<string, unknown>) {
  for (const [name, value] of Object.entries(styles)) {
    const camel = name.replace(/-([a-z])/g, (_m, c: string) => c.toUpperCase());
    const kebab = name.startsWith('--')
      ? name
      : camel.replace(/[A-Z]/g, (c) => `-${c.toLowerCase()}`);
    const css =
      typeof value === 'number' && !UNITLESS.has(camel)
        ? `${value}px`
        : String(value ?? '');

    element.style.setProperty(kebab, css);
  }
}

function dataAttributes(): string[] {
  return (
    (window as {Craft?: {dataAttributes?: string[]}}).Craft?.dataAttributes ?? [
      'aria',
      'data',
      'data-ng',
      'ng',
    ]
  );
}

/**
 * Sets attributes the way the server renders them: `null` and `false` remove
 * one, `true` sets it empty, and `data`/`aria` objects expand to one attribute
 * per key.
 */
export function setElementAttributes(
  element: Element | ArrayLike<Element> | string,
  attributes: Record<string, unknown>
): void {
  const elements =
    typeof element === 'string'
      ? Array.from(document.querySelectorAll(element))
      : element instanceof Element
        ? [element]
        : Array.from(element);

  for (const el of elements) {
    setAttributes(el, attributes);
  }
}

function setAttributes(el: Element, attributes: Record<string, unknown>) {
  for (const [name, value] of Object.entries(attributes)) {
    if (value === null || value === false) {
      el.removeAttribute(name);
    } else if (value === true) {
      el.setAttribute(name, '');
    } else if (Array.isArray(value) || isPlainObject(value)) {
      if (dataAttributes().includes(name)) {
        for (const [key, sub] of Object.entries(Object.assign({}, value))) {
          if (sub === null || sub === false) {
            continue;
          }
          const subValue =
            isPlainObject(sub) || Array.isArray(sub)
              ? JSON.stringify(sub)
              : sub === true
                ? ''
                : escapeHtml(sub);
          el.setAttribute(`${name}-${key}`, subValue);
        }
      } else if (name === 'class') {
        const classes: string[] = [];
        for (const c of Array.isArray(value) ? value : Object.values(value)) {
          classes.push(...String(c).split(/\s+/).filter(Boolean));
        }
        el.classList.add(...classes);
      } else if (name === 'style') {
        if (el instanceof HTMLElement) {
          setStyle(el, {...(value as Record<string, unknown>)});
        }
      } else {
        el.setAttribute(name, JSON.stringify(value));
      }
    } else {
      el.setAttribute(name, escapeHtml(value));
    }
  }
}
