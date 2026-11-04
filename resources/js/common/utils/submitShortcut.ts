import {t} from '@craftcms/ui/utilities/translate';
import {registerShortcut} from '@/common/shortcuts';

const textInputTypes = new Set([
  'text',
  'search',
  'email',
  'url',
  'tel',
  'password',
  'number',
]);

function isTextControl(
  target: EventTarget | undefined
): target is HTMLInputElement | HTMLTextAreaElement {
  return (
    target instanceof HTMLTextAreaElement ||
    (target instanceof HTMLInputElement && textInputTypes.has(target.type))
  );
}

/**
 * Submits the form that a text input or textarea belongs to when Ctrl/Command +
 * Enter is pressed in it. Handlers that claim the shortcut for themselves call
 * `preventDefault()`, and are left alone.
 */
export function submitFormOnCtrlEnter(event: KeyboardEvent): void {
  if (
    event.key !== 'Enter' ||
    (!event.ctrlKey && !event.metaKey) ||
    event.altKey ||
    event.isComposing ||
    event.defaultPrevented
  ) {
    return;
  }

  const path = event.composedPath();
  const [target] = path;
  if (!isTextControl(target) || target.disabled || target.readOnly) {
    return;
  }

  // Inputs in a custom element's shadow root don't know their form, but the form is in the path.
  const form =
    target.form ??
    path.find(
      (node): node is HTMLFormElement => node instanceof HTMLFormElement
    );
  if (!form) {
    return;
  }

  event.preventDefault();
  form.requestSubmit();
}

export function registerSubmitShortcut(): void {
  registerShortcut(
    'Mod+Enter',
    (event) => {
      submitFormOnCtrlEnter(event);

      // It prevents the default itself, when it submits.
      return false;
    },
    {
      description: () => t('Submit the form a text field is in'),
      group: () => t('Forms'),
    }
  );
}
