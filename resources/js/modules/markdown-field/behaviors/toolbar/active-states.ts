import {markdownActions, type OverType as OverTypeInstance} from 'overtype';
import type {PreviewController} from '../preview';

const headingLevelByButton = {h4: 4, h5: 5, h6: 6};
const nonToggleButtonNames = ['asset', 'guide', 'link', 'upload'];
const stateAttributes = ['aria-controls', 'aria-expanded', 'aria-pressed'];

export function syncToolbarButtonStates(
  editor: OverTypeInstance,
  preview: PreviewController
): () => void {
  const sync = () => {
    for (const [buttonName, button] of Object.entries(
      editor.toolbar?.buttons ?? {}
    )) {
      if (
        button instanceof HTMLElement &&
        !nonToggleButtonNames.includes(buttonName) &&
        !button.hasAttribute('aria-pressed')
      ) {
        button.setAttribute('aria-pressed', 'false');
      }
    }

    const activeFormats = markdownActions.getActiveFormats(editor.textarea);
    syncPressedButton(editor, 'code', activeFormats.includes('code'));
    syncPressedButton(
      editor,
      'strikethrough',
      hasSurroundingMarker(editor.textarea, '~~')
    );

    const activeHeadingLevel = headingLevel(editor.textarea);
    for (const [buttonName, level] of Object.entries(headingLevelByButton)) {
      syncPressedButton(editor, buttonName, activeHeadingLevel === level);
    }

    syncPressedButton(editor, 'preview', preview.isActive());
    toolbarButton(editor, 'link')?.removeAttribute('aria-pressed');

    clearButtonState(editor, 'asset');
    clearButtonState(editor, 'upload');
  };

  editor.textarea.addEventListener('input', sync);
  editor.textarea.addEventListener('selectionchange', sync);
  sync();

  return () => {
    editor.textarea.removeEventListener('input', sync);
    editor.textarea.removeEventListener('selectionchange', sync);
  };
}

function syncPressedButton(
  editor: OverTypeInstance,
  buttonName: string,
  active: boolean
): void {
  const button = toolbarButton(editor, buttonName);

  button?.classList.toggle('active', active);
  button?.setAttribute('aria-pressed', active.toString());
}

function toolbarButton(
  editor: OverTypeInstance,
  buttonName: string
): HTMLElement | null {
  const button = editor.toolbar?.buttons?.[buttonName];

  return button instanceof HTMLElement ? button : null;
}

function headingLevel(textarea: HTMLTextAreaElement): number {
  const {selectionStart, value} = textarea;
  const lineStart =
    value.lastIndexOf('\n', Math.max(0, selectionStart - 1)) + 1;
  const nextLineBreak = value.indexOf('\n', selectionStart);
  const lineEnd = nextLineBreak === -1 ? value.length : nextLineBreak;

  return value.slice(lineStart, lineEnd).match(/^(#{1,6})\s/)?.[1]?.length ?? 0;
}

function hasSurroundingMarker(
  textarea: HTMLTextAreaElement,
  marker: string
): boolean {
  const {selectionEnd, selectionStart, value} = textarea;
  const beforeSelection = value.slice(0, selectionStart);
  const afterSelection = value.slice(selectionEnd);

  return (
    (beforeSelection.split(marker).length - 1) % 2 === 1 &&
    afterSelection.includes(marker)
  );
}

function clearButtonState(editor: OverTypeInstance, buttonName: string): void {
  const button = toolbarButton(editor, buttonName);

  button?.classList.remove('active');

  for (const attribute of stateAttributes) {
    button?.removeAttribute(attribute);
  }
}
