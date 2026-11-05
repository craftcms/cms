/**
 * Whether the text before the caret opens a fenced code block that it
 * doesn't close, so the caret is inside it.
 */
function caretInCodeFence(before: string): boolean {
  let fence = 0;

  for (const line of before.split('\n')) {
    if (!fence) {
      fence = line.match(/^ {0,3}(`{3,})[^`]*$/)?.[1]?.length ?? 0;
    } else if ((line.match(/^ {0,3}(`+)\s*$/)?.[1]?.length ?? 0) >= fence) {
      fence = 0;
    }
  }

  return fence > 0;
}

/**
 * Whether Return sends what's in a Markdown message box, such as a comment or
 * a message to Ask AI, rather than starting a new line. Shift + Return starts
 * a new line. So does Return within a fenced code block, on a list item,
 * where Markdown fields continue the list, and on touch screens, whose
 * keyboards have no Shift + Return. Ctrl/Command + Return always sends.
 *
 * `input` is the message box's textarea.
 */
export function returnSends(
  event: KeyboardEvent,
  input: HTMLTextAreaElement | null
): boolean {
  if (
    event.key !== 'Enter' ||
    event.shiftKey ||
    event.altKey ||
    event.isComposing
  ) {
    return false;
  }

  if (event.ctrlKey || event.metaKey) {
    return true;
  }

  if (window.matchMedia?.('(pointer: coarse)').matches) {
    return false;
  }

  const value = input?.value ?? '';
  const caret = input?.selectionStart ?? value.length;
  const before = value.slice(0, caret);
  const line =
    before.slice(before.lastIndexOf('\n') + 1) +
    value.slice(caret).split('\n', 1)[0];

  return !caretInCodeFence(before) && !/^\s*(?:[-*+]|\d+\.)\s/.test(line);
}
