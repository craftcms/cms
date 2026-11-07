/**
 * The system clipboard, as the control panel's clipboards use it.
 *
 * Copied elements and a copied field value go on the system clipboard as
 * text, and as HTML, for other apps to paste. The control panel keeps one
 * thing on it at a time, so copying one claims the clipboard from the other.
 *
 * Nothing is read back from the system clipboard. A paste only pastes what
 * the control panel copied while the clipboard still holds the text it was
 * copied as, so another page can't put something in a field.
 */

import {escapeHtml} from '@craftcms/ui/utilities/escapeHtml';

/** Something copied, as other apps take it from the clipboard. */
export interface ClipboardFormats {
  text: string;
  /** Only when it says more than the text does. */
  html: string | null;
}

/** Who has the control panel's clipboard. */
export type ClipboardOwner = 'elements' | 'fieldValue';

const claimListeners = new Set<(owner: ClipboardOwner) => void>();

/** Lets the other clipboards know `owner` has the clipboard now. */
export function claimClipboard(owner: ClipboardOwner): void {
  claimListeners.forEach((listener) => listener(owner));
}

/**
 * Calls `listener` whenever something is copied. Returns a function that
 * stops listening.
 */
export function onClipboardClaimed(
  listener: (owner: ClipboardOwner) => void
): () => void {
  claimListeners.add(listener);

  return () => claimListeners.delete(listener);
}

/**
 * Puts `formats` on the system clipboard. Called while the user's click is
 * being handled, which the system clipboard needs, even when the formats are
 * still on their way, such as elements' titles.
 */
export function writeSystemClipboard(
  formats: ClipboardFormats | Promise<ClipboardFormats | null>
): void {
  const clipboard = navigator.clipboard as Clipboard | undefined;

  if (!clipboard) {
    return;
  }

  const ready = Promise.resolve(formats).then((value) => {
    // An empty value would only empty the clipboard.
    if (!value?.text) {
      throw new Error('Nothing to copy.');
    }

    return value;
  });

  const written =
    typeof ClipboardItem !== 'undefined'
      ? clipboard.write([
          new ClipboardItem({
            'text/plain': ready.then(
              ({text}) => new Blob([text], {type: 'text/plain'})
            ),
            'text/html': ready.then(
              ({text, html}) =>
                new Blob([html ?? escapeHtml(text).replace(/\n/g, '<br>')], {
                  type: 'text/html',
                })
            ),
          }),
        ])
      : ready.then(({text}) => clipboard.writeText(text));

  written.catch(() => {
    // It can still be pasted within the control panel from its paste buttons.
  });
}

/**
 * Whether text pasted from the system clipboard is what something was copied
 * as, give or take the line endings other apps and systems use.
 */
export function isCopiedText(copied: string, pasted: string): boolean {
  const normalize = (value: string) =>
    value.replace(/\r\n?/g, '\n').replace(/\n+$/, '');

  return copied !== '' && normalize(pasted) === normalize(copied);
}

/** Elements as text, a title per line, and as links to their pages. */
export function elementFormats(elements: unknown[]): ClipboardFormats {
  const presentations = elements.flatMap((element) => {
    const {label, url} = (element ?? {}) as {label?: unknown; url?: unknown};

    return typeof label === 'string' && label
      ? [{label, url: typeof url === 'string' && url ? url : null}]
      : [];
  });

  return {
    text: presentations.map(({label}) => label).join('\n'),
    // Front-end URLs only: an element's edit URL would give away the control
    // panel's address wherever it's pasted.
    html: presentations.some(({url}) => url)
      ? presentations
          .map(({label, url}) =>
            url
              ? `<a href="${escapeHtml(url)}">${escapeHtml(label)}</a>`
              : escapeHtml(label)
          )
          .join('<br>')
      : null,
  };
}

/** Inputs that don't take typed text, so a paste on them isn't theirs. */
const NON_TEXT_INPUT_TYPES = new Set([
  'button',
  'checkbox',
  'color',
  'file',
  'image',
  'radio',
  'range',
  'reset',
  'submit',
]);

function takesText(element: HTMLElement): boolean {
  return (
    element.isContentEditable ||
    element instanceof HTMLTextAreaElement ||
    (element instanceof HTMLInputElement &&
      !NON_TEXT_INPUT_TYPES.has(element.type))
  );
}

/**
 * Whether a paste lands on `target` itself, rather than on something text is
 * typed into within it, which pastes text as usual, or on a field nested in
 * it, which pastes into that field.
 */
export function pastesInto(event: Event, target: Element | null): boolean {
  for (const element of event.composedPath()) {
    if (element === target) {
      return true;
    }

    if (
      element instanceof HTMLElement &&
      (element.localName === 'craft-field' || takesText(element))
    ) {
      return false;
    }
  }

  return false;
}
