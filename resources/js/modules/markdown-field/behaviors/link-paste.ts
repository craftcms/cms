import type {OverType as OverTypeInstance} from 'overtype';
import type {PreviewController} from './preview';
import {isModifierKeyPressed} from './utilities';

const linkProtocols = ['http:', 'https:', 'mailto:', 'tel:'];

export function registerLinkPasteBehavior(
  editor: OverTypeInstance,
  preview: PreviewController
): () => void {
  let pasteAsPlainText = false;
  let pasteAsPlainTextTimeout: number | null = null;

  function clearPasteAsPlainText(): void {
    pasteAsPlainText = false;

    if (pasteAsPlainTextTimeout) {
      window.clearTimeout(pasteAsPlainTextTimeout);
      pasteAsPlainTextTimeout = null;
    }
  }

  function allowNextPasteAsPlainText(): void {
    pasteAsPlainText = true;

    if (pasteAsPlainTextTimeout) {
      window.clearTimeout(pasteAsPlainTextTimeout);
    }

    // Cmd/Ctrl+Shift+V should skip link formatting, but only for the paste
    // event that immediately follows this keydown.
    pasteAsPlainTextTimeout = window.setTimeout(() => {
      pasteAsPlainText = false;
      pasteAsPlainTextTimeout = null;
    }, 1000);
  }

  function insertLinkAtSelection(url: string): void {
    const {selectionEnd, selectionStart, value} = editor.textarea;
    const selectedText = value.slice(selectionStart, selectionEnd);
    const markdown = `[${selectedText}](${url})`;

    editor.textarea.setRangeText(markdown, selectionStart, selectionEnd, 'end');

    if (!selectedText) {
      editor.textarea.setSelectionRange(selectionStart + 1, selectionStart + 1);
    }

    editor.textarea.dispatchEvent(new Event('input', {bubbles: true}));

    if (preview.isActive()) {
      void preview.render(editor.getValue());
    }
  }

  function handlePaste(event: ClipboardEvent): void {
    if (pasteAsPlainText) {
      clearPasteAsPlainText();

      return;
    }

    const pastedText = event.clipboardData?.getData('text/plain').trim();

    if (!pastedText || !isUrl(pastedText)) {
      return;
    }

    event.preventDefault();
    insertLinkAtSelection(pastedText);
  }

  function handleKeydown(event: KeyboardEvent): void {
    if (
      isModifierKeyPressed(event) &&
      event.key.toLowerCase() === 'v' &&
      event.shiftKey
    ) {
      allowNextPasteAsPlainText();
    }
  }

  function isUrl(value: string): boolean {
    try {
      const url = new URL(value);

      return linkProtocols.includes(url.protocol);
    } catch {
      return false;
    }
  }

  editor.textarea.addEventListener('keydown', handleKeydown);
  editor.textarea.addEventListener('paste', handlePaste);

  return () => {
    clearPasteAsPlainText();
    editor.textarea.removeEventListener('keydown', handleKeydown);
    editor.textarea.removeEventListener('paste', handlePaste);
  };
}
