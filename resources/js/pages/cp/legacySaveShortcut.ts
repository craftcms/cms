import {t} from '@craftcms/ui/utilities/translate';
import {useShortcut} from '@/common/shortcuts';

/** A Craft 5 form action, as `_layouts/cp` takes them in `formActions`. */
export interface LegacyFormAction {
  label?: string;
  action?: string;
  redirect?: string;
  confirm?: string;
  params?: Record<string, string>;
  data?: Record<string, unknown>;
  retainScroll?: boolean;
  shortcut?: boolean;
  shift?: boolean;
}

export interface LegacySaveShortcutProps {
  fullPageForm?: boolean;
  mainFormAttributes?: {data?: Record<string, unknown>} | null;
  formActions?: Array<LegacyFormAction> | null;
  saveShortcutRedirect?: string | null;
}

type SubmitOptions = Omit<LegacyFormAction, 'label' | 'shortcut' | 'shift'>;

interface SaveShortcut {
  label?: string;
  options: SubmitOptions;
}

/**
 * The save shortcuts a Craft 5 `#main-form` declared, keyed by whether Shift
 * is held.
 */
export function legacySaveShortcuts(
  props: LegacySaveShortcutProps
): Map<boolean, SaveShortcut> {
  const shortcuts = new Map<boolean, SaveShortcut>();
  const data = props.mainFormAttributes?.data ?? {};

  if (!props.fullPageForm || data.saveshortcut === false) {
    return shortcuts;
  }

  const actions = Array.isArray(data.actions)
    ? (data.actions as Array<LegacyFormAction>)
    : props.formActions;

  if (!actions?.length) {
    const redirect =
      data['saveshortcut-redirect'] || props.saveShortcutRedirect;
    shortcuts.set(false, {
      options: {
        redirect: typeof redirect === 'string' ? redirect : undefined,
        retainScroll: Boolean(data['saveshortcut-scroll']),
      },
    });

    return shortcuts;
  }

  for (const {label, shortcut, shift, ...options} of actions) {
    if (shortcut && !shortcuts.has(Boolean(shift))) {
      shortcuts.set(Boolean(shift), {label, options});
    }
  }

  return shortcuts;
}

/**
 * Submits `#main-form` the way Craft 5's save shortcut did. The legacy CP
 * does it when it's loaded, so its `beforeSaveShortcut` listeners still run.
 */
export function submitLegacyForm(options: SubmitOptions): void {
  const cp = (window as any).Craft?.cp;

  if (cp?.submitPrimaryForm && cp.$primaryForm?.length) {
    cp.submitPrimaryForm(options);
    return;
  }

  const form = document.getElementById('main-form');

  if (!(form instanceof HTMLFormElement)) {
    return;
  }

  if (options.confirm && !window.confirm(options.confirm)) {
    return;
  }

  const params: Record<string, string | undefined> = {
    action: options.action,
    redirect: options.redirect,
    ...options.params,
  };

  for (const [name, value] of Object.entries(params)) {
    if (value === undefined || value === null) {
      continue;
    }

    const input = document.createElement('input');
    input.type = 'hidden';
    input.name = name;
    input.value = String(value);
    form.append(input);
  }

  form.requestSubmit();
}

/**
 * Registers <kbd>Mod</kbd> + <kbd>S</kbd> (and <kbd>Mod</kbd> + <kbd>Shift</kbd>
 * + <kbd>S</kbd>) for a bridged Craft 5 screen's full-page form.
 */
export function useLegacySaveShortcut(
  props: () => LegacySaveShortcutProps
): void {
  for (const shift of [false, true]) {
    const current = () => legacySaveShortcuts(props()).get(shift);

    useShortcut(
      shift ? 'Mod+Shift+S' : 'Mod+S',
      () => {
        const shortcut = current();

        if (!shortcut) {
          return false;
        }

        submitLegacyForm(shortcut.options);
      },
      {
        description: () => current()?.label || t('Save'),
        group: () => t('Forms'),
        isAvailable: () => current() !== undefined,
      }
    );
  }
}
