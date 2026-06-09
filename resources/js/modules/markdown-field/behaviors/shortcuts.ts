import type {OverType as OverTypeInstance} from 'overtype';
import type {PreviewController} from './preview';
import {isModifierKeyPressed} from './utilities';

export function registerShortcutBehavior(
  editor: OverTypeInstance,
  preview: PreviewController
): () => void {
  async function handleKeydown(event: KeyboardEvent): Promise<void> {
    if (event.key === 'Tab') {
      event.stopPropagation();

      return;
    }

    if (!isModifierKeyPressed(event)) {
      return;
    }

    const action = shortcutAction(event);

    if (action === null) {
      return;
    }

    event.preventDefault();

    if (action === 'indent') {
      indentSelection(editor.textarea);

      return;
    }

    if (action === 'outdent') {
      outdentSelection(editor.textarea);

      return;
    }

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

type ShortcutAction =
  | 'indent'
  | 'outdent'
  | 'toggleCode'
  | 'togglePreview'
  | 'toggleQuote';

function shortcutAction(event: KeyboardEvent): ShortcutAction | null {
  const key = event.key.toLowerCase();

  if (key === ']') {
    return 'indent';
  }

  if (key === '[') {
    return 'outdent';
  }

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

function indentSelection(textarea: HTMLTextAreaElement): void {
  replaceSelectedLines(textarea, (line) => `  ${line}`);
}

function outdentSelection(textarea: HTMLTextAreaElement): void {
  replaceSelectedLines(textarea, (line) => line.replace(/^( {1,2}|\t)/, ''));
}

function replaceSelectedLines(
  textarea: HTMLTextAreaElement,
  transformLine: (line: string) => string
): void {
  const {selectionEnd, selectionStart, value} = textarea;
  const lineStart = value.lastIndexOf('\n', selectionStart - 1) + 1;
  const lineEndOffset = value.indexOf(
    '\n',
    effectiveSelectionEnd(value, selectionStart, selectionEnd)
  );
  const lineEnd = lineEndOffset === -1 ? value.length : lineEndOffset;
  const replacement = value
    .slice(lineStart, lineEnd)
    .split('\n')
    .map(transformLine)
    .join('\n');

  textarea.setRangeText(replacement, lineStart, lineEnd, 'preserve');
  textarea.dispatchEvent(new Event('input', {bubbles: true}));
}

function effectiveSelectionEnd(
  value: string,
  selectionStart: number,
  selectionEnd: number
): number {
  return selectionEnd > selectionStart && value[selectionEnd - 1] === '\n'
    ? selectionEnd - 1
    : selectionEnd;
}
