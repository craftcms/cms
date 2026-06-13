import type {OverType as OverTypeInstance} from 'overtype';

export function syncToolbarKeyboardNavigation(
  editor: OverTypeInstance
): () => void {
  const toolbar = editor.toolbar?.container;

  if (!(toolbar instanceof HTMLElement)) {
    return () => {};
  }

  if (editor.textarea.id) {
    toolbar.setAttribute('aria-controls', editor.textarea.id);
  }

  let activeItem = toolbarItemsForKeyboard(toolbar)[0] ?? null;

  const sync = () => {
    const items = toolbarItemsForKeyboard(toolbar);

    if (!items.length) {
      return;
    }

    if (!activeItem || !items.includes(activeItem)) {
      const firstItem = items[0];

      if (!firstItem) {
        return;
      }

      activeItem = firstItem;
    }

    for (const item of items) {
      item.tabIndex = item === activeItem ? 0 : -1;
    }
  };

  const focusItem = (item: HTMLElement) => {
    activeItem = item;
    sync();
    item.focus();
  };

  const moveFocus = (direction: 1 | -1) => {
    const items = toolbarItemsForKeyboard(toolbar);

    if (!items.length) {
      return;
    }

    const currentIndex = Math.max(
      0,
      items.indexOf(document.activeElement as HTMLElement)
    );
    const nextIndex = (currentIndex + direction + items.length) % items.length;
    const nextItem = items[nextIndex];

    if (nextItem) {
      focusItem(nextItem);
    }
  };

  const handleFocus = (event: FocusEvent) => {
    const item = event.target;

    if (!(item instanceof HTMLElement) || !isToolbarKeyboardItem(item)) {
      return;
    }

    activeItem = item;
    sync();
  };

  const handleKeydown = (event: KeyboardEvent) => {
    if (!isToolbarKeyboardItem(event.target)) {
      return;
    }

    switch (event.key) {
      case 'ArrowLeft':
        event.preventDefault();
        moveFocus(-1);
        break;
      case 'ArrowRight':
        event.preventDefault();
        moveFocus(1);
        break;
      case 'Home':
        event.preventDefault();
        focusFirstItem(toolbar);
        break;
      case 'End':
        event.preventDefault();
        focusLastItem(toolbar);
        break;
    }
  };

  toolbar.addEventListener('focusin', handleFocus);
  toolbar.addEventListener('keydown', handleKeydown);
  sync();

  return () => {
    toolbar.removeEventListener('focusin', handleFocus);
    toolbar.removeEventListener('keydown', handleKeydown);
    toolbar.removeAttribute('aria-controls');

    for (const item of toolbarItemsForKeyboard(toolbar)) {
      item.removeAttribute('tabindex');
    }
  };
}

function focusFirstItem(toolbar: HTMLElement): void {
  const item = toolbarItemsForKeyboard(toolbar)[0];

  if (item) {
    item.focus();
  }
}

function focusLastItem(toolbar: HTMLElement): void {
  const items = toolbarItemsForKeyboard(toolbar);
  const item = items[items.length - 1];

  if (item) {
    item.focus();
  }
}

function toolbarItemsForKeyboard(toolbar: HTMLElement): HTMLElement[] {
  return Array.from(toolbar.querySelectorAll<HTMLElement>('[data-button]'))
    .filter(isToolbarKeyboardItem)
    .filter((item) => !item.matches(':disabled'));
}

function isToolbarKeyboardItem(item: EventTarget | null): item is HTMLElement {
  return (
    item instanceof HTMLElement && item.closest('[role="toolbar"]') !== null
  );
}
