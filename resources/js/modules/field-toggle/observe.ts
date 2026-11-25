import {FieldToggle} from './field-toggle';
import {fieldToggleData} from './support';

const SELECTOR = '.fieldtoggle';

let observer: MutationObserver | null = null;

/**
 * Binds a {@link FieldToggle} to every `.fieldtoggle` already on the page and
 * to each one added later, so inserted markup needs no init call.
 */
export function observeFieldToggles(): void {
  if (observer) {
    return;
  }

  observer = new MutationObserver((records) => {
    for (const record of records) {
      record.addedNodes.forEach((node) => {
        if (node instanceof Element) {
          bindWithin(node);
        }
      });
    }
  });
  observer.observe(document.documentElement, {childList: true, subtree: true});

  bindWithin(document.documentElement);
}

function bindWithin(root: Element): void {
  if (root.matches(SELECTOR)) {
    bind(root);
  }
  root.querySelectorAll(SELECTOR).forEach(bind);
}

function bind(toggle: Element): void {
  // Moved toggles come through again, and some code binds its own first
  if (!fieldToggleData.has(toggle)) {
    new FieldToggle(toggle as HTMLElement);
  }
}
