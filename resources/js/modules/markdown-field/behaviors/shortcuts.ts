import type {OverType as OverTypeInstance} from 'overtype';
import type {PreviewController} from './preview';
import {isModifierKeyPressed} from './utilities';

export function registerShortcutBehavior(
  editor: OverTypeInstance,
  preview: PreviewController
): () => void {
  async function handleKeydown(event: KeyboardEvent): Promise<void> {
    if (event.defaultPrevented || !isModifierKeyPressed(event)) {
      return;
    }

    const action = shortcutAction(event);

    if (action === null) {
      return;
    }

    event.preventDefault();

    if (action === 'togglePreview') {
      await preview.toggle();

      return;
    }

    await editor.performAction(action, event);
  }

  const keydownListener = (event: KeyboardEvent) => void handleKeydown(event);

  editor.textarea.addEventListener('keydown', keydownListener);

  return () => {
    editor.textarea.removeEventListener('keydown', keydownListener);
  };
}

type ShortcutAction = 'toggleCode' | 'togglePreview' | 'toggleQuote';

function shortcutAction(event: KeyboardEvent): ShortcutAction | null {
  const key = event.key.toLowerCase();

  if (key === 'e' && !event.shiftKey) {
    return 'toggleCode';
  }

  if (key === '.' && event.shiftKey) {
    return 'toggleQuote';
  }

  if (key === 'p' && event.shiftKey) {
    return 'togglePreview';
  }

  return null;
}
