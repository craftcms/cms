import {markdownActions, type OverType as OverTypeInstance} from 'overtype';

const unsupportedActiveButtons = {
  code: (editor: OverTypeInstance) => {
    return markdownActions.getActiveFormats(editor.textarea).includes('code');
  },
  strikethrough: (editor: OverTypeInstance) => {
    return hasSurroundingMarker(editor.textarea, '~~');
  },
};

export function syncUnsupportedToolbarButtonStates(
  editor: OverTypeInstance
): () => void {
  const sync = () => {
    for (const [buttonName, isActive] of Object.entries(
      unsupportedActiveButtons
    )) {
      const button = editor.toolbar?.buttons?.[buttonName];

      if (!(button instanceof HTMLElement)) {
        continue;
      }

      const active = isActive(editor);

      button.classList.toggle('active', active);
      button.setAttribute('aria-pressed', active.toString());
    }
  };

  editor.textarea.addEventListener('input', sync);
  editor.textarea.addEventListener('selectionchange', sync);
  sync();

  return () => {
    editor.textarea.removeEventListener('input', sync);
    editor.textarea.removeEventListener('selectionchange', sync);
  };
}

function hasSurroundingMarker(
  textarea: HTMLTextAreaElement,
  marker: string
): boolean {
  const {selectionEnd, selectionStart, value} = textarea;
  const beforeSelection = value.slice(0, selectionStart);
  const afterSelection = value.slice(selectionEnd);

  return (
    markerCount(beforeSelection, marker) % 2 === 1 &&
    afterSelection.indexOf(marker) !== -1
  );
}

function markerCount(value: string, marker: string): number {
  return value.split(marker).length - 1;
}
