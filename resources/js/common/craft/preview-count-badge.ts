import {loadElementThumbs} from '@/modules/element-thumb-loader/globals';

const SELECTOR = '[data-preview-count-badge]';

/**
 * The "+3" button after a preview's first chip swaps itself for the rest of
 * the chips (in its `data-other` JSON) when pressed, keeping focus in place.
 */
function expand(event: Event): void {
  const badge = (event.target as Element | null)?.closest<HTMLElement>(
    SELECTOR
  );

  if (!badge?.dataset.other) {
    return;
  }

  if (event instanceof KeyboardEvent) {
    if (event.key !== ' ' && event.key !== 'Enter') {
      return;
    }
    // Space would scroll the page too
    event.preventDefault();
  }

  const parent = badge.parentElement;
  const template = document.createElement('template');
  template.innerHTML = JSON.parse(badge.dataset.other) as string;
  const first = template.content.firstElementChild;
  const loadThumbs = badge.dataset.previewCountBadge === 'thumbs';
  badge.replaceWith(template.content);

  if (parent && loadThumbs) {
    loadElementThumbs(parent);
  }

  (first?.matches('a, button, [tabindex]')
    ? (first as HTMLElement)
    : first?.querySelector<HTMLElement>('a, button, [tabindex]')
  )?.focus();
}

document.addEventListener('click', expand);
document.addEventListener('keydown', expand);
