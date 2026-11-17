import {html} from 'lit';
import {ref} from 'lit/directives/ref.js';
import {
  computeAccessibleDescription,
  computeAccessibleName,
} from 'dom-accessibility-api';
import type CraftSlidePickerButton from '../src/components/slide-picker-button/slide-picker-button.js';

/**
 * A slide picker button's name, role, state and description, as the
 * accessibility tree exposes them and a screen reader reads them on focus.
 */
function accessibilityTree(picker: CraftSlidePickerButton): string {
  const button = picker.shadowRoot?.querySelector<HTMLElement>('craft-button');
  if (!button) {
    return '';
  }
  const expanded = button.getAttribute('aria-expanded');
  const state =
    expanded === 'true' ? 'expanded' : expanded === 'false' ? 'collapsed' : '';
  return [
    computeAccessibleName(button),
    'button',
    state,
    computeAccessibleDescription(button),
  ]
    .filter(Boolean)
    .join(', ');
}

const watched = new WeakSet<Element>();

/**
 * Keeps the accessibility tree output in step with the buttons, as controls
 * change them, buttons come and go, or a popover opens.
 */
function watch(element?: Element): void {
  if (!element || watched.has(element)) {
    return;
  }
  watched.add(element);

  const content = element.querySelector('[data-content]')!;
  const list = element.querySelector('[data-accessibility-tree]')!;
  const observed = new WeakSet<ShadowRoot>();
  const update = () => {
    const pickers = [
      ...content.querySelectorAll<CraftSlidePickerButton>(
        'craft-slide-picker-button'
      ),
    ];
    for (const picker of pickers) {
      if (picker.shadowRoot && !observed.has(picker.shadowRoot)) {
        observed.add(picker.shadowRoot);
        observer.observe(picker.shadowRoot, {
          subtree: true,
          childList: true,
          characterData: true,
          attributeFilter: [
            'aria-expanded',
            'aria-labelledby',
            'aria-describedby',
          ],
        });
      }
    }
    list.replaceChildren(
      ...pickers.map((picker) => {
        const item = document.createElement('li');
        item.textContent = `“${accessibilityTree(picker)}”`;
        return item;
      })
    );
  };
  const observer = new MutationObserver(update);
  // Buttons come and go as controls change.
  observer.observe(content, {childList: true, subtree: true});
  // The ref runs before the content inside it renders, so wait a frame.
  requestAnimationFrame(update);
}

/**
 * Shows `content`, then the name, role, state and description of each slide
 * picker button in it, kept up to date. The output is hidden from screen
 * readers, which hear the buttons themselves.
 */
export function withAccessibilityTree(content: unknown) {
  return html`
    <div
      ${ref(watch)}
      style="display: grid; gap: var(--c-spacing-lg); max-width: 20rem;"
    >
      <div data-content>${content}</div>
      <div aria-hidden="true" style="color: var(--c-text-quiet);">
        <div>Accessibility tree</div>
        <ol
          data-accessibility-tree
          style="margin: var(--c-spacing-xs) 0 0; padding-inline-start: 1.25rem;"
        ></ol>
      </div>
    </div>
  `;
}
