import type CraftDialog from '../components/dialog/dialog.js';
import {createTextInput} from './create-text-input.js';

export interface CopyTextPromptConfig {
  /** Shown as the dialog's header/accessible name (e.g. "Full URL"). */
  label?: string;
  /** The value to display and copy. */
  value: string;
  id?: string;
  /** Render a multi-line `<textarea>` instead of a single-line input. */
  textarea?: boolean;
  /** Rows for the textarea variant. */
  rows?: number;
  size?: number;
  class?: string | string[];
}

/**
 * Opens a modal dialog showing `value` in a read-only field with a
 * copy-to-clipboard button — the jQuery-free twin of the legacy
 * `Craft.ui.createCopyTextPrompt`.
 *
 * Built entirely on `@craftcms/ui` components: `<craft-dialog>` (the modal) and
 * `<craft-copy-button>` (which copies via `navigator.clipboard` and shows its
 * own feedback, replacing the legacy `execCommand('copy')` + `displayNotice`).
 * The dialog is appended to `<body>` and shown immediately; it closes once the
 * value is copied. Returns the dialog element (callers use it fire-and-forget).
 */
export function createCopyTextPrompt(
  config: CopyTextPromptConfig
): CraftDialog {
  const dialog = document.createElement('craft-dialog') as CraftDialog;
  if (config.label) {
    dialog.setAttribute('label', config.label);
  }

  const copytext = document.createElement('div');
  copytext.className = 'copytext';
  copytext.append(buildField(config));

  const copyButton = document.createElement('craft-copy-button');
  copyButton.setAttribute('value', config.value);
  copytext.append(copyButton);

  dialog.append(copytext);

  // Legacy hid the modal once the value was copied.
  dialog.addEventListener('craft-copy', () => {
    dialog.opened = false;
  });

  dialog.setAttribute('open', '');
  document.body.appendChild(dialog);

  return dialog;
}

function buildField(config: CopyTextPromptConfig): HTMLElement {
  if (config.textarea) {
    const textarea = document.createElement('textarea');
    const classes = ['text'];
    if (Array.isArray(config.class)) {
      classes.push(...config.class);
    } else if (config.class) {
      classes.push(config.class);
    }
    if (config.size === undefined) {
      classes.push('fullwidth');
    }
    textarea.className = classes.join(' ');
    textarea.readOnly = true;
    textarea.rows = config.rows ?? 2;
    if (config.id) {
      textarea.id = config.id;
    }
    textarea.value = config.value;
    return textarea;
  }

  return createTextInput({
    id: config.id,
    value: config.value,
    readonly: true,
    // Legacy sizing: fit the value, clamped to [25, 50].
    size: config.size ?? Math.max(Math.min(config.value.length, 50), 25),
    class: config.class,
  });
}
